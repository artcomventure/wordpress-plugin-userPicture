<?php

/**
 * Plugin Name: User Picture
 * Plugin URI: https://github.com/artcomventure/wordpress-plugin-userPicture
 * Description: Set user's profile picture in WordPress
 * Version: 1.0.1
 * Text Domain: userpicture
 * Author: artcom venture GmbH
 * Author URI: http://www.artcom-venture.de/
 */

/**
 * Load plugin's textdomain.
 */
add_action( 'after_setup_theme', 'userpicture_t9n' );
function userpicture_t9n() {
	load_theme_textdomain( 'userpicture', plugin_dir_path( __FILE__ ) . 'languages' );
}

/**
 * Add user picture image size.
 * get_avatar() uses max 96px, so I make this one 96px.
 * Wanna change!? @See https://github.com/artcomventure/wordpress-plugin-imageSizes
 */
add_action( 'after_setup_theme', 'userpicture_image_size' );
function userpicture_image_size() {
	add_image_size( 'userpicture', 96, 96, true );
}

/**
 * Enqueue admin script.
 */
add_action( 'admin_enqueue_scripts', 'userpicture_admin_scripts' );
function userpicture_admin_scripts( $hook ) {
	global $pagenow;

	// ... but only for profile pages.
	if ( ! in_array( $pagenow, array(
		'profile.php',
		'user-edit.php',
		'user-new.php'
	) )
	) {
		return;
	}

	wp_enqueue_media(); // media uploader
	wp_enqueue_script( 'user-picture-profile', plugin_dir_url( __FILE__ ) . '/js/profile.admin.js', array(), false, true );
}

/**
 * Duplicate AND extend WP's avatar section.
 * Will be replacing original @see ./js/profile.admin.js
 */
add_action( 'show_user_profile', 'user_profile_picture' );
add_action( 'edit_user_profile', 'user_profile_picture' );
function user_profile_picture( $profileuser ) { ?>

	<table class="form-table">
		<tbody>

		<?php if ( get_option( 'show_avatars' ) ) : ?>
			<tr class="user-picture-wrap">
				<th><?php _e( 'Profile Picture' ); ?></th>
				<td>
					<?php user_profile_picture_html( $profileuser->ID ); ?>
				</td>
			</tr>
		<?php endif; ?>

		</tbody>
	</table>
<?php }

/**
 * Display profile picture input html.
 *
 * @param $user_id
 * @param int|null $attachment_id
 */
add_action( 'wp_ajax_get-user-picture-html', 'user_profile_picture_html' );
function user_profile_picture_html( $user_id, $attachment_id = NULL ) {
	if ( isset( $_POST['user_id'] ) ) {
		$user_id = intval( $_POST['user_id'] );
	}

	if ( isset( $_POST['attachment_id'] ) ) {
		$attachment_id = intval( $_POST['attachment_id'] );
	}

	$return = get_user_profile_picture_html( $user_id, $attachment_id );

	if ( wp_doing_ajax() ) {
		wp_send_json_success( $return );
	}

	echo $return;
}

/**
 * Retrieve profile picture input html.
 *
 * @param $user_id
 * @param int|null $attachment_id
 *
 * @return string
 */
function get_user_profile_picture_html( $user_id, $attachment_id = NULL ) {
	if ( is_null( $attachment_id ) ) {
		$attachment_id = get_user_meta( $user_id, 'avatar', true );
	}

	$avatar = wp_get_attachment_image( $attachment_id, 'userpicture', false, array(
		'class' => 'change-picture'
	) );

	// user picture or avatar
	$html = $avatar ? $avatar : get_avatar( $user_id, 96, '', '', array( 'force_display' => true ) );
	// input field to save attament id with @see save_userpicture()
	$html .= '<input type="hidden" value="' . $attachment_id . '" name="avatar" />';
	// set/remove button
	$html .= $avatar
		? ' <a href="#remove-picture" class="button">' . __( 'Remove Profile Picture', 'userpicture' ) . '</a>'
		: ' <a href="#change-picture" class="button change-picture">' . __( 'Set Profile Picture', 'userpicture' ) . '</a>';

	$description = sprintf( __( 'You can also change your profile picture on <a href="%s">Gravatar</a>. But the WordPress\' picture always has priority.', 'userpicture' ), __( 'https://en.gravatar.com/' ) );
	$html .= '<p>' . apply_filters( 'user_profile_picture_description', $description, get_user_by( 'ID', $user_id ) ) . '</p>';

	return $html;
}

/**
 * Save user picture in user meta.
 *
 * @param $user_id
 */
add_action( 'personal_options_update', 'save_userpicture' );
add_action( 'edit_user_profile_update', 'save_userpicture' );
function save_userpicture( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	if ( isset( $_POST['avatar'] ) ) {
		update_user_meta( $user_id, 'avatar', $_POST['avatar'] );
	} else {
		delete_user_meta( $user_id, 'avatar' );
	}
}

/**
 * Override gravatar's avatar with user picture.
 *
 * @param string $avatar
 * @param mixed $id_or_email
 * @param int $size
 * @param string $default
 * @param string $alt
 * @param array $args
 */
add_filter( 'get_avatar', 'get_user_avatar', 10, 6 );
function get_user_avatar( $avatar, $id_or_email, $size, $default, $alt, $args ) {
	if ( ! $args['force_display'] && ( $picture = get_user_picture( $id_or_email ) ) ) {
		preg_match_all( '/(width|height)="(\d*)"/', $picture, $matches );

		// switch avatar classes to user picture
		if ( preg_match( "/class='[^']*'/", $avatar, $classes ) ) {
			$picture = preg_replace( '/class="[^"]*"/', str_replace( "'", '"', $classes[0] ), $picture );
		}

		// change WP's image size attributes to requested $size
		if ( count( $matches[0] ) == 1 ) {
			$avatar = preg_replace( '/' . $matches[1][0] . '="\d*"/', $matches[1][0] . '="' . $size . '"', $picture );
		} // fixes height and calculated (aspect ratio) width
		else if ( $matches[0] ) {
			$sizes                   = array();
			$sizes[ $matches[1][0] ] = $matches[2][0];
			$sizes[ $matches[1][1] ] = $matches[2][1];
			ksort( $sizes );

			$avatar = preg_replace( '/height="\d*"/', 'height="' . $size . '"', $picture );
			$avatar = preg_replace( '/width="\d*"/', 'width="' . ( $sizes['width'] * $size / $sizes['height'] ) . '"', $avatar );
		}
	}

	return $avatar;
}

/**
 * Remove srcset and sizes attributes for user picture.
 *
 * @param array $attr
 * @param WP_POST $attachment
 * @param string $size
 *
 * @return array
 */
add_filter( 'wp_get_attachment_image_attributes', 'userpicture_image_attributes', 10, 3 );
function userpicture_image_attributes( $attr, $attachment, $size ) {
	if ( $size == 'userpicture' ) {
		unset( $attr['srcset'], $attr['sizes'] );
	}

	return $attr;
}

/**
 * Retrieve the user picture.
 * Detached from default gravatar.
 * Wrapper function for WP's wp_get_attachment_image().
 *
 * @param int|string $id_or_email
 * @param string $size
 * @param bool $icon
 * @param string $attr
 *
 * @return string
 */
function get_user_picture( $id_or_email, $size = 'userpicture', $icon = false, $attr = '' ) {
	if ( $attachment_id = get_user_picture_id( $id_or_email, 'avatar', true ) ) {
		return wp_get_attachment_image( $attachment_id, $size, $icon, $attr );
	}

	return '';
}

/**
 * Retrieve the user picture's url.
 *
 * @param int|string $id_or_email
 * @param string $size
 * @param bool $icon
 *
 * @return string
 */
function get_user_picture_url( $id_or_email, $size = 'userpicture', $icon = false ) {
	if ( $attachment_id = get_user_picture_id( $id_or_email ) ) {
		return wp_get_attachment_image_url( $attachment_id, $size, $icon );
	}

	return '';
}

/**
 * Retrieve attachment id of user picture.
 *
 * @param int|string $id_or_email
 *
 * @return mixed
 */
function get_user_picture_id( $id_or_email ) {
	if ( is_object( $id_or_email ) && isset( $id_or_email->comment_ID ) ) {
		$id_or_email = get_comment( $id_or_email )->user_id;
	}

	if ( is_numeric( $id_or_email ) ) {
		$user = get_user_by( 'id', absint( $id_or_email ) );
	} else {
		$user = get_user_by( 'email', $id_or_email );
	}

	if ( $user ) return get_user_meta( $user->ID, 'avatar', true );

	return false;
}

/**
 * Remove update notification (since this plugin isn't listed on https://wordpress.org/plugins/).
 */
add_filter( 'site_transient_update_plugins', 'remove_userpicture_update_notification' );
function remove_userpicture_update_notification( $value ) {
	$plugin_file = plugin_basename( __FILE__ );

	if ( isset( $value->response[ $plugin_file ] ) ) {
		unset( $value->response[ $plugin_file ] );
	}

	return $value;
}

/**
 * Change details link to GitHub repository.
 */
add_filter( 'plugin_row_meta', 'userpicture_plugin_row_meta', 10, 2 );
function userpicture_plugin_row_meta( $links, $file ) {
	if ( plugin_basename( __FILE__ ) == $file ) {
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $file );

		$links[2] = '<a href="' . $plugin_data['PluginURI'] . '">' . __( 'Visit plugin site' ) . '</a>';
	}

	return $links;
}

/**
 * Delete all plugin traces on deactivation.
 */
add_action( 'wp_ajax_imagesizes_reset', 'userpicture_deactivate' );
add_action( 'wp_ajax_nopriv_imagesizes_reset', 'userpicture_deactivate' );
register_deactivation_hook( __FILE__, 'userpicture_deactivate' );
function userpicture_deactivate() {
	delete_metadata( 'user', 0, 'avatar', '', true );
}

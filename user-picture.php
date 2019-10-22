<?php

/**
 * Plugin Name: User Picture
 * Plugin URI: https://github.com/artcomventure/wordpress-plugin-userPicture
 * Description: Set user's profile picture in WordPress
 * Version: 1.1.1
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
 * Default user picture meta key.
 * @since 1.1.0
 */
add_filter( 'userpicture_meta_key', function( $meta_key ) {
	return $meta_key ?: 'avatar';
}, 19820511 );

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
		$attachment_id = get_user_meta( $user_id, apply_filters( 'userpicture_meta_key', '' ), true );
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
		update_user_meta( $user_id, apply_filters( 'userpicture_meta_key', '' ), $_POST['avatar'] );
	} else {
		delete_user_meta( $user_id, apply_filters( 'userpicture_meta_key', '' ) );
	}
}

/**
 * Retrieve the avatar `<img>` tag for a user, email address, MD5 hash, comment, or post.
 * Original @see `/wp-includes/pluggable.php`
 *
 * On activation WP's `get_avatar()` is loaded prior this file
 * so userpicture's one needs to be conditionally defined.
 * Once activated THIS `get_avatar()` is used!
 */
if ( ! function_exists( 'get_avatar' ) ) :
	function get_avatar( $id_or_email, $size = 96, $default = '', $alt = '', $args = null ) {
		$defaults = array(
			'size'          => 96,
			'height'        => null,
			'width'         => null,
			'default'       => get_option( 'avatar_default', 'mystery' ),
			'force_default' => false,
			'rating'        => get_option( 'avatar_rating' ),
			'scheme'        => null,
			'alt'           => '',
			'class'         => null,
			'force_display' => false,
			'extra_attr'    => '',
		);

		if ( empty( $args ) ) {
			$args = array();
		}

		// try to get size from WP's image size
		if ( !is_numeric($size) ) {
			global $_wp_additional_image_sizes;

			if ( in_array( $size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
				$args['width'] = get_option( "{$size}_size_w" );
				$args['height'] = get_option( "{$size}_size_h" );
				$args['size'] = $args['width'] ?: $args['height'];
			} elseif ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
				$args['width'] = $_wp_additional_image_sizes[ $size ]['width'];
				$args['height'] = $_wp_additional_image_sizes[ $size ]['height'];
				$args['size'] = $args['width'] ?: $args['height'];
			}
			else $size = 96; // default
		}
		else $args['size'] = (int) $size;

		$args['default'] = $default;
		$args['alt']     = $alt;

		$args = wp_parse_args( $args, $defaults );

		if ( empty( $args['height'] ) ) {
			$args['height'] = $args['size'];
		}
		if ( empty( $args['width'] ) ) {
			$args['width'] = $args['size'];
		}

		if ( is_object( $id_or_email ) && isset( $id_or_email->comment_ID ) ) {
			$id_or_email = get_comment( $id_or_email );
		}

		/**
		 * Filters whether to retrieve the avatar URL early.
		 *
		 * Passing a non-null value will effectively short-circuit get_avatar(), passing
		 * the value through the {@see 'get_avatar'} filter and returning early.
		 *
		 * @since 4.2.0
		 *
		 * @param string $avatar      HTML for the user's avatar. Default null.
		 * @param mixed  $id_or_email The Gravatar to retrieve. Accepts a user_id, gravatar md5 hash,
		 *                            user email, WP_User object, WP_Post object, or WP_Comment object.
		 * @param array  $args        Arguments passed to get_avatar_url(), after processing.
		 */
		$avatar = apply_filters( 'pre_get_avatar', null, $id_or_email, $args );

		if ( ! is_null( $avatar ) ) {
			/** This filter is documented in wp-includes/pluggable.php */
			return apply_filters( 'get_avatar', $avatar, $id_or_email, $args['size'], $args['default'], $args['alt'], $args );
		}

		if ( ! $args['force_display'] && ! get_option( 'show_avatars' ) ) {
			return false;
		}

		// try to get user picture
		if ( $attachment_id = get_user_meta( $id_or_email, apply_filters( 'userpicture_meta_key', '' ), true ) ) {
			if ( !$picture = image_get_intermediate_size( $attachment_id, $size ) ) // image size
				$picture = image_get_intermediate_size( $attachment_id, array($size, $size) ); // explicit dimensions
			$args['url'] = $picture['url'];
			$args['width'] = $picture['width'];
			$args['height'] = $picture['height'];
			$args['found_avatar'] = true;
		}
		else { // ... otherwise take gravatar
			$url2x = get_avatar_url( $id_or_email, array_merge( $args, array( 'size' => $args['size'] * 2 ) ) );
			$url2x = esc_url( $url2x ) . ' 2x';
			$args = get_avatar_data( $id_or_email, $args );
		}

		$url = $args['url'];

		if ( ! $url || is_wp_error( $url ) ) {
			return false;
		}

		$class = array( 'avatar', 'avatar-' . $size, 'photo' );

		if ( ! $args['found_avatar'] || $args['force_default'] ) {
			$class[] = 'avatar-default';
		}

		if ( $args['class'] ) {
			if ( is_array( $args['class'] ) ) {
				$class = array_merge( $class, $args['class'] );
			} else {
				$class[] = $args['class'];
			}
		}

		$avatar = sprintf(
			"<img alt='%s' src='%s' srcset='%s' class='%s' height='%d' width='%d' %s/>",
			esc_attr( $args['alt'] ),
			esc_url( $url ),
			isset($url2x) ? $url2x : '',
			esc_attr( join( ' ', $class ) ),
			(int) $args['height'],
			(int) $args['width'],
			$args['extra_attr']
		);

		/**
		 * Filters the avatar to retrieve.
		 *
		 * @since 2.5.0
		 * @since 4.2.0 The `$args` parameter was added.
		 *
		 * @param string $avatar      &lt;img&gt; tag for the user's avatar.
		 * @param mixed  $id_or_email The Gravatar to retrieve. Accepts a user_id, gravatar md5 hash,
		 *                            user email, WP_User object, WP_Post object, or WP_Comment object.
		 * @param int    $size        Square avatar width and height in pixels to retrieve.
		 * @param string $default     URL for the default image or a default type. Accepts '404', 'retro', 'monsterid',
		 *                            'wavatar', 'indenticon','mystery' (or 'mm', or 'mysteryman'), 'blank', or 'gravatar_default'.
		 *                            Default is the value of the 'avatar_default' option, with a fallback of 'mystery'.
		 * @param string $alt         Alternative text to use in the avatar image tag. Default empty.
		 * @param array  $args        Arguments passed to get_avatar_data(), after processing.
		 */
		return apply_filters( 'get_avatar', $avatar, $id_or_email, $args['size'], $args['default'], $args['alt'], $args );
	}
endif;

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
	if ( $attachment_id = get_user_picture_id( $id_or_email ) ) {
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

	if ( $user ) return get_user_meta( $user->ID, apply_filters( 'userpicture_meta_key', '' ), true );

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
//add_action( 'wp_ajax_imagesizes_reset', 'userpicture_on_uninstall' );
//add_action( 'wp_ajax_nopriv_imagesizes_reset', 'userpicture_on_uninstall' );
//register_deactivation_hook( __FILE__, 'userpicture_on_uninstall' );
register_uninstall_hook( __FILE__, 'userpicture_on_uninstall' );
function userpicture_on_uninstall() {
	delete_metadata( 'user', 0, apply_filters( 'userpicture_meta_key', '' ), '', true );
}

;
(function ( $ ) {

    var $userPictureWrap = $( 'tr.user-picture-wrap' );
    // replace default avatar display with plugin's
    $( 'tr.user-profile-picture' ).replaceWith( $userPictureWrap );
    var mediaUploader = wp.media( {
        title: 'Choose User Picture',
        button: {
            text: 'Choose Picture'
        }, multiple: false
    } ).on( 'select', function () {
        var selection = mediaUploader.state().get( 'selection' );

        wp.media.post( 'get-user-picture-html', {
            user_id: $( '#user_id' ).val(),
            attachment_id: selection.length ? selection.first().toJSON().id : ''
        } ).done( function ( html ) {
            if ( html == '0' ) {
                window.alert( 'Could not set featured post slider. Please try again.' );
            }
            else $( 'td', $userPictureWrap ).html( html );
        } );
    } ).on( 'open', function () {
        var attachment = wp.media.attachment( $( '[name="avatar"]' ).val() );
        // preselect current picture
        if ( attachment.fetch() ) mediaUploader.state().get( 'selection' ).add( attachment );
    } );

    // change/remove selected user picture
    $userPictureWrap.on( 'click', '.change-picture', function ( e ) {
        e.preventDefault();
        mediaUploader.open();
    } ).on( 'click', 'a[href="#remove-picture"]', function ( e ) {
        e.preventDefault();
        mediaUploader.open().close().state().get( 'selection' ).add( [] );
        mediaUploader.trigger( 'select' );
    } );

})( jQuery );
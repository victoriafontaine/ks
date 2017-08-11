<?php
/**
 * 1.7.6
 *
 * Make sure all existing accounts that use public_content are forced to be reauthed
 *
 */

$accounts = instagrate_pro()->accounts->get_accounts();

if ( ! isset( $accounts ) || ! is_array( $accounts ) ) {
	return;
}

foreach ( $accounts as $key => $account ) {
	$options = get_post_meta( $key, '_instagrate_pro_settings', true );
	$stream  = Instagrate_Pro_Helper::setting( 'instagram_images', 'recent', $options );

	if ( ! in_array( $stream, array( 'recent', 'feed' ) ) ) {
		// Stream is using public_content
		$options['needs_reconnect'] = 1;

		update_post_meta( $key, '_instagrate_pro_settings', $options );
	}
}
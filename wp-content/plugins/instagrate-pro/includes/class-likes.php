<?php

/**
 * Likes Class
 *
 * @package     instagrate-pro
 * @subpackage  likes
 * @copyright   Copyright (c) 2014, polevaultweb
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
 */
class Instagrate_Pro_Likes {

	function __construct() {
		add_shortcode( 'igp-likes', array( $this, 'get_likes' ) );
		add_action( 'wp_ajax_igp_sync_likes', array( $this, 'ajax_sync_likes' ) );
	}

	/**
	 * Get likes shortcode
	 *
	 * @param      $atts
	 * @param null $content
	 *
	 * @return mixed
	 */
	public function get_likes( $atts, $content = null ) {
		extract( shortcode_atts( array(), $atts ) );
		global $post;
		$likes = get_post_meta( $post->ID, 'ig_likes', true );

		return $likes;
	}

	/**
	 * Sync likes from Instagram across posts that have been created
	 *
	 * @param int $account_id
	 */
	public function sync_likes( $account_id = 0 ) {
		$access_token = '';
		//get all likes count from instagram on images in _igp_images table
		$acc_where = '';
		if ( $account_id == 0 ) {
			$accounts = instagrate_pro()->accounts->get_accounts( 'publish' );
			if ( isset( $accounts ) && $accounts && count( $accounts ) > 0 ) {
				$account_id = key( $accounts );
			}
		} else {
			$acc_where = 'AND account_id = ' . $account_id;
		}
		if ( $account_id == 0 ) {
			return;
		}

		$account_settings = get_post_meta( $account_id, '_instagrate_pro_settings', true );
		$settings         = (object) $account_settings;
		if ( isset( $settings->token ) && $settings->token != '' ) {
			$access_token = $settings->token;
		}

		if ( $access_token == '' ) {
			return;
		}

		global $wpdb;
		$table  = instagrate_pro()->images->get_table_name();
		$images = $wpdb->get_results( "SELECT * FROM $table WHERE status = 'posted' $acc_where" );
		foreach ( $images as $image ) {
			$image_id = $image->image_id;
			$media    = instagrate_pro()->instagram->get_media( $access_token, $image_id );
			$likes    = $media->likes->count;
			$wpdb->query( "UPDATE $table SET likes_count = $likes WHERE image_id = '$image_id'" );
		}
		$meta_table = $wpdb->prefix . 'postmeta';
		$post_table = $wpdb->prefix . 'posts';
		//sync those like counts across posts that have been created
		$wpdb->query(
			"UPDATE $meta_table a
				INNER JOIN $meta_table b
					ON a.post_id = b.post_id
					AND b.meta_key = '_igp_id'
						INNER JOIN $table c
						ON b.meta_value = c.id
				SET a.meta_value = c.likes_count
				WHERE a.meta_key = 'ig_likes'"
		);
	}

	function ajax_sync_likes() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['post_id'] ) ) {
			return 0;
		}
		$response['error']    = false;
		$response['redirect'] = '';
		instagrate_pro()->likes->sync_likes( $_POST['post_id'] );
		$redirect             = get_admin_url() . 'edit.php?post_type=instagrate_pro&message=15';
		$response['redirect'] = $redirect;
		echo json_encode( $response );
		die;
	}
}
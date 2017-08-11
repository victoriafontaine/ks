<?php

/**
 * Comments Class
 *
 * @package     instagrate-pro
 * @subpackage  comments
 * @copyright   Copyright (c) 2014, polevaultweb
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
 */
class Instagrate_Pro_Comments {


	function __construct() {
		add_filter( 'get_avatar', array( $this, 'instagram_avatar' ), 10, 5 );
		add_filter( 'get_comment_text', array( $this, 'comment_text' ), 10, 2 );

		add_action( 'wp_ajax_igp_sync_comments', array( $this, 'ajax_sync_comments' ) );
		add_action( 'wp_ajax_nopriv_instagram_sync', array( $this, 'ajax_sync_all_comments_likes' ) );
	}

	/**
	 * Import comments from Instagram and load as WP comments for an image and post
	 *
	 * @param      $access_token
	 * @param      $comments
	 * @param      $image_id
	 * @param      $post_id
	 * @param      $id
	 * @param bool $sync
	 */
	public function import_comments( $access_token, $comments, $image_id, $post_id, $id, $sync = false ) {
		global $wpdb;
		if ( $comments == '' ) {
			$comments = instagrate_pro()->accounts->get_comments( $access_token, $image_id );
			$data     = array(
				'comments' => ( isset( $comments ) ) ? base64_encode( serialize( $comments ) ) : array(),
			);
			$where    = array( 'id' => $id );
			$wpdb->update( instagrate_pro()->images->get_table_name(), $data, $where );
		}

		$meta_table = $wpdb->prefix . 'commentmeta';

		if ( ! is_array( $comments ) ) {
			return;
		}

		foreach ( $comments as $comment ) {
			$querystr = "	SELECT count(*)
							FROM $meta_table m
							WHERE m.meta_key = '_igp_comment_id'
							AND m.meta_value = '$comment->id'	";
			$exists   = $wpdb->get_var( $querystr );

			if ( $exists > 0 ) {
				continue;
			}

			// set comment data
			$data = array(
				'comment_post_ID'      => $post_id,
				'comment_author'       => $comment->from->username,
				'comment_author_email' => '@instagram_igp',
				'comment_author_url'   => 'http://instagram.com/' . $comment->from->username,
				'comment_content'      => instagrate_pro()->helper->clean_caption( $comment->text ),
				'comment_type'         => '',
				'comment_parent'       => 0,
				'user_id'              => 0,
				'comment_author_IP'    => '127.0.0.1',
				'comment_agent'        => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10 (.NET CLR 3.5.30729)',
				'comment_date'         => date( 'Y-m-d H:i:s', $comment->created_time ),
				'comment_approved'     => Instagrate_Pro_Helper::setting( 'igpsettings_comments_auto-approve', '0' ),
			);

			$comment_id = wp_insert_comment( $data );

			//set comment meta ig comment id
			add_comment_meta( $comment_id, '_igp_comment_id', $comment->id, true );
			//set comment meta with user image url
			add_comment_meta( $comment_id, '_igp_comment_avatar', $comment->from->profile_picture, true );

		}
	}

	/**
	 * Sync comments for all posts associated with an account
	 *
	 * @param int $account_id
	 */
	function sync_comments( $account_id = 0 ) {

		if ( Instagrate_Pro_Helper::setting( 'igpsettings_comments_enable-comments', '0' ) == 0 ) {
			return;
		}

		$access_token = '';
		$acc_where    = '';
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
			//get posts with the ig_id
			$meta_table = $wpdb->prefix . 'postmeta';
			$querystr   = "	SELECT post_id
							FROM $meta_table m
							WHERE m.meta_key = '_igp_id'
							AND m.meta_value = '$image->id'	";

			$posts = $wpdb->get_results( $querystr, OBJECT );

			foreach ( $posts as $post ) {
				$this->import_comments( $access_token, '', $image->image_id, $post->post_id, $image->id, true );
			}
		}
	}

	function ajax_sync_comments() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['post_id'] ) ) {
			return 0;
		}
		$response['error']    = false;
		$response['redirect'] = '';
		instagrate_pro()->comments->sync_comments( $_POST['post_id'] );
		$redirect             = get_admin_url() . 'edit.php?post_type=instagrate_pro&message=16';
		$response['redirect'] = $redirect;
		echo json_encode( $response );
		die;
	}

	/**
	 * Display Instagram avatar for comment poster
	 *
	 * @filter get_avatar
	 *
	 * @param $avatar
	 * @param $id_or_email
	 * @param $size
	 * @param $default
	 * @param $alt
	 *
	 * @return string
	 */
	function instagram_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
		if ( Instagrate_Pro_Helper::setting( 'igpsettings_comments_avatar', '1' ) == 0 ) {
			return $avatar;
		}
		$comment = $id_or_email;
		if ( ! isset( $comment->comment_ID ) ) {
			return $avatar;
		}
		$return    = $avatar;
		$ig_avatar = get_comment_meta( $comment->comment_ID, '_igp_comment_avatar', true );
		if ( $ig_avatar ) {
			$return = "<img alt='{$comment->comment_author} profile image' src='{$ig_avatar}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
		}

		return $return;
	}

	/**
	 * Filter the comment text to replace usernames with links
	 *
	 * @param $content
	 * @param $comment
	 *
	 * @return mixed
	 */
	public function comment_text( $content, $comment = '' ){
		if ( Instagrate_Pro_Helper::setting( 'igpsettings_comments_mentions', '1' ) == 0 ) {
			return $content;
		}

		$igp_comment = get_comment_meta( $comment->comment_ID, '_igp_comment_id', true );
		if ( $igp_comment ) {
			$content = preg_replace( "/@(\w+)/", "<a href=\"http://instagram.com/\\1\" target=\"_blank\">@\\1</a>", $content );
		}

		return $content;
	}

	function ajax_sync_all_comments_likes() {
		instagrate_pro()->comments->sync_comments();
		instagrate_pro()->likes->sync_likes();
		exit;
	}
}
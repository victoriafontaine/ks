<?php

/**
 * Images Database Class
 *
 * @package     instagrate-pro
 * @subpackage  images
 * @copyright   Copyright (c) 2014, polevaultweb
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
 */
class Instagrate_Pro_DB_Images extends Instagrate_Pro_DB {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.6
	 */
	public function __construct() {
		$this->table_name        = 'igp_images';
		$this->option_key        = 'igp_db_version';
		$this->version           = '1.6.2';
	}

	/**
	 * Create the table
	 *
	 * @access  public
	 * @since   1.6
	 */
	public function create_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		if ( version_compare( $this->installed_version(), $this->version, '!=' ) ) {

			$sql = "CREATE TABLE {$this->get_table_name()} (
				  id int(11) unsigned NOT NULL AUTO_INCREMENT,
				  account_id bigint(20) NOT NULL,
				  image_id varchar(256) NOT NULL,
				  image_timestamp bigint(20) NOT NULL,
				  status enum('pending','posted','ignore','posting', 'moderate') NOT NULL,
				  media_type varchar(50) NOT NULL,
				  image_url varchar(256) NOT NULL,
				  image_thumb_url varchar(256) NOT NULL,
				  video_url varchar(256) NOT NULL,
				  tags text NULL,
				  filter varchar(256) NULL,
				  link varchar(256) NULL,
				  caption text NULL,
				  caption_clean text NULL,
				  caption_clean_no_tags text NULL,
				  username text NULL,
				  user_id varchar(256) NULL,
				  user_image_url text NULL,
				  latitude varchar(256) NULL,
				  longitude varchar(256) NULL,
				  location_name text NULL,
				  location_id varchar(256) NULL,
				  comments_count bigint(20) NOT NULL,
				  comments longblob NOT NULL,
				  likes_count bigint(20) NOT NULL,
				  UNIQUE KEY (id)
			) DEFAULT CHARACTER SET utf8;";

			dbDelta( $sql );

			global $wpdb;
			if ( 'utf8mb4' === $wpdb->charset && function_exists( 'maybe_convert_table_to_utf8mb4' ) ){
				maybe_convert_table_to_utf8mb4( $this->get_table_name() );
			}

			update_option( $this->option_key, $this->version );
		}
	}

	/**
	 * Check that the image exists for a certain account
	 *
	 * @param $account_id
	 * @param $image_id
	 *
	 * @return bool
	 */
	public function check_account_image_exists( $account_id, $image_id ) {
		global $wpdb;
		$wpdb->get_results( "SELECT * FROM {$this->get_table_name()} WHERE account_id = $account_id AND image_id = '$image_id'" );
		if ( $wpdb->num_rows > 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Delete all images for an account
	 *
	 * @param $account_id
	 */
	public function delete_images( $account_id ) {
		global $wpdb;
		$wpdb->query(
			"DELETE FROM {$this->get_table_name()}
				WHERE account_id = $account_id"
		);
	}

	/**
	 * Get total images for an account
	 *
	 * @param $account_id
	 *
	 * @return mixed
	 */
	public function images_total( $account_id ) {
		global $wpdb;
		$stats = $wpdb->get_results(
			"	SELECT COUNT(*) AS Total
										FROM {$this->get_table_name()}
										WHERE account_id = $account_id"
		);

		return $stats;
	}

	/**
	 * Update any image that has already been posted by another account to ignore
	 *
	 * @param $account_id
	 */
	function update_duplicate_images( $account_id ) {
		global $wpdb;
		$wpdb->query(
			"UPDATE {$this->get_table_name()} a
					INNER JOIN {$this->get_table_name()} b
				 	ON a.image_id = b.image_id
				 	AND b.status = 'posted'
				 	AND a.account_id != b.account_id
				SET a.status = 'ignore'
				WHERE a.account_id = $account_id
				and a.status = 'pending'"
		);
	}

	/**
	 * Duplicate images when duplicating an account
	 *
	 * @param $old_account_id
	 * @param $new_account_id
	 */
	public function duplicate_images( $old_account_id, $new_account_id ) {
		global $wpdb;
		$sql =
			"	INSERT INTO {$this->get_table_name()}
						(
							account_id
							,image_id
							  ,image_timestamp
							  ,status
							  ,media_type
							  ,image_url
							  ,image_thumb_url
							  ,tags
							  ,filter
							  ,link
							  ,caption
							  ,caption_clean
							  ,caption_clean_no_tags
							  ,username
							  ,user_id
							  ,user_image_url
							  ,latitude
							  ,longitude
							  ,location_name
							  ,location_id
							  ,comments_count
							  ,comments
							  ,likes_count
						)
					SELECT 	$new_account_id
							  ,image_id
							  ,image_timestamp
							  ,status
							  ,media_type
							  ,image_url
							  ,image_thumb_url
							  ,tags
							  ,filter
							  ,link
							  ,caption
							  ,caption_clean
							  ,caption_clean_no_tags
							  ,username
							  ,user_id
							  ,user_image_url
							  ,latitude
							  ,longitude
							  ,location_name
							  ,location_id
							  ,comments_count
							  ,comments
							  ,likes_count
					FROM {$this->get_table_name()}
					WHERE account_id = $old_account_id
				";

		$wpdb->query( $sql );
	}

	/**
	 * Get images for an account
	 *
	 * @param        $account_id
	 * @param string $status
	 * @param string $order
	 * @param bool   $exclude_other_accounts
	 * @param string $locked
	 * @param string $limit
	 * @param string $offset
	 *
	 * @return mixed
	 */
	public function get_images( $account_id, $status = '', $order = 'DESC', $exclude_other_accounts = false, $locked = '', $limit = '', $offset = '' ) {
		global $wpdb;
		if ( $status != '' ) {
			$status = 'AND status = "' . $status . '"';
		}
		$exclude = ( $exclude_other_accounts ) ? "AND image_id NOT IN (SELECT image_id FROM {$this->get_table_name()} WHERE account_id <> $account_id AND status = 'posted')" : '';

		if ( $limit != '' ) {
			$limit = ' LIMIT ' . $limit;
		}
		if ( $offset != '' ) {
			$limit = ' LIMIT 20 OFFSET ' . $offset;
		}

		$images = $wpdb->get_results( "SELECT * FROM {$this->get_table_name()} WHERE account_id = $account_id $status $exclude ORDER BY image_timestamp $order$limit" );

		if ( $locked != '' ) {
			$meta = array( 'status' => $locked );
			foreach ( $images as $img ) {
				$this->save_image_meta( $img->image_id, $img->account_id, $meta );
			}
		}

		return $images;
	}

	/**
	 * Get image meta
	 *
	 * @param $image_id
	 * @param $account_id
	 *
	 * @return mixed
	 */
	public function get_image_meta( $image_id, $account_id ) {
		global $wpdb;
		$meta                        = $wpdb->get_row( "SELECT * FROM {$this->get_table_name()} WHERE account_id = $account_id AND image_id = '$image_id'" );
		$meta->caption_clean         = $meta->caption_clean;
		$meta->caption_clean_no_tags = $meta->caption_clean_no_tags;
		$meta->tags                  = unserialize( $meta->tags );

		return $meta;
	}

	/**
	 * Save image meta
	 *
	 * @param $image_id
	 * @param $account_id
	 * @param $meta
	 */
	public function save_image_meta( $image_id, $account_id, $meta ) {
		global $wpdb;
		$wpdb->update(
			$this->get_table_name(),
			$meta,
			array(
				'account_id' => $account_id,
				'image_id'   => $image_id
			)
		);
	}

	/**
	 * Edit image status in bulk
	 *
	 * @param        $account_id
	 * @param        $status
	 * @param string $images
	 */
	function bulk_edit_status( $account_id, $status, $images = '' ) {
		global $wpdb;
		$images = "'" . str_replace( ",", "','", $images ) . "'";
		$wpdb->query(
			"UPDATE {$this->get_table_name()} SET status = '$status' WHERE account_id = $account_id AND image_id IN ($images)"
		);
	}

	function bulk_toggle_status( $account_id, $from_status, $to_status ) {
		global $wpdb;
		$wpdb->query(
			"UPDATE {$this->get_table_name()} SET status = '$to_status' WHERE account_id = $account_id AND status = '$from_status'"
		);
	}

	function edit_status( $ids, $status ) {
		global $wpdb;
		$wpdb->query(
			"UPDATE {$this->get_table_name()} SET status = '$status' WHERE id IN ($ids)"
		);
	}


	function get_images_awaiting_moderation( $args ) {
		global $wpdb;

		$defaults = array(
			'number'       => 20,
			'offset'       => 0,
			'user_id'      => 0,
			'orderby'      => 'id',
			'order'        => 'DESC'
		);

		$args  = wp_parse_args( $args, $defaults );

		if( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$where = " WHERE `status` = 'moderate'";

		if( isset( $args['account'] ) ) {
			$where .= ' AND `account_id` = '. $args['account'];
		}

		$cache_key = md5( 'igp_images_moderation' . serialize( $args ) );

		$images = wp_cache_get( $cache_key, 'images' );

		if( $images === false ) {
			$images = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM  {$this->get_table_name()} $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d,%d;", absint( $args['offset'] ), absint( $args['number'] ) ), ARRAY_A );
			wp_cache_set( $cache_key, $images, 'images', 3600 );
		}

		return $images;
	}

	/**
	 * Get total images awaiting moderation
	 *
	 * @return mixed
	 */
	public function moderation_images_total() {
		global $wpdb;
		$stats = $wpdb->get_col(
			"	SELECT COUNT(*) AS Total
										FROM {$this->get_table_name()}
										WHERE status = 'moderate'"
		);

		return isset( $stats[0] ) ? $stats[0] : 0;
	}
}
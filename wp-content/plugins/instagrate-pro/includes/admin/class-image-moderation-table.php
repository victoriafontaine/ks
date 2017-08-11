<?php
/**
 * Image Moderation Table Class
 *
 * @package     instagrate-pro
 * @subpackage  install
 * @copyright   Copyright (c) 2014, polevaultweb
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Instagrate_Pro_Image_Moderation_Table Class
 *
 * Renders the images to be moderated table
 *
 * @since 1.6
 */
class Instagrate_Pro_Image_Moderation_Table extends WP_List_Table {

	/**
	 * Number of items per page
	 *
	 * @var int
	 * @since 1.6
	 */
	public $per_page = 10;

	/**
	 * Number of images found
	 *
	 * @var int
	 * @since 1.6
	 */
	public $count = 0;

	/**
	 * Total image
	 *
	 * @var int
	 * @since 1.6
	 */
	public $total = 0;

	public $checkbox = true;

	/**
	 * Get things started
	 *
	 * @since 1.6
	 * @see   WP_List_Table::__construct()
	 */
	public function __construct() {
		global $status, $page;

		// Set parent defaults
		parent::__construct( array(
			'singular' => __( 'Image', 'instagrate-pro' ),     // Singular name of the listed records
			'plural'   => __( 'Images', 'instagrate-pro' ),    // Plural name of the listed records
			'ajax'     => false                        // Does this table support ajax?
		) );

	}

	protected function get_bulk_actions() {
		$actions            = array();
		$actions['pending'] = __( 'Approve' );
		$actions['ignore']  = __( 'Ignore' );

		// Spam - blacklist the user / hashtags?

		return $actions;
	}

	/**
	 * Do actions
	 */
	function process_bulk_action() {

		$action = $this->current_action();
		if ( false !== $action ) {
			if ( isset( $_REQUEST['ids'] ) ) {
				$image_ids = array_map( 'absint',  $_REQUEST['ids'] );
			} else if ( isset( $_REQUEST['id'] ) ) {
				$image_ids = array( absint( $_REQUEST['id'] ) );
			}

			if ( $image_ids ) {
				$images = implode( ',', $image_ids );
				$status = $action;
				if ( 'approve' == $status ) {
					$status = 'pending';
				}
				instagrate_pro()->images->edit_status( $images, $status );
			}
		}
	}

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @access public
	 * @since  1.6
	 *
	 * @param array  $item        Contains all the data of the customers
	 * @param string $column_name The name of the column
	 *
	 * @return string Column Name
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {

		case 'image' :

				$value = '<a target="_blank" href="' .
				         $item['link'] . '"><img src="' . $item['image_thumb_url'] . '" width="50" height="50" alt="' . $item['caption_clean'] . '"></a>';
				break;

			case 'account_id' :
				$account_id = $item['account_id'];
				$value      = '<a href="' . esc_url( admin_url( '/post.php?post=' . $account_id . '&action=edit' ) ) . '">' . $account_id . '</a>';
				break;

			case 'caption' :
				$value = isset( $item['caption_clean'] ) ? $item['caption_clean'] : null;
				break;

			case 'hashtags' :
				$value = isset( $item['tags'] ) ? implode( ', ', unserialize( $item['tags'] ) ) : null;
				break;

			case 'username' :
				$value = isset( $item['username'] ) ? '<a  target="_blank" href="http://instagram.com/' . $item['username'] . '">' . $item['username'] . '</a>' : null;
				break;

			default:
				$value = isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
				break;
		}

		return apply_filters( 'igp_images_moderation_' . $column_name, $value, $item['id'] );
	}

	/**
	 * Retrieve the table columns
	 *
	 * @access public
	 * @since  1.6
	 * @return array $columns Array of all the list table columns
	 */
	public function get_columns() {
		$columns = array(
			'cb'                    => '<input type="checkbox" />',
			'image'                 => __( 'Image', 'instagrate-pro' ),
			'caption_clean_no_tags' => __( 'Caption', 'instagrate-pro' ),
			'account_id'            => __( 'Account', 'instagrate-pro' ),
			'username'              => __( 'User', 'instagrate-pro' ),
			'hashtags'              => __( 'Tags', 'instagrate-pro' ),
		);

		return $columns;
	}

	public function column_caption_clean_no_tags( $item ) {
		$actions = array(
			'pending' => sprintf( '<a href="?post_type=%s&page=%s&action=%s&id=%s">Approve</a>', INSTAGRATEPRO_POST_TYPE, $_REQUEST['page'], 'approve', $item['id'] ),
			'ignore'  => sprintf( '<a href="?post_type=%s&page=%s&action=%s&id=%s">Ignore</a>', INSTAGRATEPRO_POST_TYPE, $_REQUEST['page'], 'ignore', $item['id'] ),
		);

		return sprintf( '%1$s %2$s', $item['caption_clean_no_tags'], $this->row_actions( $actions ) );
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="ids[]" value="%s" />', $item['id']
		);
	}

	/**
	 * Get the sortable columns
	 *
	 * @access public
	 * @since  1.6
	 * @return array Array of all the sortable columns
	 */
	public function get_sortable_columns() {
		return array(
			'id'         => array( 'id', true ),
			'account_id' => array( 'account_id', true ),
			'caption'    => array( 'caption', false ),
			'username'   => array( 'username', false ),
		);
	}

	/**
	 * Retrieve the current page number
	 *
	 * @access public
	 * @since  1.6
	 * @return int Current page number
	 */
	public function get_paged() {
		return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	}

	/**
	 * Retrieves the account id number
	 *
	 * @access public
	 * @since  1.6
	 * @return int Current page number
	 */
	public function get_account() {
		return isset( $_GET['account_id'] ) ? absint( $_GET['account_id'] ) : false;
	}

	/**
	 * Retrieves the search query string
	 *
	 * @access public
	 * @since  1.6
	 * @return mixed string If search is present, false otherwise
	 */
	public function get_search() {
		return ! empty( $_GET['s'] ) ? urldecode( trim( $_GET['s'] ) ) : false;
	}

	/**
	 * Build all the table data
	 *
	 * @access public
	 * @since  1.6
	 * @global object $wpdb Used to query the database using the WordPress
	 *                      Database API
	 * @return array All the data for images
	 */
	public function image_data() {
		global $wpdb;

		$data    = array();
		$paged   = $this->get_paged();
		$offset  = $this->per_page * ( $paged - 1 );
		$search  = $this->get_search();
		$order   = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'DESC';
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'id';
		$account = $this->get_account();

		$args = array(
			'number'  => $this->per_page,
			'offset'  => $offset,
			'order'   => $order,
			'orderby' => $orderby
		);

		if ( false !== $account ) {
			$args['account'] = $account;
		}

		if ( is_email( $search ) ) {
			$args['email'] = $search;
		} elseif ( is_numeric( $search ) ) {
			$args['id'] = $search;
		}

		$images = instagrate_pro()->images->get_images_awaiting_moderation( $args );

		if ( $images ) {

			$this->count = count( $images );

			$data = $images;
		}

		return $data;
	}

	/**
	 * Setup the final data for the table
	 *
	 * @access public
	 * @since  1.5
	 * @uses   Instagrate_Pro_Image_Moderation_Table::get_columns()
	 * @uses   WP_List_Table::get_sortable_columns()
	 * @uses   Instagrate_Pro_Image_Moderation_Table::get_pagenum()
	 * @uses   Instagrate_Pro_Image_Moderation_Table::get_total_customers()
	 * @return void
	 */
	public function prepare_items() {
		global $image_status;

		$image_status = isset( $_REQUEST['image_status'] ) ? $_REQUEST['image_status'] : 'all';

		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->process_bulk_action();

		$this->items = $this->image_data();

		$this->total = instagrate_pro()->images->moderation_images_total();

		$this->set_pagination_args( array(
			'total_items' => $this->total,
			'per_page'    => $this->per_page,
			'total_pages' => ceil( $this->total / $this->per_page )
		) );
	}
}
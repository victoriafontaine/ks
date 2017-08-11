<?php

/**
 * Controller Class
 *
 * @package     instagrate-pro
 * @subpackage  controller
 * @copyright   Copyright (c) 2014, polevaultweb
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
 */
class Instagrate_Pro_Controller {

	public $debug_mode;
	public $debug_text;

	private $account_id;
	private $account;
	private $settings;
	private $images;

	private $is_home;
	private $high_res_images;
	private $dup_image;
	private $default_title;
	private $title_limit;
	private $title_limit_type;
	private $credit_link;
	private $image_caption_tag;
	private $comment_credit;
	private $link_credit;

	private $wp_post_author;
	private $wp_post_status;
	private $wp_post_type;
	private $wp_post_tax;
	private $wp_post_term;
	private $multimap;
	private $map_zoom;
	private $image_order;
	private $tags;
	private $custom_feat_attach_id;
	private $default_tags;

	private $new_post_id;
	private $new_post;
	private $video_count = 0;

	function __construct() {
		add_shortcode( 'igp-image-position', array( $this, 'image_position' ) );
		add_action( 'the_posts', array( $this, 'has_video_shortcode' ) );
		add_shortcode( 'igp-video', array( $this, 'get_video' ) );
		add_shortcode( 'igp-embed', array( $this, 'get_embed' ) );

		add_action( 'wp_ajax_nopriv_instagrate', array( $this, 'ajax_cron_controller' ) );
		add_action( 'wp_ajax_igp_manual_post', array( $this, 'ajax_process_manual_post' ) );
		add_action( 'scheduled_post_account', array( $this, 'post_account' ) ); // deprecated
		add_action( 'igp_scheduled_post_account', array( $this, 'post_account' ) );
		add_action( 'template_redirect', array( $this, 'controller' ) );
	}

	/**
	 * Triggers the plugin posting on template_redirect
	 *
	 * @action template_redirect
	 */
	function controller() {
		$this->master_controller( 'constant' );
	}

	/**
	 * Used for cron jobs to trigger posting
	 *
	 * Accepts 'account_id' param for specific account posting.
	 */
	function ajax_cron_controller() {
		$id = filter_input( INPUT_GET, 'account_id', FILTER_VALIDATE_INT );
		if ( false === $id ) {
			$id = null;
		}

		instagrate_pro()->controller->master_controller( 'cron', $id );
		exit;
	}

	/**
	 * Loop through all accounts and trigger if they have the correct frequency
	 *
	 * @param string   $frequency
	 * @param null|int $account_id
	 */
	private function master_controller( $frequency, $account_id = null ) {
		// Check if debug mode is on
		$this->debug_mode = Instagrate_Pro_Helper::setting( 'igpsettings_support_debug-mode', '0' );
		instagrate_pro()->debug->make_debug( 'Starting the controller for accounts with the frequency ' . $frequency );

		$accounts = instagrate_pro()->accounts->get_accounts( 'publish', $account_id );
		instagrate_pro()->debug->make_debug( 'Total accounts: ' . sizeof( $accounts ) );
		if ( isset( $accounts ) && $accounts ) {
			foreach ( $accounts as $key => $account ) {
				$account_settings = get_post_meta( $key, '_instagrate_pro_settings', true );
				instagrate_pro()->debug->make_debug( 'Account: ' . $key );
				// Check if correct frequency
				if ( Instagrate_Pro_Helper::setting( 'posting_frequency', 'constant', $account_settings ) == $frequency ) {
					instagrate_pro()->debug->make_debug( 'Account: ' . $key . ' is a ' . $frequency . ' poster. Run the posting function' );

					if ( 'cron' == $frequency ) {
						instagrate_pro()->accounts->lock_account( $key, false );
					}

					$this->post_account( $key, $frequency, '' );
				}
			}
		}
	}

	/**
	 * Takes external image and sideloads to media library and attachs to post
	 *
	 * @param        $url
	 * @param        $postid
	 * @param        $post_name
	 * @param string $type
	 * @param string $image_caption
	 *
	 * @return int|object
	 */
	private function attach_image( $url, $postid, $post_name, $type = 'image', $image_caption = '' ) {
		require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
		require_once( ABSPATH . "wp-admin" . '/includes/file.php' );
		require_once( ABSPATH . "wp-admin" . '/includes/media.php' );

		$tmp  = download_url( $url );
		$file = basename( $url );
		$info = pathinfo( $file );

		$image_id = Instagrate_Pro_Helper::setting( 'igpsettings_general_image-save-name', '0' );
		if ( $post_name == '' || $image_id == 1 ) {
			$file_name = $file;
		} else {
			$file_name = $post_name;
			$file_name = sanitize_file_name( $file_name );
			$file_name = remove_accents( $file_name );
			$file_name = substr( $file_name, 0, 100 );
			$file_name = strtolower( $file_name ) . '.' . $info['extension'];
		}

		$file_array = array(
			'name'     => apply_filters( 'igp_image_filename', $file_name, $file, $post_name, $postid ),
			'tmp_name' => $tmp
		);

		// Check for download errors
		if ( is_wp_error( $tmp ) ) {
			@unlink( $file_array['tmp_name'] );
			instagrate_pro()->debug->make_debug( 'Attaching ' . $type . ': ' . $file_name );
			instagrate_pro()->debug->make_debug( 'Error Attaching ' . $type . ' - download_url: ' . $tmp->get_error_message() );

			return 0;
		}
		$id = media_handle_sideload( $file_array, $postid );

		// Check for handle sideload errors.
		if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );
			instagrate_pro()->debug->make_debug( 'Attaching ' . $type . ': ' . $file_name );
			instagrate_pro()->debug->make_debug( 'Error Attaching ' . $type . ' - media_handle_sideload: ' . $id->get_error_message() );

			return 0;
		}

		// If caption supplied add it to the image attachment
		if ( $image_caption != '' ) {
			$attachment_post = array(
				'ID'           => $id,
				'post_excerpt' => apply_filters( 'igp_image_caption', $image_caption )
			);
			wp_update_post( $attachment_post );
		}

		return $id;
	}

	/**
	 * Shortcode used to specify where to post images to the same post
	 *
	 * @param      $atts
	 * @param null $content
	 *
	 * @return bool
	 */
	public function image_position( $atts, $content = null ) {
		extract( shortcode_atts( array( 'position' => 'below' ), $atts ) );

		return false;
	}

	/**
	 * Checks if posts have the video shortcode and loads scripts
	 *
	 * @param $posts
	 *
	 * @action the_posts
	 *
	 * @return mixed
	 */
	public function has_video_shortcode( $posts ) {
		if ( empty( $posts ) ) {
			return $posts;
		}
		$found = false;
		foreach ( $posts as $post ) {
			if ( stripos( $post->post_content, '[igp-video' ) !== false ) {
				$found = true;
				break;
			}
		}
		if ( $found ) {
			instagrate_pro()->scripts->enqueue_video();
		}

		return $posts;
	}

	/**
	 * Embed shortcode for Instagram image
	 *
	 * @param      $atts
	 * @param null $content
	 *
	 * @return string
	 */
	public function get_embed( $atts, $content = null ) {
		extract( shortcode_atts( array(
			'url'    => '',
			'width'  => '612',
			'height' => '710'
		), $atts ) );
		$html = '';
		if ( $url != '' ) {
			$html .= '<iframe src="' . $url . '" width="' . $width . '" height="' . $height . '" frameborder="0" scrolling="no" allowtransparency="true"></iframe>';
		}

		return $html;
	}

	/**
	 * Video player shortcode, either native WP 3.6+ or jPlayer
	 *
	 * @param array $atts
	 * @param null  $content
	 *
	 * @return string
	 */
	public function get_video( $atts, $content = null ) {
		$this->video_count += 1;

		if ( empty( $atts['src'] ) ) {
			return '';
		}

		if ( function_exists( 'wp_video_shortcode' ) && ! isset( $atts['jplayer'] ) ) {

			if ( ! isset( $atts['width'] ) && ! isset( $atts['height'] ) ) {
				switch ( $atts['size'] ) {
					case 'medium':
						$atts['width'] = $atts['height'] = '460';
						break;
					case 'small':
						$dim = '320';
						break;
					case 'large':
						$dim = '620';
						break;
					default:
						$dim = '620';
						break;
				}
				$atts['width'] = $atts['height'] = $dim;
				unset( $atts['size'] );

			}

			return wp_video_shortcode( $atts );
		} else {

			$default = array(
				'src'    => '',
				'title'  => '',
				'poster' => '',
				'size'   => 'large',
				'count'  => $this->video_count
			);

			$args = shortcode_atts( $default, $atts );
			extract( $args );

			$dim = '620';

			switch ( $size ) {
				case 'medium':
					$dim = '460';
					break;
				case 'small':
					$dim = '320';
					break;
				case 'large':
					$dim = '620';
					break;
			}

			$args['dim']   = $dim;
			$args['jsurl'] = plugins_url( 'assets/js/jquery.jplayer/', __FILE__ );;

			instagrate_pro()->helper->template( 'jplayer', $args );
		}
	}

	/**
	 * Checks the account is ok before triggering
	 *
	 * @param $frequency
	 *
	 * @return bool
	 */
	public function check_account( $frequency ) {
		if ( ini_get( 'safe_mode' ) ) {
			instagrate_pro()->debug->make_debug( 'Safe Mode On' );
		} else {
			@set_time_limit( 0 );
		}

		if ( isset( $this->account->post_status ) && $this->account->post_status != 'publish' ) {
			return $this->post_exit( 'Account not published' );
		}
		if ( $frequency == 'manual' ) {
			instagrate_pro()->accounts->lock_account( $this->account_id, false );
		}

		// Check if Account locked
		if ( isset( $this->settings->locked ) && 'locked' == $this->settings->locked ) {
			return $this->post_exit( 'Account already Locked - already posting' );
		}

		return true;
	}

	/**
	 * Check the image is pending before posting
	 *
	 * @param $image
	 *
	 * @return bool
	 */
	private function check_image( $image ) {
		instagrate_pro()->debug->make_debug( 'Image ID: ' . $image->image_id, true );
		instagrate_pro()->debug->make_debug( 'Image Status: ' . $image->status );
		if ( $image->status != 'pending' ) {
			instagrate_pro()->debug->make_debug( 'Image not pending' );

			return false;
		}

		return true;
	}

	/**
	 * Sets up general defaults for posting
	 *
	 * @param $frequency
	 * @param $schedule
	 */
	private function general_defaults( $frequency, $schedule ) {
		$this->debug_mode        = Instagrate_Pro_Helper::setting( 'igpsettings_support_debug-mode', '0' );
		$this->is_home           = Instagrate_Pro_Helper::setting( 'igpsettings_general_bypass-home', '0' );
		$this->high_res_images   = Instagrate_Pro_Helper::setting( 'igpsettings_general_high-res-images', '0' );
		$this->dup_image         = Instagrate_Pro_Helper::setting( 'igpsettings_general_allow-duplicates', '0' );
		$this->default_title     = Instagrate_Pro_Helper::setting( 'igpsettings_general_default-title', 'Instagram Image' );
		$this->title_limit       = Instagrate_Pro_Helper::setting( 'igpsettings_general_title-limit', '' );
		$this->title_limit_type  = Instagrate_Pro_Helper::setting( 'igpsettings_general_title-limit-type', 'characters' );
		$this->credit_link       = Instagrate_Pro_Helper::setting( 'igpsettings_general_credit-link', '0' );
		$this->image_caption_tag = Instagrate_Pro_Helper::setting( 'igpsettings_general_image-caption', '' );
		instagrate_pro()->debug->make_debug( 'Starting to Post Account: ' . $this->account_id );
		instagrate_pro()->debug->make_debug( 'Frequency: ' . $frequency );
		instagrate_pro()->debug->make_debug( 'Schedule: ' . $schedule );
		instagrate_pro()->debug->make_debug( 'is_home option: ' . $this->is_home );
		instagrate_pro()->debug->make_debug( 'dup_image option: ' . $this->dup_image );
		instagrate_pro()->debug->make_debug( 'default_title option: ' . $this->default_title );
		instagrate_pro()->debug->make_debug( 'credit_link option: ' . $this->credit_link );

		$this->comment_credit = "\n<!-- This post is created by Intagrate v" . INSTAGRATEPRO_VERSION . " -->\n";
		$this->link_credit    = '<br/><a href="https://intagrate.io" title="A plugin by polevaultweb.com">Posted by Intagrate v' . INSTAGRATEPRO_VERSION . '</a>';
	}

	/**
	 * Sets up account defaults for posting
	 */
	private function account_defaults() {
		$this->wp_post_author = $this->settings->post_author;
		$this->wp_post_status = $this->settings->post_status;
		$this->wp_post_type   = $this->settings->post_type;
		$this->wp_post_tax    = $this->settings->post_taxonomy;
		$this->wp_post_term   = isset( $this->settings->post_term ) ? $this->settings->post_term : array();
		$this->multimap       = isset( $this->settings->grouped_multi_map ) ? $this->settings->grouped_multi_map : 'none';
		$this->map_zoom       = isset( $this->settings->map_zoom ) ? $this->settings->map_zoom : '15';

		// Get the order ASC or DESC for group and single types
		$this->image_order = 'ASC';
		if ( $this->settings->posting_multiple != 'each' ) {
			$this->image_order = $this->settings->posting_image_order;
		}

		// Hastag Filter
		$tags = ( isset( $this->settings->instagram_hashtags ) ) ? $this->settings->instagram_hashtags : '';
		$this->tags = instagrate_pro()->helper->prepare_tags( $tags );
		instagrate_pro()->debug->make_debug( 'Hashtags Filter: ' . $tags );
		$tag_name = instagrate_pro()->helper->get_first_tag( $this->tags );
		instagrate_pro()->debug->make_debug( 'Tag to use for API: ' . $tag_name );

		// Custom Featured image
		$this->custom_feat_attach_id = get_post_thumbnail_id( $this->account_id );

		// Default Tags
		$default_tags_all   = wp_get_post_tags( $this->account_id );
		$this->default_tags = array();
		if ( $default_tags_all ) {
			foreach ( $default_tags_all as $tag_default ) {
				$this->default_tags[] = $tag_default->name;
			}
		}
	}

	/**
	 * Prepare the images for the controller
	 */
	protected function prepare_images() {
		foreach ( $this->images as $key => $image ) {
			// High Res images
			$image->image_url = $this->maybe_high_res_image( $image->image_url );

			$this->images[ $key ] = $image;
		}
	}

	/**
	 * Filters images based on media type of account
	 */
	private function media_type_filtering() {
		$media_filter = isset( $this->settings->instagram_media_filter ) ? $this->settings->instagram_media_filter : 'all';
		if ( $media_filter != 'all' ) {
			foreach ( $this->images as $key => $image ) {
				if ( $image->media_type != $media_filter ) {
					instagrate_pro()->images->bulk_edit_status( $this->account_id, 'ignore', $image->image_id );
					instagrate_pro()->debug->make_debug( 'Media Filter set as ' . $media_filter . ' - Instagram Media is ' . $image->media_type );
					unset( $this->images[ $key ] );
				}
			}
		}
	}

	/**
	 * Filters images based on hashtags
	 */
	private function hashtag_filtering() {
		if ( ! empty( $this->tags ) ) {
			foreach ( $this->images as $key => $image ) {
				$hashtags = unserialize( $image->tags );
				if ( $hashtags !== false ) {
					instagrate_pro()->debug->make_debug( 'Image ' . $key . ' - Tags: ' );
					instagrate_pro()->debug->make_debug( $hashtags );
					if ( is_array( $hashtags ) ) {
						$tags     = array_map( 'strtolower', $this->tags );
						$hashtags = array_map( 'strtolower', $hashtags );

						$tag_match = true;
						foreach ( $tags as $tag ) {
							if ( substr( $tag, 0, 1 ) == '-' ) {
								if ( in_array( substr( $tag, 1 ), $hashtags ) ) {
									$tag_match = false;
									break;
								}
							} else {
								if ( ! in_array( $tag, $hashtags ) ) {
									$tag_match = false;
									break;
								}
							}
						}

						if ( ! $tag_match ) {
							instagrate_pro()->images->bulk_edit_status( $this->account_id, 'ignore', $image->image_id );
							unset( $this->images[ $key ] );
						}
					}
				}
			}
		}
	}

	/**
	 * If frequency is constant, check the page is correct to trigger on
	 *
	 * Improvements to logic needed.
	 *
	 * @return bool
	 */
	private function constant_post_check() {
		// Only do run for correct pages
		if ( $this->settings->posting_frequency == 'constant' && ! $this->is_home ) {
			instagrate_pro()->debug->make_debug( 'Posting constantly as ' . $this->settings->post_type );
			$settings_post_term = ( isset( $this->settings->post_term ) ) ? $this->settings->post_term : '';
			switch ( $this->settings->post_type ) {
				case 'post':
					if ( $this->settings->posting_multiple != 'single' ) {
						if ( ! is_front_page() && ! is_home() && ! is_category( $settings_post_term ) && ! is_tax( $this->settings->post_taxonomy, $settings_post_term ) ) {
							return $this->post_exit( 'Not single ' . $this->settings->post_type . ' but not homepage or category page' );
						}
					} else {
						if ( ! is_single( $this->settings->posting_same_post ) ) {
							return $this->post_exit( 'Single ' . $this->settings->post_type . ' but not the single ' . $this->settings->post_type . '(' . $this->settings->posting_same_post . ')' );
						}
					}
					break;
				case 'page':
					if ( $this->settings->posting_multiple != 'single' ) {
						if ( ! is_front_page() && ! is_category( $settings_post_term ) && ! is_tax( $this->settings->post_taxonomy, $settings_post_term ) ) {
							return $this->post_exit( 'Not single ' . $this->settings->post_type . ' but not front page or category page' );
						}
					} else {
						if ( ! is_page( $this->settings->posting_same_post ) ) {
							return $this->post_exit( 'Single ' . $this->settings->post_type . ' but not the single ' . $this->settings->post_type . '(' . $this->settings->posting_same_post . ')' );
						}
					}
					break;
				default:
					if ( $this->settings->posting_multiple != 'single' ) {
						if ( ! is_front_page() && ! is_category( $settings_post_term ) && ! is_tax( $this->settings->post_taxonomy, $settings_post_term ) ) {
							return $this->post_exit( 'Not single ' . $this->settings->post_type . ' but not front page or category page' );
						}
					} else {
						if ( ! is_singular( $this->settings->post_type ) ) {
							return $this->post_exit( 'Single ' . $this->settings->post_type . ' but not the single ' . $this->settings->post_type . '(' . $this->settings->posting_same_post . ')' );
						}
					}
					break;
			}
		}

		return true;
	}

	/**
	 * Insert post in draft form
	 *
	 * @param $title
	 * @param $content
	 * @param $date
	 * @param $date_gmt
	 */
	private function insert_post( $title, $content, $date, $date_gmt ) {
		// Insert post
		$new_post = array(
			'post_title'    => $title,
			'post_content'  => $content,
			'post_author'   => $this->wp_post_author,
			'post_status'   => 'draft',
			'post_type'     => $this->wp_post_type,
			'post_date'     => $date,
			'post_date_gmt' => $date_gmt
		);
		instagrate_pro()->debug->make_debug( 'Insert New Post' );
		instagrate_pro()->debug->make_debug( $new_post );
		$this->new_post_id = wp_insert_post( $new_post );
		$this->new_post    = get_post( $this->new_post_id );
	}

	/**
	 * Update post with content and publish if necessary
	 *
	 * @param bool        $title
	 * @param string      $content
	 * @param null|string $excerpt
	 */
	private function update_post( $title = false, $content, $excerpt = null ) {
		// Update post
		$update_post                 = array();
		$update_post['ID']           = $this->new_post_id;
		$update_post['post_content'] = $content;
		if ( ! is_null( $excerpt ) ) {
			$update_post['post_excerpt'] = $excerpt;
		}

		if ( $title ) {
			$post_name                = sanitize_title( $title );
			$update_post['post_name'] = wp_unique_post_slug( $post_name, $this->new_post_id, 'publish', $this->wp_post_type, 0 );
		}

		if ( 'publish' !== $this->wp_post_status ) {
			$update_post['post_status'] = $this->wp_post_status;
		}

		// Update the post into the database
		instagrate_pro()->debug->make_debug( 'Update New Post' );
		instagrate_pro()->debug->make_debug( $update_post );
		wp_update_post( $update_post );
		// If status is publish then publish post and fire hooks
		if ( 'publish' === $this->wp_post_status ) {
			wp_publish_post( $this->new_post_id );
		}
	}

	/**
	 * Set taxonomy terms for post
	 */
	private function post_taxonomy() {
		// Post Tax and Term
		if ( $this->wp_post_tax != '0' && $this->wp_post_term ) {
			$inserted_terms = wp_set_post_terms( $this->new_post_id, $this->wp_post_term, $this->wp_post_tax );
			if ( is_wp_error( $inserted_terms ) ) {
				instagrate_pro()->debug->make_debug( 'Error: Setting Tax Term - ' . $inserted_terms->get_error_message() );
			}
		}
	}

	/**
	 * Set tags for post
	 *
	 * @param $image
	 */
	private function post_tags( $image ) {
		// Post Tags
		if ( $this->settings->post_tag_taxonomy != '0' ) {
			$wp_post_tags   = unserialize( $image->tags );
			$wp_post_tags   = array_merge( $wp_post_tags, $this->default_tags );
			$inserted_terms = wp_set_post_terms( $this->new_post_id, $wp_post_tags, $this->settings->post_tag_taxonomy, true );
			if ( is_wp_error( $inserted_terms ) ) {
				instagrate_pro()->debug->make_debug( 'Error: Setting Tag Terms - ' . $inserted_terms->get_error_message() );
			}
		}
	}

	/**
	 * Set format of post
	 */
	private function post_format() {
		// Post format
		if ( $this->settings->post_format != 'Standard' ) {
			set_post_format( $this->new_post_id, $this->settings->post_format );
		}
	}

	/**
	 * Process the Instagram image
	 *
	 * @param $image
	 */
	private function post_image( &$image ) {
		// Post image
		$image->instagram_image = $image->image_url;
		$image->wp_image_url    = '';
		if ( $this->settings->post_save_media == 'on' ) {
			$file_name = $image->caption_clean;
			if ( $file_name == '' ) {
				$file_name = $this->new_post->post_title;
			}
			$att_image = instagrate_pro()->helper->strip_querysting( $image->image_url );
			// Load into media library
			$attach_id = $this->attach_image( $att_image, $this->new_post_id, $file_name, 'image', $image->image_caption );
			if ( $attach_id != 0 ) {
				// Get new shot image url from media attachment
				$image->attachment_id   = $attach_id;
				$image->instagram_image = wp_get_attachment_url( $attach_id );
				$image->wp_image_url    = $image->instagram_image;
				// Featured image
				if ( $this->settings->post_featured_image == 'on' ) {
					$wp_attach_id = ( isset( $this->custom_feat_attach_id ) && $this->custom_feat_attach_id != '' ) ? $this->custom_feat_attach_id : $attach_id;
					add_post_meta( $this->new_post_id, '_thumbnail_id', $wp_attach_id );
				}
			}
		} else {
			if ( $this->custom_feat_attach_id ) {
				add_post_meta( $this->new_post_id, '_thumbnail_id', $this->custom_feat_attach_id );
			}
		}
	}

	/**
	 * Alter the image URL to replace with high resolution URLs
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	protected function maybe_high_res_image( $url ) {
		if ( $this->high_res_images ) {
			$url = str_replace( 's640x640', 's1080x1080', $url );
		}

		return apply_filters( 'igp_instagram_image_url', $url );
	}

	/**
	 * Process the Instagram Video
	 *
	 * @param $image
	 */
	private function post_video( &$image ) {
		// Post Video
		$image->instagram_video = $image->video_url;
		$image->wp_video_url    = '';
		if ( $this->settings->post_save_media == 'on' && $image->media_type == 'video' ) {
			$file_name = $image->caption_clean;
			if ( $file_name == '' ) {
				$file_name = $this->new_post->post_title;
			}
			$att_image = instagrate_pro()->helper->strip_querysting( $image->video_url );
			// Load into media library
			$attach_id = $this->attach_image( $att_image, $this->new_post_id, $file_name, 'video', $image->image_caption );
			if ( $attach_id != 0 ) {
				// Get new shot image url from media attachment
				$image->instagram_video = wp_get_attachment_url( $attach_id );
				$image->wp_video_url    = $image->instagram_video;
			}
		}
	}

	/**
	 * Process position of new content in existing for same post
	 *
	 * @param $wp_post_content
	 * @param $old_content
	 *
	 * @return mixed|string
	 */
	private function post_existing_gallery( $wp_post_content, $old_content ) {
		// Check for existing gallery content
		if ( preg_match( "|^\[gallery|", $wp_post_content ) == 0 || preg_match( "|^\[gallery|", $old_content ) == 0 ) {
			// Put new content at the top or bottom of old content
			if ( $this->settings->posting_single_location == 'specific' ) {
				$pattern = get_shortcode_regex();
				if ( preg_match_all( '/' . $pattern . '/s', $old_content, $matches )
				     && array_key_exists( 2, $matches )
				     && in_array( 'igp-image-position', $matches[2] )
				) {

					$key            = array_search( 'igp-image-position', $matches[2] );
					$full_shortcode = $matches[0][ $key ];
					$atts           = shortcode_parse_atts( $matches[3][ $key ] );
					extract( shortcode_atts( array( 'position' => 'below' ), $atts ) );
					$num_position    = ( $position == 'above' ) ? strrpos( $old_content, $full_shortcode ) : strrpos( $old_content, $full_shortcode ) + strlen( $full_shortcode );
					$wp_post_content = ( $position == 'above' ) ? $wp_post_content . "\n" : "\n" . $wp_post_content;
					$wp_post_content = substr_replace( $old_content, $wp_post_content, $num_position, 0 );
				} else {
					$wp_post_content = $old_content . $wp_post_content;
				}
			} else {
				if ( $this->settings->posting_single_location == 'top' ) {
					$wp_post_content = $wp_post_content . $old_content;
				} else {
					$wp_post_content = $old_content . $wp_post_content;
				}
			}
		}

		return $wp_post_content;
	}

	/**
	 * Set up tag values for title
	 *
	 * @param $image
	 *
	 * @return mixed
	 */
	private function title_tags( &$image ) {
		instagrate_pro()->tags->set_tag( 'instagram_media_type', $image->media_type );
		instagrate_pro()->tags->set_tag( 'instagram_media_id', $image->image_id );
		instagrate_pro()->tags->set_tag( 'caption', $image->caption_clean );
		instagrate_pro()->tags->set_tag( 'caption_escaped', esc_attr( $image->caption_clean ) );
		instagrate_pro()->tags->set_tag( 'caption_tags_no_hash', str_replace( '#', '', $image->caption_clean ) );
		instagrate_pro()->tags->set_tag( 'caption_tags_no_hash_escaped', str_replace( '#', '', esc_attr( $image->caption_clean ) ) );
		instagrate_pro()->tags->set_tag( 'caption_no_tags', $image->caption_clean_no_tags );
		instagrate_pro()->tags->set_tag( 'caption_no_tags_escaped', esc_attr( $image->caption_clean_no_tags ) );
		$no_usernames = instagrate_pro()->tags->strip_mentions( $image->caption_clean );
		instagrate_pro()->tags->set_tag( 'caption_no_usernames', $no_usernames  );
		instagrate_pro()->tags->set_tag( 'caption_no_usernames_escaped', esc_attr( $no_usernames ) );
		$no_usernames_no_tags = instagrate_pro()->tags->strip_mentions( $image->caption_clean_no_tags );
		instagrate_pro()->tags->set_tag( 'caption_no_usernames_no_tags', $no_usernames_no_tags );
		instagrate_pro()->tags->set_tag( 'caption_no_usernames_no_tags_escaped', esc_attr( $no_usernames_no_tags ) );
		instagrate_pro()->tags->set_tag( 'tags', implode( apply_filters( 'igp_tag_sep', ' ' ), unserialize( $image->tags ) ) );
		$image_tags = unserialize( $image->tags );
		instagrate_pro()->tags->set_tag( 'tags_first', reset( $image_tags ) );
		instagrate_pro()->tags->set_tag( 'username', $image->username );
		instagrate_pro()->tags->set_tag( 'date', date( apply_filters( 'igp_date_format', 'M d, Y @ H:i' ), instagrate_pro()->helper->get_instagram_time( $image->image_timestamp ) ) );
		instagrate_pro()->tags->set_tag( 'filter', $image->filter );
		instagrate_pro()->tags->set_tag( 'location_name', $image->location_name );
		instagrate_pro()->tags->set_tag( 'location_lat', $image->latitude );
		instagrate_pro()->tags->set_tag( 'location_lng', $image->longitude );

		$template_tags = instagrate_pro()->tags->get_template_tags( 'title' );
		// Image caption
		$image->image_caption = ( $this->image_caption_tag != '' ) ? instagrate_pro()->tags->replace_template_tags( $this->image_caption_tag, $template_tags, '' ) : '';

		return $template_tags;
	}

	/**
	 * Set up tag values for content / meta
	 *
	 * @param $image
	 *
	 * @return mixed
	 */
	private function content_tags( &$image ) {
		$caption_clean = instagrate_pro()->helper->clean_caption( $image->caption, false );
		instagrate_pro()->tags->set_tag( 'caption', $caption_clean );
		instagrate_pro()->tags->set_tag( 'caption_escaped', esc_attr( $caption_clean ) );
		instagrate_pro()->tags->set_tag( 'caption_tags_no_hash', str_replace( '#', '', $caption_clean ) );
		instagrate_pro()->tags->set_tag( 'caption_tags_no_hash_escaped', str_replace( '#', '', esc_attr( $caption_clean ) ) );

		if ( isset( $image->tags ) ) {
			$tags = $image->tags;
			$tags = unserialize( $tags );
			$tags = instagrate_pro()->helper->clean_tags( $tags );
		} else {
			$tags = '';
		}
		$caption_clean_no_tags = instagrate_pro()->helper->caption_strip_tags( $caption_clean, $tags );
		instagrate_pro()->tags->set_tag( 'caption_no_tags', $caption_clean_no_tags );
		instagrate_pro()->tags->set_tag( 'caption_no_tags_escaped', esc_attr( $caption_clean_no_tags ) );
		$no_usernames = instagrate_pro()->tags->strip_mentions( $caption_clean );
		instagrate_pro()->tags->set_tag( 'caption_no_usernames', $no_usernames  );
		instagrate_pro()->tags->set_tag( 'caption_no_usernames_escaped', esc_attr( $no_usernames ) );
		$no_usernames_no_tags = instagrate_pro()->tags->strip_mentions( $caption_clean_no_tags );
		instagrate_pro()->tags->set_tag( 'caption_no_usernames_no_tags', $no_usernames_no_tags );
		instagrate_pro()->tags->set_tag( 'caption_no_usernames_no_tags_escaped', esc_attr( $no_usernames_no_tags ) );

		instagrate_pro()->tags->set_tag( 'image', $image->instagram_image );
		instagrate_pro()->tags->set_tag( 'video', $image->instagram_video );
		instagrate_pro()->tags->set_tag( 'user_profile_url', 'http://instagram.com/' . $image->username );
		instagrate_pro()->tags->set_tag( 'user_profile_image_url', $image->user_image_url );
		instagrate_pro()->tags->set_tag( 'instagram_url', $image->link );
		instagrate_pro()->tags->set_tag( 'instagram_image_url', $image->image_url );
		instagrate_pro()->tags->set_tag( 'instagram_video_url', $image->video_url );
		instagrate_pro()->tags->set_tag( 'instagram_embed_url', str_replace( 'http:', '', $image->link ) . 'embed/' );
		instagrate_pro()->tags->set_tag( 'wordpress_image_url', $image->wp_image_url );
		instagrate_pro()->tags->set_tag( 'wordpress_video_url', $image->wp_video_url );
		instagrate_pro()->tags->set_tag( 'wordpress_post_url', get_permalink( $this->new_post_id ) );
		instagrate_pro()->tags->set_tag( 'image_class', isset( $image->attachment_id ) ? 'wp-image-' . $image->attachment_id : '' );
		instagrate_pro()->tags->set_tag( 'map', $image->map );

		$template_tags = instagrate_pro()->tags->get_template_tags();

		// Custom body text
		return instagrate_pro()->tags->replace_template_tags( $this->account->post_content, $template_tags, '' );
	}

	/**
	 * Get the account excerpt with template tags replaced
	 *
	 * @return null|string
	 */
	private function get_excerpt() {
		$wp_post_excerpt = null;
		if ( '' !== trim( $this->account->post_excerpt ) ) {
			$template_tags   = instagrate_pro()->tags->get_template_tags();
			$wp_post_excerpt = instagrate_pro()->tags->replace_template_tags( $this->account->post_excerpt, $template_tags, '' );
		}

		return $wp_post_excerpt;
	}

	/**
	 * Process the creation of a post for each image
	 *
	 * @param $i
	 * @param $count
	 */
	private function each( $i, $count ) {
		foreach ( $this->images as $image ) {
			$i ++;
			// Check image
			if ( ! $this->check_image( $image ) ) {
				continue;
			}

			// Template tags for title
			$template_tags = $this->title_tags( $image );
			// Custom title
			$wp_post_title = instagrate_pro()->tags->replace_template_tags( $this->account->post_title, $template_tags, $this->default_title );
			// Limit post title
			$wp_post_title = $this->limit_title( $wp_post_title, $this->title_limit_type, $this->title_limit );
			// Post date
			$wp_post_date_gmt = date( 'Y-m-d H:i:s', current_time( 'timestamp', 1 ) - ( ( $count - $i ) * 20 ) );
			$wp_post_date     = date( 'Y-m-d H:i:s', current_time( 'timestamp', 0 ) - ( ( $count - $i ) * 20 ) );
			if ( $this->settings->post_date == 'on' ) {
				$wp_post_date     = date( 'Y-m-d H:i:s', instagrate_pro()->helper->get_instagram_time( $image->image_timestamp ) );
				$wp_post_date_gmt = date( 'Y-m-d H:i:s', $image->image_timestamp );
			}

			// Insert post
			$this->insert_post( $wp_post_title, '', $wp_post_date, $wp_post_date_gmt );

			// Post Tax and Term
			$this->post_taxonomy();

			// Post Tags
			$this->post_tags( $image );

			// Post format
			$this->post_format();

			// Post image
			$this->post_image( $image );

			// Post Video
			$this->post_video( $image );

			// Comments
			if ( Instagrate_Pro_Helper::setting( 'igpsettings_comments_enable-comments', '0' ) == 1 ) {
				$comments = $image->comments;
				if ( $comments != '' ) {
					$comments = unserialize( base64_decode( $image->comments ) );
				}
				instagrate_pro()->comments->import_comments( $this->settings->token, $comments, $image->image_id, $this->new_post_id, $image->id );
			}

			// Location map (shortcode)
			$image->map = '';
			if ( $image->latitude != '' && $image->longitude != '' ) {
				update_post_meta( $this->new_post_id, '_igp_latlon', $image->latitude . ',' . $image->longitude );
				$image->map = '[igp_map lat="' . $image->latitude . '" lon="' . $image->longitude . '" marker="' . $image->location_name . '" style="' . $this->settings->map_style . '" class="' . $this->settings->map_css . '" width="' . $this->settings->map_width . '" height="' . $this->settings->map_height . '" width_type="' . $this->settings->map_width_type . '" height_type="' . $this->settings->map_height_type . '" zoom="' . $this->map_zoom . '"]';
			}

			// Custom body text
			$wp_post_content = $this->content_tags( $image );

			// Custom Excerpt
			$wp_post_excerpt = $this->get_excerpt();

			// Post meta
			add_post_meta( $this->new_post_id, '_igp_id', $image->id );
			add_post_meta( $this->new_post_id, '_igp_instagram_id', $image->image_id );
			add_post_meta( $this->new_post_id, 'ig_likes', $image->likes_count );

			// Template tags for meta
			$template_tags = instagrate_pro()->tags->get_template_tags( 'meta' );
			$account_meta  = get_post_meta( $this->account_id );
			foreach ( $account_meta as $meta_key => $meta_value ) {
				// Add meta to new post
				if ( $meta_key != '_instagrate_pro_settings' && $meta_value[0] != '' ) {
					instagrate_pro()->debug->make_debug( 'Add Post Meta: Key - ' . $meta_key . ', Template - ' . $meta_value[0] . ', Value - ' . instagrate_pro()->tags->replace_template_tags( $meta_value[0], $template_tags, '' ) );
					add_post_meta( $this->new_post_id, $meta_key, instagrate_pro()->tags->replace_template_tags( $meta_value[0], $template_tags, '' ) );
				}
			}
			// Credit links
			if ( $this->credit_link ) {
				$wp_post_content = $this->comment_credit . $wp_post_content . $this->link_credit;
			}

			// Update post
			$this->update_post( $wp_post_title, $wp_post_content, $wp_post_excerpt );

			// Set image in table to published
			instagrate_pro()->images->save_image_meta( $image->image_id, $this->account_id, array( 'status' => 'posted' ) );
		}
	}

	/**
	 * Process the creation of one post with images grouped
	 *
	 * @param $i
	 * @param $count
	 */
	private function group( $i, $count ) {
		// Template tags for title
		$template_tags = instagrate_pro()->tags->get_template_tags( 'title' );
		// Custom title
		$wp_post_title = instagrate_pro()->tags->replace_template_tags( $this->account->post_title, $template_tags, $this->default_title );
		// Limit post title
		$wp_post_title = $this->limit_title( $wp_post_title, $this->title_limit_type, $this->title_limit );
		// Post date
		$wp_post_date_gmt = date( 'Y-m-d H:i:s', current_time( 'timestamp', 1 ) );
		$wp_post_date     = date( 'Y-m-d H:i:s', current_time( 'timestamp', 0 ) );
		// Insert post
		$wp_post_content = '';
		$lat_lng         = array();

		// Insert post
		$this->insert_post( $wp_post_title, $wp_post_content, $wp_post_date, $wp_post_date_gmt );

		// Post Tax and Term
		$this->post_taxonomy();

		// Post format
		$this->post_format();

		// Add all images to Post Content
		foreach ( $this->images as $image ) {
			// Check image
			if ( ! $this->check_image( $image ) ) {
				continue;
			}

			// Tags for title
			$this->title_tags( $image );

			// Post Tags
			$this->post_tags( $image );

			// Post image
			$this->post_image( $image );

			// Post Video
			$this->post_video( $image );

			// Location map (shortcode)
			$image->map = '';
			if ( $image->latitude != '' && $image->longitude != '' ) {
				$lat_lng[] = array(
					'lat'    => $image->latitude,
					'lng'    => $image->longitude,
					'marker' => $image->location_name,
					'image'  => $image->instagram_image,
				);
				$image->map = '[igp_map lat="' . $image->latitude . '" lon="' . $image->longitude . '" marker="' . $image->location_name . '" style="' . $this->settings->map_style . '" class="' . $this->settings->map_css . '" width="' . $this->settings->map_width . '" height="' . $this->settings->map_height . '" width_type="' . $this->settings->map_width_type . '" height_type="' . $this->settings->map_height_type . '" zoom="' . $this->map_zoom . '"]';
			}
			// Custom body text
			$new_content = $this->content_tags( $image );

			$gallery_exist = strrpos( $wp_post_content, '[gallery]' );
			if ( $gallery_exist !== false ) {
				$new_content = str_replace( '[gallery]', '', $new_content );
			}
			$wp_post_content .= $new_content;

			// Set image in table to published
			instagrate_pro()->images->save_image_meta( $image->image_id, $this->account_id, array( 'status' => 'posted' ) );
		}
		// Update post meta with Lat Lng
		update_post_meta( $this->new_post_id, '_igp_latlon', $lat_lng );

		// Multi Map
		if ( $this->multimap != 'none' ) {
			$multimap_sc     = '[igp_multimap style="' . $this->settings->map_style . '" class="' . $this->settings->map_css . '" width="' . $this->settings->map_width . '" height="' . $this->settings->map_height . '" width_type="' . $this->settings->map_width_type . '" height_type="' . $this->settings->map_height_type . '" zoom="' . $this->map_zoom . '"]';
			$wp_post_content = ( $this->multimap == 'top' ) ? $multimap_sc . '<br>' . $wp_post_content : $wp_post_content . '<br>' . $multimap_sc;
		}

		// Credit links
		if ( $this->credit_link ) {
			$wp_post_content = $this->comment_credit . $wp_post_content . $this->link_credit;
		}

		// Update post
		$this->update_post( $wp_post_title, $wp_post_content );
	}

	/**
	 * Process the creation of image content to the same single post
	 *
	 * @param $i
	 * @param $count
	 */
	private function single( $i, $count ) {
		$this->new_post_id = $this->settings->posting_same_post;
		$this->new_post    = get_post( $this->new_post_id );
		$old_content       = $this->new_post->post_content;

		// Add all images to Post Content
		$wp_post_content = '';
		$old_lat_lng     = get_post_meta( $this->new_post_id, '_igp_latlon' );
		if ( ! is_array( $old_lat_lng ) ) {
			$old_lat_lng = array();
		}
		if ( isset( $old_lat_lng[0] ) ) {
			$old_lat_lng = $old_lat_lng[0];
		}
		$lat_lng = array();

		foreach ( $this->images as $image ) {
			// Check image
			if ( ! $this->check_image( $image ) ) {
				continue;
			}

			// Get title template tags loaded
			$this->title_tags( $image );

			// Post Tags
			$this->post_tags( $image );

			// Post image
			$this->post_image( $image );

			// Post Video
			$this->post_video( $image );

			// Location map (shortcode)
			$image->map = '';
			if ( $image->latitude != '' && $image->longitude != '' ) {
				$lat_lng[]  = array(
					'lat'    => $image->latitude,
					'lng'    => $image->longitude,
					'marker' => $image->location_name,
					'image'  => $image->instagram_image,
				);
				$image->map = '[igp_map lat="' . $image->latitude . '" lon="' . $image->longitude . '" marker="' . $image->location_name . '" style="' . $this->settings->map_style . '" class="' . $this->settings->map_css . '" width="' . $this->settings->map_width . '" height="' . $this->settings->map_height . '" width_type="' . $this->settings->map_width_type . '" height_type="' . $this->settings->map_height_type . '" zoom="' . $this->map_zoom . '"]';
			}

			// Custom body text
			$new_content = $this->content_tags( $image );

			$gallery_exist = strrpos( $wp_post_content, '[gallery]' );
			if ( $gallery_exist !== false ) {
				$new_content = str_replace( '[gallery]', '', $new_content );
			}
			$wp_post_content .= $new_content;

			// Set image in table to published
			instagrate_pro()->images->save_image_meta( $image->image_id, $this->account_id, array( 'status' => 'posted' ) );
		}

		// Update post meta with Lat Lng
		$lat_lng = array_merge( (array) $old_lat_lng, $lat_lng );
		update_post_meta( $this->new_post_id, '_igp_latlon', $lat_lng );

		// Check for existing gallery content
		$wp_post_content = $this->post_existing_gallery( $wp_post_content, $old_content );
		// Update the post into the database
		$this->update_post( false, $wp_post_content );
	}

	/**
	 * Main wrapper for triggering the posting for an account
	 *
	 * @param        $account_id
	 * @param string $frequency
	 * @param string $schedule
	 *
	 * @return mixed
	 */
	function post_account( $account_id, $frequency = 'schedule', $schedule = '' ) {
		$this->account_id = $account_id;
		$this->account    = get_post( $account_id );
		$this->settings   = (object) get_post_meta( $account_id, '_instagrate_pro_settings', true );

		if ( ! $this->check_account( $frequency ) ) {
			return;
		}

		if ( $frequency == 'schedule' ) {
			$schedule = Instagrate_Pro_Helper::setting( 'posting_schedule', 'igp_daily', $this->settings );
		}

		// Lock Account
		instagrate_pro()->accounts->lock_account( $account_id, true );
		instagrate_pro()->debug->make_debug( 'Account Now Locked' );

		// Set up soon defaults
		$this->general_defaults( $frequency, $schedule );

		// Check the account has a token and last id
		if ( isset( $this->settings->token ) && $this->settings->token == '' && isset( $this->settings->last_id ) && $this->settings->last_id == '' ) {
			return $this->post_exit( 'Account has empty token and last id' );
		}

		$this->settings->posting_frequency = Instagrate_Pro_Helper::setting( 'posting_frequency', 'constant', $this->settings );
		// Check the account has the correct frequency
		if ( $frequency != $this->settings->posting_frequency ) {
			return $this->post_exit( 'Account frequency (' . $this->settings->posting_frequency . ') is not the running frequency (' . $frequency . ')' );
		}

		// Constant specific checks
		if ( ! $this->constant_post_check() ) {
			return;
		}

		//Account specific settings
		$this->account_defaults();

		// Retrieve newer images from Instragam
		instagrate_pro()->accounts->retrieve_images( $this->account_id );

		// Get all images pending
		$this->images = instagrate_pro()->images->get_images( $this->account_id, 'pending', $this->image_order, ! $this->dup_image, 'posting' );

		if ( ! $this->dup_image ) {
			instagrate_pro()->images->update_duplicate_images( $this->account_id );
		}

		if ( ! $this->images ) {
			return $this->post_exit( '0 Images to post' );
		}

		// Prepare images
		$this->prepare_images();

		// Media Filtering
		$this->media_type_filtering();

		// Hashtag filtering on images
		$this->hashtag_filtering();

		// Check there are images left to post
		if ( empty( $this->images ) ) {
			return $this->post_exit( '0 Images to post' );
		} else {
			$count = sizeof( $this->images );
			instagrate_pro()->debug->make_debug( 'Images to post: ' . $count );
			$i = 0;
		}

		// Reset template tag values
		instagrate_pro()->tags->clear_values();

		$function = $this->settings->posting_multiple;
		// Functions for different for posting_multiple config
		if ( method_exists( $this, $function ) ) {
			$this->$function( $i, $count );
		}

		// Write to debug file if mode on
		instagrate_pro()->debug->write_debug( $this->account_id );

		return $this->images;
	}

	/**
	 * Limit a title by numerous methods
	 *
	 * @param $wp_post_title
	 * @param $title_limit_type
	 * @param $title_limit
	 *
	 * @return mixed|void
	 */
	public function limit_title( $wp_post_title, $title_limit_type, $title_limit ) {
		if ( $title_limit != '' && is_numeric( $title_limit ) && floor( $title_limit ) == $title_limit ) {
			if ( $title_limit_type == 'characters' ) {
				$wp_post_title = substr( $wp_post_title, 0, $title_limit );
			} else if ( $title_limit_type == 'words' ) {
				$words         = explode( " ", $wp_post_title );
				$wp_post_title = implode( " ", array_splice( $words, 0, $title_limit ) );
			}
		}

		return apply_filters( 'igp_post_title', $wp_post_title );
	}

	/**
	 * Helper function to exit the posting and write out debug
	 *
	 * @param string $msg
	 *
	 * @return bool
	 */
	private function post_exit( $msg = '' ) {
		if ( $msg != '' ) {
			instagrate_pro()->debug->make_debug( $msg );
		}
		instagrate_pro()->debug->write_debug( $this->account_id );

		return false;
	}

	function ajax_process_manual_post() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['frequency'] ) ) {
			return 0;
		}
		$response['error']   = false;
		$response['message'] = '';
		$images              = instagrate_pro()->controller->post_account( $_POST['post_id'], 'manual' );
		$response['stats']   = instagrate_pro()->accounts->account_stats( $_POST['post_id'] );
		$response['meta']    = 'Done';
		$response['images']  = $images;
		$msg                 = "No media posted";
		if ( is_array( $images ) ) {
			$count     = sizeof( $images );
			$image_txt = ( $count > 1 ) ? ' media items posted' : ' media item posted';
			$msg       = $count . $image_txt;
		}
		$response['message'] = $msg;
		echo json_encode( $response );
		die;
	}
}
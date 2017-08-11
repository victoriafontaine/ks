<?php

class Instagrate_Pro_Accounts {

	public function __construct() {
		add_action( 'admin_init', array( $this, 'instagram_connect' ) );
		add_action( 'admin_init', array( $this, 'onload_images' ) );
		add_action( 'delete_post', array( $this, 'delete_account' ) );
		add_action( 'wp_ajax_igp_duplicate_account', array( $this, 'ajax_duplicate_account' ) );
		add_action( 'wp_ajax_igp_bulk_edit_status', array( $this, 'ajax_bulk_edit_status' ) );
		add_action( 'wp_ajax_igp_bulk_toggle_status', array( $this, 'ajax_bulk_toggle_status' ) );
		add_action( 'wp_ajax_igp_save_meta', array( $this, 'ajax_save_meta' ) );
		add_action( 'wp_ajax_igp_load_meta', array( $this, 'ajax_load_meta' ) );
		add_action( 'wp_ajax_igp_manual_frequency', array( $this, 'ajax_manual_frequency' ) );
		add_action( 'wp_ajax_igp_get_user_id', array( $this, 'ajax_get_user_id' ) );
		add_action( 'wp_ajax_igp_disconnect', array( $this, 'ajax_disconnect_instagram' ) );
		add_action( 'wp_ajax_igp_refresh', array( $this, 'ajax_refresh_instagram' ) );
		add_action( 'wp_ajax_igp_get_locations', array( $this, 'ajax_get_locations' ) );
		add_action( 'wp_ajax_igp_post_objects', array( $this, 'ajax_get_post_objects' ) );
		add_action( 'wp_ajax_igp_taxonomies', array( $this, 'ajax_get_taxonomies' ) );
		add_action( 'wp_ajax_igp_terms', array( $this, 'ajax_get_terms' ) );
		add_action( 'wp_ajax_igp_tag_taxonomies', array( $this, 'ajax_get_tag_taxonomies' ) );
		add_action( 'wp_ajax_igp_change_stream', array( $this, 'ajax_change_stream' ) );
		add_action( 'wp_ajax_igp_load_images', array( $this, 'ajax_load_images' ) );
	}

	public function get_images_key( $account_id, $numbers = false ) {
		$pending           = 0;
		$posted            = 0;
		$ignore            = 0;
		$processing_amount = 0;
		$moderating_amount = 0;
		if ( $account_id != 0 ) {
			$stats = $this->account_stats( $account_id );
			if ( is_array( $stats ) && ! empty( $stats ) ) {
				$pending           = ( isset( $stats['pending']->Total ) ) ? $stats['pending']->Total : 0;
				$posted            = ( isset( $stats['posted']->Total ) ) ? $stats['posted']->Total : 0;
				$ignore            = ( isset( $stats['ignore']->Total ) ) ? $stats['ignore']->Total : 0;
				$processing_amount = ( isset( $stats['posting']->Total ) ) ? $stats['posting']->Total : 0;
				$moderating_amount = ( isset( $stats['moderate']->Total ) ) ? $stats['moderate']->Total : 0;
			}
		}
		$pending    = '<span class="stat" title="' . __( 'Pending', 'instagrate-pro' ) . '">' . $pending . '</span>';
		$posted     = '<span class="stat" title="' . __( 'Posted', 'instagrate-pro' ) . '">' . $posted . '</span>';
		$ignore     = '<span class="stat" title="' . __( 'Ignore', 'instagrate-pro' ) . '">' . $ignore . '</span>';
		$processing = '<span class="stat" title="' . __( 'Posting', 'instagrate-pro' ) . '">' . $processing_amount . '</span>';
		$moderating = '<span class="stat" title="' . __( 'Awaiting Moderation', 'instagrate-pro' ) . '">' . $moderating_amount . '</span>';

		if ( ! $numbers ) {
			$pending    = $pending . ' ' . __( 'Pending', 'instagrate-pro' );
			$processing = $processing . ' ' . __( 'Posting', 'instagrate-pro' );
			$posted     = $posted . ' ' . __( 'Posted', 'instagrate-pro' );
			$moderating = $moderating . ' '. __( 'Awaiting Moderation', 'instagrate-pro' );
			$ignore     = $ignore . ' ' . __( 'Ignore', 'instagrate-pro' );
		}

		$html = '<div class="images-key">';

		$moderating_class = ( $moderating_amount > 0 ) ? '': ' hide';
		$moderate_url = admin_url( 'edit.php?post_type='. INSTAGRATEPRO_POST_TYPE .'&page=moderation&account_id=' . $account_id);
		$html .= '<label class="igp-status moderate'. $moderating_class .'"><a href="' . $moderate_url .'">' . $moderating . '</a></label>';

		$html .= '<label class="igp-status pending">' . $pending . '</label>';

		if ( $processing_amount > 0 ) {
			$html .= '<label class="igp-status posting">' . $processing . '</label>';
		}
		$html .= '<label class="igp-status posted">' . $posted . '</label>';
		$html .= '<label class="igp-status ignore">' . $ignore . '</label>';

		$html .= '</div>';

		return $html;
	}

	public function account_stats( $account_id ) {
		global $wpdb;
		$table = instagrate_pro()->images->get_table_name();
		$stats = $wpdb->get_results(
			"	SELECT status, COUNT(*) AS Total
										FROM $table
										WHERE account_id = $account_id
										GROUP BY status", OBJECT_K
		);

		return $stats;
	}

	public function instagram_connect() {
		if ( instagrate_pro()->instagram->custom_api_client() ) {
			if ( isset( $_GET['return_uri'] ) ) {
				$redirect = '';
				if ( isset( $_GET['error'] ) || isset( $_GET['error_reason'] ) || isset( $_GET['error_description'] ) ) {
					$error    = $_GET['error'];
					$reason   = $_GET['error_reason'];
					$descp    = $_GET['error_description'];
					$url      = base64_decode( $_GET['return_uri'] );
					$redirect = $url . '&igp_error=' . $error . '&igp_error_reason=' . $reason . '&igp_error_description=' . $descp;
				} else {
					if ( isset( $_GET['code'] ) ) {
						$code     = $_GET['code'];
						$url      = base64_decode( $_GET['return_uri'] );
						$redirect = $url . '&igp_code=' . $code;
					}
				}

				if ( $redirect != '' ) {
					wp_redirect( $redirect );
					die();
				}

			}
		}

		if ( isset( $_GET['post'] ) && ( isset( $_GET['igp_code'] ) || isset( $_GET['igp_error'] ) ) ) {
			if ( isset( $_GET['igp_code'] ) ) {
				$code             = $_GET['igp_code'];
				$account_settings = get_post_meta( $_GET['post'], '_instagrate_pro_settings', true );
				$auth_token       = instagrate_pro()->instagram->instagram_auth_token( $code, isset( $_GET['reconnect'] ) );
				if ( $auth_token['error'] == true ) {
					echo '<div class="error"><strong>Error getting Access Token: </strong><p>' . $auth_token['message'] . '</p></div>';

					return;
				}
				$auth_token = $auth_token['token'];
				if ( isset( $auth_token->code ) ) {
					$account_settings['ig_error'] = '<strong>' . $auth_token->error_type . '</strong> ' . $auth_token->error_message;
					$redirect                     = get_admin_url() . 'post.php?post=' . $_GET['post'] . '&action=edit';
				} else {
					if ( isset( $_GET['reconnect'] ) ) {
						unset( $account_settings['needs_reconnect'] );
					}
					$existing_user                        = isset( $account_settings['user_id'] ) ? $account_settings['user_id'] : '';
					$account_settings['token']            = $auth_token->access_token;
					$account_settings['user_id']          = isset( $auth_token->user->id ) ? $auth_token->user->id : '';
					$account_settings['username']         = isset( $auth_token->user->username ) ? $auth_token->user->username : '';
					$account_settings['bio']              = ''; //isset( $auth_token->user->bio ) ? $this->clean_caption( $auth_token->user->bio ) : '';
					$account_settings['user_thumb']       = isset( $auth_token->user->profile_picture ) ? $auth_token->user->profile_picture : '';
					$account_settings['instagram_images'] = isset( $account_settings['instagram_images'] ) ? $account_settings['instagram_images'] : 'recent';

					// Fetch images for new connection or if reconnecting
					if ( ! isset( $_GET['reconnect'] )
					     || ( isset( $_GET['reconnect'] ) && $existing_user !== $account_settings['user_id'] && 'recent' === $account_settings['instagram_images'] )
					) {
						$response                             = $this->instagram_get_images( $_GET['post'], 'recent', $auth_token->access_token, '', $auth_token->user->id );
						$data                                 = $response[0];
						$last_id                              = $response[1];
						$next_url                             = $response[2];
						if ( $data != '' ) {
							if ( $data->meta->code == 200 ) {
								$account_settings['next_url'] = $next_url;
								$images                       = $data->data;
								$account_settings['last_id']  = $last_id;
								$moderate                     = Instagrate_Pro_Helper::setting( 'moderate_images', 'off', $account_settings );
								$status                       = ( 'on' == $moderate ) ? 'moderate' : 'pending';
								$this->insert_images( $_GET['post'], $images, $status );
								$account_settings['ig_error'] = '';
								$redirect                     = get_admin_url() . 'post.php?post=' . $_GET['post'] . '&action=edit&message=11';
							} else {
								$account_settings['ig_error'] = '<strong>' . $data->meta->error_type . '</strong> ' . $data->meta->error_message;
							}
						}
					}
					$redirect = get_admin_url() . 'post.php?post=' . $_GET['post'] . '&action=edit';
				}
				update_post_meta( $_GET['post'], '_instagrate_pro_settings', $account_settings );
				header( 'Location: ' . $redirect );
			}
			if ( isset( $_GET['igp_error'] ) ) {
				$redirect = get_admin_url() . 'post.php?post=' . $_GET['post'] . '&action=edit&message=13';
				header( 'Location: ' . $redirect );
			}
		}
	}

	public function onload_images() {
		// Retrieve Images if PT is Instagrate_pro, account is logged in and not auto-draft
		global $post;
		if ( isset( $_GET['post'] ) && isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {
			$post = get_post( $_GET['post'] );
			if ( 'instagrate_pro' == get_post_type( $post ) && $post->post_status != 'auto-draft' ) {
				$this->retrieve_images( $post->ID );
			}
		}
	}

	public function retrieve_images( $account_id ) {
		$account_settings = get_post_meta( $account_id, '_instagrate_pro_settings', true );
		$settings         = (object) $account_settings;
		if ( ! isset( $settings->last_id ) ) {
			return;
		}
		if ( $settings->last_id != '' && $settings->token != '' ) {
			$moderate = Instagrate_Pro_Helper::setting('moderate_images', 'off', $account_settings );
			$status = ( 'on' == $moderate ) ? 'moderate' : 'pending';
			$tags        = ( isset( $settings->instagram_hashtags ) ) ? $settings->instagram_hashtags : '';
			$tags        = instagrate_pro()->helper->prepare_tags( $tags );
			$tag_name    = instagrate_pro()->helper->get_first_tag( $tags );
			$users_id    = ( isset( $settings->instagram_users_id ) ) ? $settings->instagram_users_id : '';
			$location_id = ( isset( $settings->instagram_location_id ) ) ? $settings->instagram_location_id : '';
			$response    = $this->instagram_get_images( $account_id, $settings->instagram_images, $settings->token, $settings->last_id, $settings->user_id, $tag_name, $users_id, $location_id );
			$data        = $response[0];
			$last_id     = $response[1];
			if ( $data != '' && isset( $data->data ) && ( is_array( $data->data ) && ! empty( $data->data ) ) ) {
				if ( $data->meta->code == 200 ) {
					$images                       = $data->data;
					if ( '' !== $last_id ) {
						$account_settings['last_id'] = $last_id;
					}
					$account_settings['ig_error'] = '';
					$this->insert_images( $account_id, $images, $status );
				} else {
					$account_settings['ig_error'] = '<strong>' . $data->meta->error_type . '</strong> ' . $data->meta->error_message;
				}
				update_post_meta( $account_id, '_instagrate_pro_settings', $account_settings );
			}
		}
	}

	public function insert_images( $account_id, $images, $status = 'pending' ) {
		global $wpdb;
		if ( ! $images ) {
			return;
		}
		foreach ( $images as $key => $image ) {
			if ( instagrate_pro()->images->check_account_image_exists( $account_id, $image->id ) ) {
				continue;
			}

			$tags = '';
			$clean_tags = '';
			if ( isset( $image->tags ) ) {
				$clean_tags = instagrate_pro()->helper->clean_tags( $image->tags );
				$tags = serialize( $clean_tags);
			}

			$caption               = '';
			$caption_clean         = '';
			$caption_clean_no_tags = '';
			if ( isset( $image->caption->text ) ) {
				$fixed_caption         = instagrate_pro()->helper->fix_tags_caption( $image->caption->text );
				$caption               = instagrate_pro()->helper->clean_initial_caption( $image->caption->text );
				$caption_clean         = instagrate_pro()->helper->clean_caption( $fixed_caption );
				$caption_clean_no_tags = instagrate_pro()->helper->caption_strip_tags( $caption_clean, $clean_tags );
			}
			$comments = ( isset( $image->comments->data ) ) ? $image->comments->data : array();
			if ( Instagrate_Pro_Helper::setting( 'igpsettings_comments_enable-comments', '0' ) == 1 && $image->comments->count > 8 ) {
				$comments = $this->get_comments( '', $image->id, $account_id );
			}

			$data = array(
				'account_id'            => esc_attr( $account_id ),
				'image_id'              => esc_attr( $image->id ),
				'status'                => $status,
				'image_timestamp'       => esc_attr( $image->created_time ),
				'media_type'            => esc_attr( $image->type ),
				'image_url'             => esc_attr( $image->images->standard_resolution->url ),
				'image_thumb_url'       => esc_attr( $image->images->thumbnail->url ),
				'video_url'             => ( isset( $image->videos->standard_resolution->url ) ) ? esc_attr( $image->videos->standard_resolution->url ) : '',
				'tags'                  => $tags,
				'filter'                => ( isset( $image->filter ) ) ? esc_attr( $image->filter ) : 'nofilter',
				'link'                  => esc_attr( $image->link ),
				'caption'               => $caption,
				'caption_clean'         => $caption_clean,
				'caption_clean_no_tags' => $caption_clean_no_tags,
				'username'              => esc_attr( $image->user->username ),
				'user_id'               => esc_attr( $image->user->id ),
				'user_image_url'        => esc_attr( $image->user->profile_picture ),
				'latitude'              => ( isset( $image->location->latitude ) ) ? esc_attr( $image->location->latitude ) : '',
				'longitude'             => ( isset( $image->location->longitude ) ) ? esc_attr( $image->location->longitude ) : '',
				'location_name'         => ( isset( $image->location->name ) ) ? esc_attr( $image->location->name ) : '',
				'location_id'           => ( isset( $image->location->id ) ) ? esc_attr( $image->location->id ) : '',
				'comments_count'        => esc_attr( $image->comments->count ),
				'likes_count'           => esc_attr( $image->likes->count ),
				'comments'              => base64_encode( serialize( $comments ) ),
			);
			$wpdb->insert( instagrate_pro()->images->get_table_name(), $data );
		}
	}

	function get_comments( $access_token = '', $image_id, $account_id = 0 ) {
		if ( $access_token == '' ) {
			$account_settings = get_post_meta( $account_id, '_instagrate_pro_settings', true );
			$access_token     = $account_settings['token'];
		}
		$comments = instagrate_pro()->instagram->get_media_comments( $access_token, $image_id );

		return $comments;
	}

	/**
	 * Are we allowed to get images from Instagram
	 *
	 * @param array  $options
	 * @param string $stream
	 *
	 * @return bool
	 */
	public function can_get_images( $options, $stream = '' ) {
		if ( isset( $options['needs_reconnect'] ) ) {
			// Needs reconnection
			return false;
		}

		$stream = empty( $stream) ? Instagrate_Pro_Helper::setting( 'instagram_images', 'recent', $options ) : $stream;
		if ( 'feed' === $stream) {
			// Deprecated feed endpoint
			return false;
		}
		
		return true;
	}

	public function instagram_get_images( $account_id, $stream, $access = '', $min_id = '', $user_id = '', $tag_name = '', $users_id = '', $location_id = '' ) {
		$response    = array();
		$response[0] = '';
		$response[1] = '';
		$response[2] = '';

		$account_settings = get_post_meta( $account_id, '_instagrate_pro_settings', true );

		if ( $access == '' ) {
			$access = $account_settings['token'];
		}
		if ( $user_id == '' ) {
			$user_id = $account_settings['user_id'];
		}
		if ( $users_id == '' ) {
			$users_id = Instagrate_Pro_Helper::setting( 'instagram_users_id', '', $account_settings );
		}

		if ( false === $this->can_get_images( $account_settings, $stream ) ) {
			return $response;
		}

		$params    = '';
		$param_key = 'min_id';
		switch ( $stream ) {
			case 'recent':
				$url = 'users/' . $user_id . '/media/recent/';
				break;
			case 'feed':
				// Deprecated
				return $response;
				break;
			case 'users':
				$url = 'users/' . $users_id . '/media/recent/';
				break;
			case 'tagged':
				$url       = 'tags/' . $tag_name . '/media/recent/';
				$param_key = 'min_tag_id';
				break;
			case 'location':
				if ( $location_id == '' || $location_id == '0' ) {
					return $response;
				}
				$url = 'locations/' . $location_id . '/media/recent/';
				break;
			default:
				$url = 'users/' . $user_id . '/media/recent/';
				break;
		}
		if ( $min_id != '' ) {
			$params = array( $param_key => $min_id );
		}
		$data = instagrate_pro()->http->do_http_request( $access, $url, $params );
		if ( ! $data ) {
			return $response;
		}
		if ( $data->meta->code == 200 ) {
			if ( is_array( $data->data ) && ! empty( $data->data ) ) {
				$images  = $data->data;
				$last_id = $images[0]->id;
				$count   = 1;
				if ( $stream == 'tagged' ) {
					$last_id = isset( $data->pagination->min_tag_id ) ? $data->pagination->min_tag_id : '';
				}
				$next_url = ( isset( $data->pagination->next_url ) ) ? $data->pagination->next_url : '';
				if ( $min_id != '' ) {
					if ( isset( $data->pagination->next_url ) ) {
						$nexturl = $data->pagination->next_url;
						do {
							$count++;
							$new_data = instagrate_pro()->http->do_http_request( $access, '', '', $nexturl );
							unset( $nexturl );
							if ( isset( $new_data->pagination->next_url ) ) {
								$nexturl = $new_data->pagination->next_url;
							}
							if ( $stream != 'tagged' || ( $stream == 'tagged' && $new_data->pagination->min_tag_id > $min_id ) ) {
								if ( is_array( $new_data->data ) && ! empty( $new_data->data ) ) {
									$images = array_merge( $images, $new_data->data );
								}
							}
						} while ( ( $stream != 'tagged' && isset( $nexturl ) ) || ( $stream == 'tagged' && isset( $nexturl ) && $new_data->pagination->min_tag_id > $min_id ) );
					}
				}
				$data->data  = $images;
				$response[0] = $data;
				$response[1] = $last_id;
				$response[2] = $next_url;
			} else {
				// No Images returned
				$response[0] = $data;
			}
		} else {
			$account_settings['ig_error'] = '<strong>' . $data->meta->error_type . '</strong> ' . $data->meta->error_message;
			update_post_meta( $account_id, '_instagrate_pro_settings', $account_settings );
		}

		return $response;
	}

	function load_images( $account_id ) {
		$images           = array();
		$account_settings = get_post_meta( $account_id, '_instagrate_pro_settings', true );
		$next_url         = $account_settings['next_url'];
		if ( $next_url == '' ) {
			return $images;
		}

		if ( false === $this->can_get_images( $account_settings ) ) {
			return $images;
		}

		$access = $account_settings['token'];
		$data   = instagrate_pro()->http->do_http_request( $access, '', '', $next_url );
		if ( ! $data ) {
			return $images;
		}
		if ( $data->meta->code == 200 ) {
			$account_settings['next_url'] = ( isset( $data->pagination->next_url ) ) ? $data->pagination->next_url : '';
			$account_settings['ig_error'] = '';
			$images                       = $data->data;
			update_post_meta( $account_id, '_instagrate_pro_settings', $account_settings );
			$moderate = Instagrate_Pro_Helper::setting('moderate_images', 'off', $account_settings );
			$status = ( 'on' == $moderate ) ? 'moderate' : 'pending';
			$this->insert_images( $account_id, $images, $status );

			return $images;
		} else {
			$account_settings['ig_error'] = '<strong>' . $data->meta->error_type . '</strong> ' . $data->meta->error_message;
			update_post_meta( $account_id, '_instagrate_pro_settings', $account_settings );
		}

		return $images;
	}

	function change_stream( $account_id, $stream, $tags, $tag, $users_id, $location_id, $status = 'pending' ) {
		$images           = array();
		$account_settings = get_post_meta( $account_id, '_instagrate_pro_settings', true );
		$access           = $account_settings['token'];
		$response         = $this->instagram_get_images( $account_id, $stream, $access, '', '', $tag, $users_id, $location_id );
		$data             = $response[0];
		$last_id          = $response[1];
		$next_url         = $response[2];
		if ( $data == '' ) {
			return $images;
		}
		if ( ! isset( $data->meta->code ) ) {
			return $images;
		}
		if ( $data->meta->code == 200 ) {
			instagrate_pro()->images->delete_images( $account_id );
			$account_settings['next_url']              = $next_url;
			$account_settings['last_id']               = $last_id;
			$account_settings['instagram_images']      = $stream;
			$account_settings['instagram_location_id'] = $location_id;
			$account_settings['instagram_hashtags']    = $tags;
			$account_settings['ig_error']              = '';
			if ( is_array( $data->data ) && ! empty( $data->data ) ) {
				$images = $data->data;
				$this->insert_images( $account_id, $images, $status );
			}
			update_post_meta( $account_id, '_instagrate_pro_settings', $account_settings );

			return $images;
		} else {
			$account_settings['ig_error'] = '<strong>' . $data->meta->error_type . '</strong> ' . $data->meta->error_message;
			update_post_meta( $account_id, '_instagrate_pro_settings', $account_settings );
		}

		return $images;
	}

	function get_user_id( $account_id, $username ) {
		$account_settings = get_post_meta( $account_id, '_instagrate_pro_settings', true );
		$access           = $account_settings['token'];
		$options          = get_post_meta( $account_id, '_instagrate_pro_settings', true );
		$url              = 'users/search/';
		$users_id         = '';
		$params           = array( 'q' => $username, 'count' => 1 );
		$data             = instagrate_pro()->http->do_http_request( $access, $url, $params );
		if ( ! $data ) {
			return;
		}
		if ( $data->meta->code == 200 ) {
			$usernames = $data->data;
			if ( $usernames && is_array( $usernames ) ) {
				$users_id                      = $usernames[0]->id;
				$options['instagram_users_id'] = $users_id;
				$options['instagram_user']     = $username;
			}
			$options['ig_error'] = '';
		} else {
			$options['ig_error'] = '<strong>' . $data->meta->error_type . '</strong> ' . $data->meta->error_message;
		}
		update_post_meta( $account_id, '_instagrate_pro_settings', $options );

		return $users_id;
	}

	function get_location_name( $account_id, $location_id ) {
		$account_settings = get_post_meta( $account_id, '_instagrate_pro_settings', true );
		$access           = $account_settings['token'];
		$url              = 'locations/' . $location_id;
		$data             = instagrate_pro()->http->do_http_request( $access, $url, '' );
		if ( ! $data ) {
			return;
		}
		if ( $data->meta->code == 200 ) {
			$location = $data->data;

			return $location->name;
		}

		return 'Not Found';
	}

	function delete_account( $post_id ) {
		$post = get_post( $post_id );
		if ( $post->post_type == INSTAGRATEPRO_POST_TYPE ) {
			instagrate_pro()->images->delete_images( $post_id );
			instagrate_pro()->scheduler->clear_all_schedules( $post_id );
		}
	}

	/**
	 * Get the Instagrate accounts
	 *
	 * @param string   $status
	 * @param null|int $account_id
	 *
	 * @return array
	 */
	function get_accounts( $status = '', $account_id = null ) {
		$accounts = array();
		global $wpdb;
		$post_type = INSTAGRATEPRO_POST_TYPE;
		$where = '';
		if ( $status != '' ) {
			$where .= " AND post_status = '" . $status . "'";
		}
		if ( ! is_null( $account_id ) && is_int( $account_id ) ) {
			$where .= " AND ID = $account_id";
		}
		$post_data = $wpdb->get_results(
			$wpdb->prepare(
				"	SELECT * FROM $wpdb->posts
								WHERE post_type = %s
								AND post_status <> 'auto-draft'
								$where
								ORDER BY post_date desc 	", $post_type
			)
		);

		if ( $post_data ) {
			foreach ( $post_data as $post_item ) {
				$meta                       = array(
					'account_status' => $post_item->post_status,
					'custom_title'   => $post_item->post_title,
					'custom_text'    => $post_item->post_content
				);
				$accounts[ $post_item->ID ] = $meta;
			}
		}

		return $accounts;
	}

	function get_account_data() {
		global $post;
		$data     = array();
		$accounts = $this->get_accounts();
		if ( $accounts ) {
			$data['title'] = 'Install Data - Intagrate v' . INSTAGRATEPRO_VERSION;
			foreach ( $accounts as $key => $account ) {
				$account_settings        = get_post_meta( $key, '_instagrate_pro_settings', true );
				$data[ 'title-' . $key ] = 'Account Data - ' . $key;
				// Stats
				$stats = $this->account_stats( $key );
				if ( is_array( $stats ) && ! empty( $stats ) ) {
					$data[ '[' . $key . '] IMAGES PENDING' ] = ( ( isset( $stats['pending'] ) ) ? $stats['pending']->Total : 0 );
					$data[ '[' . $key . '] IMAGES POSTED' ]  = ( ( isset( $stats['posted'] ) ) ? $stats['posted']->Total : 0 );
					$data[ '[' . $key . '] IMAGES IGNORE' ]  = ( ( isset( $stats['ignore'] ) ) ? $stats['ignore']->Total : 0 );
					$data[ '[' . $key . '] IMAGES POSTING' ] = ( ( isset( $stats['posting'] ) ) ? $stats['posting']->Total : 0 );
					$data[ '[' . $key . '] IMAGES AWAITING MODERATION' ] = ( ( isset( $stats['moderate'] ) ) ? $stats['moderate']->Total : 0 );
				} else {
					$data[ 'images-' . $key ] = 'No images';
				}
				foreach ( $account as $meta_key => $meta ) {
					$data[ '[' . $key . '] ' . strtoupper( $meta_key ) ] = $meta;
				}
				foreach ( $account_settings as $setting_key => $setting ) {
					if ( $setting_key == 'post_term' ) {
						$terms     = $setting;
						$term_text = '';
						if ( $terms ) {
							foreach ( $terms as $term_selected ) {
								$term_add = get_term( $term_selected, $account_settings['post_taxonomy'] );
								if ( ! is_wp_error( $term_add ) ) {
									$term_text .= $term_add->name . ', ';
								}
							}
							if ( substr( $term_text, - 2 ) == ', ' ) {
								$term_text = substr( $term_text, 0, - 2 );
							}
						} else {
							$term_text = 'Not Selected';
						}
						$setting = $term_text;
					}
					if ( $setting_key == 'posting_same_post' && $setting != 0 && $account_settings['posting_multiple'] == 'single' ) {
						$single_post = get_post( $setting );
						$setting     = $setting . ' - <a href="' . get_permalink( $setting ) . '">' . $single_post->post_title . '</a>';
					}
					$data[ '[' . $key . '] ' . strtoupper( $setting_key ) ] = $setting;
				}
				$custom_fields = get_post_custom( $key );
				foreach ( $custom_fields as $meta_key => $meta_value ) {
					if ( substr( $meta_key, 0, 1 ) == '_' ) {
						continue;
					}
					$data[ '[' . $key . '] CUSTOM META - ' . $meta_key ] = $meta_value[0];
				}
				$data[ '[' . $key . '] CUSTOM FEATURED IMAGE' ] = has_post_thumbnail( $key );
				unset( $account_settings );
			}
		}
		$settings               = instagrate_pro()->settings->get_settings();;
		$data['title-settings'] = 'Settings';
		foreach ( $settings as $key => $setting ) {
			$data[ strtoupper( $key ) ] = $setting;
		}

		return $data;
	}

	function duplicate_account( $account_id ) {
		$old_post        = get_post( $account_id, ARRAY_A );
		$old_post_meta   = get_post_meta( $account_id, '_instagrate_pro_settings', true );
		$old_custom_meta = get_post_meta( $account_id );
		unset( $old_post['ID'] );
		unset( $old_post['guid'] );
		// New Account
		$new_post_id = wp_insert_post( $old_post );
		// New Account settings
		add_post_meta( $new_post_id, '_instagrate_pro_settings', $old_post_meta );
		// Add account custom meta
		if ( $old_custom_meta ) {
			foreach ( $old_custom_meta as $key => $value ) {
				if ( substr( $key, 0, 1 ) != '_' ) {
					add_post_meta( $new_post_id, $key, $value[0] );
				}
			}
		}
		// Copy Images across
		instagrate_pro()->images->duplicate_images( $account_id, $new_post_id );
		// New schedule if needed
		if ( isset( $old_post_meta['posting_frequency'] ) && 'schedule' == $old_post_meta['posting_frequency'] ) {
			instagrate_pro()->scheduler->clear_all_schedules( $new_post_id );
			$posting_day = isset( $old_post_meta['posting_day'] ) ? $old_post_meta['posting_day'] : '';
			instagrate_pro()->scheduler->set_schedule( $new_post_id, $posting_day, $old_post_meta['posting_time'], $old_post_meta['posting_schedule'] );
		}
	}

	function lock_account( $account_id, $lock ) {
		if ( $account_id == 0 ) {
			return;
		}
		$account_settings = get_post_meta( $account_id, '_instagrate_pro_settings', true );
		if ( isset( $account_settings['locked'] ) ) {
			unset( $account_settings['locked'] );
		}
		if ( $lock ) {
			$account_settings['locked'] = 'locked';
		}
		update_post_meta( $account_id, '_instagrate_pro_settings', $account_settings );
	}

	function ajax_duplicate_account() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['post_id'] ) ) {
			return 0;
		}
		$response['error']    = false;
		$response['redirect'] = '';
		instagrate_pro()->accounts->duplicate_account( $_POST['post_id'] );
		$redirect             = get_admin_url() . 'edit.php?post_type=instagrate_pro&message=14';
		$response['redirect'] = $redirect;
		echo json_encode( $response );
		die;
	}

	function ajax_bulk_edit_status() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['status'] ) || ! isset( $_POST['images'] ) ) {
			return 0;
		}
		$response['error']   = false;
		$response['message'] = '';
		instagrate_pro()->images->bulk_edit_status( $_POST['post_id'], $_POST['status'], $_POST['images'] );
		$response['stats']   = instagrate_pro()->accounts->account_stats( $_POST['post_id'] );
		$response['message'] = 'Images updated to ' . $_POST['status'];
		echo json_encode( $response );
		die;
	}

	function ajax_bulk_toggle_status() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['from_status'] ) || ! isset( $_POST['to_status'] ) ) {
			return 0;
		}
		$response['error']   = false;
		$response['message'] = '';
		instagrate_pro()->images->bulk_toggle_status( $_POST['post_id'], $_POST['from_status'], $_POST['to_status'] );
		$old_post_meta                      = get_post_meta( $_POST['post_id'], '_instagrate_pro_settings', true );
		$old_post_meta['moderate_images'] = ( 'pending' == $_POST['from_status'] ) ? 'on' : 'off';
		update_post_meta( $_POST['post_id'], '_instagrate_pro_settings', $old_post_meta );

		$response['stats']   = instagrate_pro()->accounts->account_stats( $_POST['post_id'] );
		$response['message'] = ucfirst( $_POST['from_status'] ) . ' images updated to ' . $_POST['to_status'];
		echo json_encode( $response );
		die;
	}

	function ajax_save_meta() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['id'] ) ) {
			return 0;
		}
		$response['error']     = false;
		$response['message']   = '';
		$caption_clean_no_tags = instagrate_pro()->helper->clean_caption( stripslashes( $_POST['caption'] ) );
		$caption_clean         = str_replace( stripslashes( $_POST['caption_old'] ), $caption_clean_no_tags, stripslashes( $_POST['caption_clean'] ) );
		$meta                  = array(
			'caption_clean_no_tags' => $caption_clean_no_tags,
			'caption_clean'         => $caption_clean,
			'status'                => strip_tags( $_POST['status'] )
		);
		instagrate_pro()->images->save_image_meta( $_POST['id'], $_POST['post_id'], $meta );
		$response['stats']   = instagrate_pro()->accounts->account_stats( $_POST['post_id'] );
		$response['message'] = 'success';
		echo json_encode( $response );
		die;
	}

	function ajax_load_meta() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['id'] ) ) {
			return 0;
		}
		$response['error']   = false;
		$response['message'] = '';
		$meta                = instagrate_pro()->images->get_image_meta( $_POST['id'], $_POST['post_id'] );
		$response['meta']    = $meta;
		$response['message'] = 'success';
		echo json_encode( $response );
		die;
	}

	function ajax_manual_frequency() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['post_id'] ) ) {
			return 0;
		}
		$response['error']                  = false;
		$response['message']                = '';
		$old_post_meta                      = get_post_meta( $_POST['post_id'], '_instagrate_pro_settings', true );
		$old_post_meta['posting_frequency'] = 'manual';
		update_post_meta( $_POST['post_id'], '_instagrate_pro_settings', $old_post_meta );
		$response['message'] = 'success';
		echo json_encode( $response );
		die;
	}

	function ajax_get_user_id() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['username'] ) ) {
			return 0;
		}
		$response['error']    = false;
		$response['message']  = '';
		$users_id             = instagrate_pro()->accounts->get_user_id( $_POST['post_id'], $_POST['username'] );
		$response['users_id'] = $users_id;
		$response['message']  = 'success';
		echo json_encode( $response );
		die;
	}

	function ajax_disconnect_instagram() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['post_id'] ) ) {
			return 0;
		}
		$response['error']              = false;
		$response['message']            = '';
		$response['redirect']           = '';
		$account_settings               = get_post_meta( $_POST['post_id'], '_instagrate_pro_settings', true );
		$account_settings['token']      = '';
		$account_settings['user_id']    = '';
		$account_settings['username']   = '';
		$account_settings['bio']        = '';
		$account_settings['user_thumb'] = '';
		$account_settings['ig_error']   = '';
		update_post_meta( $_POST['post_id'], '_instagrate_pro_settings', $account_settings );
		instagrate_pro()->images->delete_images( $_POST['post_id'] );
		$redirect             = get_admin_url() . 'post.php?post=' . $_POST['post_id'] . '&action=edit&message=12';
		$response['redirect'] = $redirect;
		$response['message']  = 'success';
		echo json_encode( $response );
		die;
	}

	function ajax_refresh_instagram() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['post_id'] ) ) {
			return 0;
		}
		$response['error']              = false;
		$response['message']            = '';
		$response['redirect']           = '';
		$account_settings               = get_post_meta( $_POST['post_id'], '_instagrate_pro_settings', true );
		$user                           = instagrate_pro()->instagram->get_user( $account_settings['token'], $account_settings['user_id'] );
		$account_settings['user_thumb'] = $user->profile_picture;
		update_post_meta( $_POST['post_id'], '_instagrate_pro_settings', $account_settings );
		$response['user_thumb'] = $account_settings['user_thumb'];
		$response['message']    = 'success';
		echo json_encode( $response );
		die;
	}

	function ajax_get_locations() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['location'] ) || ! isset( $_POST['lat'] ) || ! isset( $_POST['lng'] ) ) {
			return 0;
		}
		$response['error']     = false;
		$response['message']   = '';
		$locations             = instagrate_pro()->instagram->get_locations( $_POST['post_id'], $_POST['location'], $_POST['lat'], $_POST['lng'], true );
		$response['locations'] = $locations['locations'];
		$response['message']   = ( $locations['error'] == '' ) ? 'success' : $locations['error'];
		echo json_encode( $response );
		die;
	}

	function ajax_get_post_objects() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['post_type'] ) ) {
			return 0;
		}
		$response['error']   = false;
		$response['message'] = '';
		$objects             = instagrate_pro()->helper->get_post_objects( $_POST['post_type'] );
		$response['objects'] = $objects;
		$response['message'] = 'success';
		echo json_encode( $response );
		die;
	}

	function ajax_get_taxonomies() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['post_type'] ) ) {
			return 0;
		}
		$response['error']   = false;
		$response['message'] = '';
		$objects             = instagrate_pro()->helper->get_all_taxonomies( $_POST['post_type'] );
		$response['objects'] = $objects;
		$response['message'] = 'success';
		echo json_encode( $response );
		die;
	}

	function ajax_get_terms() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['taxonomy'] ) ) {
			return 0;
		}
		$response['error']   = false;
		$response['message'] = '';
		$objects             = instagrate_pro()->helper->get_all_terms( $_POST['taxonomy'], false );
		$taxonomy_label      = '';
		if ( $_POST['taxonomy'] != '' && $_POST['taxonomy'] != '0' ) {
			$taxonomy_obj   = get_taxonomy( $_POST['taxonomy'] );
			$taxonomy_label = $taxonomy_obj->labels->name;
		}
		$response['label']   = $taxonomy_label;
		$response['objects'] = $objects;
		$response['message'] = 'success';
		echo json_encode( $response );
		die;
	}

	function ajax_get_tag_taxonomies() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['post_type'] ) ) {
			return 0;
		}
		$response['error']   = false;
		$response['message'] = '';
		$objects             = instagrate_pro()->helper->get_all_tag_taxonomies( $_POST['post_type'] );
		$response['objects'] = $objects;
		$response['message'] = 'success';
		echo json_encode( $response );
		die;
	}

	function ajax_change_stream() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['stream'] ) ) {
			return 0;
		}
		$response['error']    = false;
		$response['message']  = '';
		$response['next_url'] = '';
		$response['last_id']  = '';
		$account_settings     = get_post_meta( $_POST['post_id'], '_instagrate_pro_settings', true );
		$moderate = Instagrate_Pro_Helper::setting('moderate_images', 'off', $account_settings );
		$status = ( 'on' == $moderate ) ? 'moderate' : 'pending';
		$images               = instagrate_pro()->accounts->change_stream( $_POST['post_id'], $_POST['stream'], $_POST['tags'], $_POST['tag'], $_POST['users_id'], $_POST['location_id'], $status );
		$response['stats']    = instagrate_pro()->accounts->account_stats( $_POST['post_id'] );
		$account_settings     = get_post_meta( $_POST['post_id'], '_instagrate_pro_settings', true );
		$response['images']   = $images;
		$response['next_url'] = $account_settings['next_url'];
		$response['last_id']  = $account_settings['last_id'];
		$response['message']  = 'success';
		echo json_encode( $response );
		die;
	}

	function ajax_load_images() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		if ( ! isset( $_POST['post_id'] ) ) {
			return 0;
		}
		if ( ! isset( $_POST['img_count'] ) ) {
			return 0;
		}
		$response['error']    = false;
		$response['message']  = '';
		$response['next_url'] = '';
		$response['load']     = false;

		$all_count = instagrate_pro()->images->images_total( $_POST['post_id'] );

		if ( $_POST['img_count'] < $all_count[0]->Total && Instagrate_Pro_Helper::setting( 'igpsettings_general_admin-images', '' ) != '' ) {

			$older_images = instagrate_pro()->images->get_images( $_POST['post_id'], '', 'DESC', false, '', 20, $_POST['img_count'] );
			$images       = array();
			foreach ( $older_images as $image ) {
				$images[] = array(
					'id'         => $image->image_id,
					'images'     => array( 'thumbnail' => array( 'url' => $image->image_thumb_url ) ),
					'status'     => $image->status,
					'media_type' => $image->media_type,
				);

			}
			$response['images'] = $images;
		} else {
			$images               = instagrate_pro()->accounts->load_images( $_POST['post_id'] );
			$response['stats']    = instagrate_pro()->accounts->account_stats( $_POST['post_id'] );
			$account_settings     = get_post_meta( $_POST['post_id'], '_instagrate_pro_settings', true );
			$response['images']   = $images;
			$response['next_url'] = $account_settings['next_url'];
			$response['load']     = true;
		}
		$response['message'] = 'success';

		echo json_encode( $response );
		die;
	}
}
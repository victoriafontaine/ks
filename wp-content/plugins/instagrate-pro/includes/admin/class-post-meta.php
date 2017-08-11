<?php

class Instagrate_Pro_Post_Meta {

	public function __construct() {

		add_action( 'admin_init', array( $this, 'meta_boxes' ) );
		add_action( 'do_meta_boxes', array( $this, 'change_image_box' ) );
		add_action( 'do_meta_boxes', array( $this, 'change_custom_meta_box' ) );
		add_action( 'do_meta_boxes', array( $this, 'change_tag_box' ) );
		add_filter( 'admin_post_thumbnail_html', array( $this, 'custom_feat_image_text' ) );
		add_action( 'save_post', array( $this, 'save_post_meta' ) );

	}

	/**
	 * Register meta boxes
	 */
	public function meta_boxes() {
		if ( ! isset( $_GET['post'] ) ) {
			return;
		}

		if ( ! isset( $_GET['action'] ) || ( isset( $_GET['action'] ) && $_GET['action'] != 'edit' ) ) {
			return;
		}

 		if ( INSTAGRATEPRO_POST_TYPE != get_post_type( $_GET['post'] ) ) {
			return;
		}

		$account_id = $_GET['post'];

		// Instagram Settings
		add_meta_box(
			'igp_instagram_box',
			__( 'Instagram Settings', 'instagrate-pro' ),
			array(
				$this,
				'meta_box_instagram'
			),
			INSTAGRATEPRO_POST_TYPE,
			'side',
			'high'
		);

		// Media box
		add_meta_box(
			'igp_images_box',
			__( 'Instagram Media' . instagrate_pro()->accounts->get_images_key( $account_id ), 'instagrate-pro' ),
			array(
				$this,
				'meta_box_images'
			),
			INSTAGRATEPRO_POST_TYPE,
			'normal',
			'high'
		);

		// Template Tags
		if ( Instagrate_Pro_Helper::setting( 'igpsettings_general_hide-meta-template', '0' ) == '0' ) {
			add_meta_box(
				'igp_template_tags_box', __( 'Template Tags', 'instagrate-pro' ),
				array(
					$this,
					'meta_box_template_tags'
				),
				INSTAGRATEPRO_POST_TYPE,
				'normal',
				'high'
			);
		}

		// Posting Settings
		add_meta_box(
			'igp_posting_box', __( 'Posting Settings', 'instagrate-pro' ),
			array(
				$this,
				'meta_box_posting'
			),
			INSTAGRATEPRO_POST_TYPE,
			'side',
			'default'
		);

		// Post Settings
		add_meta_box(
			'igp_post_box', __( 'Post Settings', 'instagrate-pro' ),
			array(
				$this,
				'meta_box_post'
			),
			INSTAGRATEPRO_POST_TYPE,
			'side',
			'default'
		);

		// Map Settings
		if ( Instagrate_Pro_Helper::setting( 'igpsettings_general_hide-meta-map', '0' ) == '0' ) {
			add_meta_box(
				'igp_map_map', __( 'Map Settings', 'instagrate-pro' ),
				array(
					$this,
					'meta_box_map'
				),
				INSTAGRATEPRO_POST_TYPE,
				'side',
				'low'
			);
		}

		// Useful links
		if ( Instagrate_Pro_Helper::setting( 'igpsettings_general_hide-meta-links', '0' ) == '0' ) {
			add_meta_box(
				'igp_links', __( 'Useful Links', 'instagrate-pro' ),
				array(
					$this,
					'meta_box_links'
				),
				INSTAGRATEPRO_POST_TYPE,
				'normal',
				'low'
			);
		}

	}

	public function change_image_box() {
		remove_meta_box( 'postimagediv', INSTAGRATEPRO_POST_TYPE, 'side' );
		if ( Instagrate_Pro_Helper::setting( 'igpsettings_general_hide-meta-featured', '0' ) == '0' ) {
			add_meta_box( 'postimagediv', __( 'Custom Featured Image', 'instagrate-pro' ), 'post_thumbnail_meta_box', 'instagrate_pro', 'side', 'low' );
		}
	}

	public function change_custom_meta_box() {
		remove_meta_box( 'postcustom', INSTAGRATEPRO_POST_TYPE, 'normal' );
		if ( Instagrate_Pro_Helper::setting( 'igpsettings_general_hide-meta-custom', '0' ) == '0' ) {
			add_meta_box( 'postcustom', __( 'Custom Fields For Template Tags', 'instagrate-pro' ), 'post_custom_meta_box', 'instagrate_pro', 'normal' );
		}
	}

	public function change_tag_box() {
		remove_meta_box( 'tagsdiv-post_tag', INSTAGRATEPRO_POST_TYPE, 'side' );
		if ( Instagrate_Pro_Helper::setting( 'igpsettings_general_hide-meta-tags', '0' ) == '0' ) {
			add_meta_box( 'tagsdiv-post_tag', __( 'Default Tags', 'instagrate-pro' ), 'post_tags_meta_box', 'instagrate_pro', 'side' );
		}
	}

	public function custom_feat_image_text( $content, $post_id = 0 ) {
		$screen      = get_current_screen();
		$new_content = $content;
		if ( isset( $screen ) && INSTAGRATEPRO_POST_TYPE == $screen->post_type ) {
			$new_content = '<p>' . __( 'You can make an Instagram image the featured image by using the setting in the Post Settings box.', 'instagrate-pro' ) . '</p>';
			$new_content .= '<p>' . __( 'Setting a featured image here will override that setting and always set the featured image as this image.', 'instagrate-pro' ) . '</p>';
			$new_content .= str_replace( __( 'featured image' ), __( 'custom featured image' ), $content );
		}

		return $new_content;
	}

	public function meta_box_instagram() {
		global $post;
		?>
		<iframe id="logoutframe" src="https://instagram.com/accounts/logout/" width="0" height="0"></iframe>
		<?php
		if ( $post->post_status == 'auto-draft' ) {
			$html = '<input value="' . __( 'Login to Instagram', 'instagrate-pro' ) . '" class="button-primary" type="button" disabled="disabled">';
			echo $html;
		} else {
			$options = get_post_meta( $post->ID, '_instagrate_pro_settings', true );
			//check post meta for auth token and username, and valid auth token
			if ( ! isset( $options['token'] ) || $options['token'] == '' ) {
				$auth_url = instagrate_pro()->instagram->instagram_authorise_url();
				$html     = '<p><a class="button-primary" href="' . $auth_url . '">' . __( 'Login to Instagram', 'instagrate-pro' ) . '</a></p>';
				echo $html;
			} else {
				wp_nonce_field( plugin_basename( __FILE__ ), 'instagrate_pro_noncename' );
				?>
				<div class="igp-profile">
					<div class="igp-avatar">
						<?php
						$profile_src = ( ! isset( $options['user_thumb'] ) || $options['user_thumb'] == '' ) ? plugins_url( 'assets/img/not-connected.png', __FILE__ ) : $options['user_thumb'];
						?>
						<img id="user-thumb" src="<?php echo $profile_src; ?>" width="50" height="50" alt="Instagram profile image for <?php echo $options['username']; ?>">
					</div>
					<div class="igp-details">
						<?php
						$reconnect_url = instagrate_pro()->instagram->instagram_authorise_url( true ); ?>
						<p><b><?php echo $options['username']; ?></b><br/>
							<a href="#" id="igp-logout" title="<?php _e( 'Disconnect', 'instagrate-pro' ); ?>"><?php _e( 'Disconnect', 'instagrate-pro' ); ?></a><br/>
							<a href="<?php echo $reconnect_url; ?>" id="igp-reconnect" title="<?php _e( 'Reconnect', 'instagrate-pro' ); ?>"><?php _e( 'Reconnect', 'instagrate-pro' ); ?></a><br/>
						</p>
						<div class="spinner"></div>
					</div>
				</div>
				<table class="form-table igp-admin">
					<tr valign="top">
						<th scope="row"><?php _e( 'Media Stream', 'instagrate-pro' ); ?></th>
						<td>
							<?php
							$selected = Instagrate_Pro_Helper::setting( 'instagram_images', 'recent', $options );

							$streams = array(
								'recent'   => __( 'My Recent Media', 'instagrate-pro' ),
								'users'    => __( 'Users Media', 'instagrate-pro' ),
								'tagged'   => __( 'All Hashtagged Media', 'instagrate-pro' ),
								'location' => __( 'Location Media', 'instagrate-pro' ),
							);
							if ( 'feed' === $selected ) {
								$streams['feed'] = __( 'My Feed (Deprecated)', 'instagrate-pro' );
							}
							?>
							<select name="_instagrate_pro_settings[instagram_images]">
								<?php
								foreach ( $streams as $key => $value ) : ?>
									<option value="<?php echo $key; ?>" <?php selected( $selected, $key ); ?>><?php echo $value; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<?php
						if ( 'feed' === $selected ) {
							printf( '<div class="notice-info notice"><p><strong>API Update</strong> The Feed API endpoint has been deprecated by Instagram, please use another stream.</p></div>');
						}

						// Reauthorize public_content accounts
						if ( isset( $options['needs_reconnect'] ) ) {
							$stream     = $streams[ $selected ];
							$notice_msg = sprintf( 'Since June 1st 2016 existing accounts using the <i>%s</i> stream will need to <a href="%s">re-authorize</a> with the API client to ensure the plugin continues to work.', $stream, $reconnect_url );
							printf( '<div class="notice-warning notice"><p><strong>Re-Authorisation Required</strong> %s</p></div>', $notice_msg );
						}
						?>
					</tr>
					<tr valign="top" class="instagram_user">
						<th scope="row"><?php _e( 'Users Name', 'instagrate-pro' ); ?></th>
						<td>
							<input class="large-text" type="text" id="igp-users-name-input" name="_instagrate_pro_settings[instagram_user]" value="<?php echo Instagrate_Pro_Helper::setting( 'instagram_user', '', $options ); ?>" /><br />
							<input type="hidden" value="<?php echo Instagrate_Pro_Helper::setting( 'instagram_users_id', '', $options ); ?>" name="_instagrate_pro_settings[instagram_users_id]" />
						</td>
					</tr>
					<tr valign="top" class="instagram_location">
						<th scope="row"><?php _e( 'Location Name', 'instagrate-pro' ); ?></th>
						<td>
							<input type="text" id="igp-location-name-input" class="large-text" name="_instagrate_pro_settings[instagram_location]" value="<?php echo Instagrate_Pro_Helper::setting( 'instagram_location', '', $options ); ?>" /><br />
							<input type="hidden" value="<?php echo Instagrate_Pro_Helper::setting( 'location_lat', '', $options ); ?>" name="_instagrate_pro_settings[location_lat]" />
							<input type="hidden" value="<?php echo Instagrate_Pro_Helper::setting( 'location_lng', '', $options ); ?>" name="_instagrate_pro_settings[location_lng]" />
						</td>
					</tr>
					<tr valign="top" class="instagram_location">
						<th scope="row"><?php _e( 'Instagram Location', 'instagrate-pro' ); ?></th>
						<td>
							<select name="_instagrate_pro_settings[instagram_location_id]">
								<?php
								$locations = instagrate_pro()->instagram->get_locations( $post->ID, Instagrate_Pro_Helper::setting( 'instagram_location', '', $options ), Instagrate_Pro_Helper::setting( 'location_lat', '', $options ), Instagrate_Pro_Helper::setting( 'location_lng', '', $options ) );
								if ( $locations && is_array( $locations ) ) {
									$selected = Instagrate_Pro_Helper::setting( 'instagram_location_id', '', $options );
									foreach ( $locations as $key => $value ) : ?>
										<option value="<?php echo $key; ?>" <?php selected( $selected, $key ); ?>><?php echo $value; ?></option>
									<?php endforeach;
								}
								?>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Hashtags Filter', 'instagrate-pro' ); ?></th>
						<td>
							<input type="text" class="large-text" name="_instagrate_pro_settings[instagram_hashtags]" value="<?php echo Instagrate_Pro_Helper::setting( 'instagram_hashtags', '', $options ); ?>" /><br />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Media Filter', 'instagrate-pro' ); ?></th>
						<td>
							<?php
							$media_types = array(
								'all'   => __( 'Images and Videos', 'instagrate-pro' ),
								'image' => __( 'Only Images', 'instagrate-pro' ),
								'video' => __( 'Only Videos', 'instagrate-pro' ),
							);
							$selected    = Instagrate_Pro_Helper::setting( 'instagram_media_filter', 'all', $options ); ?>
							<select name="_instagrate_pro_settings[instagram_media_filter]">
								<?php
								foreach ( $media_types as $key => $value ) : ?>
									<option value="<?php echo $key; ?>" <?php selected( $selected, $key ); ?>><?php echo $value; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

				</table>
				<div style="display: none">
					<input type="hidden" name="_instagrate_pro_settings[token]" value="<?php echo Instagrate_Pro_Helper::setting( 'token', '', $options ); ?>" /><br />
					<input type="hidden" name="_instagrate_pro_settings[user_id]" value="<?php echo Instagrate_Pro_Helper::setting( 'user_id', '', $options ); ?>" /><br />
					<input type="hidden" name="_instagrate_pro_settings[username]" value="<?php echo Instagrate_Pro_Helper::setting( 'username', '', $options ); ?>" /><br />
					<input type="hidden" name="_instagrate_pro_settings[bio]" value="<?php echo Instagrate_Pro_Helper::setting( 'bio', '', $options ); ?>" /><br />
					<input type="hidden" name="_instagrate_pro_settings[user_thumb]" value="<?php echo Instagrate_Pro_Helper::setting( 'user_thumb', '', $options ); ?>" /><br />
					<input type="hidden" name="_instagrate_pro_settings[last_id]" value="<?php echo Instagrate_Pro_Helper::setting( 'last_id', '', $options ); ?>" /><br />
					<input type="hidden" name="_instagrate_pro_settings[next_url]" value="<?php echo Instagrate_Pro_Helper::setting( 'next_url', '', $options ); ?>" /><br />
					<input type="hidden" name="_instagrate_pro_settings[ig_error]" value="<?php echo Instagrate_Pro_Helper::setting( 'ig_error', '', $options ); ?>" /><br />
					<input type="hidden" name="_instagrate_pro_settings[locked]" value="" /><br />
				</div>
			<?php
			}
		}
	}

	function meta_box_images() {
		global $post;
		$html    = '';
		$options = get_post_meta( $post->ID, '_instagrate_pro_settings', true );
		if ( ! isset( $options['token'] ) || $options['token'] == '' ) {
			_e( 'You will need to connect an Instagram account from the Instagram settings', 'instagrate-pro' );
		} else {
			//get images from table
			$limit       = Instagrate_Pro_Helper::setting( 'igpsettings_general_admin-images', '' );
			$images      = instagrate_pro()->images->get_images( $post->ID, '', 'DESC', false, '', $limit );
			$show_zero   = '';
			$toggle_load = ' style="display: none;"';
			$show_bulk   = ' style="display: none;"';
			if ( $images ) {
				$html = '';
				if ( sizeof( $images ) > 0 ) {
					$show_bulk = '';
					$show_zero = ' style="display: none;"';
				}
				foreach ( $images as $key => $image ) {
					$title = esc_attr( substr( $image->caption_clean_no_tags, 0, 20 ) );
					$html .= '<li>';
					$html .= '<a class="edit-image ' . $image->media_type . '" href="#" rel="' . $image->image_id . '">';
					$html .= '<img class="' . $image->status . '" src="' . $image->image_url . '" width="70" alt="' . $title . '" title="' . __( 'Edit', 'instagrate-pro' ) . ' ' . $title . '">';
					$html .= '<span class="video-ind"></span>';
					$html .= '</a>';
					$html .= '<input id="' . $image->image_id . '" class="igp-bulk" type="checkbox"' . $show_bulk . '>';
					$html .= '</li>';
				}
				if ( isset( $options['next_url'] ) && $options['next_url'] != '' ) {
					$toggle_load = '';
				}
			}

			if ( false === instagrate_pro()->accounts->can_get_images( $options ) ) {
				// Hide load more if can't retrieve images
				$toggle_load = ' style="display: none;"';
			}
			?>
			<div class="igp-zero-images" <?php echo $show_zero; ?>>
				<?php echo __( 'No images found', 'instagrate-pro' ); ?>
			</div>
			<div id="igp-image-manager">
				<input id="igp-load-images" type="button" class="button" value="<?php _e( 'Load More', 'instagrate-pro' ); ?>" <?php echo $toggle_load; ?>>

				<div id="igp-bulk-wrap" class="igp-bulk" <?php echo $show_bulk; ?>>
					<label>
						<input type="checkbox" id="toggle_bulk" />
						<span id="toggle_bulk_text"><?php _e( 'All Media', 'instagrate-pro' ); ?></span>
					</label>
					<input class="set-status button" data-status="pending" type="button" value="Pending" title="Set images to pending">
					<input class="set-status button" data-status="ignore" type="button" value="Ignore" title="Set images to ignore">
				</div>
			</div>
			<ul id="igp-images" class="">
				<?php echo $html; ?>
			</ul>
			<div id="igp-edit-image">
				<p><strong><?php _e( 'Edit Details', 'instagrate-pro' ); ?></strong></p>

				<!--container for everything-->
				<div id="jp_container_1" class="jp-video jp-video-460p">

					<!--container in which our video will be played-->
					<div id="igp-jplayer" class="jp-jplayer"></div>

					<!--main containers for our controls-->
					<div class="jp-gui">

						<div class="jp-video-play" style="display: block;">
							<a class="jp-video-play-icon jp-play" tabindex="1" href="javascript:;">play</a>
						</div>

						<div class="jp-interface">
							<div class="jp-controls-holder">

								<!--play and pause buttons-->
								<a href="javascript:;" class="jp-play" tabindex="1">play</a>
								<a href="javascript:;" class="jp-pause" tabindex="1">pause</a>
								<span class="separator sep-1"></span>

								<!--progress bar-->
								<div class="jp-progress">
									<div class="jp-seek-bar">
										<div class="jp-play-bar"><span></span></div>
									</div>
								</div>

								<!--time notifications-->
								<div class="jp-current-time"></div>
								<span class="time-sep">/</span>

								<div class="jp-duration"></div>
								<span class="separator sep-2"></span>

								<!--mute / unmute toggle-->
								<a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a>
								<a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a>

								<!--volume bar-->
								<div class="jp-volume-bar">
									<div class="jp-volume-bar-value"><span class="handle"></span></div>
								</div>
								<span class="separator sep-2"></span>

								<!--full screen toggle-->
								<a href="javascript:;" class="jp-full-screen" tabindex="1" title="full screen">full screen</a>
								<a href="javascript:;" class="jp-restore-screen" tabindex="1" title="restore screen">restore screen</a>
								<a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a>
								<a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a>

							</div>
							<!--end jp-controls-holder-->
						</div>
						<!--end jp-interface-->
					</div>
					<!--end jp-gui-->

					<!--unsupported message-->
					<div class="jp-no-solution">
						<span>Update Required</span>
						To play the media you will need to either update your browser to a recent version or update your
						<a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
					</div>

				</div>
				<!--end jp_container_1-->

				<img id="igp_meta_image" class="" width="455" src="<?php echo INSTAGRATEPRO_PLUGIN_URL . 'assets/img/large_spinner.gif'; ?>" alt="<?php _e( 'Image to edit', 'instagrate-pro' ); ?>">
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( 'Caption (without Tags)', 'instagrate-pro' ); ?></th>
						<td>
							<input type="text" name="igp_meta_caption" id="igp_meta_caption" value="" class="regular-text" />
							<input type="hidden" name="igp_meta_caption_old" id="igp_meta_caption_old" value="" class="regular-text" />
							<input type="hidden" name="igp_meta_caption_clean_old" id="igp_meta_caption_clean_old" value="" class="regular-text" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Status', 'instagrate-pro' ); ?></th>
						<td>
							<label id="igp_meta_status_old" class="igp-status-old"></label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Change Status', 'instagrate-pro' ); ?></th>
						<td>
							<select name="igp_meta_status" id="igp_meta_status">
								<option value="0"><?php echo '— ' . __( 'Select', 'instagrate-pro' ) . ' —'; ?></option>
								<option value="pending"><?php _e( 'Pending', 'instagrate-pro' ); ?></option>
								<option value="ignore"><?php _e( 'Ignore', 'instagrate-pro' ); ?></option>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Hashtags', 'instagrate-pro' ); ?></th>
						<td>
							<div id="igp_meta_hashtags"></div>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="button" name="igp_meta_submit" id="igp_meta_submit" class="button-primary" value="<?php _e( 'Save Changes', 'instagrate-pro' ); ?>">
				</p>
			</div>
		<?php
		}

	}

	function meta_box_posting() {
		global $post;
		$options = get_post_meta( $post->ID, '_instagrate_pro_settings', true );
		wp_nonce_field( plugin_basename( __FILE__ ), 'instagrate_pro_noncename' );
		?>
		<table class="form-table igp-admin">
			<tr valign="top">
				<th scope="row"><?php _e( 'Frequency', 'instagrate-pro' ); ?></th>
				<td>
					<?php
					$frequencies = array(
						'constant' => __( 'Constant', 'instagrate-pro' ),
						'schedule' => __( 'Scheduled', 'instagrate-pro' ),
						'cron'     => __( 'Cron Job', 'instagrate-pro' ),
						'manual'   => __( 'Manual', 'instagrate-pro' ),
					);
					$selected    = Instagrate_Pro_Helper::setting( 'posting_frequency', 'constant', $options ); ?>
					<select name="_instagrate_pro_settings[posting_frequency]">
						<?php
						foreach ( $frequencies as $key => $value ) : ?>
							<option value="<?php echo $key; ?>" <?php selected( $selected, $key ); ?>><?php echo $value; ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr valign="top" class="schedule">
				<th scope="row"><?php _e( 'Schedule', 'instagrate-pro' ); ?></th>
				<td>
					<select name="_instagrate_pro_settings[posting_schedule]">
						<?php
						$schedules = instagrate_pro()->scheduler->get_all_schedules();
						$selected  = Instagrate_Pro_Helper::setting( 'posting_schedule', 'igp_daily', $options );
						foreach ( $schedules as $key => $value ) : ?>
							<option value="<?php echo $key; ?>" <?php selected( $selected, $key ); ?>><?php echo $value; ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<?php
			$next_time = instagrate_pro()->scheduler->get_next_schedule( $post->ID, Instagrate_Pro_Helper::setting( 'posting_schedule', 'igp_daily', $options ) );
			if ( $next_time !== '' ) {
				?>
				<tr valign="top" class="schedule">
					<th scope="row">
						<div class="curtime"><span id="timestamp"><?php _e( 'Next', 'instagrate-pro' ); ?>: </span>
						</div>
					</th>
					<td>
						<?php echo '<b>' . $next_time . '</b>'; ?>
					</td>
				</tr>
			<?php } ?>
			<tr valign="top" class="schedule">
				<th scope="row"><?php _e( 'Day', 'instagrate-pro' ); ?></th>
				<td>
					<select name="_instagrate_pro_settings[posting_day]" <?php echo ( instagrate_pro()->scheduler->schedule_no_day( Instagrate_Pro_Helper::setting( 'posting_schedule', 'igp_daily', $options ) ) ) ? 'disabled="disabled"' : ''; ?>>
						<?php
						$days     = array(
							'Mon' => __( 'Monday', 'instagrate-pro' ),
							'Tue' => __( 'Tuesday', 'instagrate-pro' ),
							'Wed' => __( 'Wednesday', 'instagrate-pro' ),
							'Thu' => __( 'Thursday', 'instagrate-pro' ),
							'Fri' => __( 'Friday', 'instagrate-pro' ),
							'Sat' => __( 'Saturday', 'instagrate-pro' ),
							'Sun' => __( 'Sunday', 'instagrate-pro' )
						);
						$selected = Instagrate_Pro_Helper::setting( 'posting_day', '', $options );
						foreach ( $days as $key => $value ) : ?>
							<option value="<?php echo $key; ?>" <?php selected( $selected, $key ); ?>><?php echo $value; ?></option>
						<?php endforeach; ?>
						<?php if ( instagrate_pro()->scheduler->schedule_no_day( Instagrate_Pro_Helper::setting( 'posting_schedule', 'igp_daily', $options ) ) ) { ?>
							<option value="" selected="selected"><?php _e( 'Daily', 'instagrate-pro' ); ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
			<tr valign="top" class="schedule">
				<th scope="row"><?php _e( 'Time', 'instagrate-pro' ); ?></th>
				<td>
					<select name="_instagrate_pro_settings[posting_time]">
						<?php
						$hours    = array(
							'00:00',
							'01:00',
							'02:00',
							'03:00',
							'04:00',
							'05:00',
							'06:00',
							'07:00',
							'08:00',
							'09:00',
							'10:00',
							'11:00',
							'12:00',
							'13:00',
							'14:00',
							'15:00',
							'16:00',
							'17:00',
							'18:00',
							'19:00',
							'20:00',
							'21:00',
							'22:00',
							'23:00',
						);
						$selected = Instagrate_Pro_Helper::setting( 'posting_time', '', $options );
						foreach ( $hours as $key ) : ?>
							<option value="<?php echo $key; ?>" <?php selected( $selected, $key ); ?>><?php echo $key; ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr valign="top" class="manual">
				<th scope="row"></th>
				<td>
					<?php
					if ( $post->post_status != 'publish' ) {
						_e( 'You can only manually post images for a published account.	', 'instagrate-pro' );
					} else {
						?>
						<div id="manual-posting">
							<img class="ig_ajax-loading" src="<?php echo get_admin_url() . 'images/wpspin_light.gif'; ?>">
							<input id="igp-manual-post" class="button-primary" type="button" value="Manual Post">
						</div>
					<?php } ?>
				</td>
			</tr>
			<tr valign="top">
				<td colspan="2"><input type="hidden" name="_instagrate_pro_settings[moderate_images]" value="off" />
					<label>
						<input type="checkbox" name="_instagrate_pro_settings[moderate_images]" value="on"<?php if ( Instagrate_Pro_Helper::setting( 'moderate_images', 'off', $options ) == 'on' ) {
							echo ' checked="checked"';
						} ?> />&nbsp;<strong><?php _e( 'Moderate Images', 'instagrate-pro' ); ?></strong>
					</label></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Post Type', 'instagrate-pro' ); ?></th>
				<td>
					<?php
					$args       = array(
						'public'  => true,
						'show_ui' => true
					);
					$posttypes  = get_post_types( $args, 'objects' );
					$post_types = array();
					foreach ( $posttypes as $pt ) {
						$post_types[ esc_attr( $pt->name ) ] = $pt->labels->singular_name;
					}
					Instagrate_Pro_Helper::metabox_select( 'post_type', $post_types, 'post', $options );
					?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Multiple Media', 'instagrate-pro' ); ?></th>
				<td>
					<?php
					$multiple = array(
						'each'   => ucfirst( Instagrate_Pro_Helper::setting( 'post_type', 'post', $options ) ) . ' ' . __( 'Per Media', 'instagrate-pro' ),
						'group'  => __( 'Media Grouped', 'instagrate-pro' ),
						'single' => __( 'Same', 'instagrate-pro' ) . ' ' . ucfirst( Instagrate_Pro_Helper::setting( 'post_type', 'post', $options ) ),
					);
					Instagrate_Pro_Helper::metabox_select( 'posting_multiple', $multiple, 'each', $options );
					?>
				</td>
			</tr>
			<tr valign="top" class="single_post">
				<th scope="row" id="select_post_label"><?php _e( 'Select ' . ucfirst( Instagrate_Pro_Helper::setting( 'post_type', 'post', $options ) ), 'instagrate-pro' ); ?></th>
				<td>
					<?php
					$post_objects = instagrate_pro()->helper->get_post_objects( Instagrate_Pro_Helper::setting( 'post_type', 'post', $options ) );
					Instagrate_Pro_Helper::metabox_select( 'posting_same_post', $post_objects, '', $options );
					?>
				</td>
			</tr>
			<tr valign="top" class="single_post">
				<th scope="row"><?php _e( 'Media Location', 'instagrate-pro' ); ?></th>
				<td>
					<?php
					$locations = array(
						'top'      => __( 'Top', 'instagrate-pro' ),
						'bottom'   => __( 'Bottom', 'instagrate-pro' ),
						'specific' => __( 'Specific', 'instagrate-pro' )
					);
					Instagrate_Pro_Helper::metabox_select( 'posting_single_location', $locations, 'top', $options );
					?>
				</td>
			</tr>
			<tr valign="top" class="single_post grouped">
				<th scope="row"><?php _e( 'Media Ordering', 'instagrate-pro' ); ?></th>
				<td>
					<?php
					$orders = array(
						'ASC'  => __( 'Oldest at Top', 'instagrate-pro' ),
						'DESC' => __( 'Newest at Top', 'instagrate-pro' )
					);
					Instagrate_Pro_Helper::metabox_select( 'posting_image_order', $orders, 'ASC', $options );
					?>
				</td>
			</tr>
			<tr valign="top" class="grouped">
				<th scope="row"><?php _e( 'Multi Map', 'instagrate-pro' ); ?></th>
				<td>
					<?php
					$position = array(
						'none'   => __( 'No multi map', 'instagrate-pro' ),
						'bottom' => __( 'Map at bottom', 'instagrate-pro' ),
						'top'    => __( 'Map at top', 'instagrate-pro' )
					);
					Instagrate_Pro_Helper::metabox_select( 'grouped_multi_map', $position, 'none', $options );
					?>
				</td>
			</tr>
		</table>
	<?php
	}

	function meta_box_post() {
		global $post;
		$options = get_post_meta( $post->ID, '_instagrate_pro_settings', true );
		wp_nonce_field( plugin_basename( __FILE__ ), 'instagrate_pro_noncename' );
		?>
		<table class="form-table igp-admin">
			<tr valign="top">
				<th scope="row"><?php _e( 'Save Instagram Media to Media Library', 'instagrate-pro' ); ?></th>
				<td><input type="hidden" name="_instagrate_pro_settings[post_save_media]" value="off" />
					<label><input type="checkbox" name="_instagrate_pro_settings[post_save_media]" value="on"<?php if ( Instagrate_Pro_Helper::setting( 'post_save_media', 'off', $options ) == 'on' ) {
							echo ' checked="checked"';
						} ?> />
					</label></td>
			</tr>
			<tr valign="top" class="image-saving">
				<th scope="row"><?php _e( 'Featured Image', 'instagrate-pro' ); ?></th>
				<td><input type="hidden" name="_instagrate_pro_settings[post_featured_image]" value="off" />
					<label><input type="checkbox" name="_instagrate_pro_settings[post_featured_image]" value="on"<?php if ( Instagrate_Pro_Helper::setting( 'post_featured_image', 'off', $options ) == 'on' ) {
							echo ' checked="checked"';
						} ?> />
					</label></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Taxonomy', 'instagrate-pro' ); ?></th>
				<td>
					<?php
					$post_type  = Instagrate_Pro_Helper::setting( 'post_type', 'post', $options );
					$taxonomies = instagrate_pro()->helper->get_all_taxonomies( $post_type );
					Instagrate_Pro_Helper::metabox_select( 'post_taxonomy', $taxonomies, 'category', $options );
					?>
				</td>
			</tr>
			<tr valign="top">
				<?php
				$taxonomy       = Instagrate_Pro_Helper::setting( 'post_taxonomy', '', $options );
				$taxonomy_label = '';
				if ( $taxonomy != '' && $taxonomy != '0' ) {
					$taxonomy_obj   = get_taxonomy( $taxonomy );
					$taxonomy_label = $taxonomy_obj->labels->name;
				}
				?>
				<th scope="row" id="tax_plural_label"><?php echo $taxonomy_label; ?></th>
				<td>
					<div id="post_terms">
						<?php
						if ( $taxonomy != '' && $taxonomy != '0' ) {
							$terms = instagrate_pro()->helper->get_all_terms( $taxonomy, false );
							foreach ( $terms as $key => $term ) {
								$selected = ( in_array( $key, (array) Instagrate_Pro_Helper::setting( 'post_term', '', $options ) ) ) ? ' checked' : '';
								echo '<input type="checkbox" name="_instagrate_pro_settings[post_term][]" value="' . $key . '" ' . $selected . '/> ' . $term . '<br />';
							}
						}
						?>
					</div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Convert Media Hashtags to', 'instagrate-pro' ); ?></th>
				<td>
					<?php
					$terms = instagrate_pro()->helper->get_all_tag_taxonomies( $post_type );
					Instagrate_Pro_Helper::metabox_select( 'post_tag_taxonomy', $terms, '', $options );
					?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Author', 'instagrate-pro' ); ?></th>
				<td>
					<?php
					$args = array(
						'selected'         => Instagrate_Pro_Helper::setting( 'post_author', '', $options ),
						'include_selected' => true,
						'name'             => '_instagrate_pro_settings[post_author]',
					);
					wp_dropdown_users( $args );
					?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Status', 'instagrate-pro' ); ?></th>
				<td>
					<?php
					$status = array(
						'publish' => __( 'Publish', 'instagrate-pro' ),
						'draft'   => __( 'Draft', 'instagrate-pro' ),
						'pending' => __( 'Pending Review', 'instagrate-pro' ),
						'private' => __( 'Private', 'instagrate-pro' ),
					);
					Instagrate_Pro_Helper::metabox_select( 'post_status', $status, 'publish', $options );
					?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Format', 'instagrate-pro' ); ?></th>
				<td>
					<select name="_instagrate_pro_settings[post_format]">
						<option value="Standard"<?php if ( Instagrate_Pro_Helper::setting( 'post_format', 'Standard', $options ) == 'Standard' ) {
							echo ' selected="selected"';
						} ?>><?php _e( 'Standard', 'instagrate-pro' ); ?></option>
						<?php
						if ( current_theme_supports( 'post-formats' ) ) {
							$post_formats = get_theme_support( 'post-formats' );
							foreach ( $post_formats[0] as $option ) {
								$selected = ( Instagrate_Pro_Helper::setting( 'post_format', '', $options ) == $option ) ? ' selected="selected"' : '';
								echo '<option value="' . $option . '"' . $selected . '>' . ucfirst( $option ) . '</option>';
							}
						} ?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Use Instagram Date', 'instagrate-pro' ); ?></th>
				<td><input type="hidden" name="_instagrate_pro_settings[post_date]" value="off" />
					<label><input type="checkbox" name="_instagrate_pro_settings[post_date]" value="on"<?php if ( Instagrate_Pro_Helper::setting( 'post_date', 'on', $options ) == 'on' ) {
							echo ' checked="checked"';
						} ?> />
					</label></td>
			</tr>
		</table>
	<?php
	}

	function meta_box_map() {
		global $post;
		$options = get_post_meta( $post->ID, '_instagrate_pro_settings', true );
		wp_nonce_field( plugin_basename( __FILE__ ), 'instagrate_pro_noncename' );
		?>
		<table class="form-table igp-admin">
			<tr valign="top">
				<th scope="row"><?php _e( 'Style', 'instagrate-pro' ); ?></th>
				<td>
					<?php
					$map_styles = array(
						'ROADMAP'   => __( 'Road', 'instagrate-pro' ),
						'HYBRID'    => __( 'Hybrid', 'instagrate-pro' ),
						'SATELLITE' => __( 'Satellite', 'instagrate-pro' ),
						'TERRAIN'   => __( 'Terrain', 'instagrate-pro' ),
					);
					Instagrate_Pro_Helper::metabox_select( 'map_style', $map_styles, 'ROADMAP', $options );
					?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'CSS Class', 'instagrate-pro' ); ?></th>
				<td>
					<input type="text" class="large-text" name="_instagrate_pro_settings[map_css]" value="<?php echo Instagrate_Pro_Helper::setting( 'map_css', '', $options ); ?>" /><br />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Width', 'instagrate-pro' ); ?></th>
				<td>
					<input type="text" class="small-text" name="_instagrate_pro_settings[map_width]" value="<?php echo Instagrate_Pro_Helper::setting( 'map_width', '400', $options ); ?>" />
					<?php
					$units = array(
						'pixel'   => __( 'px', 'instagrate-pro' ),
						'percent' => __( '%', 'instagrate-pro' )
					);
					Instagrate_Pro_Helper::metabox_select( 'map_width_type', $units, 'pixel', $options, 'igp-select-small' );
					?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Height', 'instagrate-pro' ); ?></th>
				<td>
					<input type="text" class="small-text" name="_instagrate_pro_settings[map_height]" value="<?php echo Instagrate_Pro_Helper::setting( 'map_height', '300', $options ); ?>" />
					<?php
					Instagrate_Pro_Helper::metabox_select( 'map_height_type', $units, 'pixel', $options, 'igp-select-small' );
					?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Zoom Level', 'instagrate-pro' ); ?></th>
				<td>
					<input type="text" class="small-text" name="_instagrate_pro_settings[map_zoom]" value="<?php echo Instagrate_Pro_Helper::setting( 'map_zoom', '15', $options ); ?>" />
				</td>
			</tr>
		</table>
	<?php
	}

	function meta_box_template_tags() {
		$template_tags = instagrate_pro()->tags->get_template_tags();
		$html          = '<p>' . __( 'You can use the following template tags in the title, content, excerpt and custom field values:', 'instagrate-pro' ) . '</p>';
		foreach ( $template_tags as $tag ) {
			$no_title = ' ';
			if ( ! empty( $tag['exclude_from'] ) ) {
				$no_title .= __( 'Not available for ', 'instagrate-pro' );
				foreach ( $tag['exclude_from'] as $key => $exclude ) {
					$no_title .= ucwords( $exclude ) . ', ';
				}
				$no_title = substr( $no_title, 0, - 2 ) . '.';
			}
			$html .= '<b>%%' . $tag['name'] . '%%</b> - ' . $tag['desc'] . $no_title . '<br/>';
		}
		$html .= '<p>' . __( 'You can also run these through filters for each. The filter name is in the format igp_template_[tag] where tag has underscores not hypens, <br>e.g. %%location-name%% can be filtered with \'igp_template_location_name\')', 'instagrate-pro' ) . '</p>';
		echo $html;
	}

	function meta_box_links() {
		$html = Instagrate_Pro_Helper::get_cron_job_html();
		$html .= '<ul>';
		$html .= '<li><a target="_blank" href="https://intagrate.io/docs">' . __( 'Documentation', 'instagrate-pro' ) . '</a> - ' . __( 'get help on what settings mean and how to use them', 'instagrate-pro' ) . '</li>';
		$html .= '<li><a target="_blank" href="https://intagrate.io/docs/template-tags/">' . __( 'Template Tags', 'instagrate-pro' ) . '</a> - ' . __( 'get some simple examples of using template tags for the custom content', 'instagrate-pro' ) . '</li>';
		$html .= '<li><a target="_blank" href="https://intagrate.io/support/">' . __( 'Support', 'instagrate-pro' ) . '</a> - ' . __( 'get support for the plugin', 'instagrate-pro' ) . '</li>';
		$html .= '<li><a target="_blank" href="https://intagrate.io/category/release/">' . __( 'Changelog', 'instagrate-pro' ) . '</a> - ' . __( 'read the plugin\'s changelog', 'instagrate-pro' ) . '</li>';
		$html .= '</ul>';
		echo $html;
	}

	function save_post_meta( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['instagrate_pro_noncename'] ) || ! wp_verify_nonce( $_POST['instagrate_pro_noncename'], plugin_basename( __FILE__ ) ) ) {
			return;
		}
		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		$settings = $_POST['_instagrate_pro_settings'];
		// Schedule
		if ( $settings['posting_frequency'] == 'schedule' ) {
			$old_settings = get_post_meta( $post_id, '_instagrate_pro_settings', true );
			$old_sch      = isset( $old_settings['posting_schedule'] ) ? $old_settings['posting_schedule'] : '';
			$old_day      = isset( $old_settings['posting_day'] ) ? $old_settings['posting_day'] : '';
			$old_time     = isset( $old_settings['posting_time'] ) ? $old_settings['posting_time'] : '';
			$new_sch      = $settings['posting_schedule'];
			$new_day      = isset( $settings['posting_day'] ) ? $settings['posting_day'] : '';
			$new_time     = $settings['posting_time'];
			if ( $old_sch != $new_sch || $old_day != $new_day || $old_time != $new_time || $settings['posting_frequency'] != $old_settings['posting_frequency'] ) {
				instagrate_pro()->scheduler->clear_all_schedules( $post_id );
				$posting_day = isset( $settings['posting_day'] ) ? $settings['posting_day'] : '';
				instagrate_pro()->scheduler->set_schedule( $post_id, $new_day, $new_time, $new_sch );
			}
		} else {
			instagrate_pro()->scheduler->clear_all_schedules( $post_id );
		}

		update_post_meta( $post_id, '_instagrate_pro_settings', $settings );
	}
}

new Instagrate_Pro_Post_Meta();
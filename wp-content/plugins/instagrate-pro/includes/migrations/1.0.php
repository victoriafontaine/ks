<?php
/**
 * Large upgrade to v1.0
 */

global $wpdb;

// Install
instagrate_pro()->installer->install( true );

// Get old general settings
$old_options = get_option( 'pvw_igp_options' );
$new_options = get_option( 'igpsettings_settings' );

// Set the Default Image title
$new_options['igpsettings_general_default-title'] = $old_options['default_title'];
// Set Is Home override
$new_options['igpsettings_general_bypass-home'] = ( isset( $old_options['is_home'] ) && $old_options['is_home'] == 'true' ) ? '1' : '0';
// Set Duplicate image posting
$new_options['igpsettings_general_allow-duplicates'] = ( isset( $old_options['dup_image'] ) && $old_options['dup_image'] == 'true' ) ? '1' : '0';
// Set Credit Link
$new_options['igpsettings_general_credit-link'] = ( $old_options['credit_link'] == 'true' ) ? '1' : '0';
// Set Debug Mode
$new_options['igpsettings_support_debug-mode'] = ( $old_options['debug_mode'] == 'true' ) ? '1' : '0';

// Update new options
update_option( 'igpsettings_settings', $new_options );

$old_accounts = get_option( 'pvw_igp_accounts' );

$new_account_meta = array();

if ( isset( $old_accounts ) && $old_accounts ) {
	foreach ( $old_accounts as $key => $old_account ) {
		$tag = 'igp_' . $old_account['id'] . '_' . $old_account['userid'];

		// Instagram Settings
		$new_account_meta['token']    = $old_account['access'];
		$new_account_meta['user_id']  = $old_account['userid'];
		$new_account_meta['username'] = $old_account['username'];
		$user                         = instagrate_pro()->instagram->get_user( $old_account['access'], $old_account['userid'] );
		if ( $user != '' ) {
			$new_account_meta['user_thumb'] = $user->profile_picture;
		}

		$new_account_meta['last_id']  = '';
		$new_account_meta['next_url'] = '';
		$stream                       = $old_options[ $tag . '_instagram_options' ];
		if ( $stream == 'all' ) {
			$stream = 'recent';
		}
		if ( $stream == 'hashtag' ) {
			$stream = 'tagged';
		}
		$new_account_meta['instagram_images']   = $stream;
		$new_account_meta['instagram_hashtags'] = $old_options[ $tag . '_hashtag' ];

		// Posting Settings
		$frequency = $old_options[ $tag . '_post_config' ];
		if ( $frequency == 'real' ) {
			$frequency = 'constant';
		}
		$new_account_meta['posting_frequency'] = $frequency;
		$schedule                              = $old_options[ $tag . '_schedule' ];
		$new_account_meta['posting_schedule']  = 'igp_' . $schedule;
		if ( $frequency == 'schedule' ) {
			$new_day                          = ( instagrate_pro()->scheduler->schedule_no_day( 'igp_' . $schedule ) ) ? '' : date( 'D' );
			$new_time                         = date( 'H:00', strtotime( '+1 hour' ) );
			$new_account_meta['posting_day']  = $new_day;
			$new_account_meta['posting_time'] = $new_time;
		}
		$new_account_meta['posting_multiple'] = $old_options[ $tag . '_schedule_config' ];
		if ( isset( $old_options[ $tag . '_single_config' ] ) ) {
			$new_account_meta['posting_same_post'] = $old_options[ $tag . '_single_config' ];
		}
		$new_account_meta['posting_image_order']     = 'ASC';
		$new_account_meta['posting_single_location'] = 'top';

		// Post Settings
		$new_account_meta['post_save_media']     = ( $old_options[ $tag . '_image_saving' ] == 'media' ) ? 'on' : 'off';
		$new_account_meta['post_featured_image'] = ( $old_options[ $tag . '_image_saving' ] == 'media' && $old_options[ $tag . '_feat_img' ] == 'true' ) ? 'on' : 'off';
		$new_account_meta['post_type']           = $old_options[ $tag . '_post_type' ];
		if ( $old_options[ $tag . '_post_type' ] == 'post' ) {
			$new_account_meta['post_taxonomy'] = 'category';
			$new_account_meta['post_term']     = (array) $old_options[ $tag . '_post_category' ];
			if ( $old_options[ $tag . '_tags' ] == 'true' ) {
				$new_account_meta['post_tag_taxonomy'] = 'post_tag';
			}
		}
		$new_account_meta['post_author'] = $old_options[ $tag . '_post_author' ];
		$new_account_meta['post_status'] = $old_options[ $tag . '_post_status' ];
		$new_account_meta['post_format'] = $old_options[ $tag . '_post_format' ];
		$new_account_meta['post_date']   = ( $old_options[ $tag . '_post_date' ] == 'true' ) ? 'on' : 'off';

		// Map Settinsg
		$new_account_meta['map_style']  = 'ROAD';
		$new_account_meta['map_css']    = $old_options[ $tag . '_location_css' ];
		$new_account_meta['map_width']  = $old_options[ $tag . '_location_width' ];
		$new_account_meta['map_height'] = $old_options[ $tag . '_location_height' ];

		// Custom
		$new_title_tag = ( isset( $old_options[ $tag . '_title' ] ) && $old_options[ $tag . '_title' ] == 'true' ) ? '%%caption-no-tags%%' : '%%caption%%';
		$wp_post_title = ( isset( $old_options[ $tag . '_custom_title' ] ) ) ? $old_options[ $tag . '_custom_title' ] : '';
		if ( $wp_post_title == '' ) {
			$wp_post_title = $new_title_tag;
		} else {
			$wp_post_title = str_replace( '%%title%%', $new_title_tag, $wp_post_title );
		}

		$old_custom_body = $old_options[ $tag . '_custom_body' ];

		//%%title%%
		$old_custom_body = str_replace( '%%title%%', $new_title_tag, $old_custom_body );
		//%%image%%
		if ( $old_options[ $tag . '_img_post' ] == 'true' ) {
			$new_image_class = ( ! isset( $old_options[ $tag . '_css' ] ) || $old_options[ $tag . '_css' ] == '' ) ? '' : ' class="' . $old_options[ $tag . '_css' ] . '"';
			$new_image_size  = ( ! isset( $old_options[ $tag . '_size' ] ) || $old_options[ $tag . '_size' ] == '' ) ? '' : ' width="' . $old_options[ $tag . '_size' ] . ' height="' . $old_options[ $tag . '_size' ] . '"';
			$new_image       = '<img alt="' . $new_title_tag . '" src="%%image%%"' . $new_image_class . $new_image_size . '>';
			if ( isset( $old_options[ $tag . '_link' ] ) && $old_options[ $tag . '_link' ] != 'no' ) {
				$new_link_target = ( isset( $old_options[ $tag . '_link_target' ] ) && $old_options[ $tag . '_link_target' ] == 'true' ) ? ' target="_blank"' : '';
				$new_link        = '%%instagram-image-url%%';
				if ( $old_options[ $tag . '_image_saving' ] == 'media' && $old_options[ $tag . '_link' ] == 'image' ) {
					$new_link = '%%wordpress-image-url%%';
				}
				$new_image = '<a href="' . $new_link . '" title="' . $new_title_tag . '"' . $new_link_target . '>' . $new_image . '</a>';
			}
			if ( $old_custom_body == '' ) {
				$old_custom_body = $new_image;
			} else {
				$old_custom_body = str_replace( '%%image%%', $new_image, $old_custom_body );
			}
		} else {
			$old_custom_body = str_replace( '%%image%%', '', $old_custom_body );
		}
		//%%link%%
		if ( $old_options[ $tag . '_link_text' ] != '' ) {
			$new_link        = '<a href="%%instagram-image-url%%" title="' . $old_options[ $tag . '_link_text' ] . '">' . $old_options[ $tag . '_link_text' ] . '</a>';
			$old_custom_body = str_replace( '%%link%%', $new_link, $old_custom_body );
		}
		//%%date%%
		$old_custom_body = str_replace( '%%date%%', '%%image-date%%', $old_custom_body );
		//%%location%%
		$old_custom_body = str_replace( '%%location-name%%', '%%image-date%%', $old_custom_body );
		//Gallery
		if ( $new_account_meta['posting_multiple'] != 'each' && $old_options[ $tag . '_gallery' ] == 'true' ) {
			$old_custom_body = '[gallery]<br/>' . $old_custom_body;
		}
		//%%map%%
		if ( $old_options[ $tag . '_location' ] == 'true' ) {
			$old_custom_body .= '<br/>%%map%%';
		}
		$wp_post_content = $old_custom_body;

		// Insert post
		$new_post    = array(
			'post_title'   => $wp_post_title,
			'post_content' => $wp_post_content,
			'post_status'  => 'publish',
			'post_type'    => 'instagrate_pro'
		);
		$new_post_id = wp_insert_post( $new_post );

		// Add post meta
		update_post_meta( $new_post_id, '_instagrate_pro_settings', $new_account_meta );

		// Run change stream to get images
		$tags = $old_options[ $tag . '_hashtag' ];
		if ( $tags != '' ) {
			$tags = str_replace( ' ', '', $tags );
			$tags = str_replace( '#', '', $tags );
			$tags = str_replace( ',', '', $tags );
		}
		$images = instagrate_pro()->accounts->change_stream( $new_post_id, $stream, $tags, $tags, '', '', 'posted' );

		// Set schedule if needed
		if ( $frequency == 'schedule' ) {
			instagrate_pro()->scheduler->set_schedule( $new_post_id, $new_day, $new_time, 'igp_' . $schedule );
		}
	}
}

// Convert meta for lat long
$table = $wpdb->prefix . 'postmeta';
$wpdb->query( "UPDATE $table SET meta_key = '_igp_latlon' WHERE meta_key = 'igp_latlon'" );

// Delete ig image id meta
$table = $wpdb->prefix . 'postmeta';
$wpdb->query( "DELETE FROM $table WHERE meta_key IN ('instagrate_id', 'instagrate_image_id')" );

// Uninstall of old plugin

//remove schedule hooks
if ( wp_next_scheduled( 'hourly_listen' ) ) {
	wp_clear_scheduled_hook( 'hourly_listen' );
}
if ( wp_next_scheduled( 'twicedaily_listen' ) ) {
	wp_clear_scheduled_hook( 'twicedaily_listen' );
}
if ( wp_next_scheduled( 'daily_listen' ) ) {
	wp_clear_scheduled_hook( 'daily_listen' );
}
if ( wp_next_scheduled( 'weekly_listen' ) ) {
	wp_clear_scheduled_hook( 'weekly_listen' );
}
if ( wp_next_scheduled( 'fortnightly_listen' ) ) {
	wp_clear_scheduled_hook( 'fortnightly_listen' );
}
if ( wp_next_scheduled( 'monthly_listen' ) ) {
	wp_clear_scheduled_hook( 'monthly_listen' );
}

//delete settings and template options
delete_option( 'pvw_igp_template' );
delete_option( 'pvw_igp_options' );
delete_option( 'pvw_igp_accounts' );
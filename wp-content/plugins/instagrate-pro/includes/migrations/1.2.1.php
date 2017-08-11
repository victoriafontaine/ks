<?php
/**
 * Large upgrade to v1.2
 */

$accounts = instagrate_pro()->accounts->get_accounts( 'publish' );
global $wpdb;

if ( isset( $accounts ) && $accounts ) {
	foreach ( $accounts as $key => $account ) {
		$title_template = $account['custom_title'];

		$images = instagrate_pro()->images->get_images( $key, 'posted' );

		if ( isset( $images ) && $images ) {
			foreach ( $images as $image ) {
				// Set up template tags for title
				$this->template_caption              = $image->caption_clean;
				$this->template_caption_tags_no_hash = str_replace( '#', '', $image->caption_clean );
				$this->template_caption_no_tags      = $image->caption_clean_no_tags;
				$this->template_tags                 = implode( apply_filters( 'igp_tag_sep', ' ' ), unserialize( $image->tags ) );
				$image_tags                          = unserialize( $image->tags );
				$this->template_tags_first           = reset( $image_tags );
				$this->template_username             = $image->username;
				$this->template_date                 = date( 'M d, Y @ H:i', instagrate_pro()->helper->get_instagram_time( $image->image_timestamp ) );
				$this->template_filter               = $image->filter;
				$this->template_location_name        = $image->location_name;
				$this->template_location_lat         = $image->latitude;
				$this->template_location_lng         = $image->longitude;
				// Template tags for title
				$template_tags = instagrate_pro()->tags->get_template_tags( 'title' );
				// Custom title
				$wp_post_title = instagrate_pro()->tags->replace_template_tags( $title_template, $template_tags, 'migrate_one_two' );

				$post_table = $wpdb->prefix . 'posts';

				$querystr = $wpdb->prepare( "	SELECT ID
														FROM $post_table
														WHERE post_title = %s
														AND post_status = 'publish' ", $wp_post_title );

				$posts = $wpdb->get_results( $querystr, OBJECT );

				foreach ( $posts as $post ) {
					update_post_meta( $post->ID, '_igp_id', $image->id );
					update_post_meta( $post->ID, 'ig_likes', $image->likes_count );
				}
			}
		}
	}
}
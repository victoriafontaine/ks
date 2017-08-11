<?php
/**
 * Uninstall Intagrate
 *
 * @package     instagrate-pro
 * @subpackage  Uninstall
 * @copyright   Copyright (c) 2014, polevaultweb
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
 */

// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load Intagrate file
include_once( 'instagrate-pro.php' );

global $wpdb;
if ( function_exists( 'is_multisite' ) && is_multisite() ) {
	$current_blog = $wpdb->blogid;
	// Get all blog ids
	$blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	foreach ( $blogids as $blog_id ) {
		switch_to_blog( $blog_id );
		igp_uninstall();
	}
	switch_to_blog( $current_blog );

	return;
} else {
	igp_uninstall();
}


function igp_uninstall() {
	global $wpdb;
	$images_table_name = instagrate_pro()->images->get_table_name();

	// Delete tables igp_images
	$wpdb->query( "DROP TABLE IF EXISTS `$images_table_name`;" );

	// Delete all meta for posts of type Intagrate
	$meta_table = $wpdb->prefix . 'postmeta';
	$post_table = $wpdb->prefix . 'posts';
	$post_type  = INSTAGRATEPRO_POST_TYPE;
	$wpdb->query( "DELETE FROM $meta_table WHERE post_id IN ( SELECT id FROM $post_table WHERE post_type = '$post_type')" );

	// Delete posts of type instagrate_pro
	$wpdb->query( "DELETE FROM $post_table WHERE post_type = '$post_type'" );

	// Delete options settings, version and db version
	delete_option( 'igp_db_version' );
	delete_option( 'pvw_igp_version' );
	delete_option( 'igpsettings_settings' );
}
<?php

/**
 * Install Class
 *
 * @package     instagrate-pro
 * @subpackage  install
 * @copyright   Copyright (c) 2014, polevaultweb
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
 */
class Instagrate_Pro_Install {

	function __construct() {
		register_activation_hook( INSTAGRATEPRO_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( INSTAGRATEPRO_PLUGIN_FILE, array( $this, 'deactivate' ) );
		add_action( 'wpmu_new_blog', array( $this, 'new_blog_install' ), 10, 6 );
	}

	/**
	 * Runs activation when plugin is first activated
	 *
	 * @param $network_wide
	 */
	public function activate( $network_wide ) {

		global $wpdb;
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			// check if it is a network activation - if so, run the activation function for each blog id
			if ( $network_wide ) {
				$current_blog = $wpdb->blogid;
				// Get all blog ids
				$blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
				foreach ( $blogids as $blog_id ) {
					switch_to_blog( $blog_id );
					$this->install();
				}
				switch_to_blog( $current_blog );

				return;
			}
		}

		$this->install();
	}

	/**
	 * Runs install on a new site when plugin is network activated
	 *
	 * @param $blog_id
	 * @param $user_id
	 * @param $domain
	 * @param $path
	 * @param $site_id
	 * @param $meta
	 */
	public function new_blog_install( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
		global $wpdb;
		if ( is_plugin_active_for_network( 'instagrate-pro/instagrate-pro' ) ) {
			$old_blog = $wpdb->blogid;
			switch_to_blog( $blog_id );
			$this->install();
			switch_to_blog( $old_blog );
		}
	}

	/**
	 * Deactivate plugin across network
	 *
	 * @param $network_wide
	 */
	function deactivate( $network_wide ) {
		global $wpdb;
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			// check if it is a network deactivation - if so, run the deactivation function for each blog id
			if ( $network_wide ) {
				$current_blog = $wpdb->blogid;
				// Get all blog ids
				$blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
				foreach ( $blogids as $blog_id ) {
					switch_to_blog( $blog_id );
					$this->deactivate_plugin();
				}
				switch_to_blog( $current_blog );

				return;
			}
		}
		$this->deactivate_plugin();
	}

	/**
	 * Deactivate - clear schedules to stop WP cron from posting
	 */
	public function deactivate_plugin() {
		instagrate_pro()->scheduler->clear_schedules();
	}

	/**
	 * Install
	 * - Reactivate schedules if not upgrade
	 * - Create tables
	 *
	 * @param bool $upgrade
	 */
	function install( $upgrade = false ) {
		// Reactivate schedules
		if ( ! $upgrade ) {
			instagrate_pro()->scheduler->reactivate_schedules();
		}

		// Install Table(s)
		instagrate_pro()->images->create_table();
	}
}


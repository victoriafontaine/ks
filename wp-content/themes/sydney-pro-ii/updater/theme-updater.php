<?php
/**
 * Easy Digital Downloads Theme Updater
 *
 * @package Sydney Pro
 */

// Includes the files needed for the theme updater
if ( !class_exists( 'EDD_Theme_Updater_Admin' ) ) {
	include( dirname( __FILE__ ) . '/theme-updater-admin.php' );
}

// Loads the updater classes
$updater = new EDD_Theme_Updater_Admin(

	// Config settings
	$config = array(
		'remote_api_url' => 'http://athemes.com', // Site where EDD is hosted
		'item_name'      => 'Sydney Pro', // Name of theme
		'theme_slug'     => 'sydney-pro-ii', // Theme slug
		'version'        => '1.56', // The current version of this theme
		'author'         => 'aThemes', // The author of this theme
		'download_id'    => '', // Optional, used for generating a license renewal link
		'renew_url'      => '', // Optional, allows for a custom license renewal link
	),

	// Strings
	$strings = array(
		'theme-license'             => __( 'Theme License', 'talon' ),
		'enter-key'                 => __( 'Enter your theme license key.', 'talon' ),
		'license-key'               => __( 'License Key', 'talon' ),
		'license-action'            => __( 'License Action', 'talon' ),
		'deactivate-license'        => __( 'Deactivate License', 'talon' ),
		'activate-license'          => __( 'Activate License', 'talon' ),
		'status-unknown'            => __( 'License status is unknown.', 'talon' ),
		'renew'                     => __( 'Renew?', 'talon' ),
		'unlimited'                 => __( 'unlimited', 'talon' ),
		'license-key-is-active'     => __( 'License key is active.', 'talon' ),
		'expires%s'                 => __( 'Expires %s.', 'talon' ),
		'expires-never'             => __( 'Lifetime License.', 'talon' ),
		'%1$s/%2$-sites'            => __( 'You have %1$s / %2$s sites activated.', 'talon' ),
		'license-key-expired-%s'    => __( 'License key expired %s.', 'talon' ),
		'license-key-expired'       => __( 'License key has expired.', 'talon' ),
		'license-keys-do-not-match' => __( 'License keys do not match.', 'talon' ),
		'license-is-inactive'       => __( 'License is inactive.', 'talon' ),
		'license-key-is-disabled'   => __( 'License key is disabled.', 'talon' ),
		'site-is-inactive'          => __( 'Site is inactive.', 'talon' ),
		'license-status-unknown'    => __( 'License status is unknown.', 'talon' ),
		'update-notice'             => __( "Updating this theme will lose any code customizations you have made. 'Cancel' to stop, 'OK' to update.", 'talon' ),
		'update-available'          => __('<strong>%1$s %2$s</strong> is available. <a href="%3$s" class="thickbox" title="%4s">Check out what\'s new</a> or <a href="%5$s"%6$s>update now</a>.', 'talon' ),
	)

);

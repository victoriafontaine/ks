<?php

/**
 * Upgrade Class
 *
 * @package     instagrate-pro
 * @subpackage  upgrade
 * @copyright   Copyright (c) 2014, polevaultweb
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
 */
class Instagrate_Pro_Upgrade {

	public $installed_key = 'pvw_igp_version';

	function __construct() {
		add_action( 'admin_init', array( $this, 'upgrade_check' ) );
	}

	/**
	 * Runs the upgrade check for the plugin
	 *
	 * @action admin_init
	 */
	function upgrade_check() {
		$current_version = get_option( $this->installed_key, false );
		if ( false === $current_version ) {
			add_option( $this->installed_key, INSTAGRATEPRO_VERSION );

			return;
		}
		
		if ( version_compare( $current_version, INSTAGRATEPRO_VERSION, '!=' ) ) {
			// Database upgrades
			instagrate_pro()->installer->install( true );

			// Load migrations
			$this->load_migrations( $current_version );

			// Finally update the database version
			update_option( $this->installed_key, INSTAGRATEPRO_VERSION );
		}
	}

	/**
	 * Includes all the migration files needed to run
	 *
	 * @param $current_version
	 */
	function load_migrations( $current_version ) {
		$migration_files = glob( INSTAGRATEPRO_PLUGIN_DIR . '/includes/migrations/*.php' );

		if ( $migration_files ) {
			foreach ( $migration_files as $migration ) {
				$migration_version = basename( $migration, '.php' );
				if ( version_compare( $current_version, $migration_version, '<' ) ) {
					include_once( $migration );
				}
			}
		}
	}
}

new Instagrate_Pro_Upgrade();
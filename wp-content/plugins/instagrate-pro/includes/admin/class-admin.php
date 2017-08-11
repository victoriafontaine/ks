<?php

class Instagrate_Pro_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'disable_menu' ) );
		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
	}

	public function disable_menu() {
		$default       = array();
		$default[]     = 'administrator';
		$allowed_roles = apply_filters( 'igp_allowed_roles', $default );
		global $current_user;
		$user_roles = $current_user->roles;
		$show       = false;
		foreach ( $allowed_roles as $role ) {
			if ( in_array( $role, $user_roles ) ) {
				$show = true;
			}
		}
		if ( ! $show ) {
			remove_menu_page( 'edit.php?post_type=' . INSTAGRATEPRO_POST_TYPE );
		}
	}

	function plugin_action_links( $links, $file ) {
		static $this_plugin;
		if ( ! $this_plugin ) {
			$this_plugin = plugin_basename( __FILE__ );
		}
		if ( $file == $this_plugin ) {
			$settings_link = '<a href="' . get_admin_url() . 'edit.php?post_type='. INSTAGRATEPRO_POST_TYPE .'&page=instagrate-pro-settings">' . __( 'Settings', 'instagrate-pro' ) . '</a>';
			array_unshift( $links, $settings_link );
		}

		return $links;
	}

}

new Instagrate_Pro_Admin();
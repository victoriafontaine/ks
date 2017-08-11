<?php

class Instagrate_Pro_Settings {

	/**
	 * @var array
	 */
	public $settings;

	/**
	 * @var igpWordPressSettingsFramework
	 */
	public $wpsf;

	function __construct() {
		add_action( 'init', array( $this, 'init') );
	}

	public function init() {
		require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/admin/wp-settings-framework.php';
		$this->wpsf = new igpWordPressSettingsFramework( INSTAGRATEPRO_PLUGIN_DIR . 'includes/admin/igp-settings.php', '' );
		add_filter( $this->wpsf->get_option_group() . '_settings_validate', array( $this, 'validate_settings' ) );
		$this->settings = wpsf_get_settings( INSTAGRATEPRO_PLUGIN_DIR . 'includes/admin/igp-settings.php' );
	}

	/**
	 * Process the settings section for display
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param $page
	 */
	function do_settings_sections( $page ) {
		global $wp_settings_sections, $wp_settings_fields;
		$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
		if ( ! isset( $wp_settings_sections ) || ! isset( $wp_settings_sections[$page] ) ) {
			return;
		}
		foreach ( (array) $wp_settings_sections[$page] as $section ) {
			echo '<div id="section-' . $section['id'] . '"class="igp-section' . ( $active_tab == $section['id'] ? ' igp-section-active' : '' ) . '">';
			call_user_func( $section['callback'], $section );
			if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[$page] ) || ! isset( $wp_settings_fields[$page][$section['id']] ) ) {
				continue;
			}
			echo '<table class="form-table">';
			do_settings_fields( $page, $section['id'] );
			echo '</table>
            </div>';
		}
	}

	/**
	 * Validate settings on save
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param $input
	 *
	 * @return mixed
	 */
	function validate_settings( $input ) {
		return $input;
	}

	public function get_settings() {
		return $this->settings;
	}

}
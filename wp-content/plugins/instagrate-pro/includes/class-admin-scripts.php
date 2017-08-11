<?php

class Instagrate_Pro_Admin_Scripts {

	private $version, $min;

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ) );

		$this->version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : INSTAGRATEPRO_VERSION;
		$this->min     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	}

	function add_admin_scripts() {
		global $post;
		if ( ( isset( $post->post_type ) && $post->post_type == INSTAGRATEPRO_POST_TYPE )
		     || ( isset( $_GET['page'] ) && $_GET['page'] == 'instagrate-pro-settings' )
		) {
			// js
			wp_register_script( 'igp-simple-modal', INSTAGRATEPRO_PLUGIN_URL . 'assets/js/lib/jquery.simplemodal.min.js', array( 'jquery' ), $this->version );
			wp_enqueue_script( 'igp-simple-modal' );
			wp_register_script( 'igp-google-geo', 'https://maps.googleapis.com/maps/api/js?sensor=false', array( 'jquery' ), $this->version );
			wp_enqueue_script( 'igp-google-geo' );

			$this->enqueue_video();

			wp_register_script(
				'igp-admin', INSTAGRATEPRO_PLUGIN_URL . "assets/js/admin$this->min.js",
				array(
					'jquery',
					'igp-simple-modal',
					'igp-google-geo',
					'igp-jplayer'
				),
				$this->version
			);
			wp_enqueue_script( 'igp-admin' );
			wp_localize_script(
				'igp-admin',
				'instagrate_pro',
				array(
					'nonce'        => wp_create_nonce( 'instagrate_pro' ),
					'jplayer_path' => INSTAGRATEPRO_PLUGIN_URL . 'assets/js/jquery.jplayer/'
				)
			);

			// css
			wp_register_style( 'igp-admin-style', INSTAGRATEPRO_PLUGIN_URL . "assets/css/admin.css", array(), $this->version );
			wp_enqueue_style( 'igp-admin-style' );
		}
	}

	/**
	 * Register and enqueue the scripts needed for the jPlayer video player.
	 */
	public function enqueue_video() {
		wp_register_script( 'igp-jplayer', INSTAGRATEPRO_PLUGIN_URL . 'assets/js/lib/jquery.jplayer/jquery.jplayer.min.js', array( 'jquery' ), $this->version );
		wp_enqueue_script( 'igp-jplayer' );
		wp_register_style( 'igp-jplayer-style', INSTAGRATEPRO_PLUGIN_URL . "assets/css/video.css", array(), $this->version );
		wp_enqueue_style( 'igp-jplayer-style' );
	}
}
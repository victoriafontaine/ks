<?php

/**
 * Maps Class
 *
 * @package     instagrate-pro
 * @subpackage  maps
 * @copyright   Copyright (c) 2014, polevaultweb
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
 */
class Instagrate_Pro_Maps {

	function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'add_map_scripts' ) );

		add_shortcode( 'igp_map', array( $this, 'get_map_shortcode' ) );
		add_shortcode( 'igp_get_map', array( $this, 'get_map_shortcode' ) );
		add_shortcode( 'igp_multimap', array( $this, 'get_multimap_shortcode' ) );
	}

	/**
	 * Single map shortcode
	 *
	 * @param      $atts
	 * @param null $content
	 *
	 * @return string
	 */
	public function get_map_shortcode( $atts, $content = null ) {
		extract( shortcode_atts( array(
			'lat'         => '',
			'lon'         => '',
			'marker'      => '',
			'style'       => 'ROADMAP',
			'class'       => '',
			'width'       => '400',
			'height'      => '300',
			'width_type'  => 'pixel',
			'height_type' => 'pixel',
			'zoom'        => '15'
		), $atts ) );
		$html = '';
		if ( $lat != '' && $lon != '' ) {
			$width_type  = ( $width_type == 'percent' ) ? '%' : 'px';
			$height_type = ( $height_type == 'percent' ) ? '%' : 'px';

			$html .= '<div class="map_canvas ' . $class . '" ';
			$html .= 'data-lat="' . $lat . '" ';
			$html .= 'data-lon="' . $lon . '" ';
			$html .= 'data-style="' . $style . '" ';
			$html .= 'data-zoom="' . $zoom . '" ';
			if ( $marker != '' ) {
				$html .= 'data-marker="' . $marker . '" ';
			}
			$html .= 'style="width: ' . $width . $width_type . '; height: ' . $height . $height_type . ';">';
			$html .= '</div>';
		}

		return $html;
	}

	/**
	 * Multi Map Shortcode
	 *
	 * @param      $atts
	 * @param null $content
	 *
	 * @return string
	 */
	public function get_multimap_shortcode( $atts, $content = null ) {
		extract( shortcode_atts( array(
			'style'       => 'ROADMAP',
			'class'       => '',
			'width'       => '400',
			'height'      => '300',
			'width_type'  => 'pixel',
			'height_type' => 'pixel',
			'zoom'        => '15'
		), $atts ) );
		$html = '';
		global $post;
		$markers = get_post_meta( $post->ID, '_igp_latlon', true );
		if ( is_array( $markers ) && $markers && count( $markers ) > 0 ) {
			$width_type  = ( $width_type == 'percent' ) ? '%' : 'px';
			$height_type = ( $height_type == 'percent' ) ? '%' : 'px';

			$html .= '<div class="multi_map_canvas ' . $class . '" ';
			$html .= 'data-markers="' . htmlspecialchars( json_encode( $markers ) ) . '" ';
			$html .= 'data-style="' . $style . '" ';
			$html .= 'data-zoom="' . $zoom . '" ';
			$html .= 'style="width: ' . $width . $width_type . '; height: ' . $height . $height_type . ';">';
			$html .= '</div>';
		}

		return $html;
	}

	/**
	 * Register and enqueue the scripts needed for the Google Maps
	 */
	public function add_map_scripts() {
		global $wp_query;
		$posts = $wp_query->posts;
		if ( $this->page_has_maps( $posts ) ) {
			$version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : INSTAGRATEPRO_VERSION;
			$min     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			$google_api_key = apply_filters( 'igp_google_maps_api_key', 'AIzaSyBDXfmkoqkd_-LuGz8jW2E3zH9XNoWaFbM' );
			wp_register_script( 'igp-google-maps', 'https://maps.googleapis.com/maps/api/js?sensor=false&key=' . $google_api_key, array( 'jquery' ), $version );
			wp_enqueue_script( 'igp-google-maps' );
			wp_register_script(
				'igp-maps', INSTAGRATEPRO_PLUGIN_URL . "assets/js/maps$min.js", array(
					'jquery',
					'igp-google-maps'
				), $version
			);
			wp_enqueue_script( 'igp-maps' );
			$custom_rel = Instagrate_Pro_Helper::setting( 'igpsettings_general_lightbox-rel', 'lightbox' );
			wp_localize_script( 'igp-maps', 'igp_maps', array( 'lightbox_rel' => $custom_rel ) );

			wp_register_style( 'igp-maps-style', INSTAGRATEPRO_PLUGIN_URL . "assets/css/maps.css", array(), $version );
			wp_enqueue_style( 'igp-maps-style' );
		}
	}

	/**
	 * Check if a page has the maps shortcode
	 *
	 * @param $posts
	 *
	 * @return bool
	 */
	public function page_has_maps( $posts ) {
		$result = false;
		if ( isset( $posts ) && is_array( $posts ) ) {
			foreach ( $posts as $post ) {
				$post_id = $post->ID;
				if ( get_post_meta( $post_id, '_igp_latlon' ) && $this->has_map_shortcode( $post->post_content ) ) {
					$result = true;
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Checks if some content has the map shortcodes
	 *
	 * @param $content
	 *
	 * @return bool
	 */
	public function has_map_shortcode( $content ) {
		$shortcodes = array( 'igp_map', 'igp_multimap', 'igp_get_map' );
		foreach ( $shortcodes as $shortcode ) {
			if ( instagrate_pro()->helper->has_shortcode( $content, $shortcode ) ) {
				return true;
			}
		}

		return false;
	}
}

new Instagrate_Pro_Maps();
<?php

/**
 * HTTP Class
 *
 * @package     instagrate-pro
 * @subpackage  http
 * @copyright   Copyright (c) 2014, polevaultweb
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
 */
class Instagrate_Pro_Http {

	public $api_base;
	public $http_timeout = 60;
	public $http_user_agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)';

	function __construct() {
		$this->api_base = 'https://api.instagram.com/v1/';
	}

	/**
	 * Main HTTP request
	 *
	 * @param        $access
	 * @param        $url
	 * @param string $params
	 * @param string $full_url
	 *
	 * @return array|bool|mixed
	 */
	public function do_http_request( $access, $url, $params = '', $full_url = '' ) {
		if ( $full_url == '' ) {
			$url = $this->api_base . $url;
			$url = $url . '?access_token=' . $access;
			$url = $url . $this->encode_params( $params );
		} else {
			$url = $full_url;
		}
		$contents = wp_remote_get(
			$url, array(
				'sslverify'  => false,
				'user-agent' => $this->http_user_agent,
				'timeout'    => $this->http_timeout
			)
		);
		if ( is_wp_error( $contents ) ) {
			return false;
		}
		if ( 200 == $contents ['response']['code'] ) {
			if ( is_wp_error( $contents ) || ! isset( $contents['body'] ) ) {
				return false;
			}
			$contents = $contents['body'];
			if ( $contents == '' ) {
				return false;
			}
			if ( empty( $contents ) ) {
				return false;
			}
			$data = json_decode( $contents );

			return $data;
		} else {
			return false;
		}
	}

	/**
	 * Encode url parameters
	 *
	 * @param $params
	 *
	 * @return string
	 */
	private function encode_params( $params ) {
		$postdata = '';
		if ( empty( $params ) ) {
			return $postdata;
		}
		foreach ( $params as $key => $value ) {
			$postdata .= '&' . $key . '=' . urlencode( $value );
		}

		return $postdata;
	}
}
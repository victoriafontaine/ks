<?php

/**
 * Instagram Class
 *
 * @package     instagrate-pro
 * @subpackage  instagram
 * @copyright   Copyright (c) 2014, polevaultweb
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
 */
class Instagrate_Pro_Instagram extends Instagrate_Pro_Http {

	private $access_token_url;
	private $authorize_url;
	private $consumer_key;
	private $consumer_secret;
	private $redirect_uri;

	function __construct() {
		parent::__construct();
		$this->consumer_key     = '41107c261543439b870a95c97fd17398';
		$this->consumer_secret  = '6879b4b9656c4365aed0843333f641b4';
		$this->redirect_uri     = 'http://plugins.polevaultweb.com/igpv2.php';
		$this->access_token_url = 'https://api.instagram.com/oauth/access_token/';
		$this->authorize_url    = 'https://api.instagram.com/oauth/authorize/';
	}

	/**
	 * Check Instagram API is up and available - api_base should always 404
	 *
	 * @return array
	 */
	public function instagram_api_check() {
		$response    = array();
		$response[0] = 1;
		$response[1] = '';
		$resp        = wp_remote_get(
			$this->api_base, array(
				'sslverify'  => false,
				'user-agent' => $this->http_user_agent,
				'timeout'    => $this->http_timeout
			)
		);
		if ( is_wp_error( $resp ) ) {
			$response[0] = 0;
			$response[1] = __( 'Error attempting API check:', 'instagrate-pro' ) . ' ' . $resp->get_error_message();
		} else {
			if ( 404 != $resp['response']['code'] ) {
				$response[0] = 0;
				$response[1] = __( 'Error: Instagram API Servers Down', 'instagrate-pro' );
			}
		}

		return $response;
	}

	/**
	 * Get url of current page
	 *
	 * @param bool $reconnect
	 *
	 * @return string
	 */
	private function instagram_callback_url( $reconnect = false ) {
		$protocol = ( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" ) ? "https://" : "http://";
		$callback = $protocol . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

		if ( $reconnect ) {
			$callback .= '&reconnect=true';
		}

		return $callback;
	}

	/**
	 * Check if a custom api client has been configured
	 *
	 * @return bool
	 */
	public function custom_api_client() {
		$enabled = Instagrate_Pro_Helper::setting( 'igpsettings_api_enable-custom-client', '0' );
		if ( $enabled == '0' ) {
			return false;
		}

		$client_id     = Instagrate_Pro_Helper::setting( 'igpsettings_api_custom-client-id', '' );
		$client_secret = Instagrate_Pro_Helper::setting( 'igpsettings_api_custom-client-secret', '' );

		if ( $client_id == '' || $client_secret == '' ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the api client id, configured or default
	 *
	 * @return string
	 */
	public function get_client_id() {
		$client_id = $this->consumer_key;
		if ( $this->custom_api_client() ) {
			$client_id = Instagrate_Pro_Helper::setting( 'igpsettings_api_custom-client-id', '' );
		}

		return $client_id;
	}

	/**
	 * Get the api client secret, configured or default
	 *
	 * @return string
	 */
	public function get_client_secret() {
		$client_secret = $this->consumer_secret;
		if ( $this->custom_api_client() ) {
			$client_secret = Instagrate_Pro_Helper::setting( 'igpsettings_api_custom-client-secret', '' );
		}

		return $client_secret;
	}

	/**
	 * Get the redirect uri passed to Instagram api
	 *
	 * @param string $callback
	 *
	 * @param bool   $reconnect
	 *
	 * @return string
	 */
	private function get_redirect_uri( $callback = '', $reconnect = false ) {
		if ( '' == $callback ) {
			$callback = $this->instagram_callback_url( $reconnect );
		}

		$return_uri = base64_encode( $callback );

		return ( ( $this->custom_api_client() ) ? get_admin_url() : $this->redirect_uri ) . '?return_uri=' . $return_uri;
	}

	/**
	 * Get the Instagram authorise url
	 *
	 * @param bool $reconnect
	 *
	 * @return string
	 */
	public function instagram_authorise_url( $reconnect = false ) {
		$params = array(
			'client_id'     => $this->get_client_id(),
			'redirect_uri'  => $this->get_redirect_uri( '', $reconnect ),
			'response_type' => 'code',
			'scope'         => 'basic public_content'
		);

		$url = $this->authorize_url. '?' . http_build_query( $params );

		return $url;
	}

	/**
	 * Retrieve Access token from code
	 *
	 * @param      $code
	 * @param bool $reconnect
	 *
	 * @return array
	 */
	public function instagram_auth_token( $code, $reconnect = false ) {
		$callback         = $this->instagram_callback_url( $reconnect );
		$callback         = substr( $callback, 0, strpos( $callback, 'igp_code' ) - 1 );
		$token            = array();
		$token['error']   = false;
		$token['message'] = '';
		$token['token']   = '';
		$client_id        = $this->get_client_id();
		$client_secret    = $this->get_client_secret();
		$redirect_uri     = $this->get_redirect_uri( $callback );
		$data             = array(
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'grant_type'    => 'authorization_code',
			'redirect_uri'  => $redirect_uri,
			'code'          => $code
		);
		$remote           = wp_remote_post(
			$this->access_token_url,
			array(
				'method'     => 'POST',
				'sslverify'  => false,
				'timeout'    => $this->http_timeout,
				'user-agent' => $this->http_user_agent,
				'body'       => $data
			)
		);
		$response         = wp_remote_retrieve_body( $remote );
		if ( is_wp_error( $response ) ) {
			$token['error']   = true;
			$token['message'] = $response->get_error_message();
		} else {
			$token['token'] = json_decode( $response );
		}

		return $token;
	}

	/**
	 * Get comments for a media item
	 *
	 * @param $access
	 * @param $media_id
	 *
	 * @return string
	 */
	public function get_media_comments( $access, $media_id ) {
		$url   = 'media/' . $media_id . '/comments';
		$data  = $this->do_http_request( $access, $url, array() );
		$media = '';
		if ( ! $data ) {
			return $media;
		}
		if ( $data->meta->code == 200 ) {
			$media = $data->data;
		}

		return $media;
	}

	/**
	 * Get locations in Instagram
	 *
	 * @param      $account_id
	 * @param      $location
	 * @param      $lat
	 * @param      $lng
	 * @param bool $ajax
	 *
	 * @return array
	 */
	public function get_locations( $account_id, $location, $lat, $lng, $ajax = false ) {
		$account_settings = get_post_meta( $account_id, '_instagrate_pro_settings', true );
		$access           = $account_settings['token'];
		$options          = get_post_meta( $account_id, '_instagrate_pro_settings', true );
		$url              = 'locations/search/';
		$new_locations    = array();
		$distance         = Instagrate_Pro_Helper::setting( 'igpsettings_general_location-distance', '' );
		if ( $lat == '' || $lng == '' ) {
			$new_locations[0] = '— ' . __( 'Enter Location', 'instagrate-pro' ) . ' —';

			return $new_locations;
		}
		$new_locations[0] = '— ' . __( 'No Locations Found', 'instagrate-pro' ) . ' —';
		$params           = array( 'lat' => $lat, 'lng' => $lng );
		if ( $distance != '' ) {
			$params['distance'] = $distance;
		}
		$data = $this->do_http_request( $access, $url, $params );
		if ( ! $data ) {
			if ( $ajax ) {
				$options['ig_error'] = 'The Instagram API is currently throwing errors for large locations';
			} else {
				$options['ig_error'] = '<strong>Instagram API Error</strong> For large locations try settings the distance to default or 500 metres in the <a href="' . admin_url( 'edit.php?post_type=instagrate_pro&page=instagrate-pro-settings&tab=general' ) . '">settings</a>';
			}
		} else {

			if ( $data->meta->code == 200 ) {
				$locations = $data->data;
				if ( $locations && is_array( $locations ) ) {
					$new_locations[0] = '— ' . __( 'Select', 'instagrate-pro' ) . ' —';
					foreach ( $locations as $ig_location ) {
						$new_locations[ $ig_location->id ] = $ig_location->name;
					}
					$options['instagram_location'] = $location;
					$options['location_lat']       = $lat;
					$options['location_lng']       = $lng;
				}
				$options['ig_error'] = '';
			} else {
				$options['ig_error'] = '<strong>' . $data->meta->error_type . '</strong> ' . $data->meta->error_message;
			}

		}
		update_post_meta( $account_id, '_instagrate_pro_settings', $options );
		if ( $ajax ) {
			return array( 'locations' => $new_locations, 'error' => $options['ig_error'] );
		}

		return $new_locations;
	}

	/**
	 * Get Instagram User
	 *
	 * @param $access
	 * @param $user_id
	 *
	 * @return string
	 */
	public function get_user( $access, $user_id ) {
		$url  = 'users/' . $user_id . '/';
		$data = $this->do_http_request( $access, $url, array() );
		$user = '';
		if ( ! $data ) {
			return $user;
		}
		if ( $data->meta->code == 200 ) {
			$user = $data->data;
		}

		return $user;
	}

	/**
	 * Get Instagram media
	 *
	 * @param $access
	 * @param $media_id
	 *
	 * @return string
	 */
	function get_media( $access, $media_id ) {
		$url   = 'media/' . $media_id . '/';
		$data  = $this->do_http_request( $access, $url, array() );
		$media = '';
		if ( ! $data ) {
			return $media;
		}
		if ( $data->meta->code == 200 ) {
			$media = $data->data;
		}

		return $media;
	}

}
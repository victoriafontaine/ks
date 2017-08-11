<?php

/**
 * Debug Class
 *
 * @package     instagrate-pro
 * @subpackage  debug
 * @copyright   Copyright (c) 2014, polevaultweb
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
 */
class Instagrate_Pro_Debug {

	function __construct() {
		add_action( 'admin_init', array( $this, 'handle_download_data' ) );
	}

	/**
	 * Check if we can write a file
	 *
	 * @param $file
	 *
	 * @return bool
	 */
	public function can_write( $file ) {
		$fhandle = @fopen( $file, 'a' );
		if ( $fhandle == '' ) {
			return false;
		} else {
			fclose( $fhandle );

			return true;
		}
	}

	/**
	 * Write out the debug file
	 *
	 * @param $string
	 */
	public function plugin_debug_write( $string ) {
		$debug_path_file = INSTAGRATEPRO_PLUGIN_DIR . 'debug.txt';
		if ( $this->can_write( $debug_path_file ) == true ) {
			try {
				$fh = fopen( $debug_path_file, "a" );
				fwrite( $fh, $string );
				fclose( $fh );
			} catch ( Exception $e ) {
			}
		}
	}

	/**
	 * Check if the debug file exists
	 *
	 * @return bool
	 */
	public function debug_file_exists() {
		$debug_mode = Instagrate_Pro_Helper::setting( 'igpsettings_support_debug-mode', 0 );
		$debug_file = INSTAGRATEPRO_PLUGIN_DIR . 'debug.txt';
		$file       = file_exists( $debug_file );
		if ( $debug_mode == 1 && $file ) {
			return true;
		}

		return false;
	}
	
	/**
	 * Listen for diagnostic log requests and render it
	 */
	public function handle_download_data() {
		global $typenow;
		
		if ( ! isset( $typenow ) || INSTAGRATEPRO_POST_TYPE !== $typenow ) {
			return;
		}
		
		$download = filter_input( INPUT_GET, 'download' );
		if ( ! isset( $download ) || 'data' !== $download ) {
			return;
		}

		$nonce = filter_input( INPUT_GET,  'nonce' );
		if ( ! isset( $nonce ) || ! wp_verify_nonce( $nonce, 'install-data' ) ) {
			return;
		}

		$log = $this->get_install_body();

		$url      = parse_url( home_url() );
		$host     = sanitize_file_name( $url['host'] );
		$filename = sprintf( '%s-intagrate-install-data-%s.txt', $host, date( 'YmdHis' ) );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Length: ' . strlen( $log ) );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		echo $log;
		exit;
	}

	/**
	 * Get the install data including account settings
	 *
	 * @return string
	 */
	private function get_install_body() {
		$html         = '';
		$break        = "\n";
		$install_data = $this->get_install_data();
		$account_data = instagrate_pro()->accounts->get_account_data();
		$data         = array_merge( (array) $install_data, (array) $account_data );
		foreach ( $data as $key => $setting ) {
			if ( $key == 'title' ) {
				$html .= '================= ' . $setting . ' =====================';
				$html .= $break . $break;
			} elseif ( substr( $key, 0, 6 ) == 'title-' ) {
				$html .= $break;
				$html .= '== ' . $setting . ' ==';
				$html .= $break . $break;
			} else {
				$html .= $key . ': ' . $setting;
				$html .= $break;
			}
		}

		return $html;
	}

	/**
	 * Get the install data
	 *
	 * @return array
	 */
	public function get_install_data() {
		global $current_user, $wpt_version;
		get_currentuserinfo();
		$data          = array();
		$data['title'] = 'Install Data - Intagrate v' . INSTAGRATEPRO_VERSION;
		// WordPress
		$data['title-wp'] = 'WordPress Settings';
		$wpver            = get_bloginfo( 'version' );
		$data['Version']  = $wpver;
		$data['URL']      = home_url();
		$data['Install']  = get_bloginfo( 'wpurl' );
		$data['Language'] = get_bloginfo( 'language' );
		$data['Charset']  = get_bloginfo( 'charset' );
		//PHP
		$data['title-php']       = 'PHP Settings';
		$data['PHP Version']     = phpversion();
		$data['Server Software'] = $_SERVER['SERVER_SOFTWARE'];
		$data['User Agent']      = $_SERVER['HTTP_USER_AGENT'];
		$data['cURL Init']       = ( function_exists( 'curl_init' ) ) ? 'On' : 'Off';
		$data['cURL Exec']       = ( function_exists( 'curl_exec' ) ) ? 'On' : 'Off';
		// Sessions
		$data['title-sess']             = 'Session Settings';
		$_SESSION['enableSessionsTest'] = "On";
		$data['Session Support']        = ! empty( $_SESSION['enableSessionsTest'] ) ? "Enabled" : "Disabled";
		$data['Session name']           = ini_get( 'session.name' );
		$data['Cookie path path']       = ini_get( 'session.cookie_path' );
		$data['Save path']              = ini_get( 'session.save_path' );
		$data['Use cookies']            = ( ini_get( 'session.use_cookies' ) ? 'On' : 'Off' );
		$data['Use only cookies']       = ( ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off' );
		// Theme
		if ( function_exists( 'wp_get_theme' ) ) {
			$theme_data = wp_get_theme();
			$theme_uri  = $theme_data->ThemeURI;
			$author_uri = $theme_data->Author_URI;
		} else {
			$theme_data = (object) get_theme_data( get_template_directory() . '/style.css' );
			$theme_uri  = $theme_data->URI;
			$author_uri = $theme_data->AuthorURI;
		}
		$theme_version         = $theme_data->Version;
		$theme_name            = $theme_data->Name;
		$author                = $theme_data->Author;
		$theme_parent          = $theme_data->Template;
		$data['title-theme']   = 'Theme Settings';
		$data['Theme Name']    = $theme_name;
		$data['URI']           = $theme_uri;
		$data['Theme Author']  = $author;
		$data['Author URI']    = $author_uri;
		$data['Parent']        = $theme_parent;
		$data['Theme Version'] = $theme_version;
		// Plugins
		$data['title-plugins'] = 'Plugins Activated';
		$plugins               = get_plugins();
		foreach ( array_keys( $plugins ) as $key ) {
			if ( is_plugin_active( $key ) ) {
				$plugin               =& $plugins[ $key ];
				$plugin_name          = $plugin['Name'];
				$plugin_uri           = $plugin['PluginURI'];
				$plugin_version       = $plugin['Version'];
				$data[ $plugin_name ] = 'v.' . $plugin_version . ' - ' . $plugin_uri;
			}
		}

		return $data;
	}

	/**
	 * Add debug text to the debug buffer
	 *
	 * @param      $text
	 * @param bool $divider
	 */
	public function make_debug( $text, $divider = false ) {
		if ( ! instagrate_pro()->controller->debug_mode ) {
			return;
		}
		if ( $divider ) {
			instagrate_pro()->controller->debug_text .= '------------------------------------------------------------------------------' . "\n";
		}
		if ( is_array( $text ) || is_object( $text ) ) {
			$write_text = print_r( $text, true );
		} else {
			$write_text = $text;
		}
		instagrate_pro()->controller->debug_text .= Date( DATE_RFC822 ) . ' -- ' . $write_text . "\n";
	}

	/**
	 * Write out the debug buffer
	 *
	 * @param int $account_id
	 */
	public function write_debug( $account_id = 0 ) {
		// UnLock Account
		$this->make_debug( 'Account Unlocked' );
		instagrate_pro()->accounts->lock_account( $account_id, false );
		if ( ini_get( 'safe_mode' ) ) {
			$this->make_debug( 'Safe Mode On' );
		} else {
			@set_time_limit( 30 );
		}
		if ( ! instagrate_pro()->controller->debug_mode ) {
			return;
		}
		$output = Date( DATE_RFC822 ) . ' -- ' . 'Debug Output Intagrate v' . INSTAGRATEPRO_VERSION . ' for ' . get_bloginfo( 'wpurl' ) . "\n";
		$output .= '------------------------------------------------------------------------------' . "\n";
		$output .= instagrate_pro()->controller->debug_text . '------------------------------------------------------------------------------' . "\n";
		$this->plugin_debug_write( $output );
		instagrate_pro()->controller->debug_text = '';
	}

	function ajax_send_debug_data() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		$response['error']   = true;
		$response['message'] = __( 'There was an error sending your email using wp_mail()', 'instagrate-pro' );
		$email               = $this->send_debug_data();
		$response['message'] = $email[1];
		echo json_encode( $response );
		die;
	}

	function ajax_send_install_data() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'instagrate_pro' ) ) {
			return 0;
		}
		$response['error']   = true;
		$response['message'] = __( 'There was an error sending your email using wp_mail()', 'instagrate-pro' );
		$email               = instagrate_pro()->debug->send_install_data();
		if ( $email ) {
			$response['error']   = false;
			$response['message'] = __( 'Install data sent successfully. Please make sure you have raised an issue on the Support Forum. Without knowing the issue this file isn\'t much help on its own and will not be responded too.', 'instagrate-pro' );
		}
		echo json_encode( $response );
		die;
	}
}
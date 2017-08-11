<?php

class Instagrate_Pro_Licenses {

	private $sellwire_id = 'uz';
	private $sellwire_url = 'https://app.sellwire.net/api/1/';

	public function __construct() {
		if ( is_admin() ) {
			add_action( 'after_plugin_row_' . plugin_basename( INSTAGRATEPRO_PLUGIN_FILE ), array(
				$this,
				'plugin_row'
			), 11 );
			add_action( 'wp_ajax_igp_activate_license', array( $this, 'activate_license' ) );
			add_action( 'wp_ajax_igp_deactivate_license', array( $this, 'deactivate_license' ) );
		}
	}

	function license_args() {
		$args = array(
			'timeout'    => 15,
			'sslverify'  => false,
			'user-agent' => 'WordPress; ' . home_url()
		);

		return $args;
	}

	public function is_license_constant() {
		return defined( 'IGP_LICENSE' );
	}

	public function get_license_key( $settings = false ) {
		if ( $this->is_license_constant() ) {
			$license = IGP_LICENSE;
		} else {
			if ( ! $settings ) {
				$settings = get_option( 'igpsettings_settings' );
			}
			$license = trim( Instagrate_Pro_Helper::setting( 'igpsettings_support_license-key', '', $settings ) );
		}

		return $license;
	}

	public function plugin_row() {
		$licence = $this->get_license_key();
		if ( empty( $licence ) || $licence == '' ) {
			$settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'edit.php?post_type=instagrate_pro&page=instagrate-pro-settings&tab=support' ), __( 'Settings', 'instagrate-pro' ) );
			$message       = 'To finish activating Intagrate, please go to ' . $settings_link . ' and enter your licence key and activate it to enable automatic updates.';
		} else {
			return;
		}
		?>
		<tr class="plugin-update-tr igp-custom">
			<td colspan="3" class="plugin-update">
				<div class="update-message">
					<div class="igp-licence-error-notice" style="display: inline-block;"><?php echo $message; ?></div>
				</div>
			</td>
		</tr>
	<?php
	}

	function activate_license() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], plugin_basename( 'instagrate_pro' ) ) ) {
			return 0;
		}

		if ( ! isset( $_POST['license_key'] ) ) {
			return 0;
		}

		$ajax_response['error']   = false;
		$ajax_response['message'] = '';

		$options                                    = get_option( 'igpsettings_settings' );
		$license                                    = trim( $_POST['license_key'] );
		$options['igpsettings_support_license-key'] = $license;

		$api_params = array(
			'license' => $license,
			'file'    => $this->sellwire_id
		);
		$url = esc_url_raw( add_query_arg( $api_params, $this->sellwire_url . 'activate_license' ) );
		$response = wp_remote_get( $url, $this->license_args() );
		if ( is_wp_error( $response ) ) {
			$ajax_response['error']   = true;
			$ajax_response['message'] = $response->get_error_message();
		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( isset( $license_data->license ) ) {
				$options['igpsettings_support_license-status'] = $license_data->license;
				$ajax_response['license_status']               = $license_data->license;
				$ajax_response['redirect']                     = admin_url( 'edit.php?post_type=instagrate_pro&page=instagrate-pro-settings&tab=support' );
			} else {
				if ( isset( $license_data->error ) ) {
					$ajax_response['error']   = true;
					$ajax_response['message'] = $license_data->error;
				}
			}
		}
		update_option( 'igpsettings_settings', $options );
		echo json_encode( $ajax_response );
		die;
	}

	function deactivate_license() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], plugin_basename( 'instagrate_pro' ) ) ) {
			return 0;
		}

		$ajax_response['error']   = false;
		$ajax_response['message'] = '';

		$options = get_option( 'igpsettings_settings' );
		$license = trim( Instagrate_Pro_Helper::setting( 'igpsettings_support_license-key', '', $options ) );

		$api_params = array(
			'license' => $license,
			'file'    => $this->sellwire_id
		);
		$url = esc_url_raw( add_query_arg( $api_params, $this->sellwire_url . 'deactivate_license' ) );
		$response = wp_remote_get( $url, $this->license_args() );
		if ( is_wp_error( $response ) ) {
			$ajax_response['error']   = true;
			$ajax_response['message'] = $response->get_error_message();
		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( isset( $license_data->license ) || ( isset( $license_data->error ) && $license_data->error == 'License expired' ) ) {
				unset( $options['igpsettings_support_license-key'] );
				unset( $options['igpsettings_support_license-status'] );
				update_option( 'igpsettings_settings', $options );
				$ajax_response['license_status'] = 'deactivated';
				$ajax_response['redirect']       = admin_url( 'edit.php?post_type=instagrate_pro&page=instagrate-pro-settings&tab=support' );
			} else {
				if ( isset( $license_data->error ) ) {
					$ajax_response['error']   = true;
					$ajax_response['message'] = $license_data->error;
				}
			}
		}
		echo json_encode( $ajax_response );
		die;
	}
}
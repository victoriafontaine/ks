<?php

/**
 * Scheduler Class
 *
 * @package     instagrate-pro
 * @subpackage  scheduler
 * @copyright   Copyright (c) 2014, polevaultweb
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
 */
class Instagrate_Pro_Scheduler {

	function __construct() {
		add_filter( 'cron_schedules', array( $this, 'add_custom_schedules' ) );
	}

	/**
	 * Set a WP cron for an account for posting images
	 *
	 * @param $account_id
	 * @param $day
	 * @param $time
	 * @param $frequency
	 */
	public function set_schedule( $account_id, $day, $time, $frequency ) {
		$args      = array( 'account_id' => $account_id, 'frequency' => 'schedule', 'schedule' => $frequency );
		$blog_time = strtotime( date( 'Y-m-d H', strtotime( current_time( 'mysql' ) ) ) . ':00:00' );
		//Grab the date in the blogs timezone
		$date = date( 'Y-m-d', $blog_time );
		//Check if we need to schedule in the future
		$time_arr    = explode( ':', $time );
		$current_day = date( 'D', $blog_time );
		if ( $day && ( $current_day != $day ) ) {
			$date = date( 'Y-m-d', strtotime( "next $day" ) );
		} else {
			if ( (int) $time_arr[0] <= (int) date( 'H', $blog_time ) ) {
				if ( $day ) {
					$date = date( 'Y-m-d', strtotime( "+7 days", $blog_time ) );
				} else {
					$date = date( 'Y-m-d', strtotime( "+1 day", $blog_time ) );
				}
			}
		}
		// Clear any future schedules
		$this->clear_schedule( $account_id, $frequency );
		//This will be in the blogs timezone
		$scheduled_time = strtotime( $date . ' ' . $time );
		//Convert the selected time to that of the server
		$server_time = strtotime( date( 'Y-m-d H' ) . ':00:00' ) + ( $scheduled_time - $blog_time );
		wp_schedule_event( $server_time, $frequency, 'igp_scheduled_post_account', $args );
	}

	/**
	 * Clear a schedule for an account
	 *
	 * @param $account_id
	 * @param $frequency
	 */
	public function clear_schedule( $account_id, $frequency ) {
		$args      = array( 'account_id' => $account_id, 'frequency' => 'schedule', 'schedule' => $frequency );
		$timestamp = wp_next_scheduled( 'igp_scheduled_post_account', $args );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'igp_scheduled_post_account', $args );
		}
	}

	/**
	 * Get the next schedule set for an account
	 *
	 * @param $account_id
	 * @param $frequency
	 *
	 * @return bool|string
	 */
	public function get_next_schedule( $account_id, $frequency ) {
		$args      = array( 'account_id' => (int) $account_id, 'frequency' => 'schedule', 'schedule' => $frequency );
		$timestamp = wp_next_scheduled( 'igp_scheduled_post_account', $args );
		if ( $timestamp == '' ) {
			return '';
		}

		return date( 'M d, Y @ H:i', $timestamp );
	}

	/**
	 * Clear all schedules for an account
	 *
	 * @param $account_id
	 */
	public function clear_all_schedules( $account_id ) {
		$schedules = $this->get_all_schedules();
		foreach ( $schedules as $key => $schedule ) {
			$this->clear_schedule( $account_id, $key );
		}
	}

	/**
	 * Clear all schedules for all accounts
	 */
	public function clear_schedules() {
		// Remove scheduled hooks
		$accounts = instagrate_pro()->accounts->get_accounts();
		if ( isset( $accounts ) && $accounts ) {
			foreach ( $accounts as $key => $account ) {
				$account_settings = get_post_meta( $key, '_instagrate_pro_settings', true );
				if ( Instagrate_Pro_Helper::setting( 'posting_frequency', 'constant', $account_settings ) == 'schedule' ) {
					$this->clear_all_schedules( $key );
				}
			}
		}
	}

	/**
	 * Reactivate all schedules for all accounts
	 */
	public function reactivate_schedules() {
		$accounts = instagrate_pro()->accounts->get_accounts();
		if ( isset( $accounts ) && $accounts ) {
			foreach ( $accounts as $key => $account ) {
				$account_settings = get_post_meta( $key, '_instagrate_pro_settings', true );
				if ( Instagrate_Pro_Helper::setting( 'posting_frequency', 'constant', $account_settings ) == 'schedule' ) {
					$schedule = Instagrate_Pro_Helper::setting( 'posting_schedule', 'igp_daily', $account_settings );
					$new_day  = ( $this->schedule_no_day( $schedule ) ) ? '' : Instagrate_Pro_Helper::setting( 'posting_day', '', $account_settings );
					$new_time = Instagrate_Pro_Helper::setting( 'posting_time', date( 'H:00', strtotime( '+1 hour' ) ), $account_settings );
					$this->set_schedule( $key, $new_day, $new_time, $schedule );
				}
			}
		}
	}

	/**
	 * Sets up the custom schedules
	 *
	 * @param $schedules
	 * @action cron_schedules
	 *
	 * @return array
	 */
	public function add_custom_schedules( $schedules ) {
		$new_schedules = array(
			'igp_hourly'      => array(
				'interval' => 3600,
				'display'  => __( 'Hourly', 'instagrate-pro' )
			),
			'igp_twicedaily'  => array(
				'interval' => 43200,
				'display'  => __( 'Twice Daily', 'instagrate-pro' )
			),
			'igp_daily'       => array(
				'interval' => 86400,
				'display'  => __( 'Daily', 'instagrate-pro' )
			),
			'igp_weekly'      => array(
				'interval' => 604800,
				'display'  => __( 'Weekly', 'instagrate-pro' )
			),
			'igp_fortnightly' => array(
				'interval' => 1209600,
				'display'  => __( 'Fortnightly', 'instagrate-pro' )
			),
			'igp_monthly'     => array(
				'interval' => 2419200,
				'display'  => __( 'Monthly', 'instagrate-pro' )
			)
		);

		return array_merge( $schedules, $new_schedules );
	}

	/**
	 * Get all schedules
	 *
	 * @param string $schedule
	 *
	 * @return array|string
	 */
	public function get_all_schedules( $schedule = '' ) {
		$options   = array();
		$schedules = wp_get_schedules();
		if ( $schedules ) {
			foreach ( $schedules as $key => $row ) {
				$orderByInterval[ $key ] = $row['interval'];
			}
			array_multisort( $orderByInterval, SORT_ASC, $schedules );
			foreach ( $schedules as $key => $option ) {
				if ( $schedule != '' && $key == $schedule ) {
					return ucfirst( $option['display'] );
				}
				if ( substr( $key, 0, 4 ) == 'igp_' ) {
					$options[ $key ] = ucfirst( $option['display'] );
				}
			}
		}

		return $options;
	}

	/**
	 * Get a certain schedule
	 *
	 * @param $frequency
	 *
	 * @return mixed
	 */
	public function get_schedule( $frequency ) {
		$schedules = wp_get_schedules();

		return $schedules[ $frequency ];
	}

	/**
	 * Check a schedule is less than a day
	 *
	 * @param $frequency
	 *
	 * @return bool
	 */
	public function schedule_no_day( $frequency ) {
		$schedule = $this->get_schedule( $frequency );
		if ( $schedule['interval'] <= 86400 ) {
			return true;
		}

		return false;
	}

}
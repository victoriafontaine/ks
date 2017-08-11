<?php

class Instagrate_Pro_Admin_Notices {

	private $messages = array();

	public function __construct() {

		add_action( 'admin_notices', array( $this, 'show_notices' ) );
	}

	private function load_messages() {
		$messages = array(
			'14'		=>	__( 'Account duplicated', 'instagrate-pro' ),
			'15'		=>	__( 'Likes have been synced for this account successfully', 'instagrate-pro' ),
			'blog_page'	=>	__( 'You must select a page to display your posts in', 'instagrate-pro' ),
			'debug'		=>	__( 'Debug mode is turned on. However, the debug file in the plugin folder is not writeable. Please contact your web hosting provider to amend the permissions on the', 'instagrate-pro' )
		);

		$this->messages = $messages;
	}


	public function show_notices() {
		global $post;
		if ( ( isset( $post->post_type ) && $post->post_type == INSTAGRATEPRO_POST_TYPE )
			 || ( isset( $_GET['page'] ) && $_GET['page'] == 'instagrate-pro-settings' )
		) {

			// Duplicate Account
			if ( isset( $_GET['message'] ) && $_GET['message'] == '14' ) {
				echo '<div class="updated">
							<p>' . __( 'Account duplicated' ) . ' </p>
						</div>';
			}
			// Likes Synced Account
			if ( isset( $_GET['message'] ) && $_GET['message'] == '15' ) {
				echo '<div class="updated">
							<p>' . __( 'Likes have been synced for this account successfully' ) . ' </p>
						</div>';
			}
			// Comments Synced Account
			if ( isset( $_GET['message'] ) && $_GET['message'] == '16' ) {
				echo '<div class="updated">
							<p>' . __( 'Comments have been synced for this account successfully' ) . ' </p>
						</div>';
			}
			// Display check for user to make sure a blog page is selected
			if ( 'page' == get_option( 'show_on_front' ) ) {
				if ( 0 == get_option( 'page_for_posts' ) ) {
					$link_text = __( 'Settings -> Reading', 'instagrate-pro' );
					$link      = '<a href="' . get_admin_url() . 'options-reading.php">' . $link_text . '</a>';
					echo '<div class="updated">
							<p>' . __( 'You must select a page to display your posts in ', 'instagrate-pro' ) . $link . ' </p>
						</div>';
				}
			}
			// Display check to make sure there is write permissions on the debug file
			$debug_mode = Instagrate_Pro_Helper::setting(  'igpsettings_support_debug-mode', '0' );
			$debug_file = INSTAGRATEPRO_PLUGIN_DIR . 'debug.txt';
			$file       = file_exists( $debug_file );
			if ( $debug_mode == 1 && $file ) {
				$write = instagrate_pro()->debug->can_write( $debug_file );
				if ( $write == false ) {
					$link_text = __( 'file', 'instagrate-pro' );
					$link      = ' <a href="' . plugin_dir_url( INSTAGRATEPRO_PLUGIN_FILE ) . 'debug.txt">debug.txt ' . $link_text . '</a>';
					echo '<div class="error">
							<p>' . __( 'Debug mode is turned on. However, the debug file in the plugin folder is not writeable. Please contact your web hosting provider to amend the permissions on the', 'instagrate-pro' ) . $link . '</p>
						  </div>';
				}
			}
			// Instagram API Check
			$check = array();
			$check = instagrate_pro()->instagram->instagram_api_check();
			if ( $check[0] == 0 ) {
				echo '<div class="error"><p>' . $check[1] . '</p></div>';
			}
			// Account Error Message
			if ( isset( $_GET['post'] ) && isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {
				$account_settings = get_post_meta( $post->ID, '_instagrate_pro_settings', true );
				$settings         = (object) $account_settings;
				if ( isset( $settings->ig_error ) && $settings->ig_error != '' ) {
					echo '<div class="error"><p>' . $settings->ig_error . '</p></div>';
				}
			}
			// Auto Draft Warning Message
			if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'instagrate_pro' ) {
				if ( isset( $post->post_status ) && $post->post_status == 'auto-draft' ) {
					echo '<div class="updated"><p>' . __( 'You must save the draft of this account before authorising with Instagram', 'instagrate-pro' ) . '</p></div>';
				}
			}
			// Safe mode for execution time
			if ( ini_get( 'safe_mode' ) && ini_get( 'max_execution_time' ) != 0 ) {
				echo '<div class="updated">
							<p>' . sprintf(
						__(
							"%sSafe mode%s is enabled on your server, so the PHP time and memory limits cannot be set by this plugin.
												Your time limit is %s seconds and your memory limit is %s, so if your accounts are posting lots of images at a time and saving them to the WordPress Media Library this may exceed the execution time. Each host has different methods available to increase these settings and a quick Google search should
												yield some information. If not, please contact your host for help.
												If you cannot find an answer, please feel free to post a new topic.", 'instagrate-pro'
						),
						'<a href="http://php.net/manual/en/features.safe-mode.php"><strong>',
						'</a></strong>',
						ini_get( 'max_execution_time' ),
						ini_get( 'memory_limit' ),
						'</a>'
					) . ' </p>
						</div>';
			}
		}

	}
}

new Instagrate_Pro_Admin_Notices();
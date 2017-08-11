<?php
/**
 * Plugin Name: Intagrate
 * Plugin URI: http://intagrate.io
 * Description: Automatic Instagram image publishing for WordPress
 * Author: polevaultweb
 * Author URI: http://polevaultweb.com
 * Version: 1.7.7
 * Text Domain: instagrate-pro
 * Domain Path: languages
 *
 * Intagrate is distributed under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Intagrate is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Intagrate. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package  instagrate-pro
 * @category Core
 * @author   polevaultweb
 * @version  1.6
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Instagrate_Pro' ) ) {

	/**
	 * Main Instagrate_Pro Class
	 *
	 * @since 1.6
	 */
	final class Instagrate_Pro {
		/** Singleton *************************************************************/

		/**
		 * @var Instagrate_Pro The one true Instagrate_Pro
		 * @since 1.6
		 */
		private static $instance;

		private $version = '1.7.7';

		public $installer;
		public $images;
		public $scheduler;
		public $post_types;
		public $settings;
		public $scripts;
		public $accounts;
		public $helper;
		public $http;
		public $instagram;
		public $tags;
		public $debug;
		public $controller;
		public $likes;
		public $comments;
		public $licenses;

		/**
		 * Main Instagrate_Pro Instance
		 *
		 * Insures that only one instance of Instagrate_Pro exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since     1.6
		 * @static
		 * @staticvar array $instance
		 * @uses      Instagrate_Pro::setup_globals() Setup the globals needed
		 * @uses      Instagrate_Pro::includes() Include the required files
		 * @uses      Instagrate_Pro::setup_actions() Setup the hooks and actions
		 * @uses      Instagrate_Pro::updater() Setup the plugin updater
		 * @return The one true Instagrate_Pro
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Instagrate_Pro ) ) {

				self::$instance = new Instagrate_Pro;
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->load_textdomain();

				// Setup objects
				if ( is_admin() ) {
					self::$instance->installer = new Instagrate_Pro_Install();
				}

				self::$instance->licenses   = new Instagrate_Pro_Licenses();
				self::$instance->images     = new Instagrate_Pro_DB_Images;
				self::$instance->scheduler  = new Instagrate_Pro_Scheduler;
				self::$instance->post_types = new Instagrate_Pro_Post_Types();
				self::$instance->settings   = new Instagrate_Pro_Settings();
				self::$instance->scripts    = new Instagrate_Pro_Admin_Scripts();
				self::$instance->accounts   = new Instagrate_Pro_Accounts();
				self::$instance->helper     = new Instagrate_Pro_Helper();
				self::$instance->http       = new Instagrate_Pro_Http();
				self::$instance->instagram  = new Instagrate_Pro_Instagram();
				self::$instance->tags       = new Instagrate_Pro_Template_Tags();
				self::$instance->debug      = new Instagrate_Pro_Debug();
				self::$instance->controller = new Instagrate_Pro_Controller();
				self::$instance->likes      = new Instagrate_Pro_Likes();
				self::$instance->comments   = new Instagrate_Pro_Comments();

				self::$instance->updater();
			}

			return self::$instance;
		}

		/**
		 * Setup plugin constants
		 *
		 * @access private
		 * @since  1.6
		 * @return void
		 */
		private function setup_constants() {
			// Plugin version
			if ( ! defined( 'INSTAGRATEPRO_VERSION' ) ) {
				define( 'INSTAGRATEPRO_VERSION', $this->version );
			}

			// Plugin Folder Path
			if ( ! defined( 'INSTAGRATEPRO_PLUGIN_DIR' ) ) {
				define( 'INSTAGRATEPRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}

			// Plugin Folder URL
			if ( ! defined( 'INSTAGRATEPRO_PLUGIN_URL' ) ) {
				define( 'INSTAGRATEPRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}

			// Plugin Root File
			if ( ! defined( 'INSTAGRATEPRO_PLUGIN_FILE' ) ) {
				define( 'INSTAGRATEPRO_PLUGIN_FILE', __FILE__ );
			}

			// Plugin Post Type
			if ( ! defined( 'INSTAGRATEPRO_POST_TYPE' ) ) {
				define( 'INSTAGRATEPRO_POST_TYPE', 'instagrate_pro' );
			}
		}

		/**
		 * Include required files
		 *
		 * @access private
		 * @since  1.6
		 * @return void
		 */
		private function includes() {

			if ( is_admin() ) {
				require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/admin/class-upgrade.php';
				require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/admin/class-install.php';
				require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/admin/class-admin.php';
				require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/admin/class-admin-pages.php';
				require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/admin/class-post-meta.php';
				require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/admin/class-notices.php';
			}

			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/emoji-functions.php';
			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/class-licenses.php';
			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/wp-sellwire-plugin.php';
			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/class-db.php';
			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/class-images-db.php';
			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/class-scheduler.php';
			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/class-post-types.php';
			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/class-settings.php';
			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/class-admin-scripts.php';
			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/class-accounts.php';
			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/class-template-tags.php';
			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/class-http.php';
			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/class-instagram.php';
			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/class-helper.php';
			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/class-debug.php';
			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/class-likes.php';
			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/class-comments.php';
			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/class-maps.php';
			require_once INSTAGRATEPRO_PLUGIN_DIR . 'includes/class-controller.php';
		}

		/**
		 * Plugin Updater
		 *
		 * @access private
		 * @since  1.6
		 * @return void
		 */
		private function updater() {
			$license_key = instagrate_pro()->licenses->get_license_key();

			if ( $license_key ) {
				new SellwirePluginUpdater_3lM( 'https://app.sellwire.net/api/1/plugin', plugin_basename(INSTAGRATEPRO_PLUGIN_FILE), $license_key );
			}
		}

		/**
		 * Loads the plugin language files
		 *
		 * @access public
		 * @since  1.6
		 * @return void
		 */
		public function load_textdomain() {
			$lang_dir = dirname( plugin_basename( INSTAGRATEPRO_PLUGIN_FILE ) ) . '/languages/';
			load_plugin_textdomain( 'instagrate-pro', false, $lang_dir );
		}

		/**
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since  1.6
		 * @access protected
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'instagrate-pro' ), '1.0' );
		}

		/**
		 * Disable unserializing of the class
		 *
		 * @since  1.6
		 * @access protected
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'instagrate-pro' ), '1.0' );
		}

	}

} // End if class_exists check

/**
 * The main function responsible for returning the one true Instagrate_Pro
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $instagrate_pro = instagrate_pro(); ?>
 *
 * @since 1.6
 * @return object The one true Instagrate_Pro Instance
 */
function instagrate_pro() {
	return Instagrate_Pro::instance();
}

// Start it up!
instagrate_pro();
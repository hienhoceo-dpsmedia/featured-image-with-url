<?php
/**
 * Class DPSFIWU
 *
 * @package DPSFIWU
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'DPSFIWU' ) ) :

	/**
	 * Main Featured Image with URL class
	 */
	class DPSFIWU {

		/** Singleton *************************************************************/
		/**
		 * DPSFIWU The one true DPSFIWU.
		 *
		 * @var DPSFIWU $instance
		 */
		private static $instance;

		/**
		 * Admin Instance.
		 *
		 * @var DPSFIWU_Admin $admin
		 */
		public $admin;

		/**
		 * Common Instance.
		 *
		 * @var DPSFIWU_Common $common
		 */
		public $common;

		/**
		 * SEO Instance.
		 *
		 * @var DPSFIWU_SEO $seo
		 */
		public $seo;

		/**
		 * Main Featured Image with URL Instance.
		 *
		 * Insure that only one instance of DPSFIWU exists in memory at any one time.
		 * Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0.0
		 * @static object $instance
		 * @uses DPSFIWU::setup_constants() Setup the constants needed.
		 * @uses DPSFIWU::includes() Include the required files.
		 * @uses DPSFIWU::load_textdomain() load the language files.
		 * @see dpsfiwu_run()
		 * @return object|DPSFIWU the one true DPSFIWU Instance.
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DPSFIWU ) ) {
				self::$instance = new DPSFIWU();

				add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
				add_filter( 'plugin_row_meta', array( self::$instance, 'plugin_row_meta' ), 10, 2 );

				self::$instance->includes();
				self::$instance->admin  = new DPSFIWU_Admin();
				self::$instance->common = new DPSFIWU_Common();
				self::$instance->seo    = new DPSFIWU_SEO();

			}
			return self::$instance;
		}

		/** Magic Methods *********************************************************/

		/**
		 * A dummy constructor to prevent DPSFIWU from being loaded more than once.
		 *
		 * @since 1.0.0
		 * @see DPSFIWU::instance()
		 * @see dpsfiwu_run()
		 */
		private function __construct() {
			/* Do nothing here */
		}

		/**
		 * A dummy magic method to prevent DPSFIWU from being cloned.
		 *
		 * @since 1.0.0
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'dpsfiwu' ), '1.0.0' );
		}

		/**
		 * A dummy magic method to prevent DPSFIWU from being unserialized.
		 *
		 * @since 1.0.0
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'dpsfiwu' ), '1.0.0' );
		}

		/**
		 * Include required files.
		 *
		 * @access private
		 * @since 1.0.0
		 * @return void
		 */
		private function includes() {
			require_once DPSFIWU_PLUGIN_DIR . 'includes/class-dpsfiwu-admin.php';
			require_once DPSFIWU_PLUGIN_DIR . 'includes/class-dpsfiwu-common.php';
			require_once DPSFIWU_PLUGIN_DIR . 'includes/class-dpsfiwu-seo.php';
		}

		/**
		 * Loads the plugin language files.
		 *
		 * @access public
		 * @since 1.0.0
		 * @return void
		 */
		public function load_textdomain() {

			load_plugin_textdomain(
				'dpsfiwu',
				false,
				basename( dirname( __FILE__ ) ) . '/languages'
			);
		}

		/**
		 * Add custom links to plugin row in WordPress admin
		 *
		 * @since 1.0.0
		 * @param array $links Existing plugin action links
		 * @param string $file Plugin file path
		 * @return array Modified plugin action links
		 */
		public function plugin_row_meta( $links, $file ) {
			if ( strpos( $file, 'featured-image-with-url.php' ) !== false ) {
				$new_links = array(
					'github' => '<a href="https://github.com/hienhoceo-dpsmedia/featured-image-with-url" target="_blank">' . esc_html__( 'GitHub Repository', 'dpsfiwu' ) . '</a>',
					'documentation' => '<a href="https://dps.media/" target="_blank">' . esc_html__( 'Documentation', 'dpsfiwu' ) . '</a>',
					'support' => '<a href="mailto:marketing@dps.media">' . esc_html__( 'Email Support', 'dpsfiwu' ) . '</a>',
				);
				$links = array_merge( $links, $new_links );
			}
			return $links;
		}
	}

endif; // End If class exists check.

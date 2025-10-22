<?php
/**
 * Plugin Name:       Featured Image with URL
 * Plugin URI:        https://github.com/hienhoceo-dpsmedia/featured-image-with-url
 * Description:       Use external URL images as featured images with SEO optimization. Supports WooCommerce, social media previews, and fallback images. Developed by DPS.MEDIA - Digital Marketing solutions for SMEs since 2017.
 * Version:           1.0.0
 * Author:            DPS.MEDIA JSC
 * Author URI:        https://dps.media/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       dpsfiwu
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Tested up to:      6.7
 * Network:           false
 *
 * @package     DPSFIWU
 * @author      DPS.MEDIA JSC <marketing@dps.media>
 * @copyright   2025 DPS.MEDIA JSC
 * @license     GPL-2.0+
 *
 * GitHub Plugin URI: https://github.com/hienhoceo-dpsmedia/featured-image-with-url
 * GitHub Branch:     main
 * Release Asset: true
 * Primary Branch: main
 *
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set up the plugin constants.
 */
// Plugin version.
if ( ! defined( 'DPSFIWU_VERSION' ) ) {
	define( 'DPSFIWU_VERSION', '1.0.0' );
}

// Plugin folder Path.
if ( ! defined( 'DPSFIWU_PLUGIN_DIR' ) ) {
	define( 'DPSFIWU_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Plugin folder URL.
if ( ! defined( 'DPSFIWU_PLUGIN_URL' ) ) {
	define( 'DPSFIWU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Plugin root file.
if ( ! defined( 'DPSFIWU_PLUGIN_FILE' ) ) {
	define( 'DPSFIWU_PLUGIN_FILE', __FILE__ );
}

// Options.
if ( ! defined( 'DPSFIWU_OPTIONS' ) ) {
	define( 'DPSFIWU_OPTIONS', 'dpsfiwu_options' );
}

// Gallary meta key.
if ( ! defined( 'DPSFIWU_WCGALLARY' ) ) {
	define( 'DPSFIWU_WCGALLARY', '_dpsfiwu_wcgallary' );
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-dpsfiwu.php';

/**
 * The main function for that returns DPSFIWU
 *
 * The main function responsible for returning the one true DPSFIWU
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $dpsfiwu = dpsfiwu_run(); ?>
 *
 * @since 1.0.0
 * @return object|DPSFIWU The one true DPSFIWU Instance.
 */
function dpsfiwu_run() {
	return DPSFIWU::instance();
}

// Get Featured Image With URL Running.
$GLOBALS['dpsfiwu'] = dpsfiwu_run();

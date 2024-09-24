<?php
/**
 * Plugin Name: Confetti
 * Plugin URI: https://www.wpsunshine.com/plugins/confetti
 * Description: Add some fun and excitement to your site with confetti effects on any page of your WordPress site via shortcode or block, easily!
 * Version: 1.3.7
 * Author: WP Sunshine
 * Author URI: https://www.wpsunshine.com
 * Text Domain: confetti
 *
 * @package WPSConfetti
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WPS_CONFETTI_VERSION' ) ) {

	define( 'WPS_CONFETTI_VERSION', '1.3.7' );
	define( 'WPS_CONFETTI_NAME', 'Confetti' );
	define( 'WPS_CONFETTI_PLUGIN_FILE', __FILE__ );
	define( 'WPS_CONFETTI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	define( 'WPS_CONFETTI_ABSPATH', dirname( __FILE__ ) );

	include_once WPS_CONFETTI_ABSPATH . '/includes/class-confetti.php';

	/**
	 * Returns the main instance of WPSunshine_Confetti.
	 *
	 * @since  1.0
	 * @return WPSunshine_Confetti
	 */
	function WPS_Confetti() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
		return WPSunshine_Confetti::instance();
	}

	/**
	 * Sets WPSunshine_Confetti to global variable.
	 *
	 * @since  1.0
	 */
	function wps_confetti_load_me() {
		$GLOBALS['wps_confetti'] = WPS_Confetti();
	}
	add_action( 'plugins_loaded', 'wps_confetti_load_me' );

}

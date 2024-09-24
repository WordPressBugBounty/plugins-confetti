<?php
/**
 * WPSunshine_Confetti_Shortcode
 *
 * @package WPSConfetti\Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WPSunshine_Confetti_Shortcode class.
 */
class WPSunshine_Confetti_Shortcode {

	/**
	 * Constructor to setup shortcode.
	 */
	public function __construct() {

		add_shortcode( 'confetti', array( $this, 'shortcode' ) );

	}

	/**
	 * Process shortcode.
	 *
	 * @param array $atts Array of attributes to parse.
	 */
	public function shortcode( $atts ) {

		$atts = shortcode_atts(
			array(
				'onload' => true,
				'inview' => false,
			),
			$atts
		);

		// Enqueue no matter what.
		WPS_Confetti()->enqueue_scripts();

		$output = '';

		if ( $atts['onload'] === true ) {
			$output = WPS_Confetti()->trigger();
		}
		$output = apply_filters( 'wps_confetti_shortcode', $output, $atts );
		return $output;

	}

}

$wps_confetti_shortcode = new WPSunshine_Confetti_Shortcode();

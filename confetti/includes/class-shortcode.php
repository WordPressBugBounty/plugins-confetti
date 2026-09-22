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

		$defaults = array(
			'onload'   => 'true',
			'inview'   => 'false',
			'instance' => 'default',
		);

		$atts        = shortcode_atts( $defaults, $atts, 'confetti' );
		$instance_id = sanitize_key( $atts['instance'] );

		$onload = filter_var( $atts['onload'], FILTER_VALIDATE_BOOLEAN );
		$inview = filter_var( $atts['inview'], FILTER_VALIDATE_BOOLEAN );

		// Generate unique ID for this shortcode instance
		$unique_id = 'wps-confetti-shortcode-' . wp_rand( 1000, 9999 );

		if ( $inview ) {
			// Render marker element for scroll detection
			$js_safe_id = esc_js( str_replace( '-', '_', $unique_id ) );
			$output     = '<div id="wps-confetti-' . esc_attr( $js_safe_id ) . '" style="height: 1px; width: 100%;"></div>';

			// Create trigger data for inview
			$trigger = array(
				'id'          => $unique_id,
				'instance_id' => $instance_id,
				'method'      => array(
					'event'     => 'inview',
					'inview_id' => 'wps-confetti-' . $js_safe_id,
				),
				'params'      => array(),
			);

			// Use the core confetti class to render the trigger script
			$confetti_script = WPS_Confetti()->render_trigger( $trigger, true );
			$output         .= $confetti_script;
		} else {
			// Create trigger data for page load
			$trigger = array(
				'id'          => $unique_id,
				'instance_id' => $instance_id,
				'method'      => $onload ? 'load' : '',
				'params'      => array(),
			);

			// Use the core confetti class to render the trigger script
			$output = WPS_Confetti()->render_trigger( $trigger, true );
		}

		return $output;

	}

}

$wps_confetti_shortcode = new WPSunshine_Confetti_Shortcode();

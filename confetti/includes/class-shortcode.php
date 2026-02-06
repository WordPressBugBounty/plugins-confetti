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
			'onload'                  => 'true',
			'inview'                  => 'false',
			'instance'                => 'default',
			// Customization parameters
			'style'                   => '',
			'duration'                => '',
			'delay'                   => '',
			'speed'                   => '',
			'particlecount'           => '',
			'angle'                   => '',
			'spread'                  => '',
			'startvelocity'           => '',
			'decay'                   => '',
			'gravity'                 => '',
			'drift'                   => '',
			'ticks'                   => '',
			'scalar'                  => '',
			'zindex'                  => '',
			'origin_x'                => '',
			'origin_y'                => '',
			'disableforreducedmotion' => '',
		);

		$atts = shortcode_atts( $defaults, $atts, 'confetti' );

		// Normalize boolean values
		$onload = filter_var( $atts['onload'], FILTER_VALIDATE_BOOLEAN );
		$inview = filter_var( $atts['inview'], FILTER_VALIDATE_BOOLEAN );

		// Get instance ID
		$instance_id = sanitize_key( $atts['instance'] );

		// Build custom parameters array (only include non-empty values)
		$custom_params = array();
		$param_map     = array(
			'style'                   => 'style',
			'duration'                => 'duration',
			'delay'                   => 'delay',
			'speed'                   => 'speed',
			'particlecount'           => 'particleCount',
			'angle'                   => 'angle',
			'spread'                  => 'spread',
			'startvelocity'           => 'startVelocity',
			'decay'                   => 'decay',
			'gravity'                 => 'gravity',
			'drift'                   => 'drift',
			'ticks'                   => 'ticks',
			'scalar'                  => 'scalar',
			'zindex'                  => 'zindex',
			'origin_x'                => 'origin_x',
			'origin_y'                => 'origin_y',
			'disableforreducedmotion' => 'disableForReducedMotion',
		);

		foreach ( $param_map as $att_key => $param_key ) {
			if ( ! empty( $atts[ $att_key ] ) ) {
				if ( 'disableforreducedmotion' === $att_key ) {
					$custom_params[ $param_key ] = filter_var( $atts[ $att_key ], FILTER_VALIDATE_BOOLEAN );
				} else {
					$custom_params[ $param_key ] = $atts[ $att_key ];
				}
			}
		}

		// Enqueue no matter what.
		WPS_Confetti()->enqueue_scripts( false, $instance_id );

		$output = '';

		// Handle onload
		if ( $onload && empty( $custom_params ) ) {
			// Use default instance trigger
			$output = WPS_Confetti()->trigger( false, true, true, $instance_id );
		} elseif ( $onload && ! empty( $custom_params ) ) {
			// Use custom params trigger
			$output  = '<script id="confetti-trigger">';
			$output .= "document.addEventListener( 'DOMContentLoaded', function( event ) { ";
			$output .= WPS_Confetti()->trigger_with_params( $custom_params, $instance_id );
			$output .= ' } );';
			$output .= '</script>';
		}

		// Handle inview (requires premium)
		if ( $inview ) {
			$output = apply_filters( 'wps_confetti_shortcode_inview', $output, $atts, $custom_params, $instance_id );
		}

		$output = apply_filters( 'wps_confetti_shortcode', $output, $atts );
		return $output;

	}

}

$wps_confetti_shortcode = new WPSunshine_Confetti_Shortcode();

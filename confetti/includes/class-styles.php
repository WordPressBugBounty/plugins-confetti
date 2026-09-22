<?php
/**
 * WPSunshine_Confetti_Styles
 *
 * The style registry. Reads the style catalog and the option definitions and
 * answers every "what styles exist / what can this style do / where is its
 * JavaScript" question for the admin, the block and the frontend loader.
 *
 * @package WPSConfetti\Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WPSunshine_Confetti_Styles class.
 */
class WPSunshine_Confetti_Styles {

	/**
	 * Parsed style catalog, keyed by style ID.
	 *
	 * @var array
	 */
	private static $catalog = null;

	/**
	 * Parsed option definitions, keyed by option ID.
	 *
	 * @var array
	 */
	private static $options = null;

	/**
	 * Read and cache the style catalog.
	 *
	 * @return array
	 */
	public static function get_all() {

		if ( is_null( self::$catalog ) ) {
			self::$catalog = self::read_json( WPS_CONFETTI_ABSPATH . '/styles/catalog.json' );

			// Fill in the pieces every entry is expected to have so callers
			// never need to check whether a key exists.
			foreach ( self::$catalog as $id => $style ) {
				self::$catalog[ $id ] = wp_parse_args(
					$style,
					array(
						'name'     => $id,
						'tier'     => 'premium',
						'icon'     => '🎉',
						'blurb'    => '',
						'options'  => array(),
						'defaults' => array(),
						'fields'   => array(),
					)
				);
				self::$catalog[ $id ]['id'] = $id;
			}

			self::$catalog = apply_filters( 'wps_confetti_styles', self::$catalog );
		}

		return self::$catalog;
	}

	/**
	 * Styles this install is allowed to actually run.
	 *
	 * @return array
	 */
	public static function get_available() {

		$styles = self::get_all();

		if ( WPS_Confetti()->is_premium() ) {
			return $styles;
		}

		return array_filter(
			$styles,
			function ( $style ) {
				return 'free' === $style['tier'];
			}
		);
	}

	/**
	 * Get a single style.
	 *
	 * @param string $id Style ID.
	 * @return array|false
	 */
	public static function get( $id ) {
		$styles = self::get_all();
		return isset( $styles[ $id ] ) ? $styles[ $id ] : false;
	}

	/**
	 * Whether this install can run the given style.
	 *
	 * @param string $id Style ID.
	 * @return bool
	 */
	public static function is_available( $id ) {
		$available = self::get_available();
		return isset( $available[ $id ] );
	}

	/**
	 * The style used when the saved one is missing or not licensed.
	 *
	 * @return string
	 */
	public static function get_fallback() {
		return apply_filters( 'wps_confetti_fallback_style', 'cannon' );
	}

	/**
	 * Read and cache the option definitions.
	 *
	 * @return array
	 */
	public static function get_options() {

		if ( is_null( self::$options ) ) {
			self::$options = self::read_json( WPS_CONFETTI_ABSPATH . '/styles/options.json' );
			self::$options = apply_filters( 'wps_confetti_style_options', self::$options );
		}

		return self::$options;
	}

	/**
	 * The panels the option rows are split across, in the order they appear.
	 *
	 * @return array
	 */
	public static function get_option_groups() {
		return apply_filters(
			'wps_confetti_option_groups',
			array(
				'behavior'   => __( 'Behavior', 'confetti' ),
				'physics'    => __( 'Advanced Physics', 'confetti' ),
				'appearance' => __( 'Appearance', 'confetti' ),
			)
		);
	}

	/**
	 * Join labels into a readable list: "Colors, Shapes, Emojis & Size".
	 *
	 * Labels keep their own capitals. Lowercasing the whole string turns
	 * "Custom SVGs" into "custom svgs", which is just wrong.
	 *
	 * @param array $labels Labels to join.
	 * @return string
	 */
	public static function label_list( $labels ) {

		$labels = array_values( $labels );

		if ( count( $labels ) < 2 ) {
			return implode( '', $labels );
		}

		$last = array_pop( $labels );

		/* translators: 1: all but the last item, comma separated. 2: the last item. */
		return sprintf( __( '%1$s & %2$s', 'confetti' ), implode( ', ', $labels ), $last );
	}

	/**
	 * Every option in one group, in the order defined in options.json.
	 *
	 * @param string $group Group ID.
	 * @return array
	 */
	public static function get_options_in_group( $group ) {

		$options = array();

		foreach ( self::get_options() as $option_id => $option ) {
			$option_group = isset( $option['group'] ) ? $option['group'] : 'behavior';
			if ( $group === $option_group ) {
				$options[ $option_id ] = $option;
			}
		}

		return $options;
	}

	/**
	 * The options a given style offers: everything marked common, plus the
	 * ones the style lists for itself, in the order defined in options.json.
	 *
	 * @param string $style_id Style ID.
	 * @return array
	 */
	public static function get_style_options( $style_id ) {

		$style   = self::get( $style_id );
		$listed  = $style ? $style['options'] : array();
		$options = array();

		foreach ( self::get_options() as $option_id => $option ) {
			if ( ! empty( $option['common'] ) || in_array( $option_id, $listed, true ) ) {
				$options[ $option_id ] = $option;
			}
		}

		return $options;
	}

	/**
	 * Every style ID that offers a given option. The admin uses this to build
	 * the show/hide classes on each option row.
	 *
	 * @param string $option_id Option ID.
	 * @return array
	 */
	public static function get_styles_for_option( $option_id ) {

		$options = self::get_options();

		// Common options apply to everything, which the admin markup already
		// expresses with a single "all" class.
		if ( ! empty( $options[ $option_id ]['common'] ) ) {
			return array( 'all' );
		}

		$style_ids = array();
		foreach ( self::get_all() as $style_id => $style ) {
			if ( in_array( $option_id, $style['options'], true ) ) {
				$style_ids[] = $style_id;
			}
		}

		return $style_ids;
	}

	/**
	 * Where to look for style JavaScript. Premium adds its own directory on
	 * top so its styles are found first.
	 *
	 * @return array Array of arrays with 'path' and 'url' keys.
	 */
	public static function get_script_locations() {
		return apply_filters(
			'wps_confetti_style_script_locations',
			array(
				array(
					'path' => WPS_CONFETTI_ABSPATH . '/assets/js/styles/',
					'url'  => WPS_CONFETTI_PLUGIN_URL . 'assets/js/styles/',
				),
			)
		);
	}

	/**
	 * URL of the JavaScript file that runs a style, if it is installed.
	 *
	 * @param string $id Style ID.
	 * @return string|false
	 */
	public static function get_script_url( $id ) {

		foreach ( self::get_script_locations() as $location ) {
			if ( file_exists( $location['path'] . $id . '.js' ) ) {
				return $location['url'] . $id . '.js';
			}
		}

		return false;
	}

	/**
	 * The catalog trimmed down to what the admin JavaScript needs.
	 *
	 * @return array
	 */
	public static function get_js_catalog() {

		$catalog = array();

		foreach ( self::get_all() as $id => $style ) {
			$catalog[ $id ] = array(
				'name'      => $style['name'],
				'tier'      => $style['tier'],
				'available' => self::is_available( $id ),
				'options'   => array_keys( self::get_style_options( $id ) ),
				'defaults'  => $style['defaults'],
				'fields'    => $style['fields'],
			);
		}

		return $catalog;
	}

	/**
	 * Read a JSON file into an array.
	 *
	 * @param string $file Absolute path to the file.
	 * @return array
	 */
	private static function read_json( $file ) {

		if ( ! file_exists( $file ) ) {
			return array();
		}

		$data = json_decode( file_get_contents( $file ), true );

		if ( ! is_array( $data ) ) {
			return array();
		}

		// Keys starting with an underscore are notes for whoever edits the
		// file, not data.
		foreach ( array_keys( $data ) as $key ) {
			if ( '_' === substr( $key, 0, 1 ) ) {
				unset( $data[ $key ] );
			}
		}

		return $data;
	}

}

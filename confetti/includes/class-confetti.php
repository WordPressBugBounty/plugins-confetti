<?php
/**
 * WPSunshine_Confetti
 *
 * Main Confetti instance class
 *
 * @package WPSConfetti\Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WPSunshine_Confetti class.
 */
class WPSunshine_Confetti {

	/**
	 * Contains an array of cart items.
	 *
	 * @var class|WPSunshine_Confetti
	 */
	protected static $_instance = null;

	/**
	 * User entered options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Available addons.
	 *
	 * @var array
	 */
	private $addons;

	/**
	 * Boolean check if Confetti has already been run so we don't duplicate things.
	 *
	 * @var boolean
	 */
	private $has_run = false;

	/**
	 * Gets the WPSunshine_Confetti instance.
	 *
	 * @return class|WPSunshine_Confetti Instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor to get initial options.
	 */
	public function __construct() {

		$this->options = get_option( 'wps_confetti' );

		$this->includes();
		$this->init_hooks();

	}

	/**
	 * Include needed files.
	 */
	private function includes() {

		include_once WPS_CONFETTI_ABSPATH . '/includes/class-block.php';

		if ( is_admin() ) {
			include_once WPS_CONFETTI_ABSPATH . '/includes/admin/class-options.php';
			include_once WPS_CONFETTI_ABSPATH . '/includes/admin/promos.php';
		} else {
			include_once WPS_CONFETTI_ABSPATH . '/includes/class-shortcode.php';
		}

	}

	/**
	 * Setup init hook.
	 */
	private function init_hooks() {

		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ), 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ), 1 );

	}

	/**
	 * Get enabled addons.
	 */
	public function get_addons() {
		$addons = apply_filters( 'wps_confetti_addons', array() );
		return $addons;
	}

	/**
	 * Register a new addon.
	 */
	public function register_addon( $type, $key, $name ) {
		$this->addons[ $type ][ $key ] = $name;
	}

	/**
	 * Get options.
	 */
	public function get_options( $force = false ) {
		if ( empty( $this->options ) || $force ) {
			$this->options = get_option( 'wps_confetti' );
		}
		return apply_filters( 'wps_confetti_options', $this->options );
	}

	/**
	 * Get an option by key.
	 *
	 * @param string $key Key to get option value for.
	 */
	public function get_option( $key ) {
		if ( ! empty( $this->options[ $key ] ) ) {
			return $this->options[ $key ];
		}
		return false;
	}

	/**
	 * Register the needed JS scripts.
	 */
	public function register_scripts() {
		wp_register_script( 'confetti-core', WPS_CONFETTI_PLUGIN_URL . 'assets/js/confetti-core.js', '', WPS_CONFETTI_VERSION, true );
		wp_register_script( 'confetti', WPS_CONFETTI_PLUGIN_URL . 'assets/js/confetti.js', array( 'jquery', 'confetti-core' ), WPS_CONFETTI_VERSION, true );
		wp_add_inline_script( 'confetti', $this->inline_script() );
	}

	/**
	 * Enqueue scripts with added inline custom scripts.
	 */
	public function enqueue_scripts( $onload = false ) {
		wp_enqueue_script( 'confetti-core' );
		wp_enqueue_script( 'confetti' );
		if ( $onload ) {
			wp_add_inline_script( 'confetti', $this->trigger( false, false ) );
		}
	}

	/**
	 * Generate the inline scripts with the custom options output.
	 */
	public function inline_script( $echo = false ) {

		$options = $this->options;

		$defaults = array(
			'style'                   => ( ! empty( $options['style'] ) ) ? $options['style'] : '',
			'duration'                => ( ! empty( $options['duration'] ) ) ? $options['duration'] : '',
			'delay'                   => ( ! empty( $options['delay'] ) ) ? $options['delay'] : '',
			'speed'                   => ( ! empty( $options['speed'] ) ) ? $options['speed'] : '',
			'particleCount'           => ( ! empty( $options['particleCount'] ) ) ? $options['particleCount'] : '',
			'angle'                   => ( ! empty( $options['angle'] ) ) ? $options['angle'] : '',
			'spread'                  => ( ! empty( $options['spread'] ) ) ? $options['spread'] : '',
			'startVelocity'           => ( ! empty( $options['startVelocity'] ) ) ? $options['startVelocity'] : '',
			'decay'                   => ( ! empty( $options['decay'] ) ) ? $options['decay'] : '',
			'gravity'                 => ( ! empty( $options['gravity'] ) ) ? $options['gravity'] : '',
			'drift'                   => ( ! empty( $options['drift'] ) ) ? $options['drift'] : '',
			'ticks'                   => ( ! empty( $options['ticks'] ) ) ? $options['ticks'] : '',
			'zindex'                  => ( ! empty( $options['zindex'] ) ) ? $options['zindex'] : '',
			'colors'                  => ( ! empty( $options['colors'] ) ) ? $options['colors'] : '',
			'disableForReducedMotion' => ( ! empty( $options['disableForReducedMotion'] ) ) ? $options['disableForReducedMotion'] : '',
		);

		if ( ! empty( $options['origin_x'] ) && ! empty( $options['origin_y'] ) ) {
			$defaults['origin'] = array(
				'x' => $options['origin_x'],
				'y' => $options['origin_y'],
			);
		}

		if ( ! $echo ) {
			ob_start();
		}
		?>

		var wps_confetti_defaults = {
			<?php
			foreach ( $defaults as $key => $option ) {
				if ( empty( $option ) ) {
					continue;
				}
				echo esc_js( $key ) . ': ';
				if ( is_object( $option ) ) {
					echo '{';
					foreach ( $option as $subkey => $suboption ) {
						echo esc_js( $subkey ) . ': ' . esc_js( $suboption ) . ', ';
					}
					echo '}';
				} elseif ( is_array( $option ) ) {
					foreach ( $option as $key => $value ) {
						if ( $value == '' ) {
							unset( $option[ $key ] );
						}
					}
					echo '[';
					echo '"' . join( '", "', array_map( 'esc_js', $option ) ) . '"';
					echo ']';
				} else {
					echo '\'' . esc_js( $option ) . '\'';
				}
				echo ", \r\n";
			}
			?>
		};

		document.addEventListener( "confetti", wps_launch_confetti_cannon );

		function wps_launch_confetti_cannon() {
			wps_run_confetti( wps_confetti_defaults );
		}

		var wps_confetti_click_tracker = document.getElementsByClassName( 'wps-confetti' );
		for ( var i = 0; i < wps_confetti_click_tracker.length; i++ ) {
			wps_confetti_click_tracker[ i ].addEventListener( "click", wps_launch_confetti_cannon );
		}

		<?php

		if ( ! $echo ) {
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}

	}

	/**
	 * Outputs the triggering JS code.
	 */
	public function trigger( $echo = false, $script = true, $onload = true ) {
		$js_safe = '';
		if ( $script ) {
			$js_safe = '<script id="confetti-trigger">';
		}
		if ( $onload ) {
			$js_safe .= "document.addEventListener( 'DOMContentLoaded', function( event ) { ";
		}
		$js_safe .= "document.dispatchEvent( new CustomEvent( 'confetti' ) );";
		if ( $onload ) {
			$js_safe .= ' } );';
		}
		if ( $script ) {
			$js_safe .= '</script>';
		}
		if ( $echo ) {
			echo $js_safe;
		}
		return $js_safe;
	}

	/**
	 * Shortcut function to run the trigger immediately.
	 */
	public function trigger_now( $echo = false, $script = true ) {
		return $this->trigger( $echo, $script, false );
	}

}

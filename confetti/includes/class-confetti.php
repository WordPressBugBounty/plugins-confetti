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
	 * Array of confetti triggers to be rendered in footer.
	 *
	 * @var array
	 */
	private $triggers = array();

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

		$this->maybe_migrate_data();
		$this->options = $this->get_options();

		$this->includes();
		$this->init_hooks();

	}

	/**
	 * Migrate old data structure to new instance-based model.
	 */
	private function maybe_migrate_data() {
		$migrated = get_option( 'wps_confetti_migrated_v2', false );
		if ( $migrated ) {
			return; // Already migrated
		}

		$old_options = get_option( 'wps_confetti', array() );

		// If we have old data, migrate it
		if ( ! empty( $old_options ) && ! isset( $old_options['instances'] ) ) {
			$instances = array(
				'default' => array_merge(
					array(
						'id'   => 'default',
						'name' => __( 'Default', 'confetti' ),
					),
					$old_options
				),
			);

			$new_options = array(
				'instances'       => $instances,
				'active_instance' => 'default',
			);

			update_option( 'wps_confetti', $new_options );
			update_option( 'wps_confetti_migrated_v2', true );
		} elseif ( empty( $old_options ) ) {
			// Fresh install, set up default instance
			$new_options = array(
				'instances'       => array(
					'default' => array(
						'id'    => 'default',
						'name'  => __( 'Default', 'confetti' ),
						'style' => 'cannon',
					),
				),
				'active_instance' => 'default',
			);
			update_option( 'wps_confetti', $new_options );
			update_option( 'wps_confetti_migrated_v2', true );
		}
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
		add_action( 'wp_footer', array( $this, 'render_triggers' ), 9999 );

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
	 * Get all options.
	 */
	public function get_options( $force = false ) {
		if ( empty( $this->options ) || $force ) {
			$this->options = get_option( 'wps_confetti', array() );
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
	 * Get the max number of instances.
	 */
	public function get_max_instances() {
		return apply_filters( 'wps_confetti_max_instances', 1 );
	}

	/**
	 * Get all instances.
	 */
	public function get_instances() {
		$options       = $this->get_options();
		$max_instances = $this->get_max_instances();
		if ( ! empty( $options['instances'] ) && count( $options['instances'] ) >= $max_instances ) {
			// Trim the instances array to the max number of instances
			$options['instances'] = array_slice( $options['instances'], 0, $max_instances );
		}
		return isset( $options['instances'] ) ? $options['instances'] : array();
	}

	/**
	 * Get a specific instance by ID.
	 *
	 * @param string $instance_id Instance ID to retrieve.
	 */
	public function get_instance( $instance_id = 'default' ) {
		$instances = $this->get_instances();
		if ( isset( $instances[ $instance_id ] ) ) {
			return $instances[ $instance_id ];
		}
		// Fall back to default instance
		return isset( $instances['default'] ) ? $instances['default'] : array();
	}

	/**
	 * Get instance settings for a specific instance.
	 *
	 * @param string $instance_id Instance ID.
	 */
	public function get_instance_settings( $instance_id = 'default' ) {
		return $this->get_instance( $instance_id );
	}

	/**
	 * Check if premium version is active.
	 */
	public function is_premium() {
		return apply_filters( 'wps_confetti_premium', false );
	}

	/**
	 * Add a confetti trigger to be rendered in footer.
	 *
	 * @param string $instance_id Instance ID to use.
	 * @param array  $method      Event method details (selector, event, format, etc.).
	 * @param array  $params      Custom parameters to override instance settings.
	 * @param string $id          Unique ID for this trigger (optional).
	 */
	public function add_trigger( $instance_id = 'default', $method = array(), $params = array(), $id = '' ) {

		if ( empty( $id ) ) {
			$id = md5( $instance_id . json_encode( $method ) . json_encode( $params ) . uniqid() );
		}

		$trigger = array(
			'id'          => $id,
			'instance_id' => $instance_id,
			'method'      => $method,
			'params'      => $params,
		);

		$trigger = apply_filters( 'wps_confetti_add_trigger', $trigger );

		$this->triggers[] = $trigger;

		// Enqueue scripts
		$this->enqueue_scripts( false, $instance_id );

	}

	/**
	 * Render a single confetti trigger.
	 *
	 * @param array $trigger Trigger data.
	 * @return string JavaScript code for this trigger.
	 */
	private function render_confetti_trigger( $trigger ) {

		// Get the confetti JavaScript for this instance/params
		$confetti_js = $this->trigger_with_params( $trigger['params'], $trigger['instance_id'] );

		$code = $confetti_js;

		// Wrap in event listener if method is specified
		if ( ! empty( $trigger['method'] ) && is_array( $trigger['method'] ) ) {

			$method = $trigger['method'];

			// Special handling for inview (scroll into view)
			if ( isset( $method['event'] ) && $method['event'] === 'inview' && ! empty( $method['inview_id'] ) ) {
				$unique_id  = esc_js( $method['inview_id'] );
				$js_safe_id = str_replace( '-', '_', $unique_id ); // Make ID safe for JavaScript variable names.
				$code       = '
var wps_confetti_has_run_' . $js_safe_id . ' = false;
function wps_confetti_inview_' . $js_safe_id . '() {
	const wps_confetti_el = document.querySelector( "#wps-confetti-' . $unique_id . '" );
	if ( ! wps_confetti_el ) return;
	const wps_confetti_el_rect = wps_confetti_el.getBoundingClientRect();
	const wps_confetti_viewport_height = (window.innerHeight || document.documentElement.clientHeight);
	const wps_confetti_inview = (
		wps_confetti_el_rect.top < wps_confetti_viewport_height &&
		wps_confetti_el_rect.bottom > 0
	);
	if ( wps_confetti_inview && ! wps_confetti_has_run_' . $js_safe_id . ' ) {
		wps_confetti_has_run_' . $js_safe_id . ' = true;
		' . $confetti_js . '
	}
}
window.addEventListener( "scroll", function() { wps_confetti_inview_' . $js_safe_id . '(); } );
wps_confetti_inview_' . $js_safe_id . '();';
			}
			// Document selector with jQuery format
			elseif ( isset( $method['selector'] ) && $method['selector'] === 'document' ) {
				if ( ! empty( $method['format'] ) && $method['format'] === 'jquery' ) {
					if ( ! empty( $method['sub_selector'] ) ) {
						$code = 'jQuery( document ).on( "' . esc_js( $method['event'] ) . '", "' . esc_js( $method['sub_selector'] ) . '", function( event ) { ' . $code . ' } );';
					} else {
						$code = 'jQuery( document ).on( "' . esc_js( $method['event'] ) . '", function( event ) { ' . $code . ' } );';
					}
				} else {
					$code = 'document.addEventListener( "' . esc_js( $method['event'] ) . '", function( event ) { ' . $code . ' } );';
				}
			}
			// Window selector
			elseif ( isset( $method['selector'] ) && $method['selector'] === 'window' ) {
				if ( ! empty( $method['format'] ) && $method['format'] === 'jquery' ) {
					$code = 'jQuery( window ).on( "' . esc_js( $method['event'] ) . '", function( event ) { ' . $code . ' } );';
				} else {
					$code = 'window.addEventListener( "' . esc_js( $method['event'] ) . '", function( event ) { ' . $code . ' } );';
				}
			}
			// Specific selector
			elseif ( ! empty( $method['selector'] ) && ! empty( $method['event'] ) ) {
				if ( ! empty( $method['format'] ) && $method['format'] === 'jquery' ) {
					$code = 'jQuery( "' . esc_js( $method['selector'] ) . '" ).on( "' . esc_js( $method['event'] ) . '", function( event ) { ' . $code . ' } );';
				} else {
					$code = 'if ( document.querySelector( "' . esc_js( $method['selector'] ) . '" ) ) { document.querySelector( "' . esc_js( $method['selector'] ) . '" ).addEventListener( "' . esc_js( $method['event'] ) . '", function( event ) { ' . $code . ' } ); }';
				}
			}

			// Add delay if specified
			if ( ! empty( $method['delay'] ) ) {
				$delay = intval( $method['delay'] );
				$code  = 'setTimeout(function(){ ' . $code . ' }, ' . $delay . ');';
			}
		}

		$code = apply_filters( 'wps_confetti_render_trigger', $code, $trigger );

		return $code;

	}

	/**
	 * Render all confetti triggers in footer.
	 */
	public function render_triggers() {

		if ( empty( $this->triggers ) ) {
			return;
		}

		$output = '';
		foreach ( $this->triggers as $key => $trigger ) {
			$output .= $this->render_confetti_trigger( $trigger );
			$output .= "\n";
			unset( $this->triggers[ $key ] ); // Unset so we don't repeat it
		}

		if ( ! empty( $output ) ) {
			echo "\n" . '<script id="wps-confetti">' . "\n" . $output . '</script>' . "\n";
		}

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
	 *
	 * @param bool   $onload      Whether to trigger on load.
	 * @param string $instance_id Instance ID to use.
	 */
	public function enqueue_scripts( $onload = false, $instance_id = 'default' ) {
		// Add timestamp to the script URL to prevent caching
		$timestamp = time();
		wp_enqueue_script( 'confetti-core', WPS_CONFETTI_PLUGIN_URL . 'assets/js/confetti-core.js?' . $timestamp, '', WPS_CONFETTI_VERSION, true );
		wp_enqueue_script( 'confetti', WPS_CONFETTI_PLUGIN_URL . 'assets/js/confetti.js?' . $timestamp, array( 'jquery', 'confetti-core' ), WPS_CONFETTI_VERSION, true );
		if ( $onload ) {
			wp_add_inline_script( 'confetti', $this->trigger( false, false, true, $instance_id ) );
		}
	}

	/**
	 * Generate the inline scripts with the custom options output.
	 *
	 * @param bool   $echo        Whether to echo or return.
	 * @param string $instance_id Instance ID to use for settings.
	 * @param array  $overrides   Array of option overrides.
	 */
	public function inline_script( $echo = false, $instance_id = 'default', $overrides = array() ) {

		// Get instance settings
		$instance_settings = $this->get_instance( $instance_id );

		// Merge with any overrides
		$options = array_merge( $instance_settings, $overrides );

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
			'scalar'                  => ( ! empty( $options['scalar'] ) ) ? $options['scalar'] : '',
			'zindex'                  => ( ! empty( $options['zindex'] ) ) ? $options['zindex'] : '',
			'colors'                  => ( ! empty( $options['colors'] ) ) ? $options['colors'] : '',
			'svgs'                    => ( ! empty( $options['svgs'] ) ) ? $options['svgs'] : '',
			'emojis'                  => ( ! empty( $options['emojis'] ) ) ? $options['emojis'] : '',
			'disableForReducedMotion' => 1, // Always disable for reduced motion.
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

				// Special handling for SVGs and emojis (complex arrays/objects)
				if ( $key === 'svgs' || $key === 'emojis' ) {
					echo wp_json_encode( $option );
				} elseif ( is_object( $option ) ) {
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
	 *
	 * @param bool   $echo        Whether to echo or return.
	 * @param bool   $script      Whether to wrap in script tags.
	 * @param bool   $onload      Whether to wait for DOMContentLoaded.
	 * @param string $instance_id Instance ID to use (not used in trigger, but kept for compatibility).
	 */
	public function trigger( $echo = false, $script = true, $onload = true, $instance_id = 'default' ) {
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
	 *
	 * @param bool   $echo        Whether to echo or return.
	 * @param bool   $script      Whether to wrap in script tags.
	 * @param string $instance_id Instance ID to use.
	 */
	public function trigger_now( $echo = false, $script = true, $instance_id = 'default' ) {
		return $this->trigger( $echo, $script, false, $instance_id );
	}

	/**
	 * Generate confetti trigger with custom parameters.
	 *
	 * @param array  $params      Custom parameters to override instance settings.
	 * @param string $instance_id Instance ID to use as base.
	 */
	public function trigger_with_params( $params = array(), $instance_id = 'default' ) {
		// Get instance settings
		$instance_settings = $this->get_instance( $instance_id );

		// Merge with custom parameters
		$merged_settings = array_merge( $instance_settings, $params );

		// Generate the confetti options
		$confetti_options = $this->build_confetti_options( $merged_settings );

		// Output JavaScript
		$js = 'wps_run_confetti(' . wp_json_encode( $confetti_options ) . ');';
		return $js;
	}

	/**
	 * Build confetti options array from settings.
	 *
	 * @param array $settings Settings array.
	 */
	private function build_confetti_options( $settings ) {
		$options = array();

		$allowed_keys = array(
			'style',
			'duration',
			'delay',
			'speed',
			'particleCount',
			'angle',
			'spread',
			'startVelocity',
			'decay',
			'gravity',
			'drift',
			'ticks',
			'scalar',
			'zindex',
			'colors',
			'disableForReducedMotion',
		);

		foreach ( $allowed_keys as $key ) {
			if ( ! empty( $settings[ $key ] ) ) {
				$options[ $key ] = $settings[ $key ];
			}
		}

		if ( ! empty( $settings['origin_x'] ) && ! empty( $settings['origin_y'] ) ) {
			$options['origin'] = array(
				'x' => $settings['origin_x'],
				'y' => $settings['origin_y'],
			);
		}

		return $options;
	}

}

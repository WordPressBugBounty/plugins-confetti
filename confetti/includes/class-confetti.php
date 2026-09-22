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
	 * Boolean check if Confetti has already been enqueued so we don't duplicate things.
	 *
	 * @var boolean
	 */
	private $enqueued = false;

	/**
	 * Array of confetti triggers to be rendered in footer.
	 *
	 * @var array
	 */
	private $triggers = array();

	/**
	 * Style IDs this page needs the JavaScript for.
	 *
	 * @var array
	 */
	private $required_styles = array();

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

		include_once WPS_CONFETTI_ABSPATH . '/includes/class-styles.php';
		include_once WPS_CONFETTI_ABSPATH . '/includes/class-block.php';
		include_once WPS_CONFETTI_ABSPATH . '/includes/class-telemetry.php'; // Not admin-only: its weekly event runs on the front end.

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
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_for_content' ) );
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
	 * The names of every plugin Confetti can integrate with.
	 *
	 * Premium reads the integrations it actually registered. Free ships none of
	 * that code, so it reads the list from integrations.json instead. Either
	 * way nothing has to hardcode a count.
	 *
	 * @return array
	 */
	public function get_integration_names() {

		$addons = $this->get_addons();

		if ( ! empty( $addons ) ) {

			// One plugin can register more than one addon: Ninja Forms has a
			// second entry for its action. Those are the same plugin, so the
			// count has to fold them together or it overstates what you get.
			// A name in the form "Thing (Variant)" is the same plugin as
			// "Thing" whenever plain "Thing" is registered too.
			$names = array();
			$all   = wp_list_pluck( $addons, 'name' );

			foreach ( $all as $name ) {
				$base = trim( preg_replace( '/\s*\(.*$/', '', $name ) );
				if ( $base !== $name && in_array( $base, $all, true ) ) {
					continue;
				}
				$names[] = $name;
			}

			$names = array_values( array_unique( $names ) );
			sort( $names );

			return $names;
		}

		$file = WPS_CONFETTI_ABSPATH . '/integrations.json';

		if ( ! file_exists( $file ) ) {
			return array();
		}

		$data = json_decode( file_get_contents( $file ), true );

		return isset( $data['integrations'] ) ? $data['integrations'] : array();
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

		$instances = isset( $options['instances'] ) ? $options['instances'] : array();

		return $instances;
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
	 * Check if premium version is active.
	 */
	public function is_premium() {
		return apply_filters( 'wps_confetti_premium', false );
	}

	/**
	 * Register the needed JS scripts.
	 */
	public function register_scripts() {
		// Add timestamp to the script URL to prevent caching
		$timestamp = time();
		wp_register_script( 'confetti-core', WPS_CONFETTI_PLUGIN_URL . 'assets/js/confetti-core.js?' . $timestamp, '', WPS_CONFETTI_VERSION, true );
		wp_register_script( 'confetti', WPS_CONFETTI_PLUGIN_URL . 'assets/js/confetti.js?' . $timestamp, array( 'jquery', 'confetti-core' ), WPS_CONFETTI_VERSION, true );

		// One script per style, so a page only downloads the ones it uses.
		foreach ( WPSunshine_Confetti_Styles::get_available() as $style_id => $style ) {
			$url = WPSunshine_Confetti_Styles::get_script_url( $style_id );
			if ( $url ) {
				wp_register_script( 'confetti-style-' . $style_id, $url . '?' . $timestamp, array( 'confetti' ), WPS_CONFETTI_VERSION, true );
			}
		}
	}

	/**
	 * Note that this page needs a style, and load it if the main scripts have
	 * already gone out.
	 *
	 * Styles that are not installed or not licensed fall back to the default
	 * one, so a page never ends up asking for confetti that cannot run.
	 *
	 * @param string $style_id Style ID.
	 * @return string The style that will actually run.
	 */
	public function require_style( $style_id = '' ) {

		if ( empty( $style_id ) || ! WPSunshine_Confetti_Styles::is_available( $style_id ) ) {
			$style_id = WPSunshine_Confetti_Styles::get_fallback();
		}

		if ( ! in_array( $style_id, $this->required_styles, true ) ) {
			$this->required_styles[] = $style_id;
		}

		if ( $this->enqueued ) {
			wp_enqueue_script( 'confetti-style-' . $style_id );
		}

		return $style_id;
	}

	/**
	 * Load every style this install can run. Used by the settings page and the
	 * block editor, where any style might be previewed.
	 */
	public function require_all_styles() {
		foreach ( array_keys( WPSunshine_Confetti_Styles::get_available() ) as $style_id ) {
			$this->require_style( $style_id );
		}
	}

	/**
	 * The style an instance will run with.
	 *
	 * @param string $instance_id Instance ID.
	 * @return string
	 */
	public function get_instance_style( $instance_id = 'default' ) {
		$instance = $this->get_instance( $instance_id );
		return ! empty( $instance['style'] ) ? $instance['style'] : WPSunshine_Confetti_Styles::get_fallback();
	}

	/**
	 * Instances prepared for JavaScript: the style checked against what this
	 * install can run, and any gaps filled in from the style's own defaults.
	 *
	 * @return array
	 */
	public function get_instances_for_js() {

		$instances = $this->get_instances();

		foreach ( $instances as $instance_id => $instance ) {

			$style_id = ! empty( $instance['style'] ) ? $instance['style'] : WPSunshine_Confetti_Styles::get_fallback();

			if ( ! WPSunshine_Confetti_Styles::is_available( $style_id ) ) {
				$style_id = WPSunshine_Confetti_Styles::get_fallback();
			}

			$instance['style'] = $style_id;
			$style             = WPSunshine_Confetti_Styles::get( $style_id );

			if ( $style ) {
				foreach ( $style['defaults'] as $key => $value ) {
					if ( ! isset( $instance[ $key ] ) || '' === $instance[ $key ] ) {
						$instance[ $key ] = $value;
					}
				}
				foreach ( $style['fields'] as $field_id => $field ) {
					if ( empty( $instance[ $field_id ] ) && isset( $field['default'] ) ) {
						$instance[ $field_id ] = $field['default'];
					}
				}
			}

			// The setting is saved lowercase but canvas-confetti reads zIndex.
			//
			// 100 was the old default, and it came from canvas-confetti rather
			// than from any deliberate choice - every instance was saved with
			// it. It is far below what popup plugins use (MailPoet, Popup
			// Builder and Hustle all sit in the hundreds of millions), so
			// confetti fired behind the very popup it was celebrating. Treat
			// that legacy value as unset so those instances pick up the new
			// default; anything else the user has actually chosen is kept.
			if ( isset( $instance['zindex'] ) && '' !== $instance['zindex'] && '100' !== (string) $instance['zindex'] ) {
				$instance['zIndex'] = $instance['zindex'];
			}

			$instances[ $instance_id ] = $instance;
		}

		return apply_filters( 'wps_confetti_instances_for_js', $instances );
	}

	/**
	 * Enqueue scripts with added inline custom scripts.
	 *
	 * @param bool   $trigger     Whether to trigger on page load.
	 * @param string $instance_id Instance ID to use when triggering.
	 */
	public function enqueue_scripts( $trigger = false, $instance_id = '' ) {

		// Another plugin can ask for confetti before our own registration hook
		// has run - Gravity Forms does exactly that. Localizing to a handle
		// that is not registered yet silently throws the data away, which
		// leaves every trigger on the page calling confetti_instances before
		// it exists. Registering on demand makes that impossible.
		if ( ! wp_script_is( 'confetti', 'registered' ) ) {
			$this->register_scripts();
		}

		if ( ! $this->enqueued ) {
			$this->enqueued = true;
			wp_enqueue_script( 'confetti-core' );
			wp_enqueue_script( 'confetti' );

			// Localize instances for JavaScript
			wp_localize_script( 'confetti', 'confetti_instances', $this->get_instances_for_js() );
			wp_localize_script( 'confetti', 'confetti_style_defaults', $this->get_style_defaults_for_js() );

			// Anything asked for before now still needs loading.
			foreach ( $this->required_styles as $style_id ) {
				wp_enqueue_script( 'confetti-style-' . $style_id );
			}
		}

		if ( $trigger && ! empty( $instance_id ) ) {
			$this->add_trigger( $instance_id, 'load' );
		}
	}

	/**
	 * Each available style's own defaults, so JavaScript can swap a style into
	 * an instance and still have everything that style expects to be set.
	 * Only styles this install can run are included.
	 *
	 * @return array
	 */
	public function get_style_defaults_for_js() {

		$defaults = array();

		foreach ( WPSunshine_Confetti_Styles::get_available() as $style_id => $style ) {
			$defaults[ $style_id ] = $style['defaults'];
			foreach ( $style['fields'] as $field_id => $field ) {
				if ( isset( $field['default'] ) && ! isset( $defaults[ $style_id ][ $field_id ] ) ) {
					$defaults[ $style_id ][ $field_id ] = $field['default'];
				}
			}
		}

		return $defaults;
	}

	/**
	 * Load the scripts when the page content fires confetti from a class or
	 * data attribute (wps-confetti-style-{style}, wps-confetti-instance-{id},
	 * data-wps-confetti-style, data-wps-confetti-instance, or the plain
	 * wps-confetti class). Those never go through add_trigger, so nothing else
	 * would load the scripts or the styles they name.
	 */
	public function maybe_enqueue_for_content() {

		if ( ! is_singular() ) {
			return;
		}

		$post = get_post();
		if ( ! $post || false === strpos( $post->post_content, 'wps-confetti' ) ) {
			return;
		}

		$content = $post->post_content;
		$found   = false;

		// Styles named directly.
		if ( preg_match_all( '/(?:wps-confetti-style-|data-wps-confetti-style=["\'])([a-z0-9_]+)/i', $content, $matches ) ) {
			foreach ( array_unique( $matches[1] ) as $style_id ) {
				$this->require_style( $style_id );
			}
			$found = true;
		}

		// Instances, which need their own style loaded.
		if ( preg_match_all( '/(?:wps-confetti-instance-|data-wps-confetti-instance=["\'])([a-z0-9_-]+)/i', $content, $matches ) ) {
			foreach ( array_unique( $matches[1] ) as $instance_id ) {
				$this->require_style( $this->get_instance_style( $instance_id ) );
			}
			$found = true;
		}

		// The plain legacy class, or a style with no instance named: both run
		// on top of the default instance.
		if ( preg_match( '/wps-confetti["\'\s]/', $content ) || $found ) {
			$this->require_style( $this->get_instance_style( 'default' ) );
			$found = true;
		}

		if ( $found ) {
			$this->enqueue_scripts();
		}
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
			$id = md5( $instance_id . json_encode( $method ) . json_encode( $params ) );
		}

		$trigger = array(
			'id'          => $id,
			'instance_id' => $instance_id,
			'method'      => $method,
			'params'      => $params,
		);

		$trigger = apply_filters( 'wps_confetti_add_trigger', $trigger );

		$this->triggers[] = $trigger;

		$this->require_trigger_style( $trigger );

		$this->enqueue_scripts();

	}

	/**
	 * Render a single confetti trigger.
	 *
	 * @param array $trigger Trigger data.
	 * @return string JavaScript code for this trigger.
	 */
	public function render_trigger( $trigger, $script = false ) {

		// Triggers rendered straight from a shortcode or block never went
		// through add_trigger, so claim the style and load the scripts here
		// too. This is what keeps confetti off pages that have no trigger.
		$this->require_trigger_style( $trigger );
		$this->enqueue_scripts();

		// Get the confetti JavaScript for this instance/params
		$params = isset( $trigger['params'] ) ? $trigger['params'] : array();
		$code   = $this->get_instance_script( $trigger['instance_id'], $params );

		// Wrap in event listener if method is specified
		if ( ! empty( $trigger['method'] ) && is_array( $trigger['method'] ) ) {

			$method = $trigger['method'];

			// Special handling for inview (scroll into view)
			if ( isset( $method['event'] ) && $method['event'] === 'inview' ) {
				// Use custom inview_id if provided, otherwise fall back to instance_id
				$inview_element_id = isset( $method['inview_id'] ) ? $method['inview_id'] : 'wps-confetti-' . $trigger['instance_id'];
				$element_id_safe   = esc_js( $inview_element_id );
				$instance_id_safe  = esc_js( $trigger['instance_id'] );
				$code              = 'wps_confetti_inview_setup( "' . $element_id_safe . '", "' . $instance_id_safe . '" );';
				$code              = 'window.addEventListener( "load", function() { ' . $code . ' } ); ';
			}
			// Document selector with jQuery format.
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
			// Window selector.
			elseif ( isset( $method['selector'] ) && $method['selector'] === 'window' ) {
				if ( ! empty( $method['format'] ) && $method['format'] === 'jquery' ) {
					$code = 'jQuery( window ).on( "' . esc_js( $method['event'] ) . '", function( event ) { ' . $code . ' } );';
				} else {
					$code = 'window.addEventListener( "' . esc_js( $method['event'] ) . '", function( event ) { ' . $code . ' } );';
				}
			}
			// Specific selector.
			elseif ( ! empty( $method['selector'] ) && ! empty( $method['event'] ) ) {
				if ( ! empty( $method['format'] ) && $method['format'] === 'jquery' ) {
					$code = 'jQuery( "' . esc_js( $method['selector'] ) . '" ).on( "' . esc_js( $method['event'] ) . '", function( event ) { ' . $code . ' } );';
				} else {
					$code = 'if ( document.querySelector( "' . esc_js( $method['selector'] ) . '" ) ) { document.querySelector( "' . esc_js( $method['selector'] ) . '" ).addEventListener( "' . esc_js( $method['event'] ) . '", function( event ) { ' . $code . ' } ); }';
				}
			}
		}
		// Load event.
		elseif ( ! empty( $trigger['method'] ) && $trigger['method'] === 'load' ) {
			$code = 'window.addEventListener( "load", function() { ' . $code . ' } ); ';
		}

		if ( $script ) {
			$code = '<script id="wps-confetti-trigger-' . esc_attr( $trigger['id'] ) . '">' . $code . '</script>';
		}

		return $code;

	}

	/**
	 * Markup for dropping confetti onto a page.
	 *
	 * This is what the block and every page-builder module render: pick an
	 * instance, choose whether it fires on load or when scrolled to. Keeping it
	 * here means Elementor, Divi, Beaver Builder, Bricks and the block all
	 * behave identically instead of each carrying its own copy.
	 *
	 * @param string $instance_id Instance to run.
	 * @param string $method      'load' or 'inview'.
	 * @param string $unique_id   Optional unique ID for this placement.
	 * @return string
	 */
	public function render_placement( $instance_id = 'default', $method = 'load', $unique_id = '' ) {

		if ( empty( $instance_id ) ) {
			$instance_id = 'default';
		}

		if ( empty( $unique_id ) ) {
			$unique_id = 'wps-confetti-' . wp_rand( 1000, 9999 );
		}

		if ( 'inview' === $method ) {

			// Needs an element on the page to watch for.
			$js_safe_id = esc_js( str_replace( '-', '_', $unique_id ) );
			$output     = '<div id="wps-confetti-' . esc_attr( $js_safe_id ) . '" style="height: 1px; width: 100%;"></div>';

			$trigger = array(
				'id'          => $unique_id,
				'instance_id' => $instance_id,
				'method'      => array(
					'event'     => 'inview',
					'inview_id' => 'wps-confetti-' . $js_safe_id,
				),
				'params'      => array(),
			);

			return $output . $this->render_trigger( $trigger, true );
		}

		$trigger = array(
			'id'          => $unique_id,
			'instance_id' => $instance_id,
			'method'      => 'load',
			'params'      => array(),
		);

		return $this->render_trigger( $trigger, true );
	}

	/**
	 * The instances as a plain id => name list, for building a dropdown.
	 *
	 * @return array
	 */
	public function get_instance_choices() {

		$choices = array();

		foreach ( $this->get_instances() as $instance_id => $instance ) {
			$choices[ $instance_id ] = ! empty( $instance['name'] ) ? $instance['name'] : $instance_id;
		}

		return $choices;
	}

	/**
	 * Work out which style a trigger will run and make sure it is loaded.
	 * A style named directly in the trigger params wins over the instance.
	 *
	 * @param array $trigger Trigger data.
	 */
	private function require_trigger_style( $trigger ) {

		if ( ! empty( $trigger['params']['style'] ) ) {
			$this->require_style( $trigger['params']['style'] );
			return;
		}

		$instance_id = ! empty( $trigger['instance_id'] ) ? $trigger['instance_id'] : 'default';
		$this->require_style( $this->get_instance_style( $instance_id ) );
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
			$output .= $this->render_trigger( $trigger );
			$output .= "\n";
			unset( $this->triggers[ $key ] ); // Unset so we don't repeat it
		}

		if ( ! empty( $output ) ) {
			?>
			<script id="wps-confetti-triggers">
			<?php echo $output; ?>
			</script>
			<?php
		}

	}

		/**
		 * Generate the inline scripts with the custom options output.
		 *
		 * @param string $instance_id Instance ID to use for settings.
		 * @param array  $params     Array of parameter overrides (e.g., origin_selector).
		 */
	public function get_instance_script( $instance_id = 'default', $params = array() ) {

		// Get instance settings
		ob_start();
		?>
		(function() {
			var base_settings = confetti_instances['<?php echo esc_attr( $instance_id ); ?>'];
			<?php if ( ! empty( $params ) ) : ?>
				// Merge params overrides with instance settings, params wins
				var params = <?php echo wp_json_encode( $params ); ?>;
				var instance_settings = Object.assign( {}, base_settings );
				Object.assign( instance_settings, params );
			<?php else : ?>
				var instance_settings = base_settings;
			<?php endif; ?>
			wps_run_confetti( instance_settings );
		})();
		<?php
		$content = ob_get_contents();
		ob_end_clean();

		return $content;

	}

}

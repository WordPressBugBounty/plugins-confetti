<?php
/**
 * WPSunshine_Confetti_Options
 *
 * @package WPSConfetti\Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WPSunshine_Confetti_Options class.
 */
class WPSunshine_Confetti_Options {

	/**
	 * Array of notices based on user interactions.
	 *
	 * @var array
	 */
	protected static $notices = array();

	/**
	 * Array of errors based on user interactions.
	 *
	 * @var array
	 */
	protected static $errors = array();

	/**
	 * Plugin settings main navigation tabs.
	 *
	 * @var array
	 */
	private $tabs;

	/**
	 * Current settings navigation active tab.
	 *
	 * @var array
	 */
	private $tab;

	private $max_instances = 1;
	/**
	 * Constructor setup all needed hooks.
	 */
	public function __construct() {

		// Add settings page.
		add_action( 'admin_menu', array( $this, 'options_page_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wps_confetti_header_links', array( $this, 'header_links' ) );

		// Tabs.
		add_action( 'admin_init', array( $this, 'set_tabs' ) );

		// Show settings.
		add_action( 'wps_confetti_options_tab_instances', array( $this, 'instances_tab' ) );
		add_action( 'wps_confetti_instance_sidebar', array( $this, 'sidebar_preview' ), 10, 2 );
		add_action( 'wps_confetti_options_tab_integrations_promo', array( $this, 'integrations_promo_tab' ) );
		add_action( 'wps_confetti_options_tab_usage', array( $this, 'usage_tab' ) );

		// Save settings.
		add_action( 'admin_init', array( $this, 'save_options' ) );
		add_action( 'wps_confetti_save_tab_instances', array( $this, 'save_instances_tab' ), 1, 2 );
		add_action( 'admin_notices', array( $this, 'show_notices' ) );

	}

	/**
	 * Create Settings page menu.
	 */
	public function options_page_menu() {
		add_options_page( __( 'Confetti', 'confetti' ), __( 'Confetti', 'confetti' ), 'manage_options', 'wps_confetti', array( $this, 'options_page' ) );
	}

	/**
	 * Enqueue scripts for admin.
	 */
	public function admin_enqueue_scripts() {

		if ( isset( $_GET['page'] ) && 'wps_confetti' == $_GET['page'] ) {
			WPS_Confetti()->require_all_styles();
			WPS_Confetti()->enqueue_scripts();
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'confetti-admin', WPS_CONFETTI_PLUGIN_URL . 'assets/css/admin.css', false, WPS_CONFETTI_VERSION );

			// Enqueue Thickbox for promo popup
			wp_enqueue_script( 'thickbox', null, array( 'jquery' ) );
			wp_enqueue_style( 'thickbox' );

			// Add inline script for admin functionality
			wp_add_inline_script( 'jquery', $this->get_admin_script() );
		}

	}

	/**
	 * Get admin JavaScript for the instances tab.
	 *
	 * The sample payload for each style comes from the style catalog, so a new
	 * style previews correctly without anything being added here.
	 */
	private function get_admin_script() {

		$catalog = wp_json_encode( WPSunshine_Confetti_Styles::get_js_catalog() );

		// The premium version binds its own preview handler, which reads every
		// option off the form. Free has nothing to read, so it previews the
		// chosen style on its own.
		$bind_preview = $this->is_premium() ? 'false' : 'true';

		$preview_label = wp_json_encode( __( 'Preview %s', 'confetti' ) );
		$copied_label  = wp_json_encode( __( 'Copied!', 'confetti' ) );

		return "
		var wps_confetti_catalog = {$catalog};

		jQuery( document ).ready(function($) {

			// Fire one style with nothing but its own defaults.
			function wps_confetti_sample( style_id ) {

				var style = wps_confetti_catalog[ style_id ];

				if ( ! style || ! style.available ) {
					return;
				}

				var sample = jQuery.extend( { style: style_id }, style.defaults || {} );

				jQuery.each( style.fields || {}, function( field_id, field ){
					sample[ field_id ] = field['default'];
				});

				if ( typeof WPSConfetti !== 'undefined' ) {
					WPSConfetti.reset();
				}

				wps_run_confetti( sample );
			}

			// Preview a style from its card.
			$( document ).on( 'click', '.wps-confetti-sample', function(){
				wps_confetti_sample( $( this ).data( 'style' ) );
				return false;
			});

			if ( {$bind_preview} ) {
				$( document ).on( 'click', '#wps-confetti-preview', function(){
					wps_confetti_sample( $( 'input[name=\"style\"]:checked' ).val() );
					return false;
				});
			}

			// Show only the options the chosen style actually uses, and keep
			// the card and the preview button in step with it.
			function wps_confetti_sync_options() {

				var style_id = $( 'input[name=\"style\"]:checked' ).val();
				var style    = wps_confetti_catalog[ style_id ] || {};

				$( '#wps-confetti-display .wps-option-row' ).each(function(){
					var applies = $( this ).hasClass( 'all' ) || $( this ).hasClass( style_id );
					$( this ).toggleClass( 'is-other-style', ! applies ).toggle( applies );
				});

				// Hide a section only when this style has nothing to put in it.
				// This has to go by the style class rather than :visible: these
				// sections start collapsed, so their rows are never visible and
				// they would hide themselves the moment the page loaded.
				$( '.wps-panel--collapsible' ).each(function(){
					var rows = $( this ).find( '.wps-option-row' );
					$( this ).toggle( rows.length === 0 || rows.not( '.is-other-style' ).length > 0 );
				});

				$( '.wps-style-card' ).removeClass( 'is-selected' );
				$( 'input[name=\"style\"]:checked' ).closest( '.wps-style-card' ).addClass( 'is-selected' );

				if ( style.name ) {
					$( '#wps-confetti-preview' ).text( {$preview_label}.replace( '%s', style.name ) );
				}
			}

			$( document ).on( 'change', 'input[name=\"style\"]', wps_confetti_sync_options );
			wps_confetti_sync_options();

			// Keep each slider and its number box showing the same value.
			$( document ).on( 'input', '.wps-slider input[type=\"range\"]', function(){
				$( this ).siblings( '.wps-slider__value' ).val( $( this ).val() );
			});
			$( document ).on( 'input', '.wps-slider__value', function(){
				$( this ).siblings( 'input[type=\"range\"]' ).val( $( this ).val() );
			});

			// Behavior and Advanced Physics open and close.
			$( document ).on( 'click', '.wps-panel__toggle', function(){
				var panel = $( this ).closest( '.wps-panel' );
				var open  = ! panel.hasClass( 'is-open' );
				panel.toggleClass( 'is-open', open );
				$( this ).attr( 'aria-expanded', open ? 'true' : 'false' );
				panel.children( '.wps-panel__body' ).slideToggle( 150 );
				return false;
			});

			// The instance menu.
			$( document ).on( 'click', '.wps-instance-menu-toggle', function(e){
				e.preventDefault();
				e.stopPropagation();
				var menu = $( this ).siblings( '.wps-instance-menu' );
				$( '.wps-instance-menu' ).not( menu ).hide();
				menu.toggle();
			});

			$( document ).on( 'click', function(){
				$( '.wps-instance-menu' ).hide();
			});

			// Rename shows the name field rather than sending you elsewhere.
			$( document ).on( 'click', '.wps-instance-rename-link', function(e){
				e.preventDefault();
				$( '.wps-instance-menu' ).hide();
				$( '#wps-instance-rename' ).show().find( 'input' ).trigger( 'focus' ).trigger( 'select' );
			});

			$( document ).on( 'click', '.wps-copy-instance-shortcode', function(e){
				e.preventDefault();

				var link = $( this );
				var text = link.data( 'shortcode' );
				var done = function() {
					var original = link.text();
					link.text( {$copied_label} );
					setTimeout(function(){
						link.text( original );
						$( '.wps-instance-menu' ).hide();
					}, 1500 );
				};

				if ( navigator.clipboard && window.isSecureContext ) {
					navigator.clipboard.writeText( text ).then( done );
				} else {
					var temp = $( '<textarea>' ).val( text ).appendTo( 'body' ).select();
					document.execCommand( 'copy' );
					temp.remove();
					done();
				}
			});

		});
		";
	}

	/**
	 * Open a settings panel: the white box with a heading.
	 *
	 * @param string $id    Panel ID, used for the element ID.
	 * @param string $title Panel heading.
	 * @param array  $args  meta: small grey text beside the heading. badge: text
	 *                      for a premium pill. locked: dim the whole body. head:
	 *                      extra markup for the right of the heading row.
	 */
	public static function panel_open( $id, $title, $args = array() ) {

		$args = wp_parse_args(
			$args,
			array(
				'badge'       => '',
				'locked'      => false,
				'head'        => '',
				'collapsible' => false,
				'open'        => true,
			)
		);

		$open = ! $args['collapsible'] || $args['open'];

		$classes = 'wps-panel';
		if ( $args['locked'] ) {
			$classes .= ' is-locked';
		}
		if ( $args['collapsible'] ) {
			$classes .= ' wps-panel--collapsible';
		}
		if ( $args['collapsible'] && $open ) {
			$classes .= ' is-open';
		}

		// A collapsible head is the control that opens the panel, so it has to
		// be a real button rather than a div with a click handler on it.
		$head_tag  = $args['collapsible'] ? 'button' : 'div';
		$head_attr = $args['collapsible']
			? ' type="button" class="wps-panel__head wps-panel__toggle" aria-expanded="' . ( $open ? 'true' : 'false' ) . '"'
			: ' class="wps-panel__head"';
		?>
		<section class="<?php echo esc_attr( $classes ); ?>" id="wps-panel-<?php echo esc_attr( $id ); ?>">
			<<?php echo $head_tag . $head_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php if ( $args['collapsible'] ) : ?>
					<span class="wps-panel__arrow" aria-hidden="true"></span>
				<?php endif; ?>
				<h2 class="wps-panel__title"><?php echo esc_html( $title ); ?></h2>
				<?php if ( $args['badge'] ) : ?>
					<span class="wps-badge"><?php echo esc_html( $args['badge'] ); ?></span>
				<?php endif; ?>
				<?php echo $args['head']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</<?php echo $head_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="wps-panel__body"<?php echo $open ? '' : ' style="display:none;"'; ?>>
		<?php
	}

	/**
	 * Close a settings panel.
	 */
	public static function panel_close() {
		echo '</div></section>';
	}

	/**
	 * Open one option row. The classes are the styles that offer the option,
	 * which is what the show/hide script matches against.
	 *
	 * @param string $label   Row label.
	 * @param array  $styles  Style IDs this row belongs to, or array( 'all' ).
	 */
	public static function option_row_open( $label, $styles ) {
		?>
		<div class="wps-option-row <?php echo esc_attr( implode( ' ', $styles ) ); ?>">
			<div class="wps-option-row__label"><?php echo esc_html( $label ); ?></div>
			<div class="wps-option-row__control">
		<?php
	}

	/**
	 * Close one option row.
	 *
	 * @param string $description Help text shown under the control.
	 */
	public static function option_row_close( $description = '' ) {
		if ( $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		echo '</div></div>';
	}

	/**
	 * A number option: a slider and a number box that track each other.
	 *
	 * Both the real premium control and the free version's locked preview use
	 * this, so they can never look like two different things.
	 *
	 * @param string $option_id Option ID, used as the field name.
	 * @param array  $option    Option definition from options.json.
	 * @param mixed  $value     Current value.
	 * @param bool   $readonly  Whether the visitor may change it.
	 */
	public static function render_number_option( $option_id, $option, $value, $readonly = false ) {

		$default   = isset( $option['default'] ) ? $option['default'] : '';
		$has_range = ( ! isset( $option['slider'] ) || $option['slider'] ) && isset( $option['min'] ) && isset( $option['max'] );

		$attributes = '';
		foreach ( array( 'min', 'max', 'step' ) as $attribute ) {
			if ( isset( $option[ $attribute ] ) ) {
				$attributes .= ' ' . $attribute . '="' . esc_attr( $option[ $attribute ] ) . '"';
			}
		}

		echo '<div class="wps-slider' . ( $has_range ? '' : ' wps-slider--no-range' ) . '">';

		if ( $has_range ) {
			?>
			<input type="range"<?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> value="<?php echo esc_attr( $value ); ?>" <?php disabled( $readonly ); ?> tabindex="-1" aria-hidden="true" />
			<?php
		}
		?>
		<input
			class="wps-slider__value"
			name="<?php echo esc_attr( $option_id ); ?>"
			type="number"
			<?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			value="<?php echo esc_attr( $value ); ?>"
			data-default="<?php echo esc_attr( $default ); ?>"
			<?php echo $readonly ? 'readonly' : ''; ?>
		/>
		<?php
		if ( ! empty( $option['suffix'] ) ) {
			echo '<span class="wps-slider__suffix">' . esc_html( $option['suffix'] ) . '</span>';
		}
		echo '</div>';
	}

	/**
	 * The built in shapes, so the real control and the locked preview in the
	 * free version always offer the same list.
	 *
	 * @return array
	 */
	public static function get_shape_choices() {
		return array(
			'square'   => array(
				'label' => __( 'Square', 'confetti' ),
				'icon'  => '■',
			),
			'circle'   => array(
				'label' => __( 'Circle', 'confetti' ),
				'icon'  => '●',
			),
			'star'     => array(
				'label' => __( 'Star', 'confetti' ),
				'icon'  => '★',
			),
			'triangle' => array(
				'label' => __( 'Triangle', 'confetti' ),
				'icon'  => '▲',
			),
		);
	}

	/**
	 * Output the grid of style choices.
	 *
	 * Styles this install cannot run are still shown, locked, so people can
	 * see what upgrading would give them.
	 *
	 * @param array $instance Current instance settings.
	 */
	public function style_grid( $instance ) {

		$selected = ! empty( $instance['style'] ) ? $instance['style'] : 'cannon';

		echo '<div class="wps-style-grid">';

		foreach ( WPSunshine_Confetti_Styles::get_all() as $style_id => $style ) {

			$available = WPSunshine_Confetti_Styles::is_available( $style_id );

			$classes = array( 'wps-style-card' );
			if ( ! $available ) {
				$classes[] = 'is-locked';
			}
			if ( $available && $selected === $style_id ) {
				$classes[] = 'is-selected';
			}
			?>
			<label class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-name="<?php echo esc_attr( $style['name'] ); ?>" title="<?php echo esc_attr( $style['blurb'] ); ?>">
				<input type="radio" name="style" value="<?php echo esc_attr( $style_id ); ?>" <?php checked( $selected, $style_id ); ?> <?php disabled( ! $available ); ?> />
				<span class="wps-style-card__name"><?php echo esc_html( $style['name'] ); ?></span>
				<?php if ( ! $available ) : ?>
					<span class="wps-style-card__lock"><?php _e( 'Premium', 'confetti' ); ?></span>
				<?php else : ?>
					<span class="wps-style-card__state"><?php _e( 'Selected', 'confetti' ); ?></span>
					<a href="#" class="wps-confetti-sample" data-style="<?php echo esc_attr( $style_id ); ?>"><?php _e( 'Preview', 'confetti' ); ?></a>
				<?php endif; ?>
			</label>
			<?php
		}

		echo '</div>';

		do_action( 'wps_confetti_after_style_grid', $instance, $selected );
	}

	/**
	 * Output the extra fields a style needs, such as the word to spell out.
	 * Each one only shows while its own style is chosen.
	 *
	 * @param array $instance Current instance settings.
	 */
	public function style_fields( $instance ) {

		foreach ( WPSunshine_Confetti_Styles::get_available() as $style_id => $style ) {

			if ( empty( $style['fields'] ) ) {
				continue;
			}

			foreach ( $style['fields'] as $field_id => $field ) {

				$value = isset( $instance[ $field_id ] ) ? $instance[ $field_id ] : $field['default'];

				$this->option_row_open( $field['label'], array( $style_id ) );
				?>
					<input
						type="text"
						name="<?php echo esc_attr( $field_id ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						class="regular-text"
						<?php echo isset( $field['maxlength'] ) ? 'maxlength="' . absint( $field['maxlength'] ) . '"' : ''; ?>
						data-default="<?php echo esc_attr( $field['default'] ); ?>"
					/>
				<?php
				$this->option_row_close( isset( $field['description'] ) ? $field['description'] : '' );
			}
		}
	}

	/**
	 * Setup plugin admin screen header resource links.
	 *
	 * @param array $links Array of links to include in the header on plugin settings page
	 */
	public function header_links( $links ) {
		$links = array(
			'documentation' => array(
				'url'   => 'https://wpsunshine.com/support/',
				'label' => 'Documentation',
			),
			'review'        => array(
				'url'   => 'https://wordpress.org/support/plugin/confetti/reviews/#new-post',
				'label' => 'Write a Review',
			),
			'feedback'      => array(
				'url'   => 'https://wpsunshine.com/feedback',
				'label' => 'Feedback',
			),
			'upgrade'       => array(
				'url'   => 'https://wpsunshine.com/plugins/confetti/',
				'label' => 'Upgrade',
			),
		);
		return $links;
	}

	/**
	 * Return if we are running the Premium version of this plugin.
	 */
	public function is_premium() {
		return apply_filters( 'wps_confetti_premium', false );
	}

	/**
	 * Get available tabs and set the current.
	 */
	public function set_tabs() {
		$this->tabs = apply_filters(
			'wps_confetti_tabs',
			array(
				'instances'          => __( 'Instances', 'confetti' ),
				'integrations_promo' => __( 'Integrations', 'confetti' ),
				'usage'              => __( 'Usage', 'confetti' ),
			)
		);
		$this->tab  = array_key_first( $this->tabs );
		if ( isset( $_GET['tab'] ) ) {
			$this->tab = sanitize_key( $_GET['tab'] );
		}
	}

	/**
	 * Display options page.
	 */
	public function options_page() {
		$options = WPS_Confetti()->get_options( true );
		?>
		<div id="wps-aa-admin">

			<div class="wps-header">
				<a href="https://www.wpsunshine.com/?utm_source=plugin&utm_medium=link&utm_campaign=confetti" target="_blank" class="wps-logo"><img src="<?php echo WPS_CONFETTI_PLUGIN_URL; ?>/assets/images/confetti-logo.svg" alt="Confetti by WP Sunshine" /></a>

				<?php
				$header_links = apply_filters( 'wps_confetti_header_links', array() );
				if ( ! empty( $header_links ) ) {
					echo '<div id="wps-header-links">';
					foreach ( $header_links as $key => $link ) {
						echo '<a href="' . $link['url'] . '?utm_source=plugin&utm_medium=link&utm_campaign=confetti" target="_blank" class="wps-header-link--' . $key . '">' . $link['label'] . '</a>';
					}
					echo '</div>';
				}
				?>

				<?php if ( count( $this->tabs ) > 1 ) { ?>
				<nav class="wps-options-menu">
					<ul>
						<?php foreach ( $this->tabs as $key => $label ) { ?>
							<li
							<?php
							if ( $this->tab == $key ) {
								?>
  class="wps-options-active"<?php } ?>><a href="<?php echo admin_url( 'options-general.php?page=wps_confetti&tab=' . $key ); ?>"><?php echo $label; ?></a></li>
						<?php } ?>
					</ul>
				</nav>
				<?php } ?>

			</div>

			<div class="wrap wps-wrap">
				<h2></h2>

				<?php
				// Conditionally wrap in form - addons tab uses AJAX forms so no outer form needed
				$needs_form_wrapper = ! in_array( $this->tab, array( 'addons' ), true );
				if ( $needs_form_wrapper ) {
					?>
					<form method="post" action="<?php echo admin_url( 'options-general.php?page=wps_confetti&tab=' . $this->tab ); ?>">
					<?php wp_nonce_field( 'wps_confetti_options', 'wps_confetti_options' ); ?>
				<?php } ?>

				<?php do_action( 'wps_confetti_options_before', $options, $this->tab ); ?>

				<?php do_action( 'wps_confetti_options_tab_' . $this->tab, $options ); ?>

				<?php do_action( 'wps_confetti_options_after', $options, $this->tab ); ?>

				<?php if ( $needs_form_wrapper ) { ?>
				</form>
				<?php } ?>
			</div>

		</div>
		<?php
	}

	/**
	 * Display instances tab with confetti instance management.
	 *
	 * @param array $options Current plugin options.
	 */
	public function instances_tab( $options ) {

		// Get all instances
		$instances = WPS_Confetti()->get_instances();

		// Determine which instance we're editing
		$current_instance_id = isset( $_GET['editing_instance'] ) ? sanitize_key( $_GET['editing_instance'] ) : 'default';

		// If instance doesn't exist, fall back to default
		if ( ! isset( $instances[ $current_instance_id ] ) ) {
			$current_instance_id = 'default';
		}

		// Get current instance settings
		$instance = $instances[ $current_instance_id ];

		if ( empty( $instance['style'] ) ) {
			$instance['style'] = 'cannon';
		}

		$this->instance_nav( $instances, $current_instance_id, $instance );
		?>

		<input type="hidden" name="editing_instance" value="<?php echo esc_attr( $current_instance_id ); ?>" />

		<div class="wps-layout">
			<div class="wps-layout__main" id="wps-confetti-display">

				<?php
				$this->panel_open( 'style', __( 'Style', 'confetti' ), array( 'collapsible' => true ) );
				$this->style_grid( $instance );
				$this->panel_close();

				// Appearance comes before behavior: how it looks is the first
				// thing people want to change.
				do_action( 'wps_confetti_instance_panels', $instance, $current_instance_id );

				// Behavior and physics are their own sections, both closed to
				// start with so the page opens short.
				$premium_badge = $this->is_premium() ? '' : __( 'Premium', 'confetti' );

				$this->panel_open(
					'behavior',
					__( 'Behavior', 'confetti' ),
					array(
						'collapsible' => true,
						'open'        => false,
						'badge'       => $premium_badge,
					)
				);
				$this->style_fields( $instance );
				do_action( 'wps_confetti_options_behavior', $instance, $current_instance_id );
				$this->panel_close();

				$this->panel_open(
					'physics',
					__( 'Advanced Physics', 'confetti' ),
					array(
						'collapsible' => true,
						'open'        => false,
						'badge'       => $premium_badge,
					)
				);
				do_action( 'wps_confetti_options_physics', $instance, $current_instance_id );
				$this->panel_close();

				$this->save_bar( $instance );
				?>

			</div>

			<div class="wps-layout__side">
				<?php do_action( 'wps_confetti_instance_sidebar', $instance, $current_instance_id ); ?>
			</div>
		</div>

		<?php
		do_action( 'wps_confetti_after_instance_options', $instance, $current_instance_id );
	}

	/**
	 * The row of instance tabs, with the menu on the one being edited and the
	 * add button on the end.
	 *
	 * @param array  $instances           Every instance.
	 * @param string $current_instance_id Instance being edited.
	 * @param array  $instance            Settings of the instance being edited.
	 */
	private function instance_nav( $instances, $current_instance_id, $instance ) {

		$add_url = wp_nonce_url( admin_url( 'options-general.php?page=wps_confetti&tab=instances&add_instance=1' ), 'add_instance' );
		?>
		<div class="wps-instance-nav">

			<div class="wps-instance-tabs">
				<?php foreach ( $instances as $instance_id => $instance_data ) : ?>
					<?php $is_current = ( $current_instance_id === $instance_id ); ?>
					<div class="wps-instance-tab<?php echo $is_current ? ' is-current' : ''; ?>">
						<a href="<?php echo esc_url( admin_url( 'options-general.php?page=wps_confetti&tab=instances&editing_instance=' . $instance_id ) ); ?>" class="wps-instance-tab__name">
							<?php echo esc_html( $instance_data['name'] ); ?>
						</a>
						<?php if ( $is_current ) : ?>
							<button type="button" class="wps-instance-menu-toggle" aria-label="<?php esc_attr_e( 'Instance actions', 'confetti' ); ?>">&hellip;</button>
							<div class="wps-instance-menu">
								<?php do_action( 'wps_confetti_instance_menu_before', $instance_id, $instance_data ); ?>
								<a href="#" class="wps-copy-instance-shortcode" data-shortcode="<?php echo esc_attr( $this->instance_shortcode( $instance_id ) ); ?>">
									<span class="dashicons dashicons-shortcode"></span> <?php _e( 'Copy shortcode', 'confetti' ); ?>
								</a>
								<?php do_action( 'wps_confetti_instance_menu', $instance_id, $instance_data ); ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( count( $instances ) < WPS_Confetti()->get_max_instances() ) : ?>
				<a href="<?php echo esc_url( $add_url ); ?>" class="wps-add-instance-btn" id="wps-add-instance"><?php _e( '+ Add Instance', 'confetti' ); ?></a>
			<?php else : ?>
				<span class="wps-add-instance-btn is-locked" id="wps-add-instance"
				data-upgrade-title="<?php esc_attr_e( 'Run more than one confetti', 'confetti' ); ?>"
				data-upgrade-sub="<?php esc_attr_e( 'Unlimited instances are part of Premium.', 'confetti' ); ?>">
					<?php _e( '+ Add Instance', 'confetti' ); ?>
					<span class="wps-badge"><?php _e( 'Premium', 'confetti' ); ?></span>
				</span>
			<?php endif; ?>

		</div>

		<div class="wps-instance-rename" id="wps-instance-rename" style="display:none;">
			<label for="wps-instance-name"><?php _e( 'Instance name', 'confetti' ); ?></label>
			<input type="text" id="wps-instance-name" name="instance_name" value="<?php echo esc_attr( $instance['name'] ); ?>" class="regular-text" />
			<span class="description"><?php _e( 'Save changes to keep the new name.', 'confetti' ); ?></span>
		</div>
		<?php
	}

	/**
	 * The shortcode that runs one instance.
	 *
	 * @param string $instance_id Instance ID.
	 * @return string
	 */
	public function instance_shortcode( $instance_id ) {
		if ( 'default' === $instance_id ) {
			return '[confetti]';
		}
		return '[confetti instance="' . $instance_id . '"]';
	}

	/**
	 * Save button, and when this instance was last saved.
	 *
	 * @param array $instance Current instance settings.
	 */
	public function save_bar( $instance ) {
		?>
		<div class="wps-save-bar">
			<input type="submit" value="<?php esc_attr_e( 'Save Changes', 'confetti' ); ?>" class="button button-primary" />
			<?php if ( ! empty( $instance['updated'] ) ) : ?>
				<span class="wps-save-bar__time">
					<?php
					/* translators: %s: how long ago the instance was saved, such as "2 minutes". */
					printf( esc_html__( 'Last saved %s ago', 'confetti' ), esc_html( human_time_diff( $instance['updated'] ) ) );
					?>
				</span>
			<?php endif; ?>
			<?php do_action( 'wps_confetti_save_bar', $instance ); ?>
		</div>
		<?php
	}

	/**
	 * The preview button at the top of the sidebar.
	 *
	 * @param array  $instance            Current instance settings.
	 * @param string $current_instance_id Instance being edited.
	 */
	public function sidebar_preview( $instance, $current_instance_id ) {

		$style_id = ! empty( $instance['style'] ) ? $instance['style'] : 'cannon';
		$style    = WPSunshine_Confetti_Styles::get( $style_id );
		$name     = $style ? $style['name'] : $style_id;
		?>
		<div class="wps-card wps-card--preview">
			<button type="button" class="button button-primary" id="wps-confetti-preview">
				<?php
				/* translators: %s: name of the chosen confetti style. */
				printf( esc_html__( 'Preview %s', 'confetti' ), esc_html( $name ) );
				?>
			</button>
			<p class="description"><?php _e( 'Plays full screen, exactly as visitors see it.', 'confetti' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Display integrations tab (promo for free version)
	 */
	public function integrations_promo_tab() {
		?>
		<div class="wps-promo">
			<h2><?php _e( 'Trigger Confetti with Popular Plugins', 'confetti' ); ?></h2>
			<p><?php _e( 'Upgrade to Premium to automatically trigger confetti animations when users complete actions in popular WordPress plugins.', 'confetti' ); ?></p>
			<ul>
				<li><strong><?php _e( 'WooCommerce', 'confetti' ); ?></strong> - <?php _e( 'Trigger confetti on order completion, product purchase, or cart actions', 'confetti' ); ?></li>
				<li><strong><?php _e( 'Gravity Forms', 'confetti' ); ?></strong> - <?php _e( 'Show confetti when forms are successfully submitted', 'confetti' ); ?></li>
				<li><strong><?php _e( 'WPForms', 'confetti' ); ?></strong> - <?php _e( 'Celebrate form submissions with confetti', 'confetti' ); ?></li>
				<li><strong><?php _e( 'Contact Form 7', 'confetti' ); ?></strong> - <?php _e( 'Add confetti to successful form submissions', 'confetti' ); ?></li>
				<li><strong><?php _e( 'Fluent Forms', 'confetti' ); ?></strong> - <?php _e( 'Trigger confetti on form completion', 'confetti' ); ?></li>
				<li><strong><?php _e( 'Elementor Forms', 'confetti' ); ?></strong> - <?php _e( 'Add confetti effects to Elementor form submissions', 'confetti' ); ?></li>
				<li><strong><?php _e( 'LearnDash', 'confetti' ); ?></strong> - <?php _e( 'Celebrate course completions and quiz successes', 'confetti' ); ?></li>
				<li><strong><?php _e( 'MemberPress', 'confetti' ); ?></strong> - <?php _e( 'Welcome new members with confetti', 'confetti' ); ?></li>
				<li><strong><?php _e( 'Easy Digital Downloads', 'confetti' ); ?></strong> - <?php _e( 'Show confetti on successful purchases', 'confetti' ); ?></li>
			</ul>

			<h3><?php _e( 'Additional Premium Features:', 'confetti' ); ?></h3>
			<ul>
				<li><?php _e( 'More advanced confetti styling options', 'confetti' ); ?></li>
				<li><?php _e( 'Enable/disable integrations individually', 'confetti' ); ?></li>
				<li><?php _e( 'Choose which confetti instance to use per integration', 'confetti' ); ?></li>
			</ul>

			<div style="margin-top: 30px;">
				<a href="https://wpsunshine.com/plugins/confetti/?utm_source=plugin&utm_medium=integrations_tab&utm_campaign=confetti" target="_blank" class="button button-primary button-large">
					<?php _e( 'Upgrade to Premium', 'confetti' ); ?>
				</a>
				<p style="margin-top: 15px; font-size: 12px; color: #666;">
					<?php _e( 'Starting at $19/year', 'confetti' ); ?> &mdash; <?php _e( '14 days money back guarantee', 'confetti' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Display usage tab with shortcode documentation.
	 */
	public function usage_tab() {
		?>

		<script>
		jQuery( document ).ready(function($) {

			// Copy shortcode to clipboard
			$( '.wps-copy-shortcode' ).on( 'click', function(){
				var $button = $( this );
				var $icon = $button.find( '.dashicons' );
				var textToCopy = $( $button.data( 'clipboard-target' ) ).text();

				// The icon turns into a green tick, then back again.
				function copied() {
					$button.addClass( 'is-copied' );
					$icon.removeClass( 'dashicons-admin-page' ).addClass( 'dashicons-yes' );
					setTimeout( function() {
						$button.removeClass( 'is-copied' );
						$icon.removeClass( 'dashicons-yes' ).addClass( 'dashicons-admin-page' );
					}, 2000 );
				}

				// Older browsers, and the newer one when it refuses - it turns
				// the request down whenever the document is not focused, and
				// without this that failure was silent.
				function copyTheOldWay() {
					var $temp = $( '<textarea>' );
					$( 'body' ).append( $temp );
					$temp.val( textToCopy ).select();
					document.execCommand( 'copy' );
					$temp.remove();
					copied();
				}

				if ( navigator.clipboard && window.isSecureContext ) {
					navigator.clipboard.writeText( textToCopy ).then( copied, copyTheOldWay );
				} else {
					copyTheOldWay();
				}

				return false;
			});

		});
		</script>

		<p>
			<?php _e( 'You can add confetti to your pages using either the Confetti block or the shortcode.', 'confetti' ); ?>
			<a href="https://wpsunshine.com/documentation/how-to-put-confetti-on-any-page/?utm_source=plugin&utm_medium=link&utm_campaign=confetti" target="_blank"><?php _e( 'View full documentation', 'confetti' ); ?></a>
		</p>
		
		<table class="form-table">
			<tr>
				<th><?php _e( 'Confetti Block', 'confetti' ); ?></th>
				<td>
					<p><?php _e( 'When editing a page or post with the block editor, search for the "Confetti" block and add it to your content.', 'confetti' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Shortcode', 'confetti' ); ?></th>
				<td>
					<p><?php _e( 'Use this shortcode to trigger confetti on any page or post:', 'confetti' ); ?></p>
					<div class="wps-shortcode-box">
						<code id="wps-confetti-shortcode">[confetti]</code>
						<button type="button" class="wps-copy-shortcode" data-clipboard-target="#wps-confetti-shortcode" title="<?php esc_attr_e( 'Copy shortcode', 'confetti' ); ?>">
							<span class="dashicons dashicons-admin-page"></span>
							<span class="screen-reader-text"><?php _e( 'Copy shortcode', 'confetti' ); ?></span>
						</button>
					</div>
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Shortcode Parameters', 'confetti' ); ?></th>
				<td>
					<ul class="wps-shortcode-params">
						<li>
							<code>onload</code> - <?php _e( 'Set to "true" to trigger confetti when the page loads. Default: true', 'confetti' ); ?><br />
							<span class="wps-description"><?php _e( 'Example:', 'confetti' ); ?> <code>[confetti onload="true"]</code></span>
						</li>
						<li>
							<code>inview</code> - <?php _e( 'Set to "true" to trigger confetti when the element scrolls into view. Default: false', 'confetti' ); ?><br />
							<span class="wps-description"><?php _e( 'Example:', 'confetti' ); ?> <code>[confetti onload="false" inview="true"]</code></span>
						</li>
					<?php if ( WPS_Confetti()->is_premium() ) : ?>
						<li>
							<code>instance</code> - <?php _e( 'Use a specific confetti instance by ID (Premium only). Default: default', 'confetti' ); ?><br />
							<span class="wps-description"><?php _e( 'Example:', 'confetti' ); ?> <code>[confetti instance="instance_12345"]</code></span>
						</li>
						<li>
							<strong><?php _e( 'Custom Parameters (Premium only)', 'confetti' ); ?></strong> - <?php _e( 'You can also customize individual parameters: style, duration, delay, speed, particlecount, angle, spread, startvelocity, decay, gravity, drift, ticks, scalar, zindex, origin_x, origin_y', 'confetti' ); ?><br />
							<span class="wps-description"><?php _e( 'Example:', 'confetti' ); ?> <code>[confetti style="fireworks" duration="5" particlecount="200"]</code></span>
						</li>
						<?php endif; ?>
					</ul>
				</td>
			</tr>
		</table>

			<?php
	}

		/**
		 * Save options based on which tab we are viewing.
		 */
	public function save_options() {

		$post_data = wp_unslash( $_POST );

		if ( ! isset( $post_data['wps_confetti_options'] ) || ! wp_verify_nonce( $post_data['wps_confetti_options'], 'wps_confetti_options' ) ) {
			return;
		}

		$options = get_option( 'wps_confetti' );
		if ( empty( $options ) ) {
			$options = array();
		}
		$options = apply_filters( 'wps_confetti_save_tab_' . $this->tab, $options, $post_data );

		// If all valid.
		if ( count( self::$errors ) > 0 ) {
			foreach ( self::$errors as $error ) {
				$this->add_notice( $error, 'error' );
			}
		} else {
			update_option( 'wps_confetti', $options );
			$this->add_notice( __( 'Settings saved!', 'confetti' ) );

			// Redirect to maintain editing instance
			if ( isset( $post_data['editing_instance'] ) && ! empty( $post_data['editing_instance'] ) ) {
				$redirect_url = admin_url( 'options-general.php?page=wps_confetti&tab=' . $this->tab . '&editing_instance=' . sanitize_key( $post_data['editing_instance'] ) );
				wp_redirect( $redirect_url );
				exit;
			}
		}

	}

		/**
		 * Save options for the instances tab.
		 *
		 * @param array $options Current plugin options.
		 * @param array $post_data Posted form data.
		 * @return array Updated options.
		 */
	public function save_instances_tab( $options, $post_data ) {
		// Get current instances
		$instances = isset( $options['instances'] ) ? $options['instances'] : array();

		// Determine which instance we're editing
		$editing_instance_id = isset( $post_data['editing_instance'] ) ? sanitize_key( $post_data['editing_instance'] ) : 'default';

		// Make sure the instance exists
		if ( ! isset( $instances[ $editing_instance_id ] ) ) {
			$editing_instance_id = 'default';
			if ( ! isset( $instances['default'] ) ) {
				$instances['default'] = array(
					'id'   => 'default',
					'name' => __( 'Default', 'confetti' ),
				);
			}
		}

		// Update instance name if one was posted. An empty box is ignored so a
		// cleared field cannot leave a tab with no label on it.
		if ( ! empty( $post_data['instance_name'] ) ) {
			$instances[ $editing_instance_id ]['name'] = sanitize_text_field( $post_data['instance_name'] );
		}

		$instances[ $editing_instance_id ]['updated'] = time();

		// Save style. Anything this install cannot run is refused rather than
		// stored, so a posted premium style on a free site does not stick.
		$style = isset( $post_data['style'] ) ? sanitize_text_field( $post_data['style'] ) : '';
		if ( $style && ! WPSunshine_Confetti_Styles::is_available( $style ) ) {
			$style = WPSunshine_Confetti_Styles::get_fallback();
		}
		$instances[ $editing_instance_id ]['style'] = $style;

		// Save any extra fields the chosen style defines, such as the word for
		// Confetti Type.
		$style_data = WPSunshine_Confetti_Styles::get( $style );
		if ( $style_data && ! empty( $style_data['fields'] ) ) {
			foreach ( array_keys( $style_data['fields'] ) as $field_id ) {
				if ( isset( $post_data[ $field_id ] ) ) {
					$instances[ $editing_instance_id ][ $field_id ] = sanitize_text_field( $post_data[ $field_id ] );
				}
			}
		}

		// Save back to options
		$options['instances'] = $instances;

		return $options;
	}

		/**
		 * Add a notice to be shown after action such as save option.
		 */
	public function add_notice( $text, $type = 'success' ) {
		self::$notices[] = array(
			'text' => $text,
			'type' => $type,
		);
	}

		/**
		 * Output/show the notices.
		 */
	public function show_notices() {
		if ( ! empty( self::$notices ) ) {
			foreach ( self::$notices as $notice ) {
				echo '<div class="notice notice-' . esc_attr( $notice['type'] ) . '"><p>' . wp_kses_post( $notice['text'] ) . '</p></div>';
			}
		}
	}

}

		// Only instantiate if premium version hasn't already created an instance
if ( ! class_exists( 'WPSunshine_Confetti_Options_Premium' ) ) {
	new WPSunshine_Confetti_Options();
}

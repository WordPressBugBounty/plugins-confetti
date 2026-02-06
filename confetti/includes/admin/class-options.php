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
	private $save_button   = true;
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
	 * Get admin JavaScript for the instances tab
	 */
	private function get_admin_script() {
		return "
		jQuery( document ).ready(function($) {

			$( '.wps-confetti-sample' ).on( 'click', function(){

				var sample_style = $( this ).data( 'style' );

				if ( sample_style == 'cannon' ) {

					var defaults = {
						style: 'cannon'
					};

				} else if ( sample_style == 'cannon_real' ) {

					var defaults = {
						style: 'cannon_real',
						particleCount: 200
					};

				} else if ( sample_style == 'cannon_repeat' ) {

					var defaults = {
						style: 'cannon_repeat'
					};

				} else if ( sample_style == 'fireworks' ) {

					var defaults = {
						style: 'fireworks'
					};

				} else if ( sample_style == 'falling' ) {

					var defaults = {
						style: 'falling',
						colors: ['#26ccff','#a25afd','#ff5e7e','#88ff5a','#fcff42','#ffa62d','#ff36ff']
					};

				} else if ( sample_style == 'burst' ) {

					var defaults = {
						style: 'burst'
					};

				} else if ( sample_style == 'school' ) {

					var defaults = {
						style: 'school',
					};

				}

				wps_run_confetti( defaults );

				return false;

			});

		});
		";
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
				<form method="post" action="<?php echo admin_url( 'options-general.php?page=wps_confetti&tab=' . $this->tab ); ?>">
				<?php wp_nonce_field( 'wps_confetti_options', 'wps_confetti_options' ); ?>

				<?php do_action( 'wps_confetti_options_before', $options, $this->tab ); ?>

				<?php do_action( 'wps_confetti_options_tab_' . $this->tab, $options ); ?>

				<?php do_action( 'wps_confetti_options_after', $options, $this->tab ); ?>

				<?php if ( $this->save_button ) : ?>
					<p id="wps-settings-submit">
						<input type="submit" value="<?php _e( 'Save Changes', 'confetti' ); ?>" class="button button-primary" />
					</p>
				<?php endif; ?>

				</form>
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

		// Always show instance tabs area with add button
		?>
		<!-- Instance Tabs -->
		<ul class="subsubsub">
			<?php
			$instance_count  = 0;
			$total_instances = count( $instances );
			if ( $total_instances > 1 ) :
				foreach ( $instances as $instance_id => $instance_data ) :
					$instance_count++;
					$is_last = ( $instance_count === $total_instances );
					?>
				<li>
					<a href="<?php echo esc_url( admin_url( 'options-general.php?page=wps_confetti&tab=instances&editing_instance=' . $instance_id ) ); ?>" class="<?php echo ( $current_instance_id === $instance_id ) ? 'current' : ''; ?>">
						<?php echo esc_html( $instance_data['name'] ); ?>
					</a>
					<?php
					if ( ! $is_last ) :
						?>
						 | <?php endif; ?>
						<?php
						// Hook for instance actions (premium will add dropdown here)
						do_action( 'wps_confetti_instance_tab_actions', $instance_id, $instance_data );
						?>
					</li>
					<?php endforeach; ?>
			<?php endif; ?>
			
			<!-- Add Instance Button -->
			<li>
				<?php
				if ( $total_instances > 1 ) :
					?>
					 | <?php endif; ?>
					 <!--
					<a href="<?php echo wp_nonce_url( admin_url( 'options-general.php?page=wps_confetti&tab=instances&add_instance=1' ), 'add_instance' ); ?>" class="wps-add-instance-btn" id="wps-add-instance">
						<?php _e( '+ Add Instance', 'confetti' ); ?>
					</a>
				-->
			</li>
		</ul>

		<input type='hidden' name='editing_instance' value="<?php echo esc_attr( $current_instance_id ); ?>" / >

		<?php if ( $current_instance_id !== 'default' && count( $instances ) > 1 ) : ?>
		<table class="form-table">
			<tr>
				<th><?php _e( 'Instance Name', 'confetti' ); ?></th>
				<td>
					<input type="text" name="instance_name" value="<?php echo esc_attr( $instance['name'] ); ?>" class="regular-text" />
					<p class="description"><?php _e( 'Use this ID in your shortcode:', 'confetti' ); ?> <code>[confetti instance="<?php echo esc_html( $current_instance_id ); ?>"]</code></p>
				</td>
			</tr>
		</table>
		<?php endif; ?>


		<table class="form-table" id="wps-confetti-display">
			<tr class="all">
				<th><?php _e( 'Style', 'confetti' ); ?></th>
				<td>
					<p>
						<?php
						if ( empty( $instance['style'] ) ) {
							$instance['style'] = '';
						}
						?>
						<label><input type="radio" name="style" value="cannon" <?php checked( $instance['style'], 'cannon' ); ?>/> <?php _e( 'Basic Cannon', 'confetti' ); ?></label> <a href="#" class="wps-confetti-sample" data-style="cannon" style="font-size: 12px;"><?php _e( 'See sample', 'confetti' ); ?></a><br />
						<label><input type="radio" name="style" value="cannon_real" <?php checked( $instance['style'], 'cannon_real' ); ?>/> <?php _e( 'Realistic Cannon', 'confetti' ); ?></label> <a href="#" class="wps-confetti-sample" data-style="cannon_real" style="font-size: 12px;"><?php _e( 'See sample', 'confetti' ); ?></a><br />
						<label><input type="radio" name="style" value="cannon_repeat" <?php checked( $instance['style'], 'cannon_repeat' ); ?>/> <?php _e( 'Repeating Cannon', 'confetti' ); ?></label> <a href="#" class="wps-confetti-sample" data-style="cannon_repeat" style="font-size: 12px;"><?php _e( 'See sample', 'confetti' ); ?></a><br />
						<label><input type="radio" name="style" value="fireworks" <?php checked( $instance['style'], 'fireworks' ); ?>/> <?php _e( 'Fireworks', 'confetti' ); ?></label> <a href="#" class="wps-confetti-sample" data-style="fireworks" style="font-size: 12px;"><?php _e( 'See sample', 'confetti' ); ?></a> <br />
						<label><input type="radio" name="style" value="burst" <?php checked( $instance['style'], 'burst' ); ?>/> <?php _e( 'Burst', 'confetti' ); ?></label> <a href="#" class="wps-confetti-sample" data-style="burst" style="font-size: 12px;"><?php _e( 'See sample', 'confetti' ); ?></a> <br />
						<label><input type="radio" name="style" value="school" <?php checked( $instance['style'], 'school' ); ?>/> <?php _e( 'School Pride', 'confetti' ); ?></label> <a href="#" class="wps-confetti-sample" data-style="school" style="font-size: 12px;"><?php _e( 'See sample', 'confetti' ); ?></a> <br />
						<label><input type="radio" name="style" value="falling" <?php checked( $instance['style'], 'falling' ); ?>/> <?php _e( 'Falling', 'confetti' ); ?></label> <a href="#" class="wps-confetti-sample" data-style="falling" style="font-size: 12px;"><?php _e( 'See sample', 'confetti' ); ?></a> <br />
					</p>
				</td>
			</tr>
			
			<?php
			// Hook for premium to add additional customization options
			do_action( 'wps_confetti_instance_options', $instance, $current_instance_id );
			?>
		</table>

		<?php
		// Hook for premium to add preview button and JavaScript
		do_action( 'wps_confetti_after_instance_options', $instance, $current_instance_id );
		?>

		<?php
	}

	/**
	 * Display integrations tab (promo for free version)
	 */
	public function integrations_promo_tab() {
		$this->save_button = false;
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
				<!-- <li><?php _e( 'Choose which confetti instance to use per integration', 'confetti' ); ?></li> -->
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
				var targetId = $button.data( 'clipboard-target' );
				var $target = $( targetId );
				var textToCopy = $target.text();

				// Use modern Clipboard API if available
				if ( navigator.clipboard && window.isSecureContext ) {
					navigator.clipboard.writeText( textToCopy ).then( function() {
						var originalText = $button.text();
						$button.text( '<?php _e( 'Copied!', 'confetti' ); ?>' );
						setTimeout( function() {
							$button.text( originalText );
						}, 2000 );
					});
				} else {
					// Fallback for older browsers
					var $temp = $( '<textarea>' );
					$( 'body' ).append( $temp );
					$temp.val( textToCopy ).select();
					document.execCommand( 'copy' );
					$temp.remove();

					var originalText = $button.text();
					$button.text( '<?php _e( 'Copied!', 'confetti' ); ?>' );
					setTimeout( function() {
						$button.text( originalText );
					}, 2000 );
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
						<button type="button" class="button button-secondary wps-copy-shortcode" data-clipboard-target="#wps-confetti-shortcode"><?php _e( 'Copy', 'confetti' ); ?></button>
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
							<code>inview</code> - <?php _e( 'Set to "true" to trigger confetti when the element scrolls into view (Premium only). Default: false', 'confetti' ); ?><br />
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

		// Update instance name if provided and not default
		if ( $editing_instance_id !== 'default' && isset( $post_data['instance_name'] ) ) {
			$instances[ $editing_instance_id ]['name'] = sanitize_text_field( $post_data['instance_name'] );
		}

		// Save style
		$instances[ $editing_instance_id ]['style'] = isset( $post_data['style'] ) ? sanitize_text_field( $post_data['style'] ) : '';

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

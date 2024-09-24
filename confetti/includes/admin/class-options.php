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
		add_action( 'wps_confetti_options_tab_options', array( $this, 'options_tab' ) );

		// Save settings.
		add_action( 'admin_init', array( $this, 'save_options' ) );
		add_action( 'wps_confetti_save_tab_options', array( $this, 'save_options_tab' ), 1, 2 );
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
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'confetti-admin', WPS_CONFETTI_PLUGIN_URL . 'assets/css/admin.css', false, WPS_CONFETTI_VERSION );
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
		$this->tabs = apply_filters( 'wps_confetti_tabs', array( 'options' => __( 'Options', 'confetti' ) ) );
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

				<p id="wps-settings-submit">
					<input type="submit" value="<?php _e( 'Save Changes', 'confetti' ); ?>" class="button button-primary" />
				</p>

				</form>
			</div>

		</div>
		<?php
	}

	/**
	 * Get available tabs and set the current.
	 */
	public function options_tab( $options ) {
		?>

		<script>
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
						colors: ["#26ccff","#a25afd","#ff5e7e","#88ff5a","#fcff42","#ffa62d","#ff36ff"]
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
		</script>

		<?php if ( empty( $options['style'] ) ) { $options['style'] = ''; } ?>
		<table class="form-table" id="">
			<tr>
				<th><?php _e( 'Style', 'confetti' ); ?></th>
				<td>
					<p>
						<label><input type="radio" name="style" value="cannon" <?php checked( $options['style'], 'cannon' ); ?>/> <?php _e( 'Basic Cannon', 'confetti' ); ?></label> <a href="#" class="wps-confetti-sample" data-style="cannon" style="font-size: 12px;"><?php _e( 'See sample', 'confetti' ); ?></a><br />
						<label><input type="radio" name="style" value="cannon_real" <?php checked( $options['style'], 'cannon_real' ); ?>/> <?php _e( 'Realistic Cannon', 'confetti' ); ?></label> <a href="#" class="wps-confetti-sample" data-style="cannon_real" style="font-size: 12px;"><?php _e( 'See sample', 'confetti' ); ?></a><br />
						<label><input type="radio" name="style" value="cannon_repeat" <?php checked( $options['style'], 'cannon_repeat' ); ?>/> <?php _e( 'Repeating Cannon', 'confetti' ); ?></label> <a href="#" class="wps-confetti-sample" data-style="cannon_repeat" style="font-size: 12px;"><?php _e( 'See sample', 'confetti' ); ?></a><br />
						<label><input type="radio" name="style" value="fireworks" <?php checked( $options['style'], 'fireworks' ); ?>/> <?php _e( 'Fireworks', 'confetti' ); ?></label> <a href="#" class="wps-confetti-sample" data-style="fireworks" style="font-size: 12px;"><?php _e( 'See sample', 'confetti' ); ?></a> <br />
						<label><input type="radio" name="style" value="school" <?php checked( $options['style'], 'school' ); ?>/> <?php _e( 'School Pride', 'confetti' ); ?></label> <a href="#" class="wps-confetti-sample" data-style="school" style="font-size: 12px;"><?php _e( 'See sample', 'confetti' ); ?></a> <br />
						<label><input type="radio" name="style" value="falling" <?php checked( $options['style'], 'falling' ); ?>/> <?php _e( 'Falling', 'confetti' ); ?></label> <a href="#" class="wps-confetti-sample" data-style="falling" style="font-size: 12px;"><?php _e( 'See sample', 'confetti' ); ?></a> <br />
					</p>
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
		}

	}

	/**
	 * Save options for the options tab.
	 */
	public function save_options_tab( $options, $post_data ) {
		$options['style'] = isset( $post_data['style'] ) ? sanitize_text_field( $post_data['style'] ) : '';
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

new WPSunshine_Confetti_Options();

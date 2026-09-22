<?php
/**
 * WPSunshine_Confetti_Telemetry
 *
 * Opt-in anonymous usage data. Once a week, sends which styles and
 * integrations a site uses plus WordPress basics to wpsunshine.com. Nothing
 * is sent until the site owner says yes.
 *
 * @package WPSConfetti\Classes
 */

defined( 'ABSPATH' ) || exit;

// Free has no store URL constant, so the endpoint gets its own. The guard lets
// a dev site's wp-config.php point it at a local receiver.
defined( 'WPS_CONFETTI_TELEMETRY_URL' ) || define( 'WPS_CONFETTI_TELEMETRY_URL', 'https://wpsunshine.com/wp-json/wpsunshine/v1/telemetry' );

/**
 * WPSunshine_Confetti_Telemetry class.
 */
class WPSunshine_Confetti_Telemetry {

	const HOOK           = 'wps_confetti_telemetry';
	const SCHEMA_VERSION = 1;

	/**
	 * Hook everything up.
	 */
	public function __construct() {

		add_action( self::HOOK, array( $this, 'cron' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'maybe_schedule' ) );
			add_action( 'admin_notices', array( $this, 'notice' ) );
			add_action( 'admin_notices', array( $this, 'manual_send' ) );
			add_action( 'wp_ajax_wps_confetti_telemetry', array( $this, 'ajax_opt' ) );
			add_action( 'wps_confetti_options_tab_usage', array( $this, 'settings_panel' ), 20 );
			add_filter( 'wps_confetti_save_tab_usage', array( $this, 'save_settings' ), 10, 2 );
		}

	}

	/**
	 * The saved answer: 'yes', 'no', or '' when never asked.
	 *
	 * @return string
	 */
	public function get_status() {
		$options = get_option( 'wps_confetti', array() );
		return isset( $options['telemetry'] ) ? $options['telemetry'] : '';
	}

	/**
	 * Save the answer. This is the only place the weekly event is scheduled or
	 * removed, so the two can never disagree.
	 *
	 * @param string $choice 'yes' or 'no'.
	 */
	public function set_status( $choice ) {

		$options              = get_option( 'wps_confetti', array() );
		$options['telemetry'] = $choice;
		update_option( 'wps_confetti', $options );

		if ( 'yes' === $choice ) {
			if ( ! wp_next_scheduled( self::HOOK ) ) {
				// Starting now means the first send happens on the next page load.
				wp_schedule_event( time(), 'weekly', self::HOOK );
			}
		} else {
			wp_clear_scheduled_hook( self::HOOK );
		}

	}

	/**
	 * Put the weekly event back if it went missing, for example after the
	 * plugin was deactivated and reactivated while opted in.
	 */
	public function maybe_schedule() {
		if ( 'yes' === $this->get_status() && ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time(), 'weekly', self::HOOK );
		}
	}

	/**
	 * Ask once, on the Confetti settings page only.
	 */
	public function notice() {

		if ( ! isset( $_GET['page'] ) || 'wps_confetti' !== $_GET['page'] ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) || '' !== $this->get_status() ) {
			return;
		}
		?>
		<div id="wps-confetti-telemetry-notice" class="notice notice-info">
			<p style="font-size: 16px; font-weight: 600;"><?php esc_html_e( 'Help improve Confetti', 'confetti' ); ?></p>
			<p><?php esc_html_e( 'Sharing anonymous usage data shows us which confetti styles and plugin integrations get used most, so we know where to focus next.', 'confetti' ); ?></p>
			<p>
				<strong><?php esc_html_e( 'What is sent:', 'confetti' ); ?></strong>
				<?php esc_html_e( 'Plugin, WordPress and PHP versions, site language, theme name, which confetti styles and options your instances use, and which integrations are turned on or available. No site URL, email address, or personal data is ever sent.', 'confetti' ); ?>
				<a href="https://wpsunshine.com/documentation/anonymous-usage-data/?utm_source=plugin&utm_medium=link&utm_campaign=confetti" target="_blank"><?php esc_html_e( 'Learn more', 'confetti' ); ?></a>
			</p>
			<p>
				<button type="button" class="button button-primary wps-confetti-telemetry-choice" data-choice="yes"><?php esc_html_e( 'Yes, share anonymous data', 'confetti' ); ?></button>
				<button type="button" class="button wps-confetti-telemetry-choice" data-choice="no"><?php esc_html_e( 'No thanks', 'confetti' ); ?></button>
			</p>
		</div>
		<script>
		jQuery( document ).on( 'click', '.wps-confetti-telemetry-choice', function() {
			var choice = jQuery( this ).data( 'choice' );
			jQuery.post( ajaxurl, {
				action: 'wps_confetti_telemetry',
				choice: choice,
				nonce: '<?php echo esc_js( wp_create_nonce( 'wps_confetti_telemetry' ) ); ?>'
			}, function() {
				jQuery( '#wps-confetti-telemetry-notice' ).fadeOut();
				jQuery( '#wps-confetti-telemetry-checkbox' ).prop( 'checked', 'yes' === choice );
			} );
		} );
		</script>
		<?php

	}

	/**
	 * Save a yes/no from the admin notice.
	 */
	public function ajax_opt() {

		check_ajax_referer( 'wps_confetti_telemetry', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Not allowed', 'confetti' ), 403 );
		}

		$choice = isset( $_POST['choice'] ) ? sanitize_key( $_POST['choice'] ) : '';
		if ( ! in_array( $choice, array( 'yes', 'no' ), true ) ) {
			wp_send_json_error( __( 'Invalid choice', 'confetti' ), 400 );
		}

		$this->set_status( $choice );
		wp_send_json_success();

	}

	/**
	 * A checkbox on the Usage tab so the answer can be changed later. Saved
	 * with the tab's Save Changes button like every other setting.
	 */
	public function settings_panel() {

		WPSunshine_Confetti_Options::panel_open( 'telemetry', __( 'Anonymous usage data', 'confetti' ) );
		WPSunshine_Confetti_Options::option_row_open( __( 'Share usage data', 'confetti' ), array( 'all' ) );
		?>
		<label>
			<input type="checkbox" name="telemetry" id="wps-confetti-telemetry-checkbox" value="yes" <?php checked( 'yes', $this->get_status() ); ?> />
			<?php esc_html_e( 'Send anonymous usage data to WP Sunshine once a week', 'confetti' ); ?>
		</label>
		<?php
		WPSunshine_Confetti_Options::option_row_close( __( 'Plugin, WordPress and PHP versions, site language, theme name, and which styles, options and integrations you use. Never your site URL, email, or any personal data.', 'confetti' ) );
		WPSunshine_Confetti_Options::panel_close();
		?>
		<div class="wps-save-bar">
			<input type="submit" value="<?php esc_attr_e( 'Save Changes', 'confetti' ); ?>" class="button button-primary" />
		</div>
		<?php

	}

	/**
	 * Save the checkbox when the Usage tab is submitted.
	 *
	 * @param array $options   Current plugin options.
	 * @param array $post_data Posted form data.
	 * @return array Updated options.
	 */
	public function save_settings( $options, $post_data ) {

		$choice = ! empty( $post_data['telemetry'] ) ? 'yes' : 'no';
		$this->set_status( $choice );
		$options['telemetry'] = $choice;

		return $options;

	}

	/**
	 * Weekly event. Re-checks the answer so a leftover event can never send.
	 */
	public function cron() {

		if ( 'yes' !== $this->get_status() ) {
			wp_clear_scheduled_hook( self::HOOK );
			return;
		}

		$this->send();

	}

	/**
	 * Send right now via ?wps_confetti_send_telemetry=1, for testing.
	 */
	public function manual_send() {

		if ( ! isset( $_GET['wps_confetti_send_telemetry'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( 'yes' !== $this->get_status() ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Anonymous usage data is not enabled, nothing was sent.', 'confetti' ) . '</p></div>';
			return;
		}

		$response = $this->send();
		$code     = is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_response_code( $response );
		echo '<div class="notice notice-success"><p>' . esc_html( sprintf( 'Usage data sent. Response: %s', $code ) ) . '</p></div>';

	}

	/**
	 * Post the payload.
	 *
	 * WordPress's default user agent includes the site URL, which would put
	 * the URL in the receiver's server logs and break the promise that only a
	 * hashed key is sent. So the request carries its own.
	 *
	 * @return array|WP_Error
	 */
	public function send() {

		$data = $this->collect();

		return wp_remote_post(
			WPS_CONFETTI_TELEMETRY_URL,
			array(
				'timeout'    => 15,
				'headers'    => array( 'Content-Type' => 'application/json' ),
				'body'       => wp_json_encode( $data ),
				'user-agent' => 'WPSunshine-Confetti/' . $data['version'],
			)
		);

	}

	/**
	 * Build the payload.
	 *
	 * @return array
	 */
	public function collect() {

		$options   = get_option( 'wps_confetti', array() );
		$instances = WPS_Confetti()->get_instances();
		$version   = WPS_CONFETTI_VERSION;

		return array(
			'schema_version'         => self::SCHEMA_VERSION,
			'product'                => 'confetti',
			'key'                    => md5( get_site_url() ),
			'tier'                   => WPS_Confetti()->is_premium() ? 'premium' : 'free',
			'version'                => $version,
			'wp_version'             => get_bloginfo( 'version' ),
			'php_version'            => PHP_VERSION,
			'locale'                 => get_locale(),
			'multisite'              => is_multisite() ? 1 : 0,
			'environment'            => wp_get_environment_type(),
			'theme'                  => get_template(),
			'license_status'         => $this->license_status(),
			'install_date'           => ! empty( $options['install_time'] ) ? gmdate( 'Y-m-d H:i:s', (int) $options['install_time'] ) : null,
			'instance_count'         => count( $instances ),
			'styles'                 => $this->style_counts( $instances ),
			'style_options_used'     => $this->style_options_used( $instances ),
			'integrations_enabled'   => $this->integrations( $options, true ),
			'integrations_available' => $this->integrations( $options, false ),
			'block_posts'            => $this->content_count( 'wp:wpsunshine/confetti' ),
			'shortcode_posts'        => $this->content_count( '[confetti' ),
		);

	}

	/**
	 * Premium license state. Empty string in free.
	 *
	 * @return string
	 */
	private function license_status() {

		if ( ! WPS_Confetti()->is_premium() ) {
			return '';
		}

		$license_data = get_option( 'wps_confetti_license_data' );
		if ( empty( $license_data ) || empty( $license_data->license ) ) {
			return 'none';
		}

		return 'valid' === $license_data->license ? 'valid' : 'invalid';

	}

	/**
	 * How many instances use each style.
	 *
	 * @param array $instances Instances.
	 * @return array style id => count
	 */
	private function style_counts( $instances ) {

		$counts = array();
		foreach ( $instances as $instance ) {
			$style = ! empty( $instance['style'] ) ? $instance['style'] : 'cannon';
			$counts[ $style ] = isset( $counts[ $style ] ) ? $counts[ $style ] + 1 : 1;
		}
		return $counts;

	}

	/**
	 * How many instances customize each of the bigger options.
	 *
	 * @param array $instances Instances.
	 * @return array option => count
	 */
	private function style_options_used( $instances ) {

		$counts = array(
			'colors'  => 0,
			'shapes'  => 0,
			'emojis'  => 0,
			'svgs'    => 0,
			'overlay' => 0,
		);

		foreach ( $instances as $instance ) {
			foreach ( array( 'colors', 'shapes', 'emojis', 'svgs' ) as $option ) {
				if ( ! empty( $instance[ $option ] ) ) {
					++$counts[ $option ];
				}
			}
			if ( ! empty( $instance['overlay_enabled'] ) ) {
				++$counts['overlay'];
			}
		}

		return $counts;

	}

	/**
	 * Integration option keys that have an instance selected, or the addons
	 * whose plugin is present but nothing is selected yet. Same rule the
	 * addons tab uses to decide what counts as enabled.
	 *
	 * @param array $options Plugin options.
	 * @param bool  $enabled True for enabled keys, false for available-but-off addon IDs.
	 * @return array
	 */
	private function integrations( $options, $enabled ) {

		$list = array();

		foreach ( WPS_Confetti()->get_addons() as $id => $addon ) {

			$keys = array( $id );
			if ( ! empty( $addon['sub_instances'] ) ) {
				foreach ( array_keys( $addon['sub_instances'] ) as $sub_key ) {
					$keys[] = $id . '_' . $sub_key;
				}
			}

			$on = array();
			foreach ( $keys as $key ) {
				if ( ! empty( $options[ $key ] ) ) {
					$on[] = $key;
				}
			}

			if ( $enabled ) {
				$list = array_merge( $list, $on );
			} elseif ( empty( $on ) && ! empty( $addon['available'] ) ) {
				$list[] = $id;
			}
		}

		return $list;

	}

	/**
	 * Published posts whose content contains a string. Runs once a week, so a
	 * LIKE scan is fine.
	 *
	 * @param string $needle Text to look for.
	 * @return int
	 */
	private function content_count( $needle ) {

		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_content LIKE %s",
				'%' . $wpdb->esc_like( $needle ) . '%'
			)
		);

	}

}

new WPSunshine_Confetti_Telemetry();

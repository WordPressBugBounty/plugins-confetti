<?php
/**
 * WPSunshine_Confetti_Block
 *
 * The WPSunshine Confetti block class sets up admin/frontend
 *
 * @package WPSConfetti\Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WPSunshine_Confetti_Block class.
 */
class WPSunshine_Confetti_Block {

	/**
	 * Constructor for the block class.
	 * Loads options and hooks in the init method.
	 * Enqueues scripts and renders the block.
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_scripts' ) );
	}

	/**
	 * Registers block script on init.
	 */
	public function init() {

		// Check if Gutenberg is active.
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'confetti-block',
			WPS_CONFETTI_PLUGIN_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
			WPS_CONFETTI_VERSION,
			true
		);

		// Add instance data for preview functionality.
		wp_localize_script(
			'confetti-block',
			'confetti_instances',
			WPS_Confetti()->get_instances_for_js()
		);

		register_block_type(
			WPS_CONFETTI_ABSPATH . '/includes/blocks/confetti',
			array(
				'render_callback' => array( $this, 'render_block' ),
			)
		);

	}

	/**
	 * Enqueue necessary scripts.
	 */
	public function editor_scripts() {
		wp_enqueue_script(
			'confetti-core-editor',
			WPS_CONFETTI_PLUGIN_URL . 'assets/js/confetti-core.js',
			'',
			WPS_CONFETTI_VERSION,
			true
		);
		wp_enqueue_script(
			'confetti-editor',
			WPS_CONFETTI_PLUGIN_URL . 'assets/js/confetti.js',
			array( 'confetti-core-editor', 'jquery' ),
			WPS_CONFETTI_VERSION,
			true
		);

		// Any instance might be previewed in the editor, so load every style
		// this install can run rather than guessing.
		foreach ( array_keys( WPSunshine_Confetti_Styles::get_available() ) as $style_id ) {
			$url = WPSunshine_Confetti_Styles::get_script_url( $style_id );
			if ( $url ) {
				wp_enqueue_script(
					'confetti-editor-style-' . $style_id,
					$url,
					array( 'confetti-editor' ),
					WPS_CONFETTI_VERSION,
					true
				);
			}
		}
	}

	/**
	 * Render the block.
	 *
	 * @param array  $attributes Array of attributes for the block.
	 * @param string $content Content to be output.
	 */
	public function render_block( $attributes, $content = '' ) {

		$instance_id = isset( $attributes['instance'] ) ? sanitize_key( $attributes['instance'] ) : 'default';
		$trigger     = isset( $attributes['trigger'] ) ? sanitize_text_field( $attributes['trigger'] ) : 'onload';

		// The block calls it "onload"; everything else calls it "load".
		$method = ( 'inview' === $trigger ) ? 'inview' : 'load';

		return WPS_Confetti()->render_placement(
			$instance_id,
			$method,
			'wps-confetti-block-' . wp_rand( 1000, 9999 )
		);

	}

}

$wps_confetti_block = new WPSunshine_Confetti_Block();

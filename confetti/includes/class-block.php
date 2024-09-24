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
		add_action( 'enqueue_block_assets', array( $this, 'scripts' ) );
		add_filter( 'render_block', array( $this, 'render_block' ), 10, 2 );
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
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'jquery' ),
			WPS_CONFETTI_VERSION,
			true
		);

		register_block_type(
			'wpsunshine/confetti',
			array(
				'editor_script' => 'confetti-block',
				'editor_style'  => 'confetti-style',
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
		wp_add_inline_script( 'confetti-editor', WPS_Confetti()->inline_script() );
	}

	/**
	 * Function to be called when needed so Confetti is not loaded on every page.
	 */
	public function scripts() {
		if ( is_singular() ) {
			$id = get_the_ID();
			if ( has_block( 'wpsunshine/confetti', $id ) ) {
				WPS_Confetti()->enqueue_scripts();
			}
		}
	}

	/**
	 * Render the block.
	 *
	 * @param string $content Content to be output.
	 * @param array  $attributes Array of attributes for the block.
	 */
	public function render_block( $content, $attributes ) {

		if ( 'wpsunshine/confetti' != $attributes['blockName'] ) {
			return $content;
		}

		$output = WPS_Confetti()->trigger();
		$output = apply_filters( 'wps_confetti_block', $output, $attributes );
		return $output;

	}

}

$wps_confetti_block = new WPSunshine_Confetti_Block();

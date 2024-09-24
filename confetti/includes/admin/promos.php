<?php
/**
 * Promotional functions to get users to upgrade.
 *
 * @package WPSConfetti\promos
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Show the upgrade notice in header.
 */
function wps_confetti_header_upgrade() {
	echo '<a href="https://www.wpsunshine.com/plugins/confetti/?utm_source=plugin&utm_medium=button&utm_content=upgrade&utm_campaign=plugin_upgrade" target="_blank" class="wps-button" id="wps-confetti-header-upgrade">' . __( 'Upgrade to premium!', 'confetti' ) . '</a>';
}
add_action( 'wps_confetti_header', 'wps_confetti_header_upgrade' );

/**
 * Show the upgrade notice when viewing the options page.
 */
function wps_confetti_options_upgrade() {
	?>
	<div class="wps-confetti-upgrade">
		<h2>Upgrade for more features!</h2>
		<ul>
			<li>Customize colors and confetti effects.</li>
			<li>Integrate with e-commerce plugins to have confetti show on after purchase or donation pages:
				<ul>
					<li>WooCommerce</li>
					<li>Easy Digital Downloads</li>
					<li>Sunshine Photo Cart</li>
					<li>GiveWP</li>
					<li>WP Simple Pay</li>
					<li>Restrict Content Pro</li>
				</ul>
			</li>
			<li>Integrate with forms plugins to have confetti show after form submissions:
				<ul>
					<li>WS Form</li>
					<li>Gravity Forms</li>
					<li>WP Forms</li>
					<li>Ninja Forms</li>
					<li>Formidable Forms</li>
					<li>Forminator</li>
					<li>Contact Form 7</li>
				</ul>
			</li>
			<li>Integrate with LMS plugins to have confetti show after users complete various tasks:
				<ul>
					<li>Learn Dash</li>
					<li>LifterLMS</li>
				</ul>
			</li>
		</ul>
		<p><a href="https://www.wpsunshine.com/plugins/confetti/?utm_source=plugin&utm_medium=banner&utm_content=upgrade&utm_campaign=plugin_upgrade" target="_blank" class="wps-button" id="wps-confetti-upgrade"><?php _e( 'Upgrade Now!', 'confetti' ); ?></a></p>
	</div>
	<?php
}
add_action( 'wps_confetti_options_before', 'wps_confetti_options_upgrade' );

/**
 * Request a review notice.
 */
function wps_confetti_review_request() {
	$options = get_option( 'wps_confetti' );
	if ( ! empty( $options['review'] ) && 'dismissed' == $options['review'] ) {
		return;
	}
	if ( empty( $options['install_time'] ) ) {
		$options['install_time'] = time();
		update_option( 'wps_confetti', $options );
	}
	if ( ( time() - $options['install_time'] ) < DAY_IN_SECONDS * 15 ) {
		return;
	}
	?>
		<div class="notice notice-info is-dismissable" id="wps-confetti-review">
			<p>You having been using WP Sunshine Confetti for a bit and that's awesome! Could you please do a big favor and give it a review on WordPress?  Reviews from users like you really help our plugins to grow and continue to improve.</p>
			<p>- Derek, WP Sunshine Lead Developer</p>
			<p><a href="https://wordpress.org/support/view/plugin-reviews/confetti?filter=5#postform" target="_blank" class="button-primary wps-confetti-review-dismiss-button">Sure thing!</a> &nbsp; <a href="#" class="button wps-confetti-review-dismiss-button">No thanks</a>
		</div>
		<script>
			jQuery( document ).on( 'click', '.wps-confetti-review-dismiss-button', function() {
				jQuery.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'wps_confetti_dismiss_review',
					},
					success: function( data, textStatus, jqXHR ) {
						jQuery( '#wps-confetti-review' ).remove();
					}
				});
			});
		</script>
	<?php
}
add_action( 'admin_notices', 'wps_confetti_review_request' );

/**
 * Processes the dismiss notice for the review.
 */
function wps_confetti_review_dismiss() {
	$options           = get_option( 'wps_confetti' );
	$options['review'] = 'dismissed';
	update_option( 'wps_confetti', $options );
	wp_die();
}
add_action( 'wp_ajax_wps_confetti_dismiss_review', 'wps_confetti_review_dismiss' );

?>

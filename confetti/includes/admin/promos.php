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
	if ( isset( $_GET['tab'] ) && $_GET['tab'] === 'integrations_promo' ) {
		return;
	}
	?>
	<div id="wps-promos">
		<div id="wps-confetti-upgrade-premium">
		<div class="wps-promo wps-promo-featured">
			<h3>Unlock Premium Confetti Features!</h3>
			<ul>
				<li>Customize colors and 15 more confetti options</li>
				<li>Integrations with popular e-commerce, form, and LMS plugins</li>
				<!-- <li>Create multiple confetti configurations</li> -->
			</ul>
			<p><a href="https://www.wpsunshine.com/plugins/confetti/?utm_source=plugin&utm_medium=banner&utm_content=upgrade&utm_campaign=plugin_upgrade" target="_blank" class="button"><?php _e( 'Upgrade Now!', 'confetti' ); ?></a></p>
			<p class="wps-promo-price">Starting at $19, 14 days money back guarantee</p>
		</div>
</div>
		<h2>More WP Sunshine Plugins:</h2>
		<div id="wps-cb" class="wps-promo">
			<h3>Conversion Bridge 📊</h3>
			<p>Effortlessly connect your WordPress site with 15+ analytics and 8+ ad platforms, enabling no-code conversion tracking across 50+ popular plugins.</p>
			<p><a href="https://conversionbridgewp.com/?utm_source=plugin&utm_medium=link&utm_campaign=confetti" target="_blank" class="button">Learn more</a></p>
		</div>
		<div id="wps-confetti" class="wps-promo">
			<h3>Address Autocomplete Anything 📍</h3>
			<p>Add address autocomplete to any form on your WordPress website for better user experience.</p>
			<p><a href="https://wpsunshine.com/plugins/address-autocomplete/?utm_source=plugin&utm_medium=banner&utm_content=upgrade&utm_campaign=aa_upgrade" target="_blank" class="button">Learn more</a></p>
		</div>
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

add_action( 'wps_confetti_instance_options', 'wps_confetti_instance_options_upgrade_fake_options', 99, 2 );
function wps_confetti_instance_options_upgrade_fake_options( $instance, $current_instance_id ) {
	// Promote premium options here, explain what kind of things you can do with premium.
	?>
	
		<tr>
			<td colspan="2" style="padding: 20px 0;">
				<span style="border: 1px solid #ddd; padding: 10px 20px; border-radius: 5px; display: inline-block;">
				🎉 15 more options available with Premium - <a href="#" class="wps-confetti-upgrade-options-link">See additional options</a>
				</span>
				<script>
					jQuery( document ).ready( function() {
						jQuery( '.wps-confetti-upgrade-options-link' ).on( 'click', function( e ) {
							e.preventDefault();
							jQuery( '.upgrade-option' ).toggle();
						});
					});
				</script>
			</td>
		</tr>
		<tr class="upgrade-option" style="display: none;">
			<th><?php _e( 'Colors', 'confetti' ); ?></th>
				<td>
					<a href="#" id="wps-confetti-add-color"><?php _e( 'Add custom color', 'confetti' ); ?></a>
				</td>
			</tr>
			<tr class="upgrade-option" style="display: none;">
				<th><?php _e( 'Duration', 'confetti' ); ?></th>
				<td>
				<label><input name="duration" type="number" min="0" step="1" value="" readonly data-default="3" /> <?php _e( 'Default', 'confetti' ); ?>: 3</label>
					<p class="description"><?php _e( 'How long should this style last, in seconds.', 'confetti' ); ?></p>
				</td>
			</tr>
			<tr class="upgrade-option" style="display: none;">
				<th><?php _e( 'Delay', 'confetti' ); ?></th>
				<td>
				<label><input name="delay" type="number" min="0" step="1" value="0" readonly data-default="0" /> <?php _e( 'Default', 'confetti' ); ?>: 0</label>
					<p class="description"><?php _e( 'How long we wait to run the confetti, in seconds.', 'confetti' ); ?></p>
				</td>
			</tr>
			<tr class="upgrade-option" style="display: none;">
				<th><?php _e( 'Speed', 'confetti' ); ?></th>
				<td>
				<label><input name="speed" type="number" min="1" max="100" step="1" value="75" readonly data-default="75" /> <?php _e( 'Default', 'confetti' ); ?>: 75</label>
					<p class="description"><?php _e( 'How fast should the style play, 1-100', 'confetti' ); ?></p>
				</td>
			</tr>
			<tr class="upgrade-option" style="display: none;">
				<th><?php _e( 'Particle Count', 'confetti' ); ?></th>
				<td>
				<label><input name="particleCount" type="number" value="50" readonly data-default="50" /> <?php _e( 'Default', 'confetti' ); ?>: 50</label>
					<p class="description"><?php _e( 'The number of confetti to launch. More is always fun... but be cool, there is a lot of math involved.', 'confetti' ); ?></p>
				</td>
			</tr>
			<tr class="upgrade-option" style="display: none;">
				<th><?php _e( 'Launch Angle', 'confetti' ); ?></th>
				<td>
				<label><input name="angle" type="number" value="90" readonly data-default="90" /> <?php _e( 'Default', 'confetti' ); ?>: 90</label>
					<p class="description"><?php _e( 'The angle in which to launch the confetti, in degrees. 90 is straight up.', 'confetti' ); ?></p>
				</td>
			</tr>
			<tr class="upgrade-option" style="display: none;">
				<th><?php _e( 'Spread', 'confetti' ); ?></th>
				<td>
				<label><input name="spread" type="number" min="0" max="360" step="1" value="45" readonly data-default="45" /> <?php _e( 'Default', 'confetti' ); ?>: 45</label>
					<p class="description"><?php _e( 'How far off center the confetti can go, in degrees. 45 means the confetti will launch at the defined angle plus or minus 22.5 degrees.', 'confetti' ); ?></p>
				</td>
			</tr>
			<tr class="upgrade-option" style="display: none;">
				<th><?php _e( 'Start Velocity', 'confetti' ); ?></th>
				<td>
				<label><input name="startVelocity" type="number" value="45" readonly data-default="45" /> <?php _e( 'Default', 'confetti' ); ?>: 45</label>
					<p class="description"><?php _e( 'How fast the confetti will start going, in pixels.', 'confetti' ); ?></p>
				</td>
			</tr>
			<tr class="upgrade-option" style="display: none;">
				<th><?php _e( 'Decay', 'confetti' ); ?></th>
				<td>
				<label><input name="decay" type="number" min="0" max="1" step=".01" value=".9" readonly data-default=".9" /> <?php _e( 'Default', 'confetti' ); ?>: .9</label>
					<p class="description"><?php _e( 'How quickly the confetti will lose speed. Keep this number between 0 and 1, otherwise the confetti will gain speed. Better yet, just never change it.', 'confetti' ); ?></p>
				</td>
			</tr>
			<tr class="upgrade-option" style="display: none;">
				<th><?php _e( 'Gravity', 'confetti' ); ?></th>
				<td>
				<label><input name="gravity" type="number" min="0" max="1" step=".01" value="1" readonly data-default="1" /> <?php _e( 'Default', 'confetti' ); ?>: 1</label>
					<p class="description"><?php _e( 'How quickly the particles are pulled down. 1 is full gravity, 0.5 is half gravity, etc., but there are no limits. You can even make particles go up if you would like.', 'confetti' ); ?></p>
				</td>
			</tr>
			<tr class="upgrade-option" style="display: none;">
				<th><?php _e( 'Drift', 'confetti' ); ?></th>
				<td>
				<label><input name="drift" type="number" step=".1" value="0" readonly data-default="0" /> <?php _e( 'Default', 'confetti' ); ?>: 0</label>
					<p class="description"><?php _e( 'How much to the side the confetti will drift. The default is 0, meaning that they will fall straight down. Use a negative number for left and positive number for right.', 'confetti' ); ?></p>
				</td>
			</tr>
			<tr class="upgrade-option" style="display: none;">
				<th><?php _e( 'Ticks', 'confetti' ); ?></th>
				<td>
				<label><input name="ticks" type="number" min="0" value="200" readonly data-default="200" /> <?php _e( 'Default', 'confetti' ); ?>: 200</label>
					<p class="description"><?php _e( 'How many times the confetti will move. This is abstract... but play with it if the confetti disappear too quickly for you.', 'confetti' ); ?></p>
				</td>
			</tr>
			<tr class="upgrade-option" style="display: none;">
				<th><?php _e( 'Scalar', 'confetti' ); ?></th>
				<td>
				<label><input name="scalar" type="number" min="0" step=".01" value="1" readonly data-default="1" /> <?php _e( 'Default', 'confetti' ); ?>: 1</label>
					<p class="description"><?php _e( 'Scale factor for each confetti particle. Use decimals to make the confetti smaller. Go on, try teeny tiny confetti, they are adorable!', 'confetti' ); ?></p>
				</td>
			</tr>
			<tr class="upgrade-option" style="display: none;">
				<th><?php _e( 'Z-Index', 'confetti' ); ?></th>
				<td>
				<label><input name="zindex" type="number" value="100" readonly data-default="100" /> <?php _e( 'Default', 'confetti' ); ?>: 100</label>
					<p class="description"><?php _e( 'The confetti should be on top, after all. But if you have a crazy high page, you can set it even higher.', 'confetti' ); ?></p>
				</td>
			</tr>
			<tr class="upgrade-option" style="display: none;">
				<th><?php _e( 'Origin', 'confetti' ); ?></th>
				<td>
				<label>X <input name="origin_x" type="number" min="0" max="1" step="0.01" value=".5" readonly data-default=".5" /> <?php _e( 'Default', 'confetti' ); ?>: .5</label><br />
				<label>Y <input name="origin_y" type="number" min="0" max="1" step="0.01" value=".5" readonly data-default=".5" /> <?php _e( 'Default', 'confetti' ); ?>: .5</label>
					<p class="description">
						<?php _e( 'Where to start firing confetti from. Feel free to launch off-screen if you would like with negative numbers.', 'confetti' ); ?><br />
						<?php _e( 'X = Left to right with 0 left edge and 1 right edge, Y = Top to bottom with 0 top edge and 1 bottom edge', 'confetti' ); ?>
					</p>
				</td>
			</tr>

			<script>
				jQuery( document ).ready( function() {
					jQuery( 'input[readonly]' ).on( 'click', function() {
						// Open the .wps-promo-featured div in thickbox
					tb_show( '<?php _e( 'Upgrade to Premium', 'confetti' ); ?>', '#TB_inline?width=400&inlineId=wps-confetti-upgrade-premium' );
					});
				});
			</script>
	<?php
}

// Add instance promo popup (can be unhooked by premium)
add_action( 'admin_footer', 'wps_confetti_add_instance_promo' );
function wps_confetti_add_instance_promo() {
	?>
		<script>
			jQuery( document ).ready(function($) {
				$( '#wps-add-instance' ).on( 'click', function(e){
					e.preventDefault();
					tb_show( '<?php _e( 'Add New Instance - Premium Feature', 'confetti' ); ?>', '#TB_inline?width=600&height=460&inlineId=wps-add-instance-promo' );
					return false;
				});
			});
		</script>

		<div id="wps-add-instance-promo" style="display:none;">
			<div class="wps-promo wps-promo-featured">
				<h3><?php _e( 'Add New Confetti Instances', 'confetti' ); ?></h3>
				<p><?php _e( 'Create multiple confetti instances with different styles and settings. Perfect for different pages, events, or user interactions.', 'confetti' ); ?></p>
				
				<h3><?php _e( 'Premium Features:', 'confetti' ); ?></h3>
				<ul>
					<li><?php _e( 'Unlimited confetti instances', 'confetti' ); ?></li>
					<li><?php _e( 'Advanced customization options per instance', 'confetti' ); ?></li>
					<li><?php _e( 'Priority support and updates', 'confetti' ); ?></li>
				</ul>

				<div style="text-align: center; margin-top: 30px;">
					<a href="https://wpsunshine.com/plugins/confetti/?utm_source=plugin&utm_medium=popup&utm_campaign=confetti" target="_blank" class="button">
					<?php _e( 'Upgrade to Premium', 'confetti' ); ?>
					</a>
					<p class="wps-promo-price">
					<?php _e( 'Starting at $19/year', 'confetti' ); ?> &mdash; <?php _e( '14 days money back guarantee', 'confetti' ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php
}

?>

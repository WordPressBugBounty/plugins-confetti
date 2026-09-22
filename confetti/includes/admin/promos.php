<?php
/**
 * Promotional functions to get users to upgrade.
 *
 * Everything a free user sees about premium is built from the same data the
 * premium version runs on: the style catalog, options.json and the integration
 * list. No counts or feature lists are typed out by hand, so this page cannot
 * fall out of date with what premium actually gives you.
 *
 * @package WPSConfetti\promos
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The styles this site cannot run yet.
 *
 * @return array Style definitions keyed by ID.
 */
function wps_confetti_locked_styles() {
	$available = WPSunshine_Confetti_Styles::get_available();
	return array_diff_key( WPSunshine_Confetti_Styles::get_all(), $available );
}

/**
 * A few names from a list, for "Vortex, Grand Finale, Confetti Type and more".
 *
 * @param array $names How many names there are to pick from.
 * @param int   $count How many to name.
 * @return string
 */
function wps_confetti_name_a_few( $names, $count = 3 ) {
	return implode( ', ', array_slice( array_values( $names ), 0, $count ) );
}

/**
 * The same thing, but for integrations, where the alphabetically first names
 * are not the ones people recognise. Anything on the headline list that is
 * really registered goes first, then whatever else is in the list, so this
 * still cannot name a plugin Confetti does not actually integrate with.
 *
 * @param array $names Every integration name.
 * @param int   $count How many to name.
 * @return string
 */
function wps_confetti_name_a_few_integrations( $names, $count = 3 ) {

	$headline = array( 'WooCommerce', 'WPForms', 'Gravity Forms', 'LearnDash', 'Easy Digital Downloads', 'Elementor' );
	$known    = array_values( array_intersect( $headline, $names ) );
	$rest     = array_values( array_diff( $names, $known ) );

	return wps_confetti_name_a_few( array_merge( $known, $rest ), $count );
}

/**
 * Show the upgrade notice in header.
 */
function wps_confetti_header_upgrade() {
	echo '<a href="https://www.wpsunshine.com/plugins/confetti/?utm_source=plugin&utm_medium=button&utm_content=upgrade&utm_campaign=plugin_upgrade" target="_blank" class="wps-button" id="wps-confetti-header-upgrade">' . esc_html__( 'Upgrade to premium!', 'confetti' ) . '</a>';
}
add_action( 'wps_confetti_header', 'wps_confetti_header_upgrade' );

add_action( 'wps_confetti_options_behavior', 'wps_confetti_locked_behavior_options', 10, 2 );
/**
 * The behavior options, locked, so people can see what they would get.
 *
 * The rows come from styles/options.json, the same file the premium version
 * renders its real options from, so the two can never drift apart.
 *
 * @param array  $instance            Current instance settings.
 * @param string $current_instance_id Instance being edited.
 */
function wps_confetti_locked_behavior_options( $instance, $current_instance_id ) {

	$options = WPSunshine_Confetti_Styles::get_options_in_group( 'behavior' );

	if ( empty( $options ) ) {
		return;
	}

	echo '<div class="wps-locked-group">';

	foreach ( $options as $option_id => $option ) {
		wps_confetti_locked_option_row( $option_id, $option );
	}

	wps_confetti_unlock_overlay(
		__( 'Unlock all behavior options', 'confetti' ),
		__( 'Fine-tune how it moves', 'confetti' ),
		sprintf(
			/* translators: %s: a few behavior option names. */
			__( '%s and more are part of Premium.', 'confetti' ),
			wps_confetti_name_a_few( wp_list_pluck( $options, 'label' ), 3 )
		)
	);

	echo '</div>';
}

add_action( 'wps_confetti_options_physics', 'wps_confetti_locked_physics_options', 10, 2 );
/**
 * The advanced physics options, locked. Core opens the section around these,
 * so opening it is what reveals the upgrade notice.
 *
 * @param array  $instance            Current instance settings.
 * @param string $current_instance_id Instance being edited.
 */
function wps_confetti_locked_physics_options( $instance, $current_instance_id ) {

	$options = WPSunshine_Confetti_Styles::get_options_in_group( 'physics' );

	if ( empty( $options ) ) {
		return;
	}

	echo '<div class="wps-locked-group">';

	foreach ( $options as $option_id => $option ) {
		wps_confetti_locked_option_row( $option_id, $option );
	}

	wps_confetti_unlock_overlay(
		__( 'Unlock all physics controls', 'confetti' ),
		__( 'Take control of the physics', 'confetti' ),
		sprintf(
			/* translators: %s: a few physics option names. */
			__( '%s and more are part of Premium.', 'confetti' ),
			wps_confetti_name_a_few( wp_list_pluck( $options, 'label' ), 3 )
		)
	);

	echo '</div>';
}

add_action( 'wps_confetti_instance_panels', 'wps_confetti_locked_appearance_panel', 10, 2 );
/**
 * The appearance panel, locked behind an unlock button.
 *
 * @param array  $instance            Current instance settings.
 * @param string $current_instance_id Instance being edited.
 */
function wps_confetti_locked_appearance_panel( $instance, $current_instance_id ) {

	$options = WPSunshine_Confetti_Styles::get_options_in_group( 'appearance' );

	if ( empty( $options ) ) {
		return;
	}

	WPSunshine_Confetti_Options::panel_open(
		'appearance',
		__( 'Appearance', 'confetti' ),
		array(
			'badge'       => __( 'Premium', 'confetti' ),
			'locked'      => true,
			'collapsible' => true,
		)
	);

	foreach ( $options as $option_id => $option ) {
		wps_confetti_locked_option_row( $option_id, $option );
	}

	wps_confetti_unlock_overlay(
		sprintf(
			/* translators: %s: the appearance option names. */
			__( 'Unlock %s', 'confetti' ),
			WPSunshine_Confetti_Styles::label_list( wp_list_pluck( $options, 'label' ) )
		),
		__( 'Make it match your brand', 'confetti' ),
		sprintf(
			/* translators: %s: the appearance option names. */
			__( '%s are part of Premium.', 'confetti' ),
			WPSunshine_Confetti_Styles::label_list( wp_list_pluck( $options, 'label' ) )
		)
	);

	WPSunshine_Confetti_Options::panel_close();
}

/**
 * The button that floats over a locked group.
 *
 * @param string $label Button text.
 */
function wps_confetti_unlock_overlay( $label, $title = '', $sub = '' ) {
	?>
	<div class="wps-unlock-overlay">
		<a href="#" class="button button-primary wps-confetti-upgrade-locked" data-upgrade-title="<?php echo esc_attr( $title ); ?>" data-upgrade-sub="<?php echo esc_attr( $sub ); ?>">
			<span class="dashicons dashicons-lock"></span> <?php echo esc_html( $label ); ?>
		</a>
	</div>
	<?php
}

/**
 * One locked option row for the free version. It looks like the real control
 * so people can see exactly what they are buying, it just cannot be changed.
 *
 * @param string $option_id Option ID.
 * @param array  $option    Option definition.
 */
function wps_confetti_locked_option_row( $option_id, $option ) {

	$styles = WPSunshine_Confetti_Styles::get_styles_for_option( $option_id );

	if ( empty( $styles ) ) {
		return;
	}

	$render  = isset( $option['render'] ) ? $option['render'] : 'input';
	$default = isset( $option['default'] ) ? $option['default'] : '';

	WPSunshine_Confetti_Options::option_row_open( $option['label'], $styles );

	switch ( $render ) {

		case 'colors':
			echo '<div class="wps-swatches">';
			foreach ( array( '#a8e6ff', '#c9b6ff', '#ffb3c7', '#c3f0b4' ) as $color ) {
				echo '<span class="wps-color-box" style="background-color: ' . esc_attr( $color ) . ';"></span>';
			}
			echo '<span class="wps-swatch-add">' . esc_html__( '+ Add', 'confetti' ) . '</span>';
			echo '</div>';
			break;

		case 'shapes':
			echo '<div class="wps-choice-pills">';
			foreach ( WPSunshine_Confetti_Options::get_shape_choices() as $shape ) {
				echo '<span class="wps-choice-pill"><span class="wps-choice-pill__icon" aria-hidden="true">' . esc_html( $shape['icon'] ) . '</span><span class="wps-choice-pill__label">' . esc_html( $shape['label'] ) . '</span></span>';
			}
			echo '</div>';
			break;

		case 'svgs':
			echo '<div class="wps-tiles"><span class="wps-tile wps-tile--add">' . esc_html__( '+ Add', 'confetti' ) . '</span></div>';
			break;

		case 'emojis':
			echo '<div class="wps-tiles">';
			foreach ( array( '🎉', '🎊' ) as $emoji ) {
				echo '<span class="wps-tile">' . esc_html( $emoji ) . '</span>';
			}
			echo '<span class="wps-tile wps-tile--add">' . esc_html__( '+ Add', 'confetti' ) . '</span>';
			echo '</div>';
			break;

		case 'origin':
			?>
			<div class="wps-origin__fields">
				<label>X <input name="origin_x" type="number" step="0.01" value=".5" readonly data-default=".5" /></label>
				<label>Y <input name="origin_y" type="number" step="0.01" value=".5" readonly data-default=".5" /></label>
			</div>
			<?php
			break;

		case 'overlay':
			?>
			<label><input type="checkbox" disabled /> <?php esc_html_e( 'Cover the screen with a message', 'confetti' ); ?></label>
			<?php
			break;

		default:
			if ( isset( $option['type'] ) && 'checkbox' === $option['type'] ) {
				?>
				<label><input type="checkbox" disabled /> <?php echo esc_html( $option['checkbox_label'] ); ?></label>
				<?php
			} else {
				WPSunshine_Confetti_Options::render_number_option( $option_id, $option, $default, true );
			}
			break;
	}

	WPSunshine_Confetti_Options::option_row_close( isset( $option['description'] ) ? $option['description'] : '' );
}

add_action( 'wps_confetti_instance_sidebar', 'wps_confetti_sidebar_upgrade', 20, 2 );
/**
 * The upgrade card in the sidebar. Every number in it is counted, never typed.
 *
 * @param array  $instance            Current instance settings.
 * @param string $current_instance_id Instance being edited.
 */
function wps_confetti_sidebar_upgrade( $instance, $current_instance_id ) {

	$locked_styles = wps_confetti_locked_styles();
	$integrations  = WPS_Confetti()->get_integration_names();
	$appearance    = WPSunshine_Confetti_Styles::get_options_in_group( 'appearance' );
	?>
	<div class="wps-card wps-card--upgrade">
		<p class="wps-card__eyebrow"><?php esc_html_e( 'Confetti Premium', 'confetti' ); ?></p>
		<h3>
			<?php
			printf(
				/* translators: 1: number of premium styles, 2: number of plugin integrations. */
				esc_html__( '%1$d more styles, brand colors & %2$d plugin integrations', 'confetti' ),
				count( $locked_styles ),
				count( $integrations )
			);
			?>
		</h3>
		<ul>
			<li><?php esc_html_e( 'Unlimited confetti instances', 'confetti' ); ?></li>
			<li><?php echo esc_html( WPSunshine_Confetti_Styles::label_list( wp_list_pluck( $appearance, 'label' ) ) ); ?></li>
			<li>
				<?php
				printf(
					/* translators: 1: a few plugin names, 2: how many more there are. */
					esc_html__( '%1$s and %2$d more plugins', 'confetti' ),
					esc_html( wps_confetti_name_a_few_integrations( $integrations ) ),
					esc_html( max( 0, count( $integrations ) - 3 ) )
				);
				?>
			</li>
		</ul>
		<p>
			<a href="https://wpsunshine.com/plugins/confetti/?utm_source=plugin&utm_medium=sidebar&utm_content=upgrade&utm_campaign=plugin_upgrade" target="_blank" class="button button-primary">
				<?php esc_html_e( 'Upgrade — from $19/yr', 'confetti' ); ?>
			</a>
		</p>
		<p class="wps-card__fineprint"><?php esc_html_e( '14-day money back guarantee', 'confetti' ); ?></p>
	</div>
	<?php
}

add_action( 'wps_confetti_instance_sidebar', 'wps_confetti_sidebar_more_plugins', 30, 2 );
/**
 * The other WP Sunshine plugins, at the bottom of the sidebar.
 */
function wps_confetti_sidebar_more_plugins() {

	$plugins = array(
		array(
			'name'  => 'Conversion Bridge',
			'blurb' => __( 'No code analytics and conversion tracking for 70+ plugin integrations, 20+ analytics platforms, 9 ad platforms.', 'confetti' ),
			'url'   => 'https://conversionbridgewp.com/',
		),
		array(
			'name'  => 'Address Autocomplete Anything',
			'blurb' => __( 'Address autocomplete on any form.', 'confetti' ),
			'url'   => 'https://wpsunshine.com/plugins/address-autocomplete/',
		),
	);
	?>
	<div class="wps-card wps-card--plugins">
		<p class="wps-card__eyebrow"><?php esc_html_e( 'More from WP Sunshine', 'confetti' ); ?></p>
		<ul>
			<?php foreach ( $plugins as $plugin ) : ?>
				<li>
					<a href="<?php echo esc_url( $plugin['url'] ); ?>?utm_source=plugin&utm_medium=sidebar&utm_campaign=confetti" target="_blank"><?php echo esc_html( $plugin['name'] ); ?></a>
					<span class="description"><?php echo esc_html( $plugin['blurb'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}

add_action( 'admin_footer', 'wps_confetti_upgrade_popup' );
/**
 * The popup every locked control opens, and the script that opens it.
 *
 * The heading changes to match whatever was clicked, so the popup answers the
 * question the person actually asked. Triggers set data-upgrade-title and
 * data-upgrade-sub; anything that does not falls back to the generic wording.
 */
function wps_confetti_upgrade_popup() {

	if ( ! isset( $_GET['page'] ) || 'wps_confetti' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$locked_styles = wps_confetti_locked_styles();
	$integrations  = WPS_Confetti()->get_integration_names();
	$appearance    = WPSunshine_Confetti_Styles::get_options_in_group( 'appearance' );
	$physics       = WPSunshine_Confetti_Styles::get_options_in_group( 'physics' );

	// Counted, never typed, so this list cannot promise the wrong thing.
	$selling_points = array(
		WPSunshine_Confetti_Styles::label_list( wp_list_pluck( $appearance, 'label' ) ),
		sprintf(
			/* translators: %d: number of premium styles. */
			_n( '%d extra style', '%d extra styles', count( $locked_styles ), 'confetti' ),
			count( $locked_styles )
		),
		__( 'Unlimited instances', 'confetti' ),
		sprintf(
			/* translators: %d: number of plugin integrations. */
			_n( '%d plugin integration', '%d plugin integrations', count( $integrations ), 'confetti' ),
			count( $integrations )
		),
		sprintf(
			/* translators: %d: number of advanced physics options. */
			_n( '%d physics control', '%d physics controls', count( $physics ), 'confetti' ),
			count( $physics )
		),
		__( 'Trigger on scroll into view', 'confetti' ),
	);
	?>
	<div id="wps-confetti-upgrade-premium" style="display:none;">
		<div class="wps-upgrade">

			<div class="wps-upgrade__head">
				<p class="wps-upgrade__eyebrow"><?php esc_html_e( 'Confetti Premium', 'confetti' ); ?></p>
				<h2 class="wps-upgrade__title"><?php esc_html_e( 'Unlock the whole thing', 'confetti' ); ?></h2>
				<p class="wps-upgrade__sub"><?php esc_html_e( 'Everything below is part of Premium.', 'confetti' ); ?></p>
			</div>

			<div class="wps-upgrade__body">
				<ul class="wps-upgrade__list">
					<?php foreach ( $selling_points as $point ) : ?>
						<li><?php echo esc_html( $point ); ?></li>
					<?php endforeach; ?>
				</ul>

				<div class="wps-upgrade__actions">
					<a href="https://www.wpsunshine.com/plugins/confetti/?utm_source=plugin&utm_medium=popup&utm_content=upgrade&utm_campaign=plugin_upgrade" target="_blank" class="button button-primary">
						<?php esc_html_e( 'Upgrade — from $19/yr', 'confetti' ); ?>
					</a>
					<span class="wps-upgrade__fineprint"><?php esc_html_e( '14-day money back guarantee', 'confetti' ); ?></span>
					<a href="#" class="wps-upgrade__dismiss"><?php esc_html_e( 'Maybe later', 'confetti' ); ?></a>
				</div>
			</div>

		</div>
	</div>

	<script>
		jQuery( document ).ready( function( $ ) {

			var defaults = {
				title: <?php echo wp_json_encode( __( 'Unlock the whole thing', 'confetti' ) ); ?>,
				sub: <?php echo wp_json_encode( __( 'Everything below is part of Premium.', 'confetti' ) ); ?>
			};

			function wps_show_upgrade( $trigger ) {

				var title = $trigger.data( 'upgrade-title' );
				var sub   = $trigger.data( 'upgrade-sub' );

				// A locked style card names the style you just reached for.
				if ( ! title && $trigger.hasClass( 'wps-style-card' ) ) {
					title = $trigger.data( 'name' );
					sub   = <?php echo wp_json_encode( __( 'This style is part of Premium.', 'confetti' ) ); ?>;
				}

				$( '.wps-upgrade__title' ).text( title || defaults.title );
				$( '.wps-upgrade__sub' ).text( sub || defaults.sub );

				tb_show( '', '#TB_inline?width=620&inlineId=wps-confetti-upgrade-premium' );
			}

			// Anything locked opens the same popup.
			$( document ).on( 'click', '.wps-confetti-upgrade-locked, .wps-style-card.is-locked, .wps-panel.is-locked input, .wps-panel.is-locked .wps-tile, .wps-locked-group input[readonly], #wps-add-instance.is-locked', function( e ) {
				e.preventDefault();
				wps_show_upgrade( $( this ) );
			});

			$( document ).on( 'click', '.wps-upgrade__dismiss', function( e ) {
				e.preventDefault();
				tb_remove();
			});

		});
	</script>
	<?php
}

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

/**
 * Confetti runner.
 *
 * This file does not know about any individual style. It works out the
 * settings for a run, then hands them to whichever style registered itself
 * under that name. Each style lives in its own file in assets/js/styles/.
 *
 * A style registers itself like this:
 *
 *   WPSConfetti.register( 'my_style', {
 *       colors: [ '#fff' ],        // used only when no custom colors are set
 *       shapes: [ 'circle' ],      // used only when no shapes are chosen
 *       originSelector: true,      // can launch from a CSS selector element
 *       run: function ( settings, api ) { ... }
 *   } );
 *
 * See STYLES.md for the full API.
 */

window.WPSConfetti = window.WPSConfetti || ( function () {

	var styles = {};
	var timers = [];
	var current_stop = null;
	var pointer = null;

	/**
	 * Register a style. Accepts a plain function as a shorthand for { run: fn }.
	 */
	function register( id, definition ) {
		if ( typeof definition === 'function' ) {
			definition = { run: definition };
		}
		if ( ! definition || typeof definition.run !== 'function' ) {
			console.warn( 'Confetti: style "' + id + '" has no run function.' );
			return;
		}
		styles[ id ] = definition;
	}

	function get( id ) {
		return styles[ id ];
	}

	/**
	 * Repeat a callback. Calling api.done() inside the callback stops that
	 * repeat and nothing else, so two styles can run side by side.
	 */
	function every( ms, fn ) {
		var id = setInterval(
			function () {
				var previous = current_stop;
				current_stop = function () {
					clearInterval( id );
				};
				try {
					fn();
				} finally {
					current_stop = previous;
				}
			},
			ms
		);
		timers.push( id );
		return id;
	}

	function later( ms, fn ) {
		var id = setTimeout( fn, ms );
		timers.push( id );
		return id;
	}

	function done() {
		if ( current_stop ) {
			current_stop();
		}
	}

	/**
	 * Stop everything currently running and clear the screen. Used by the
	 * admin preview so repeated clicks do not stack up.
	 */
	function reset() {
		timers.forEach( clearInterval );
		timers.forEach( clearTimeout );
		timers = [];
		if ( typeof confetti !== 'undefined' && confetti.reset ) {
			confetti.reset();
		}
	}

	function random( min, max ) {
		return Math.random() * ( max - min ) + min;
	}

	function random_color( colors ) {
		return colors[ Math.floor( Math.random() * colors.length ) ];
	}

	/**
	 * Wait, using a tracked timer. reset() clears it, which leaves the promise
	 * unresolved on purpose so a style waiting on it simply stops there.
	 */
	function sleep( ms ) {
		return new Promise( function ( resolve ) {
			later( ms, resolve );
		} );
	}

	/**
	 * Start following the mouse or finger. Only attached when a style asks
	 * for it, so pages that do not need it carry no listeners.
	 */
	function track_pointer() {

		if ( pointer ) {
			return pointer;
		}

		pointer = { x: 0.5, y: 0.5 };

		window.addEventListener(
			'mousemove',
			function ( e ) {
				pointer.x = e.clientX / window.innerWidth;
				pointer.y = e.clientY / window.innerHeight;
			}
		);

		window.addEventListener(
			'touchmove',
			function ( e ) {
				if ( ! e.touches.length ) {
					return;
				}
				pointer.x = e.touches[ 0 ].clientX / window.innerWidth;
				pointer.y = e.touches[ 0 ].clientY / window.innerHeight;
			},
			{ passive: true }
		);

		return pointer;
	}

	return {
		styles: styles,
		register: register,
		get: get,
		every: every,
		later: later,
		done: done,
		reset: reset,
		random: random,
		randomColor: random_color,
		sleep: sleep,
		trackPointer: track_pointer,
		shape: {}
	};

}() );

/**
 * The rainbow palette used when a style wants colors but none are set.
 */
var wps_confetti_default_colors = [ '#26ccff', '#a25afd', '#ff5e7e', '#88ff5a', '#fcff42', '#ffa62d', '#ff36ff' ];

/**
 * Run confetti.
 *
 * @param {string|object} instance_id_or_settings Instance ID or a settings object.
 */
async function wps_run_confetti( instance_id_or_settings = {} ) {

	// Shapes drawn from a path, shared by any style that wants them.
	if ( ! WPSConfetti.shape.triangle ) {
		WPSConfetti.shape.triangle = confetti.shapeFromPath( { path: 'M0 10 L5 0 L10 10z' } );
		WPSConfetti.shape.star     = confetti.shapeFromPath( { path: 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z' } );
	}

	// Handle both instance ID (string) and settings object.
	var instance_settings = {};
	if ( typeof instance_id_or_settings === 'string' ) {
		if ( typeof confetti_instances !== 'undefined' && confetti_instances[ instance_id_or_settings ] ) {
			// Copy it so a repeat run does not inherit changes made below.
			instance_settings = Object.assign( {}, confetti_instances[ instance_id_or_settings ] );
		} else {
			console.warn( 'Confetti instance not found:', instance_id_or_settings );
			return;
		}
	} else if ( typeof instance_id_or_settings === 'object' && instance_id_or_settings !== null ) {
		instance_settings = Object.assign( {}, instance_id_or_settings );
	}

	// Show once check - bail early if this instance was already shown.
	if ( instance_settings.show_once && instance_settings.show_once != '0' ) {
		var storage_key = 'wps_confetti_shown_' + ( instance_settings.id || 'default' );
		if ( localStorage.getItem( storage_key ) ) {
			return;
		}
		localStorage.setItem( storage_key, '1' );
	}


	// Handle CSS selector as origin (string) - check FIRST before converting origin_x/origin_y.
	var origin_selector = null;
	if ( typeof instance_settings.origin === 'string' && instance_settings.origin ) {
		origin_selector = instance_settings.origin;
		delete instance_settings.origin_x;
		delete instance_settings.origin_y;
	} else if ( instance_settings.origin_x !== undefined || instance_settings.origin_y !== undefined ) {
		instance_settings.origin = {
			x: instance_settings.origin_x !== undefined && instance_settings.origin_x !== '' ? parseFloat( instance_settings.origin_x ) : 0.5,
			y: instance_settings.origin_y !== undefined && instance_settings.origin_y !== '' ? parseFloat( instance_settings.origin_y ) : 0.5
		};
		delete instance_settings.origin_x;
		delete instance_settings.origin_y;
	}

	// A blank coordinate would be treated as 0 by canvas-confetti and fire
	// from the top left corner, so fall back to the center instead.
	if ( instance_settings.origin && typeof instance_settings.origin === 'object' ) {
		var origin_x = parseFloat( instance_settings.origin.x );
		var origin_y = parseFloat( instance_settings.origin.y );
		instance_settings.origin = {
			x: isNaN( origin_x ) ? 0.5 : origin_x,
			y: isNaN( origin_y ) ? 0.5 : origin_y
		};
	}

	// Remove empty values (but keep '0' as it is a valid value), and keep a
	// string origin because it is a CSS selector we still need.
	for ( var key in instance_settings ) {
		if ( instance_settings.hasOwnProperty( key ) ) {
			var value = instance_settings[ key ];
			if ( 'origin' === key && typeof value === 'string' && value ) {
				continue;
			}
			if ( value === '' ||
				( Array.isArray( value ) && value.length === 0 ) ||
				( typeof value === 'object' && value !== null && Object.keys( value ).length === 0 ) ||
				value === null ||
				value === undefined ) {
				delete instance_settings[ key ];
			}
		}
	}

	if ( instance_settings.delay > 0 ) {
		await wps_confetti_sleep( instance_settings.delay * 1000 );
	}


	// Work out which style is running before touching shapes or colors,
	// because a style can supply its own defaults for both.
	var style_id  = instance_settings.style || 'cannon';
	var style_def = WPSConfetti.get( style_id );

	if ( ! style_def ) {
		console.warn( 'Confetti: style "' + style_id + '" is not loaded, falling back to cannon.' );
		style_def = WPSConfetti.get( 'cannon' );
		if ( ! style_def ) {
			return;
		}
	}

	// Build the shape list: custom SVGs, then emojis, then standard shapes.
	var custom_shapes = [];

	if ( instance_settings.svgs && Array.isArray( instance_settings.svgs ) && instance_settings.svgs.length > 0 ) {
		instance_settings.svgs.forEach(
			function ( svg_data ) {
				if ( ! svg_data.path ) {
					return;
				}
				try {
					custom_shapes.push( confetti.shapeFromPath( { path: svg_data.path } ) );
				} catch ( e ) {
					console.warn( 'Error creating SVG shape:', e );
				}
			}
		);
	}

	if ( instance_settings.emojis && Array.isArray( instance_settings.emojis ) && instance_settings.emojis.length > 0 ) {
		var emoji_scalar = instance_settings.scalar || 1;
		instance_settings.emojis.forEach(
			function ( emoji ) {
				try {
					custom_shapes.push( confetti.shapeFromText( { text: emoji, scalar: emoji_scalar } ) );
				} catch ( e ) {
					console.warn( 'Error creating emoji shape:', e );
				}
			}
		);
	}

	var chosen_shapes = instance_settings.shapes;

	// Nothing chosen at all, so let the style pick if it cares.
	if ( ( ! chosen_shapes || ! chosen_shapes.length ) && ! custom_shapes.length && style_def.shapes ) {
		chosen_shapes = style_def.shapes.slice();
	}

	if ( chosen_shapes && Array.isArray( chosen_shapes ) && chosen_shapes.length > 0 ) {
		chosen_shapes.forEach(
			function ( shape ) {
				if ( 'triangle' === shape ) {
					custom_shapes.push( WPSConfetti.shape.triangle );
				} else if ( 'star' === shape ) {
					custom_shapes.push( WPSConfetti.shape.star );
				} else {
					custom_shapes.push( shape );
				}
			}
		);
	}

	if ( custom_shapes.length > 0 ) {
		instance_settings.shapes = custom_shapes;
	} else {
		delete instance_settings.shapes;
	}

	// Colors: the style only gets a say when the user has not set any.
	if ( ( ! instance_settings.colors || ! instance_settings.colors.length ) && style_def.colors ) {
		instance_settings.colors = style_def.colors.slice();
	}

	var defaults = {
		style: 'cannon',
		duration: 3,
		speed: 75,
		particleCount: 50,
		angle: 90,
		spread: 45,
		startVelocity: 45,
		decay: .9,
		gravity: 1,
		drift: 0,
		ticks: 200,
		origin: { x: .5, y: .5 },
		scalar: 1,
		// The highest value a browser accepts. Popup plugins routinely use
		// z-index values in the hundreds of millions - MailPoet, Popup Builder
		// and Hustle all do - so anything lower means confetti fires behind the
		// very popup it is celebrating. The canvas is pointer-events: none and
		// clears itself, so sitting on top costs nothing.
		zIndex: 2147483647,
		disableForReducedMotion: 0,
		delay: 0
	};

	// Merge instance settings over the defaults. The defaults carry an origin
	// object, so a CSS selector origin has to be put back afterwards.
	instance_settings = Object.assign( defaults, instance_settings );

	if ( origin_selector ) {
		instance_settings.origin = origin_selector;
	}

	// Turn a CSS selector origin into coordinates, for the styles that fire
	// from a single point and can therefore honour it.
	if ( origin_selector && style_def.originSelector ) {
		var selector_element = document.querySelector( origin_selector );
		if ( selector_element ) {
			var rect          = selector_element.getBoundingClientRect();
			var window_width  = window.innerWidth || document.documentElement.clientWidth;
			var window_height = window.innerHeight || document.documentElement.clientHeight;
			var x             = ( rect.left + rect.width / 2 ) / window_width;
			var y             = ( rect.top + rect.height / 2 ) / window_height;

			instance_settings.origin = {
				x: Math.max( 0, Math.min( 1, x ) ),
				y: Math.max( 0, Math.min( 1, y ) )
			};
		} else {
			console.warn( 'Confetti: origin selector not found:', origin_selector );
			instance_settings.origin = { x: .5, y: .5 };
		}
	} else if ( typeof instance_settings.origin === 'string' ) {
		// A selector was given to a style that cannot use one.
		instance_settings.origin = { x: .5, y: .5 };
	} else if ( ! instance_settings.origin || typeof instance_settings.origin !== 'object' ) {
		instance_settings.origin = { x: .5, y: .5 };
	}

	style_def.run( instance_settings, wps_confetti_api( instance_settings ) );

}

/**
 * Build the helper object handed to a style, with the settings for this run
 * baked into api.fire() so styles do not have to merge them by hand.
 *
 * @param {object} settings Resolved settings for this run.
 */
function wps_confetti_api( settings ) {
	return {
		fire: function ( overrides ) {
			var options = Object.assign( {}, settings, overrides || {} );

			// canvas-confetti hands colors out in list order, so a style that
			// fires one or two particles per call would paint every one of
			// them the first color. Start each call at a random point in the
			// list so small bursts still mix the whole palette.
			var colors = options.colors && options.colors.length ? options.colors : wps_confetti_default_colors;
			var offset = Math.floor( Math.random() * colors.length );
			options.colors = colors.slice( offset ).concat( colors.slice( 0, offset ) );

			return confetti( options );
		},
		every: WPSConfetti.every,
		later: WPSConfetti.later,
		done: WPSConfetti.done,
		sleep: WPSConfetti.sleep,
		random: WPSConfetti.random,
		randomColor: WPSConfetti.randomColor,
		trackPointer: WPSConfetti.trackPointer,
		shape: WPSConfetti.shape,
		defaultColors: wps_confetti_default_colors,

		/**
		 * Turn the 1-100 speed setting into a gap between bursts, in
		 * milliseconds. Higher speed means a shorter gap.
		 */
		gap: function () {
			var speed = parseFloat( settings.speed );
			if ( isNaN( speed ) ) {
				speed = 75;
			}
			return Math.max( 16, Math.round( 420 - ( speed * 3.8 ) ) );
		}
	};
}

/**
 * Everything below keeps 1.x working.
 *
 * Up to and including 1.3.9 there were three public ways to fire confetti, and
 * people used all of them in themes and custom code:
 *
 *   wps_launch_confetti_cannon()                  a global function
 *   document.dispatchEvent( new Event('confetti') ) a document event
 *   <button class="wps-confetti">                 a click on that class
 *
 * They all ran the site's saved settings, which is now the default instance.
 */
function wps_launch_confetti_cannon() {
	wps_run_confetti( 'default' );
}

document.addEventListener( 'confetti', function () {
	wps_launch_confetti_cannon();
} );

/**
 * Fire confetti from a class or data attribute on any clickable element, with
 * no saved instance needed. Handy for demo pages and buttons built in the
 * block editor, where "Additional CSS class" is the only field on offer.
 *
 *   class="wps-confetti"                       the default instance
 *   class="wps-confetti-instance-{id}"         a saved instance
 *   class="wps-confetti-style-{style}"         the default instance, with that style swapped in
 *   class="wps-confetti-instance-{id} wps-confetti-style-{style}"  both
 *
 * data-wps-confetti-instance="{id}" and data-wps-confetti-style="{style}" do
 * the same thing as the matching classes.
 *
 * The scripts only load when the page content uses one of these; see
 * WPSunshine_Confetti::maybe_enqueue_for_content().
 */
var wps_confetti_click_selector = '.wps-confetti, [class*="wps-confetti-style-"], [class*="wps-confetti-instance-"], [data-wps-confetti-style], [data-wps-confetti-instance]';

function wps_confetti_read_trigger( el, name ) {
	var value = el.getAttribute( 'data-wps-confetti-' + name );
	if ( value ) {
		return value;
	}
	var match = ( el.getAttribute( 'class' ) || '' ).match( new RegExp( '(?:^|\\s)wps-confetti-' + name + '-([a-z0-9_-]+)', 'i' ) );
	return match ? match[ 1 ] : '';
}

// Delegated, so it also covers elements added to the page later.
document.addEventListener( 'click', function ( e ) {

	if ( ! e.target || ! e.target.closest ) {
		return;
	}

	var el = e.target.closest( wps_confetti_click_selector );
	if ( ! el ) {
		return;
	}

	var instance_id = wps_confetti_read_trigger( el, 'instance' ) || 'default';
	var style_id    = wps_confetti_read_trigger( el, 'style' );

	if ( ! style_id ) {
		wps_run_confetti( instance_id );
		return;
	}

	if ( typeof confetti_instances === 'undefined' || ! confetti_instances[ instance_id ] ) {
		console.warn( 'Confetti instance not found:', instance_id );
		return;
	}

	// The instance's settings with the style swapped in, and anything that
	// style needs which the instance never set filled in from its defaults.
	var settings = Object.assign( {}, confetti_instances[ instance_id ], { style: style_id } );
	var defaults = ( typeof confetti_style_defaults !== 'undefined' && confetti_style_defaults[ style_id ] ) ? confetti_style_defaults[ style_id ] : {};
	for ( var key in defaults ) {
		if ( defaults.hasOwnProperty( key ) && ( settings[ key ] === undefined || settings[ key ] === '' ) ) {
			settings[ key ] = defaults[ key ];
		}
	}

	wps_run_confetti( settings );
} );

function wps_confetti_get_random_color( colors ) {
	return colors[ Math.floor( Math.random() * colors.length ) ];
}

function wps_confetti_sleep( ms ) {
	return new Promise( resolve => setTimeout( resolve, ms ) );
}


function wps_confetti_inview_setup( element_id, instance_id ) {
	var has_run_key = 'wps_confetti_has_run_' + element_id;
	var inview_key  = 'wps_confetti_inview_' + element_id;

	// Check if already set up to avoid duplicates.
	if ( window[ has_run_key ] !== undefined ) {
		return;
	}

	window[ has_run_key ] = false;

	window[ inview_key ] = function () {
		var el = document.querySelector( '#' + element_id );
		if ( ! el ) {
			return;
		}

		var el_rect         = el.getBoundingClientRect();
		var viewport_height = ( window.innerHeight || document.documentElement.clientHeight );
		var is_inview       = false;

		if ( el_rect.top < viewport_height && el_rect.bottom > 0 ) {
			is_inview = true;
		}

		if ( is_inview && ! window[ has_run_key ] ) {
			window[ has_run_key ] = true;
			wps_run_confetti( instance_id );
		}
	};

	window.addEventListener( 'scroll', window[ inview_key ] );

	window[ inview_key ]();
}

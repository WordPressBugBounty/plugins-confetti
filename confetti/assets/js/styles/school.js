/**
 * School Pride - two steady streams firing in from the left and right edges
 * at the same time, one color per particle.
 */
WPSConfetti.register(
	'school',
	{
		originSelector: false,
		run: function ( settings, api ) {

			var end    = Date.now() + ( settings.duration * 1000 );
			var colors = settings.colors && settings.colors.length ? settings.colors : api.defaultColors;

			( function frame() {

				api.fire(
					{
						particleCount: 2,
						angle: 60,
						origin: { x: 0, y: settings.origin.y },
						zIndex: 99999,
						colors: [ api.randomColor( colors ) ]
					}
				);

				api.fire(
					{
						particleCount: 2,
						angle: 120,
						origin: { x: 1, y: settings.origin.y },
						zIndex: 99999,
						colors: [ api.randomColor( colors ) ]
					}
				);

				if ( Date.now() < end ) {
					requestAnimationFrame( frame );
				}

			}() );

		}
	}
);

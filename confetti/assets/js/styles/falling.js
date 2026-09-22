/**
 * Falling - confetti drifting down from above the screen, released one piece
 * at a time with a little randomness in size, gravity and drift.
 */
WPSConfetti.register(
	'falling',
	{
		run: function ( settings, api ) {

			var duration      = settings.duration * 1000;
			var animation_end = Date.now() + duration;
			var skew          = 1;
			var interval_time = Math.max( ( 750 / settings.particleCount ), .1 );
			var colors        = settings.colors && settings.colors.length ? settings.colors : api.defaultColors;

			api.every(
				interval_time,
				function () {

					var time_left = animation_end - Date.now();

					if ( time_left <= 0 ) {
						return api.done();
					}

					var ticks = Math.max( 200, 500 * ( time_left / duration ) );
					skew      = Math.max( 0.8, skew - 0.001 );

					api.fire(
						{
							particleCount: 1,
							zIndex: 99999,
							startVelocity: 0,
							ticks: ticks,
							origin: {
								x: Math.random(),
								// Particles fall down, so skew the start toward the top.
								y: ( Math.random() * skew ) - 0.2
							},
							gravity: api.random( parseFloat( settings.gravity ) - .2, parseFloat( settings.gravity ) + .2 ),
							scalar: api.random( parseFloat( settings.scalar ) - .3, parseFloat( settings.scalar ) + .3 ),
							drift: api.random( parseFloat( settings.drift ) - .4, parseFloat( settings.drift ) + .4 ),
							colors: [ api.randomColor( colors ) ]
						}
					);

				}
			);

		}
	}
);

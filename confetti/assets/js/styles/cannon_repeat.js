/**
 * Repeating Cannon - keeps firing for the set duration, varying the angle,
 * spread and count each time.
 */
WPSConfetti.register(
	'cannon_repeat',
	{
		originSelector: true,
		run: function ( settings, api ) {

			var duration      = settings.duration * 1000;
			var animation_end = Date.now() + duration;
			var speed         = 100 + ( 10 * ( 100 - settings.speed ) );

			api.every(
				speed,
				function () {

					if ( animation_end - Date.now() <= 0 ) {
						return api.done();
					}

					api.fire(
						{
							angle: api.random( settings.angle * .6, settings.angle * 1.4 ),
							spread: api.random( settings.spread * 1.1, settings.spread * 1.2 ),
							particleCount: api.random( settings.particleCount * .75, settings.particleCount * 1.25 )
						}
					);

				}
			);

		}
	}
);

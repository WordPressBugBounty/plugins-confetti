/**
 * Fireworks - bursts going off at random spots near the top of the screen,
 * thinning out as the duration runs down.
 */
WPSConfetti.register(
	'fireworks',
	{
		run: function ( settings, api ) {

			var duration      = settings.duration * 1000;
			var animation_end = Date.now() + duration;
			var speed         = 100 + ( 10 * ( 100 - settings.speed ) );

			api.every(
				speed,
				function () {

					var time_left = animation_end - Date.now();

					if ( time_left <= 0 ) {
						return api.done();
					}

					var particle_count = 50 * ( time_left / duration );

					// Particles fall down, so start a bit higher than random.
					api.fire(
						{
							particleCount: particle_count,
							spread: 360,
							origin: { x: api.random( 0.1, 0.3 ), y: Math.random() - 0.2 }
						}
					);
					api.fire(
						{
							particleCount: particle_count,
							spread: 360,
							origin: { x: api.random( 0.7, 0.9 ), y: Math.random() - 0.2 }
						}
					);

				}
			);

		}
	}
);

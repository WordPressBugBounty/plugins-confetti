/**
 * Burst - three quick pulses radiating out from the center with no gravity,
 * so they hang in the air.
 */
WPSConfetti.register(
	'burst',
	{
		run: function ( settings, api ) {

			var scalar = settings.scalar || 2;

			function shoot() {

				api.fire(
					{
						spread: 360,
						ticks: 60,
						gravity: 0,
						decay: 0.96,
						startVelocity: 20,
						particleCount: settings.particleCount * .8,
						scalar: scalar * 1.2
					}
				);

				api.fire(
					{
						spread: 360,
						ticks: 60,
						gravity: 0,
						decay: 0.96,
						startVelocity: 20,
						particleCount: settings.particleCount * .2,
						scalar: scalar * .75,
						flat: true
					}
				);

			}

			api.later( 0, shoot );
			api.later( 100, shoot );
			api.later( 200, shoot );

		}
	}
);

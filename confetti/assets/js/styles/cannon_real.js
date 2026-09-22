/**
 * Realistic Cannon - five overlapping bursts of different sizes and spreads,
 * so it reads like a real cannon rather than one even puff.
 */
WPSConfetti.register(
	'cannon_real',
	{
		originSelector: true,
		run: function ( settings, api ) {

			var count = settings.particleCount;

			function shoot( particle_ratio, opts ) {
				api.fire(
					Object.assign(
						{},
						opts,
						{
							particleCount: Math.floor( count * particle_ratio )
						}
					)
				);
			}

			shoot(
				0.25,
				{
					spread: settings.spread * .6,
					startVelocity: settings.startVelocity * 1.2
				}
			);
			shoot(
				0.2,
				{
					spread: settings.spread * 1.1
				}
			);
			shoot(
				0.35,
				{
					spread: settings.spread * 2,
					decay: 0.91,
					scalar: settings.scalar * .8
				}
			);
			shoot(
				0.1,
				{
					spread: settings.spread * 2.9,
					startVelocity: settings.startVelocity * .6,
					decay: 0.92,
					scalar: settings.scalar * 1.2
				}
			);
			shoot(
				0.1,
				{
					spread: settings.spread * 2.9,
					startVelocity: settings.startVelocity
				}
			);

		}
	}
);

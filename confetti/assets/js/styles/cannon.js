/**
 * Basic Cannon - one burst from a single spot.
 */
WPSConfetti.register(
	'cannon',
	{
		originSelector: true,
		run: function ( settings, api ) {
			api.fire();
		}
	}
);

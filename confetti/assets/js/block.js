( function( blocks, blockEditor, element, components ) {

	const { registerBlockType }           = blocks;
	const { useBlockProps, InspectorControls } = blockEditor;
	const { Fragment }                    = element;
	const { SelectControl, Button } = components;

	const el = element.createElement;

	const iconEl = el(
		'svg',
		{ width: 20, height: 20 },
		el( 'path', { d: "M13.0675,0,11.88,1.188l1.188,1.1879L14.2554,1.188ZM6.7317.792a1.188,1.188,0,0,0,0,2.3759H7.9289a.7888.7888,0,0,1,.792.78C8.736,5.2582,8.7116,7.92,8.7116,7.92a10.0391,10.0391,0,0,0,1.584-4.7518A2.2691,2.2691,0,0,0,7.92.792Zm10.6916,0a1.5957,1.5957,0,0,0-1.584,1.5839V3.96h-.7919a1.5958,1.5958,0,0,0-1.584,1.584v.7919h-.0587a2.9928,2.9928,0,0,0-1.7959.5986l-.0572.0434L9.714,8.9653l1.1632,1.0766,1.7092-1.85a1.4035,1.4035,0,0,1,.8183-.2722h.0587a1.5958,1.5958,0,0,0,1.584-1.584V5.5438h.7919a1.5958,1.5958,0,0,0,1.584-1.584V2.3759h2.3759V.792ZM2.7719,1.5839l-1.188,1.188L2.7719,3.96,3.96,2.7719ZM1.188,6.3357,0,7.5237,1.188,8.7116,2.3759,7.5237Zm17.4232,0-1.1879,1.188,1.1879,1.1879,1.188-1.1879ZM4.7441,7.12a.792.792,0,0,0-.5522,1.36l.24.24L.8739,17.6135a1.0092,1.0092,0,0,0,1.3117,1.3117L11.08,15.3676l.24.24A.792.792,0,1,0,12.4622,14.51l-.0228-.0228L8.8957,10.9437,5.3117,7.36A.7921.7921,0,0,0,4.7441,7.12ZM16.6313,9.5036A10.04,10.04,0,0,0,11.88,11.0875s2.6615-.0243,3.9722-.0092a.7888.7888,0,0,1,.78.7919v1.1973a1.188,1.188,0,0,0,2.3759,0V11.88A2.2691,2.2691,0,0,0,16.6313,9.5036Zm-1.1879,5.5438-1.188,1.1879,1.188,1.188,1.1879-1.188Z" } )
	);


	// Get instances from localized data with fallback
	const instances = (typeof confetti_instances !== 'undefined') ? confetti_instances : { default: { name: 'Default' } };
	const instanceOptions = Object.keys(instances).map(key => ({
		label: instances[key].name || key,
		value: key
	}));

	const preview_click = (instanceId) => {
		// Trigger confetti preview using the selected instance
		if (typeof wps_run_confetti === 'function') {
			wps_run_confetti(instanceId);
		} else {
			console.error("wps_run_confetti function not available");
		}
	}

	registerBlockType(
		'wpsunshine/confetti',
		{
			icon: iconEl,
			edit: function( props ) {
				const { attributes, setAttributes } = props;
				const blockProps = useBlockProps();

				return el(
					'div',
					blockProps,
					el(
						InspectorControls,
						{},
						el(
							components.PanelBody,
							{ title: 'Confetti Settings', initialOpen: true },
							el(
								SelectControl,
								{
									label: 'Instance',
									value: attributes.instance,
									options: instanceOptions,
									onChange: (value) => setAttributes({ instance: value })
								}
							),
							el(
								SelectControl,
								{
									label: 'Trigger Method',
									value: attributes.trigger,
									options: [
										{ label: 'On page load', value: 'onload' },
										{ label: 'When scrolled into view', value: 'inview' }
									],
									onChange: (value) => setAttributes({ trigger: value })
								}
							)
						)
					),
					el(
						'div',
						{
							className: 'wps-confetti-block-preview',
							style: {
								backgroundColor: '#e8f5e9',
								border: '2px dashed #4caf50',
								borderRadius: '4px',
								padding: '20px',
								textAlign: 'center'
							}
						},
						el(
							'div',
							{ style: { fontSize: '48px', marginBottom: '10px' } },
							'🎉'
						),
						el(
							Button,
							{
								variant: 'primary',
								onClick: () => preview_click(attributes.instance),
								style: {
									backgroundColor: '#4caf50',
									color: 'white',
									border: 'none',
									borderRadius: '4px',
									padding: '8px 16px',
									fontSize: '13px',
									fontWeight: '600',
									marginBottom: '12px'
								}
							},
							'Preview Confetti'
						),
						el(
							'div',
							{ style: { fontSize: '12px', color: '#666', fontStyle: 'italic' } },
							'This preview box is not visible to visitors on frontend'
						)
					)
				);
			},
			save: function( props ) {
				return null;
			},

		}
	);

} )(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.element,
	window.wp.components,
);

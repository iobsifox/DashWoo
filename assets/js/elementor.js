/* global jQuery, elementor */
( function ( $ ) {
	'use strict';

	var DashWooElementor = {
		settings: ( window.elementorLocalizeSettings || {} ).dashwoo || {},

		init: function () {
			elementor.hooks.addFilter( 'panel/controls/typography/available_fonts', function ( fonts ) {
				return fonts.concat( DashWooElementor.settings.fonts || [] );
			} );
		},
	};

	$( window ).on( 'elementor:init', DashWooElementor.init );
}( jQuery ) );

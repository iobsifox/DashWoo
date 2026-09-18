/* global jQuery */
( function ( $ ) {
	'use strict';

	// The token picker stores var(--dw-*) references; keep the panel preview in sync.
	$( document ).on( 'change', '.dw-token-select', function () {
		$( this ).closest( '.elementor-control-field' ).find( '.dw-token-preview' ).css( 'background', $( this ).val() );
	} );
}( jQuery ) );

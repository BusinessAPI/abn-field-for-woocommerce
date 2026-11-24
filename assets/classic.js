/* ABN field on the classic checkout and My Account billing address: shown for Australia only. */
( function ( $ ) {
	'use strict';

	function update() {
		var $row = $( '#billing_abn_field' );
		if ( ! $row.length ) {
			return;
		}
		var $input = $( '#billing_abn' );
		var country = $( '#billing_country' ).val();
		var mode = $input.attr( 'data-abnf-required' );
		var company = $.trim( $( '#billing_company' ).val() || '' );
		var required = mode === 'yes' || ( mode === 'company' && company !== '' );
		var $label = $row.find( 'label' ).first();

		$row.toggle( ! country || country === 'AU' );
		$label.find( '.optional' ).toggle( ! required );
		$label.find( '.abnf-required' ).remove();
		if ( required ) {
			$label.append( '<span class="required abnf-required" aria-hidden="true">*</span>' );
		}
		$input.attr( 'aria-required', required ? 'true' : 'false' );
		$row.toggleClass( 'validate-required', required );
	}

	$( document.body ).on( 'change', '#billing_country', update );
	$( document.body ).on( 'input change', '#billing_company', update );
	$( document.body ).on( 'updated_checkout country_to_state_changed', update );
	$( update );
}( jQuery ) );

( function ( $ ) {
	'use strict';

	var wscToast = typeof Swal !== 'undefined'
		? Swal.mixin( {
			toast: true,
			position: 'top-end',
			showConfirmButton: false,
			timer: 4000,
			timerProgressBar: true,
		} )
		: null;

	function wscErr( msg ) {
		if ( wscToast ) {
			wscToast.fire( { icon: 'error', title: msg } );
		} else {
			window.alert( msg );
		}
	}

	function wscOk( msg ) {
		if ( wscToast ) {
			wscToast.fire( { icon: 'success', title: msg } );
		}
	}

	$( function () {
		$( document ).on( 'click', '.wsc-ai-generate-summary', function () {
			var $btn = $( this );
			var id = parseInt( $btn.data( 'post-id' ), 10 );
			if ( ! id ) {
				return;
			}
			$btn.prop( 'disabled', true );
			$.post(
				WSCAiAdmin.ajaxurl,
				{
					action: 'wsc_ai_regenerate_summary',
					nonce: WSCAiAdmin.nonce,
					post_id: id,
				}
			)
				.done( function ( res ) {
					if ( res && res.success ) {
						var ok = ( WSCAiAdmin.i18n && WSCAiAdmin.i18n.success ) ? WSCAiAdmin.i18n.success : 'OK';
						wscOk( ok );
						window.setTimeout( function () {
							window.location.reload();
						}, 650 );
					} else {
						wscErr( ( res && res.data && res.data.message ) ? res.data.message : WSCAiAdmin.i18n.error );
						$btn.prop( 'disabled', false );
					}
				} )
				.fail( function () {
					wscErr( WSCAiAdmin.i18n.error );
					$btn.prop( 'disabled', false );
				} );
		} );
	} );
}( jQuery ) );

( function ( $ ) {
	'use strict';

	var vefgToast = typeof Swal !== 'undefined'
		? Swal.mixin( {
			toast: true,
			position: 'top-end',
			showConfirmButton: false,
			timer: 4000,
			timerProgressBar: true,
		} )
		: null;

	function vefgErr( msg ) {
		if ( vefgToast ) {
			vefgToast.fire( { icon: 'error', title: msg } );
		} else {
			window.alert( msg );
		}
	}

	function vefgOk( msg ) {
		if ( vefgToast ) {
			vefgToast.fire( { icon: 'success', title: msg } );
		}
	}

	$( function () {
		$( document ).on( 'click', '.vefg-ai-generate-summary', function () {
			var $btn = $( this );
			var id = parseInt( $btn.data( 'post-id' ), 10 );
			if ( ! id ) {
				return;
			}
			$btn.prop( 'disabled', true );
			$.post(
				VEFGAiAdmin.ajaxurl,
				{
					action: 'vefg_ai_regenerate_summary',
					nonce: VEFGAiAdmin.nonce,
					post_id: id,
				}
			)
				.done( function ( res ) {
					if ( res && res.success ) {
						var ok = ( VEFGAiAdmin.i18n && VEFGAiAdmin.i18n.success ) ? VEFGAiAdmin.i18n.success : 'OK';
						vefgOk( ok );
						window.setTimeout( function () {
							window.location.reload();
						}, 650 );
					} else {
						vefgErr( ( res && res.data && res.data.message ) ? res.data.message : VEFGAiAdmin.i18n.error );
						$btn.prop( 'disabled', false );
					}
				} )
				.fail( function () {
					vefgErr( VEFGAiAdmin.i18n.error );
					$btn.prop( 'disabled', false );
				} );
		} );
	} );
}( jQuery ) );

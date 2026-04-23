( function () {
	'use strict';

	if ( typeof Swal === 'undefined' || ! document.body.classList.contains( 'wsc-plugin-admin' ) ) {
		return;
	}

	var toast = Swal.mixin( {
		toast: true,
		position: 'top-end',
		showConfirmButton: false,
		timer: 4200,
		timerProgressBar: true,
		didOpen: function ( t ) {
			t.addEventListener( 'mouseenter', Swal.stopTimer );
			t.addEventListener( 'mouseleave', Swal.resumeTimer );
		},
	} );

	function noticeText( el ) {
		var $el = jQuery( el );
		var parts = $el.find( 'p' ).map( function () {
			return jQuery( this ).text().trim();
		} ).get();
		var t = parts.filter( Boolean ).join( ' ' ).trim();
		return t || $el.text().trim();
	}

	jQuery( function ( $ ) {
		var $root = $( '#wpbody-content' );
		if ( ! $root.length ) {
			return;
		}

		var selectors = '.updated, .error, .notice.notice-success, .notice.notice-error';
		$root.children( selectors ).each( function () {
			var $n = $( this );
			if ( $n.hasClass( 'notice-warning' ) || $n.hasClass( 'notice-info' ) ) {
				return;
			}
			var msg = noticeText( this );
			if ( ! msg ) {
				return;
			}
			var isErr = $n.is( '.error, .notice-error' );
			toast.fire( {
				icon: isErr ? 'error' : 'success',
				title: msg,
			} );
			$n.remove();
		} );
	} );
}() );

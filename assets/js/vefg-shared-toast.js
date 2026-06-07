/**
 * Shared SweetAlert2 toast (load once per page).
 */
(function (w) {
	'use strict';
	if (typeof w.vefgToast !== 'undefined') {
		return;
	}
	if (typeof Swal === 'undefined') {
		return;
	}
	w.vefgToast = Swal.mixin({
		toast: true,
		position: 'center',
		showConfirmButton: false,
		timer: 3000,
		timerProgressBar: true,
	});
})(window);

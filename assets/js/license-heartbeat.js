/**
 * Pro license admin heartbeat (every 5 minutes).
 */
(function ($) {
	'use strict';

	var cfg = typeof WPSpanChecker !== 'undefined' && WPSpanChecker.license ? WPSpanChecker.license : null;
	if (!cfg || !cfg.heartbeat) {
		return;
	}

	var intervalMs = (parseInt(cfg.interval, 10) || 300) * 1000;
	var ajaxUrl = cfg.ajaxUrl || (typeof WPSpanChecker !== 'undefined' && (WPSpanChecker.ajaxUrl || WPSpanChecker.ajaxurl)) || (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
	var nonce = cfg.nonce || (typeof WPSpanChecker !== 'undefined' ? WPSpanChecker.nonce : '');
	var lastRun = 0;
	var lastForce = 0;
	var running = false;

	function runLicenseCheck(force) {
		if (running || !ajaxUrl) {
			return;
		}
		var now = Date.now();
		if (force) {
			if (now - lastForce < intervalMs) {
				return;
			}
			lastForce = now;
		} else if (now - lastRun < intervalMs) {
			return;
		}
		running = true;
		lastRun = now;
		$.ajax({
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'vms_span_checker_license_refresh',
				nonce: nonce,
				force: force ? 1 : 0,
			},
			dataType: 'json',
		})
			.done(function (response) {
				var data = response && response.data ? response.data : {};
				if (data.valid === false && !data.throttled) {
					$(document).trigger('vms_span_checker:license_invalid', [data]);
					if (cfg.licenseUrl) {
						window.setTimeout(function () {
							window.location.href = cfg.licenseUrl;
						}, parseInt(cfg.redirectDelayMs, 10) || 1500);
					}
				}
			})
			.always(function () {
				running = false;
			});
	}

	runLicenseCheck(false);
	window.setInterval(function () {
		runLicenseCheck(false);
	}, intervalMs);

	if (cfg.forceSelector) {
		$(document).on('click', cfg.forceSelector, function (e) {
			e.preventDefault();
			runLicenseCheck(true);
		});
	}
})(jQuery);

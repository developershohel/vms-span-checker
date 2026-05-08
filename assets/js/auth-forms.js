/**
 * WP Span Checker - Auth Forms JavaScript
 */

(function($) {
	'use strict';

	var WSCAuthForms = {
		config: window.WSCAuthForms || {},
		recaptchaLoaded: false,
		recaptchaWidgets: {},

		init: function() {
			this.bindEvents();
			this.initPasswordToggles();
			this.initPasswordStrength();
			this.loadRecaptcha();
		},

		bindEvents: function() {
			var self = this;

			// Login form
			$(document).on('submit', '#wsc-login-form', function(e) {
				e.preventDefault();
				self.handleLogin($(this));
			});

			// Register form
			$(document).on('submit', '#wsc-register-form', function(e) {
				e.preventDefault();
				self.handleRegister($(this));
			});

			// Forgot password form
			$(document).on('submit', '#wsc-forgot-form', function(e) {
				e.preventDefault();
				self.handleForgotPassword($(this));
			});

			// Reset password form
			$(document).on('submit', '#wsc-reset-form', function(e) {
				e.preventDefault();
				self.handleResetPassword($(this));
			});
		},

		initPasswordToggles: function() {
			$(document).on('click', '.wsc-auth-toggle-pass', function() {
				var $btn = $(this);
				var $input = $btn.siblings('input');
				var $icon = $btn.find('.dashicons');

				if ($input.attr('type') === 'password') {
					$input.attr('type', 'text');
					$icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
				} else {
					$input.attr('type', 'password');
					$icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
				}
			});
		},

		initPasswordStrength: function() {
			$(document).on('input', '#wsc-reg-pass, #wsc-reset-pass', function() {
				var password = $(this).val();
				var $strength = $(this).closest('.wsc-auth-field').find('.wsc-auth-password-strength');

				if (!$strength.length) return;

				$strength.removeClass('weak medium strong');

				if (password.length === 0) return;

				var score = 0;
				if (password.length >= 8) score++;
				if (password.length >= 12) score++;
				if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
				if (/\d/.test(password)) score++;
				if (/[^a-zA-Z0-9]/.test(password)) score++;

				if (score <= 2) {
					$strength.addClass('weak');
				} else if (score <= 4) {
					$strength.addClass('medium');
				} else {
					$strength.addClass('strong');
				}
			});
		},

		loadRecaptcha: function() {
			var self = this;
			var $recaptchaContainers = $('.wsc-auth-recaptcha');

			if (!$recaptchaContainers.length || !this.config.recaptchaSiteKey) {
				return;
			}

			if (typeof grecaptcha !== 'undefined') {
				this.recaptchaLoaded = true;
				this.renderRecaptcha();
				return;
			}

			var script = document.createElement('script');
			script.src = 'https://www.google.com/recaptcha/api.js?onload=wscRecaptchaOnload&render=explicit';
			script.async = true;
			script.defer = true;
			document.head.appendChild(script);

			window.wscRecaptchaOnload = function() {
				self.recaptchaLoaded = true;
				self.renderRecaptcha();
			};
		},

		renderRecaptcha: function() {
			var self = this;

			$('.wsc-auth-recaptcha').each(function() {
				var $container = $(this);
				var id = $container.attr('id');

				if (self.recaptchaWidgets[id]) return;

				if (self.config.recaptchaVersion === 'v2') {
					self.recaptchaWidgets[id] = grecaptcha.render($container[0], {
						sitekey: self.config.recaptchaSiteKey,
						callback: function(token) {
							$container.data('token', token);
						}
					});
				}
			});
		},

		getRecaptchaToken: function($form) {
			var self = this;
			var $container = $form.find('.wsc-auth-recaptcha');

			if (!$container.length || !this.config.recaptchaSiteKey) {
				return Promise.resolve('');
			}

			if (this.config.recaptchaVersion === 'v3') {
				return new Promise(function(resolve) {
					grecaptcha.ready(function() {
						grecaptcha.execute(self.config.recaptchaSiteKey, { action: 'submit' })
							.then(function(token) {
								resolve(token);
							});
					});
				});
			} else {
				var token = $container.data('token') || '';
				if (!token) {
					return Promise.resolve(null); // null indicates reCAPTCHA not completed
				}
				return Promise.resolve(token);
			}
		},

		handleLogin: function($form) {
			var self = this;
			var $btn = $form.find('.wsc-auth-submit');
			var $messageArea = $form.find('.wsc-auth-message-area');

			$btn.addClass('loading').prop('disabled', true);
			$messageArea.empty();

			this.getRecaptchaToken($form).then(function(token) {
				if (token === null) {
					self.showMessage($messageArea, self.config.i18n.completeRecaptcha || 'Please complete the reCAPTCHA.', 'error');
					$btn.removeClass('loading').prop('disabled', false);
					return;
				}

				var data = {
					action: 'wsc_auth_login',
					wsc_auth_nonce: $form.find('[name="wsc_auth_nonce"]').val(),
					user_login: $form.find('[name="user_login"]').val(),
					user_password: $form.find('[name="user_password"]').val(),
					remember: $form.find('[name="remember"]').is(':checked') ? '1' : '0',
					recaptcha_token: token
				};

				$.post(self.config.ajaxUrl, data, function(response) {
					$btn.removeClass('loading').prop('disabled', false);

					if (response.success) {
						self.showMessage($messageArea, response.data.message, 'success');
						if (response.data.redirect) {
							setTimeout(function() {
								window.location.href = response.data.redirect;
							}, 1000);
						}
					} else {
						self.showMessage($messageArea, response.data.message, 'error');
						self.resetRecaptcha($form);
					}
				}).fail(function() {
					$btn.removeClass('loading').prop('disabled', false);
					self.showMessage($messageArea, self.config.i18n.networkError || 'Network error. Please try again.', 'error');
				});
			});
		},

		handleRegister: function($form) {
			var self = this;
			var $btn = $form.find('.wsc-auth-submit');
			var $messageArea = $form.find('.wsc-auth-message-area');

			// Client-side validation
			var password = $form.find('[name="user_password"]').val();
			var passwordConfirm = $form.find('[name="user_password_confirm"]').val();

			if (password !== passwordConfirm) {
				self.showMessage($messageArea, self.config.i18n.passwordsMismatch || 'Passwords do not match.', 'error');
				return;
			}

			if (password.length < 8) {
				self.showMessage($messageArea, self.config.i18n.passwordTooShort || 'Password must be at least 8 characters.', 'error');
				return;
			}

			$btn.addClass('loading').prop('disabled', true);
			$messageArea.empty();

			this.getRecaptchaToken($form).then(function(token) {
				if (token === null) {
					self.showMessage($messageArea, self.config.i18n.completeRecaptcha || 'Please complete the reCAPTCHA.', 'error');
					$btn.removeClass('loading').prop('disabled', false);
					return;
				}

				var data = {
					action: 'wsc_auth_register',
					wsc_auth_nonce: $form.find('[name="wsc_auth_nonce"]').val(),
					user_login: $form.find('[name="user_login"]').val(),
					user_email: $form.find('[name="user_email"]').val(),
					user_password: password,
					user_password_confirm: passwordConfirm,
					recaptcha_token: token
				};

				$.post(self.config.ajaxUrl, data, function(response) {
					$btn.removeClass('loading').prop('disabled', false);

					if (response.success) {
						self.showMessage($messageArea, response.data.message, 'success');
						if (response.data.redirect) {
							setTimeout(function() {
								window.location.href = response.data.redirect;
							}, 1000);
						}
					} else {
						self.showMessage($messageArea, response.data.message, 'error');
						self.resetRecaptcha($form);
					}
				}).fail(function() {
					$btn.removeClass('loading').prop('disabled', false);
					self.showMessage($messageArea, self.config.i18n.networkError || 'Network error. Please try again.', 'error');
				});
			});
		},

		handleForgotPassword: function($form) {
			var self = this;
			var $btn = $form.find('.wsc-auth-submit');
			var $messageArea = $form.find('.wsc-auth-message-area');

			$btn.addClass('loading').prop('disabled', true);
			$messageArea.empty();

			var data = {
				action: 'wsc_auth_forgot_password',
				wsc_auth_nonce: $form.find('[name="wsc_auth_nonce"]').val(),
				user_email: $form.find('[name="user_email"]').val()
			};

			$.post(self.config.ajaxUrl, data, function(response) {
				$btn.removeClass('loading').prop('disabled', false);

				if (response.success) {
					self.showMessage($messageArea, response.data.message, 'success');
				} else {
					self.showMessage($messageArea, response.data.message, 'error');
				}
			}).fail(function() {
				$btn.removeClass('loading').prop('disabled', false);
				self.showMessage($messageArea, self.config.i18n.networkError || 'Network error. Please try again.', 'error');
			});
		},

		handleResetPassword: function($form) {
			var self = this;
			var $btn = $form.find('.wsc-auth-submit');
			var $messageArea = $form.find('.wsc-auth-message-area');

			// Client-side validation
			var password = $form.find('[name="user_password"]').val();
			var passwordConfirm = $form.find('[name="user_password_confirm"]').val();

			if (password !== passwordConfirm) {
				self.showMessage($messageArea, self.config.i18n.passwordsMismatch || 'Passwords do not match.', 'error');
				return;
			}

			if (password.length < 8) {
				self.showMessage($messageArea, self.config.i18n.passwordTooShort || 'Password must be at least 8 characters.', 'error');
				return;
			}

			$btn.addClass('loading').prop('disabled', true);
			$messageArea.empty();

			var data = {
				action: 'wsc_auth_reset_password',
				wsc_auth_nonce: $form.find('[name="wsc_auth_nonce"]').val(),
				rp_key: $form.find('[name="rp_key"]').val(),
				rp_login: $form.find('[name="rp_login"]').val(),
				user_password: password,
				user_password_confirm: passwordConfirm
			};

			$.post(self.config.ajaxUrl, data, function(response) {
				$btn.removeClass('loading').prop('disabled', false);

				if (response.success) {
					self.showMessage($messageArea, response.data.message, 'success');
					if (response.data.redirect) {
						setTimeout(function() {
							window.location.href = response.data.redirect;
						}, 2000);
					}
				} else {
					self.showMessage($messageArea, response.data.message, 'error');
				}
			}).fail(function() {
				$btn.removeClass('loading').prop('disabled', false);
				self.showMessage($messageArea, self.config.i18n.networkError || 'Network error. Please try again.', 'error');
			});
		},

		showMessage: function($container, message, type) {
			var className = type === 'success' ? 'wsc-auth-message--success' : 'wsc-auth-message--error';
			$container.html('<div class="wsc-auth-message ' + className + '">' + message + '</div>');
		},

		resetRecaptcha: function($form) {
			var $container = $form.find('.wsc-auth-recaptcha');
			if (!$container.length) return;

			var id = $container.attr('id');
			if (this.recaptchaWidgets[id] !== undefined && this.config.recaptchaVersion === 'v2') {
				grecaptcha.reset(this.recaptchaWidgets[id]);
				$container.data('token', '');
			}
		}
	};

	$(document).ready(function() {
		WSCAuthForms.init();
		WSCAuthForms.initOTPInputs();
		WSCAuthForms.initResendOTP();
	});

	// OTP digit inputs handling
	WSCAuthForms.initOTPInputs = function() {
		$(document).on('input', '.wsc-otp-digit', function(e) {
			var $this = $(this);
			var val = $this.val().replace(/[^0-9]/g, '');
			$this.val(val);

			if (val.length === 1) {
				$this.addClass('filled');
				$this.next('.wsc-otp-digit').focus();
			} else {
				$this.removeClass('filled');
			}

			// Update hidden field
			var otp = '';
			$('.wsc-otp-digit').each(function() {
				otp += $(this).val();
			});
			$('#wsc-otp-code').val(otp);
		});

		$(document).on('keydown', '.wsc-otp-digit', function(e) {
			var $this = $(this);

			if (e.key === 'Backspace' && $this.val() === '') {
				$this.prev('.wsc-otp-digit').focus().val('').removeClass('filled');
			}
		});

		$(document).on('paste', '.wsc-otp-digit', function(e) {
			e.preventDefault();
			var pastedData = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
			var digits = pastedData.replace(/[^0-9]/g, '').split('');

			$('.wsc-otp-digit').each(function(index) {
				if (digits[index]) {
					$(this).val(digits[index]).addClass('filled');
				}
			});

			var otp = '';
			$('.wsc-otp-digit').each(function() {
				otp += $(this).val();
			});
			$('#wsc-otp-code').val(otp);

			$('.wsc-otp-digit').last().focus();
		});
	};

	// Resend OTP
	WSCAuthForms.initResendOTP = function() {
		$(document).on('click', '#wsc-resend-otp', function(e) {
			e.preventDefault();
			var $link = $(this);
			var email = $link.data('email');
			var $messageArea = $link.closest('form').find('.wsc-auth-message-area');

			$link.text(WSCAuthForms.config.i18n.sendingOTP || 'Sending...');

			$.post(WSCAuthForms.config.ajaxUrl, {
				action: 'wsc_auth_resend_otp',
				email: email
			}, function(response) {
				$link.text(WSCAuthForms.config.i18n.resendOTP || 'Resend');
				if (response.success) {
					WSCAuthForms.showMessage($messageArea, response.data.message, 'success');
				} else {
					WSCAuthForms.showMessage($messageArea, response.data.message, 'error');
				}
			}).fail(function() {
				$link.text(WSCAuthForms.config.i18n.resendOTP || 'Resend');
				WSCAuthForms.showMessage($messageArea, WSCAuthForms.config.i18n.networkError || 'Network error.', 'error');
			});
		});
	};

	// OTP form handler
	$(document).on('submit', '#wsc-otp-form', function(e) {
		e.preventDefault();
		var $form = $(this);
		var $btn = $form.find('.wsc-auth-submit');
		var $messageArea = $form.find('.wsc-auth-message-area');

		$btn.addClass('loading').prop('disabled', true);
		$messageArea.empty();

		var data = {
			action: 'wsc_auth_verify_otp',
			wsc_auth_nonce: $form.find('[name="wsc_auth_nonce"]').val(),
			email: $form.find('[name="email"]').val(),
			otp_code: $form.find('[name="otp_code"]').val()
		};

		$.post(WSCAuthForms.config.ajaxUrl, data, function(response) {
			$btn.removeClass('loading').prop('disabled', false);

			if (response.success) {
				WSCAuthForms.showMessage($messageArea, response.data.message, 'success');
				if (response.data.redirect) {
					setTimeout(function() {
						window.location.href = response.data.redirect;
					}, 1000);
				}
			} else {
				WSCAuthForms.showMessage($messageArea, response.data.message, 'error');
			}
		}).fail(function() {
			$btn.removeClass('loading').prop('disabled', false);
			WSCAuthForms.showMessage($messageArea, WSCAuthForms.config.i18n.networkError || 'Network error.', 'error');
		});
	});

})(jQuery);

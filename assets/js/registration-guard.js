/**
 * WP Span Checker - Registration Guard Frontend
 * Adds validation button and reCAPTCHA protection to WordPress registration forms.
 */
(function($) {
    'use strict';

    if (typeof WPSpanRegistrationGuard === 'undefined') {
        return;
    }

    var config = WPSpanRegistrationGuard;
    var ajaxUrl = config.ajaxUrl || '';
    var nonce = config.nonce || '';
    var frontendEnabled = config.frontendEnabled || false;
    var recaptchaEnabled = config.recaptchaEnabled || false;
    var recaptchaSiteKey = config.recaptchaSiteKey || '';
    var recaptchaVersion = config.recaptchaVersion || 'v2';
    var i18n = config.i18n || {};

    function t(key, fallback) {
        return i18n[key] || fallback || key;
    }

    var Toast = null;
    if (typeof Swal !== 'undefined') {
        Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
        });
    }

    function showToast(icon, title) {
        if (Toast) {
            Toast.fire({ icon: icon, title: title });
        } else if (icon === 'error') {
            alert(title);
        }
    }

    function showFieldError($field, message) {
        clearFieldError($field);
        $field.addClass('wsc-field-invalid');
        var $error = $('<span class="wsc-field-error wsc-reg-error" style="color:#d63638;display:block;margin-top:5px;">' + message + '</span>');
        $field.after($error);
    }

    function clearFieldError($field) {
        $field.removeClass('wsc-field-invalid');
        $field.siblings('.wsc-reg-error').remove();
        $field.parent().find('.wsc-reg-error').remove();
    }

    function clearAllErrors($form) {
        $form.find('.wsc-field-invalid').removeClass('wsc-field-invalid');
        $form.find('.wsc-reg-error').remove();
    }

    function copyButtonStyles($source, $target) {
        if (!$source.length) return;
        
        var computed = window.getComputedStyle($source[0]);
        $target.css({
            'background': computed.background,
            'background-color': computed.backgroundColor,
            'color': computed.color,
            'font-size': computed.fontSize,
            'font-weight': computed.fontWeight,
            'font-family': computed.fontFamily,
            'padding': computed.padding,
            'margin': computed.margin,
            'border': computed.border,
            'border-radius': computed.borderRadius,
            'width': computed.width,
            'height': computed.height,
            'line-height': computed.lineHeight,
            'cursor': 'pointer',
            'display': 'inline-block',
            'text-align': computed.textAlign
        });
    }

    function hideOriginalSubmit($btn) {
        $btn.css({
            'display': 'none',
            'position': 'absolute',
            'left': '-9999px',
            'visibility': 'hidden',
            'pointer-events': 'none',
            'opacity': '0',
            'width': '0',
            'height': '0'
        });
    }

    function validateRegistration(email, recaptchaToken) {
        return new Promise(function(resolve, reject) {
            var data = {
                action: 'wsc_validate_registration',
                nonce: nonce,
                email: email
            };
            
            if (recaptchaToken) {
                data.recaptcha_token = recaptchaToken;
            }

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response && response.success && response.data) {
                        resolve(response.data);
                    } else {
                        resolve({ status: false, message: t('validationFailed', 'Validation failed.') });
                    }
                },
                error: function() {
                    reject(new Error(t('serverError', 'Server error. Please try again.')));
                }
            });
        });
    }

    function getRecaptchaToken() {
        return new Promise(function(resolve, reject) {
            if (!recaptchaEnabled || !recaptchaSiteKey) {
                resolve(null);
                return;
            }

            if (recaptchaVersion === 'v3') {
                if (typeof grecaptcha !== 'undefined' && grecaptcha.execute) {
                    grecaptcha.ready(function() {
                        grecaptcha.execute(recaptchaSiteKey, { action: 'registration' })
                            .then(function(token) {
                                resolve(token);
                            })
                            .catch(function(err) {
                                console.error('[Registration Guard] reCAPTCHA v3 error:', err);
                                resolve(null);
                            });
                    });
                } else {
                    resolve(null);
                }
            } else {
                var response = typeof grecaptcha !== 'undefined' ? grecaptcha.getResponse() : '';
                if (response) {
                    resolve(response);
                } else {
                    reject(new Error(t('recaptchaRequired', 'Please complete the reCAPTCHA verification.')));
                }
            }
        });
    }

    function setupRegistrationGuard() {
        var $form = $('#registerform, form[name="registerform"], .woocommerce-form-register, .register form, form.registration-form');
        
        if (!$form.length) {
            $('form').each(function() {
                var $f = $(this);
                var formId = ($f.attr('id') || '').toLowerCase();
                var formClass = ($f.attr('class') || '').toLowerCase();
                var formAction = ($f.attr('action') || '').toLowerCase();
                
                if (formId.indexOf('register') !== -1 || 
                    formClass.indexOf('register') !== -1 ||
                    formAction.indexOf('register') !== -1 ||
                    formAction.indexOf('signup') !== -1) {
                    $form = $form.add($f);
                }
            });
        }

        if (!$form.length) {
            return;
        }

        var $thisForm = $form.first();

        if ($thisForm.data('wsc-registration-guard')) {
            return;
        }

        var $emailField = $thisForm.find('input[type="email"], input[name="user_email"], input[name="email"], input[id="user_email"]').first();

        if (!$emailField.length) {
            return;
        }

        $thisForm.data('wsc-registration-guard', true);
        console.log('[WP Span Checker] Registration Guard attached to form:', $thisForm[0]);

        if (!frontendEnabled) {
            if (recaptchaEnabled && recaptchaSiteKey) {
                setupRecaptchaOnly($thisForm);
            }
            return;
        }

        var $originalSubmit = $thisForm.find('#wp-submit, input[type="submit"], button[type="submit"]').first();

        if (!$originalSubmit.length) {
            $originalSubmit = $thisForm.find('button:not([type])').first();
        }

        var $actionsWrap = $('<div class="wsc-guard-actions"></div>');
        $originalSubmit.after($actionsWrap);

        var submitText = $originalSubmit.val() || $originalSubmit.text() || t('register', 'Register');
        var $validationBtn = $('<input type="button" class="wsc-validation-btn wsc-reg-validation-btn">');
        $validationBtn.val(submitText);

        copyButtonStyles($originalSubmit, $validationBtn);
        hideOriginalSubmit($originalSubmit);

        if (recaptchaEnabled && recaptchaSiteKey && recaptchaVersion === 'v2') {
            var recaptchaContainer = $('<div class="wsc-recaptcha-container"></div>');
            $actionsWrap.prepend(recaptchaContainer);

            $validationBtn.prop('disabled', true).css('opacity', '0.6');

            var checkRecaptcha = function() {
                if (typeof grecaptcha !== 'undefined' && grecaptcha.render) {
                    try {
                        grecaptcha.render(recaptchaContainer[0], {
                            sitekey: recaptchaSiteKey,
                            callback: function() {
                                $validationBtn.prop('disabled', false).css('opacity', '1');
                            },
                            'expired-callback': function() {
                                $validationBtn.prop('disabled', true).css('opacity', '0.6');
                            }
                        });
                    } catch (e) {
                        console.log('[Registration Guard] reCAPTCHA already rendered:', e);
                    }
                } else {
                    setTimeout(checkRecaptcha, 100);
                }
            };
            checkRecaptcha();
        }

        $actionsWrap.append($validationBtn);

        var isValidating = false;

        $validationBtn.on('click', function(e) {
            e.preventDefault();

            if (isValidating) {
                return;
            }

            clearAllErrors($thisForm);

            var email = $emailField.val().trim();

            if (!email) {
                showFieldError($emailField, t('emailRequired', 'Email address is required.'));
                return;
            }

            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showFieldError($emailField, t('emailInvalid', 'Please enter a valid email address.'));
                return;
            }

            isValidating = true;
            $validationBtn.val(t('validating', 'Validating...')).prop('disabled', true);

            getRecaptchaToken()
                .then(function(recaptchaToken) {
                    return validateRegistration(email, recaptchaToken);
                })
                .then(function(result) {
                    isValidating = false;
                    $validationBtn.val(submitText).prop('disabled', false);

                    if (result.status) {
                        console.log('[WP Span Checker] Registration Guard: Validation passed');

                        $thisForm.append('<input type="hidden" name="wsc_validation_token" value="' + (result.token || '') + '">');

                        $originalSubmit.css({
                            'display': '',
                            'position': '',
                            'left': '',
                            'visibility': '',
                            'pointer-events': '',
                            'opacity': '',
                            'width': '',
                            'height': ''
                        });

                        $originalSubmit[0].click();

                        setTimeout(function() {
                            hideOriginalSubmit($originalSubmit);
                        }, 100);
                    } else {
                        showFieldError($emailField, result.message || t('emailInvalid', 'This email address is not accepted.'));
                        showToast('error', result.message || t('emailInvalid', 'This email address is not accepted.'));

                        if (recaptchaEnabled && recaptchaVersion === 'v2' && typeof grecaptcha !== 'undefined') {
                            grecaptcha.reset();
                            $validationBtn.prop('disabled', true).css('opacity', '0.6');
                        }
                    }
                })
                .catch(function(err) {
                    isValidating = false;
                    $validationBtn.val(submitText).prop('disabled', recaptchaEnabled && recaptchaVersion === 'v2');
                    console.error('[WP Span Checker] Registration Guard error:', err);
                    showToast('error', err.message || t('serverError', 'Validation error. Please try again.'));
                });
        });

        $thisForm.on('submit', function(e) {
            if ($thisForm.find('input[name="wsc_validation_token"]').length) {
                return true;
            }

            e.preventDefault();
            e.stopImmediatePropagation();
            $validationBtn.trigger('click');
            return false;
        });
    }

    function setupRecaptchaOnly($form) {
        var $submitBtn = $form.find('#wp-submit, input[type="submit"], button[type="submit"]').first();
        
        if (!$submitBtn.length) {
            return;
        }

        if (recaptchaVersion === 'v2') {
            var $submitParent = $submitBtn.closest('p, .submit').first();
            var $actionsWrap = $('<div class="wsc-guard-actions"></div>');
            $submitParent.before($actionsWrap);
            var recaptchaContainer = $('<div class="wsc-recaptcha-container"></div>');
            $actionsWrap.append(recaptchaContainer);
            $actionsWrap.append($submitParent.detach());

            $submitBtn.prop('disabled', true).css('opacity', '0.6');
            
            var checkRecaptcha = function() {
                if (typeof grecaptcha !== 'undefined' && grecaptcha.render) {
                    try {
                        grecaptcha.render(recaptchaContainer[0], {
                            sitekey: recaptchaSiteKey,
                            callback: function(token) {
                                $submitBtn.prop('disabled', false).css('opacity', '1');
                                $form.find('input[name="wsc_recaptcha_token"]').remove();
                                $form.append('<input type="hidden" name="wsc_recaptcha_token" value="' + token + '">');
                            },
                            'expired-callback': function() {
                                $submitBtn.prop('disabled', true).css('opacity', '0.6');
                                $form.find('input[name="wsc_recaptcha_token"]').remove();
                            }
                        });
                    } catch (e) {
                        console.log('[Registration Guard] reCAPTCHA error:', e);
                    }
                } else {
                    setTimeout(checkRecaptcha, 100);
                }
            };
            checkRecaptcha();
        } else if (recaptchaVersion === 'v3') {
            $form.on('submit', function(e) {
                var $hiddenToken = $form.find('input[name="wsc_recaptcha_token"]');
                
                if ($hiddenToken.length && $hiddenToken.val()) {
                    return true;
                }
                
                e.preventDefault();
                
                if (typeof grecaptcha !== 'undefined' && grecaptcha.execute) {
                    grecaptcha.ready(function() {
                        grecaptcha.execute(recaptchaSiteKey, { action: 'registration' })
                            .then(function(token) {
                                $form.find('input[name="wsc_recaptcha_token"]').remove();
                                $form.append('<input type="hidden" name="wsc_recaptcha_token" value="' + token + '">');
                                $form[0].submit();
                            })
                            .catch(function(err) {
                                console.error('[Registration Guard] reCAPTCHA v3 error:', err);
                                $form[0].submit();
                            });
                    });
                } else {
                    $form[0].submit();
                }
                
                return false;
            });
        }
    }

    function loadRecaptchaScript() {
        if (recaptchaEnabled && recaptchaSiteKey) {
            if (typeof grecaptcha !== 'undefined') {
                setupRegistrationGuard();
                return;
            }

            var script = document.createElement('script');
            if (recaptchaVersion === 'v3') {
                script.src = 'https://www.google.com/recaptcha/api.js?render=' + recaptchaSiteKey;
            } else {
                script.src = 'https://www.google.com/recaptcha/api.js';
            }
            script.async = true;
            script.defer = true;
            script.onload = function() {
                setupRegistrationGuard();
            };
            document.head.appendChild(script);
        } else {
            setupRegistrationGuard();
        }
    }

    $(document).ready(function() {
        loadRecaptchaScript();
    });

})(jQuery);

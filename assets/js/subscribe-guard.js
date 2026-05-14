/**
 * WP Span Checker - Subscribe Guard
 * Validates email addresses on newsletter/subscription forms using a validation button approach.
 */
(function($) {
    'use strict';

    if (typeof WPSpanSubscribeGuard === 'undefined') {
        return;
    }

    var config = WPSpanSubscribeGuard;
    var ajaxUrl = config.ajaxUrl || '';
    var nonce = config.nonce || '';
    var formSelector = config.formSelector || '';
    var i18n = config.i18n || {};
    var recaptchaEnabled = config.recaptchaEnabled || false;
    var recaptchaSiteKey = config.recaptchaSiteKey || '';
    var recaptchaVersion = config.recaptchaVersion || 'v2';

    /** Only one subscribe/newsletter form per page (selector often matches multiple blocks). */
    var subscribeGuardAttached = false;

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
        } else {
            alert(title);
        }
    }

    function resolveFormElement(el) {
        var $el = $(el);
        if ($el.is('form')) {
            return el;
        }
        var $inner = $el.find('form').first();
        return $inner.length ? $inner[0] : null;
    }

    function detectSubscribeForms() {
        if (subscribeGuardAttached) {
            return [];
        }

        // Form selector is required - only use provided selector
        if (!formSelector || formSelector.trim() === '') {
            console.log('[WP Span Checker] Subscribe Guard: No form selector configured. Form selector is required.');
            return [];
        }

        var matches = $(formSelector);
        for (var i = 0; i < matches.length; i++) {
            var formEl = resolveFormElement(matches[i]);
            if (!formEl) {
                continue;
            }
            var $form = $(formEl);
            if ($form.data('wsc-subscribe-guard')) {
                continue;
            }
            if (formEl.id === 'adminbarsearch' || $form.closest('#wpadminbar').length > 0) {
                continue;
            }
            if (findEmailField($form).length === 0) {
                continue;
            }
            return [formEl];
        }

        return [];
    }

    function findEmailField($form) {
        return $form.find('input[type="email"], input[name*="email"], input[name*="Email"]').first();
    }

    function validateEmail(email, recaptchaToken) {
        return new Promise(function(resolve, reject) {
            var data = {
                action: 'wsc_validate_subscribe',
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

    function showFieldError($field, message) {
        clearFieldError($field);
        $field.addClass('wsc-field-invalid');
        var $error = $('<span class="wsc-field-error wsc-subscribe-error">' + message + '</span>');
        $field.after($error);
    }

    function clearFieldError($field) {
        $field.removeClass('wsc-field-invalid');
        $field.siblings('.wsc-subscribe-error').remove();
        $field.parent().find('.wsc-subscribe-error').remove();
    }

    function clearAllErrors($form) {
        $form.find('.wsc-field-invalid').removeClass('wsc-field-invalid');
        $form.find('.wsc-subscribe-error').remove();
    }

    function copyButtonStyles($source, $target) {
        if (!$source.length) return;
        
        var computed = window.getComputedStyle($source[0]);
        var styles = {
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
            'min-width': computed.minWidth,
            'min-height': computed.minHeight,
            'line-height': computed.lineHeight,
            'text-transform': computed.textTransform,
            'letter-spacing': computed.letterSpacing,
            'box-shadow': computed.boxShadow,
            'cursor': 'pointer',
            'display': 'inline-block',
            'text-align': computed.textAlign,
            'vertical-align': computed.verticalAlign
        };
        $target.css(styles);
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

    function getRecaptchaToken() {
        return new Promise(function(resolve, reject) {
            if (!recaptchaEnabled || !recaptchaSiteKey) {
                resolve(null);
                return;
            }

            if (recaptchaVersion === 'v3') {
                if (typeof grecaptcha !== 'undefined' && grecaptcha.execute) {
                    grecaptcha.ready(function() {
                        grecaptcha.execute(recaptchaSiteKey, { action: 'subscribe_guard' })
                            .then(function(token) {
                                resolve(token);
                            })
                            .catch(function(err) {
                                console.error('[Subscribe Guard] reCAPTCHA v3 error:', err);
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

    function renderRecaptcha($actionsWrap, $validationBtn) {
        if (!recaptchaEnabled || !recaptchaSiteKey) {
            return;
        }

        if (recaptchaVersion === 'v2') {
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
                        console.log('[Subscribe Guard] reCAPTCHA already rendered or error:', e);
                    }
                } else {
                    setTimeout(checkRecaptcha, 100);
                }
            };
            checkRecaptcha();
        }
    }

    function setupGuard(form) {
        var $form = $(form);
        
        if ($form.data('wsc-subscribe-guard')) {
            return;
        }

        var $emailField = findEmailField($form);
        if ($emailField.length === 0) {
            return;
        }

        $form.data('wsc-subscribe-guard', true);
        subscribeGuardAttached = true;
        console.log('[WP Span Checker] Subscribe Guard attached to form:', form);

        var $originalSubmit = $form.find('input[type="submit"], button[type="submit"]').first();
        if (!$originalSubmit.length) {
            $originalSubmit = $form.find('button:not([type])').first();
        }

        var $actionsWrap = $('<div class="wsc-guard-actions"></div>');
        $originalSubmit.after($actionsWrap);

        var submitText = $originalSubmit.val() || $originalSubmit.text() || t('submit', 'Subscribe');
        var $validationBtn = $('<input type="button" class="wsc-validation-btn wsc-subscribe-validation-btn">');
        $validationBtn.val(submitText);
        
        copyButtonStyles($originalSubmit, $validationBtn);
        hideOriginalSubmit($originalSubmit);

        renderRecaptcha($actionsWrap, $validationBtn);
        $actionsWrap.append($validationBtn);

        var isValidating = false;

        $validationBtn.on('click', function(e) {
            e.preventDefault();
            
            if (isValidating) {
                return;
            }

            clearAllErrors($form);

            var email = $emailField.val().trim();

            if (!email) {
                showFieldError($emailField, t('emailRequired', 'Email address is required.'));
                showToast('error', t('emailRequired', 'Email address is required.'));
                return;
            }

            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showFieldError($emailField, t('emailInvalid', 'Please enter a valid email address.'));
                showToast('error', t('emailInvalid', 'Please enter a valid email address.'));
                return;
            }

            isValidating = true;
            $validationBtn.val(t('validating', 'Validating...')).prop('disabled', true);

            getRecaptchaToken()
                .then(function(recaptchaToken) {
                    return validateEmail(email, recaptchaToken);
                })
                .then(function(result) {
                    isValidating = false;
                    $validationBtn.val(submitText).prop('disabled', false);

                    if (result.blocked) {
                        var blockMessage = result.strike_message || t('userBlocked', 'You have been blocked due to repeated violations.');
                        $validationBtn.prop('disabled', true).css('opacity', '0.5');
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: t('blocked', 'Blocked'),
                                text: blockMessage,
                                confirmButtonColor: '#d33'
                            });
                        } else {
                            alert(blockMessage);
                        }
                        return;
                    }

                    if (result.status) {
                        console.log('[WP Span Checker] Subscribe Guard: Email validated, submitting form');
                        
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
                    console.error('[WP Span Checker] Subscribe Guard error:', err);
                    showToast('error', err.message || t('serverError', 'Validation error. Please try again.'));
                });
        });

        $form.on('submit', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            $validationBtn.trigger('click');
            return false;
        });
    }

    function loadRecaptchaScript() {
        if (!recaptchaEnabled || !recaptchaSiteKey) {
            return;
        }

        if (typeof grecaptcha !== 'undefined') {
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
        document.head.appendChild(script);
    }

    function init() {
        console.log('[WP Span Checker] Subscribe Guard initializing...');
        
        loadRecaptchaScript();
        
        var forms = detectSubscribeForms();
        console.log('[WP Span Checker] Subscribe Guard found', forms.length, 'form(s)');
        
        forms.forEach(function(form) {
            setupGuard(form);
        });

        if (typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(function(mutations) {
                if (subscribeGuardAttached) {
                    return;
                }
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length > 0) {
                        setTimeout(function() {
                            if (subscribeGuardAttached) {
                                return;
                            }
                            var newForms = detectSubscribeForms();
                            newForms.forEach(function(form) {
                                if (!$(form).data('wsc-subscribe-guard')) {
                                    setupGuard(form);
                                }
                            });
                        }, 100);
                    }
                });
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    $(document).ready(function() {
        init();
    });

})(jQuery);

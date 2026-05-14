/**
 * WP Span Checker - Contact Guard
 * Validates contact forms with email and message fields using a validation button approach.
 */
(function($) {
    'use strict';

    if (typeof WPSpanContactGuard === 'undefined') {
        return;
    }

    var config = WPSpanContactGuard;
    var ajaxUrl = config.ajaxUrl || '';
    var nonce = config.nonce || '';
    var formSelector = config.formSelector || '';
    var i18n = config.i18n || {};
    var recaptchaEnabled = config.recaptchaEnabled || false;
    var recaptchaSiteKey = config.recaptchaSiteKey || '';
    var recaptchaVersion = config.recaptchaVersion || 'v2';

    /** Only one contact form per page gets validation + reCAPTCHA (avoids duplicates when multiple forms match). */
    var contactGuardAttached = false;

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

    /** If the selector matches a wrapper, use the first nested form. */
    function resolveFormElement(el) {
        var $el = $(el);
        if ($el.is('form')) {
            return el;
        }
        var $inner = $el.find('form').first();
        return $inner.length ? $inner[0] : null;
    }

    function detectContactForms() {
        if (contactGuardAttached) {
            return [];
        }

        if (formSelector && formSelector.trim() !== '') {
            var matches = $(formSelector);
            for (var i = 0; i < matches.length; i++) {
                var formEl = resolveFormElement(matches[i]);
                if (!formEl) {
                    continue;
                }
                var $form = $(formEl);
                if ($form.data('wsc-contact-guard')) {
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

        var $all = $('form');
        for (var j = 0; j < $all.length; j++) {
            var f = $all[j];
            var $f = $(f);
            if ($f.data('wsc-contact-guard')) {
                continue;
            }
            if (f.id === 'adminbarsearch' || $f.closest('#wpadminbar').length > 0) {
                continue;
            }
            if (isContactForm($f) && findEmailField($f).length > 0) {
                return [f];
            }
        }

        return [];
    }

    function isContactForm($form) {
        var $emailInputs = $form.find('input[type="email"], input[name*="email"], input[name*="Email"]');
        var $textareas = $form.find('textarea');
        var $submitBtn = $form.find('input[type="submit"], button[type="submit"], button:not([type])');

        var formId = ($form.attr('id') || '').toLowerCase();
        var formClass = ($form.attr('class') || '').toLowerCase();
        var formAction = ($form.attr('action') || '').toLowerCase();

        var keywords = ['contact', 'inquiry', 'enquiry', 'message', 'feedback', 'support', 'wpcf7', 'wpforms', 'formidable', 'ninja'];
        var hasKeyword = keywords.some(function(kw) {
            return formId.indexOf(kw) !== -1 || 
                   formClass.indexOf(kw) !== -1 || 
                   formAction.indexOf(kw) !== -1;
        });

        if (hasKeyword && $emailInputs.length > 0) {
            return true;
        }

        if ($emailInputs.length >= 1 && $textareas.length >= 1 && $submitBtn.length > 0) {
            return true;
        }

        return false;
    }

    function findEmailField($form) {
        return $form.find('input[type="email"], input[name*="email"], input[name*="Email"]').first();
    }

    function findMessageField($form) {
        return $form.find('textarea').first();
    }

    function validateContact(email, message, recaptchaToken) {
        return new Promise(function(resolve, reject) {
            var data = {
                action: 'wsc_validate_contact',
                nonce: nonce,
                email: email,
                message: message
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
                        resolve({ status: false, errors: [{ field: 'form', message: t('validationFailed', 'Validation failed.') }] });
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
        var $error = $('<span class="wsc-field-error wsc-contact-error">' + message + '</span>');
        $field.after($error);
    }

    function clearFieldError($field) {
        $field.removeClass('wsc-field-invalid');
        $field.siblings('.wsc-contact-error').remove();
        $field.parent().find('.wsc-contact-error').remove();
    }

    function clearAllErrors($form) {
        $form.find('.wsc-field-invalid').removeClass('wsc-field-invalid');
        $form.find('.wsc-contact-error').remove();
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
                        grecaptcha.execute(recaptchaSiteKey, { action: 'contact_guard' })
                            .then(function(token) {
                                resolve(token);
                            })
                            .catch(function(err) {
                                console.error('[Contact Guard] reCAPTCHA v3 error:', err);
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
                        console.log('[Contact Guard] reCAPTCHA already rendered or error:', e);
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
        
        if ($form.data('wsc-contact-guard')) {
            return;
        }

        var $emailField = findEmailField($form);
        var $messageField = findMessageField($form);

        if ($emailField.length === 0) {
            return;
        }

        $form.data('wsc-contact-guard', true);
        contactGuardAttached = true;
        console.log('[WP Span Checker] Contact Guard attached to form:', form);

        var $originalSubmit = $form.find('input[type="submit"], button[type="submit"]').first();
        if (!$originalSubmit.length) {
            $originalSubmit = $form.find('button:not([type])').first();
        }

        var $actionsWrap = $('<div class="wsc-guard-actions"></div>');
        $originalSubmit.after($actionsWrap);

        var submitText = $originalSubmit.val() || $originalSubmit.text() || t('submit', 'Submit');
        var $validationBtn = $('<input type="button" class="wsc-validation-btn wsc-contact-validation-btn">');
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
            var message = $messageField.length > 0 ? $messageField.val().trim() : '';

            var hasError = false;
            $form.find('[required]').each(function() {
                var $field = $(this);
                var val = $field.val();
                if (!val || val.trim() === '') {
                    showFieldError($field, t('fieldRequired', 'This field is required.'));
                    hasError = true;
                }
            });

            if (hasError) {
                showToast('error', t('checkFields', 'Please fill in all required fields.'));
                return;
            }

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
                    return validateContact(email, message, recaptchaToken);
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
                        console.log('[WP Span Checker] Contact Guard: Validation passed, submitting form');
                        
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
                        if (result.errors && result.errors.length > 0) {
                            result.errors.forEach(function(err) {
                                if (err.field === 'email') {
                                    showFieldError($emailField, err.message);
                                } else if (err.field === 'message' && $messageField.length > 0) {
                                    showFieldError($messageField, err.message);
                                }
                            });
                            showToast('error', result.errors[0].message);
                        } else if (result.message) {
                            showToast('error', result.message);
                        } else {
                            showToast('error', t('validationFailed', 'Validation failed.'));
                        }

                        if (recaptchaEnabled && recaptchaVersion === 'v2' && typeof grecaptcha !== 'undefined') {
                            grecaptcha.reset();
                            $validationBtn.prop('disabled', true).css('opacity', '0.6');
                        }
                    }
                })
                .catch(function(err) {
                    isValidating = false;
                    $validationBtn.val(submitText).prop('disabled', recaptchaEnabled && recaptchaVersion === 'v2');
                    console.error('[WP Span Checker] Contact Guard error:', err);
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
        console.log('[WP Span Checker] Contact Guard initializing...');
        
        loadRecaptchaScript();
        
        var forms = detectContactForms();
        console.log('[WP Span Checker] Contact Guard found', forms.length, 'form(s)');
        
        forms.forEach(function(form) {
            setupGuard(form);
        });

        if (typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(function(mutations) {
                if (contactGuardAttached) {
                    return;
                }
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length > 0) {
                        setTimeout(function() {
                            if (contactGuardAttached) {
                                return;
                            }
                            var newForms = detectContactForms();
                            newForms.forEach(function(form) {
                                if (!$(form).data('wsc-contact-guard')) {
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

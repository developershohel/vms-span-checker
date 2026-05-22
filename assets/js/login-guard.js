/**
 * VMS Span Checker - Login Guard
 * Adds reCAPTCHA protection to WordPress login forms.
 */
(function($) {
    'use strict';

    if (typeof WPSpanLoginGuard === 'undefined') {
        return;
    }

    var config = WPSpanLoginGuard;
    var recaptchaEnabled = config.recaptchaEnabled || false;
    var recaptchaSiteKey = config.recaptchaSiteKey || '';
    var recaptchaVersion = config.recaptchaVersion || 'v2';
    var formSelector = config.formSelector || '';
    var i18n = config.i18n || {};
    var recaptchaWidgetId = null;
    var loginGuardAttached = false;

    function t(key, fallback) {
        return i18n[key] || fallback || key;
    }

    function resolveFormElement(el) {
        var $el = $(el);
        if ($el.is('form')) {
            return el;
        }
        var $inner = $el.find('form').first();
        return $inner.length ? $inner[0] : null;
    }

    function isButtonVisible($btn) {
        if (!$btn.length) return false;
        if ($btn.attr('aria-hidden') === 'true') return false;
        if ($btn.attr('tabindex') === '-1') {
            var styles = $btn.attr('style') || '';
            if (styles.indexOf('display: none') !== -1 || 
                styles.indexOf('visibility: hidden') !== -1 ||
                (styles.indexOf('position: absolute') !== -1 && styles.indexOf('left: -9999') !== -1)) {
                return false;
            }
        }
        try {
            var computed = window.getComputedStyle($btn[0]);
            if (computed.display === 'none' || computed.visibility === 'hidden' || computed.opacity === '0') {
                return false;
            }
        } catch(e) {}
        return true;
    }

    function findVisibleSubmit($form, selector) {
        var $buttons = $form.find(selector);
        for (var i = 0; i < $buttons.length; i++) {
            var $btn = $buttons.eq(i);
            if (isButtonVisible($btn)) return $btn;
        }
        return $();
    }

    function findSubmitButton($form) {
        // 1. WordPress login specific submit
        var $btn = $form.find('#wp-submit').first();
        if ($btn.length && isButtonVisible($btn)) return $btn;
        
        // 2. input[type="submit"] - visible
        $btn = findVisibleSubmit($form, 'input[type="submit"]');
        if ($btn.length) return $btn;
        
        // 3. button[type="submit"] - visible
        $btn = findVisibleSubmit($form, 'button[type="submit"]:not(.quform-default-submit)');
        if ($btn.length) return $btn;
        
        // 4. Common login submit classes - visible
        $btn = findVisibleSubmit($form, '.submit, .login-submit, .btn-submit, .wp-submit');
        if ($btn.length) return $btn;
        
        // 5. Any element with type="submit" - visible
        $btn = findVisibleSubmit($form, '[type="submit"]');
        if ($btn.length) return $btn;
        
        // 6. Button without type - visible
        $btn = findVisibleSubmit($form, 'button:not([type]):not([aria-hidden="true"])');
        if ($btn.length) return $btn;
        
        // 7. Button not explicitly button/reset - visible
        $btn = findVisibleSubmit($form, 'button:not([type="button"]):not([type="reset"]):not([aria-hidden="true"])');
        if ($btn.length) return $btn;
        
        // 8. Last visible button as fallback
        var $allButtons = $form.find('button');
        for (var i = $allButtons.length - 1; i >= 0; i--) {
            var $b = $allButtons.eq(i);
            if (isButtonVisible($b)) return $b;
        }
        
        // 9. Last input[type="button"]
        $btn = $form.find('input[type="button"]').last();
        if ($btn.length && isButtonVisible($btn)) return $btn;
        
        return $();
    }

    function setupLoginGuard() {
        if (loginGuardAttached) {
            return;
        }

        var $form = null;
        
        // Use provided form selector if available
        if (formSelector && formSelector.trim() !== '') {
            var matches = $(formSelector);
            for (var i = 0; i < matches.length; i++) {
                var formEl = resolveFormElement(matches[i]);
                if (formEl) {
                    $form = $(formEl);
                    break;
                }
            }
        }
        
        // Fall back to default WordPress login selectors if no custom selector
        if (!$form || !$form.length) {
            $form = $('#loginform, #registerform, .login form').first();
        }
        
        if (!$form || !$form.length) {
            console.log('[VMS Span Checker] Login Guard: No login form found.');
            return;
        }

        if ($form.data('wsc-login-guard')) {
            return;
        }

        // Check if form is already protected by another guard
        if ($form.data('wsc-guard-protected')) {
            console.log('[VMS Span Checker] Login Guard: Form already protected by another guard, skipping');
            return;
        }

        $form.data('wsc-login-guard', true);
        $form.data('wsc-guard-protected', true); // Mark as protected
        loginGuardAttached = true;
        console.log('[VMS Span Checker] Login Guard initializing on form:', $form[0]);

        if (!recaptchaEnabled || !recaptchaSiteKey) {
            return;
        }

        var $submitBtn = findSubmitButton($form);
        
        if (!$submitBtn.length) {
            console.log('[VMS Span Checker] Login Guard: No submit button found');
            return;
        }

        if (recaptchaVersion === 'v2') {
            var uniqueId = 'wsc-login-recaptcha-' + Math.random().toString(36).substr(2, 9);
            var recaptchaContainer = $('<div id="' + uniqueId + '" class="wsc-login-recaptcha" style="margin-bottom: 16px;"></div>');
            $submitBtn.closest('p, .submit, .login-submit').before(recaptchaContainer);
            
            $submitBtn.prop('disabled', true).css('opacity', '0.6');
            
            var checkRecaptcha = function() {
                if (typeof grecaptcha !== 'undefined' && grecaptcha.render) {
                    try {
                        recaptchaWidgetId = grecaptcha.render(recaptchaContainer[0], {
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
                        console.log('[Login Guard] reCAPTCHA already rendered or error:', e);
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
                        grecaptcha.execute(recaptchaSiteKey, { action: 'login' })
                            .then(function(token) {
                                $form.find('input[name="wsc_recaptcha_token"]').remove();
                                $form.append('<input type="hidden" name="wsc_recaptcha_token" value="' + token + '">');
                                $form[0].submit();
                            })
                            .catch(function(err) {
                                console.error('[Login Guard] reCAPTCHA v3 error:', err);
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
        if (!recaptchaEnabled || !recaptchaSiteKey) {
            return;
        }

        if (typeof grecaptcha !== 'undefined') {
            setupLoginGuard();
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
            setupLoginGuard();
        };
        document.head.appendChild(script);
    }

    $(document).ready(function() {
        loadRecaptchaScript();
    });

})(jQuery);

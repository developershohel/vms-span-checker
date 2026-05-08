/**
 * WP Span Checker - Login Guard
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
    var i18n = config.i18n || {};

    function t(key, fallback) {
        return i18n[key] || fallback || key;
    }

    function setupLoginGuard() {
        var $form = $('#loginform, #registerform, .login form');
        
        if (!$form.length) {
            return;
        }

        console.log('[WP Span Checker] Login Guard initializing...');

        if (!recaptchaEnabled || !recaptchaSiteKey) {
            return;
        }

        var $submitBtn = $form.find('#wp-submit, input[type="submit"], button[type="submit"]').first();
        
        if (!$submitBtn.length) {
            return;
        }

        if (recaptchaVersion === 'v2') {
            var recaptchaContainer = $('<div class="wsc-login-recaptcha" style="margin-bottom: 16px;"></div>');
            $submitBtn.closest('p, .submit, .login-submit').before(recaptchaContainer);
            
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

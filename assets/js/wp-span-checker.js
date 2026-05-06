const wpSpanCheckerToast = Swal.mixin({
    toast: true,
    position: 'center',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

/**
 * Form Guard registry for tracking guarded forms.
 */
(function () {
    if (typeof window === 'undefined') {
        return;
    }
    var g = (window.wpSpanCheckerFormGuard = window.wpSpanCheckerFormGuard || {});
    if (!g.registry) {
        g.registry =
            typeof WeakMap !== 'undefined' ? new WeakMap() : typeof Map !== 'undefined' ? new Map() : null;
    }
})();

jQuery(function ($) {
    console.log('[WP Span Checker] Form Guard initializing...');
    
    const I = typeof WPSpanChecker !== 'undefined' && WPSpanChecker.i18n ? WPSpanChecker.i18n : {};
    const t = function (key, fallback) {
        return I[key] !== undefined && I[key] !== '' ? I[key] : fallback;
    };

    const settings = WPSpanChecker.settings || [];
    const ajaxUrl = WPSpanChecker.ajaxUrl;
    const nonce = WPSpanChecker.nonce;

    console.log('WPSpanChecker', WPSpanChecker);
    console.log('ajaxUrl', ajaxUrl);
    console.log('nonce', nonce);
    console.log('[WP Span Checker] Settings loaded:', settings.length, 'mapping(s)');

    function wscLooksCombinedSelector(fid) {
        const s = String(fid || '').trim();
        return (
            s !== '' &&
            (/^[#.[]/.test(s) || s.indexOf('#') !== -1 || s.indexOf('.') !== -1 || s.indexOf('[') !== -1)
        );
    }

    function wscEscapeSel(seg) {
        if (typeof $.escapeSelector === 'function') {
            return $.escapeSelector(seg);
        }
        return seg.replace(/([!"#$%&'()*+,./:;<=>?@[\]^`{|}~])/g, '\\$1');
    }

    function resolveForm$(formId, formClass) {
        const fid = String(formId || '').trim();
        const fcls = String(formClass || '').trim();
        if (wscLooksCombinedSelector(fid)) {
            const $hit = $(fid);
            if (!$hit.length) {
                return $();
            }
            const $formHit = $hit.filter('form').first();
            if ($formHit.length) {
                return $formHit;
            }
            const $inForm = $hit.closest('form').first();
            return $inForm.length ? $inForm : $();
        }
        const id = fid.replace(/^#/, '');
        const rawClass = fcls.replace(/^\./g, '');
        const classes = rawClass.split(/\s+/).filter(Boolean).map(function (c) {
            return c.replace(/^\./, '');
        });
        if (id && classes.length) {
            return $('#' + wscEscapeSel(id) + '.' + classes.join('.'));
        }
        if (id) {
            return $('#' + wscEscapeSel(id));
        }
        if (classes.length) {
            return $('.' + classes.join('.'));
        }
        return $();
    }

    function resolveSubmit$($form, submitSelector) {
        const raw = String(submitSelector || '').trim();
        if (raw) {
            let $btn = $form.find(raw);
            if (!$btn.length) {
                $btn = $(raw);
            }
            if ($btn.length) {
                console.log('[WP Span Checker] Found submit button with custom selector:', raw);
                return $btn.first();
            }
        }
        
        let $btn = $form.find('input[type="submit"]').first();
        if ($btn.length) {
            console.log('[WP Span Checker] Found input[type="submit"]');
            return $btn;
        }
        
        $btn = $form.find('button[type="submit"]').first();
        if ($btn.length) {
            console.log('[WP Span Checker] Found button[type="submit"]');
            return $btn;
        }
        
        $btn = $form.find('.wpcf7-submit').first();
        if ($btn.length) {
            console.log('[WP Span Checker] Found .wpcf7-submit');
            return $btn;
        }
        
        $btn = $form.find('[type="submit"]').first();
        if ($btn.length) {
            console.log('[WP Span Checker] Found [type="submit"]');
            return $btn;
        }
        
        $btn = $form.find('button:not([type="button"]):not([type="reset"])').first();
        if ($btn.length) {
            console.log('[WP Span Checker] Found button (default type=submit)');
            return $btn;
        }
        
        $btn = $form.find('button').last();
        if ($btn.length) {
            console.log('[WP Span Checker] Found last button as fallback');
            return $btn;
        }
        
        console.log('[WP Span Checker] No submit button found in form');
        return $();
    }

    function generateFieldClassId($form, fieldId, fieldClass) {
        const id = String(fieldId || '').trim().replace(/^#/, '');
        const rawClass = String(fieldClass || '').trim().replace(/^\./g, '');
        const classes = rawClass.split(/\s+/).filter(Boolean).map(function (c) {
            return c.replace(/^\./, '');
        });

        if (wscLooksCombinedSelector(id)) {
            const $h = $form.find(id);
            return $h.length ? $h : $(id);
        }
        if (id && classes.length) {
            return $form.find('#' + wscEscapeSel(id) + '.' + classes.join('.'));
        }
        if (id) {
            return $form.find('#' + wscEscapeSel(id));
        }
        if (classes.length) {
            return $form.find('.' + classes.join('.'));
        }
        return $();
    }

    function fieldServerGate(field) {
        const f = field.field_type || field.field || '';
        const iv = parseInt(field.isValidate, 10) || 0;
        const regex = String(field.regex || '').trim();
        const ai = parseInt(field.textarea_ai_spam, 10) || 0;
        if (regex !== '') {
            return true;
        }
        if ('textarea' === f) {
            const allow =
                field.textarea_allow_links === undefined ||
                field.textarea_allow_links === null ||
                String(field.textarea_allow_links) === ''
                    ? true
                    : parseInt(field.textarea_allow_links, 10) !== 0;
            if (!allow || ai || iv) {
                return true;
            }
            return false;
        }
        if ('text' === f) {
            const allowTu =
                field.text_allow_urls === undefined ||
                field.text_allow_urls === null ||
                String(field.text_allow_urls) === ''
                    ? true
                    : parseInt(field.text_allow_urls, 10) !== 0;
            if (!allowTu || iv) {
                return true;
            }
            return false;
        }
        if ('username' === f) {
            const uchkn = parseInt(field.check_username_exists, 10) || 0;
            if (uchkn || iv) {
                return true;
            }
            return false;
        }
        if (('email' === f || 'url' === f) && iv) {
            return true;
        }
        return !!iv;
    }

    function readFieldValue($el, fieldType) {
        if (!$el.length) {
            return '';
        }
        const tag = ($el.prop('tagName') || '').toLowerCase();
        if (tag === 'textarea') {
            return String($el.val() || '');
        }
        return String($el.val() || '');
    }

    function applyRequired($el, on) {
        if (!$el.length) {
            return;
        }
        if (on) {
            $el.attr('required', 'required');
        } else {
            $el.removeAttr('required');
        }
    }

    function validateUrlServer(domain, type, apiSettings) {
        return new Promise(function (resolve, reject) {
            jQuery.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'validateDomainName',
                    domain: domain,
                    type: type || 'unknown',
                    settings: apiSettings,
                    nonce: nonce,
                },
                success: function (response) {
                    if (response && response.success && response.data) {
                        resolve(response.data);
                        return;
                    }
                    const msg =
                        response && response.data && response.data.message
                            ? response.data.message
                            : t('validationFailed', 'Validation failed');
                    reject(new Error(msg));
                },
                error: function (xhr) {
                    reject(new Error(xhr.statusText || 'Request failed'));
                },
            });
        });
    }

    function validateGuardFieldServer(mappingId, fieldIndex, value) {
        return new Promise(function (resolve, reject) {
            jQuery.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'validateFormGuardField',
                    nonce: nonce,
                    mappingId: mappingId,
                    fieldIndex: fieldIndex,
                    value: value,
                },
                success: function (response) {
                    if (response && response.success && response.data) {
                        resolve(response.data);
                        return;
                    }
                    const msg =
                        response && response.data && response.data.message
                            ? response.data.message
                            : t('validationFailed', 'Validation failed');
                    reject(new Error(msg));
                },
                error: function (xhr) {
                    let msg = xhr.statusText || t('requestFailed', 'Request failed');
                    try {
                        const j = xhr.responseJSON;
                        if (j && j.data && j.data.message) {
                            msg = j.data.message;
                        }
                    } catch (e) {
                        /* ignore */
                    }
                    reject(new Error(msg));
                },
            });
        });
    }

    function disableSubmitButton(submitButton) {
        submitButton.prop('disabled', true);
    }

    function enableSubmitButton(submitButton) {
        submitButton.prop('disabled', false);
    }

    function apiPayloadFromField(field, fallbackWr, fallbackVt) {
        const ft = field.field_type || field.field || '';
        if (ft !== 'email' && ft !== 'url') {
            return [{ is_webrisk: 0, is_virustotal: 0 }];
        }
        const wr =
            field.is_webrisk !== undefined && field.is_webrisk !== null && String(field.is_webrisk) !== ''
                ? parseInt(field.is_webrisk, 10) || 0
                : fallbackWr;
        const vt =
            field.is_virustotal !== undefined && field.is_virustotal !== null && String(field.is_virustotal) !== ''
                ? parseInt(field.is_virustotal, 10) || 0
                : fallbackVt;
        return [{ is_webrisk: wr ? 1 : 0, is_virustotal: vt ? 1 : 0 }];
    }

    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    function getDomainFromEmail(email) {
        const parts = String(email).split('@');
        return parts.length > 1 ? parts[1] : '';
    }

    function isValidUrl(value) {
        const pattern = /^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(\/\S*)?$/;
        return pattern.test(value);
    }

    function addChangeEventUrl(element, submitButton, apiSettings) {
        element.on('change', function () {
            let inputVal = String($(this).val() || '').trim();
            if (!inputVal.length) {
                disableSubmitButton(submitButton);
                return;
            }
            if (!isValidUrl(inputVal)) {
                wpSpanCheckerToast.fire({
                    icon: 'error',
                    title: t('urlNotValid', 'URL not valid'),
                });
                disableSubmitButton(submitButton);
                return;
            }
            if (!/^https?:\/\//i.test(inputVal)) {
                inputVal = 'https://' + inputVal;
            }
            validateUrlServer(inputVal, 'url', apiSettings)
                .then(function (result) {
                    if (result.status) {
                        wpSpanCheckerToast.fire({
                            icon: 'success',
                            title: result.message || t('urlValid', 'URL is valid'),
                        });
                        enableSubmitButton(submitButton);
                    } else {
                        wpSpanCheckerToast.fire({
                            icon: 'error',
                            title: result.message || t('urlNotValid', 'URL not valid'),
                        });
                        disableSubmitButton(submitButton);
                    }
                })
                .catch(function (err) {
                    wpSpanCheckerToast.fire({
                        icon: 'error',
                        title: err.message || t('validationFailed', 'Validation failed'),
                    });
                    disableSubmitButton(submitButton);
                });
        });
    }

    function addChangeEvent(element, fieldType, submitButton, apiSettings) {
        element.on('change', function () {
            const inputVal = String($(this).val() || '').trim();
            if (!inputVal.length) {
                disableSubmitButton(submitButton);
                return;
            }
            if (fieldType === 'email') {
                if (!isValidEmail(inputVal)) {
                    wpSpanCheckerToast.fire({
                        icon: 'error',
                        title: t('emailInvalid', 'Email address is invalid'),
                    });
                    disableSubmitButton(submitButton);
                    return;
                }
                const domainName = getDomainFromEmail(inputVal);
                if (!domainName) {
                    wpSpanCheckerToast.fire({
                        icon: 'error',
                        title: t('emailInvalid', 'Email address is invalid'),
                    });
                    disableSubmitButton(submitButton);
                    return;
                }
                validateUrlServer(domainName, 'email', apiSettings)
                    .then(function (result) {
                        if (result.status) {
                            wpSpanCheckerToast.fire({
                                icon: 'success',
                                title: result.message,
                            });
                            enableSubmitButton(submitButton);
                        } else {
                            wpSpanCheckerToast.fire({
                                icon: 'error',
                                title: result.message || t('emailInvalid', 'Email address is invalid'),
                            });
                            disableSubmitButton(submitButton);
                        }
                    })
                    .catch(function (err) {
                        wpSpanCheckerToast.fire({
                            icon: 'error',
                            title: err.message || t('validationFailed', 'Validation failed'),
                        });
                        disableSubmitButton(submitButton);
                    });
            }
        });
    }

    function addInputEvent(element, fieldType, submitButton, apiSettings) {
        element.on('input', function () {
            const inputVal = String($(this).val() || '').trim();
            if (fieldType === 'email' && inputVal && isValidEmail(inputVal)) {
                const domainName = getDomainFromEmail(inputVal);
                if (domainName) {
                    validateUrlServer(domainName, 'email', apiSettings).catch(function () {
                        /* optional debounce later */
                    });
                }
                return;
            }
            if (fieldType === 'url' && inputVal && isValidUrl(inputVal)) {
                let u = inputVal;
                if (!/^https?:\/\//i.test(u)) {
                    u = 'https://' + u;
                }
                validateUrlServer(u, 'url', apiSettings).catch(function () {
                    /* optional debounce */
                });
            }
        });
    }

    function bindUsernameLiveCheck(field$, submitButton, mappingId, fieldIndex) {
        let timer = null;
        function schedule(val) {
            if (timer) {
                clearTimeout(timer);
            }
            timer = setTimeout(function () {
                timer = null;
                const trimmed = String(val || '').trim();
                if (!trimmed.length) {
                    enableSubmitButton(submitButton);
                    return;
                }
                validateGuardFieldServer(mappingId, fieldIndex, trimmed)
                    .then(function (data) {
                        if (data && data.status) {
                            enableSubmitButton(submitButton);
                        } else {
                            disableSubmitButton(submitButton);
                            if (data && data.message) {
                                wpSpanCheckerToast.fire({
                                    icon: 'error',
                                    title: data.message,
                                });
                            }
                        }
                    })
                    .catch(function (err) {
                        disableSubmitButton(submitButton);
                        wpSpanCheckerToast.fire({
                            icon: 'error',
                            title: err.message || t('validationFailed', 'Validation failed'),
                        });
                    });
            }, 450);
        }
        field$.off('.wscUsernameLive').on('input.wscUsernameLive change.wscUsernameLive', function () {
            schedule($(this).val());
        });
    }

    /**
     * Form Guard registry lives on window.
     */
    function wscGuardRegistry() {
        return window.wpSpanCheckerFormGuard && window.wpSpanCheckerFormGuard.registry;
    }

    function wscGetFormGuardEntry(formEl) {
        const reg = wscGuardRegistry();
        if (!formEl || !reg) {
            return null;
        }
        let entry = reg.get(formEl);
        if (!entry) {
            entry = {
                configs: [],
                validating: false,
                originalSubmitBtn: null,
                validationBtn: null,
            };
            reg.set(formEl, entry);
        }
        return entry;
    }

    function wscRegisterFormGuardConfig(formEl, config) {
        const entry = wscGetFormGuardEntry(formEl);
        if (!entry) {
            return;
        }
        entry.configs.push(config);
    }

    /**
     * Create validation button and hide original submit button.
     * This is the new approach: validation button triggers validation,
     * then clicks the original hidden submit button on success.
     */
    function wscSetupValidationButton($form, $originalSubmit, entry) {
        if (!$originalSubmit.length) {
            console.log('[WP Span Checker] No submit button found in form');
            return;
        }
        
        if (entry.validationBtn) {
            console.log('[WP Span Checker] Validation button already exists');
            return;
        }

        const formEl = $form.get(0);
        const originalEl = $originalSubmit.get(0);

        const btnText = $originalSubmit.val() || $originalSubmit.text() || t('submit', 'Submit');
        const btnType = ($originalSubmit.prop('tagName') || 'button').toLowerCase();

        let $validationBtn;
        if (btnType === 'input') {
            $validationBtn = $('<input type="button">').val(btnText);
        } else {
            $validationBtn = $('<button type="button">').html($originalSubmit.html() || btnText);
        }

        const originalClasses = $originalSubmit.attr('class') || '';
        if (originalClasses) {
            const classesWithoutSubmit = originalClasses.replace(/\bwpcf7-submit\b/g, '').trim();
            $validationBtn.attr('class', classesWithoutSubmit);
        }
        $validationBtn.addClass('wsc-validation-btn');

        const computedStyle = window.getComputedStyle(originalEl);
        const cssProps = [
            'background', 'backgroundColor', 'color', 'fontSize', 'fontWeight', 'fontFamily',
            'padding', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
            'margin', 'marginTop', 'marginRight', 'marginBottom', 'marginLeft',
            'border', 'borderRadius', 'width', 'height', 'minWidth', 'minHeight',
            'lineHeight', 'textTransform', 'letterSpacing', 'boxShadow', 'cursor',
            'display', 'textAlign', 'verticalAlign'
        ];
        cssProps.forEach(function(prop) {
            try {
                const val = computedStyle.getPropertyValue(prop.replace(/([A-Z])/g, '-$1').toLowerCase());
                if (val && val !== '') {
                    $validationBtn.css(prop.replace(/([A-Z])/g, '-$1').toLowerCase(), val);
                }
            } catch(e) {}
        });

        $originalSubmit.hide();
        $originalSubmit.css({
            'position': 'absolute',
            'left': '-9999px',
            'visibility': 'hidden',
            'pointer-events': 'none',
            'opacity': '0',
            'width': '0',
            'height': '0'
        });

        $validationBtn.insertAfter($originalSubmit);

        entry.originalSubmitBtn = originalEl;
        entry.validationBtn = $validationBtn.get(0);
        entry.originalBtnText = btnText;

        console.log('[WP Span Checker] Validation button created for form, original text:', btnText);

        $validationBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (entry.validating) {
                return;
            }

            wscRunValidation(formEl, entry, $validationBtn);
        });
    }

    /**
     * Run validation for all configured fields, then submit if valid.
     */
    function wscRunValidation(formEl, entry, $validationBtn) {
        entry.validating = true;
        const originalBtnText = entry.originalBtnText || ($validationBtn.is('input') ? $validationBtn.val() : $validationBtn.html());
        const validatingText = t('validating', 'Validating...');

        console.log('[WP Span Checker] Starting validation...');

        if ($validationBtn.is('input')) {
            $validationBtn.val(validatingText);
        } else {
            $validationBtn.html(validatingText);
        }
        $validationBtn.prop('disabled', true);

        const before = new CustomEvent('wp_span_checker:guard_before_validate', {
            cancelable: true,
            bubbles: true,
            detail: { form: formEl },
        });
        if (!formEl.dispatchEvent(before)) {
            wscResetValidationBtn($validationBtn, originalBtnText, entry);
            return;
        }

        const jobs = [];
        entry.configs.forEach(function (cfg) {
            const mappingId = cfg.mappingId;
            const formSettingData = cfg.formSettingData || [];
            formSettingData.forEach(function (fs, idx) {
                const ft = fs.field_type || fs.field || '';
                const $el = generateFieldClassId($(formEl), fs.id, fs.class);
                const val = readFieldValue($el, ft);
                const required = parseInt(fs.isRequired, 10) || 0;
                const nonEmpty = String(val).trim() !== '';
                if (required || (nonEmpty && fieldServerGate(fs))) {
                    jobs.push({ mappingId: mappingId, idx: idx, val: val, fieldName: fs.id || fs.class || '' });
                }
            });
        });

        if (jobs.length === 0) {
            console.log('[WP Span Checker] No validation jobs, submitting directly');
            wscSubmitOriginalForm(formEl, entry, $validationBtn, originalBtnText);
            return;
        }

        console.log('[WP Span Checker] Validation jobs:', jobs.length);

        let step = Promise.resolve(true);
        jobs.forEach(function (job) {
            step = step.then(function () {
                return validateGuardFieldServer(job.mappingId, job.idx, job.val).then(function (data) {
                    if (!data || !data.status) {
                        throw new Error((data && data.message) || t('validationFailed', 'Validation failed'));
                    }
                });
            });
        });

        step
            .then(function () {
                console.log('[WP Span Checker] Validation passed');
                formEl.dispatchEvent(
                    new CustomEvent('wp_span_checker:guard_validated', {
                        bubbles: true,
                        detail: { form: formEl },
                    })
                );
                wscSubmitOriginalForm(formEl, entry, $validationBtn, originalBtnText);
            })
            .catch(function (err) {
                console.log('[WP Span Checker] Validation failed:', err.message);
                wpSpanCheckerToast.fire({
                    icon: 'error',
                    title: err.message || t('validationFailed', 'Validation failed'),
                });
                wscResetValidationBtn($validationBtn, originalBtnText, entry);
            });
    }

    /**
     * Submit the form by clicking the original hidden submit button.
     */
    function wscSubmitOriginalForm(formEl, entry, $validationBtn, originalBtnText) {
        const submittingText = t('submitting', 'Submitting...');
        if ($validationBtn.is('input')) {
            $validationBtn.val(submittingText);
        } else {
            $validationBtn.html(submittingText);
        }

        console.log('[WP Span Checker] Submitting form...');

        setTimeout(function() {
            if (entry.originalSubmitBtn) {
                const $orig = $(entry.originalSubmitBtn);
                $orig.css({
                    'position': '',
                    'left': '',
                    'visibility': '',
                    'pointer-events': '',
                    'opacity': '',
                    'width': '',
                    'height': ''
                }).show();

                entry.originalSubmitBtn.click();

                setTimeout(function() {
                    $orig.hide().css({
                        'position': 'absolute',
                        'left': '-9999px',
                        'visibility': 'hidden',
                        'pointer-events': 'none',
                        'opacity': '0',
                        'width': '0',
                        'height': '0'
                    });
                }, 100);
            } else {
                HTMLFormElement.prototype.submit.call(formEl);
            }

            setTimeout(function() {
                wscResetValidationBtn($validationBtn, originalBtnText, entry);
            }, 1000);
        }, 100);
    }

    /**
     * Reset validation button to original state.
     */
    function wscResetValidationBtn($validationBtn, originalBtnText, entry) {
        if ($validationBtn.is('input')) {
            $validationBtn.val(originalBtnText);
        } else {
            $validationBtn.html(originalBtnText);
        }
        $validationBtn.prop('disabled', false);
        entry.validating = false;
    }

    settings.forEach(function (setting) {
        const mappingId = parseInt(setting.id, 10) || 0;
        const fallbackWr = parseInt(setting.is_webrisk, 10) || 0;
        const fallbackVt = parseInt(setting.is_virustotal, 10) || 0;
        const form_id = setting.form_id ?? '';
        const form_class = setting.form_class ?? '';
        const submit_selector = setting.submit_selector ?? '';
        const rawSettings = setting.settings ? setting.settings : '{}';
        let formSettingData;
        try {
            formSettingData = typeof rawSettings === 'string' ? JSON.parse(rawSettings) : rawSettings;
        } catch (e) {
            formSettingData = [];
        }
        if (!Array.isArray(formSettingData)) {
            formSettingData = [];
        }

        const hasFormSelector = String(form_id || '').trim() !== '' || String(form_class || '').trim() !== '';
        const hasSubmitSelector = String(submit_selector || '').trim() !== '';
        
        let $form;
        let submitButton;
        
        if (hasFormSelector) {
            $form = resolveForm$(form_id, form_class);
            if (!$form.length) {
                console.log('[WP Span Checker] Form not found for selector:', form_id || form_class);
                return;
            }
            console.log('[WP Span Checker] Form found by form selector for mapping ID:', mappingId);
            submitButton = resolveSubmit$($form, submit_selector);
        } else if (hasSubmitSelector) {
            const $submitBtn = $(submit_selector).first();
            if (!$submitBtn.length) {
                console.log('[WP Span Checker] Submit button not found for selector:', submit_selector);
                return;
            }
            $form = $submitBtn.closest('form');
            if (!$form.length) {
                console.log('[WP Span Checker] No form found containing submit button:', submit_selector);
                return;
            }
            console.log('[WP Span Checker] Form found by submit selector for mapping ID:', mappingId);
            submitButton = $submitBtn;
        } else {
            console.log('[WP Span Checker] No form selector or submit selector configured for mapping ID:', mappingId);
            return;
        }

        const formEl = $form.get(0);
        if (!formEl || formEl.tagName !== 'FORM') {
            console.log('[WP Span Checker] Element is not a FORM, skipping');
            return;
        }

        wscRegisterFormGuardConfig(formEl, {
            mappingId: mappingId,
            formSettingData: formSettingData,
        });

        const entry = wscGetFormGuardEntry(formEl);

        console.log('[WP Span Checker] Setting up validation button for form');
        wscSetupValidationButton($form, submitButton, entry);

        formSettingData.forEach(function (formSetting, fieldIndex) {
            const eventType = formSetting.event;
            const fieldType = formSetting.field_type || formSetting.field || '';
            const apiOpts = apiPayloadFromField(formSetting, fallbackWr, fallbackVt);
            const reqOn = parseInt(formSetting.isRequired, 10) || 0;

            const $field = generateFieldClassId($form, formSetting.id, formSetting.class);
            applyRequired($field, !!reqOn);

            if (eventType === 'submit') {
                return;
            }
            if (!$field.length) {
                return;
            }
            switch (eventType) {
                case 'change':
                    if (fieldType === 'email') {
                        addChangeEvent($field, 'email', submitButton, apiOpts);
                    } else if (fieldType === 'url') {
                        addChangeEventUrl($field, submitButton, apiOpts);
                    }
                    break;
                case 'input':
                    addInputEvent($field, fieldType, submitButton, apiOpts);
                    break;
                default:
                    break;
            }
            if (
                fieldType === 'username' &&
                mappingId &&
                (parseInt(formSetting.check_username_exists, 10) || 0)
            ) {
                bindUsernameLiveCheck($field, submitButton, mappingId, fieldIndex);
            }
        });
    });

    /* Optional demo hooks: only bind when matching markup exists */
    const $verifyTempEmailBtn = $('#verifyTempEmailBtn');
    const $email = $('#email');
    const $password = $('#password');

    function isPasswordStrong(pwd) {
        return typeof pwd === 'string' && pwd.length >= 8;
    }

    if ($verifyTempEmailBtn.length && $email.length && $password.length) {
        $verifyTempEmailBtn.on('click', function (e) {
            e.preventDefault();
            const getEmail = $email.val().trim();
            const password = $password.val().trim();
            if (getEmail === '') {
                wpSpanCheckerToast.fire({ icon: 'error', title: t('emailFieldRequired', 'Email is required') });
                return;
            }
            if (password === '') {
                wpSpanCheckerToast.fire({ icon: 'error', title: t('passwordRequired', 'Password is required') });
                return;
            }
            if (!isPasswordStrong(password)) {
                wpSpanCheckerToast.fire({
                    icon: 'error',
                    title: t('passwordRequirements', 'Password must meet all requirements.'),
                });
                return;
            }
            const regDomain = getDomainFromEmail(getEmail);
            if (!regDomain) {
                wpSpanCheckerToast.fire({
                    icon: 'error',
                    title: t('emailInvalid', 'Email address is invalid'),
                });
                return;
            }
            validateUrlServer(regDomain, 'registration', [{ is_webrisk: 0, is_virustotal: 0 }])
                .then(function (res) {
                    if (res.status) {
                        wpSpanCheckerToast.fire({
                            icon: 'success',
                            title: res.message,
                            timer: 2000,
                        });
                    } else {
                        wpSpanCheckerToast.fire({
                            icon: 'error',
                            title: res.message || t('emailInvalid', 'Email address is invalid'),
                        });
                    }
                })
                .catch(function (err) {
                    wpSpanCheckerToast.fire({
                        icon: 'error',
                        title: err.message || t('validationFailed', 'Validation failed'),
                    });
                });
        });
    }

    const $verifyLinkButton = $('#verifyLinkButton');
    const $createShortLinkButton = $('#createShortLinkButton');
    const $urlValidation = $('#urlValidation');

    if ($verifyLinkButton.length && $createShortLinkButton.length) {
        $verifyLinkButton.on('click', async function () {
            const $urlInput = $('#link_location_url');
            const url = $urlInput.val().trim();
            if (url === '') {
                $createShortLinkButton.prop('disabled', true);
                wpSpanCheckerToast.fire({
                    icon: 'error',
                    title: t('urlRequired', 'URL is required'),
                });
                return;
            }
            $createShortLinkButton.prop('disabled', true);
            if (!isValidUrl(url)) {
                wpSpanCheckerToast.fire({
                    icon: 'error',
                    title: t('urlNotValid', 'URL not valid'),
                });
                return;
            }
            let toCheck = url;
            if (!/^https?:\/\//i.test(toCheck)) {
                toCheck = 'https://' + toCheck;
            }
            try {
                const res = await validateUrlServer(toCheck, 'url', [{ is_webrisk: 0, is_virustotal: 0 }]);
                if (res.status === true) {
                    $createShortLinkButton.prop('disabled', false);
                    wpSpanCheckerToast.fire({
                        icon: 'success',
                        title: t('urlValid', 'URL is valid'),
                    });
                    if ($urlValidation.length) {
                        $urlValidation.val(1);
                    }
                } else {
                    $createShortLinkButton.prop('disabled', true);
                    wpSpanCheckerToast.fire({
                        icon: 'error',
                        title: res.message || t('urlNotValid', 'URL not valid'),
                    });
                    if ($urlValidation.length) {
                        $urlValidation.val(0);
                    }
                }
            } catch (err) {
                $createShortLinkButton.prop('disabled', true);
                wpSpanCheckerToast.fire({
                    icon: 'error',
                    title: err.message || String(err),
                });
            }
        });
    }

    if ($createShortLinkButton.length) {
        $createShortLinkButton.on('click', async function submitShortUrl(e) {
            e.preventDefault();
            const $urlInput = $('#link_location_url');
            const urlStatus = $urlValidation.length ? parseInt($('#urlValidation').val(), 10) : 0;
            let urlValue = $urlInput.val().trim();
            if (!isValidUrl(urlValue)) {
                wpSpanCheckerToast.fire({
                    icon: 'error',
                    title: t('urlNotValid', 'URL not valid'),
                });
                return;
            }
            if (!/^https?:\/\//i.test(urlValue)) {
                urlValue = 'https://' + urlValue;
            }
            if (!urlStatus) {
                try {
                    const res = await validateUrlServer(urlValue, 'url', [{ is_webrisk: 0, is_virustotal: 0 }]);
                    if (res.status === true) {
                        $(this).parent().closest('form').trigger('submit');
                    } else {
                        $createShortLinkButton.prop('disabled', true);
                        wpSpanCheckerToast.fire({
                            icon: 'error',
                            title: res.message || 'URL not valid',
                        });
                    }
                } catch (err) {
                    $createShortLinkButton.prop('disabled', true);
                }
            } else {
                $(this).parent().closest('form').trigger('submit');
            }
        });
    }
});

jQuery(document).on('wscFormSubmit', function () {});

function wscStopFormSubmit(payload) {
    payload.originalEvent.preventDefault();
    payload.originalEvent.stopImmediatePropagation();
}

jQuery(document).on('click', '.quform-submit', function (e) {
    const $form = jQuery(this).closest('form');
    jQuery(document).trigger('wscFormSubmit', {
        originalEvent: e,
        formId: $form.attr('id'),
        action: $form.attr('action'),
        data: $form.serializeArray(),
        test: true,
    });
});

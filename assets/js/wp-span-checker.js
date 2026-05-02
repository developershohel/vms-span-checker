const wpSpanCheckerToast = Swal.mixin({
    toast: true,
    position: 'center',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

/**
 * Install submit listener synchronously when this file parses (before jQuery.ready).
 * Later code assigns wpSpanCheckerFormGuard.submitCaptureHandler so Form Guard runs
 * before most plugins that hook ready/bubble-only handlers.
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
    if (g._submitCaptureBound || !g.registry) {
        return;
    }
    g._submitCaptureBound = true;
    window.addEventListener(
        'submit',
        function (ev) {
            if (typeof g.submitCaptureHandler === 'function') {
                g.submitCaptureHandler(ev);
            }
        },
        true
    );
})();

jQuery(function ($) {
    const I = typeof WPSpanChecker !== 'undefined' && WPSpanChecker.i18n ? WPSpanChecker.i18n : {};
    const t = function (key, fallback) {
        return I[key] !== undefined && I[key] !== '' ? I[key] : fallback;
    };

    const settings = WPSpanChecker.settings || [];
    const ajaxUrl = WPSpanChecker.ajaxUrl;
    const nonce = WPSpanChecker.nonce;

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
        if (!raw) {
            return $form.find('[type="submit"]').first();
        }
        let $btn = $form.find(raw);
        if (!$btn.length) {
            $btn = $(raw);
        }
        return $btn.first();
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
     * Form Guard registry lives on window (bootstrap registers capture before ready).
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
                bypassOnce: false,
                validating: false,
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

    function wscAttachFormGuardSubmitImpl() {
        const guard = window.wpSpanCheckerFormGuard;
        const reg = wscGuardRegistry();
        if (!guard || !reg) {
            return;
        }

        guard.submitCaptureHandler = function (ev) {
                const formEl = ev.target;
                if (!formEl || formEl.tagName !== 'FORM') {
                    return;
                }
                const entry = reg.get(formEl);
                if (!entry || !entry.configs.length) {
                    return;
                }

                if (entry.bypassOnce) {
                    entry.bypassOnce = false;
                    formEl.dispatchEvent(
                        new CustomEvent('wp_span_checker:native_submit', {
                            bubbles: true,
                            detail: { form: formEl },
                        })
                    );
                    return;
                }

                if (entry.validating) {
                    ev.preventDefault();
                    ev.stopImmediatePropagation();
                    return;
                }

                ev.preventDefault();
                ev.stopImmediatePropagation();

                let submitter = null;
                if (ev && typeof ev === 'object' && 'submitter' in ev) {
                    submitter = ev.submitter;
                }

                const before = new CustomEvent('wp_span_checker:guard_before_validate', {
                    cancelable: true,
                    bubbles: true,
                    detail: { form: formEl, originalEvent: ev, submitter: submitter },
                });
                if (!formEl.dispatchEvent(before)) {
                    return;
                }

                entry.validating = true;

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
                            jobs.push({ mappingId: mappingId, idx: idx, val: val });
                        }
                    });
                });

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
                        formEl.dispatchEvent(
                            new CustomEvent('wp_span_checker:guard_validated', {
                                bubbles: true,
                                detail: { form: formEl, submitter: submitter },
                            })
                        );
                        entry.bypassOnce = true;
                        if (typeof formEl.requestSubmit === 'function') {
                            try {
                                formEl.requestSubmit(submitter || undefined);
                            } catch (err) {
                                HTMLFormElement.prototype.submit.call(formEl);
                            }
                        } else {
                            HTMLFormElement.prototype.submit.call(formEl);
                        }
                    })
                    .catch(function (err) {
                        wpSpanCheckerToast.fire({
                            icon: 'error',
                            title: err.message || t('validationFailed', 'Validation failed'),
                        });
                    })
                    .then(function () {
                        entry.validating = false;
                    });
        };
    }

    wscAttachFormGuardSubmitImpl();

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

        const $form = resolveForm$(form_id, form_class);
        if (!$form.length) {
            wpSpanCheckerToast.fire({
                icon: 'error',
                title: t('formNotFound', 'Form not found. Check Form ID / class in WP Span Checker settings.'),
            });
            return;
        }

        const submitButton = resolveSubmit$($form, submit_selector);

        const formEl = $form.get(0);
        if (!formEl || formEl.tagName !== 'FORM') {
            return;
        }

        wscRegisterFormGuardConfig(formEl, {
            mappingId: mappingId,
            formSettingData: formSettingData,
        });

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

        /* Submit validation runs via document capture listener (see wscInstallDocumentSubmitCapture). */
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

const wpSpanCheckerToast = Swal.mixin({
    toast: true,
    position: 'center',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

jQuery(function ($) {
    const I = (typeof WPSpanChecker !== 'undefined' && WPSpanChecker.i18n) ? WPSpanChecker.i18n : {};
    const t = function (key, fallback) {
        return (I[key] !== undefined && I[key] !== '') ? I[key] : fallback;
    };

    const settings = WPSpanChecker.settings || [];
    const ajaxUrl = WPSpanChecker.ajaxUrl;
    const nonce = WPSpanChecker.nonce;

    function generateFormClassId(formId, formClass) {
        const id = String(formId || '').trim().replace(/^#/, '');
        const rawClass = String(formClass || '').trim().replace(/^\./g, '');
        const classes = rawClass.split(/\s+/).filter(Boolean).map(function (c) {
            return c.replace(/^\./, '');
        });

        if (id && classes.length) {
            return $('#' + id + '.' + classes.join('.'));
        }
        if (id) {
            return $('#' + id);
        }
        if (classes.length) {
            return $('.' + classes.join('.'));
        }
        return $();
    }

    function generateFieldClassId($form, fieldId, fieldClass) {
        const id = String(fieldId || '').trim().replace(/^#/, '');
        const rawClass = String(fieldClass || '').trim().replace(/^\./g, '');
        const classes = rawClass.split(/\s+/).filter(Boolean).map(function (c) {
            return c.replace(/^\./, '');
        });

        if (id && classes.length) {
            return $form.find('#' + id + '.' + classes.join('.'));
        }
        if (id) {
            return $form.find('#' + id);
        }
        if (classes.length) {
            return $form.find('.' + classes.join('.'));
        }
        return $();
    }

    function isValidUrl(value) {
        const pattern = /^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(\/\S*)?$/;
        return pattern.test(value);
    }

    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    function getDomainFromEmail(email) {
        const parts = String(email).split('@');
        return parts.length > 1 ? parts[1] : '';
    }

    /**
     * @param {string} domain
     * @param {string} type
     * @param {Array<{is_webrisk?: *, is_virustotal?: *}>} apiSettings
     * @returns {Promise<{status: boolean, message: string}>}
     */
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
                        response &&
                        response.data &&
                        response.data.message
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

    function disableSubmitButton(submitButton) {
        submitButton.prop('disabled', true);
    }

    function enableSubmitButton(submitButton) {
        submitButton.prop('disabled', false);
    }

    function addChangeEvent(element, field, submitButton, apiSettings) {
        element.on('change', function () {
            const inputVal = String($(this).val() || '').trim();
            if (!inputVal.length) {
                disableSubmitButton(submitButton);
                return;
            }
            if (field === 'email') {
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

    function addInputEvent(element, field, submitButton, apiSettings) {
        element.on('input', function () {
            const inputVal = String($(this).val() || '').trim();
            if (field === 'email' && inputVal && isValidEmail(inputVal)) {
                const domainName = getDomainFromEmail(inputVal);
                if (domainName) {
                    validateUrlServer(domainName, 'email', apiSettings).catch(function () {
                        /* debounce could be added later */
                    });
                }
            }
        });
    }

    function bindFormSubmitValidation($form, field, apiSettings) {
        if (field !== 'email') {
            return;
        }
        $form.off('submit.wsc').on('submit.wsc', function (e) {
            e.preventDefault();
            const $emailField = $form
                .find('input[type="email"], input[name*="email"]')
                .first();
            const inputVal = String($emailField.val() || '').trim();
            if (!inputVal || !isValidEmail(inputVal)) {
                wpSpanCheckerToast.fire({
                    icon: 'error',
                    title: t('emailRequired', 'Valid email is required'),
                });
                return;
            }
            const domainName = getDomainFromEmail(inputVal);
            validateUrlServer(domainName, 'email', apiSettings)
                .then(function (result) {
                    if (result.status) {
                        $form.off('submit.wsc');
                        const el = $form.get(0);
                        if (el && typeof HTMLFormElement !== 'undefined') {
                            HTMLFormElement.prototype.submit.call(el);
                        } else {
                            $form.trigger('submit');
                        }
                    } else {
                        wpSpanCheckerToast.fire({
                            icon: 'error',
                            title: result.message || t('emailInvalid', 'Email address is invalid'),
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

    settings.forEach(function (setting) {
        const is_webrisk = setting.is_webrisk ?? 0;
        const is_virustotal = setting.is_virustotal ?? 0;
        const apiOptions = [{ is_webrisk: is_webrisk, is_virustotal: is_virustotal }];
        const form_id = setting.form_id ?? '';
        const form_class = setting.form_class ?? '';
        const rawSettings = setting.settings ? setting.settings : '{}';
        let formSettingData;
        try {
            formSettingData = JSON.parse(rawSettings);
        } catch (e) {
            formSettingData = [];
        }
        if (!Array.isArray(formSettingData)) {
            formSettingData = [];
        }

        const $form = generateFormClassId(form_id, form_class);
        if (!$form.length) {
            wpSpanCheckerToast.fire({
                icon: 'error',
                title: t('formNotFound', 'Form not found. Check Form ID / class in WP Span Checker settings.'),
            });
            return;
        }

        const submitButton = $form.find('[type="submit"]');
        formSettingData.forEach(function (formSetting) {
            const eventType = formSetting.event;
            const fieldType =
                formSetting.field_type || formSetting.field || '';
            if (eventType === 'submit') {
                bindFormSubmitValidation($form, fieldType, apiOptions);
                return;
            }
            const $field = generateFieldClassId(
                $form,
                formSetting.id,
                formSetting.class
            );
            if (!$field.length) {
                return;
            }
            switch (eventType) {
                case 'change':
                    addChangeEvent($field, fieldType, submitButton, apiOptions);
                    break;
                case 'input':
                    addInputEvent($field, fieldType, submitButton, apiOptions);
                    break;
                default:
                    break;
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
            validateUrlServer(regDomain, 'registration', [
                { is_webrisk: 0, is_virustotal: 0 },
            ])
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
                const res = await validateUrlServer(toCheck, 'url', [
                    { is_webrisk: 0, is_virustotal: 0 },
                ]);
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
            const urlStatus = $urlValidation.length
                ? parseInt($('#urlValidation').val(), 10)
                : 0;
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
                    const res = await validateUrlServer(urlValue, 'url', [
                        { is_webrisk: 0, is_virustotal: 0 },
                    ]);
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

const wpSpanCheckerToast = Swal.mixin({
    toast: true,
    position: 'center',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

jQuery(document).ready(function ($) {
    const I = (typeof WPSpanChecker !== 'undefined' && WPSpanChecker.i18n) ? WPSpanChecker.i18n : {};
    const wscT = function (key, fallback) {
        return (I[key] !== undefined && I[key] !== '') ? I[key] : fallback;
    };
    const wscAjaxErr = function (data, fallback) {
        if (data && typeof data === 'object' && data.message) {
            return data.message;
        }
        if (typeof data === 'string' && data.length) {
            return data;
        }
        return fallback;
    };
    const wscErrToast = function (msg) {
        wpSpanCheckerToast.fire({ icon: 'error', title: msg });
    };
    const wscOkToast = function (msg) {
        wpSpanCheckerToast.fire({ icon: 'success', title: msg });
    };

    const pageTargetLabels = (typeof WPSpanChecker !== 'undefined' && WPSpanChecker.pageTargetLabels) ? WPSpanChecker.pageTargetLabels : {};

    function wscNormalizePageTargetsRaw(raw) {
        if (raw === null || raw === undefined) {
            return ['all-pages'];
        }
        if (Array.isArray(raw)) {
            return raw.map(String).filter(Boolean);
        }
        const s = String(raw).trim();
        if (!s) {
            return ['all-pages'];
        }
        try {
            const d = JSON.parse(s);
            if (Array.isArray(d)) {
                return d.map(String).filter(Boolean);
            }
        } catch (e) {
            /* legacy single value */
        }
        return [s];
    }

    function wscCollectPageTargetsArray() {
        const out = [];
        $('.wsc-page-target:checked').each(function () {
            const v = $(this).val();
            if (v) {
                out.push(String(v));
            }
        });
        $('#wsc-target-pages option:selected').each(function () {
            const v = $(this).val();
            if (v) {
                out.push(String(v));
            }
        });
        $('#wsc-target-posts option:selected').each(function () {
            const v = $(this).val();
            if (v) {
                out.push(String(v));
            }
        });
        const uniq = [];
        out.forEach(function (x) {
            if (uniq.indexOf(x) === -1) {
                uniq.push(x);
            }
        });
        return uniq.length ? uniq : ['all-pages'];
    }

    function wscApplyPageTargetsFromRaw(raw) {
        $('.wsc-page-target').prop('checked', false);
        $('#wsc-target-pages option:selected').prop('selected', false);
        $('#wsc-target-posts option:selected').prop('selected', false);
        const list = wscNormalizePageTargetsRaw(raw);
        list.forEach(function (token) {
            const $cb = $('.wsc-page-target').filter(function () {
                return $(this).val() === token;
            });
            if ($cb.length) {
                $cb.prop('checked', true);
                return;
            }
            if (/^\d+$/.test(token)) {
                $('#wsc-target-pages option[value="' + token + '"], #wsc-target-posts option[value="' + token + '"]').prop('selected', true);
            }
        });
        if (!list.length) {
            $('.wsc-page-target[value="all-pages"]').prop('checked', true);
        }
    }

    function wscResetPageTargetsForNew() {
        wscApplyPageTargetsFromRaw(['all-pages']);
    }

    function wscFormatPageTargetsCell(raw) {
        const list = wscNormalizePageTargetsRaw(raw);
        const parts = list.slice(0, 4).map(function (t) {
            if (pageTargetLabels[t]) {
                return pageTargetLabels[t];
            }
            if (/^\d+$/.test(t)) {
                const opt = document.querySelector('#wsc-target-pages option[value="' + t + '"], #wsc-target-posts option[value="' + t + '"]');
                if (opt && opt.textContent) {
                    return '#' + t + ' ' + opt.textContent.trim().slice(0, 28);
                }
                return 'ID ' + t;
            }
            return t;
        });
        let s = parts.join(', ');
        if (list.length > 4) {
            s += ' (+' + (list.length - 4) + ')';
        }
        return s || '—';
    }

    let domainType = $('#add-domain-form input[name="domain_type"]').val();

    // Initialize advanced DataTable
    let table = $('#domains-table').DataTable({
        ajax: {
            url: WPSpanChecker.ajaxurl,
            type: 'POST',
            data: function (d) {
                d.action = 'get_domains';
                d.nonce = WPSpanChecker.nonce;
                d.domain_type = domainType;
            },
            dataSrc: function (json) {
                return json.success ? json.data.domains : [];
            }
        },
        columns: [
            {data: 'id'},
            {data: 'domain'},
            {
                data: null, render: function (data, type, row) {
                    return '<button class="button delete-domain" data-id="' + row.id + '">' + wscT('delete', 'Delete') + '</button>';
                }
            }
        ],
        dom: '<"dt-top"<"dt-buttons"B><"dt-search"f>>rtip', // Buttons + search inline
        buttons: [
            {extend: 'copy', className: 'dt-btn-small'},
            {extend: 'csv', className: 'dt-btn-small'},
            {extend: 'excel', className: 'dt-btn-small'},
            {extend: 'pdf', className: 'dt-btn-small'},
            {extend: 'print', className: 'dt-btn-small'},
        ],
        responsive: true,
        colReorder: true,
        rowReorder: true,
        fixedHeader: true,
        searchPanes: {cascadePanes: true},
        select: true,
        pageLength: 50,
        order: [[0, 'asc']]
    });


    // Add domain
    $('#add-domain-form').on('submit', function (e) {
        e.preventDefault();
        let domain = $(this).find('input[name="domain"]').val();

        $.post(WPSpanChecker.ajaxurl, {
            action: 'add_domain',
            nonce: WPSpanChecker.nonce,
            domain_type: domainType,
            domain: domain
        }, function (response) {
            if (response.success) {
                table.ajax.reload(null, false);
                $('#add-domain-form')[0].reset();
                wscOkToast(wscT('domainAdded', 'Domain added.'));
            } else {
                wscErrToast(wscAjaxErr(response.data, wscT('errorAddingDomain', 'Error adding domain.')));
            }
        });
    });

    $('#wsc-import-whitelist-seed').on('click', function () {
        const $btn = $(this);
        Swal.fire({
            title: wscT('importWhitelistSeedTitle', 'Import bundled whitelist domains?'),
            text: wscT('importWhitelistSeedText', 'This will add common provider domains and skip existing entries.'),
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: wscT('import', 'Import'),
            cancelButtonText: wscT('cancel', 'Cancel'),
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            $btn.prop('disabled', true);
            $.post(WPSpanChecker.ajaxurl, {
                action: 'import_whitelist_seed',
                nonce: WPSpanChecker.nonce
            }, function (response) {
                if (response && response.success) {
                    table.ajax.reload(null, false);
                    wscOkToast(wscAjaxErr(response.data, wscT('importDone', 'Whitelist import complete.')));
                } else {
                    wscErrToast(wscAjaxErr(response ? response.data : null, wscT('importFailed', 'Whitelist import failed.')));
                }
            }).fail(function () {
                wscErrToast(wscT('importFailed', 'Whitelist import failed.'));
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });
    });

    // Delete domain
    $('#domains-table').on('click', '.delete-domain', function () {
        let id = $(this).data('id');
        Swal.fire({
            title: wscT('confirmDeleteDomainTitle', 'Remove this domain?'),
            text: wscT('confirmDeleteDomain', 'Are you sure you want to delete this domain?'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: wscT('delete', 'Delete'),
            cancelButtonText: wscT('cancel', 'Cancel'),
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            $.post(WPSpanChecker.ajaxurl, {
                action: 'delete_domain',
                nonce: WPSpanChecker.nonce,
                domain_type: domainType,
                id: id
            }, function (response) {
                if (response.success) {
                    table.ajax.reload(null, false);
                    wscOkToast(wscT('domainRemoved', 'Domain removed.'));
                } else {
                    wscErrToast(wscAjaxErr(response.data, wscT('errorDeletingDomain', 'Error deleting domain.')));
                }
            });
        });
    });


    let wscRegexTargetInput = null;

    function wscEsc(s) {
        return String(s === undefined || s === null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function wscFgNextIndex() {
        let max = 0;
        $('#wsc-form-fields .wsc-form-field-row').each(function () {
            const ix = parseInt($(this).attr('data-field-index'), 10) || 0;
            if (ix > max) {
                max = ix;
            }
        });
        return max + 1;
    }

    function wscFgMergeApi(item, legacyRow) {
        const lr = legacyRow || {};
        const d = item || {};
        const has = function (k) {
            return Object.prototype.hasOwnProperty.call(d, k);
        };
        const wr = has('is_webrisk') ? (parseInt(d.is_webrisk, 10) || 0) : (parseInt(lr.is_webrisk, 10) || 0);
        const vt = has('is_virustotal') ? (parseInt(d.is_virustotal, 10) || 0) : (parseInt(lr.is_virustotal, 10) || 0);
        return { wr: wr, vt: vt };
    }

    function wscFgToggleRow($row) {
        const t = $row.find('.form-field').val();
        $row.find('.wsc-fg-opt-email').toggle(t === 'email' || t === 'url');
        $row.find('.wsc-fg-opt-text').toggle(t === 'text');
        $row.find('.wsc-fg-opt-username').toggle(t === 'username');
        $row.find('.wsc-fg-opt-textarea').toggle(t === 'textarea');
        $row.find('.wsc-fg-opt-other').toggle(
            t === 'tel' || t === 'number' || t === 'password'
        );
    }

    function wscFgPickRadio($row, name, val) {
        const sval = String(val);
        $row.find('input[type="radio"][name="' + name + '"]').each(function () {
            const on = String($(this).val()) === sval;
            $(this).prop('checked', on);
            $(this).closest('.wsc-switch-option').toggleClass('wsc-check', on);
        });
    }

    /** Reset toggles that do not apply when field type changes (conditional guard). */
    function wscFgSyncConditionalRadios($row) {
        const ft = $row.find('.form-field').val();
        const ix = $row.attr('data-field-index');
        if (ft !== 'email' && ft !== 'url') {
            wscFgPickRadio($row, 'fg_webrisk_' + ix, 0);
            wscFgPickRadio($row, 'fg_vt_' + ix, 0);
        }
        if (ft !== 'text') {
            wscFgPickRadio($row, 'fg_texturls_' + ix, 1);
        }
        if (ft !== 'username') {
            wscFgPickRadio($row, 'fg_userexists_' + ix, 0);
        }
        if (ft !== 'textarea') {
            wscFgPickRadio($row, 'fg_tlinks_' + ix, 1);
            wscFgPickRadio($row, 'fg_tai_' + ix, 0);
        }
    }

    /** Strip irrelevant keys before save (matches server Form_Guard_Conditional::normalize_field_config). */
    function wscFgEffectivePayload(ft, raw) {
        const o = Object.assign({}, raw);
        if (ft !== 'email' && ft !== 'url') {
            o.is_webrisk = 0;
            o.is_virustotal = 0;
        }
        if (ft !== 'username') {
            o.check_username_exists = 0;
        }
        if (ft !== 'text') {
            delete o.text_allow_urls;
        }
        if (ft !== 'textarea') {
            o.textarea_ai_spam = 0;
            o.textarea_allow_links = 1;
        }
        return o;
    }

    function wscFgRowHtml(ix, item, legacyRow) {
        const d = item || {};
        const field = d.field || 'text';
        const api = wscFgMergeApi(d, legacyRow);
        const optSel = function (v) {
            return field === v ? ' selected="selected"' : '';
        };
        const num = function (v, def) {
            const n = parseInt(v, 10);
            return Number.isFinite(n) ? n : def;
        };
        const req = num(d.isRequired, 0);
        const val = num(d.isValidate, 0);
        let wr = api.wr ? 1 : 0;
        let vt = api.vt ? 1 : 0;
        if (field === 'email') {
            if (!Object.prototype.hasOwnProperty.call(d, 'is_webrisk')) {
                wr = 1;
            }
            if (!Object.prototype.hasOwnProperty.call(d, 'is_virustotal')) {
                vt = 0;
            }
        }
        let tun = num(d.check_username_exists, 0);
        if (field === 'username' && !Object.prototype.hasOwnProperty.call(d, 'check_username_exists')) {
            tun = 1;
        }
        const tlinks = Object.prototype.hasOwnProperty.call(d, 'textarea_allow_links')
            ? (parseInt(d.textarea_allow_links, 10) || 0)
            : 1;
        const tai = num(d.textarea_ai_spam, 0);
        const textUrlsAllow = Object.prototype.hasOwnProperty.call(d, 'text_allow_urls')
            ? (parseInt(d.text_allow_urls, 10) !== 0)
            : true;
        const regexVal = wscEsc(d.regex || '');
        const selEv = d.event || 'change';

        function rad(name, val, cur) {
            const lab = val ? wscT('enable', 'Enable') : wscT('disable', 'Disable');
            return '<span class="wsc-switch-option' + (cur === val ? ' wsc-check' : '') + '">' +
                '<input type="radio" name="' + name + '" value="' + val + '"' + (cur === val ? ' checked="checked"' : '') + '>' +
                '<label>' + lab + '</label></span>';
        }

        return (
            '<div class="wsc-form-field-row wsc-form-group" data-field-index="' + ix + '">' +
            '<span class="removeFormField dashicons dashicons-no-alt" role="button" tabindex="0"></span>' +
            '<div class="wsc-fg-row-banner wsc-mb-4">' +
            '<strong><span class="wsc-fg-row-badge">1</span>. ' + wscT('mappedFieldTitle', 'Mapped form control') + '</strong>' +
            '<p class="wsc-form-info-message wsc-text-info wsc-mt-2 wsc-mb-0">' + wscT('mappedFieldGuardsBlurb', 'Guards in this row apply only to this field’s ID/class. Use “Add field” for each separate input (10 fields → 10 rows).') + '</p>' +
            '</div>' +
            '<label class="wsc-form-label" for="form-field-' + ix + '">' + wscT('formField', 'Form field') + '</label>' +
            '<select id="form-field-' + ix + '" class="wsc-input wsc-input-primary form-field">' +
            '<option value="text"' + optSel('text') + '>' + wscT('optionText', 'Text') + '</option>' +
            '<option value="username"' + optSel('username') + '>' + wscT('optionUsername', 'Username') + '</option>' +
            '<option value="textarea"' + optSel('textarea') + '>' + wscT('optionTextarea', 'Textarea') + '</option>' +
            '<option value="email"' + optSel('email') + '>' + wscT('optionEmail', 'Email') + '</option>' +
            '<option value="url"' + optSel('url') + '>' + wscT('optionUrl', 'URL') + '</option>' +
            '<option value="tel"' + optSel('tel') + '>' + wscT('optionTel', 'Telephone') + '</option>' +
            '<option value="number"' + optSel('number') + '>' + wscT('optionNumber', 'Number') + '</option>' +
            '<option value="password"' + optSel('password') + '>' + wscT('optionPassword', 'Password') + '</option>' +
            '</select>' +
            '<label class="wsc-form-label wsc-mt-4" for="form-id-' + ix + '">' + wscT('fieldId', 'Field ID') + '</label>' +
            '<input id="form-id-' + ix + '" type="text" class="wsc-input wsc-input-primary field-id" placeholder="' + wscEsc(wscT('fieldId', 'Field ID')) + '" value="' + wscEsc(d.id || '') + '">' +
            '<label class="wsc-form-label wsc-mt-4" for="form-class-' + ix + '">' + wscT('fieldClass', 'Field class') + '</label>' +
            '<input id="form-class-' + ix + '" type="text" class="wsc-input wsc-input-primary field-class" placeholder="' + wscEsc(wscT('fieldClass', 'Field class')) + '" value="' + wscEsc(d.class || '') + '">' +
            '<label class="wsc-form-label wsc-mt-4" for="form-event-' + ix + '">' + wscT('javascriptEvent', 'JavaScript event') + '</label>' +
            '<select class="wsc-input wsc-input-primary form-event wsc-mt-4" id="form-event-' + ix + '">' +
            '<option value="change"' + (selEv === 'change' ? ' selected' : '') + '>' + wscT('optionChange', 'Change') + '</option>' +
            '<option value="input"' + (selEv === 'input' ? ' selected' : '') + '>' + wscT('optionInput', 'Input') + '</option>' +
            '<option value="submit"' + (selEv === 'submit' ? ' selected' : '') + '>' + wscT('optionFormSubmit', 'Form submit') + '</option>' +
            '</select>' +
            '<fieldset class="wsc-fg-rules-fieldset wsc-fg-fieldset wsc-mt-4">' +
            '<legend class="wsc-form-label">' + wscT('validationRulesLegend', 'Validation rules') + '</legend>' +
            '<div class="wsc-form-attr wsc-mt-2">' +
            '<p class="wsc-form-label">' + wscT('requiredField', 'Required field') + '</p>' +
            '<div class="wsc-switch-control">' + rad('is_required_f_' + ix, 1, req) + rad('is_required_f_' + ix, 0, req ? 0 : 1) + '</div>' +
            '<span class="wsc-form-info-message wsc-text-info">' + wscT('requiredFieldHint', 'Mark the field as required in the browser.') + '</span></div>' +
            '<div class="wsc-form-attr wsc-mt-4">' +
            '<p class="wsc-form-label">' + wscT('requireValidation', 'Require validation') + '</p>' +
            '<div class="wsc-switch-control">' + rad('is_validate_f_' + ix, 1, val) + rad('is_validate_f_' + ix, 0, val ? 0 : 1) + '</div>' +
            '<span class="wsc-form-info-message wsc-text-info">' + wscT('requireValidationHint', 'Run server-side validation for this field.') + '</span></div>' +
            '<label class="wsc-form-label wsc-mt-4" for="field-regex-' + ix + '">' + wscT('customRegex', 'Custom regex (delimited)') + '</label>' +
            '<div class="wsc-flex wsc-gap-2 wsc-items-center wsc-flex-wrap">' +
            '<input id="field-regex-' + ix + '" type="text" class="wsc-input wsc-input-primary field-regex wsc-flex-grow" placeholder="/^[a-z]+$/" value="' + regexVal + '">' +
            '<button type="button" class="wsc-btn wsc-btn-outline-primary wsc-open-regex-presets">' + wscT('presetRegex', 'Preset patterns') + '</button></div>' +
            '<span class="wsc-form-info-message wsc-text-info">' + wscT('customRegexHint', 'Optional. Must look like /pattern/flags. Checked on the server when validation is enabled.') + '</span>' +
            '</fieldset>' +
            '<fieldset class="wsc-fg-security-fieldset wsc-fg-fieldset wsc-mt-4">' +
            '<legend class="wsc-form-label">' + wscT('securityMethodsLegend', 'Protection methods (based on field type)') + '</legend>' +
            '<p class="wsc-form-info-message wsc-text-info wsc-mb-4">' + wscT('securityMethodsIntro', 'Email and URL rows can enable Web Risk and VirusTotal (Google Web Risk defaults ON when you pick Email). Username rows can enable live “already registered” checks. Text adds URL-in-value rules; textarea adds links + AI spam screening.') + '</p>' +
            '<div class="wsc-form-attr wsc-mt-2 wsc-fg-opt-email">' +
            '<p class="wsc-form-label">' + wscT('googleWebRisk', 'Google Web Risk') + '</p>' +
            '<div class="wsc-switch-control">' + rad('fg_webrisk_' + ix, 1, wr) + rad('fg_webrisk_' + ix, 0, wr ? 0 : 1) + '</div>' +
            '<span class="wsc-form-info-message wsc-text-info">' + wscT('webriskEmailUrlOnly', 'Used when “Form field” is Email or URL and “Require validation” is enabled for domain checks.') + '</span></div>' +
            '<div class="wsc-form-attr wsc-mt-4 wsc-fg-opt-email">' +
            '<p class="wsc-form-label">' + wscT('virusTotal', 'VirusTotal scanner') + '</p>' +
            '<div class="wsc-switch-control">' + rad('fg_vt_' + ix, 1, vt) + rad('fg_vt_' + ix, 0, vt ? 0 : 1) + '</div>' +
            '<span class="wsc-form-info-message wsc-text-info">' + wscT('vtEmailUrlOnly', 'Same as Web Risk: applies together with Email or URL domain validation.') + '</span></div>' +
            '<div class="wsc-form-attr wsc-mt-4 wsc-fg-opt-username">' +
            '<p class="wsc-form-label">' + wscT('usernameTakenCheck', 'Reject if username exists') + '</p>' +
            '<div class="wsc-switch-control">' + rad('fg_userexists_' + ix, 1, tun) + rad('fg_userexists_' + ix, 0, tun ? 0 : 1) + '</div>' +
            '<span class="wsc-form-info-message wsc-text-info">' + wscT('usernameTakenHint', 'Maps to a normal text/username input. When enabled, checks WordPress on change/input (debounced) and again on submit.') + '</span></div>' +
            '<div class="wsc-form-attr wsc-mt-4 wsc-fg-opt-text">' +
            '<p class="wsc-form-label">' + wscT('textAllowUrls', 'Allow URLs in value') + '</p>' +
            '<div class="wsc-switch-control">' + rad('fg_texturls_' + ix, 1, textUrlsAllow ? 1 : 0) + rad('fg_texturls_' + ix, 0, textUrlsAllow ? 0 : 1) + '</div>' +
            '<span class="wsc-form-info-message wsc-text-info">' + wscT('textAllowUrlsHint', 'Turn off to reject http(s) URLs inside this single-line field.') + '</span></div>' +
            '<div class="wsc-form-attr wsc-mt-4 wsc-fg-opt-textarea">' +
            '<p class="wsc-form-label">' + wscT('textareaAllowLinks', 'Allow links in message') + '</p>' +
            '<div class="wsc-switch-control">' + rad('fg_tlinks_' + ix, 1, tlinks) + rad('fg_tlinks_' + ix, 0, tlinks ? 0 : 1) + '</div></div>' +
            '<div class="wsc-form-attr wsc-mt-4 wsc-fg-opt-textarea">' +
            '<p class="wsc-form-label">' + wscT('textareaAiSpam', 'AI spam check') + '</p>' +
            '<div class="wsc-switch-control">' + rad('fg_tai_' + ix, 1, tai) + rad('fg_tai_' + ix, 0, tai ? 0 : 1) + '</div>' +
            '<span class="wsc-form-info-message wsc-text-info">' + wscT('textareaAiSpamHint', 'Uses AI settings from WP Span Checker → AI. Runs on the server when validation is enabled.') + '</span></div>' +
            '<div class="wsc-form-attr wsc-mt-4 wsc-fg-opt-other">' +
            '<p class="wsc-form-info-message wsc-text-info wsc-mb-0">' + wscT('securityMethodsOtherHint', 'Extra reputation toggles appear for Email/URL. Use “Validation rules” above for required / server validation / regex.') + '</p></div>' +
            '</fieldset>' +
            '</div>'
        );
    }

    function wscFgRefreshRowNumbers() {
        $('#wsc-form-fields .wsc-form-field-row').each(function (i) {
            $(this).find('.wsc-fg-row-badge').text(String(i + 1));
        });
    }

    function wscFgAppendRow(item, legacyRow) {
        const ix = wscFgNextIndex();
        $('#wsc-form-fields').append(wscFgRowHtml(ix, item, legacyRow));
        const $nr = $('#wsc-form-fields .wsc-form-field-row[data-field-index="' + ix + '"]');
        $nr.data('wscPrevField', $nr.find('.form-field').val());
        wscFgToggleRow($nr);
        wscFgSyncConditionalRadios($nr);
        wscFgRefreshRowNumbers();
    }

    function wscFgResetRows() {
        $('#wsc-form-fields').empty();
        wscFgAppendRow(null, {});
    }

    function wscFgDisplaySelector(row) {
        const fid = String(row.form_id || '').trim();
        const fcls = String(row.form_class || '').trim();
        if (fid.indexOf('#') !== -1 || fid.indexOf('.') !== -1 || fid.indexOf('[') !== -1) {
            return fid;
        }
        let out = '';
        if (fid) {
            out += '#' + fid.replace(/^#/, '');
        }
        if (fcls) {
            fcls.split(/\s+/).forEach(function (c) {
                c = String(c || '').replace(/^\./, '').trim();
                if (c) {
                    out += '.' + c;
                }
            });
        }
        return out;
    }

    function wscFgFillPresetModal($input) {
        wscRegexTargetInput = $input;
        const $list = $('#wsc-regex-preset-list');
        $list.empty();
        const regexLists = (typeof WPSpanChecker !== 'undefined' && WPSpanChecker.regexList) ? WPSpanChecker.regexList : [];
        regexLists.forEach(function (item) {
            const valid = wscEsc(item.valid_example || item.example || '');
            const invalid = wscEsc(item.invalid_example || '');
            const pat = wscEsc(item.pattern || '');
            const $li = $('<li class="wsc-regex-preset-item wsc-card wsc-p-3 wsc-mb-3"></li>');
            $li.append('<strong>' + wscEsc(item.name || '') + '</strong>');
            $li.append('<div class="wsc-regex-preset-pattern">' + pat + '</div>');
            $li.append('<div class="wsc-text-info wsc-mb-2">' + wscEsc(item.desc || '') + '</div>');
            $li.append('<div class="wsc-mb-1"><span class="wsc-badge-ok">' + wscT('validExample', 'Valid') + '</span> <code>' + valid + '</code></div>');
            $li.append('<div class="wsc-mb-2"><span class="wsc-badge-bad">' + wscT('invalidExample', 'Invalid') + '</span> <code>' + invalid + '</code></div>');
            const $btn = $('<button type="button" class="wsc-btn wsc-btn-primary wsc-use-preset-regex"></button>').text(wscT('usePattern', 'Use pattern'));
            $btn.attr('data-pattern', item.pattern || '');
            $li.append($btn);
            $list.append($li);
        });
        $('#wsc-regex-preset-modal').removeClass('wsc-hidden').attr('aria-hidden', 'false');
    }

    function wscFgClosePresetModal() {
        $('#wsc-regex-preset-modal').addClass('wsc-hidden').attr('aria-hidden', 'true');
        wscRegexTargetInput = null;
    }

    $('#wsc-form-fields').on('click', '.wsc-open-regex-presets', function () {
        wscFgFillPresetModal($(this).closest('.wsc-form-field-row').find('.field-regex'));
    });

    $('#wsc-regex-preset-modal').on('click', '.wsc-regex-modal-overlay, .wsc-close-regex-modal', function () {
        wscFgClosePresetModal();
    });

    $('#wsc-regex-preset-list').on('click', '.wsc-use-preset-regex', function () {
        const p = $(this).attr('data-pattern');
        if (wscRegexTargetInput && p) {
            wscRegexTargetInput.val(p);
        }
        wscFgClosePresetModal();
    });

        $('#wsc-form-fields').on('change', '.form-field', function () {
            const $row = $(this).closest('.wsc-form-field-row');
            const ix = $row.attr('data-field-index');
            const prev = String($row.data('wscPrevField') || '');
            const ft = $(this).val();
            wscFgSyncConditionalRadios($row);
            if (ft === 'email' && prev !== 'email') {
                wscFgPickRadio($row, 'fg_webrisk_' + ix, 1);
                wscFgPickRadio($row, 'fg_vt_' + ix, 0);
            }
            if (ft === 'username' && prev !== 'username') {
                wscFgPickRadio($row, 'fg_userexists_' + ix, 1);
            }
            wscFgToggleRow($row);
            $row.data('wscPrevField', ft);
        });

    $('#wsc-form-fields').on('click', '.removeFormField', function () {
        if ($('#wsc-form-fields .wsc-form-field-row').length <= 1) {
            wscErrToast(wscT('fgNeedOneField', 'Keep at least one field row.'));
            return;
        }
        $(this).closest('.wsc-form-field-row').remove();
        wscFgRefreshRowNumbers();
    });

    $('#wscAddFormField').on('click', function () {
        wscFgAppendRow(null, {});
    });

    let formSettingTable = $('#form-setting-table').DataTable({
        ajax: {
            url: WPSpanChecker.ajaxurl,
            type: 'POST',
            data: function (d) {
                d.action = 'get_form_settings';
                d.nonce = WPSpanChecker.nonce;
            },
            dataSrc: function (json) {
                return json.success ? json.data.formSettings : [];
            }
        },
        columns: [
            {
                data: 'id',
                render: function (data) {
                    return data ?? ''; // If null/undefined, return empty string
                }
            },
            {
                data: 'form_type',
                render: function (data) {
                    return data ?? '';
                }
            },
            {
                data: 'page_id',
                render: function (data) {
                    return wscFormatPageTargetsCell(data);
                }
            },
            {
                data: null,
                render: function (data, type, row) {
                    const fid = String(row.form_id || '').trim();
                    const fcls = String(row.form_class || '').trim();
                    if (fid.indexOf('#') !== -1 || fid.indexOf('.') !== -1 || fid.indexOf('[') !== -1) {
                        return fid || '—';
                    }
                    let out = '';
                    if (fid) {
                        out += '#' + fid.replace(/^#/, '');
                    }
                    if (fcls) {
                        fcls.split(/\s+/).forEach(function (c) {
                            c = String(c || '').replace(/^\./, '').trim();
                            if (c) {
                                out += '.' + c;
                            }
                        });
                    }
                    return out || fid || '—';
                }
            },
            {
                data: 'submit_selector',
                render: function (data) {
                    return data ? String(data) : '—';
                }
            },
            {
                data: 'settings',
                render: function (data) {
                    if (!data) return '';
                    let formFields;
                    try {
                        formFields = (typeof data === "string") ? JSON.parse(data) : data;
                    } catch (e) {
                        return '';
                    }
                    if (!Array.isArray(formFields) || formFields.length === 0) return '';
                    return formFields.map(function (item, idx) {
                        const ft = item.field ?? '';
                        const wrOn = parseInt(item.is_webrisk, 10) === 1;
                        const vtOn = parseInt(item.is_virustotal, 10) === 1;
                        let guards = '';
                        if (ft === 'email' || ft === 'url') {
                            guards += '<br>' + wscT('labelWebRiskShort', 'Web Risk') + ': ' + (wrOn ? wscT('onShort', 'On') : wscT('offShort', 'Off'));
                            guards += ' · ' + wscT('labelVtShort', 'VirusTotal') + ': ' + (vtOn ? wscT('onShort', 'On') : wscT('offShort', 'Off'));
                        }
                        if (ft === 'username' && parseInt(item.check_username_exists, 10) === 1) {
                            guards += '<br>' + wscT('usernameCheckShort', 'Username exists check') + ': ' + wscT('onShort', 'On');
                        }
                        if (ft === 'text') {
                            const tau =
                                !Object.prototype.hasOwnProperty.call(item, 'text_allow_urls') ||
                                parseInt(item.text_allow_urls, 10) !== 0;
                            guards +=
                                '<br>' +
                                wscT('textUrlsInFieldShort', 'URLs in text field') +
                                ': ' +
                                (tau ? wscT('onShort', 'On') : wscT('offShort', 'Off'));
                        }
                        if (ft === 'textarea') {
                            const allow = !Object.prototype.hasOwnProperty.call(item, 'textarea_allow_links') || parseInt(item.textarea_allow_links, 10) !== 0;
                            guards += '<br>' + wscT('linksAllowedShort', 'Links allowed') + ': ' + (allow ? wscT('onShort', 'On') : wscT('offShort', 'Off'));
                            if (parseInt(item.textarea_ai_spam, 10) === 1) {
                                guards += ' · ' + wscT('aiSpamShort', 'AI spam check') + ': ' + wscT('onShort', 'On');
                            }
                        }
                        if (item.regex && String(item.regex).trim() !== '') {
                            const rp = String(item.regex).trim();
                            guards += '<br>' + wscT('regexShort', 'Regex') + ': ' + wscEsc(rp.slice(0, 48)) + (rp.length > 48 ? '…' : '');
                        }
                        return '<div class="wsc-form-setting-field wsc-mb-2 wsc-p-2 wsc-text-left"><strong>#' + (idx + 1) + ' · ' + wscEsc(ft) + '</strong><br>' +
                            wscT('labelId', 'ID') + ': ' + wscEsc(item.id ?? '') + ' · ' + wscT('labelClass', 'Class') + ': ' + wscEsc(item.class ?? '') + '<br>' +
                            wscT('eventName', 'Event name') + ': ' + wscEsc(item.event ?? '') +
                            guards + '</div>';
                    }).join('');
                }
            },
            {
                data: 'is_webrisk'
            },
            {
                data: 'is_virustotal'
            },
            {
                data: null,
                render: function (data, type, row) {
                    const rowId = row.id ?? '';
                    const jsonData = JSON.stringify(row) ?? '{}';
                    return `<button class="wsc-btn wsc-btn-primary edit-form-setting" data-json='${jsonData}' data-id="${rowId}">${wscT('edit', 'Edit')}</button> 
                    <button class="wsc-btn wsc-btn-danger delete-form-setting" data-id="${rowId}">${wscT('delete', 'Delete')}</button>`;
                }
            }
        ],
        responsive: true,   // Keep responsive enabled
        paging: false,      // Disable pagination
        searching: false,   // Disable search box
        ordering: false,    // Disable column sorting
        info: false,        // Disable "Showing x of y entries"
        lengthChange: false,// Disable changing number of rows shown
        autoWidth: false,   // Disable auto column width
        processing: true,  // Disable processing indicator
        serverSide: true,
    });

    // Delete domain
    $('#form-setting-table').on('click', '.delete-form-setting', function () {
        let id = $(this).data('id');
        const formSettingForm = $('#form-setting-table');
        Swal.fire({
            title: wscT('confirmDeleteFormTitle', 'Remove this Form Guard mapping?'),
            text: wscT('confirmDeleteFormSetting', 'Are you sure you want to delete this Form Guard mapping?'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: wscT('delete', 'Delete'),
            cancelButtonText: wscT('cancel', 'Cancel'),
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            formSettingForm.addClass('wsc-opacity');
            $.post(WPSpanChecker.ajaxurl, {
                action: 'delete_form_setting',
                nonce: WPSpanChecker.nonce,
                id: id
            }, function (response) {
                if (response.success) {
                    formSettingTable.ajax.reload(null, false);
                    wscOkToast(wscT('formSettingRemoved', 'Form Guard mapping removed.'));
                } else {
                    wscErrToast(wscAjaxErr(response.data, wscT('errorDeletingSetting', 'Could not delete Form Guard mapping.')));
                }
                formSettingForm.removeClass('wsc-opacity');
            });
        });
    });

    $('#form-setting-table').on('click', '.edit-form-setting', function () {
        let formData = $(this).data('json');
        const id = $('#form_settings_id');
        id.val(formData.id);
        const fields = $('#wsc-form-fields');
        const formError = $('#wsc-form-error-message');
        formError.html('');
        wscApplyPageTargetsFromRaw(formData.page_id);
        $('#form_type').val(formData.form_type !== '' ? formData.form_type : '');
        $('#form_selector').val(wscFgDisplaySelector(formData));
        $('#submit_selector').val(formData.submit_selector ? String(formData.submit_selector) : '');

        let formSettingData = [];
        if (Array.isArray(formData.settings)) {
            formSettingData = formData.settings;
        } else if (formData.settings && typeof formData.settings === 'string') {
            try {
                formSettingData = JSON.parse(formData.settings);
            } catch (e2) {
                formSettingData = [];
            }
        }

        fields.empty();
        if (!Array.isArray(formSettingData) || formSettingData.length === 0) {
            wscFgAppendRow(null, formData);
        } else {
            formSettingData.forEach(function (item) {
                wscFgAppendRow(item, formData);
            });
        }

        $('#wsc-settings-form').toggleClass('wsc-hidden');
        $('#form-setting-table').toggleClass('wsc-hidden');
    });

    $('#wsc-settings-form').on('submit', function (e) {
        e.preventDefault();
        const formSettingForm = $('#wsc-settings-form');
        const id = $('#form_settings_id');
        const saveButton = $('#saveFormSetting');
        const formError = $('#wsc-form-error-message');
        const formType = $('#form_type').val();
        const pageId = JSON.stringify(wscCollectPageTargetsArray());
        const combinedSel = String($('#form_selector').val() || '').trim();
        const submitSel = String($('#submit_selector').val() || '').trim();
        const formSettings = [];

        $('#wsc-form-fields .wsc-form-field-row').each(function () {
            const $row = $(this);
            const ix = $row.attr('data-field-index');
            const ft = $row.find('.form-field').val();
            const raw = {
                field: ft,
                id: $row.find('.field-id').val(),
                class: $row.find('.field-class').val(),
                event: $row.find('.form-event').val(),
                isRequired: parseInt($row.find('input[name="is_required_f_' + ix + '"]:checked').val(), 10) || 0,
                isValidate: parseInt($row.find('input[name="is_validate_f_' + ix + '"]:checked').val(), 10) || 0,
                is_webrisk: parseInt($row.find('input[name="fg_webrisk_' + ix + '"]:checked').val(), 10) || 0,
                is_virustotal: parseInt($row.find('input[name="fg_vt_' + ix + '"]:checked').val(), 10) || 0,
                check_username_exists: parseInt($row.find('input[name="fg_userexists_' + ix + '"]:checked').val(), 10) || 0,
                text_allow_urls: parseInt($row.find('input[name="fg_texturls_' + ix + '"]:checked').val(), 10) || 0,
                textarea_allow_links: parseInt($row.find('input[name="fg_tlinks_' + ix + '"]:checked').val(), 10) || 0,
                textarea_ai_spam: parseInt($row.find('input[name="fg_tai_' + ix + '"]:checked').val(), 10) || 0,
                regex: $row.find('.field-regex').val(),
            };
            formSettings.push(wscFgEffectivePayload(ft, raw));
        });

        $.ajax({
            method: 'POST',
            url: WPSpanChecker.ajaxurl,
            data: {
                action: 'add_form_settings',
                id: parseInt(id.val(), 10) || 0,
                formType: formType,
                pageId: pageId,
                formId: combinedSel,
                formClass: '',
                submitSelector: submitSel,
                formSettings: formSettings,
                nonce: WPSpanChecker.nonce
            },
            beforeSend: function () {
                formSettingForm.addClass('wsc-opacity');
                saveButton.prop('disabled', !$(this).prop('disabled'));
                saveButton.find('.wsc-spinner').removeClass('wsc-hidden');
                formError.html('');
            },
            success: function () {
                formError.addClass('wsc-text-success');
                formError.removeClass('wsc-form-error');
                formError.html(wscT('saved', 'Saved'));
                formSettingTable.ajax.reload(null, false);
                setTimeout(() => {
                    const formSettingFormEl = document.getElementById('wsc-settings-form');
                    formSettingFormEl.reset();
                    $('#wsc-settings-form').toggleClass('wsc-hidden');
                    $('#form-setting-table').toggleClass('wsc-hidden');
                    wscFgResetRows();
                }, 1000);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                formError.removeClass('wsc-text-success');
                formError.addClass('wsc-form-error');
                formError.html(errorThrown.message || String(errorThrown));
            },
            complete: function () {
                formSettingForm.removeClass('wsc-opacity');
                saveButton.removeAttr('disabled');
                saveButton.find('.wsc-spinner').addClass('wsc-hidden');
                id.val(0);
            }
        });
    });

    if ($('#wsc-form-fields').length) {
        wscFgResetRows();
    }

    $('#wscAddFormSetting').on('click', function () {
        const wasHidden = $('#wsc-settings-form').hasClass('wsc-hidden');
        $('#wsc-settings-form').toggleClass('wsc-hidden');
        $('#form-setting-table').toggleClass('wsc-hidden');
        if (wasHidden) {
            $('#form_settings_id').val('0');
            wscResetPageTargetsForNew();
            $('#form_selector').val('');
            $('#submit_selector').val('');
            wscFgResetRows();
        }
    });

    $('.toggleFormField').on('click', function () {
        $('#wsc-settings-form').toggleClass('wsc-hidden');
        $('#form-setting-table').toggleClass('wsc-hidden');
    });

    $(document).on('click', '.wsc-switch-option', function () {
         const $this = $(this);
         $this.addClass('wsc-check').siblings('.wsc-switch-option').removeClass('wsc-check');
         $this.find('input[type="radio"]').prop('checked', true);
         $this.siblings('.wsc-switch-option').find('input[type="radio"]').prop('checked', false);
    });

}); // end jquery

// Open modal
function wscOpenModal(element) {
    element.classList.add('wsc-block');
}

// Close modal
function wscCloseModal(element) {
    element.classList.remove('wsc-block');
}

function loadRegex(e){
    const element = jQuery(e);
    regexList(element.parent().next().find('ul'));
    wscOpenModal(e.parentElement.nextElementSibling);
}

function regexList(element){
    const regexLists = WPSpanChecker.regexList;
    const L = (WPSpanChecker.i18n) ? WPSpanChecker.i18n : {};
    const t = (k, fb) => (L[k]) ? L[k] : fb;
    element.html('');
    regexLists.forEach(item => {
        const $li = jQuery(`
            <li class="wsc-card wsc-p-3 border rounded" style="list-style:none; background:#fff;">
                <strong>${item.name}</strong>
                <div style="font-family:monospace; margin:8px 0;">${item.pattern}</div>
                <div style="color:#666; font-size:0.9em;">${item.desc}</div>
                <div style="margin-top:8px;">
                    <span style="font-family:monospace; background:#f4f4f4; padding:4px 8px; border-radius:4px;">${t('examplePrefix', 'Example:')} ${item.example}</span>
                    <button class="wsc-copy-button wsc-btn wsc-btn-outline-primary" type="button" data-regex="${item.pattern}" style="margin-left:8px; padding:4px 8px; border-radius:4px;" onclick="copyToClipboard(${item.pattern})">${t('copy', 'Copy')}</button>
                </div>
            </li>
        `);
        element.append($li);
    });
}

function copyToClipboard(text) {
    const L = (typeof WPSpanChecker !== 'undefined' && WPSpanChecker.i18n) ? WPSpanChecker.i18n : {};
    const t = (k, fb) => (L[k]) ? L[k] : fb;
    if (navigator.clipboard && window.isSecureContext) {
        // ✅ Modern way (requires HTTPS or localhost)
        return navigator.clipboard.writeText(text)
            .then(() => {
                wpSpanCheckerToast.fire({
                    icon: 'success',
                    title: t('copied', 'Copied'),
                    position: 'bottom-right',
                })
                console.log("Copied to clipboard:", text);
            })
            .catch(err => {
                wpSpanCheckerToast.fire({
                    icon: 'error',
                    title: err.message ?? String(err),
                    position: 'bottom-right',
                })
            });
    } else {
        // 🔄 Fallback for older browsers
        let textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";  // prevent scrolling
        textArea.style.opacity = "0";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            document.execCommand("copy");
            wpSpanCheckerToast.fire({
                icon: 'success',
                title: t('copied', 'Copied'),
                position: 'bottom-right',
            })
        } catch (err) {
            wpSpanCheckerToast.fire({
                icon: 'error',
                title: err.message ?? String(err),
                position: 'bottom-right',
            })
        }

        document.body.removeChild(textArea);
    }
}

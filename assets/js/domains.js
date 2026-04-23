const wpSpanCheckerToast = Swal.mixin({
    toast: true,
    position: 'center',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

jQuery(document).ready(function ($) {
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
                    return '<button class="button delete-domain" data-id="' + row.id + '">Delete</button>';
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
            } else {
                alert(response.data || 'Error adding domain');
            }
        });
    });

    // Delete domain
    $('#domains-table').on('click', '.delete-domain', function () {
        let id = $(this).data('id');
        if (!confirm('Are you sure you want to delete this domain?')) return;

        $.post(WPSpanChecker.ajaxurl, {
            action: 'delete_domain',
            nonce: WPSpanChecker.nonce,
            domain_type: domainType,
            id: id
        }, function (response) {
            if (response.success) {
                table.ajax.reload(null, false);
            } else {
                alert(response.data || 'Error deleting domain');
            }
        });
    });


    $('#wscAddFormField').on('click', function () {
        let fieldCounter = $('#wsc-form-fields .wsc-form-group').length; // initial count
        fieldCounter++; // increment for unique IDs

        // Create new form group HTML
        let newField = `
        <div class="wsc-form-group">
        <span class="removeFormField dashicons dashicons-no-alt" onclick="jQuery(this).parent().remove()"></span>
            <label class="wsc-form-label" for="form-field-${fieldCounter}">Form Field</label>
            <select id="form-field-${fieldCounter}"  class="wsc-input wsc-input-primary form-field" name="form-field-${fieldCounter}" data-id="${fieldCounter}" required>
                <option value="">Select field type</option>
                <option value="url">URL</option>
                <option value="email">Email</option>
                <option value="text">Text</option>
            </select>   
            <label for="form-id-${fieldCounter}" class="wsc-form-label wsc-mt-4">Field ID</label>
            <input id="form-id-${fieldCounter}" type="text" class="wsc-input wsc-input-primary field-id" name="form-field-id-${fieldCounter}" data-id="${fieldCounter}" placeholder="Field ID">
            <label for="form-class-${fieldCounter}" class="wsc-form-label wsc-mt-4">Field Class</label>
            <input id="form-class-${fieldCounter}" type="text" class="wsc-input wsc-input-primary field-class" name="form-field-class-${fieldCounter}" data-class="${fieldCounter}" placeholder="Field class">
            <label class="wsc-form-label wsc-mt-4" for="form-event-${fieldCounter}">Javascript Event Type</label>
            <select class="wsc-input wsc-input-primary form-event wsc-mt-4" id="form-event-${fieldCounter}" name="form-event-${fieldCounter}"
                    data-id="${fieldCounter}">
                <option value="change">Chance</option>
                <option value="input">Input</option>
                <option value="submit">Form Submit</option>
            </select>
        </div>
        `;

        // Insert before the Add button
        $(this).parent().siblings('#wsc-form-fields').append(newField);
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
                    return data ?? '';
                }
            },
            {
                data: 'form_id',
                render: function (data) {
                    return data ?? '';
                }
            },
            {
                data: 'form_class',
                render: function (data) {
                    return data ?? '';
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
                    return formFields.map(item =>
                        `<div class="wsc-form-setting-field wsc-mb-2 wsc-p-2 wsc-text-left">Field Type: ${item.field ?? ''} <br>ID: ${item.id ?? ''} <br>Class: ${item.class ?? ''} <br> Event Name: ${item.event}</div>`
                    ).join("");
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
                    return `<button class="wsc-btn wsc-btn-primary edit-form-setting" data-json='${jsonData}' data-id="${rowId}">Edit</button> 
                    <button class="wsc-btn wsc-btn-danger delete-form-setting" data-id="${rowId}">Delete</button>`;
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
        if (!confirm('Are you sure you want to delete this domain?')) return;
        const formSettingForm = $('#form-setting-table');
        formSettingForm.addClass('wsc-opacity');
        $.post(WPSpanChecker.ajaxurl, {
            action: 'delete_form_setting',
            nonce: WPSpanChecker.nonce,
            id: id
        }, function (response) {
            if (response.success) {
                formSettingTable.ajax.reload(null, false);
            } else {
                alert(response.data || 'Error deleting domain');
            }
            formSettingForm.removeClass('wsc-opacity');
        });
    });

    $('#form-setting-table').on('click', '.edit-form-setting', function () {
        let formData = $(this).data('json');
        const id = $('#form_settings_id');
        id.val(formData.id);
        const fields = $('#wsc-form-fields');
        const formError = $('#wsc-form-error-message');
        formError.html('');
        const pageId = formData.page_id;
        console.log(pageId);
        $('#page_id').val(pageId);
        $('#wsc-webrisk-status').val(formData.is_webrisk)
        $('#wsc-virustotal-status').val(formData.is_virustotal)
        $('#form_type').val(formData.form_type !== '' ? formData.form_type : '');
        $('#form_id').val(formData.form_id !== '' ? formData.form_id : '');
        $('#form_class').val(formData.form_class !== '' ? formData.form_class : '');
        const formSettings = formData.settings ? formData.settings : {};
        const formSettingData = JSON.parse(formSettings);

        if (formSettingData.length > 0) {
            fields.html('');
            formSettingData.forEach((item, fieldCounter) => {
                let newField = `
                    <div class="wsc-form-group">
                    <span class="removeFormField dashicons dashicons-no-alt" onclick="jQuery(this).parent().remove()"></span>
                        <label class="wsc-form-label" for="form-field-${fieldCounter + 1}">Form Field</label>
                        <select id="form-field-${fieldCounter + 1}"  class="wsc-input wsc-input-primary form-field" name="form-field-${fieldCounter + 1}" data-id="${fieldCounter + 1}">
                            <option value="url" ${item.field === 'url' ? 'selected="selected"' : ''}>URL</option>
                            <option value="email" ${item.field === 'email' ? 'selected="selected"' : ''}>Email</option>
                            <option value="text" ${item.field === 'text' ? 'selected="selected"' : ''}>Text</option>
                        </select>   
                        <label for="form-id-${fieldCounter + 1}" class="wsc-form-label wsc-mt-4">Field ID</label>
                        <input id="form-id-${fieldCounter + 1}" type="text" class="wsc-input wsc-input-primary field-id" name="form-field-id-${fieldCounter + 1}" data-id="${fieldCounter + 1}" placeholder="Field ID" value="${item.id}">
                        <label for="form-class-${fieldCounter + 1}" class="wsc-form-label wsc-mt-4">Field Class</label>
                        <input id="form-class-${fieldCounter + 1}" type="text" class="wsc-input wsc-input-primary field-class" name="form-field-class-${fieldCounter + 1}" data-class="${fieldCounter + 1}" placeholder="Field class" value="${item.class}">
                        <label class="wsc-form-label wsc-mt-4" for="form-event-${fieldCounter + 1}">Javascript Event Type</label>
                        <select class="wsc-input wsc-input-primary form-event wsc-mt-4" id="form-event-${fieldCounter + 1}" name="form-event-${fieldCounter + 1}" data-id="${fieldCounter + 1}">
                            <option value="change" ${item.event === 'change' ? 'selected="selected"' : ''}>Chance</option>
                            <option value="input" ${item.event === 'input' ? 'selected="selected"' : ''}>Input</option>
                            <option value="submit" ${item.event === 'submit' ? 'selected="selected"' : ''}>Form Submit</option>
                        </select>
                    </div>
                    `;
                fields.append(newField);
            })
        }
        loadRequiredField()
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
        const pageId = $('#page_id').val();
        const formId = $('#form_id').val();
        const formClass = $('#form_class').val();
        const webRiskStatus = parseInt($('#wsc-webrisk-status').val())
        const virustotalStatus = parseInt($('#wsc-virustotal-status').val())
        const formSettings = [];

        $('#wsc-form-fields .wsc-form-group').each(function (i, item) {
            const fieldType = $(item).find('.form-field').val();
            const fieldId = $(item).find('.field-id').val();
            const fieldClass = $(item).find('.field-class').val();
            const fieldEvent = $(item).find('.form-event').val();
            const isRequired = parseInt($(item).find('#wsc-required-status .wsc-check').find('input').val());
            const isValidate = parseInt($(item).find('#wsc-validation-status .wsc-check').find('input').val());

            formSettings.push({field: fieldType, id: fieldId, class: fieldClass, event: fieldEvent, isRequired: isRequired, isValidate: isValidate});
        })

        $.ajax({
            method: 'POST',
            url: WPSpanChecker.ajaxurl,
            data: {
                action: 'add_form_settings',
                id: parseInt(id.val()) ?? 0,
                formType: formType,
                pageId: pageId,
                formId: formId,
                formClass: formClass,
                formSettings: formSettings,
                is_webrisk: webRiskStatus,
                is_virustotal: virustotalStatus,
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
                formError.html('Saved');
                formSettingTable.ajax.reload(null, false);
                setTimeout(() => {
                    const formSettingForm = document.getElementById('wsc-settings-form');
                    formSettingForm.reset();
                    $('#wsc-settings-form').toggleClass('wsc-hidden');
                    $('#form-setting-table').toggleClass('wsc-hidden');
                }, 1000)
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
        })
    });

    $('#wsc-settings-form').on('change', function () {
        loadRequiredField();
    });

    function loadRequiredField() {
        $('#wsc-form-fields .wsc-form-group').each(function (i, item) {
            if ($(item).find('.form-field').val() === 'text') {
                $(item).find('.field-id').attr('required', 'required');
            } else {
                $(item).find('.field-id').removeAttr('required');
            }
        })
    }

    loadRequiredField();

    $('#wscAddFormSetting, .toggleFormField').on('click', function () {
        $('#wsc-settings-form').toggleClass('wsc-hidden');
        $('#form-setting-table').toggleClass('wsc-hidden');
    })

    $('.wsc-switch-option').on('click', function () {
         const $this = $(this);
         $this.addClass('wsc-check').siblings().removeClass('wsc-check');
         $this.find('input').attr('checked', 'checked').siblings().find('input').removeAttr('checked');
    })

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
    element.html('');
    regexLists.forEach(item => {
        const $li = jQuery(`
            <li class="wsc-card wsc-p-3 border rounded" style="list-style:none; background:#fff;">
                <strong>${item.name}</strong>
                <div style="font-family:monospace; margin:8px 0;">${item.pattern}</div>
                <div style="color:#666; font-size:0.9em;">${item.desc}</div>
                <div style="margin-top:8px;">
                    <span style="font-family:monospace; background:#f4f4f4; padding:4px 8px; border-radius:4px;">Example: ${item.example}</span>
                    <button class="wsc-copy-button wsc-btn wsc-btn-outline-primary" type="button" data-regex="${item.pattern}" style="margin-left:8px; padding:4px 8px; border-radius:4px;" onclick="copyToClipboard(${item.pattern})">Copy</button>
                </div>
            </li>
        `);
        element.append($li);
    });
}

function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        // ✅ Modern way (requires HTTPS or localhost)
        return navigator.clipboard.writeText(text)
            .then(() => {
                wpSpanCheckerToast.fire({
                    icon: 'success',
                    title: "Copied",
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
                title: "Copied",
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

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
            return [];
        }
        if (Array.isArray(raw)) {
            return raw.map(String).filter(Boolean);
        }
        const s = String(raw).trim();
        if (!s) {
            return [];
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
        
        $('.wsc-page-target:checked:not(:disabled)').each(function () {
            const v = $(this).val();
            if (v) {
                out.push(String(v));
            }
        });
        
        if (!$('#wsc-panel-pages').hasClass('wsc-target-panel--disabled')) {
            wscSelectedPages.forEach((title, id) => {
                out.push(String(id));
            });
        }
        
        if (!$('#wsc-panel-posts').hasClass('wsc-target-panel--disabled')) {
            wscSelectedPosts.forEach((title, id) => {
                out.push(String(id));
            });
        }
        
        const uniq = [];
        out.forEach(function (x) {
            if (uniq.indexOf(x) === -1) {
                uniq.push(x);
            }
        });
        return uniq;
    }

    function wscCollectTargetsByType() {
        const result = {
            common: [],
            page: [],
            post: []
        };
        
        $('.wsc-page-target:checked:not(:disabled)').each(function () {
            const v = $(this).val();
            if (v) {
                result.common.push(String(v));
            }
        });
        
        if (!$('#wsc-panel-pages').hasClass('wsc-target-panel--disabled')) {
            wscSelectedPages.forEach((title, id) => {
                result.page.push(String(id));
            });
        }
        
        if (!$('#wsc-panel-posts').hasClass('wsc-target-panel--disabled')) {
            wscSelectedPosts.forEach((title, id) => {
                result.post.push(String(id));
            });
        }
        
        return result;
    }

    function wscApplyPageTargetsFromRaw(raw) {
        // Reset common locations
        $('.wsc-page-target').prop('checked', false).prop('disabled', false);
        
        // Clear badge selections
        wscSelectedPages.clear();
        wscSelectedPosts.clear();
        $('#wsc-pages-selected').empty();
        $('#wsc-posts-selected').empty();
        
        const list = wscNormalizePageTargetsRaw(raw);
        const pageIdsToFetch = [];
        const postIdsToFetch = [];
        
        list.forEach(function (token) {
            const $cb = $('.wsc-page-target').filter(function () {
                return $(this).val() === token;
            });
            if ($cb.length) {
                $cb.prop('checked', true);
                return;
            }
            if (/^\d+$/.test(token)) {
                // We'll need to fetch the title for this ID
                pageIdsToFetch.push(token);
                postIdsToFetch.push(token);
            }
        });
        
        // Fetch titles for numeric IDs (pages)
        if (pageIdsToFetch.length > 0) {
            $.post(WPSpanChecker.ajaxurl, {
                action: 'wsc_search_pages',
                nonce: WPSpanChecker.nonce,
                search: '',
                per_page: 100,
                page: 1
            }, function(response) {
                if (response.success && response.data.items) {
                    response.data.items.forEach(function(item) {
                        if (pageIdsToFetch.indexOf(String(item.id)) !== -1) {
                            wscSelectedPages.set(String(item.id), item.title);
                        }
                    });
                    wscRenderSelectedBadges('#wsc-pages-selected', wscSelectedPages, 'page');
                    // Update dropdown checkmarks
                    $('#wsc-pages-list .wsc-badge-dropdown-item').each(function() {
                        const id = String($(this).data('id'));
                        if (wscSelectedPages.has(id)) {
                            $(this).addClass('wsc-item-selected');
                            $(this).find('.wsc-item-checkbox').text('✓');
                        }
                    });
                }
            });
        }
        
        // Fetch titles for numeric IDs (posts)
        if (postIdsToFetch.length > 0) {
            $.post(WPSpanChecker.ajaxurl, {
                action: 'wsc_search_posts',
                nonce: WPSpanChecker.nonce,
                search: '',
                per_page: 100,
                page: 1
            }, function(response) {
                if (response.success && response.data.items) {
                    response.data.items.forEach(function(item) {
                        if (postIdsToFetch.indexOf(String(item.id)) !== -1) {
                            wscSelectedPosts.set(String(item.id), item.title);
                        }
                    });
                    wscRenderSelectedBadges('#wsc-posts-selected', wscSelectedPosts, 'post');
                    // Update dropdown checkmarks
                    $('#wsc-posts-list .wsc-badge-dropdown-item').each(function() {
                        const id = String($(this).data('id'));
                        if (wscSelectedPosts.has(id)) {
                            $(this).addClass('wsc-item-selected');
                            $(this).find('.wsc-item-checkbox').text('✓');
                        }
                    });
                }
            });
        }
        
        wscUpdateTargetConstraints();
        wscUpdateFormSelectorRequirement();
    }

    function wscResetPageTargetsForNew() {
        $('.wsc-page-target').prop('checked', false);
        wscSelectedPages.clear();
        wscSelectedPosts.clear();
        $('#wsc-pages-selected').empty();
        $('#wsc-posts-selected').empty();
        // Reset dropdown checkmarks
        $('#wsc-pages-list .wsc-badge-dropdown-item').removeClass('wsc-item-selected').find('.wsc-item-checkbox').text('');
        $('#wsc-posts-list .wsc-badge-dropdown-item').removeClass('wsc-item-selected').find('.wsc-item-checkbox').text('');
        wscUpdateTargetConstraints();
        wscUpdateFormSelectorRequirement();
    }

    function wscUpdateTargetConstraints() {
        const allPagesChecked = $('.wsc-page-target[value="all-pages"]').is(':checked');
        const singularPageChecked = $('.wsc-page-target[value="singular-page"]').is(':checked');
        const singularPostChecked = $('.wsc-page-target[value="singular-post"]').is(':checked');
        const singularAnyChecked = $('.wsc-page-target[value="singular-any"]').is(':checked');

        if (allPagesChecked) {
            $('.wsc-common-target').not('[value="all-pages"]').prop('disabled', true).prop('checked', false);
            $('#wsc-panel-pages').addClass('wsc-target-panel--disabled');
            $('#wsc-panel-posts').addClass('wsc-target-panel--disabled');
            // Clear badge selections when disabled
            wscSelectedPages.clear();
            wscSelectedPosts.clear();
            $('#wsc-pages-selected').empty();
            $('#wsc-posts-selected').empty();
        } else {
            $('.wsc-common-target').prop('disabled', false);
            $('#wsc-panel-pages').removeClass('wsc-target-panel--disabled');
            $('#wsc-panel-posts').removeClass('wsc-target-panel--disabled');

            if (singularAnyChecked) {
                $('#wsc-panel-pages').addClass('wsc-target-panel--disabled');
                $('#wsc-panel-posts').addClass('wsc-target-panel--disabled');
                wscSelectedPages.clear();
                wscSelectedPosts.clear();
                $('#wsc-pages-selected').empty();
                $('#wsc-posts-selected').empty();
            } else {
                if (singularPageChecked) {
                    $('#wsc-panel-pages').addClass('wsc-target-panel--disabled');
                    wscSelectedPages.clear();
                    $('#wsc-pages-selected').empty();
                } else {
                    $('#wsc-panel-pages').removeClass('wsc-target-panel--disabled');
                }

                if (singularPostChecked) {
                    $('#wsc-panel-posts').addClass('wsc-target-panel--disabled');
                    wscSelectedPosts.clear();
                    $('#wsc-posts-selected').empty();
                } else {
                    $('#wsc-panel-posts').removeClass('wsc-target-panel--disabled');
                }
            }
        }
    }

    function wscUpdateFormSelectorRequirement() {
        const allPagesChecked = $('.wsc-page-target[value="all-pages"]').is(':checked');
        
        $('#wsc-form-selector-optional').toggleClass('wsc-hidden', allPagesChecked);
        $('#wsc-form-selector-required').toggleClass('wsc-hidden', !allPagesChecked);
        $('#wsc-entire-site-notice').toggleClass('wsc-hidden', !allPagesChecked);
    }

    // Badge selection state
    const wscSelectedPages = new Map();
    const wscSelectedPosts = new Map();
    let wscPagesSearchTimeout = null;
    let wscPostsSearchTimeout = null;

    function wscRenderSelectedBadges(container, selectedMap, type) {
        const $container = $(container);
        $container.empty();
        
        selectedMap.forEach((title, id) => {
            const $badge = $('<span class="wsc-selected-badge" data-id="' + id + '" data-type="' + type + '"></span>');
            $badge.append('<span class="wsc-badge-title">' + wscEsc(title) + '</span>');
            $badge.append('<span class="wsc-badge-remove dashicons dashicons-no-alt" role="button" tabindex="0" title="' + wscT('remove', 'Remove') + '"></span>');
            $container.append($badge);
        });
    }

    function wscLoadItems(type, search, page) {
        const action = type === 'page' ? 'wsc_search_pages' : 'wsc_search_posts';
        const $dropdown = type === 'page' ? $('#wsc-pages-dropdown') : $('#wsc-posts-dropdown');
        const $list = type === 'page' ? $('#wsc-pages-list') : $('#wsc-posts-list');
        const $loading = $dropdown.find('.wsc-badge-dropdown-loading');
        const $empty = $dropdown.find('.wsc-badge-dropdown-empty');
        const selectedMap = type === 'page' ? wscSelectedPages : wscSelectedPosts;

        $dropdown.removeClass('wsc-hidden');
        $loading.removeClass('wsc-hidden');
        $empty.addClass('wsc-hidden');
        
        if (page === 1) {
            $list.empty();
        }

        $.post(WPSpanChecker.ajaxurl, {
            action: action,
            nonce: WPSpanChecker.nonce,
            search: search,
            per_page: 20,
            page: page
        }, function(response) {
            $loading.addClass('wsc-hidden');
            
            if (response.success && response.data.items) {
                const items = response.data.items;
                
                if (items.length === 0 && page === 1) {
                    $empty.removeClass('wsc-hidden');
                    return;
                }

                items.forEach(function(item) {
                    const isSelected = selectedMap.has(String(item.id));
                    const $item = $('<div class="wsc-badge-dropdown-item' + (isSelected ? ' wsc-item-selected' : '') + '" data-id="' + item.id + '" data-title="' + wscEsc(item.title) + '" data-type="' + type + '"></div>');
                    $item.append('<span class="wsc-item-checkbox">' + (isSelected ? '✓' : '') + '</span>');
                    $item.append('<span class="wsc-item-title">' + wscEsc(item.title) + '</span>');
                    $item.append('<span class="wsc-item-id">#' + item.id + '</span>');
                    $list.append($item);
                });

                // Add load more button if there are more pages
                if (response.data.page < response.data.total_pages) {
                    const $loadMore = $('<div class="wsc-badge-load-more" data-page="' + (response.data.page + 1) + '" data-search="' + wscEsc(search) + '" data-type="' + type + '"></div>');
                    $loadMore.text(wscT('loadMore', 'Load more') + ' (' + response.data.total + ' ' + wscT('total', 'total') + ')');
                    $list.append($loadMore);
                }
            }
        }).fail(function() {
            $loading.addClass('wsc-hidden');
            $empty.removeClass('wsc-hidden');
        });
    }

    // Initialize badge selectors on page load
    function wscInitBadgeSelectors() {
        // Load initial pages
        wscLoadItems('page', '', 1);
        // Load initial posts
        wscLoadItems('post', '', 1);
    }

    // Search pages with debounce
    $('#wsc-search-pages').on('input', function() {
        const search = $(this).val().trim();
        clearTimeout(wscPagesSearchTimeout);
        wscPagesSearchTimeout = setTimeout(function() {
            wscLoadItems('page', search, 1);
        }, 300);
    });

    // Search posts with debounce
    $('#wsc-search-posts').on('input', function() {
        const search = $(this).val().trim();
        clearTimeout(wscPostsSearchTimeout);
        wscPostsSearchTimeout = setTimeout(function() {
            wscLoadItems('post', search, 1);
        }, 300);
    });

    // Focus on search shows dropdown
    $('#wsc-search-pages').on('focus', function() {
        $('#wsc-pages-dropdown').removeClass('wsc-hidden');
    });

    $('#wsc-search-posts').on('focus', function() {
        $('#wsc-posts-dropdown').removeClass('wsc-hidden');
    });

    // Load more items
    $(document).on('click', '.wsc-badge-load-more', function() {
        const page = parseInt($(this).data('page'), 10);
        const search = $(this).data('search') || '';
        const type = $(this).data('type');
        $(this).remove();
        wscLoadItems(type, search, page);
    });

    // Select/deselect item from dropdown
    $(document).on('click', '.wsc-badge-dropdown-item', function() {
        const $item = $(this);
        const id = String($item.data('id'));
        const title = $item.data('title');
        const type = $item.data('type');
        const selectedMap = type === 'page' ? wscSelectedPages : wscSelectedPosts;
        const containerSelector = type === 'page' ? '#wsc-pages-selected' : '#wsc-posts-selected';

        if (selectedMap.has(id)) {
            selectedMap.delete(id);
            $item.removeClass('wsc-item-selected');
            $item.find('.wsc-item-checkbox').text('');
        } else {
            selectedMap.set(id, title);
            $item.addClass('wsc-item-selected');
            $item.find('.wsc-item-checkbox').text('✓');
        }

        wscRenderSelectedBadges(containerSelector, selectedMap, type);
        wscUpdateTargetConstraints();
        wscUpdateFormSelectorRequirement();
    });

    // Remove badge
    $(document).on('click', '.wsc-badge-remove', function(e) {
        e.stopPropagation();
        const $badge = $(this).closest('.wsc-selected-badge');
        const id = String($badge.data('id'));
        const type = $badge.data('type');
        const selectedMap = type === 'page' ? wscSelectedPages : wscSelectedPosts;
        const containerSelector = type === 'page' ? '#wsc-pages-selected' : '#wsc-posts-selected';
        const $dropdownItem = (type === 'page' ? $('#wsc-pages-list') : $('#wsc-posts-list')).find('.wsc-badge-dropdown-item[data-id="' + id + '"]');

        selectedMap.delete(id);
        $dropdownItem.removeClass('wsc-item-selected');
        $dropdownItem.find('.wsc-item-checkbox').text('');
        
        wscRenderSelectedBadges(containerSelector, selectedMap, type);
        wscUpdateTargetConstraints();
        wscUpdateFormSelectorRequirement();
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#wsc-pages-badge-wrap').length) {
            $('#wsc-pages-dropdown').addClass('wsc-hidden');
        }
        if (!$(e.target).closest('#wsc-posts-badge-wrap').length) {
            $('#wsc-posts-dropdown').addClass('wsc-hidden');
        }
    });

    $(document).on('change', '.wsc-common-target', function() {
        wscUpdateTargetConstraints();
        wscUpdateFormSelectorRequirement();
    });

    if ($('#wsc-panel-common').length) {
        wscInitBadgeSelectors();
        wscUpdateTargetConstraints();
        wscUpdateFormSelectorRequirement();
    }

    function wscUpdateAutoValidationMode() {
        const isAuto = $('#wsc-auto-validation').is(':checked');
        $('#wsc-auto-validation-rules').toggleClass('wsc-hidden', !isAuto);
        $('#wsc-manual-fields-section').toggleClass('wsc-hidden', isAuto);
    }

    $('#wsc-auto-validation').on('change', function() {
        wscUpdateAutoValidationMode();
    });

    function wscCollectAutoValidationRules() {
        return {
            email: {
                validate: $('#auto_email_validate').is(':checked'),
                mx: $('#auto_email_mx').is(':checked'),
                disposable: $('#auto_email_disposable').is(':checked'),
                webrisk: $('#auto_email_webrisk').is(':checked'),
                virustotal: $('#auto_email_virustotal').is(':checked')
            },
            url: {
                validate: $('#auto_url_validate').is(':checked'),
                webrisk: $('#auto_url_webrisk').is(':checked'),
                virustotal: $('#auto_url_virustotal').is(':checked')
            },
            textarea: {
                block_links: $('#auto_textarea_links').is(':checked'),
                ai_spam: $('#auto_textarea_ai').is(':checked')
            },
            username: {
                check_exists: $('#auto_username_exists').is(':checked')
            },
            password: {
                strength: $('#auto_password_strength').is(':checked')
            },
            text: {
                block_urls: $('#auto_text_no_urls').is(':checked')
            }
        };
    }

    function wscApplyAutoValidationRules(rules) {
        if (!rules) return;
        if (rules.email) {
            $('#auto_email_validate').prop('checked', !!rules.email.validate);
            $('#auto_email_mx').prop('checked', !!rules.email.mx);
            $('#auto_email_disposable').prop('checked', !!rules.email.disposable);
            $('#auto_email_webrisk').prop('checked', !!rules.email.webrisk);
            $('#auto_email_virustotal').prop('checked', !!rules.email.virustotal);
        }
        if (rules.url) {
            $('#auto_url_validate').prop('checked', !!rules.url.validate);
            $('#auto_url_webrisk').prop('checked', !!rules.url.webrisk);
            $('#auto_url_virustotal').prop('checked', !!rules.url.virustotal);
        }
        if (rules.textarea) {
            $('#auto_textarea_links').prop('checked', !!rules.textarea.block_links);
            $('#auto_textarea_ai').prop('checked', !!rules.textarea.ai_spam);
        }
        if (rules.username) {
            $('#auto_username_exists').prop('checked', !!rules.username.check_exists);
        }
        if (rules.password) {
            $('#auto_password_strength').prop('checked', !!rules.password.strength);
        }
        if (rules.text) {
            $('#auto_text_no_urls').prop('checked', !!rules.text.block_urls);
        }
    }

    function wscResetAutoValidationRules() {
        $('#auto_email_validate').prop('checked', true);
        $('#auto_email_mx').prop('checked', true);
        $('#auto_email_disposable').prop('checked', true);
        $('#auto_email_webrisk').prop('checked', false);
        $('#auto_email_virustotal').prop('checked', false);
        $('#auto_url_validate').prop('checked', true);
        $('#auto_url_webrisk').prop('checked', false);
        $('#auto_url_virustotal').prop('checked', false);
        $('#auto_textarea_links').prop('checked', false);
        $('#auto_textarea_ai').prop('checked', false);
        $('#auto_username_exists').prop('checked', false);
        $('#auto_password_strength').prop('checked', false);
        $('#auto_text_no_urls').prop('checked', false);
    }

    if ($('#wsc-auto-validation').length) {
        wscUpdateAutoValidationMode();
    }

    function wscFormatPageTargetsCell(raw) {
        const list = wscNormalizePageTargetsRaw(raw);
        if (!list.length) {
            return '—';
        }
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
        const req = num(d.isRequired ?? d.is_required, 0);
        const val = num(d.isValidate ?? d.is_validate, 0);
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
        let tun = num(d.check_username_exists ?? d.checkUsernameExists, 0);
        if (field === 'username' && !Object.prototype.hasOwnProperty.call(d, 'check_username_exists') && !Object.prototype.hasOwnProperty.call(d, 'checkUsernameExists')) {
            tun = 1;
        }
        const hasTextareaLinks = Object.prototype.hasOwnProperty.call(d, 'textarea_allow_links') || Object.prototype.hasOwnProperty.call(d, 'textareaAllowLinks');
        const tlinks = hasTextareaLinks
            ? (parseInt(d.textarea_allow_links ?? d.textareaAllowLinks, 10) || 0)
            : 1;
        const tai = num(d.textarea_ai_spam ?? d.textareaAiSpam, 0);
        const hasTextUrls = Object.prototype.hasOwnProperty.call(d, 'text_allow_urls') || Object.prototype.hasOwnProperty.call(d, 'textAllowUrls');
        const textUrlsAllow = hasTextUrls
            ? (parseInt(d.text_allow_urls ?? d.textAllowUrls, 10) !== 0)
            : true;
        const regexVal = wscEsc(d.regex || '');
        const selEv = d.event || 'change';

        function rad(name, val, cur) {
            const lab = val ? wscT('enable', 'Enable') : wscT('disable', 'Disable');
            const numVal = parseInt(val, 10) || 0;
            const numCur = parseInt(cur, 10) || 0;
            const isChecked = numCur === numVal;
            return '<span class="wsc-switch-option' + (isChecked ? ' wsc-check' : '') + '">' +
                '<input type="radio" name="' + name + '" value="' + numVal + '"' + (isChecked ? ' checked="checked"' : '') + '>' +
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
            '<div class="wsc-switch-control">' + rad('is_required_f_' + ix, 1, req) + rad('is_required_f_' + ix, 0, req) + '</div>' +
            '<span class="wsc-form-info-message wsc-text-info">' + wscT('requiredFieldHint', 'Mark the field as required in the browser.') + '</span></div>' +
            '<div class="wsc-form-attr wsc-mt-4">' +
            '<p class="wsc-form-label">' + wscT('requireValidation', 'Require validation') + '</p>' +
            '<div class="wsc-switch-control">' + rad('is_validate_f_' + ix, 1, val) + rad('is_validate_f_' + ix, 0, val) + '</div>' +
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
            '<div class="wsc-switch-control">' + rad('fg_webrisk_' + ix, 1, wr) + rad('fg_webrisk_' + ix, 0, wr) + '</div>' +
            '<span class="wsc-form-info-message wsc-text-info">' + wscT('webriskEmailUrlOnly', 'Used when “Form field” is Email or URL and “Require validation” is enabled for domain checks.') + '</span></div>' +
            '<div class="wsc-form-attr wsc-mt-4 wsc-fg-opt-email">' +
            '<p class="wsc-form-label">' + wscT('virusTotal', 'VirusTotal scanner') + '</p>' +
            '<div class="wsc-switch-control">' + rad('fg_vt_' + ix, 1, vt) + rad('fg_vt_' + ix, 0, vt) + '</div>' +
            '<span class="wsc-form-info-message wsc-text-info">' + wscT('vtEmailUrlOnly', 'Same as Web Risk: applies together with Email or URL domain validation.') + '</span></div>' +
            '<div class="wsc-form-attr wsc-mt-4 wsc-fg-opt-username">' +
            '<p class="wsc-form-label">' + wscT('usernameTakenCheck', 'Reject if username exists') + '</p>' +
            '<div class="wsc-switch-control">' + rad('fg_userexists_' + ix, 1, tun) + rad('fg_userexists_' + ix, 0, tun) + '</div>' +
            '<span class="wsc-form-info-message wsc-text-info">' + wscT('usernameTakenHint', 'Maps to a normal text/username input. When enabled, checks WordPress on change/input (debounced) and again on submit.') + '</span></div>' +
            '<div class="wsc-form-attr wsc-mt-4 wsc-fg-opt-text">' +
            '<p class="wsc-form-label">' + wscT('textAllowUrls', 'Allow URLs in value') + '</p>' +
            '<div class="wsc-switch-control">' + rad('fg_texturls_' + ix, 1, textUrlsAllow ? 1 : 0) + rad('fg_texturls_' + ix, 0, textUrlsAllow ? 1 : 0) + '</div>' +
            '<span class="wsc-form-info-message wsc-text-info">' + wscT('textAllowUrlsHint', 'Turn off to reject http(s) URLs inside this single-line field.') + '</span></div>' +
            '<div class="wsc-form-attr wsc-mt-4 wsc-fg-opt-textarea">' +
            '<p class="wsc-form-label">' + wscT('textareaAllowLinks', 'Allow links in message') + '</p>' +
            '<div class="wsc-switch-control">' + rad('fg_tlinks_' + ix, 1, tlinks) + rad('fg_tlinks_' + ix, 0, tlinks) + '</div></div>' +
            '<div class="wsc-form-attr wsc-mt-4 wsc-fg-opt-textarea">' +
            '<p class="wsc-form-label">' + wscT('textareaAiSpam', 'AI spam check') + '</p>' +
            '<div class="wsc-switch-control">' + rad('fg_tai_' + ix, 1, tai) + rad('fg_tai_' + ix, 0, tai) + '</div>' +
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
                responsivePriority: 2,
                render: function (data) {
                    return data ?? '';
                }
            },
            {
                data: 'form_type',
                responsivePriority: 3,
                render: function (data) {
                    return data ?? '';
                }
            },
            {
                data: 'page_id',
                responsivePriority: 5,
                render: function (data) {
                    return wscFormatPageTargetsCell(data);
                }
            },
            {
                data: null,
                responsivePriority: 4,
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
                responsivePriority: 6,
                render: function (data) {
                    return data ? String(data) : '—';
                }
            },
            {
                data: 'auto_validation',
                responsivePriority: 7,
                render: function (data, type, row) {
                    const isAuto = parseInt(data, 10) === 1 || data === true || data === '1';
                    if (isAuto) {
                        return '<span class="wsc-mode-badge wsc-mode-badge--auto">' + wscT('autoMode', 'Auto') + '</span>';
                    }
                    return '<span class="wsc-mode-badge wsc-mode-badge--manual">' + wscT('manualMode', 'Manual') + '</span>';
                }
            },
            {
                data: null,
                responsivePriority: 8,
                render: function (data, type, row) {
                    const isAuto = parseInt(row.auto_validation, 10) === 1 || row.auto_validation === true || row.auto_validation === '1';
                    
                    if (isAuto) {
                        let autoRules;
                        try {
                            autoRules = typeof row.auto_rules === 'string' ? JSON.parse(row.auto_rules) : row.auto_rules;
                        } catch (e) {
                            autoRules = {};
                        }
                        autoRules = autoRules || {};
                        
                        const tags = [];
                        if (autoRules.email) {
                            if (autoRules.email.validate) tags.push('<span class="wsc-validation-tag wsc-validation-tag--on">Email</span>');
                            if (autoRules.email.webrisk) tags.push('<span class="wsc-validation-tag wsc-validation-tag--on">Web Risk</span>');
                            if (autoRules.email.virustotal) tags.push('<span class="wsc-validation-tag wsc-validation-tag--on">VirusTotal</span>');
                        }
                        if (autoRules.textarea && autoRules.textarea.ai_spam) {
                            tags.push('<span class="wsc-validation-tag wsc-validation-tag--on">AI Spam</span>');
                        }
                        if (autoRules.username && autoRules.username.check_exists) {
                            tags.push('<span class="wsc-validation-tag wsc-validation-tag--on">Username</span>');
                        }
                        if (autoRules.password && autoRules.password.strength) {
                            tags.push('<span class="wsc-validation-tag wsc-validation-tag--on">Password</span>');
                        }
                        
                        return tags.length ? '<div class="wsc-validation-summary">' + tags.join('') + '</div>' : wscT('defaultRules', 'Default rules');
                    }
                    
                    let formFields;
                    try {
                        formFields = (typeof row.settings === "string") ? JSON.parse(row.settings) : row.settings;
                    } catch (e) {
                        return '';
                    }
                    if (!Array.isArray(formFields) || formFields.length === 0) return '—';
                    
                    return formFields.map(function (item, idx) {
                        const ft = item.field ?? '';
                        let guards = [];
                        if (ft === 'email' || ft === 'url') {
                            if (parseInt(item.is_webrisk, 10) === 1) guards.push('WR');
                            if (parseInt(item.is_virustotal, 10) === 1) guards.push('VT');
                        }
                        if (ft === 'textarea' && parseInt(item.textarea_ai_spam, 10) === 1) {
                            guards.push('AI');
                        }
                        const guardStr = guards.length ? ' (' + guards.join(', ') + ')' : '';
                        return '<span class="wsc-validation-tag">' + wscEsc(ft) + guardStr + '</span>';
                    }).join(' ');
                }
            },
            {
                data: null,
                responsivePriority: 1,
                className: 'wsc-actions-cell',
                render: function (data, type, row) {
                    const rowId = row.id ?? '';
                    const jsonData = JSON.stringify(row) ?? '{}';
                    return '<div class="wsc-action-buttons">' +
                        '<button class="wsc-btn wsc-btn-primary edit-form-setting" data-json=\'' + jsonData.replace(/'/g, '&#39;') + '\' data-id="' + rowId + '">' + wscT('edit', 'Edit') + '</button>' +
                        '<button class="wsc-btn wsc-btn-danger delete-form-setting" data-id="' + rowId + '">' + wscT('delete', 'Delete') + '</button>' +
                        '</div>';
                }
            }
        ],
        responsive: {
            details: {
                type: 'column',
                target: 'tr'
            }
        },
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

        const isAuto = parseInt(formData.auto_validation, 10) === 1 || formData.auto_validation === true || formData.auto_validation === '1';
        $('#wsc-auto-validation').prop('checked', isAuto);
        
        if (isAuto) {
            let autoRules;
            try {
                autoRules = typeof formData.auto_rules === 'string' ? JSON.parse(formData.auto_rules) : formData.auto_rules;
            } catch (e) {
                autoRules = {};
            }
            wscApplyAutoValidationRules(autoRules || {});
        } else {
            wscResetAutoValidationRules();
        }
        
        // Set reCAPTCHA checkbox
        const enableRecaptcha = parseInt(formData.enable_recaptcha, 10) === 1;
        $('#wsc-enable-recaptcha').prop('checked', enableRecaptcha);
        
        wscUpdateAutoValidationMode();

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
        const pageTargets = wscCollectPageTargetsArray();
        const pageId = JSON.stringify(pageTargets);
        const combinedSel = String($('#form_selector').val() || '').trim();
        const submitSel = String($('#submit_selector').val() || '').trim();
        const formSettings = [];

        const hasPageTarget = pageTargets.length > 0 && !(pageTargets.length === 1 && pageTargets[0] === 'all-pages' && !$('.wsc-page-target[value="all-pages"]').is(':checked'));
        const hasAnySelection = $('.wsc-page-target:checked').length > 0 ||
            wscSelectedPages.size > 0 ||
            wscSelectedPosts.size > 0;

        if (!hasAnySelection) {
            formError.removeClass('wsc-text-success');
            formError.addClass('wsc-form-error');
            formError.html(wscT('locationRequired', 'Please select at least one location (Common locations, Specific pages, or Specific posts).'));
            wscErrToast(wscT('locationRequired', 'Please select at least one location.'));
            return;
        }

        const hasFormSelector = combinedSel !== '';
        const hasSubmitSelector = submitSel !== '';
        const allPagesChecked = $('.wsc-page-target[value="all-pages"]').is(':checked');
        
        // Form ID/class is ONLY required when "Entire site" is selected
        // For specific pages/posts or common locations, we'll find the first form on the page
        if (allPagesChecked && !hasFormSelector) {
            formError.removeClass('wsc-text-success');
            formError.addClass('wsc-form-error');
            formError.html(wscT('formSelectorRequiredForEntireSite', 'Form id/class is required when targeting the entire site.'));
            wscErrToast(wscT('formSelectorRequiredForEntireSite', 'Form id/class is required for Entire site.'));
            return;
        }
        
        // For specific pages/posts or common locations, form selector is optional
        // The frontend will find the first <form> on the matching page

        const isAutoValidation = $('#wsc-auto-validation').is(':checked');
        const autoRules = isAutoValidation ? wscCollectAutoValidationRules() : {};
        
        if (!isAutoValidation) {
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
        }

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
                autoValidation: isAutoValidation ? 1 : 0,
                autoRules: JSON.stringify(autoRules),
                enableRecaptcha: $('#wsc-enable-recaptcha').is(':checked') ? 1 : 0,
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
            $('#wsc-auto-validation').prop('checked', true);
            $('#wsc-enable-recaptcha').prop('checked', false);
            wscResetAutoValidationRules();
            wscUpdateAutoValidationMode();
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

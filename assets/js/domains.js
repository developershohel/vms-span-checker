jQuery(document).ready(function ($) {
    const vefgToast = window.vefgToast;
    const I = (typeof VEFGChecker !== 'undefined' && VEFGChecker.i18n) ? VEFGChecker.i18n : {};
    const vefgT = function (key, fallback) {
        return (I[key] !== undefined && I[key] !== '') ? I[key] : fallback;
    };
    const vefgAjaxErr = function (data, fallback) {
        if (data && typeof data === 'object' && data.message) {
            return data.message;
        }
        if (typeof data === 'string' && data.length) {
            return data;
        }
        return fallback;
    };
    const vefgErrToast = function (msg) {
        vefgToast.fire({ icon: 'error', title: msg });
    };
    const vefgOkToast = function (msg) {
        vefgToast.fire({ icon: 'success', title: msg });
    };

    const pageTargetLabels = (typeof VEFGChecker !== 'undefined' && VEFGChecker.pageTargetLabels) ? VEFGChecker.pageTargetLabels : {};

    function vefgNormalizePageTargetsRaw(raw) {
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

    function vefgCollectPageTargetsArray() {
        const out = [];
        
        $('.vefg-page-target:checked:not(:disabled)').each(function () {
            const v = $(this).val();
            if (v) {
                out.push(String(v));
            }
        });
        
        if (!$('#vefg-panel-pages').hasClass('vefg-target-panel--disabled')) {
            vefgSelectedPages.forEach((title, id) => {
                out.push(String(id));
            });
        }
        
        if (!$('#vefg-panel-posts').hasClass('vefg-target-panel--disabled')) {
            vefgSelectedPosts.forEach((title, id) => {
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

    function vefgCollectTargetsByType() {
        const result = {
            common: [],
            page: [],
            post: []
        };
        
        $('.vefg-page-target:checked:not(:disabled)').each(function () {
            const v = $(this).val();
            if (v) {
                result.common.push(String(v));
            }
        });
        
        if (!$('#vefg-panel-pages').hasClass('vefg-target-panel--disabled')) {
            vefgSelectedPages.forEach((title, id) => {
                result.page.push(String(id));
            });
        }
        
        if (!$('#vefg-panel-posts').hasClass('vefg-target-panel--disabled')) {
            vefgSelectedPosts.forEach((title, id) => {
                result.post.push(String(id));
            });
        }
        
        return result;
    }

    function vefgApplyPageTargetsFromRaw(raw) {
        // Reset common locations
        $('.vefg-page-target').prop('checked', false).prop('disabled', false);
        
        // Clear badge selections
        vefgSelectedPages.clear();
        vefgSelectedPosts.clear();
        $('#vefg-pages-selected').empty();
        $('#vefg-posts-selected').empty();
        
        const list = vefgNormalizePageTargetsRaw(raw);
        const pageIdsToFetch = [];
        const postIdsToFetch = [];
        
        list.forEach(function (token) {
            const $cb = $('.vefg-page-target').filter(function () {
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
            $.post(VEFGChecker.ajaxurl, {
                action: 'vefg_search_pages',
                nonce: VEFGChecker.nonce,
                search: '',
                per_page: 100,
                page: 1
            }, function(response) {
                if (response.success && response.data.items) {
                    response.data.items.forEach(function(item) {
                        if (pageIdsToFetch.indexOf(String(item.id)) !== -1) {
                            vefgSelectedPages.set(String(item.id), item.title);
                        }
                    });
                    vefgRenderSelectedBadges('#vefg-pages-selected', vefgSelectedPages, 'page');
                    // Update dropdown checkmarks
                    $('#vefg-pages-list .vefg-badge-dropdown-item').each(function() {
                        const id = String($(this).data('id'));
                        if (vefgSelectedPages.has(id)) {
                            $(this).addClass('vefg-item-selected');
                            $(this).find('.vefg-item-checkbox').text('✓');
                        }
                    });
                }
            });
        }
        
        // Fetch titles for numeric IDs (posts)
        if (postIdsToFetch.length > 0) {
            $.post(VEFGChecker.ajaxurl, {
                action: 'vefg_search_posts',
                nonce: VEFGChecker.nonce,
                search: '',
                per_page: 100,
                page: 1
            }, function(response) {
                if (response.success && response.data.items) {
                    response.data.items.forEach(function(item) {
                        if (postIdsToFetch.indexOf(String(item.id)) !== -1) {
                            vefgSelectedPosts.set(String(item.id), item.title);
                        }
                    });
                    vefgRenderSelectedBadges('#vefg-posts-selected', vefgSelectedPosts, 'post');
                    // Update dropdown checkmarks
                    $('#vefg-posts-list .vefg-badge-dropdown-item').each(function() {
                        const id = String($(this).data('id'));
                        if (vefgSelectedPosts.has(id)) {
                            $(this).addClass('vefg-item-selected');
                            $(this).find('.vefg-item-checkbox').text('✓');
                        }
                    });
                }
            });
        }
        
        vefgUpdateTargetConstraints();
        vefgUpdateFormSelectorRequirement();
    }

    function vefgResetPageTargetsForNew() {
        $('.vefg-page-target').prop('checked', false);
        vefgSelectedPages.clear();
        vefgSelectedPosts.clear();
        $('#vefg-pages-selected').empty();
        $('#vefg-posts-selected').empty();
        // Reset dropdown checkmarks
        $('#vefg-pages-list .vefg-badge-dropdown-item').removeClass('vefg-item-selected').find('.vefg-item-checkbox').text('');
        $('#vefg-posts-list .vefg-badge-dropdown-item').removeClass('vefg-item-selected').find('.vefg-item-checkbox').text('');
        vefgUpdateTargetConstraints();
        vefgUpdateFormSelectorRequirement();
    }

    function vefgUpdateTargetConstraints() {
        const allPagesChecked = $('.vefg-page-target[value="all-pages"]').is(':checked');
        const singularPageChecked = $('.vefg-page-target[value="singular-page"]').is(':checked');
        const singularPostChecked = $('.vefg-page-target[value="singular-post"]').is(':checked');
        const singularAnyChecked = $('.vefg-page-target[value="singular-any"]').is(':checked');

        if (allPagesChecked) {
            $('.vefg-common-target').not('[value="all-pages"]').prop('disabled', true).prop('checked', false);
            $('#vefg-panel-pages').addClass('vefg-target-panel--disabled');
            $('#vefg-panel-posts').addClass('vefg-target-panel--disabled');
            // Clear badge selections when disabled
            vefgSelectedPages.clear();
            vefgSelectedPosts.clear();
            $('#vefg-pages-selected').empty();
            $('#vefg-posts-selected').empty();
        } else {
            $('.vefg-common-target').prop('disabled', false);
            $('#vefg-panel-pages').removeClass('vefg-target-panel--disabled');
            $('#vefg-panel-posts').removeClass('vefg-target-panel--disabled');

            if (singularAnyChecked) {
                $('#vefg-panel-pages').addClass('vefg-target-panel--disabled');
                $('#vefg-panel-posts').addClass('vefg-target-panel--disabled');
                vefgSelectedPages.clear();
                vefgSelectedPosts.clear();
                $('#vefg-pages-selected').empty();
                $('#vefg-posts-selected').empty();
            } else {
                if (singularPageChecked) {
                    $('#vefg-panel-pages').addClass('vefg-target-panel--disabled');
                    vefgSelectedPages.clear();
                    $('#vefg-pages-selected').empty();
                } else {
                    $('#vefg-panel-pages').removeClass('vefg-target-panel--disabled');
                }

                if (singularPostChecked) {
                    $('#vefg-panel-posts').addClass('vefg-target-panel--disabled');
                    vefgSelectedPosts.clear();
                    $('#vefg-posts-selected').empty();
                } else {
                    $('#vefg-panel-posts').removeClass('vefg-target-panel--disabled');
                }
            }
        }
    }

    function vefgUpdateFormSelectorRequirement() {
        const allPagesChecked = $('.vefg-page-target[value="all-pages"]').is(':checked');
        
        $('#vefg-form-selector-optional').toggleClass('vefg-hidden', allPagesChecked);
        $('#vefg-form-selector-required').toggleClass('vefg-hidden', !allPagesChecked);
        $('#vefg-entire-site-notice').toggleClass('vefg-hidden', !allPagesChecked);
    }

    // Badge selection state
    const vefgSelectedPages = new Map();
    const vefgSelectedPosts = new Map();
    let vefgPagesSearchTimeout = null;
    let vefgPostsSearchTimeout = null;

    function vefgRenderSelectedBadges(container, selectedMap, type) {
        const $container = $(container);
        $container.empty();
        
        selectedMap.forEach((title, id) => {
            const $badge = $('<span class="vefg-selected-badge" data-id="' + id + '" data-type="' + type + '"></span>');
            $badge.append('<span class="vefg-badge-title">' + vefgEsc(title) + '</span>');
            $badge.append('<span class="vefg-badge-remove dashicons dashicons-no-alt" role="button" tabindex="0" title="' + vefgT('remove', 'Remove') + '"></span>');
            $container.append($badge);
        });
    }

    function vefgLoadItems(type, search, page) {
        const action = type === 'page' ? 'vefg_search_pages' : 'vefg_search_posts';
        const $dropdown = type === 'page' ? $('#vefg-pages-dropdown') : $('#vefg-posts-dropdown');
        const $list = type === 'page' ? $('#vefg-pages-list') : $('#vefg-posts-list');
        const $loading = $dropdown.find('.vefg-badge-dropdown-loading');
        const $empty = $dropdown.find('.vefg-badge-dropdown-empty');
        const selectedMap = type === 'page' ? vefgSelectedPages : vefgSelectedPosts;

        $dropdown.removeClass('vefg-hidden');
        $loading.removeClass('vefg-hidden');
        $empty.addClass('vefg-hidden');
        
        if (page === 1) {
            $list.empty();
        }

        $.post(VEFGChecker.ajaxurl, {
            action: action,
            nonce: VEFGChecker.nonce,
            search: search,
            per_page: 20,
            page: page
        }, function(response) {
            $loading.addClass('vefg-hidden');
            
            if (response.success && response.data.items) {
                const items = response.data.items;
                
                if (items.length === 0 && page === 1) {
                    $empty.removeClass('vefg-hidden');
                    return;
                }

                items.forEach(function(item) {
                    const isSelected = selectedMap.has(String(item.id));
                    const $item = $('<div class="vefg-badge-dropdown-item' + (isSelected ? ' vefg-item-selected' : '') + '" data-id="' + item.id + '" data-title="' + vefgEsc(item.title) + '" data-type="' + type + '"></div>');
                    $item.append('<span class="vefg-item-checkbox">' + (isSelected ? '✓' : '') + '</span>');
                    $item.append('<span class="vefg-item-title">' + vefgEsc(item.title) + '</span>');
                    $item.append('<span class="vefg-item-id">#' + item.id + '</span>');
                    $list.append($item);
                });

                // Add load more button if there are more pages
                if (response.data.page < response.data.total_pages) {
                    const $loadMore = $('<div class="vefg-badge-load-more" data-page="' + (response.data.page + 1) + '" data-search="' + vefgEsc(search) + '" data-type="' + type + '"></div>');
                    $loadMore.text(vefgT('loadMore', 'Load more') + ' (' + response.data.total + ' ' + vefgT('total', 'total') + ')');
                    $list.append($loadMore);
                }
            }
        }).fail(function() {
            $loading.addClass('vefg-hidden');
            $empty.removeClass('vefg-hidden');
        });
    }

    // Initialize badge selectors on page load
    function vefgInitBadgeSelectors() {
        // Load initial pages
        vefgLoadItems('page', '', 1);
        // Load initial posts
        vefgLoadItems('post', '', 1);
    }

    // Search pages with debounce
    $('#vefg-search-pages').on('input', function() {
        const search = $(this).val().trim();
        clearTimeout(vefgPagesSearchTimeout);
        vefgPagesSearchTimeout = setTimeout(function() {
            vefgLoadItems('page', search, 1);
        }, 300);
    });

    // Search posts with debounce
    $('#vefg-search-posts').on('input', function() {
        const search = $(this).val().trim();
        clearTimeout(vefgPostsSearchTimeout);
        vefgPostsSearchTimeout = setTimeout(function() {
            vefgLoadItems('post', search, 1);
        }, 300);
    });

    // Focus on search shows dropdown
    $('#vefg-search-pages').on('focus', function() {
        $('#vefg-pages-dropdown').removeClass('vefg-hidden');
    });

    $('#vefg-search-posts').on('focus', function() {
        $('#vefg-posts-dropdown').removeClass('vefg-hidden');
    });

    // Load more items
    $(document).on('click', '.vefg-badge-load-more', function() {
        const page = parseInt($(this).data('page'), 10);
        const search = $(this).data('search') || '';
        const type = $(this).data('type');
        $(this).remove();
        vefgLoadItems(type, search, page);
    });

    // Select/deselect item from dropdown
    $(document).on('click', '.vefg-badge-dropdown-item', function() {
        const $item = $(this);
        const id = String($item.data('id'));
        const title = $item.data('title');
        const type = $item.data('type');
        const selectedMap = type === 'page' ? vefgSelectedPages : vefgSelectedPosts;
        const containerSelector = type === 'page' ? '#vefg-pages-selected' : '#vefg-posts-selected';

        if (selectedMap.has(id)) {
            selectedMap.delete(id);
            $item.removeClass('vefg-item-selected');
            $item.find('.vefg-item-checkbox').text('');
        } else {
            selectedMap.set(id, title);
            $item.addClass('vefg-item-selected');
            $item.find('.vefg-item-checkbox').text('✓');
        }

        vefgRenderSelectedBadges(containerSelector, selectedMap, type);
        vefgUpdateTargetConstraints();
        vefgUpdateFormSelectorRequirement();
    });

    // Remove badge
    $(document).on('click', '.vefg-badge-remove', function(e) {
        e.stopPropagation();
        const $badge = $(this).closest('.vefg-selected-badge');
        const id = String($badge.data('id'));
        const type = $badge.data('type');
        const selectedMap = type === 'page' ? vefgSelectedPages : vefgSelectedPosts;
        const containerSelector = type === 'page' ? '#vefg-pages-selected' : '#vefg-posts-selected';
        const $dropdownItem = (type === 'page' ? $('#vefg-pages-list') : $('#vefg-posts-list')).find('.vefg-badge-dropdown-item[data-id="' + id + '"]');

        selectedMap.delete(id);
        $dropdownItem.removeClass('vefg-item-selected');
        $dropdownItem.find('.vefg-item-checkbox').text('');
        
        vefgRenderSelectedBadges(containerSelector, selectedMap, type);
        vefgUpdateTargetConstraints();
        vefgUpdateFormSelectorRequirement();
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#vefg-pages-badge-wrap').length) {
            $('#vefg-pages-dropdown').addClass('vefg-hidden');
        }
        if (!$(e.target).closest('#vefg-posts-badge-wrap').length) {
            $('#vefg-posts-dropdown').addClass('vefg-hidden');
        }
    });

    $(document).on('change', '.vefg-common-target', function() {
        vefgUpdateTargetConstraints();
        vefgUpdateFormSelectorRequirement();
    });

    if ($('#vefg-panel-common').length) {
        vefgInitBadgeSelectors();
        vefgUpdateTargetConstraints();
        vefgUpdateFormSelectorRequirement();
    }

    function vefgUpdateAutoValidationMode() {
        const isAuto = $('#vefg-auto-validation').is(':checked');
        $('#vefg-auto-validation-rules').toggleClass('vefg-hidden', !isAuto);
        $('#vefg-manual-fields-section').toggleClass('vefg-hidden', isAuto);
    }

    $('#vefg-auto-validation').on('change', function() {
        vefgUpdateAutoValidationMode();
    });

    function vefgCollectAutoValidationRules() {
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

    function vefgApplyAutoValidationRules(rules) {
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

    function vefgResetAutoValidationRules() {
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

    if ($('#vefg-auto-validation').length) {
        vefgUpdateAutoValidationMode();
    }

    function vefgFormatPageTargetsCell(raw) {
        const list = vefgNormalizePageTargetsRaw(raw);
        if (!list.length) {
            return '—';
        }
        const parts = list.slice(0, 4).map(function (t) {
            if (pageTargetLabels[t]) {
                return pageTargetLabels[t];
            }
            if (/^\d+$/.test(t)) {
                const opt = document.querySelector('#vefg-target-pages option[value="' + t + '"], #vefg-target-posts option[value="' + t + '"]');
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
            url: VEFGChecker.ajaxurl,
            type: 'POST',
            data: function (d) {
                d.action = 'vefg_get_domains';
                d.nonce = VEFGChecker.nonce;
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
                    return '<button class="button delete-domain" data-id="' + row.id + '">' + vefgT('delete', 'Delete') + '</button>';
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

        $.post(VEFGChecker.ajaxurl, {
            action: 'vefg_add_domain',
            nonce: VEFGChecker.nonce,
            domain_type: domainType,
            domain: domain
        }, function (response) {
            if (response.success) {
                table.ajax.reload(null, false);
                $('#add-domain-form')[0].reset();
                vefgOkToast(vefgT('domainAdded', 'Domain added.'));
            } else {
                vefgErrToast(vefgAjaxErr(response.data, vefgT('errorAddingDomain', 'Error adding domain.')));
            }
        });
    });

    $('#vefg-import-whitelist-seed').on('click', function () {
        const $btn = $(this);
        Swal.fire({
            title: vefgT('importWhitelistSeedTitle', 'Import bundled whitelist domains?'),
            text: vefgT('importWhitelistSeedText', 'This will add common provider domains and skip existing entries.'),
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: vefgT('import', 'Import'),
            cancelButtonText: vefgT('cancel', 'Cancel'),
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            $btn.prop('disabled', true);
            $.post(VEFGChecker.ajaxurl, {
                action: 'vefg_import_whitelist_seed',
                nonce: VEFGChecker.nonce
            }, function (response) {
                if (response && response.success) {
                    table.ajax.reload(null, false);
                    vefgOkToast(vefgAjaxErr(response.data, vefgT('importDone', 'Whitelist import complete.')));
                } else {
                    vefgErrToast(vefgAjaxErr(response ? response.data : null, vefgT('importFailed', 'Whitelist import failed.')));
                }
            }).fail(function () {
                vefgErrToast(vefgT('importFailed', 'Whitelist import failed.'));
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });
    });

    // Delete domain
    $('#domains-table').on('click', '.delete-domain', function () {
        let id = $(this).data('id');
        Swal.fire({
            title: vefgT('confirmDeleteDomainTitle', 'Remove this domain?'),
            text: vefgT('confirmDeleteDomain', 'Are you sure you want to delete this domain?'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: vefgT('delete', 'Delete'),
            cancelButtonText: vefgT('cancel', 'Cancel'),
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            $.post(VEFGChecker.ajaxurl, {
                action: 'vefg_delete_domain',
                nonce: VEFGChecker.nonce,
                domain_type: domainType,
                id: id
            }, function (response) {
                if (response.success) {
                    table.ajax.reload(null, false);
                    vefgOkToast(vefgT('domainRemoved', 'Domain removed.'));
                } else {
                    vefgErrToast(vefgAjaxErr(response.data, vefgT('errorDeletingDomain', 'Error deleting domain.')));
                }
            });
        });
    });


    let vefgRegexTargetInput = null;

    function vefgEsc(s) {
        return String(s === undefined || s === null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function vefgFgNextIndex() {
        let max = 0;
        $('#vefg-form-fields .vefg-form-field-row').each(function () {
            const ix = parseInt($(this).attr('data-field-index'), 10) || 0;
            if (ix > max) {
                max = ix;
            }
        });
        return max + 1;
    }

    function vefgFgMergeApi(item, legacyRow) {
        const lr = legacyRow || {};
        const d = item || {};
        const has = function (k) {
            return Object.prototype.hasOwnProperty.call(d, k);
        };
        const wr = has('is_webrisk') ? (parseInt(d.is_webrisk, 10) || 0) : (parseInt(lr.is_webrisk, 10) || 0);
        const vt = has('is_virustotal') ? (parseInt(d.is_virustotal, 10) || 0) : (parseInt(lr.is_virustotal, 10) || 0);
        return { wr: wr, vt: vt };
    }

    function vefgFgToggleRow($row) {
        const t = $row.find('.form-field').val();
        $row.find('.vefg-fg-opt-email').toggle(t === 'email' || t === 'url');
        $row.find('.vefg-fg-opt-text').toggle(t === 'text');
        $row.find('.vefg-fg-opt-username').toggle(t === 'username');
        $row.find('.vefg-fg-opt-textarea').toggle(t === 'textarea');
        $row.find('.vefg-fg-opt-other').toggle(
            t === 'tel' || t === 'number' || t === 'password'
        );
    }

    function vefgFgPickRadio($row, name, val) {
        const sval = String(val);
        $row.find('input[type="radio"][name="' + name + '"]').each(function () {
            const on = String($(this).val()) === sval;
            $(this).prop('checked', on);
            $(this).closest('.vefg-switch-option').toggleClass('vefg-check', on);
        });
    }

    /** Reset toggles that do not apply when field type changes (conditional guard). */
    function vefgFgSyncConditionalRadios($row) {
        const ft = $row.find('.form-field').val();
        const ix = $row.attr('data-field-index');
        if (ft !== 'email' && ft !== 'url') {
            vefgFgPickRadio($row, 'fg_webrisk_' + ix, 0);
            vefgFgPickRadio($row, 'fg_vt_' + ix, 0);
        }
        if (ft !== 'text') {
            vefgFgPickRadio($row, 'fg_texturls_' + ix, 1);
        }
        if (ft !== 'username') {
            vefgFgPickRadio($row, 'fg_userexists_' + ix, 0);
        }
        if (ft !== 'textarea') {
            vefgFgPickRadio($row, 'fg_tlinks_' + ix, 1);
            vefgFgPickRadio($row, 'fg_tai_' + ix, 0);
        }
    }

    /** Strip irrelevant keys before save (matches server Form_Guard_Conditional::normalize_field_config). */
    function vefgFgEffectivePayload(ft, raw) {
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

    function vefgFgRowHtml(ix, item, legacyRow) {
        const d = item || {};
        const field = d.field || 'text';
        const api = vefgFgMergeApi(d, legacyRow);
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
        const regexVal = vefgEsc(d.regex || '');
        const selEv = d.event || 'change';

        function rad(name, val, cur) {
            const lab = val ? vefgT('enable', 'Enable') : vefgT('disable', 'Disable');
            const numVal = parseInt(val, 10) || 0;
            const numCur = parseInt(cur, 10) || 0;
            const isChecked = numCur === numVal;
            return '<span class="vefg-switch-option' + (isChecked ? ' vefg-check' : '') + '">' +
                '<input type="radio" name="' + name + '" value="' + numVal + '"' + (isChecked ? ' checked="checked"' : '') + '>' +
                '<label>' + lab + '</label></span>';
        }

        return (
            '<div class="vefg-form-field-row vefg-form-group" data-field-index="' + ix + '">' +
            '<span class="removeFormField dashicons dashicons-no-alt" role="button" tabindex="0"></span>' +
            '<div class="vefg-fg-row-banner vefg-mb-4">' +
            '<strong><span class="vefg-fg-row-badge">1</span>. ' + vefgT('mappedFieldTitle', 'Mapped form control') + '</strong>' +
            '<p class="vefg-form-info-message vefg-text-info vefg-mt-2 vefg-mb-0">' + vefgT('mappedFieldGuardsBlurb', 'Guards in this row apply only to this field’s ID/class. Use “Add field” for each separate input (10 fields → 10 rows).') + '</p>' +
            '</div>' +
            '<label class="vefg-form-label" for="form-field-' + ix + '">' + vefgT('formField', 'Form field') + '</label>' +
            '<select id="form-field-' + ix + '" class="vefg-input vefg-input-primary form-field">' +
            '<option value="text"' + optSel('text') + '>' + vefgT('optionText', 'Text') + '</option>' +
            '<option value="username"' + optSel('username') + '>' + vefgT('optionUsername', 'Username') + '</option>' +
            '<option value="textarea"' + optSel('textarea') + '>' + vefgT('optionTextarea', 'Textarea') + '</option>' +
            '<option value="email"' + optSel('email') + '>' + vefgT('optionEmail', 'Email') + '</option>' +
            '<option value="url"' + optSel('url') + '>' + vefgT('optionUrl', 'URL') + '</option>' +
            '<option value="tel"' + optSel('tel') + '>' + vefgT('optionTel', 'Telephone') + '</option>' +
            '<option value="number"' + optSel('number') + '>' + vefgT('optionNumber', 'Number') + '</option>' +
            '<option value="password"' + optSel('password') + '>' + vefgT('optionPassword', 'Password') + '</option>' +
            '</select>' +
            '<label class="vefg-form-label vefg-mt-4" for="form-id-' + ix + '">' + vefgT('fieldId', 'Field ID') + '</label>' +
            '<input id="form-id-' + ix + '" type="text" class="vefg-input vefg-input-primary field-id" placeholder="' + vefgEsc(vefgT('fieldId', 'Field ID')) + '" value="' + vefgEsc(d.id || '') + '">' +
            '<label class="vefg-form-label vefg-mt-4" for="form-class-' + ix + '">' + vefgT('fieldClass', 'Field class') + '</label>' +
            '<input id="form-class-' + ix + '" type="text" class="vefg-input vefg-input-primary field-class" placeholder="' + vefgEsc(vefgT('fieldClass', 'Field class')) + '" value="' + vefgEsc(d.class || '') + '">' +
            '<label class="vefg-form-label vefg-mt-4" for="form-event-' + ix + '">' + vefgT('javascriptEvent', 'JavaScript event') + '</label>' +
            '<select class="vefg-input vefg-input-primary form-event vefg-mt-4" id="form-event-' + ix + '">' +
            '<option value="change"' + (selEv === 'change' ? ' selected' : '') + '>' + vefgT('optionChange', 'Change') + '</option>' +
            '<option value="input"' + (selEv === 'input' ? ' selected' : '') + '>' + vefgT('optionInput', 'Input') + '</option>' +
            '<option value="submit"' + (selEv === 'submit' ? ' selected' : '') + '>' + vefgT('optionFormSubmit', 'Form submit') + '</option>' +
            '</select>' +
            '<fieldset class="vefg-fg-rules-fieldset vefg-fg-fieldset vefg-mt-4">' +
            '<legend class="vefg-form-label">' + vefgT('validationRulesLegend', 'Validation rules') + '</legend>' +
            '<div class="vefg-form-attr vefg-mt-2">' +
            '<p class="vefg-form-label">' + vefgT('requiredField', 'Required field') + '</p>' +
            '<div class="vefg-switch-control">' + rad('is_required_f_' + ix, 1, req) + rad('is_required_f_' + ix, 0, req) + '</div>' +
            '<span class="vefg-form-info-message vefg-text-info">' + vefgT('requiredFieldHint', 'Mark the field as required in the browser.') + '</span></div>' +
            '<div class="vefg-form-attr vefg-mt-4">' +
            '<p class="vefg-form-label">' + vefgT('requireValidation', 'Require validation') + '</p>' +
            '<div class="vefg-switch-control">' + rad('is_validate_f_' + ix, 1, val) + rad('is_validate_f_' + ix, 0, val) + '</div>' +
            '<span class="vefg-form-info-message vefg-text-info">' + vefgT('requireValidationHint', 'Run server-side validation for this field.') + '</span></div>' +
            '<label class="vefg-form-label vefg-mt-4" for="field-regex-' + ix + '">' + vefgT('customRegex', 'Custom regex (delimited)') + '</label>' +
            '<div class="vefg-flex vefg-gap-2 vefg-items-center vefg-flex-wrap">' +
            '<input id="field-regex-' + ix + '" type="text" class="vefg-input vefg-input-primary field-regex vefg-flex-grow" placeholder="/^[a-z]+$/" value="' + regexVal + '">' +
            '<button type="button" class="vefg-btn vefg-btn-outline-primary vefg-open-regex-presets">' + vefgT('presetRegex', 'Preset patterns') + '</button></div>' +
            '<span class="vefg-form-info-message vefg-text-info">' + vefgT('customRegexHint', 'Optional. Must look like /pattern/flags. Checked on the server when validation is enabled.') + '</span>' +
            '</fieldset>' +
            '<fieldset class="vefg-fg-security-fieldset vefg-fg-fieldset vefg-mt-4">' +
            '<legend class="vefg-form-label">' + vefgT('securityMethodsLegend', 'Protection methods (based on field type)') + '</legend>' +
            '<p class="vefg-form-info-message vefg-text-info vefg-mb-4">' + vefgT('securityMethodsIntro', 'Email and URL rows can enable Web Risk and VirusTotal (Google Web Risk defaults ON when you pick Email). Username rows can enable live “already registered” checks. Text adds URL-in-value rules; textarea adds links + AI spam screening.') + '</p>' +
            '<div class="vefg-form-attr vefg-mt-2 vefg-fg-opt-email">' +
            '<p class="vefg-form-label">' + vefgT('googleWebRisk', 'Google Web Risk') + '</p>' +
            '<div class="vefg-switch-control">' + rad('fg_webrisk_' + ix, 1, wr) + rad('fg_webrisk_' + ix, 0, wr) + '</div>' +
            '<span class="vefg-form-info-message vefg-text-info">' + vefgT('webriskEmailUrlOnly', 'Used when “Form field” is Email or URL and “Require validation” is enabled for domain checks.') + '</span></div>' +
            '<div class="vefg-form-attr vefg-mt-4 vefg-fg-opt-email">' +
            '<p class="vefg-form-label">' + vefgT('virusTotal', 'VirusTotal scanner') + '</p>' +
            '<div class="vefg-switch-control">' + rad('fg_vt_' + ix, 1, vt) + rad('fg_vt_' + ix, 0, vt) + '</div>' +
            '<span class="vefg-form-info-message vefg-text-info">' + vefgT('vtEmailUrlOnly', 'Same as Web Risk: applies together with Email or URL domain validation.') + '</span></div>' +
            '<div class="vefg-form-attr vefg-mt-4 vefg-fg-opt-username">' +
            '<p class="vefg-form-label">' + vefgT('usernameTakenCheck', 'Reject if username exists') + '</p>' +
            '<div class="vefg-switch-control">' + rad('fg_userexists_' + ix, 1, tun) + rad('fg_userexists_' + ix, 0, tun) + '</div>' +
            '<span class="vefg-form-info-message vefg-text-info">' + vefgT('usernameTakenHint', 'Maps to a normal text/username input. When enabled, checks WordPress on change/input (debounced) and again on submit.') + '</span></div>' +
            '<div class="vefg-form-attr vefg-mt-4 vefg-fg-opt-text">' +
            '<p class="vefg-form-label">' + vefgT('textAllowUrls', 'Allow URLs in value') + '</p>' +
            '<div class="vefg-switch-control">' + rad('fg_texturls_' + ix, 1, textUrlsAllow ? 1 : 0) + rad('fg_texturls_' + ix, 0, textUrlsAllow ? 1 : 0) + '</div>' +
            '<span class="vefg-form-info-message vefg-text-info">' + vefgT('textAllowUrlsHint', 'Turn off to reject http(s) URLs inside this single-line field.') + '</span></div>' +
            '<div class="vefg-form-attr vefg-mt-4 vefg-fg-opt-textarea">' +
            '<p class="vefg-form-label">' + vefgT('textareaAllowLinks', 'Allow links in message') + '</p>' +
            '<div class="vefg-switch-control">' + rad('fg_tlinks_' + ix, 1, tlinks) + rad('fg_tlinks_' + ix, 0, tlinks) + '</div></div>' +
            '<div class="vefg-form-attr vefg-mt-4 vefg-fg-opt-textarea">' +
            '<p class="vefg-form-label">' + vefgT('textareaAiSpam', 'AI spam check') + '</p>' +
            '<div class="vefg-switch-control">' + rad('fg_tai_' + ix, 1, tai) + rad('fg_tai_' + ix, 0, tai) + '</div>' +
            '<span class="vefg-form-info-message vefg-text-info">' + vefgT('textareaAiSpamHint', 'Uses AI settings from VMS Elements Form Guard → AI. Runs on the server when validation is enabled.') + '</span></div>' +
            '<div class="vefg-form-attr vefg-mt-4 vefg-fg-opt-other">' +
            '<p class="vefg-form-info-message vefg-text-info vefg-mb-0">' + vefgT('securityMethodsOtherHint', 'Extra reputation toggles appear for Email/URL. Use “Validation rules” above for required / server validation / regex.') + '</p></div>' +
            '</fieldset>' +
            '</div>'
        );
    }

    function vefgFgRefreshRowNumbers() {
        $('#vefg-form-fields .vefg-form-field-row').each(function (i) {
            $(this).find('.vefg-fg-row-badge').text(String(i + 1));
        });
    }

    function vefgFgAppendRow(item, legacyRow) {
        const ix = vefgFgNextIndex();
        $('#vefg-form-fields').append(vefgFgRowHtml(ix, item, legacyRow));
        const $nr = $('#vefg-form-fields .vefg-form-field-row[data-field-index="' + ix + '"]');
        $nr.data('vefgPrevField', $nr.find('.form-field').val());
        vefgFgToggleRow($nr);
        vefgFgSyncConditionalRadios($nr);
        vefgFgRefreshRowNumbers();
    }

    function vefgFgResetRows() {
        $('#vefg-form-fields').empty();
        vefgFgAppendRow(null, {});
    }

    function vefgFgDisplaySelector(row) {
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

    function vefgFgFillPresetModal($input) {
        vefgRegexTargetInput = $input;
        const $list = $('#vefg-regex-preset-list');
        $list.empty();
        const regexLists = (typeof VEFGChecker !== 'undefined' && VEFGChecker.regexList) ? VEFGChecker.regexList : [];
        regexLists.forEach(function (item) {
            const valid = vefgEsc(item.valid_example || item.example || '');
            const invalid = vefgEsc(item.invalid_example || '');
            const pat = vefgEsc(item.pattern || '');
            const $li = $('<li class="vefg-regex-preset-item vefg-card vefg-p-3 vefg-mb-3"></li>');
            $li.append('<strong>' + vefgEsc(item.name || '') + '</strong>');
            $li.append('<div class="vefg-regex-preset-pattern">' + pat + '</div>');
            $li.append('<div class="vefg-text-info vefg-mb-2">' + vefgEsc(item.desc || '') + '</div>');
            $li.append('<div class="vefg-mb-1"><span class="vefg-badge-ok">' + vefgT('validExample', 'Valid') + '</span> <code>' + valid + '</code></div>');
            $li.append('<div class="vefg-mb-2"><span class="vefg-badge-bad">' + vefgT('invalidExample', 'Invalid') + '</span> <code>' + invalid + '</code></div>');
            const $btn = $('<button type="button" class="vefg-btn vefg-btn-primary vefg-use-preset-regex"></button>').text(vefgT('usePattern', 'Use pattern'));
            $btn.attr('data-pattern', item.pattern || '');
            $li.append($btn);
            $list.append($li);
        });
        $('#vefg-regex-preset-modal').removeClass('vefg-hidden').attr('aria-hidden', 'false');
    }

    function vefgFgClosePresetModal() {
        $('#vefg-regex-preset-modal').addClass('vefg-hidden').attr('aria-hidden', 'true');
        vefgRegexTargetInput = null;
    }

    $('#vefg-form-fields').on('click', '.vefg-open-regex-presets', function () {
        vefgFgFillPresetModal($(this).closest('.vefg-form-field-row').find('.field-regex'));
    });

    $('#vefg-regex-preset-modal').on('click', '.vefg-regex-modal-overlay, .vefg-close-regex-modal', function () {
        vefgFgClosePresetModal();
    });

    $('#vefg-regex-preset-list').on('click', '.vefg-use-preset-regex', function () {
        const p = $(this).attr('data-pattern');
        if (vefgRegexTargetInput && p) {
            vefgRegexTargetInput.val(p);
        }
        vefgFgClosePresetModal();
    });

        $('#vefg-form-fields').on('change', '.form-field', function () {
            const $row = $(this).closest('.vefg-form-field-row');
            const ix = $row.attr('data-field-index');
            const prev = String($row.data('vefgPrevField') || '');
            const ft = $(this).val();
            vefgFgSyncConditionalRadios($row);
            if (ft === 'email' && prev !== 'email') {
                vefgFgPickRadio($row, 'fg_webrisk_' + ix, 1);
                vefgFgPickRadio($row, 'fg_vt_' + ix, 0);
            }
            if (ft === 'username' && prev !== 'username') {
                vefgFgPickRadio($row, 'fg_userexists_' + ix, 1);
            }
            vefgFgToggleRow($row);
            $row.data('vefgPrevField', ft);
        });

    $('#vefg-form-fields').on('click', '.removeFormField', function () {
        if ($('#vefg-form-fields .vefg-form-field-row').length <= 1) {
            vefgErrToast(vefgT('fgNeedOneField', 'Keep at least one field row.'));
            return;
        }
        $(this).closest('.vefg-form-field-row').remove();
        vefgFgRefreshRowNumbers();
    });

    $('#vefgAddFormField').on('click', function () {
        vefgFgAppendRow(null, {});
    });

    let formSettingTable = $('#form-setting-table').DataTable({
        ajax: {
            url: VEFGChecker.ajaxurl,
            type: 'POST',
            data: function (d) {
                d.action = 'get_form_settings';
                d.nonce = VEFGChecker.nonce;
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
                    return vefgFormatPageTargetsCell(data);
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
                        return '<span class="vefg-mode-badge vefg-mode-badge--auto">' + vefgT('autoMode', 'Auto') + '</span>';
                    }
                    return '<span class="vefg-mode-badge vefg-mode-badge--manual">' + vefgT('manualMode', 'Manual') + '</span>';
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
                            if (autoRules.email.validate) tags.push('<span class="vefg-validation-tag vefg-validation-tag--on">Email</span>');
                            if (autoRules.email.webrisk) tags.push('<span class="vefg-validation-tag vefg-validation-tag--on">Web Risk</span>');
                            if (autoRules.email.virustotal) tags.push('<span class="vefg-validation-tag vefg-validation-tag--on">VirusTotal</span>');
                        }
                        if (autoRules.textarea && autoRules.textarea.ai_spam) {
                            tags.push('<span class="vefg-validation-tag vefg-validation-tag--on">AI Spam</span>');
                        }
                        if (autoRules.username && autoRules.username.check_exists) {
                            tags.push('<span class="vefg-validation-tag vefg-validation-tag--on">Username</span>');
                        }
                        if (autoRules.password && autoRules.password.strength) {
                            tags.push('<span class="vefg-validation-tag vefg-validation-tag--on">Password</span>');
                        }
                        
                        return tags.length ? '<div class="vefg-validation-summary">' + tags.join('') + '</div>' : vefgT('defaultRules', 'Default rules');
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
                        return '<span class="vefg-validation-tag">' + vefgEsc(ft) + guardStr + '</span>';
                    }).join(' ');
                }
            },
            {
                data: null,
                responsivePriority: 1,
                className: 'vefg-actions-cell',
                render: function (data, type, row) {
                    const rowId = row.id ?? '';
                    const jsonData = JSON.stringify(row) ?? '{}';
                    return '<div class="vefg-action-buttons">' +
                        '<button class="vefg-btn vefg-btn-primary edit-form-setting" data-json=\'' + jsonData.replace(/'/g, '&#39;') + '\' data-id="' + rowId + '">' + vefgT('edit', 'Edit') + '</button>' +
                        '<button class="vefg-btn vefg-btn-danger delete-form-setting" data-id="' + rowId + '">' + vefgT('delete', 'Delete') + '</button>' +
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
            title: vefgT('confirmDeleteFormTitle', 'Remove this Form Guard mapping?'),
            text: vefgT('confirmDeleteFormSetting', 'Are you sure you want to delete this Form Guard mapping?'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: vefgT('delete', 'Delete'),
            cancelButtonText: vefgT('cancel', 'Cancel'),
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            formSettingForm.addClass('vefg-opacity');
            $.post(VEFGChecker.ajaxurl, {
                action: 'delete_form_setting',
                nonce: VEFGChecker.nonce,
                id: id
            }, function (response) {
                if (response.success) {
                    formSettingTable.ajax.reload(null, false);
                    vefgOkToast(vefgT('formSettingRemoved', 'Form Guard mapping removed.'));
                } else {
                    vefgErrToast(vefgAjaxErr(response.data, vefgT('errorDeletingSetting', 'Could not delete Form Guard mapping.')));
                }
                formSettingForm.removeClass('vefg-opacity');
            });
        });
    });

    $('#form-setting-table').on('click', '.edit-form-setting', function () {
        let formData = $(this).data('json');
        const id = $('#form_settings_id');
        id.val(formData.id);
        const fields = $('#vefg-form-fields');
        const formError = $('#vefg-form-error-message');
        formError.html('');
        vefgApplyPageTargetsFromRaw(formData.page_id);
        $('#form_type').val(formData.form_type !== '' ? formData.form_type : '');
        $('#form_selector').val(vefgFgDisplaySelector(formData));
        $('#submit_selector').val(formData.submit_selector ? String(formData.submit_selector) : '');

        const isAuto = parseInt(formData.auto_validation, 10) === 1 || formData.auto_validation === true || formData.auto_validation === '1';
        $('#vefg-auto-validation').prop('checked', isAuto);
        
        if (isAuto) {
            let autoRules;
            try {
                autoRules = typeof formData.auto_rules === 'string' ? JSON.parse(formData.auto_rules) : formData.auto_rules;
            } catch (e) {
                autoRules = {};
            }
            vefgApplyAutoValidationRules(autoRules || {});
        } else {
            vefgResetAutoValidationRules();
        }
        
        // Set reCAPTCHA checkbox
        const enableRecaptcha = parseInt(formData.enable_recaptcha, 10) === 1;
        $('#vefg-enable-recaptcha').prop('checked', enableRecaptcha);
        
        vefgUpdateAutoValidationMode();

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
            vefgFgAppendRow(null, formData);
        } else {
            formSettingData.forEach(function (item) {
                vefgFgAppendRow(item, formData);
            });
        }

        $('#vefg-settings-form').toggleClass('vefg-hidden');
        $('#form-setting-table').toggleClass('vefg-hidden');
    });

    $('#vefg-settings-form').on('submit', function (e) {
        e.preventDefault();
        const formSettingForm = $('#vefg-settings-form');
        const id = $('#form_settings_id');
        const saveButton = $('#saveFormSetting');
        const formError = $('#vefg-form-error-message');
        const formType = $('#form_type').val();
        const pageTargets = vefgCollectPageTargetsArray();
        const pageId = JSON.stringify(pageTargets);
        const combinedSel = String($('#form_selector').val() || '').trim();
        const submitSel = String($('#submit_selector').val() || '').trim();
        const formSettings = [];

        const hasPageTarget = pageTargets.length > 0 && !(pageTargets.length === 1 && pageTargets[0] === 'all-pages' && !$('.vefg-page-target[value="all-pages"]').is(':checked'));
        const hasAnySelection = $('.vefg-page-target:checked').length > 0 ||
            vefgSelectedPages.size > 0 ||
            vefgSelectedPosts.size > 0;

        if (!hasAnySelection) {
            formError.removeClass('vefg-text-success');
            formError.addClass('vefg-form-error');
            formError.html(vefgT('locationRequired', 'Please select at least one location (Common locations, Specific pages, or Specific posts).'));
            vefgErrToast(vefgT('locationRequired', 'Please select at least one location.'));
            return;
        }

        const hasFormSelector = combinedSel !== '';
        const hasSubmitSelector = submitSel !== '';
        const allPagesChecked = $('.vefg-page-target[value="all-pages"]').is(':checked');
        
        // Form ID/class is ONLY required when "Entire site" is selected
        // For specific pages/posts or common locations, we'll find the first form on the page
        if (allPagesChecked && !hasFormSelector) {
            formError.removeClass('vefg-text-success');
            formError.addClass('vefg-form-error');
            formError.html(vefgT('formSelectorRequiredForEntireSite', 'Form Selector is required when targeting the entire site.'));
            vefgErrToast(vefgT('formSelectorRequiredForEntireSite', 'Form Selector is required for Entire site.'));
            return;
        }
        
        // For specific pages/posts or common locations, form selector is optional
        // The frontend will find the first <form> on the matching page

        const isAutoValidation = $('#vefg-auto-validation').is(':checked');
        const autoRules = isAutoValidation ? vefgCollectAutoValidationRules() : {};
        
        if (!isAutoValidation) {
            $('#vefg-form-fields .vefg-form-field-row').each(function () {
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
                formSettings.push(vefgFgEffectivePayload(ft, raw));
            });
        }

        $.ajax({
            method: 'POST',
            url: VEFGChecker.ajaxurl,
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
                enableRecaptcha: $('#vefg-enable-recaptcha').is(':checked') ? 1 : 0,
                formSettings: formSettings,
                nonce: VEFGChecker.nonce
            },
            beforeSend: function () {
                formSettingForm.addClass('vefg-opacity');
                saveButton.prop('disabled', !$(this).prop('disabled'));
                saveButton.find('.vefg-spinner').removeClass('vefg-hidden');
                formError.html('');
            },
            success: function () {
                formError.addClass('vefg-text-success');
                formError.removeClass('vefg-form-error');
                formError.html(vefgT('saved', 'Saved'));
                formSettingTable.ajax.reload(null, false);
                setTimeout(() => {
                    const formSettingFormEl = document.getElementById('vefg-settings-form');
                    formSettingFormEl.reset();
                    $('#vefg-settings-form').toggleClass('vefg-hidden');
                    $('#form-setting-table').toggleClass('vefg-hidden');
                    vefgFgResetRows();
                }, 1000);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                formError.removeClass('vefg-text-success');
                formError.addClass('vefg-form-error');
                formError.html(errorThrown.message || String(errorThrown));
            },
            complete: function () {
                formSettingForm.removeClass('vefg-opacity');
                saveButton.removeAttr('disabled');
                saveButton.find('.vefg-spinner').addClass('vefg-hidden');
                id.val(0);
            }
        });
    });

    if ($('#vefg-form-fields').length) {
        vefgFgResetRows();
    }

    $('#vefgAddFormSetting').on('click', function () {
        const wasHidden = $('#vefg-settings-form').hasClass('vefg-hidden');
        $('#vefg-settings-form').toggleClass('vefg-hidden');
        $('#form-setting-table').toggleClass('vefg-hidden');
        if (wasHidden) {
            $('#form_settings_id').val('0');
            vefgResetPageTargetsForNew();
            $('#form_selector').val('');
            $('#submit_selector').val('');
            $('#vefg-auto-validation').prop('checked', true);
            $('#vefg-enable-recaptcha').prop('checked', false);
            vefgResetAutoValidationRules();
            vefgUpdateAutoValidationMode();
            vefgFgResetRows();
        }
    });

    $('.toggleFormField').on('click', function () {
        $('#vefg-settings-form').toggleClass('vefg-hidden');
        $('#form-setting-table').toggleClass('vefg-hidden');
    });

    $(document).on('click', '.vefg-switch-option', function () {
         const $this = $(this);
         $this.addClass('vefg-check').siblings('.vefg-switch-option').removeClass('vefg-check');
         $this.find('input[type="radio"]').prop('checked', true);
         $this.siblings('.vefg-switch-option').find('input[type="radio"]').prop('checked', false);
    });

}); // end jquery

// Open modal
function vefgOpenModal(element) {
    element.classList.add('vefg-block');
}

// Close modal
function vefgCloseModal(element) {
    element.classList.remove('vefg-block');
}

function vefgLoadRegex(e){
    const element = jQuery(e);
    vefgRenderRegexList(element.parent().next().find('ul'));
    vefgOpenModal(e.parentElement.nextElementSibling);
}

function vefgRenderRegexList(element){
    const regexLists = VEFGChecker.regexList;
    const L = (VEFGChecker.i18n) ? VEFGChecker.i18n : {};
    const t = (k, fb) => (L[k]) ? L[k] : fb;
    element.html('');
    regexLists.forEach(item => {
        const $li = jQuery(`
            <li class="vefg-card vefg-p-3 border rounded" style="list-style:none; background:#fff;">
                <strong>${item.name}</strong>
                <div style="font-family:monospace; margin:8px 0;">${item.pattern}</div>
                <div style="color:#666; font-size:0.9em;">${item.desc}</div>
                <div style="margin-top:8px;">
                    <span style="font-family:monospace; background:#f4f4f4; padding:4px 8px; border-radius:4px;">${t('examplePrefix', 'Example:')} ${item.example}</span>
                    <button class="vefg-copy-button vefg-btn vefg-btn-outline-primary" type="button" data-regex="${item.pattern}" style="margin-left:8px; padding:4px 8px; border-radius:4px;" onclick="vefgCopyToClipboard(${item.pattern})">${t('copy', 'Copy')}</button>
                </div>
            </li>
        `);
        element.append($li);
    });
}

function vefgCopyToClipboard(text) {
    const L = (typeof VEFGChecker !== 'undefined' && VEFGChecker.i18n) ? VEFGChecker.i18n : {};
    const t = (k, fb) => (L[k]) ? L[k] : fb;
    if (navigator.clipboard && window.isSecureContext) {
        // ✅ Modern way (requires HTTPS or localhost)
        return navigator.clipboard.writeText(text)
            .then(() => {
                vefgToast.fire({
                    icon: 'success',
                    title: t('copied', 'Copied'),
                    position: 'bottom-right',
                })
                console.log("Copied to clipboard:", text);
            })
            .catch(err => {
                vefgToast.fire({
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
            vefgToast.fire({
                icon: 'success',
                title: t('copied', 'Copied'),
                position: 'bottom-right',
            })
        } catch (err) {
            vefgToast.fire({
                icon: 'error',
                title: err.message ?? String(err),
                position: 'bottom-right',
            })
        }

        document.body.removeChild(textArea);
    }
}

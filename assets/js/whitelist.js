jQuery(document).ready(function($){
    function loadWhitelist(page = 1){
        $.post(WPSpanCheckerAjax.ajax_url, {
            action: 'get_whitelist_domains',
            page: page,
            _wpnonce: WPSpanCheckerAjax.nonce
        }, function(response){
            if(response.success){
                const data = response.data;
                let html = '';
                data.domains.forEach(d => {
                    html += `<tr><td>${d.id}</td><td>${d.domain}</td></tr>`;
                });
                $('#whitelist-table tbody').html(html);

                // Pagination
                let pagination = '';
                for(let i=1;i<=data.total_pages;i++){
                    if(i === data.current_page){
                        pagination += `<span class="current">${i}</span> `;
                    } else {
                        pagination += `<a href="#" class="page-link" data-page="${i}">${i}</a> `;
                    }
                }
                $('#whitelist-pagination').html(pagination);
            }
        });
    }

    loadWhitelist(); // load first page

    $(document).on('click', '#whitelist-pagination .page-link', function(e){
        e.preventDefault();
        const page = $(this).data('page');
        loadWhitelist(page);
    });
});

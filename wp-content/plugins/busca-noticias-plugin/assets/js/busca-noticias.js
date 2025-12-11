jQuery(function ($) {
    const input = $('.bn-search-input');
    // Debounce para reduzir número de requisições enquanto usuário digita
    let debounceTimer = null;
    const DEBOUNCE_DELAY = 300; // ms

    function doSearch($input, $resultsBox) {
        const term = $input.val().trim();
        const min = parseInt($input.data('min'));
        const limit = parseInt($input.data('limit'));
        const postType = $input.attr('data-post-type') || 'noticia';
        const taxonomy = $input.attr('data-taxonomy') || '';
        const taxTerm = $input.attr('data-tax-term') || '';

        if (term.length < min) {
            $resultsBox.removeClass('active').empty();
            return;
        }

        $resultsBox.addClass('loading');

        $.post(BN_Search.ajax_url, {
            action: 'busca_noticias',
            nonce: BN_Search.nonce,
            term: term,
            limit: limit,
            post_type: postType,
            taxonomy: taxonomy,
            tax_term: taxTerm
        }).done(function (response) {
            $resultsBox.removeClass('loading');

            if (!response.success || !response.data || response.data.length === 0) {
                $resultsBox.removeClass('active').empty();
                return;
            }

            $resultsBox.empty();

            // Construir o DOM de forma segura (não usar template literals com html cru)
            response.data.forEach(function (item) {
                const $a = $('<a>').addClass('bn-card').attr('href', item.permalink);

                const $thumb = $('<div>').addClass('bn-thumb');
                if (item.thumbnail) {
                    $('<img>').attr('src', item.thumbnail).attr('alt', '').appendTo($thumb);
                } else {
                    $('<div>').addClass('bn-no-thumb').appendTo($thumb);
                }

                const $info = $('<div>').addClass('bn-info');
                $('<h4>').text(item.title).appendTo($info);
                $('<p>').text(item.resumo.slice(0, 150) + '...').appendTo($info);

                $a.append($thumb).append($info);
                $resultsBox.append($a);
            });

            $resultsBox.addClass('active');
        }).fail(function () {
            $resultsBox.removeClass('loading').removeClass('active').empty();
        });
    }

    input.on('input', function () {
        const $this = $(this);
        const $resultsBox = $this.siblings('.bn-results');
        
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            doSearch($this, $resultsBox);
        }, DEBOUNCE_DELAY);
    });

    input.on('keypress', function (e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            window.location.origin = '?s=' + encodeURIComponent($(this).val().trim())+'&post_type='+$(this).attr('data-post-type');
        }
        });

    // Ocultar ao clicar fora
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.bn-search-wrap').length) {
            $('.bn-results').removeClass('active').empty();
        }
    });
});

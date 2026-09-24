<?php
if (!defined('ABSPATH')) exit;

function dj_repository_icon($name) {
    $paths = [
        'repository' => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5z"></path><path d="M4 5.5v15A2.5 2.5 0 0 1 6.5 18H20"></path><path d="M8 7h8M8 11h8"></path>',
        'categories' => '<path d="m3 7 4-4h5l9 9-7 7-9-9z"></path><circle cx="9" cy="8" r="1"></circle>',
        'subjects' => '<path d="m20.5 13.5-7 7a2 2 0 0 1-2.8 0l-7.2-7.2a2 2 0 0 1 0-2.8l7-7a2 2 0 0 1 2.8 0l7.2 7.2a2 2 0 0 1 0 2.8z"></path><circle cx="8.5" cy="8.5" r="1.3"></circle>',
        'featured' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-2.9-5.6 2.9 1.1-6.2L3 9.6l6.2-.9z"></path>',
        'document' => '<path d="M6 3h8l4 4v14H6z"></path><path d="M14 3v5h4M9 13h6M9 17h6"></path>',
    ];

    if (!isset($paths[$name])) return '';
    return '<svg class="dj-icon-svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths[$name].'</svg>';
}

function dj_shortcode_repository() {
    wp_enqueue_style('dj-repository', DJ_URL . 'assets/css/repository.css', [], DJ_VERSION);
    wp_enqueue_script('dj-repository', DJ_URL . 'assets/js/repository.js', [], DJ_VERSION, true);

    wp_localize_script('dj-repository', 'DJRepository', [
        'apiUrl' => esc_url_raw(rest_url('documentos-juridicos/v1/')),
        'nonce' => wp_create_nonce('wp_rest'),
    ]);

    ob_start(); ?>
    <div class="dj-repository" id="dj-repository">
        <div class="dj-header">
            <h1><span class="dj-icon dj-icon--title"><?php echo dj_repository_icon('repository'); ?></span> Repositório de Documentos</h1>
            <div class="dj-search">
                <label class="screen-reader-text" for="dj-search-input">Pesquisar documentos</label>
                <input id="dj-search-input" type="search" placeholder="Pesquisar documentos, assuntos, número SEI..." autocomplete="off">
            </div>
        </div>

        <div class="dj-layout">
            <aside class="dj-sidebar">
                <div class="dj-filter-group">
                    <h2><span class="dj-icon"><?php echo dj_repository_icon('categories'); ?></span> Categorias</h2>
                    <div id="dj-categories"></div>
                </div>
                <div class="dj-filter-group">
                    <h2><span class="dj-icon"><?php echo dj_repository_icon('subjects'); ?></span> Assuntos</h2>
                    <div id="dj-subjects"></div>
                </div>
                <button type="button" class="dj-clear">Limpar filtros</button>
            </aside>

            <main class="dj-main">
                <section class="dj-featured">
                    <div class="dj-section-title">
                        <h2><span class="dj-icon"><?php echo dj_repository_icon('featured'); ?></span> Mais acessados</h2>
                    </div>
                    <div class="dj-featured-grid" id="dj-featured"></div>
                </section>

                <section class="dj-results">
                    <div class="dj-section-title">
                        <h2><span class="dj-icon"><?php echo dj_repository_icon('document'); ?></span> Documentos</h2>
                        <span id="dj-result-count"></span>
                    </div>
                    <div class="dj-grid" id="dj-results"></div>
                    <div class="dj-loading" id="dj-loading" hidden>Carregando...</div>
                    <nav class="dj-pagination" id="dj-pagination" aria-label="Paginação"></nav>
                </section>
            </main>
        </div>
    </div>
    <div class="dj-pdf-modal" id="dj-pdf-modal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="dj-pdf-modal-title">
        <div class="dj-pdf-modal__backdrop" data-dj-pdf-close></div>
        <div class="dj-pdf-modal__content">
            <div class="dj-pdf-modal__header">
                <h2 id="dj-pdf-modal-title">Visualizar PDF</h2>
                <button type="button" class="dj-pdf-modal__close" data-dj-pdf-close aria-label="Fechar visualização">&times;</button>
            </div>
            <iframe class="dj-pdf-modal__iframe" id="dj-pdf-iframe" title="Visualização do PDF" src=""></iframe>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('repositorio_juridico', 'dj_shortcode_repository');

function dj_pdf_download_url($post_id) {
    return add_query_arg([
        'action' => 'dj_download_pdf',
        'post_id' => absint($post_id),
    ], admin_url('admin-ajax.php'));
}

add_action('wp_ajax_dj_download_pdf', 'dj_ajax_download_pdf');
add_action('wp_ajax_nopriv_dj_download_pdf', 'dj_ajax_download_pdf');
function dj_ajax_download_pdf() {
    $post_id = absint($_GET['post_id'] ?? 0);
    if (!$post_id || get_post_type($post_id) !== 'documento_juridico' || get_post_status($post_id) !== 'publish') {
        wp_die('Documento não encontrado.', '', 404);
    }

    $url = get_post_meta($post_id, '_dj_pdf_url', true);
    if (empty($url)) wp_die('PDF não encontrado.', '', 404);

    $response = wp_remote_get($url, ['timeout' => 60]);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        wp_die('Não foi possível baixar o PDF.', '', 502);
    }

    nocache_headers();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="documento-' . $post_id . '.pdf"');
    echo wp_remote_retrieve_body($response);
    exit;
}

function dj_enqueue_single_assets() {
    if (is_singular('documento_juridico')) {
        wp_enqueue_style('dj-single', DJ_URL . 'assets/css/repository.css', [], DJ_VERSION);
        wp_enqueue_script('dj-single-view', DJ_URL . 'assets/js/single-view.js', [], DJ_VERSION, true);
        wp_localize_script('dj-single-view', 'DJSingle', [
            'apiUrl' => esc_url_raw(rest_url('documentos-juridicos/v1/')),
            'id' => get_the_ID(),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'dj_enqueue_single_assets');

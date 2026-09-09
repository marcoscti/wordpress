<?php
if (!defined('ABSPATH')) exit;

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
            <h1>Repositório de Documentos Jurídicos</h1>
            <div class="dj-search">
                <label class="screen-reader-text" for="dj-search-input">Pesquisar documentos</label>
                <input id="dj-search-input" type="search" placeholder="Pesquisar documentos, assuntos, número SEI..." autocomplete="off">
            </div>
        </div>

        <div class="dj-layout">
            <aside class="dj-sidebar">
                <div class="dj-filter-group">
                    <h2>Categorias</h2>
                    <div id="dj-categories"></div>
                </div>
                <div class="dj-filter-group">
                    <h2>Assuntos</h2>
                    <div id="dj-subjects"></div>
                </div>
                <button type="button" class="dj-clear">Limpar filtros</button>
            </aside>

            <main class="dj-main">
                <section class="dj-featured">
                    <div class="dj-section-title">
                        <h2>Mais acessados</h2>
                    </div>
                    <div class="dj-featured-grid" id="dj-featured"></div>
                </section>

                <section class="dj-results">
                    <div class="dj-section-title">
                        <h2>Documentos</h2>
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

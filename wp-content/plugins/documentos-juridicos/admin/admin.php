<?php
if (!defined('ABSPATH')) exit;

function dj_admin_columns($columns) {
    //$columns['dj_ai'] = 'IA';
    $columns['dj_views'] = 'Acessos';
    return $columns;
}
add_filter('manage_documento_juridico_posts_columns', 'dj_admin_columns');


function dj_add_pdf_metabox() {
    add_meta_box(
        'dj_pdf_box',
        'Documento PDF',
        'dj_pdf_metabox',
        'documento_juridico',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'dj_add_pdf_metabox');

function dj_pdf_metabox($post) {
    wp_nonce_field('dj_save_pdf', 'dj_pdf_nonce');
    $url = get_post_meta($post->ID, '_dj_pdf_url', true);
    ?>
    <p>
        <label for="dj_pdf_url">URL do PDF</label>
        <input type="url" class="widefat" id="dj_pdf_url" name="dj_pdf_url" value="<?php echo esc_attr($url); ?>">
    </p>
    <p class="description">No MVP, informe a URL do PDF. A próxima etapa pode incluir upload múltiplo e seleção pela Biblioteca de Mídia.</p>
    <?php
}

function dj_save_pdf_metabox($post_id) {
    if (!isset($_POST['dj_pdf_nonce']) || !wp_verify_nonce($_POST['dj_pdf_nonce'], 'dj_save_pdf')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    update_post_meta($post_id, '_dj_pdf_url', esc_url_raw($_POST['dj_pdf_url'] ?? ''));
    // Ao salvar/atualizar o campo do PDF, agendar reprocessamento por IA
    if (function_exists('dj_ai_schedule')) {
        dj_ai_schedule($post_id);
    }
}
add_action('save_post_documento_juridico', 'dj_save_pdf_metabox', 30);


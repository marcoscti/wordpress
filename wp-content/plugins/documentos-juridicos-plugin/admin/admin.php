<?php
if (!defined('ABSPATH')) exit;

function dj_admin_columns($columns) {
    $columns['dj_ai'] = 'IA';
    $columns['dj_views'] = 'Acessos';
    return $columns;
}
add_filter('manage_documento_juridico_posts_columns', 'dj_admin_columns');

function dj_admin_column_content($column, $post_id) {
    if ($column === 'dj_ai') {
        $status = get_post_meta($post_id, '_dj_ai_status', true) ?: 'pending';
        $labels = [
            'pending' => 'Pendente',
            'processing' => 'Processando',
            'completed' => 'Concluído',
            'failed' => 'Falhou',
        ];
        echo esc_html($labels[$status] ?? $status);
    }
    if ($column === 'dj_views') {
        echo esc_html((int) get_post_meta($post_id, '_dj_views', true));
    }
}
add_action('manage_documento_juridico_posts_custom_column', 'dj_admin_column_content', 10, 2);

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

// ===== Configurações de IA =====
add_action('admin_menu', function(){
    add_options_page('Documentos Jurídicos — IA', 'DJ IA', 'manage_options', 'dj-ia', function(){
        ?>
        <div class="wrap"><h1>Configurações IA — Documentos Jurídicos</h1>
        <form method="post" action="options.php">
            <?php settings_fields('dj_ai_settings'); do_settings_sections('dj_ai_settings'); ?>
            <table class="form-table">
            <tr><th scope="row">API Key</th><td><input name="dj_ai_api_key" value="<?php echo esc_attr(get_option('dj_ai_api_key','')); ?>" class="regular-text" autocomplete="off"></td></tr>
            <tr><th scope="row">Modelo</th><td><input name="dj_ai_model" value="<?php echo esc_attr(get_option('dj_ai_model','gpt-4o-mini')); ?>" class="regular-text"></td></tr>
            <tr><th scope="row">Prompt (opcional)</th><td><input name="dj_ai_prompt" value="<?php echo esc_attr(get_option('dj_ai_prompt','Resuma o conteúdo abaixo em 3-5 linhas.')); ?>" class="regular-text"></td></tr>
            </table>
            <?php submit_button(); ?>
        </form></div>
        <?php
    });
});
add_action('admin_init', function(){
    register_setting('dj_ai_settings', 'dj_ai_api_key');
    register_setting('dj_ai_settings', 'dj_ai_model');
    register_setting('dj_ai_settings', 'dj_ai_prompt');
});


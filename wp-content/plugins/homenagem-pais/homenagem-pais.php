<?php

/**
 * Plugin Name: Homenagem Pais
 * Description: Registra o Custom Post Type `homenagem` e provê endpoint AJAX para submissão de homenagens (para uso em landing pages).
 * Version: 1.0.0
 * Author: Marcos Cordeiro
 */

if (!defined('ABSPATH')) exit;

define('HP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HP_HOMENAGEM_PER_PAGE', 12);

add_action('init', 'hp_register_homenagem_cpt');
add_action('wp_enqueue_scripts', 'meu_plugin_carregar_dashicons');
function meu_plugin_carregar_dashicons()
{
    wp_enqueue_style('dashicons');
}
function hp_register_homenagem_cpt()
{
    if (post_type_exists('homenagem')) {
        return;
    }

    $labels = array(
        'name' => __('Homenagens', 'homenagem-pais'),
        'singular_name' => __('Homenagem', 'homenagem-pais'),
        'add_new' => __('Adicionar nova', 'homenagem-pais'),
        'add_new_item' => __('Adicionar nova homenagem', 'homenagem-pais'),
        'edit_item' => __('Editar homenagem', 'homenagem-pais'),
        'new_item' => __('Nova homenagem', 'homenagem-pais'),
        'all_items' => __('Todas as homenagens', 'homenagem-pais'),
        'view_item' => __('Ver homenagem', 'homenagem-pais'),
        'search_items' => __('Pesquisar homenagens', 'homenagem-pais'),
        'not_found' => __('Nenhuma homenagem encontrada', 'homenagem-pais'),
        'not_found_in_trash' => __('Nenhuma homenagem no lixo', 'homenagem-pais'),
        'menu_name' => __('Dia dos Pais', 'homenagem-pais'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'homenagem'),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 20,
        'supports' => array('title', 'editor', 'thumbnail'),
        'menu_icon' => 'dashicons-businessperson',
        'show_in_rest' => true,
    );

    register_post_type('homenagem', $args);
}

// Enqueue frontend assets
add_action('wp_enqueue_scripts', 'hp_enqueue_assets');
function hp_enqueue_assets()
{
    wp_register_style('homenagem-pais-style', HP_PLUGIN_URL . 'assets/css/homenagem-pais.css', array(), '1.0');
    wp_enqueue_style('homenagem-pais-style');

    wp_register_script('homenagem-pais-frontend', HP_PLUGIN_URL . 'assets/js/homenagem-pais.js', array(), '1.0', true);
    wp_localize_script('homenagem-pais-frontend', 'HomenagemPais', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('hp_homenagem_submit'),
        'nonce_like' => wp_create_nonce('hp_like')
    ));
    wp_enqueue_script('homenagem-pais-frontend');
}

// AJAX endpoint (namespaced) - frontend submissions
add_action('wp_ajax_hp_submit_homenagem', 'hp_handle_submit_homenagem');
add_action('wp_ajax_nopriv_hp_submit_homenagem', 'hp_handle_submit_homenagem');
function hp_handle_submit_homenagem()
{
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'hp_homenagem_submit')) {
        wp_send_json_error(array('message' => 'Autorização inválida'), 403);
    }

    $name = sanitize_text_field($_POST['h_name'] ?? '');
    $unit = sanitize_text_field($_POST['h_unit'] ?? '');
    $message = sanitize_textarea_field($_POST['h_message'] ?? '');

    if (empty($name) || empty($message)) {
        wp_send_json_error(array('message' => 'Nome e mensagem são obrigatórios'), 400);
    }

    $postarr = array(
        'post_title' => mb_substr($name, 0, 100),
        'post_content' => $message,
        'post_status' => 'pending',
        'post_type' => 'homenagem'
    );

    $post_id = wp_insert_post($postarr);

    if (is_wp_error($post_id) || !$post_id) {
        wp_send_json_error(array('message' => 'Erro ao criar homenagem'), 500);
    }

    update_post_meta($post_id, 'homenagem_name', $name);
    update_post_meta($post_id, 'homenagem_unit', $unit);
    update_post_meta($post_id, 'homenagem_message', $message);

    if (!empty($_FILES['h_media']) && !empty($_FILES['h_media']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attach_id = media_handle_upload('h_media', $post_id);
        if (is_wp_error($attach_id)) {
            update_post_meta($post_id, 'homenagem_media_error', $attach_id->get_error_message());
        } else {
            set_post_thumbnail($post_id, $attach_id);
            update_post_meta($post_id, 'homenagem_media_id', $attach_id);
        }
    }

    wp_send_json_success(array('message' => 'Homenagem enviada com sucesso e aguardando aprovação'));
}

// AJAX: obter dados completos da homenagem
add_action('wp_ajax_hp_get_homenagem', 'hp_get_homenagem');
add_action('wp_ajax_nopriv_hp_get_homenagem', 'hp_get_homenagem');
function hp_get_homenagem()
{
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        wp_send_json_error(array('message' => 'ID inválido'), 400);
    }

    $post = get_post($id);
    if (!$post) {
        wp_send_json_error(array('message' => 'Homenagem não encontrada'), 404);
    }

    $name = get_post_meta($id, 'homenagem_name', true) ?: get_the_title($id);
    $unit = get_post_meta($id, 'homenagem_unit', true);
    $message = hp_get_homenagem_message($id, $post);
    $preview = hp_get_homenagem_preview_data($id);
    $media_url = $preview['url'];
    $media_type = $preview['type'];
    $likes = intval(get_post_meta($id, 'homenagem_likes', true));

    wp_send_json_success(array(
        'id' => $id,
        'title' => get_the_title($id),
        'name' => $name,
        'unit' => $unit,
        'message' => wp_kses_post($message),
        'media_url' => $media_url,
        'media_type' => $media_type,
        'likes' => $likes,
    ));
}

// AJAX: carregar mais homenagens
add_action('wp_ajax_hp_load_more_homenagens', 'hp_load_more_homenagens');
add_action('wp_ajax_nopriv_hp_load_more_homenagens', 'hp_load_more_homenagens');
function hp_load_more_homenagens()
{
    $page = max(1, intval($_POST['page'] ?? 1));

    $query = new WP_Query([
        'post_type' => 'homenagem',
        'post_status' => 'publish',
        'posts_per_page' => HP_HOMENAGEM_PER_PAGE,
        'paged' => $page,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    if (!$query->have_posts()) {
        wp_send_json_error(array('message' => 'Sem mais homenagens'), 404);
    }

    $html = hp_render_homenagem_cards($query->posts);
    $has_more = $query->max_num_pages > $page;

    wp_send_json_success(array(
        'html' => $html,
        'has_more' => $has_more,
        'next_page' => $page + 1,
    ));
}

function hp_get_homenagem_preview_data($pid)
{
    $thumb = get_the_post_thumbnail_url($pid, 'thumbnail');
    $media_id = get_post_meta($pid, 'homenagem_media_id', true);
    $media_type = '';
    $media_url = '';

    if ($media_id) {
        $mime = get_post_mime_type($media_id);
        if ($mime && strpos($mime, 'video/') === 0) {
            $media_type = 'video';
            $media_url = wp_get_attachment_url($media_id);
        } elseif ($mime && strpos($mime, 'image/') === 0) {
            $media_type = 'image';
            $media_url = wp_get_attachment_url($media_id);
        }
    }

    if ($media_type === 'video') {
        return array('type' => 'video', 'url' => $media_url);
    }

    if ($media_type === 'image' && $media_url) {
        return array('type' => 'image', 'url' => $media_url);
    }

    if ($thumb) {
        return array('type' => 'image', 'url' => $thumb);
    }

    return array('type' => 'default', 'url' => get_template_directory_uri() . '/assets/images/default-avatar.png');
}

function hp_get_homenagem_message($pid, $post = null)
{
    $message = get_post_meta($pid, 'homenagem_message', true);

    if (!empty($message)) {
        $post_content = get_post_field('post_content', $pid, 'raw');
        if (!empty($post_content) && $post_content !== $message) {
            return $post_content;
        }

        return $message;
    }

    if ($post instanceof WP_Post) {
        $message = $post->post_content;
    }

    if (empty($message)) {
        $message = get_post_field('post_content', $pid, 'raw');
    }

    if (empty($message)) {
        $message = get_post_field('post_excerpt', $pid, 'raw');
    }

    return $message ?: '';
}

function hp_render_homenagem_card($post)
{
    $pid = $post->ID;
    $name = get_post_meta($pid, 'homenagem_name', true) ?: get_the_title($pid);
    $unit = get_post_meta($pid, 'homenagem_unit', true);
    $message = hp_get_homenagem_message($pid, $post);
    $short = mb_substr(strip_tags($message), 0, 70);
    $preview = hp_get_homenagem_preview_data($pid);
    $likes = intval(get_post_meta($pid, 'homenagem_likes', true));

    ob_start();
?>
    <div class="col-md-4">
        <div class="card lp-story-card h-100 shadow-sm border-0 rounded-4 p-3 btn-open" data-id="<?php echo esc_attr($pid); ?>">
            <div class="d-flex align-items-center gap-3 mb-3">
                <?php if ($preview['type'] === 'video') : ?>
                    <div class="hp-card-preview hp-card-preview--video" aria-label="Vídeo">
                        <span class="dashicons dashicons-format-video"></span>
                    </div>
                <?php else : ?>
                    <img src="<?php echo esc_url($preview['url']); ?>" class="avatar rounded-circle" alt="<?php echo esc_attr($name); ?>">
                <?php endif; ?>
                <div>
                    <h5 class="mb-1"><?php echo esc_html($name); ?></h5>
                    <p class="text-muted small mb-0"><?php echo esc_html($unit); ?></p>
                </div>
            </div>
            <p class="card-text text-secondary mb-0 lp-dia-dos-pais-text">“<?php echo esc_html($short); ?>...”</p>
            <div class="d-flex justify-content-between align-items-center mt-auto">
                <button class="btn btn-sm btn-outline-primary btn-like test" type="button" data-id="<?php echo esc_attr($pid); ?>" data-likes="<?php echo esc_attr($likes); ?>"><svg height="15" viewBox="0 0 34 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M28.9181 4.24906C28.2054 3.53604 27.3592 2.97043 26.4279 2.58452C25.4965 2.19862 24.4983 2 23.4902 2C22.4821 2 21.4838 2.19862 20.5525 2.58452C19.6211 2.97043 18.7749 3.53604 18.0623 4.24906L16.5832 5.72813L15.1041 4.24906C13.6646 2.80949 11.7121 2.00075 9.67622 2.00075C7.64036 2.00075 5.68788 2.80949 4.24831 4.24906C2.80874 5.68863 2 7.64111 2 9.67697C2 11.7128 2.80874 13.6653 4.24831 15.1049L16.5832 27.4398L28.9181 15.1049C29.6311 14.3922 30.1967 13.546 30.5826 12.6147C30.9685 11.6833 31.1671 10.6851 31.1671 9.67697C31.1671 8.66884 30.9685 7.6706 30.5826 6.73926C30.1967 5.80792 29.6311 4.96174 28.9181 4.24906Z" stroke="#0094c6" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                    </svg> <?php echo esc_html($likes); ?></button>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}

function hp_render_homenagem_cards($posts)
{
    $html = '';
    foreach ($posts as $post) {
        $html .= hp_render_homenagem_card($post);
    }
    return $html;
}

// AJAX: curtir homenagem
add_action('wp_ajax_hp_like_homenagem', 'hp_like_homenagem');
add_action('wp_ajax_nopriv_hp_like_homenagem', 'hp_like_homenagem');
function hp_like_homenagem()
{
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'hp_like')) {
        wp_send_json_error(array('message' => 'Autorização inválida'), 403);
    }

    $id = intval($_POST['id'] ?? 0);
    if (!$id) wp_send_json_error(array('message' => 'ID inválido'), 400);

    $type = sanitize_text_field($_POST['type'] ?? 'like');
    $likes = intval(get_post_meta($id, 'homenagem_likes', true));

    if ($type === 'unlike') {
        $likes = max(0, $likes - 1);
        update_post_meta($id, 'homenagem_likes', $likes);
        wp_send_json_success(array('likes' => $likes, 'liked' => false));
    }

    $likes++;
    update_post_meta($id, 'homenagem_likes', $likes);

    wp_send_json_success(array('likes' => $likes, 'liked' => true));
}

// Admin: meta box para marcar destaque
add_action('add_meta_boxes', 'hp_add_meta_boxes');
function hp_add_meta_boxes()
{
    add_meta_box('hp_homenagem_meta', 'Homenagem - Opções', 'hp_homenagem_meta_box_cb', 'homenagem', 'side', 'default');
}

function hp_homenagem_meta_box_cb($post)
{
    $featured = get_post_meta($post->ID, 'homenagem_featured', true) ? '1' : '';
    wp_nonce_field('hp_homenagem_meta', 'hp_homenagem_meta_nonce');
    echo '<p><label><input type="checkbox" name="homenagem_featured" value="1" ' . checked($featured, '1', false) . '> Destacar nesta campanha</label></p>';
}

add_action('save_post', 'hp_save_homenagem_meta');
function hp_save_homenagem_meta($post_id)
{
    if (!isset($_POST['hp_homenagem_meta_nonce']) || !wp_verify_nonce($_POST['hp_homenagem_meta_nonce'], 'hp_homenagem_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    if (isset($_POST['homenagem_featured'])) {
        update_post_meta($post_id, 'homenagem_featured', '1');
    } else {
        delete_post_meta($post_id, 'homenagem_featured');
    }
}

add_action('save_post_homenagem', 'hp_sync_homenagem_message_meta', 20);
function hp_sync_homenagem_message_meta($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'homenagem') return;

    $content = wp_kses_post($post->post_content);
    update_post_meta($post_id, 'homenagem_message', $content);
}

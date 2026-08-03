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

add_action('init', 'hp_register_homenagem_cpt');
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
        'menu_name' => __('Homenagem', 'homenagem-pais'),
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
        'menu_icon' => 'dashicons-heart',
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
    $message = get_post_meta($id, 'homenagem_message', true) ?: $post->post_content;
    $media_id = get_post_meta($id, 'homenagem_media_id', true);
    $media_url = $media_id ? wp_get_attachment_url($media_id) : (get_the_post_thumbnail_url($id) ?: '');
    $likes = intval(get_post_meta($id, 'homenagem_likes', true));

    wp_send_json_success(array(
        'id' => $id,
        'title' => get_the_title($id),
        'name' => $name,
        'unit' => $unit,
        'message' => wp_kses_post($message),
        'media_url' => $media_url,
        'likes' => $likes,
    ));
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

    $likes = intval(get_post_meta($id, 'homenagem_likes', true));
    $likes++;
    update_post_meta($id, 'homenagem_likes', $likes);

    wp_send_json_success(array('likes' => $likes));
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

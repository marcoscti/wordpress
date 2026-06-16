<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    $namespace = 'feed-social/v1';

    // Listar posts (Scroll Infinito)
    register_rest_route($namespace, '/posts', [
        'methods' => 'GET',
        'callback' => 'fs_rest_get_posts',
        'permission_callback' => '__return_true'
    ]);

    // Curtir
    register_rest_route($namespace, '/like', [
        'methods' => 'POST',
        'callback' => 'fs_rest_handle_like',
        'permission_callback' => '__return_true'
    ]);

    // Comentar
    register_rest_route($namespace, '/comment', [
        'methods' => 'POST',
        'callback' => 'fs_rest_handle_comment',
        'permission_callback' => '__return_true'
    ]);

    // Listar comentários de um post
    register_rest_route($namespace, '/comments', [
        'methods' => 'GET',
        'callback' => 'fs_rest_get_comments',
        'permission_callback' => '__return_true',
        'args' => [
            'post_id' => [
                'required' => true,
                'validate_callback' => function ($value) {
                    return is_numeric($value) && (int) $value > 0;
                },
            ],
        ],
    ]);
});

function fs_rest_get_posts($request) {
    $per_page = max(1, min(50, absint($request->get_param('per_page') ?: 10)));

    if ($request->get_param('offset') !== null) {
        $offset = max(0, absint($request->get_param('offset')));
    } else {
        $page = max(1, absint($request->get_param('page') ?: 1));
        $offset = ($page - 1) * $per_page;
    }

    $query = new WP_Query([
        'post_type' => 'feed-social',
        'posts_per_page' => $per_page,
        'offset' => $offset,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    $posts = [];
    foreach ($query->posts as $post) {
        $media_ids_string = get_post_meta($post->ID, '_fs_media_ids', true);
        $media_ids_array = array_filter(explode(',', $media_ids_string));
        $media_gallery = [];

        foreach ($media_ids_array as $media_id) {
            $media_url = wp_get_attachment_url($media_id);
            $mime_type = get_post_mime_type($media_id);
            if ($media_url) {
                $media_gallery[] = [
                    'id' => $media_id,
                    'url' => $media_url,
                    'type' => $mime_type,
                ];
            }
        }

        $posts[] = [
            'id' => $post->ID,
            'title' => get_the_title($post->ID),
            'content' => apply_filters('the_content', $post->post_content),
            'thumbnail' => get_the_post_thumbnail_url($post->ID, 'large'),
            'media_gallery' => $media_gallery, // Adiciona a galeria de mídias aqui
            'likes' => fs_get_likes_count($post->ID),
            'comments' => fs_get_comments_count($post->ID)
        ];
    }
    
    $loaded = count($posts);
    $total = (int) $query->found_posts;

    return rest_ensure_response([
        'posts' => $posts,
        'total' => $total,
        'offset' => $offset,
        'has_more' => ($offset + $loaded) < $total,
    ]);
}

function fs_rest_handle_like($request) {
    global $wpdb;
    $params = $request->get_json_params() ?: [];
    $post_id = absint($params['post_id'] ?? 0);
    $email = sanitize_email($params['email'] ?? '');
    $table = $wpdb->prefix . 'feed_social_likes';

    if (empty($post_id) || get_post_type($post_id) !== 'feed-social' || !is_email($email)) {
        return new WP_Error('invalid_data', 'Dados inválidos', ['status' => 400]);
    }

    // Verifica se já existe a curtida
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE post_id = %d AND email = %s", $post_id, $email));

    if ($exists) {
        $wpdb->delete($table, ['id' => $exists], ['%d']);
        $action = 'unliked';
    } else {
        $wpdb->insert($table, [
            'post_id' => $post_id,
            'email' => $email,
            'created_at' => current_time('mysql')
        ], ['%d', '%s', '%s']);
        $action = 'liked';
    }

    return rest_ensure_response([
        'success' => true,
        'action' => $action,
        'new_count' => fs_get_likes_count($post_id)
    ]);
}

function fs_rest_handle_comment($request) {
    global $wpdb;
    $params = $request->get_json_params() ?: [];
    $post_id = absint($params['post_id'] ?? 0);
    $name = sanitize_text_field($params['name'] ?? '');
    $email = sanitize_email($params['email'] ?? '');
    $comment = sanitize_textarea_field($params['comment'] ?? '');
    $table = $wpdb->prefix . 'feed_social_comments';

    if (empty($post_id) || get_post_type($post_id) !== 'feed-social' || empty($name) || !is_email($email) || empty($comment)) {
        return new WP_Error('missing_fields', 'Campos obrigatórios faltando', ['status' => 400]);
    }

    $inserted = $wpdb->insert($table, [
        'post_id' => $post_id,
        'name' => $name,
        'email' => $email,
        'comment' => $comment,
        'created_at' => current_time('mysql'),
    ], ['%d', '%s', '%s', '%s', '%s']);

    if ($inserted === false) {
        return new WP_Error('db_error', 'Não foi possível salvar o comentário', ['status' => 500]);
    }

    return rest_ensure_response([
        'success' => true,
        'new_count' => fs_get_comments_count($post_id),
        'comment' => [
            'id' => (int) $wpdb->insert_id,
            'name' => $name,
            'comment' => $comment,
            'created_at' => current_time('mysql'),
        ],
    ]);
}

function fs_rest_get_comments($request) {
    global $wpdb;
    $post_id = absint($request->get_param('post_id'));
    $table = $wpdb->prefix . 'feed_social_comments';

    if (get_post_type($post_id) !== 'feed-social') {
        return new WP_Error('invalid_post', 'Post inválido', ['status' => 400]);
    }

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name, comment, created_at FROM $table WHERE post_id = %d ORDER BY created_at ASC",
        $post_id
    ));

    return rest_ensure_response([
        'comments' => array_map(function ($row) {
            return [
                'id' => (int) $row->id,
                'name' => $row->name,
                'comment' => $row->comment,
                'created_at' => $row->created_at,
            ];
        }, $rows ?: []),
    ]);
}

function fs_get_likes_count($post_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'feed_social_likes';
    return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE post_id = %d", $post_id));
}

function fs_get_comments_count($post_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'feed_social_comments';
    return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE post_id = %d", $post_id));
}
<?php
if (!defined('ABSPATH')) exit;

function dj_card_excerpt($content, $limit = 150) {
    return wp_html_excerpt(wp_strip_all_tags($content), $limit, '…');
}

function dj_rest_register_routes() {
    register_rest_route('documentos-juridicos/v1', '/documentos', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'dj_api_documents',
        'permission_callback' => '__return_true',
        'args' => [
            'search' => ['sanitize_callback' => 'sanitize_text_field'],
            'page' => ['default' => 1, 'sanitize_callback' => 'absint'],
            'per_page' => ['default' => 12, 'sanitize_callback' => 'absint'],
            'categoria' => ['sanitize_callback' => 'sanitize_text_field'],
            'assunto' => ['sanitize_callback' => 'sanitize_text_field'],
        ],
    ]);
}
add_action('rest_api_init', 'dj_rest_register_routes');

function dj_api_documents(WP_REST_Request $request) {
    $page = max(1, (int) $request->get_param('page'));
    $per_page = min(48, max(1, (int) $request->get_param('per_page')));

    $args = [
        'post_type' => 'documento_juridico',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $page,
        's' => sanitize_text_field($request->get_param('search')),
    ];

    $tax_query = ['relation' => 'AND'];
    foreach (['categoria' => 'dj_categoria', 'assunto' => 'dj_assunto'] as $param => $taxonomy) {
        $value = $request->get_param($param);
        if ($value) {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => array_map('sanitize_title', explode(',', $value)),
            ];
        }
    }
    if (count($tax_query) > 1) $args['tax_query'] = $tax_query;

    $query = new WP_Query($args);
    $items = [];

    foreach ($query->posts as $post) {
        $pdf = get_post_meta($post->ID, '_dj_pdf_url', true);
        $views = (int) get_post_meta($post->ID, '_dj_views', true);

        $items[] = [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'excerpt' => dj_card_excerpt($post->post_content),
            'url' => get_permalink($post),
            'pdf_url' => esc_url_raw($pdf),
            'pdf_download_url' => $pdf ? esc_url_raw(dj_pdf_download_url($post->ID)) : '',
            'views' => $views,
            'categories' => wp_get_post_terms($post->ID, 'dj_categoria', ['fields' => 'names']),
            'subjects' => wp_get_post_terms($post->ID, 'dj_assunto', ['fields' => 'names']),
        ];
    }

    return rest_ensure_response([
        'data' => $items,
        'current_page' => $page,
        'per_page' => $per_page,
        'total' => (int) $query->found_posts,
        'last_page' => (int) $query->max_num_pages,
    ]);
}

function dj_api_most_accessed() {
    $query = new WP_Query([
        'post_type' => 'documento_juridico',
        'post_status' => 'publish',
        'posts_per_page' => 6,
        'meta_key' => '_dj_views',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
    ]);

    $items = [];
    foreach ($query->posts as $post) {
        $items[] = [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'excerpt' => dj_card_excerpt($post->post_content),
            'url' => get_permalink($post),
            'views' => (int) get_post_meta($post->ID, '_dj_views', true),
        ];
    }
    return $items;
}

function dj_register_views_route() {
    register_rest_route('documentos-juridicos/v1', '/documento/(?P<id>\d+)/view', [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => function ($request) {
            $id = absint($request['id']);
            if (get_post_type($id) !== 'documento_juridico') {
                return new WP_Error('not_found', 'Documento não encontrado', ['status' => 404]);
            }
            $views = (int) get_post_meta($id, '_dj_views', true);
            update_post_meta($id, '_dj_views', $views + 1);
            return ['success' => true, 'views' => $views + 1];
        },
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'dj_register_views_route');

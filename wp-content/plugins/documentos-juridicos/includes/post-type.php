<?php
if (!defined('ABSPATH')) exit;

function dj_register_post_type() {
    register_post_type('documento_juridico', [
        'labels' => [
            'name' => 'Documentos Jurídicos',
            'singular_name' => 'Documento Jurídico',
            'add_new' => 'Adicionar documento',
            'add_new_item' => 'Adicionar documento jurídico',
            'edit_item' => 'Editar documento jurídico',
            'new_item' => 'Novo documento jurídico',
            'view_item' => 'Ver documento',
            'search_items' => 'Pesquisar documentos',
            'not_found' => 'Nenhum documento encontrado',
            'menu_name' => 'Documentos Jurídicos',
        ],
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-media-document',
        'supports' => ['title', 'editor', 'thumbnail'],
        'has_archive' => true,
        'rewrite' => ['slug' => 'documentos-juridicos'],
        'show_in_nav_menus' => true,
    ]);
}
add_action('init', 'dj_register_post_type');

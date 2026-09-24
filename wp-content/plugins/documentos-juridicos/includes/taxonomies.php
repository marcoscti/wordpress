<?php
if (!defined('ABSPATH')) exit;

function dj_register_taxonomies() {
    register_taxonomy('dj_categoria', 'documento_juridico', [
        'labels' => [
            'name' => 'Categorias',
            'singular_name' => 'Categoria',
            'search_items' => 'Pesquisar categorias',
            'all_items' => 'Todas as categorias',
            'edit_item' => 'Editar categoria',
            'add_new_item' => 'Adicionar categoria',
            'menu_name' => 'Categorias',
        ],
        'public' => true,
        'show_in_rest' => true,
        'hierarchical' => true,
        'rewrite' => ['slug' => 'categoria-documento'],
    ]);

    register_taxonomy('dj_assunto', 'documento_juridico', [
        'labels' => [
            'name' => 'Assuntos',
            'singular_name' => 'Assunto',
            'search_items' => 'Pesquisar assuntos',
            'all_items' => 'Todos os assuntos',
            'edit_item' => 'Editar assunto',
            'add_new_item' => 'Adicionar assunto',
            'menu_name' => 'Assuntos',
        ],
        'public' => true,
        'show_in_rest' => true,
        'hierarchical' => false,
        'rewrite' => ['slug' => 'assunto-documento'],
    ]);
}
add_action('init', 'dj_register_taxonomies');

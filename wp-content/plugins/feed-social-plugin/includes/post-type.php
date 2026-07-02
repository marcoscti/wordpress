<?php
if (!defined('ABSPATH')) exit;

function fs_register_post_type() {
    $labels = array(
        'name'                  => _x('Feed Social', 'Post type general name', 'feed-social'),
        'singular_name'         => _x('Post do Feed', 'Post type singular name', 'feed-social'), // phpcs:ignore WordPress.WP.I18n.MissingSingularPlaceholder, WordPress.WP.I18n.MismatchedPlaceholders
        'menu_name'             => _x('Feed Social', 'Admin Menu text', 'feed-social'),
        'name_admin_bar'        => _x('Post do Feed', 'Add New on Toolbar', 'feed-social'),
        'add_new'               => __('Adicionar Novo', 'feed-social'),
        'add_new_item'          => __('Adicionar Novo Post', 'feed-social'),
        'new_item'              => __('Novo Post', 'feed-social'),
        'edit_item'             => __('Editar Post', 'feed-social'),
        'view_item'             => __('Ver Post', 'feed-social'),
        'all_items'             => __('Todos os Posts', 'feed-social'),
        'search_items'          => __('Pesquisar Posts', 'feed-social'),
        'parent_item_colon'     => __('Post Pai:', 'feed-social'),
        'not_found'             => __('Nenhum post encontrado.', 'feed-social'),
        'not_found_in_trash'    => __('Nenhum post encontrado na lixeira.', 'feed-social'),
        'featured_image'        => _x('Imagem de Destaque', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'feed-social'),
        'set_featured_image'    => _x('Definir imagem de destaque', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'feed-social'),
        'remove_featured_image' => _x('Remover imagem de destaque', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'feed-social'),
        'use_featured_image'    => _x('Usar como imagem de destaque', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'feed-social'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'social-feed'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-share',
        'supports'           => array('title', 'editor', 'thumbnail', 'author', 'revisions'),
        'show_in_rest'       => true,
    );
    register_post_type('feed-social', $args);

    // Register Social Story CPT
    $story_labels = array(
        'name'                  => _x('Stories', 'Post type general name', 'feed-social'),
        'singular_name'         => _x('Story', 'Post type singular name', 'feed-social'), // phpcs:ignore WordPress.WP.I18n.MissingSingularPlaceholder, WordPress.WP.I18n.MismatchedPlaceholders
        'menu_name'             => _x('Stories', 'Admin Menu text', 'feed-social'),
        'name_admin_bar'        => _x('Story', 'Add New on Toolbar', 'feed-social'),
        'add_new'               => __('Adicionar Novo Story', 'feed-social'),
        'add_new_item'          => __('Adicionar Novo Story', 'feed-social'),
        'new_item'              => __('Novo Story', 'feed-social'),
        'edit_item'             => __('Editar Story', 'feed-social'),
        'view_item'             => __('Ver Story', 'feed-social'),
        'all_items'             => __('Todos os Stories', 'feed-social'),
        'search_items'          => __('Pesquisar Stories', 'feed-social'),
        'parent_item_colon'     => __('Story Pai:', 'feed-social'),
        'not_found'             => __('Nenhum story encontrado.', 'feed-social'),
        'not_found_in_trash'    => __('Nenhum story encontrado na lixeira.', 'feed-social'),
        'featured_image'        => _x('Imagem do Story', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'feed-social'),
        'set_featured_image'    => _x('Definir imagem do story', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'feed-social'),
        'remove_featured_image' => _x('Remover imagem do story', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'feed-social'),
        'use_featured_image'    => _x('Usar como imagem do story', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'feed-social'),
    );

    $story_args = array(
        'labels'             => $story_labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => 'edit.php?post_type=feed-social',
        'query_var'          => true,
        'rewrite'            => array('slug' => 'story'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'thumbnail', 'author'),
        'show_in_rest'       => true,
    );
    register_post_type('social_story', $story_args);
}
add_action('init', 'fs_register_post_type');

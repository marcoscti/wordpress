<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Classe para registrar o Custom Post Type 'figurinhas-copa-2026'.
 */
class Album_Copa_2026_CPT {
    public function __construct() {
        add_action( 'init', array( $this, 'register_figurinhas_cpt' ) );
    }

    /**
     * Registra o Custom Post Type 'figurinhas-copa-2026'.
     *
     * @return void
     */
    public function register_figurinhas_cpt() {
        $labels = array(
            'name'                  => _x( 'Figurinhas', 'Post Type General Name', 'album-copa-2026' ),
            'singular_name'         => _x( 'Figurinha', 'Post Type Singular Name', 'album-copa-2026' ),
            'menu_name'             => __( 'Album Copa 2026', 'album-copa-2026' ),
            'name_admin_bar'        => __( 'Figurinha', 'album-copa-2026' ),
            'archives'              => __( 'Arquivos de Figurinhas', 'album-copa-2026' ),
            'attributes'            => __( 'Atributos da Figurinha', 'album-copa-2026' ),
            'parent_item_colon'     => __( 'Figurinha Pai:', 'album-copa-2026' ),
            'all_items'             => __( 'Todas as Figurinhas', 'album-copa-2026' ),
            'add_new_item'          => __( 'Adicionar Nova Figurinha', 'album-copa-2026' ),
            'add_new'               => __( 'Adicionar Nova', 'album-copa-2026' ),
            'new_item'              => __( 'Nova Figurinha', 'album-copa-2026' ),
            'edit_item'             => __( 'Editar Figurinha', 'album-copa-2026' ),
            'update_item'           => __( 'Atualizar Figurinha', 'album-copa-2026' ),
            'view_item'             => __( 'Ver Figurinha', 'album-copa-2026' ),
            'view_items'            => __( 'Ver Figurinhas', 'album-copa-2026' ),
            'search_items'          => __( 'Buscar Figurinha', 'album-copa-2026' ),
            'not_found'             => __( 'Nenhuma Figurinha Encontrada', 'album-copa-2026' ),
            'not_found_in_trash'    => __( 'Nenhuma Figurinha na Lixeira', 'album-copa-2026' ),
            'featured_image'        => __( 'Imagem da Figurinha', 'album-copa-2026' ),
            'set_featured_image'    => __( 'Definir imagem da figurinha', 'album-copa-2026' ),
            'remove_featured_image' => __( 'Remover imagem da figurinha', 'album-copa-2026' ),
            'use_featured_image'    => __( 'Usar como imagem da figurinha', 'album-copa-2026' ),
            'insert_into_item'      => __( 'Inserir na figurinha', 'album-copa-2026' ),
            'uploaded_to_this_item' => __( 'Enviado para esta figurinha', 'album-copa-2026' ),
            'items_list'            => __( 'Lista de Figurinhas', 'album-copa-2026' ),
            'items_list_navigation' => __( 'Navegação da Lista de Figurinhas', 'album-copa-2026' ),
            'filter_items_list'     => __( 'Filtrar lista de figurinhas', 'album-copa-2026' ),
        );
        $args = array(
            'label'                 => __( 'Figurinha', 'album-copa-2026' ),
            'description'           => __( 'Figurinhas para o álbum da Copa 2026', 'album-copa-2026' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-format-image',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );
        register_post_type( 'figurinhas-copa-2026', $args );
    }
}
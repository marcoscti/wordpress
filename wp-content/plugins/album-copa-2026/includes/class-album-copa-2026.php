<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once ALBUM_COPA_2026_PATH . 'includes/admin.php';
require_once ALBUM_COPA_2026_PATH . 'includes/frontend.php';

class Album_Copa_2026
{
    private static $instance = null;
    private $admin;
    private $frontend;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->admin = new Album_Copa_2026_Admin();
        $this->frontend = new Album_Copa_2026_Frontend();

        add_action('init', array($this, 'register_cpt'));
        add_action('init', array($this->frontend, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this->frontend, 'register_assets'));
        add_action('wp_ajax_album_copa_2026_like', array($this->frontend, 'ajax_like'));
        add_action('wp_ajax_nopriv_album_copa_2026_like', array($this->frontend, 'ajax_like'));
        add_action('wp_ajax_album_copa_2026_comment', array($this->frontend, 'ajax_comment'));
        add_action('wp_ajax_nopriv_album_copa_2026_comment', array($this->frontend, 'ajax_comment'));
        wp_enqueue_style(
            'album-copa-2026-fonts',
            'https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap',
            [],
            null
        );
    }

    public static function activate()
    {
        self::instance()->register_cpt();
        flush_rewrite_rules();
    }

    public static function deactivate()
    {
        flush_rewrite_rules();
    }

    public function register_cpt()
    {
        $labels = array(
            'name'               => __('Figurinhas', 'album-copa-2026'),
            'singular_name'      => __('Figurinha', 'album-copa-2026'),
            'menu_name'          => __('Album Copa 2026', 'album-copa-2026'),
            'name_admin_bar'     => __('Album Copa 2026', 'album-copa-2026'),
            'add_new'            => __('Adicionar Nova', 'album-copa-2026'),
            'add_new_item'       => __('Nova Figurinha', 'album-copa-2026'),
            'new_item'           => __('Nova figurinha', 'album-copa-2026'),
            'edit_item'          => __('Editar figurinha', 'album-copa-2026'),
            'view_item'          => __('Ver Figurinha', 'album-copa-2026'),
            'all_items'          => __('Figurinhas', 'album-copa-2026'),
            'search_items'       => __('Buscar Figurinhas', 'album-copa-2026'),
            'not_found'          => __('Nenhuma figurinha encontrada.', 'album-copa-2026'),
            'not_found_in_trash' => __('Nenhuma figurinha na lixeira.', 'album-copa-2026'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'has_archive'        => false,
            'show_in_menu'       => true,
            'taxonomies'         => array(),
            'menu_icon'          => 'dashicons-heart',
            'capability_type'    => 'post',
            'supports'           => array('title', 'editor', 'thumbnail', 'comments'),
            'show_in_rest'       => false,
        );

        register_post_type('figurinhas-copa-2026', $args);
    }
}

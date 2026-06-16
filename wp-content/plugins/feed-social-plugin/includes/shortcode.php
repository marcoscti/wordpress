<?php
if (!defined('ABSPATH')) exit;

add_shortcode('feed_social', 'fs_render_feed_shortcode');

add_action('wp_enqueue_scripts', 'fs_enqueue_feed_scripts');

function fs_enqueue_feed_scripts() {
    // Enqueue jQuery if not already enqueued by WordPress (it usually is)
    wp_enqueue_script('jquery');

    // Register and enqueue Swiper.js (will be used for carousels)
    wp_register_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.0.0', true);
    wp_register_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11.0.0');

    // Enqueue custom feed script
    wp_enqueue_script('feed-social-js', FS_PLUGIN_URL . 'assets/js/feed-social.js', ['jquery'], '1.0.0', true);
    wp_enqueue_style('feed-social-css', FS_PLUGIN_URL . 'assets/css/feed-social.css', [], '1.0.0');

    // Localize script to pass data to JavaScript
    wp_localize_script('feed-social-js', 'fs_feed_data', [
        'ajax_url' => admin_url('admin-ajax.php'), // Not strictly needed for REST API, but good practice for other AJAX
        'rest_url' => get_rest_url(null, 'feed-social/v1/posts'),
        'rest_nonce' => wp_create_nonce('wp_rest'), // Nonce for REST API authentication
        'initial_posts_per_page' => get_option('fs_initial_posts_count', 10), // From settings, default 10
        'posts_per_load' => get_option('fs_posts_per_load', 10), // From settings, default 10
        'loading_text' => __('Carregando mais posts...', 'feed-social'),
        'no_more_posts_text' => __('Não há mais posts para carregar.', 'feed-social'),
    ]);
}

function fs_render_feed_shortcode($atts) {
    ob_start();
    ?>
    <div id="feed-social-app">
        <div id="fs-posts-container"></div>
        <div id="fs-loading-indicator" style="display: none;">
            <p class="fs-loading-text"></p>
        </div>
        <div id="fs-no-more-posts" style="display: none;">
            <p class="fs-no-more-posts-text"></p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
<?php
if (!defined('ABSPATH')) exit;

add_shortcode('feed_social', 'fs_render_feed_shortcode');

add_filter('the_posts', 'fs_detect_feed_shortcode_in_posts');
add_action('wp_enqueue_scripts', 'fs_enqueue_feed_scripts');

function fs_detect_feed_shortcode_in_posts($posts) {
    global $fs_feed_shortcode_used;

    if (!empty($fs_feed_shortcode_used) || empty($posts)) {
        return $posts;
    }

    foreach ($posts as $post) {
        if (!empty($post->post_content) && has_shortcode($post->post_content, 'feed_social')) {
            $fs_feed_shortcode_used = true;
            break;
        }
    }

    return $posts;
}

function fs_get_feed_page_url() {
    $cached = get_transient('fs_feed_page_url');
    if ($cached) {
        return $cached;
    }

    $url = home_url('/');
    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]);

    foreach ($pages as $page_id) {
        $content = get_post_field('post_content', $page_id);
        if ($content && has_shortcode($content, 'feed_social')) {
            $url = get_permalink($page_id);
            break;
        }
    }

    set_transient('fs_feed_page_url', $url, DAY_IN_SECONDS);
    return $url;
}

function fs_enqueue_feed_scripts() {
    global $fs_feed_shortcode_used;

    wp_enqueue_script('jquery');

    wp_register_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.0.0', true);
    wp_register_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11.0.0');

    if (!empty($fs_feed_shortcode_used)) {
        wp_enqueue_script('swiper-js');
        wp_enqueue_style('swiper-css');
    }

    wp_enqueue_script('feed-social-js', FS_PLUGIN_URL . 'assets/js/feed-social.js', ['jquery'], '1.3.0', true);
    wp_enqueue_style('feed-social-css', FS_PLUGIN_URL . 'assets/css/feed-social.css', [], '1.3.0');

    wp_localize_script('feed-social-js', 'fs_feed_data', [
        'rest_url' => get_rest_url(null, 'feed-social/v1/posts'),
        'like_url' => get_rest_url(null, 'feed-social/v1/like'),
        'comment_url' => get_rest_url(null, 'feed-social/v1/comment'),
        'comments_url' => get_rest_url(null, 'feed-social/v1/comments'),
        'sse_url' => fs_get_sse_url(),
        'feed_page_url' => fs_get_feed_page_url(),
        'rest_nonce' => wp_create_nonce('wp_rest'),
        'initial_posts' => 5,
        'posts_per_load' => 2,
        'loading_text' => __('Carregando mais posts...', 'feed-social'),
        'no_more_posts_text' => __('Não há mais posts para carregar.', 'feed-social'),
        'like_prompt' => __('Informe seu e-mail para curtir:', 'feed-social'),
        'comment_name_prompt' => __('Seu nome:', 'feed-social'),
        'comment_email_prompt' => __('Seu e-mail:', 'feed-social'),
        'notification_title' => __('Novo post no Feed Social', 'feed-social'),
        'notification_body' => __('Acabou de publicar um novo post.', 'feed-social'),
        'has_feed' => !empty($fs_feed_shortcode_used),
    ]);
}

function fs_render_feed_shortcode($atts) {
    global $fs_feed_shortcode_used;
    $fs_feed_shortcode_used = true;

    ob_start();
    ?>
    <div id="feed-social-app">
        <div id="fs-posts-container"></div>
        <div id="fs-feed-footer">
            <div id="fs-loading-indicator" class="fs-loading-indicator" hidden>
                <span class="fs-spinner" aria-hidden="true"></span>
                <p class="fs-loading-text"></p>
            </div>
            <div id="fs-scroll-sentinel" class="fs-scroll-sentinel" aria-hidden="true"></div>
            <div id="fs-no-more-posts" class="fs-no-more-posts" hidden>
                <p class="fs-no-more-posts-text"></p>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
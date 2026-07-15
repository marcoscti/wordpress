<?php
if (!defined('ABSPATH')) exit;

add_shortcode('feed_social', 'fs_render_feed_shortcode');

add_filter('the_posts', 'fs_detect_feed_shortcode_in_posts');
add_action('wp_enqueue_scripts', 'fs_enqueue_feed_scripts');

function fs_detect_feed_shortcode_in_posts($posts)
{
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

function fs_get_feed_page_url()
{
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

function fs_enqueue_feed_scripts()
{
    global $fs_feed_shortcode_used;

    wp_enqueue_script('jquery');

    wp_register_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.0.0', true);
    wp_register_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11.0.0');

    if (!empty($fs_feed_shortcode_used)) {
        wp_enqueue_script('swiper-js');
        wp_enqueue_style('swiper-css');
    }

    wp_enqueue_script('feed-social-js', FS_PLUGIN_URL . 'assets/js/feed-social.js', ['jquery'], '1.4.0', true);
    wp_enqueue_style('feed-social-css', FS_PLUGIN_URL . 'assets/css/feed-social.css', [], '1.4.0');

    wp_localize_script('feed-social-js', 'fs_feed_data', [
        'rest_url' => get_rest_url(null, 'feed-social/v1/posts'),
        'like_url' => get_rest_url(null, 'feed-social/v1/like'),
        'comment_url' => get_rest_url(null, 'feed-social/v1/comment'),
        'comments_url' => get_rest_url(null, 'feed-social/v1/comments'),
        'ajax_url' => admin_url('admin-ajax.php'),
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

function fs_render_feed_shortcode($atts)
{
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
        <div id="fs-video-modal" class="fs-video-modal" hidden role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Reproduzir vídeo', 'feed-social'); ?>">
            <button type="button" class="fs-video-modal-backdrop" aria-label="<?php esc_attr_e('Fechar vídeo', 'feed-social'); ?>"></button>
            <div class="fs-video-modal-content">
                <button type="button" class="fs-video-modal-close" aria-label="<?php esc_attr_e('Fechar', 'feed-social'); ?>">&times;</button>
                <video class="fs-video-modal-player" controls playsinline></video>
            </div>
        </div>
    </div>
    <div id="fs-post-modal" class="fs-post-modal" hidden>
        <div class="fs-post-modal-overlay"></div>

        <div class="fs-post-modal-content">

            <div class="fs-post-modal-toolbar">
                <button type="button" class="fs-post-modal-copy-link">Compartilhe <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M18 22C17.1667 22 16.4583 21.7083 15.875 21.125C15.2917 20.5417 15 19.8333 15 19C15 18.8833 15.0083 18.7625 15.025 18.6375C15.0417 18.5125 15.0667 18.4 15.1 18.3L8.05 14.2C7.76667 14.45 7.45 14.6458 7.1 14.7875C6.75 14.9292 6.38333 15 6 15C5.16667 15 4.45833 14.7083 3.875 14.125C3.29167 13.5417 3 12.8333 3 12C3 11.1667 3.29167 10.4583 3.875 9.875C4.45833 9.29167 5.16667 9 6 9C6.38333 9 6.75 9.07083 7.1 9.2125C7.45 9.35417 7.76667 9.55 8.05 9.8L15.1 5.7C15.0667 5.6 15.0417 5.4875 15.025 5.3625C15.0083 5.2375 15 5.11667 15 5C15 4.16667 15.2917 3.45833 15.875 2.875C16.4583 2.29167 17.1667 2 18 2C18.8333 2 19.5417 2.29167 20.125 2.875C20.7083 3.45833 21 4.16667 21 5C21 5.83333 20.7083 6.54167 20.125 7.125C19.5417 7.70833 18.8333 8 18 8C17.6167 8 17.25 7.92917 16.9 7.7875C16.55 7.64583 16.2333 7.45 15.95 7.2L8.9 11.3C8.93333 11.4 8.95833 11.5125 8.975 11.6375C8.99167 11.7625 9 11.8833 9 12C9 12.1167 8.99167 12.2375 8.975 12.3625C8.95833 12.4875 8.93333 12.6 8.9 12.7L15.95 16.8C16.2333 16.55 16.55 16.3542 16.9 16.2125C17.25 16.0708 17.6167 16 18 16C18.8333 16 19.5417 16.2917 20.125 16.875C20.7083 17.4583 21 18.1667 21 19C21 19.8333 20.7083 20.5417 20.125 21.125C19.5417 21.7083 18.8333 22 18 22ZM18 6C18.2833 6 18.5208 5.90417 18.7125 5.7125C18.9042 5.52083 19 5.28333 19 5C19 4.71667 18.9042 4.47917 18.7125 4.2875C18.5208 4.09583 18.2833 4 18 4C17.7167 4 17.4792 4.09583 17.2875 4.2875C17.0958 4.47917 17 4.71667 17 5C17 5.28333 17.0958 5.52083 17.2875 5.7125C17.4792 5.90417 17.7167 6 18 6ZM6 13C6.28333 13 6.52083 12.9042 6.7125 12.7125C6.90417 12.5208 7 12.2833 7 12C7 11.7167 6.90417 11.4792 6.7125 11.2875C6.52083 11.0958 6.28333 11 6 11C5.71667 11 5.47917 11.0958 5.2875 11.2875C5.09583 11.4792 5 11.7167 5 12C5 12.2833 5.09583 12.5208 5.2875 12.7125C5.47917 12.9042 5.71667 13 6 13ZM18 20C18.2833 20 18.5208 19.9042 18.7125 19.7125C18.9042 19.5208 19 19.2833 19 19C19 18.7167 18.9042 18.4792 18.7125 18.2875C18.5208 18.0958 18.2833 18 18 18C17.7167 18 17.4792 18.0958 17.2875 18.2875C17.0958 18.4792 17 18.7167 17 19C17 19.2833 17.0958 19.5208 17.2875 19.7125C17.4792 19.9042 17.7167 20 18 20Z" fill="#C8D400"/>
</svg></button>
                <button class="fs-post-modal-close">&times;</button>
            </div>

            <div class="fs-post-modal-left">

                <div class="fs-post-modal-media"></div>

            </div>

            <div class="fs-post-modal-right">

                <div class="fs-post-modal-header">
                    <?php
                        if(fs_exist_story()) {
                    ?>
                    <div class="fs-post-modal-container active">
                        <img src="<?php echo esc_url(FS_PLUGIN_URL . 'assets/images/icone-igesdf.png'); ?>" alt="Avatar" class="fs-post-modal-avatar" width="35">
                    </div>
                    <strong>Iges+</strong>
                    <?php }else{?>
                    <div class="fs-post-modal-container inactive">
                        <?php echo fs_exist_story(); ?>
                        <img src="<?php echo esc_url(FS_PLUGIN_URL . 'assets/images/icone-igesdf.png'); ?>" alt="Avatar" class="fs-post-modal-avatar" width="35">
                    </div>
                    <strong>Iges+</strong>
                    <?}?>
                </div>

                <div class="fs-post-modal-comments"></div>

                <div class="fs-post-modal-footer">

                    <div class="fs-post-modal-actions"></div>

                    <form class="fs-comment-form">
                        <textarea
                            name="comment"
                            placeholder="Adicione um comentário..."
                            required rows="2"
                            resizable="none"
                            ></textarea>

                        <button type="submit" class="fs-comment-submit btn btn-sm mt-0">
                            Publicar
                        </button>
                    </form>

                </div>

            </div>

        </div>
    </div>
<?php
    return ob_get_clean();
}

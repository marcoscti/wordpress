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

    wp_enqueue_script('feed-social-js', FS_PLUGIN_URL . 'assets/js/feed-social.js', ['jquery'], FS_DB_VERSION, true);
    wp_enqueue_style('feed-social-css', FS_PLUGIN_URL . 'assets/css/feed-social.css', [], FS_DB_VERSION);

    wp_enqueue_script('emoji-area-js', FS_PLUGIN_URL . 'assets/js/jquery.emojiarea.min.js', ['jquery'], '3.2.1', true);
    wp_enqueue_style('emoji-area-css', FS_PLUGIN_URL . 'assets/css/style.css', [], FS_DB_VERSION);

    wp_localize_script('feed-social-js', 'fs_feed_data', [
        'rest_url' => get_rest_url(null, 'feed-social/v1/posts'),
        'post_url' => get_rest_url(null, 'feed-social/v1/post'),
        'like_url' => get_rest_url(null, 'feed-social/v1/like'),
        'comment_url' => get_rest_url(null, 'feed-social/v1/comment'),
        'comments_url' => get_rest_url(null, 'feed-social/v1/comments'),
        'ajax_url' => admin_url('admin-ajax.php'),
        'sse_url' => fs_get_sse_url(),
        'feed_page_url' => fs_get_feed_page_url(),
        'rest_nonce' => wp_create_nonce('wp_rest'),
        'initial_posts' => 5,
        'posts_per_load' => 2,
        'loading_text' => __('Carregando...', 'feed-social'),
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
                <button type="button" class="fs-post-modal-copy-link">Copiar Link
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#clip0_616_28)">
                            <path d="M10.0002 13C10.4297 13.5742 10.9776 14.0492 11.6067 14.393C12.2359 14.7367 12.9317 14.9411 13.6468 14.9924C14.362 15.0436 15.0798 14.9404 15.7515 14.6898C16.4233 14.4392 17.0333 14.0471 17.5402 13.54L20.5402 10.54C21.451 9.59702 21.955 8.334 21.9436 7.02302C21.9322 5.71204 21.4063 4.45797 20.4793 3.53093C19.5523 2.60389 18.2982 2.07805 16.9872 2.06666C15.6762 2.05526 14.4132 2.55924 13.4702 3.47003L11.7502 5.18003M14.0002 11C13.5707 10.4259 13.0228 9.95084 12.3936 9.60709C11.7645 9.26333 11.0687 9.05891 10.3535 9.00769C9.63841 8.95648 8.92061 9.05966 8.24885 9.31025C7.5771 9.56083 6.96709 9.95296 6.4602 10.46L3.4602 13.46C2.54941 14.403 2.04544 15.666 2.05683 16.977C2.06822 18.288 2.59407 19.5421 3.52111 20.4691C4.44815 21.3962 5.70221 21.922 7.01319 21.9334C8.32418 21.9448 9.58719 21.4408 10.5302 20.53L12.2402 18.82" stroke="#C8D400" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                        </g>
                        <defs>
                            <clipPath id="clip0_616_28">
                                <rect width="24" height="24" fill="white" />
                            </clipPath>
                        </defs>
                    </svg>
                </button>
                <button class="fs-post-modal-close">&times;</button>
            </div>

            <div class="fs-post-modal-left">

                <div class="fs-post-modal-media"></div>

            </div>

            <div class="fs-post-modal-right">

                <div class="fs-post-modal-header">
                    <?php
                    if (fs_exist_story()) {
                    ?>
                        <div class="fs-post-modal-container active">
                            <img src="<?php echo esc_url(FS_PLUGIN_URL . 'assets/images/icone-igesdf.png'); ?>" alt="Avatar" class="fs-post-modal-avatar" width="35">
                        </div>
                        <strong>Iges+</strong>
                    <?php } else { ?>
                        <div class="fs-post-modal-container inactive">
                            <?php echo fs_exist_story(); ?>
                            <img src="<?php echo esc_url(FS_PLUGIN_URL . 'assets/images/icone-igesdf.png'); ?>" alt="Avatar" class="fs-post-modal-avatar" width="35">
                        </div>
                        <strong>Iges+</strong>
                    <?php } ?>
                </div>
                <div class="fs-post-modal-legend"></div>
                <div class="fs-post-modal-comments"></div>

                <div class="fs-post-modal-footer">

                    <div class="fs-post-modal-actions"></div>

                    <form class="fs-comment-form" data-emojiarea data-type="css" data-global-picker="false">
                        <i class="emoji emoji-smile emoji-button"><svg aria-label="Emoji" class="x1lliihq x1n2onr6 x1roi4f4" fill="#575756" height="24" role="img" viewBox="0 0 24 24" width="24"><title>Emoji</title><path d="M15.83 10.997a1.167 1.167 0 1 0 1.167 1.167 1.167 1.167 0 0 0-1.167-1.167Zm-6.5 1.167a1.167 1.167 0 1 0-1.166 1.167 1.167 1.167 0 0 0 1.166-1.167Zm5.163 3.24a3.406 3.406 0 0 1-4.982.007 1 1 0 1 0-1.557 1.256 5.397 5.397 0 0 0 8.09 0 1 1 0 0 0-1.55-1.263ZM12 .503a11.5 11.5 0 1 0 11.5 11.5A11.513 11.513 0 0 0 12 .503Zm0 21a9.5 9.5 0 1 1 9.5-9.5 9.51 9.51 0 0 1-9.5 9.5Z"></path></svg></i>
                        <textarea
                            name="comment"
                            placeholder="Adicione um comentário..."
                            required rows="1"
                            resizable="none"></textarea>
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

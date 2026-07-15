<?php
if (!defined('ABSPATH')) exit;

function fs_register_story_shortcode()
{
    add_shortcode('feed_social_storie', 'fs_render_story_shortcode');
}
add_action('init', 'fs_register_story_shortcode');
function fs_render_story_modal()
{
?>
    <div id="fs-story-modal" class="fs-story-modal">

        <span class="fs-story-close">&times;</span>

        <div class="fs-story-modal-wrapper">

            <div class="fs-story-modal-content"></div>

            <div class="fs-story-progress-bar-container"></div>

        </div>

        <button class="fs-story-nav fs-story-prev">&lsaquo;</button>
        <button class="fs-story-nav fs-story-next">&rsaquo;</button>

    </div>
    <?php
}
function fs_exist_story()
{
    $args = array(
        'post_type'      => 'social_story',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $stories_query = new WP_Query($args);

    return $stories_query->have_posts();
}
function fs_render_story_shortcode($atts)
{
    fs_enqueue_story_assets();

    $args = array(
        'post_type'      => 'social_story',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $stories_query = new WP_Query($args);

    ob_start();

    $story_ids = [];

    if ($stories_query->have_posts()) {

        while ($stories_query->have_posts()) {
            $stories_query->the_post();

            // Mantém expiração dos stories normais
            $expires = get_post_meta(get_the_ID(), '_fs_story_expires', true);

            if ($expires === 'yes') {

                $post_time = get_post_time('U', true);
                $expiration_time = $post_time + (24 * HOUR_IN_SECONDS);

                if (time() >= $expiration_time) {
                    continue;
                }
            }

            $story_ids[] = get_the_ID();
        }

        wp_reset_postdata();


        if (!empty($story_ids)) :

            $first_story = $story_ids[0];


    ?>

            <div class="fs-story-container">

                <a href="#"
                    class="fs-story-item fs-story-single"
                    data-story-id="<?php echo esc_attr($first_story); ?>">

                    <img
                        src="<?php echo esc_url(FS_PLUGIN_URL . 'assets/images/icone-igesdf.png'); ?>"
                        class="fs-story-thumb">

                </a>

            </div>
        <?php else: ?>
            <div class="fs-story-container">

                <a href="#"
                    class="fs-story-item fs-story-single">

                    <img
                        src="<?php echo esc_url(FS_PLUGIN_URL . 'assets/images/icone-igesdf.png'); ?>"
                        class="fs-story-thumb inactive">

                </a>

            </div>

        <?php
        endif;
    } else {
        ?>
        <div class="fs-story-container">

            <a href="#"
                class="fs-story-item fs-story-single">

                <img
                    src="<?php echo esc_url(FS_PLUGIN_URL . 'assets/images/icone-igesdf.png'); ?>"
                    class="fs-story-thumb inactive">
            </a>
        </div>
    <?php
    }


    wp_localize_script(
        'fs-story-script',
        'fs_story_data',
        [
            'story_ids' => $story_ids
        ]
    );


    fs_render_story_modal();

    return ob_get_clean();
}

function fs_enqueue_story_assets()
{
    // Enqueue Swiper JS and CSS
    wp_enqueue_style('swiper-css', 'https://unpkg.com/swiper/swiper-bundle.min.css', array(), '8.4.5');
    wp_enqueue_script('swiper-js', 'https://unpkg.com/swiper/swiper-bundle.min.js', array(), '8.4.5', true);

    // Enqueue custom plugin assets
    wp_enqueue_style('fs-story-style', FS_PLUGIN_URL . 'assets/css/story.css', array(), '1.0.0');
    wp_enqueue_script('fs-story-script', FS_PLUGIN_URL . 'assets/js/story.js', array('jquery', 'swiper-js'), '1.0.0', true);

    wp_localize_script('fs-story-script', 'fs_story_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('fs_get_story_content')
    ));
}

function fs_get_story_content_ajax()
{
    check_ajax_referer('fs_get_story_content', 'nonce');

    if (!isset($_POST['story_id'])) {
        wp_send_json_error(['message' => 'ID do story ausente.']);
    }

    $story_id = intval($_POST['story_id']);
    $story = get_post($story_id);

    if (!$story || $story->post_type !== 'social_story' || $story->post_status !== 'publish') {
        wp_send_json_error(['message' => 'Story não encontrado.']);
    }

    $video_id = get_post_meta($story_id, '_fs_story_video_id', true);
    $video_url = $video_id ? wp_get_attachment_url($video_id) : '';
    $has_video = !empty($video_url);

    $content = '<h2>' . esc_html($story->post_title) . '</h2>';

    // Prioriza o vídeo. Se não houver vídeo, usa a imagem destacada.
    if ($video_url) {
        $content .= '<video src="' . esc_url($video_url) . '"controls autoplay muted playsinline></video>';
    } elseif (has_post_thumbnail($story_id)) {
        $content .= get_the_post_thumbnail($story_id, 'large');
    }

    $story_content = apply_filters('the_content', $story->post_content);
    if (!empty(trim($story_content))) {
        $content .= '<div class="story-main-content">' . $story_content . '</div>';
    }

    wp_send_json_success([
        'content' => $content,
        'has_video' => $has_video,
    ]);
}
function fs_register_highlight_shortcode()
{
    add_shortcode('feed_social_destaques', 'fs_render_highlight_shortcode');
}
add_action('init', 'fs_register_highlight_shortcode');

function fs_render_highlight_shortcode($atts)
{
    fs_enqueue_story_assets();

    $termos = get_terms([
        'taxonomy'   => 'destaque',
        'hide_empty' => true
    ]);

    if (empty($termos) || is_wp_error($termos)) {
        return '';
    }

    ob_start();

    $story_ids = [];
    ?>

    <div class="fs-highlight-container">

        <div class="swiper fs-highlight-carousel">
            <div class="swiper-wrapper">

                <?php foreach ($termos as $termo) :

                    $stories = new WP_Query([
                        'post_type'      => 'social_story',
                        'posts_per_page' => -1,
                        'post_status'    => 'publish',
                        'tax_query' => [
                            [
                                'taxonomy' => 'destaque',
                                'field'    => 'term_id',
                                'terms'    => $termo->term_id
                            ]
                        ]
                    ]);

                    if (!$stories->have_posts()) {
                        continue;
                    }
                    $story_ids_term = [];

                    while ($stories->have_posts()) {
                        $stories->the_post();

                        // Destaques não possuem expiração
                        $story_ids_term[] = get_the_ID();
                        $story_ids[] = get_the_ID();
                    }

                    if (empty($story_ids_term)) {
                        wp_reset_postdata();
                        continue;
                    }

                    wp_reset_postdata();

                    $capa = get_the_post_thumbnail_url($story_ids_term[0], 'thumbnail');

                ?>

                    <div class="swiper-slide">

                        <a href="#"
                            class="fs-highlight-item"
                            data-story-group='<?php echo json_encode($story_ids_term); ?>'>

                            <img
                                src="<?php echo esc_url($capa); ?>"
                                class="fs-story-thumb">

                            <span class="fs-story-title">
                                <?php echo esc_html($termo->name); ?>
                            </span>

                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>

        </div>

    </div>

<?php

    return ob_get_clean();
}
add_action('wp_ajax_fs_get_story_content', 'fs_get_story_content_ajax');
add_action('wp_ajax_nopriv_fs_get_story_content', 'fs_get_story_content_ajax');

?>
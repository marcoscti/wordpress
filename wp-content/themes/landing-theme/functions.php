<?php

function my_theme_styles()
{
    wp_enqueue_style(
        'my-theme-style',
        get_template_directory_uri() . '/assets/css/style.css',
        [],
        '1.0'
    );

    wp_enqueue_style(
        'google-fonts',
        'https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap',
        [],
        null
    );
}
add_action('wp_enqueue_scripts', 'my_theme_styles');


add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('dashicons');
});


add_action('init', function () {

    register_post_type('banner_home', [

        'labels' => [
            'name' => 'Banners Home',
            'singular_name' => 'Banner Home',
        ],

        'public' => false,
        'show_ui' => true,
        'show_in_rest' => true,

        'menu_icon' => 'dashicons-images-alt2',

        'supports' => [
            'title',
            'thumbnail'
        ],

        'publicly_queryable' => false,
        'exclude_from_search' => true,

    ]);
});


/**
 * Adiciona meta box para imagem mobile no banner_home
 */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'banner_mobile_meta',
        'Configurações do Banner',
        function ($post) {
            $mobile_image = get_post_meta($post->ID, '_banner_mobile_image', true);
            wp_nonce_field('banner_mobile_save', 'banner_mobile_nonce');
?>
            <p>
                <label for="banner_mobile_image"><strong>Imagem Mobile:</strong></label><br>
                <input type="text" name="banner_mobile_image" id="banner_mobile_image" value="<?php echo esc_attr($mobile_image); ?>" style="width:80%; margin-top: 5px;">
                <button type="button" class="button" id="banner_mobile_upload_btn">Selecionar Imagem</button>
            </p>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const uploadButton = document.getElementById('banner_mobile_upload_btn');
                    const imageInput = document.getElementById('banner_mobile_image');

                    uploadButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        var custom_uploader = wp.media({
                            title: 'Selecionar Imagem Mobile',
                            button: {
                                text: 'Usar esta imagem'
                            },
                            multiple: false
                        }).on('select', function() {
                            var attachment = custom_uploader.state().get('selection').first().toJSON();
                            imageInput.value = attachment.url;
                        }).open();
                    });
                });
            </script>
<?php
        },
        'banner_home',
        'normal',
        'high'
    );
});

add_action('save_post', function ($post_id) {
    if (!isset($_POST['banner_mobile_nonce']) || !wp_verify_nonce($_POST['banner_mobile_nonce'], 'banner_mobile_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (isset($_POST['banner_mobile_image'])) {
        update_post_meta($post_id, '_banner_mobile_image', esc_url_raw($_POST['banner_mobile_image']));
    }
});

add_action('admin_enqueue_scripts', function ($hook) {
    global $post_type;
    if ($post_type === 'banner_home') {
        wp_enqueue_media();
    }
});


function swiper_assets()
{
    wp_enqueue_style(
        'swiper-css',
        'https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css',
        [],
        '12.0.0'
    );

    wp_enqueue_script(
        'swiper-js',
        'https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js',
        [],
        '12.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'swiper_assets', 100);


add_shortcode('banner_home', function ($atts) {

    if (!is_front_page()) {
        return '';
    }

    $atts = shortcode_atts([
        'autoplay'   => 'true',
        'loop'       => 'true',
        'slides'     => 1,
        'space'      => 20,
        'speed'      => 600,
        'delay'      => 4000,
        'navigation' => 'true',
        'pagination' => 'true',
    ], $atts);

    $q = new WP_Query([
        'post_type'      => 'banner_home',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ]);

    if (!$q->have_posts()) {
        return '';
    }

    $uid = wp_unique_id('swiper_');

    ob_start();
?>

    <div class="swiper <?php echo esc_attr($uid); ?>">

        <div class="swiper-wrapper">

            <?php while ($q->have_posts()) : $q->the_post(); ?>

                <div class="swiper-slide">

                    <?php
                    $mobile_image = get_post_meta(get_the_ID(), '_banner_mobile_image', true);
                    if (has_post_thumbnail()) {
                        $desktop_image = get_the_post_thumbnail_url(get_the_ID(), 'full');
                        if ($mobile_image) {
                            echo '<picture>';
                            echo '<source media="(max-width: 767px)" srcset="' . esc_url($mobile_image) . '">';
                            echo '<img src="' . esc_url($desktop_image) . '" class="banner_item_image" alt="' . esc_attr(get_the_title()) . '" title="' . esc_attr(get_the_title()) . '" loading="lazy">';
                            echo '</picture>';
                        } else {
                            the_post_thumbnail('full', [
                                'class' => 'banner_item_image',
                                'alt'   => get_the_title(),
                                'title' => get_the_title(),
                                'loading' => 'lazy'
                            ]);
                        }
                    }
                    ?>

                </div>

            <?php endwhile; ?>

        </div>

        <?php if ($atts['pagination'] === 'true') : ?>
            <div class="swiper-pagination"></div>
        <?php endif; ?>

        <?php if ($atts['navigation'] === 'true') : ?>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        <?php endif; ?>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const el = document.querySelector('.<?php echo esc_js($uid); ?>');

            if (el) {

                new Swiper(el, {

                    loop: <?php echo ($atts['loop'] === 'true' && $q->post_count > 1) ? 'true' : 'false'; ?>,

                    slidesPerView: <?php echo (int)$atts['slides']; ?>,

                    spaceBetween: <?php echo (int)$atts['space']; ?>,

                    speed: <?php echo (int)$atts['speed']; ?>,

                    <?php if ($atts['autoplay'] === 'true') : ?>
                        autoplay: {
                            delay: <?php echo (int)$atts['delay']; ?>
                        },
                    <?php endif; ?>

                    <?php if ($atts['pagination'] === 'true') : ?>
                        pagination: {
                            el: '.<?php echo esc_js($uid); ?> .swiper-pagination',
                            clickable: true
                        },
                    <?php endif; ?>

                    <?php if ($atts['navigation'] === 'true') : ?>
                        navigation: {
                            nextEl: '.<?php echo esc_js($uid); ?> .swiper-button-next',
                            prevEl: '.<?php echo esc_js($uid); ?> .swiper-button-prev'
                        },
                    <?php endif; ?>

                });

            }

        });
    </script>

<?php

    wp_reset_postdata();

    return ob_get_clean();
});

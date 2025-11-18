<?php
/**
 * Plugin Name: Busca Notícias (ACF) - Sugestões AJAX
 * Description: Shortcode [busca_noticias] — busca por CPT "noticia" incluindo TÍTULO e campos ACF (resumo, revisao, autor, autor_bio). Sugestões a partir de 3 caracteres via AJAX.
 * Version: 1.7
 * Author: Marcos Cordeiro
 * Author URI: https://marcoscti.dev/
 * Text Domain: busca-noticias
 */

if (!defined('ABSPATH')) exit;

class BN_Ajax_Search {

    public function __construct() {
        add_shortcode('busca_noticias', [ $this, 'shortcode_markup' ]);
        add_action('wp_enqueue_scripts', [ $this, 'enqueue_assets' ]);
        add_action('wp_ajax_busca_noticias', [ $this, 'ajax_handler' ]);
        add_action('wp_ajax_nopriv_busca_noticias', [ $this, 'ajax_handler' ]);
    }

    public function enqueue_assets() {
        $dir = plugin_dir_url(__FILE__);

        wp_enqueue_style(
            'bn-search-style',
            $dir . 'assets/css/busca-noticias.css',
            [],
            '1.7'
        );

        wp_enqueue_script(
            'bn-search-js',
            $dir . 'assets/js/busca-noticias.js',
            ['jquery'],
            '1.7',
            true
        );

        wp_localize_script('bn-search-js', 'BN_Search', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bn_search_nonce')
        ]);
    }

    /** SHORTCODE */
    public function shortcode_markup($atts) {
        $atts = shortcode_atts([
            'placeholder' => 'Pesquisar notícias...',
            'min_chars' => 3,
            'limit' => 50,
            'post_type' => 'noticia',
            'taxonomy'  => '',
            'tax_term'  => '',
            'category'  => '',
        ], $atts);

        // alias: allow using 'category' attribute as shorthand for tax_term
        if (! empty($atts['category']) && empty($atts['tax_term'])) {
            $atts['tax_term'] = $atts['category'];
        }

        ob_start(); ?>
        
        <div class="bn-search-wrap">
            <input type="search"
                class="bn-search-input"
                placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                data-min="<?php echo esc_attr($atts['min_chars']); ?>"
                data-limit="<?php echo esc_attr($atts['limit']); ?>"
                data-post-type="<?php echo esc_attr($atts['post_type']); ?>"
                data-taxonomy="<?php echo esc_attr($atts['taxonomy']); ?>"
                data-tax-term="<?php echo esc_attr($atts['tax_term']); ?>"
                aria-label="Buscar notícias">
            <div class="bn-results" role="listbox" aria-live="polite"></div>
        </div>

        <?php
        return ob_get_clean();
    }

    /** AJAX */
    public function ajax_handler() {
        check_ajax_referer('bn_search_nonce', 'nonce');

        $term  = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : '';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;

        if (mb_strlen($term) < 3) {
            wp_send_json_error(['message' => 'Digite ao menos 3 caracteres.']);
        }

        // Receber filtros adicionais do cliente
        $post_type_raw = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'noticia';
        $taxonomy  = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
        $tax_term  = isset($_POST['tax_term']) ? sanitize_text_field($_POST['tax_term']) : '';

        // Aceitar lista separada por vírgula para post_type (ex.: "noticia,page")
        if (strpos($post_type_raw, ',') !== false) {
            $post_type = array_map('sanitize_text_field', array_map('trim', explode(',', $post_type_raw)));
        } else {
            $post_type = sanitize_text_field($post_type_raw);
        }

        // Normalizar post_type para chave de cache (string consistente)
        $post_type_key = is_array($post_type) ? implode(',', $post_type) : $post_type;

        // Cache simples por termo+limit+filtros (reduz carga em buscas repetidas)
        $cache_key = 'bn_search_' . md5(strtolower($term) . '|' . $limit . '|' . $post_type_key . '|' . $taxonomy . '|' . $tax_term);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            wp_send_json_success($cached);
        }

        /**
         * Observação: quando usamos 's' juntamente com 'meta_query', o WP aplica
         * ambos como um AND — ou seja, o post precisa satisfazer 's' E o meta_query.
         * Para incluir posts que batem apenas no título/conteúdo OU apenas nos
         * campos ACF, fazemos duas consultas (por texto e por meta) e unimos
         * os IDs (união), então buscamos os posts resultantes.
         */

        // 1) Busca por 's' (título/conteúdo/excerpt) retornando apenas IDs
        $args_s = [
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            's'              => $term,
            'fields'         => 'ids',
        ];

        if (! empty($taxonomy) && ! empty($tax_term)) {
            $args_s['tax_query'] = [
                [
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $tax_term,
                ],
            ];
        }

        $q_s = new WP_Query($args_s);
        $ids_s = $q_s->posts;

        // 2) Busca por ACF (meta_query) retornando apenas IDs
        $args_meta = [
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'fields'         => 'ids',
            'meta_query'     => [
                'relation' => 'OR',
                [ 'key' => 'resumo',    'value' => $term, 'compare' => 'LIKE' ],
                [ 'key' => 'revisao',   'value' => $term, 'compare' => 'LIKE' ],
                [ 'key' => 'autor',     'value' => $term, 'compare' => 'LIKE' ],
                [ 'key' => 'autor_bio', 'value' => $term, 'compare' => 'LIKE' ],
            ],
        ];

        if (! empty($taxonomy) && ! empty($tax_term)) {
            $args_meta['tax_query'] = [
                [
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $tax_term,
                ],
            ];
        }

        $q_meta = new WP_Query($args_meta);
        $ids_meta = $q_meta->posts;

        // Unir IDs e limitar
        $all_ids = array_values(array_unique(array_merge((array) $ids_s, (array) $ids_meta)));

        if (empty($all_ids)) {
            wp_reset_postdata();
            // cachear resultado vazio por curto período
            set_transient($cache_key, [], MINUTE_IN_SECONDS * 5);
            wp_send_json_success([]);
        }

        // respeitar o limit
        if (count($all_ids) > $limit) {
            $all_ids = array_slice($all_ids, 0, $limit);
        }

        // Buscar os posts finais (ordenar por data como antes)
        $final_args = [
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'post__in'       => $all_ids,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if (! empty($taxonomy) && ! empty($tax_term)) {
            $final_args['tax_query'] = [
                [
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $tax_term,
                ],
            ];
        }

        $final_q = new WP_Query($final_args);

        $results = [];

        if ($final_q->have_posts()) {
            while ($final_q->have_posts()) {
                $final_q->the_post();

                $post_id = get_the_ID();

                // resumo ACF ou excerpt — normalizamos e escapamos
                $resumo = '';
                if (function_exists('get_field')) {
                    $acf_resumo = get_field('resumo', $post_id);
                    if ($acf_resumo) {
                        $resumo = wp_trim_words(wp_strip_all_tags($acf_resumo), 50, '...');
                    }
                }

                if (empty($resumo)) {
                    if (has_excerpt($post_id)) {
                        $resumo = get_the_excerpt();
                    } else {
                        $resumo = wp_trim_words(strip_tags(get_the_content()), 50, '...');
                    }
                }

                // thumbnail
                $thumb = '';
                if (has_post_thumbnail($post_id)) {
                    $img = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'medium');
                    if ($img) $thumb = $img[0];
                }

                // Escapar valores para evitar XSS ao inserir no JS
                $results[] = [
                    'id'        => $post_id,
                    'title'     => wp_strip_all_tags(get_the_title()),
                    'resumo'    => wp_strip_all_tags($resumo),
                    'thumbnail' => $thumb ? esc_url_raw($thumb) : '',
                    'permalink' => esc_url_raw(get_permalink()),
                ];
            }
        }

        wp_reset_postdata();

        // cachear resultados por 1 hora
        set_transient($cache_key, $results, HOUR_IN_SECONDS);

        wp_send_json_success($results);
    }
}

new BN_Ajax_Search();

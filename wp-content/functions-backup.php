<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

/************************ IgesDF ************************/
include('custom-shortcodes.php');
/* =========================
   OPEN GRAPH DINÂMICO (com GD opcional)
========================= */

/**
 * Verifica se a GD está disponível.
 */
function igesdf_gd_is_available()
{
    return extension_loaded('gd') && function_exists('gd_info');
}

/**
 * Confere se o MIME é legível.
 */
function igesdf_can_read_format($mime)
{
    $mime = strtolower((string)$mime);
    return in_array($mime, ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'], true);
}

/**
 * Cria recurso de imagem a partir do MIME.
 */
function igesdf_image_create_from_mime($path, $mime)
{
    switch (strtolower($mime)) {
        case 'image/jpeg':
        case 'image/jpg':
            return imagecreatefromjpeg($path);
        case 'image/png':
            return imagecreatefrompng($path);
        case 'image/webp':
            return function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false;
        default:
            return false;
    }
}

/**
 * Gera uma OG 600x315 (JPG) centrada a partir da imagem destacada
 * - Salva em uploads/og/og-{postID}.jpg
 * - Retorna URL absoluta ou false
 */
function igesdf_generate_og_image_for_post($post_id)
{
    if (!igesdf_gd_is_available()) return false;
    if (!has_post_thumbnail($post_id)) return false;

    $thumb_id = get_post_thumbnail_id($post_id);
    if (!$thumb_id) return false;

    $file_path = get_attached_file($thumb_id);
    if (!$file_path || !file_exists($file_path)) return false;

    $mime = get_post_mime_type($thumb_id);
    if (!$mime) {
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $map = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        $mime = $map[$ext] ?? 'image/jpeg';
    }
    if (!igesdf_can_read_format($mime)) return false;

    $src = igesdf_image_create_from_mime($file_path, $mime);
    if (!$src) return false;

    $src_w = imagesx($src);
    $src_h = imagesy($src);

    $dst_w = 600;
    $dst_h = 315;
    $dst_ratio = $dst_w / $dst_h;
    $src_ratio = $src_w / $src_h;

    if ($src_ratio > $dst_ratio) {
        $new_w = (int) round($src_h * $dst_ratio);
        $new_h = $src_h;
        $src_x = (int) max(0, ($src_w - $new_w) / 2);
        $src_y = 0;
    } else {
        $new_w = $src_w;
        $new_h = (int) round($src_w / $dst_ratio);
        $src_x = 0;
        $src_y = (int) max(0, ($src_h - $new_h) / 2);
    }

    $dst = imagecreatetruecolor($dst_w, $dst_h);
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, $dst_w, $dst_h, $white);
    imagecopyresampled($dst, $src, 0, 0, $src_x, $src_y, $dst_w, $dst_h, $new_w, $new_h);

    $uploads = wp_upload_dir();
    if (!empty($uploads['error'])) {
        imagedestroy($src);
        imagedestroy($dst);
        return false;
    }

    $og_dir = trailingslashit($uploads['basedir']) . 'og';
    if (!file_exists($og_dir)) {
        wp_mkdir_p($og_dir);
        @chmod($og_dir, 0755);
    }

    $filename  = 'og-' . $post_id . '.jpg';
    $save_path = trailingslashit($og_dir) . $filename;

    $saved = imagejpeg($dst, $save_path, 85);
    imagedestroy($src);
    imagedestroy($dst);

    if (!$saved || !file_exists($save_path)) return false;
    @chmod($save_path, 0644);

    $og_url = trailingslashit($uploads['baseurl']) . 'og/' . $filename;
    update_post_meta($post_id, '_og_generated_image', esc_url_raw($og_url));
    return $og_url;
}

/**
 * Gera OG na atualização/salvamento do post (evita custo em página).
 */
function igesdf_maybe_generate_on_save($post_id, $post, $update)
{
    if (wp_is_post_revision($post_id)) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    $pt = get_post_type($post_id);
    $pt_obj = get_post_type_object($pt);
    if (!$pt_obj || empty($pt_obj->public)) return;

    // Se o editor definiu 'og_image' manualmente, respeita.
    $manual = get_post_meta($post_id, 'og_image', true);
    if (!empty($manual)) return;

    if (has_post_thumbnail($post_id)) {
        igesdf_generate_og_image_for_post($post_id);
    } else {
        delete_post_meta($post_id, '_og_generated_image');
    }
}
add_action('save_post', 'igesdf_maybe_generate_on_save', 10, 3);

/**
 * Resolve a melhor imagem OG para o post (ordem: meta -> gerada -> thumb -> fallback)
 */
function igesdf_resolve_og_image($post_id)
{
    $meta = get_post_meta($post_id, 'og_image', true);
    if ($meta) {
        return is_numeric($meta)
            ? esc_url(wp_get_attachment_image_url((int)$meta, 'full'))
            : esc_url_raw($meta);
    }
    $generated = get_post_meta($post_id, '_og_generated_image', true);
    if ($generated) return esc_url($generated);

    if (has_post_thumbnail($post_id)) {
        $thumb = get_the_post_thumbnail_url($post_id, 'full');
        if ($thumb) return esc_url($thumb);
    }

    // Fallback seguro (JPG)
    return esc_url('https://igesdf.org.br/wp-content/uploads/2026/01/thumb.png');
}

/**
 * Injeta OG/Twitter no <head> com prioridade ALTA (999).
 */
function igesdf_add_dynamic_og_in_head()
{
    if (is_admin() || !is_singular()) return;

    $post_id = get_queried_object_id();
    if (!$post_id) return;

    $title = get_the_title($post_id) ?: get_bloginfo('name');
    $url   = get_permalink($post_id);

    $description = get_the_excerpt($post_id);
    if (function_exists('get_field') && empty($description)) {
        $acf_desc = get_field('resumo', $post_id);
        if ($acf_desc) $description = $acf_desc;
    }
    if (!$description) {
        $description = wp_trim_words(
            wp_strip_all_tags(get_post_field('post_content', $post_id)),
            30
        );
    }

    // Garante imagem OG (gera se ainda não existir e houver thumb)
    $og_image = igesdf_resolve_og_image($post_id);
    if (!$og_image && has_post_thumbnail($post_id)) {
        $gen = igesdf_generate_og_image_for_post($post_id);
        if ($gen) $og_image = $gen;
    }
    if (!$og_image) {
        $og_image = 'https://igesdf.org.br/wp-content/uploads/2026/01/thumb.png';
    }

    $locale = get_locale();
    if (empty($locale)) $locale = 'pt_BR';

    echo '<meta property="og:locale" content="' . esc_attr($locale) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr(wp_strip_all_tags($description)) . '">' . "\n";
    echo '<meta property="og:type" content="article">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    echo '<meta property="og:image" content="' . esc_url($og_image) . '">' . "\n";
    echo '<meta name="description" content="' . esc_attr(wp_strip_all_tags($description)) . '">' . "\n";
}
add_action('wp_head', 'igesdf_add_dynamic_og_in_head', 5);

/* =========================
   TITLE DINÂMICO
========================= */
add_filter('pre_get_document_title', function ($title) {
    return is_singular() ? get_the_title() : $title;
}, 99);

// // This theme uses wp_nav_menu() in two locations.
register_nav_menus(array(
    'menu_topo'      => __('Menu Topo'),
    'menu_social'    => __('Menu Social'),
    'menu_principal' => __('Menu Principal'),
    'menu_unidades'  => __('Menu Unidades'),
));

/* =========================
   VLIBRAS
========================= */
function vlibras_widget()
{
    echo <<<EOF
<div vw class="enabled">
    <div vw-access-button class="active"></div>
    <div vw-plugin-wrapper>
        <div class="vw-plugin-top-wrapper"></div>
    </div>
</div>
<script>
    new window.VLibras.Widget();
</script>
EOF;
}
function vlibras_enqueue()
{
    wp_enqueue_script('vlibrasjs', 'https://vlibras.gov.br/app/vlibras-plugin.js', array(), '1.0');
    wp_add_inline_script('vlibrasjs', 'try{vlibrasjs.load({ async: true });}catch(e){}');
}
add_action('wp_footer', 'vlibras_widget');
add_action('wp_enqueue_scripts', 'vlibras_enqueue');

/* =========================
   Menu Superior (Elementor CSS)
========================= */
add_action('wp_enqueue_scripts', function () {
    if (!class_exists('\Elementor\Core\Files\CSS\Post')) return;
    $template_id = 25594;
    $css_file = new \Elementor\Core\Files\CSS\Post($template_id);
    $css_file->enqueue();
});

/* =========================
   Remove jQuery Migrate
========================= */
function remove_jquery_migrate($scripts)
{
    if (!is_admin() && isset($scripts->registered['jquery'])) {
        $script = $scripts->registered['jquery'];
        if ($script->deps) {
            $script->deps = array_diff($script->deps, ['jquery-migrate']);
        }
    }
}
add_action('wp_default_scripts', 'remove_jquery_migrate');

/* =========================
   Custom Post Types
========================= */
function create_posttypes()
{
    /* Post tipo Ato */
    register_post_type('ato', [
        'labels' => [
            'name'          => __('Estimativas'),
            'singular_name' => __('Estimativa'),
            'all_items'     => __('Todas as estimativas')
        ],
        'public'      => true,
        'has_archive' => false,
        'menu_icon'   => 'dashicons-chart-area',
        'rewrite'     => ['slug' => 'ato'],
        'can_export'  => true,
        'taxonomies'  => ['category'],
    ]);
    add_post_type_support('ato', 'thumbnail');
	
	register_post_type('processo', [
        'labels' => [
            'name'          => __('Processo Seletivo'),
            'singular_name' => __('Processo seletivo'),
            'all_items'     => __('Todos os Processos Seletivos')
        ],
        'public'      => true,
        'has_archive' => false,
        'menu_icon'   => 'dashicons-groups',
        'rewrite'     => ['slug' => 'processo'],
        'can_export'  => true,
        'taxonomies'  => ['category'],
    ]);
    add_post_type_support('processo', 'thumbnail');
    /* Post tipo noticia */
    register_post_type('noticia', [
        'labels' => [
            'name'          => __('Notícias'),
            'singular_name' => __('Notícia'),
            'all_items'     => __('Todas as Notícias')
        ],
        'public'             => true,
        'has_archive'        => true,
        'menu_icon'          => 'dashicons-megaphone',
        'rewrite'            => ['slug' => 'noticia'],
        'can_export'         => true,
        'publicly_queryable' => true,
        'show_in_rest'       => true,
        'taxonomies'         => ['category', 'post_tag'],
    ]);
    add_post_type_support('noticia', 'thumbnail', 'editor');

    /* Post tipo impresso */
    register_post_type('impresso', [
        'labels' => [
            'name'          => __('Impresso'),
            'singular_name' => __('Impresso'),
            'all_items'     => __('Todas os Impressos')
        ],
        'public'      => true,
        'has_archive' => false,
        'menu_icon'   => 'dashicons-printer',
        'rewrite'     => ['slug' => 'impresso'],
        'can_export'  => true,
        'show_in_rest' => true,
        'taxonomies'  => ['category', 'post_tag'],
    ]);
    add_post_type_support('impresso', 'thumbnail', 'editor');

    /* Post tipo Indexibilidade */
    register_post_type('dispensa', [
        'labels' => [
            'name'          => __('Inexigibilidade / Dispensa'),
            'singular_name' => __('Inexigibilidade / Dispensa'),
            'all_items'     => __('Todos os processos de compras inexigíveis / dispensados')
        ],
        'public'      => true,
        'has_archive' => false,
        'menu_icon'   => 'dashicons-media-spreadsheet',
        'rewrite'     => ['slug' => 'dispensa'],
        'can_export'  => true,
        'taxonomies'  => ['category'],
    ]);
    add_post_type_support('dispensa', 'thumbnail');

    /* Post tipo produções */
    register_post_type('producao', [
        'labels' => [
            'name'          => __('Produções'),
            'singular_name' => __('Produção'),
            'all_items'     => __('Todas as Produções')
        ],
        'public'      => true,
        'has_archive' => false,
        'menu_icon'   => 'dashicons-edit-page',
        'rewrite'     => ['slug' => 'producao'],
        'can_export'  => true,
        'taxonomies'  => ['category']
    ]);
    add_post_type_support('producao', 'thumbnail');
}
add_action('init', 'create_posttypes');

add_filter('tablepress_wp_search_integration', '__return_false');

/* =========================
   Cache simples (mantido)
========================= */
function get_cache_file_name()
{
    $post_id = get_queried_object_id();
    $post_id = $post_id ? (int) $post_id : 0;

    $hash = md5($_SERVER['REQUEST_URI']);

    return "cache_postid-{$post_id}-{$hash}.html";
}

function serve_cache()
{
    if (is_user_logged_in() || is_singular()) {
        return false;
    }
    $cache_file = ABSPATH . 'cache/'  . get_cache_file_name();
    if (file_exists($cache_file)) {
        echo file_get_contents($cache_file);
        exit();
    }
}

function cache_output()
{
    if (is_user_logged_in() || is_404() || is_search()) {
        return;
    }

    ob_start(function ($buffer) {
        // Safety Net: Do not cache if the page contains 404-like text.
        if (strpos($buffer, 'The page can&rsquo;t be found.') !== false) {
            return $buffer;
        }
        $timestamp = date_i18n('Y-m-d H:i:s');
        // Corrigido para comentários HTML reais:
        $buffer .= "\n<!-- Página cacheada em $timestamp -->";
        $buffer .= "\n<!-- Desenvolvedor: Marcos Cordeiro - Email: marcosc974@gmail.com -->";
        $cache_file =  ABSPATH . 'cache/' . get_cache_file_name();
        file_put_contents($cache_file, $buffer);
        return $buffer;
    });
}

function clear_all_cache()
{
    $cache_dir = ABSPATH . 'cache/';
    $files = glob($cache_dir . 'cache_*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}
add_action('save_post', 'clear_all_cache');
add_action('deleted_post', 'clear_all_cache');
add_action('edit_post', 'clear_all_cache');
add_action('init', 'serve_cache');
add_action('wp', 'cache_output');

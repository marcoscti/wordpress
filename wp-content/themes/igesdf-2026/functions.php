<?php

function meu_tema_setup()
{

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');

    register_nav_menus([
        'header_menu' => 'Menu Principal',
        'comunicados_menu'  => __('Comunicados', 'igesdf-2026'),
        'documentos_menu'  => __('Documentos', 'igesdf-2026'),

    ]);
}

add_action('after_setup_theme', 'meu_tema_setup');

function meu_tema_assets()
{
    wp_enqueue_style(
        'google-fonts',
        'https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap',
        [],
        null
    );
    wp_enqueue_style(
        'bootstrap-css',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
        [],
        '5.3.3'
    );
    // Font Awesome 4.7
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
        [],
        '4.7.0'
    );
    wp_enqueue_style(
        'main-css',
        get_template_directory_uri() . '/assets/css/main.css',
        [],
        '1.0'
    );
    wp_enqueue_script(
        'bootstrap-js',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
        [],
        '5.3.3',
        true
    );
    wp_enqueue_script(
        'main-js',
        get_template_directory_uri() . '/assets/js/main.js',
        ['jquery'],
        '1.0',
        true
    );
}
/**
 * Adiciona classes Bootstrap aos itens do menu
 */
function theme_bootstrap_menu_classes($classes, $item, $args)
{
    if ($args->theme_location === 'header_menu') {

        $classes[] = 'nav-item';

        if (in_array('menu-item-has-children', $classes)) {
            $classes[] = 'dropdown';
        }
    }

    return $classes;
}
//add_filter('nav_menu_css_class', 'theme_bootstrap_menu_classes', 10, 3);


/**
 * Adiciona classes Bootstrap aos links
 */
function theme_bootstrap_menu_link_classes($atts, $item, $args)
{
    if ($args->theme_location === 'header_menu') {

        $classes = ['nav-link'];

        if (in_array('menu-item-has-children', $item->classes)) {

            $classes[] = 'dropdown-toggle';

            $atts['data-bs-toggle'] = 'dropdown';
            $atts['aria-expanded'] = 'false';
            $atts['role'] = 'button';
        }

        $atts['class'] = implode(' ', $classes);
    }

    return $atts;
}
//add_filter('nav_menu_link_attributes', 'theme_bootstrap_menu_link_classes', 10, 3);


/**
 * Troca sub-menu por dropdown-menu
 */
function theme_bootstrap_submenu_classes($classes, $args, $depth)
{
    $classes = ['dropdown-menu'];

    return $classes;
}
//add_filter('nav_menu_submenu_css_class', 'theme_bootstrap_submenu_classes', 10, 3);
add_action('wp_enqueue_scripts', 'meu_tema_assets');


// Função para criação de menu na área administrativa
function create_post_types()
{

    // NOTÍCIAS
    $labels_noticia = array(
        'name'                => __('Notícias', 'igesdf-2026'),
        'singular_name'       => __('Notícia', 'igesdf-2026'),
        'add_new'             => __('Adicionar nova', 'igesdf-2026'),
        'add_new_item'        => __('Adicionar nova notícia', 'igesdf-2026'),
        'edit_item'           => __('Editar notícia', 'igesdf-2026'),
        'new_item'            => __('Nova notícia', 'igesdf-2026'),
        'all_items'           => __('Todas as notícias', 'igesdf-2026'),
        'view_item'           => __('Ver notícia', 'igesdf-2026'),
        'search_items'        => __('Pesquisar notícias', 'igesdf-2026'),
        'not_found'           => __('Nenhuma notícia encontrada', 'igesdf-2026'),
        'not_found_in_trash'  => __('Nenhuma notícia no lixo', 'igesdf-2026'),
        'menu_name'           => __('Notícia', 'igesdf-2026'),
    );

    $supports_noticia = array('title', 'editor', 'thumbnail');

    $slug_noticia = get_theme_mod('noticia_permalink');
    $slug_noticia = (empty($slug_noticia)) ? 'noticia' : $slug_noticia;

    $args_noticia = array(
        'labels'              => $labels_noticia,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => array('slug' => $slug_noticia),
        'capability_type'     => 'post',
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => 4,
        'supports'            => $supports_noticia,
        'taxonomies'          => array('category'),
        'menu_icon'           => 'dashicons-editor-table',
        'show_in_rest'        => true, // Adicionado para compatibilidade com Gutenberg
        'description'         => 'Fique por dentro das últimas notícias do instituto',
    );
    register_post_type('noticia', $args_noticia);


    // EVENTOS
    $labels_evento = array(
        'name'                => __('Eventos', 'igesdf-2026'),
        'singular_name'       => __('Evento', 'igesdf-2026'),
        'add_new'             => __('Adicionar novo', 'igesdf-2026'),
        'add_new_item'        => __('Adicionar novo evento', 'igesdf-2026'),
        'edit_item'           => __('Editar evento', 'igesdf-2026'),
        'new_item'            => __('Novo evento', 'igesdf-2026'),
        'all_items'           => __('Todos os eventos', 'igesdf-2026'),
        'view_item'           => __('Ver evento', 'igesdf-2026'),
        'search_items'        => __('Pesquisar eventos', 'igesdf-2026'),
        'not_found'           => __('Nenhum evento encontrado', 'igesdf-2026'),
        'not_found_in_trash'  => __('Nenhum evento no lixo', 'igesdf-2026'),
        'menu_name'           => __('Evento', 'igesdf-2026'),
    );

    $supports_evento = array('title', 'editor', 'thumbnail');

    $slug_evento = get_theme_mod('evento_permalink');
    $slug_evento = (empty($slug_evento)) ? 'evento' : $slug_evento;

    $args_evento = array(
        'labels'              => $labels_evento,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => array('slug' => $slug_evento),
        'capability_type'     => 'post',
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => 5,
        'supports'            => $supports_evento,
        'taxonomies'          => array('category'),
        'menu_icon'           => 'dashicons-sticky',
        'show_in_rest'        => true, // Adicionado para compatibilidade com Gutenberg
    );
    register_post_type('evento', $args_evento);


    // DOCUMENTOS
    $labels_documento = array(
        'name'                => __('Documentos', 'igesdf-2026'),
        'singular_name'       => __('Documento', 'igesdf-2026'),
        'add_new'             => __('Adicionar novo', 'igesdf-2026'),
        'add_new_item'        => __('Adicionar novo documento', 'igesdf-2026'),
        'edit_item'           => __('Editar documento', 'igesdf-2026'),
        'new_item'            => __('Novo documento', 'igesdf-2026'),
        'all_items'           => __('Todos os documentos', 'igesdf-2026'),
        'view_item'           => __('Ver documento', 'igesdf-2026'),
        'search_items'        => __('Pesquisar documentos', 'igesdf-2026'),
        'not_found'           => __('Nenhum documento encontrado', 'igesdf-2026'),
        'not_found_in_trash'  => __('Nenhum documento no lixo', 'igesdf-2026'),
        'menu_name'           => __('Documento', 'igesdf-2026'),
    );

    $supports_documento = array('title', 'editor', 'thumbnail');

    $slug_documento = get_theme_mod('documento_permalink');
    $slug_documento = (empty($slug_documento)) ? 'documento' : $slug_documento;

    $args_documento = array(
        'labels'              => $labels_documento,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => array('slug' => $slug_documento),
        'capability_type'     => 'post',
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => 6,
        'supports'            => $supports_documento,
        'taxonomies'          => array('category'),
        'menu_icon'           => 'dashicons-format-aside',
        'show_in_rest'        => true, // Adicionado para compatibilidade com Gutenberg
    );
    register_post_type('documento', $args_documento);


    // COMUNICADOS
    $labels_comunicado = array(
        'name'                => __('Comunicados', 'igesdf-2026'),
        'singular_name'       => __('Comunicado', 'igesdf-2026'),
        'add_new'             => __('Adicionar novo', 'igesdf-2026'),
        'add_new_item'        => __('Adicionar novo comunicado', 'igesdf-2026'),
        'edit_item'           => __('Editar comunicado', 'igesdf-2026'),
        'new_item'            => __('Novo comunicado', 'igesdf-2026'),
        'all_items'           => __('Todos os comunicados', 'igesdf-2026'),
        'view_item'           => __('Ver comunicado', 'igesdf-2026'),
        'search_items'        => __('Pesquisar comunicados', 'igesdf-2026'),
        'not_found'           => __('Nenhum comunicado encontrado', 'igesdf-2026'),
        'not_found_in_trash'  => __('Nenhum comunicado no lixo', 'igesdf-2026'),
        'menu_name'           => __('Comunicado', 'igesdf-2026'),
    );

    $supports_comunicado = array('title', 'editor', 'thumbnail');

    $slug_comunicado = get_theme_mod('comunicadoo_permalink');
    $slug_comunicado = (empty($slug_comunicado)) ? 'comunicado' : $slug_comunicado;

    $args_comunicado = array(
        'labels'              => $labels_comunicado,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => array('slug' => $slug_comunicado),
        'capability_type'     => 'post',
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => 7,
        'supports'            => $supports_comunicado,
        'taxonomies'          => array('category'),
        'menu_icon'           => 'dashicons-megaphone',
        'show_in_rest'        => true, // Adicionado para compatibilidade com Gutenberg
    );
    register_post_type('comunicado', $args_comunicado);


    // CLIPPING
    $labels_clipping = array(
        'name'                => __('Clipping', 'igesdf-2026'),
        'singular_name'       => __('Clipping', 'igesdf-2026'),
        'add_new'             => __('Adicionar nova', 'igesdf-2026'),
        'add_new_item'        => __('Adicionar nova notícia', 'igesdf-2026'),
        'edit_item'           => __('Editar notícia', 'igesdf-2026'),
        'new_item'            => __('Nova notícia', 'igesdf-2026'),
        'all_items'           => __('Todas as notícias', 'igesdf-2026'),
        'view_item'           => __('Ver notícia', 'igesdf-2026'),
        'search_items'        => __('Pesquisar notícias', 'igesdf-2026'),
        'not_found'           => __('Nenhuma notícia encontrado', 'igesdf-2026'),
        'not_found_in_trash'  => __('Nenhuma notícia no lixo', 'igesdf-2026'),
        'menu_name'           => __('Clipping', 'igesdf-2026'),
    );

    $supports_clipping = array('title', 'editor', 'thumbnail');

    $slug_clipping = get_theme_mod('clipping_permalink');
    $slug_clipping = (empty($slug_clipping)) ? 'clipping' : $slug_clipping;

    $args_clipping = array(
        'labels'              => $labels_clipping,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => array('slug' => $slug_clipping),
        'capability_type'     => 'post',
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => 8,
        'supports'            => $supports_clipping,
        'taxonomies'          => array('category'),
        'menu_icon'           => 'dashicons-editor-unlink',
        'show_in_rest'        => true, // Adicionado para compatibilidade com Gutenberg
    );
    register_post_type('clipping', $args_clipping);


    // CONVENIOS
    $labels_convenio = array(
        'name'                => __('Convênios', 'igesdf-2026'),
        'singular_name'       => __('Convênio', 'igesdf-2026'),
        'add_new'             => __('Adicionar novo', 'igesdf-2026'),
        'add_new_item'        => __('Adicionar novo convênio', 'igesdf-2026'),
        'edit_item'           => __('Editar convênio', 'igesdf-2026'),
        'new_item'            => __('Novo convênio', 'igesdf-2026'),
        'all_items'           => __('Todos os convênios', 'igesdf-2026'),
        'view_item'           => __('Ver convênio', 'igesdf-2026'),
        'search_items'        => __('Pesquisar convênios', 'igesdf-2026'),
        'not_found'           => __('Nenhum convênio encontrado', 'igesdf-2026'),
        'not_found_in_trash'  => __('Nenhum convênio no lixo', 'igesdf-2026'),
        'menu_name'           => __('Convênios', 'igesdf-2026'),
    );

    $supports_convenio = array('title', 'editor', 'thumbnail');

    $slug_convenio = get_theme_mod('convenio_permalink');
    $slug_convenio = (empty($slug_convenio)) ? 'convenio' : $slug_convenio;

    $args_convenio = array(
        'labels'              => $labels_convenio,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => array('slug' => $slug_convenio),
        'capability_type'     => 'post',
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => 9,
        'supports'            => $supports_convenio,
        'taxonomies'          => array('category'),
        'menu_icon'           => 'dashicons-portfolio',
        'show_in_rest'        => true, // Adicionado para compatibilidade com Gutenberg
    );
    register_post_type('convenio', $args_convenio);
}
add_action('init', 'create_post_types');

// Mostra os posts personalizados nas consultas padrões do wordpress
function add_my_post_types_to_query($query)
{
    if (is_home() && $query->is_main_query()) {
        $query->set('post_type', array('post', 'noticia', 'evento', 'documento', 'comunicado')); // Adicionado 'post' para incluir posts padrão
    }
    return $query;
}
add_action('pre_get_posts', 'add_my_post_types_to_query');


function order_category_archives($query)
{
    if (is_category() && $query->is_main_query()) { // is_category() can specify a category, if necessary
        $query->set('post_type', array('noticia', 'evento', 'comunicado', 'convenio'));
        $query->set('orderby', 'date');
        $query->set('order', 'DESC');
    }
    if (is_category(array('documentos', 'compliance-documentos', 'processos-e-projetos-documentos', 'compras-e-contratacao-documentos', 'das-documentos', 'ensino-e-pesquisa-documentos', 'financeiro-documentos', 'juridico-documentos', 'legislacao-documentos', 'recursos-humanos-documentos', 'tecnologia-documentos', 'ascom', 'diretoria-clinica-documentos', 'documentos-ti')) && $query->is_main_query()) { // is_category() can specify a category, if necessary
        $query->set('post_type', array('documento'));
        $query->set('orderby', 'title');
        $query->set('order', 'ASC');
        $query->set('posts_per_page', '11');
    }
    if (is_category(array('igesdf-na-midia')) && $query->is_main_query()) { // is_category() can specify a category, if necessary
        $query->set('post_type', array('clipping'));
        $query->set('meta_key', 'data');
        $query->set('orderby', 'meta_value_num');
        $query->set('order', 'DESC');
        $query->set('posts_per_page', '12');
    }
}
add_action('pre_get_posts', 'order_category_archives');

// Register Custom Navigation Walker (se o arquivo class-wp-bootstrap-navwalker.php existir no seu tema atual)
if (file_exists(get_template_directory() . '/class-wp-bootstrap-navwalker.php')) {
    require_once get_template_directory() . '/class-wp-bootstrap-navwalker.php';
}

// Adiciona colunas (Thumb + ID)
add_filter('manage_posts_columns', function ($columns) {
    $new = [];

    // ID primeiro
    $new['post_id'] = 'ID';

    foreach ($columns as $key => $value) {
        // Thumb antes do título
        if ($key === 'title') {
            $new['thumbnail'] = 'Thumb';
        }
        $new[$key] = $value;
    }

    return $new;
});

// Preenche as colunas
add_action('manage_posts_custom_column', function ($column, $post_id) {
    if ($column === 'thumbnail') {
        if (has_post_thumbnail($post_id)) {
            echo get_the_post_thumbnail($post_id, [60, 60]);
        } else {
            echo '—';
        }
    }

    if ($column === 'post_id') {
        echo $post_id;
    }
}, 10, 2);

// Estilo das colunas
add_action('admin_head', function () {
    echo '<style>
        .column-thumbnail {
            width: 80px;
            object-fit: cover;
            text-align: center;
        }
        .column-post_id {
            width: 80px;
            font-weight: bold;
        }
    </style>';
});

function breadcrumb()
{

    if (is_singular()) {

        $post_type = get_post_type();
        echo '<nav class="mb-4 text-muted" aria-label="breadcrumb">';
        echo '<ol class="breadcrumb list-">';
        echo '<li class="breadcrumb-item">';
        echo '<a href="' . home_url() . '"><i class="fa fa-home"></i>Início</a>';
        echo '</li>';

        if ($post_type !== 'post' && $post_type !== 'page') {

            $obj = get_post_type_object($post_type);

            if ($obj && $obj->has_archive) {
                echo '<li class="breadcrumb-item">';
                echo '<a href="' . get_post_type_archive_link($post_type) . '">';
                echo esc_html($obj->labels->name);
                echo '</a>';
                echo '</li>';
            }
        }

        // Título reduzido
        $title = get_the_title();

        if (mb_strlen($title) > 40) {
            $title = mb_substr($title, 0, 40) . '...';
        }

        echo '<li class="breadcrumb-item active" aria-current="page">';
        echo esc_html($title);
        echo '</li>';
        echo '</ol>';
        echo '</nav>';

    }
}

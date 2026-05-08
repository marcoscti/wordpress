<?php
function theme_enqueue_scripts()
{
    wp_enqueue_style(
        'tailwind-css',
        get_template_directory_uri() . '/dist/output.css',
        [],
        filemtime(get_template_directory() . '/dist/output.css')
    );

    wp_enqueue_script(
        'theme-menu-toggle',
        get_template_directory_uri() . '/js/tailwind-theme.js',
        [],
        filemtime(get_template_directory() . '/js/tailwind-theme.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'theme_enqueue_scripts');

register_nav_menus([
    'primary' => 'Menu Principal',
    'secondary' => 'Menu Secundário'
]);

<?php

function my_theme_styles() {
    wp_enqueue_style(
        'my-theme-style',
        get_template_directory_uri() . '/assets/css/style.css',
        [],
        '1.0'
    );
}

add_action('wp_enqueue_scripts', 'my_theme_styles');
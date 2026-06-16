<?php
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    register_post_type('feed-social', [
        'label' => 'Feed Social',
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-share',
        'supports' => ['title', 'editor', 'thumbnail', 'revisions', 'author']
    ]);
});

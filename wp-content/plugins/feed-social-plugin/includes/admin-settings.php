<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_menu_page('Configurações Feed', 'Feed Social', 'manage_options', 'feed-social-settings', 'fs_settings_page_callback', 'dashicons-share');
});

function fs_settings_page_callback() {
    echo '<div class="wrap"><h1>Configurações do Feed Social</h1><p>Em breve...</p></div>';
}
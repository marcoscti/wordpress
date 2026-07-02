<?php
/**
 * Plugin Name: Feed Social
 * Description: Feed social com mídia, galeria, curtidas, comentários, scroll infinito e notificações em tempo real (SSE).
 * Version: 2.0.1
 * Author: Marcos Cordeiro
 * Text Domain: feed-social
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

define('FS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('FS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FS_DB_VERSION', '2.0.1');

require_once FS_PLUGIN_PATH . 'includes/database.php';
require_once FS_PLUGIN_PATH . 'includes/post-type.php';
require_once FS_PLUGIN_PATH . 'includes/metaboxes.php';
require_once FS_PLUGIN_PATH . 'includes/shortcode.php';
require_once FS_PLUGIN_PATH . 'includes/rest-api.php';
require_once FS_PLUGIN_PATH . 'includes/shortcode-story.php';
require_once FS_PLUGIN_PATH . 'includes/sse.php';
//require_once FS_PLUGIN_PATH . 'includes/admin-settings.php';

add_action('plugins_loaded', function () {
    if (get_option('fs_db_version') !== FS_DB_VERSION) {
        fs_create_database_tables();
    }
});

register_activation_hook(__FILE__, function () {
    fs_create_database_tables();
    delete_transient('fs_feed_page_url');
    flush_rewrite_rules();
});

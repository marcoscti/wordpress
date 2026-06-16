<?php
/**
 * Plugin Name: Feed Social
 * Description: Feed social com mídia, curtidas, comentários, SSE e scroll infinito.
 * Version: 0.1.0
 * Author: Marcos
 */

if (!defined('ABSPATH')) exit;

define('FS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('FS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FS_DB_VERSION', '1.0.0');

require_once FS_PLUGIN_PATH . 'includes/database.php';
require_once FS_PLUGIN_PATH . 'includes/post-type.php';
require_once FS_PLUGIN_PATH . 'includes/metaboxes.php';
require_once FS_PLUGIN_PATH . 'includes/shortcode.php';
require_once FS_PLUGIN_PATH . 'includes/rest-api.php';
require_once FS_PLUGIN_PATH . 'includes/sse.php';
//require_once FS_PLUGIN_PATH . 'includes/admin-settings.php';

// Ativação do Plugin e criação de tabelas
register_activation_hook(__FILE__, function () {
    fs_create_database_tables();
    flush_rewrite_rules();
});

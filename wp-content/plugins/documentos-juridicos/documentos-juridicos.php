<?php
/**
 * Plugin Name: Documentos Jurídicos
 * Description: Repositório de PDFs jurídicos com CPT, filtros, busca AJAX, paginação, mais acessados e estrutura preparada para processamento por IA.
 * Version: 1.0.0
 * Author: Marcos Cordeiro Soares
 * Author URI: https://github.com/marcoscti
 * Requires at least: 5.0
 * Text Domain: documentos-juridicos
 */

if (!defined('ABSPATH')) exit;

define('DJ_VERSION', '1.0.0');
define('DJ_PATH', plugin_dir_path(__FILE__));
define('DJ_URL', plugin_dir_url(__FILE__));

require_once DJ_PATH . 'includes/post-type.php';
require_once DJ_PATH . 'includes/taxonomies.php';
require_once DJ_PATH . 'includes/api.php';
require_once DJ_PATH . 'includes/shortcode.php';
require_once DJ_PATH . 'includes/ai.php';
require_once DJ_PATH . 'admin/admin.php';

register_activation_hook(__FILE__, function () {
    dj_register_post_type();
    dj_register_taxonomies();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

add_action('plugins_loaded', function () {
    load_plugin_textdomain('documentos-juridicos', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

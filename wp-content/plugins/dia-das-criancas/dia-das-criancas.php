<?php
/**
 * Plugin Name: Dia das Crianças — Quiz de Nostalgia
 * Description: Motor de quiz para a campanha "Quanto de criança ainda existe em você?".
 * Version: 0.3.1
 * Author: Marcos Cordeiro Soares
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: dia-das-criancas
 */

if (!defined('ABSPATH')) exit;

define('DCDC_VERSION', '0.3.1');
define('DCDC_FILE', __FILE__);
define('DCDC_DIR', plugin_dir_path(__FILE__));
define('DCDC_URL', plugin_dir_url(__FILE__));

require_once DCDC_DIR . 'includes/class-dcdc-db.php';
require_once DCDC_DIR . 'includes/class-dcdc-categories.php';
require_once DCDC_DIR . 'includes/class-dcdc-questions.php';
require_once DCDC_DIR . 'includes/class-dcdc-quiz.php';
require_once DCDC_DIR . 'includes/class-dcdc-admin.php';

register_activation_hook(__FILE__, array('DCDC_DB', 'activate'));

add_action('plugins_loaded', function () {
    DCDC_DB::activate();
    DCDC_Categories::init();
    DCDC_Questions::init();
    DCDC_Quiz::init();
    DCDC_Admin::init();
});

add_action('wp_enqueue_scripts', function () {
    wp_register_style('dcdc-public', DCDC_URL . 'public/css/quiz.css', array(), DCDC_VERSION);
    wp_register_script('dcdc-public', DCDC_URL . 'public/js/quiz.js', array(), DCDC_VERSION, true);
    wp_register_script('dcdc-ranking', DCDC_URL . 'public/js/ranking.js', array(), DCDC_VERSION, true);
});

add_shortcode('dcdc_quiz', function ($atts) {
    $atts = shortcode_atts(array('campaign' => 'dia-das-criancas-2026'), $atts, 'dcdc_quiz');

    wp_enqueue_style('dcdc-public');
    wp_enqueue_script('dcdc-public');

    wp_localize_script('dcdc-public', 'DCDC_DATA', array(
        'restUrl' => esc_url_raw(rest_url('dcdc/v1/')),
        'nonce'   => wp_create_nonce('wp_rest'),
        'campaign'=> sanitize_key($atts['campaign']),
    ));

    ob_start();
    include DCDC_DIR . 'public/views/quiz.php';
    return ob_get_clean();
});

add_shortcode('dcdc_ranking', function ($atts) {
    $atts = shortcode_atts(array(
        'campaign' => 'dia-das-criancas-2026',
        'limit' => 10,
        'title' => 'Ranking',
    ), $atts, 'dcdc_ranking');

    $campaign = sanitize_key($atts['campaign']);
    $limit = max(1, min(25, absint($atts['limit'])));

    wp_enqueue_style('dcdc-public');
    wp_enqueue_script('dcdc-ranking');
    wp_localize_script('dcdc-ranking', 'DCDC_RANKING_DATA', array(
        'restUrl' => esc_url_raw(rest_url('dcdc/v1/')),
        'campaign' => $campaign,
        'limit' => $limit,
    ));

    ob_start();
    include DCDC_DIR . 'public/views/ranking.php';
    return ob_get_clean();
});

<?php
/**
 * Plugin Name: Album Copa 2026
 * Description: Gerencia o envio, aprovação e exibição de figurinhas para o álbum da Copa 2026.
 * Version: 1.1.3
 * Author: Marcos Cordeiro
 * Text Domain: album-copa-2026
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'ALBUM_COPA_2026_VERSION', '1.1.3' );
define( 'ALBUM_COPA_2026_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALBUM_COPA_2026_URL', plugin_dir_url( __FILE__ ) );

// Include necessary files
require_once ALBUM_COPA_2026_DIR . 'includes/class-album-copa-2026-cpt.php';
require_once ALBUM_COPA_2026_DIR . 'includes/admin.php';
require_once ALBUM_COPA_2026_DIR . 'includes/frontend.php';
require_once ALBUM_COPA_2026_DIR . 'includes/settings.php';

class Album_Copa_2026 {

    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
    }

    public function init_plugin() {
        load_plugin_textdomain( 'album-copa-2026', false, basename( ALBUM_COPA_2026_DIR ) . '/languages' );

        new Album_Copa_2026_CPT();
        new Album_Copa_2026_Admin();
        new Album_Copa_2026_Frontend();
        new Album_Copa_2026_Settings();
    }
}

new Album_Copa_2026();
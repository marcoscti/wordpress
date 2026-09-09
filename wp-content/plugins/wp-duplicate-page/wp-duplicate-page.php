<?php
/**
 * Plugin Name: WP Duplicate Page
 * Plugin URI: https://ninjateam.org
 * Description: Duplicate Posts, Pages and Custom Post Types.
 * Version: 1.8.5
 * Author: NinjaTeam
 * Author URI: https://ninjateam.org
 * Text Domain: wp-duplicate-page
 * Domain Path: /i18n/languages/
 *
 * @package NjtDuplicate
 */

namespace NjtDuplicate;

defined( 'ABSPATH' ) || exit;

define( 'NJT_DUPLICATE_VERSION', '1.8.5' );
define( 'NJT_DUPLICATE_DOMAIN', 'wp-duplicate-page' );

define( 'NJT_DUPLICATE_PLUGIN_DIR', __DIR__ );
define( 'NJT_DUPLICATE_PLUGIN_NAME', plugin_basename( __FILE__ ) );
define( 'NJT_DUPLICATE_PLUGIN_URL', plugins_url( '', __FILE__ ) );
define( 'NJT_DUPLICATE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

spl_autoload_register(
	function ( $class ) {
		$prefix   = __NAMESPACE__; // project-specific namespace prefix
		$base_dir = __DIR__ . '/includes'; // base directory for the namespace prefix

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) { // does the class use the namespace prefix?
			return; // no, move to the next registered auto loader
		}

		$relative_class_name = substr( $class, $len );

		// replace the namespace prefix with the base directory, replace namespace
		// separators with directory separators in the relative class name, append
		// with .php
		$file = $base_dir . str_replace( '\\', '/', $relative_class_name ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

if ( file_exists( __DIR__ . '/recommended-modules/loader.php' ) ) {
	require_once __DIR__ . '/recommended-modules/loader.php';
}

// Register this plugin as the ads-toggle "consumer" for every ad module it bundles above, so the
// single toggle rendered on the settings page (Page\Settings) controls all of them together. Must
// run after the loader.php require above (Registry class must already exist), and before
// plugins_loaded:0 (Registry::load_winners()) so the registration isn't dropped as "late".
if ( class_exists( '\YayRecommendedModules\Registry' ) ) {
	foreach (
		array(
			'filebird-plugins-page-notification',
			'filebird-sidebar-popup',
			'yaymail-wc-settings-banner',
			'yaymail-wc-settings-email-column',
		) as $njtDuplicateAdSlug
	) {
		\YayRecommendedModules\Registry::register_ads_consumer( NJT_DUPLICATE_DOMAIN, $njtDuplicateAdSlug );
	}
	unset( $njtDuplicateAdSlug );
}

function init() {
	I18n::loadPluginTextdomain();
	Page\Settings::getInstance();
}
add_action( 'plugins_loaded', 'NjtDuplicate\\init' );

register_activation_hook( __FILE__, array( 'NjtDuplicate\\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'NjtDuplicate\\Plugin', 'deactivate' ) );

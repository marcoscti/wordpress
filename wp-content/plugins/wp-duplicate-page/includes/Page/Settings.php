<?php
namespace NjtDuplicate\Page;

use NjtDuplicate\Classes\ButtonDuplicate;
use NjtDuplicate\Classes\EditorDuplicate;

defined( 'ABSPATH' ) || exit;
/**
 * Settings Page
 */
class Settings {
	protected static $instance = null;

	public static function getInstance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private $pageId = null;

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'settingsMenu' ) );
		// Priority 20 (after the ads-toggle module's own default-priority admin_enqueue_scripts
		// hook, see recommended-modules/ads-toggle/main.php): enqueueAdminScripts() -> enqueueAdsToggle()
		// needs the 'njt-ads-toggle' handle already wp_register_script()'d before it can
		// wp_add_inline_script() onto it — that registration happens in that other module's hook,
		// which must run first.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminScripts' ), 20 );
		add_filter( 'plugin_action_links_' . NJT_DUPLICATE_PLUGIN_NAME, array( $this, 'addActionLinks' ) );
		add_action( 'wp_ajax_njt_duplicate_page_settings', array( $this, 'saveSettings' ) );
		add_action( 'wp_ajax_njt_duplicate_page_track_review', array( $this, 'trackReview' ) );
		// Add button link to post, page, post type
		ButtonDuplicate::getInstance();
		$duplicateInEditor = get_option( 'njt_duplicate_in_editor', true );
		if ( $duplicateInEditor ) {
			EditorDuplicate::getInstance();
		}
	}

	public function settingsMenu() {
		add_submenu_page( 'options-general.php', __( 'Duplicate Page Settings', 'wp-duplicate-page' ), __( 'Duplicate Page', 'wp-duplicate-page' ), 'manage_options', $this->getPageId(), array( $this, 'settingsPage' ) );
	}

	public function settingsPage() {
		$viewPath = NJT_DUPLICATE_PLUGIN_PATH . 'views/pages/html-settings.php';
		include_once $viewPath;
	}
	public function addActionLinks( $links ) {
		$settingsLinks = array(
			'<a href="' . admin_url( 'options-general.php?page=' . $this->getPageId() ) . '">' . __( 'Settings', 'wp-duplicate-page' ) . '</a>',
		);
		return array_merge( $settingsLinks, $links );
	}

	public function enqueueAdminScripts( $screenId ) {
		if ( $screenId === 'settings_page_wp-duplicate-page-settings' ) {
			$scriptId   = $this->getPageId();
			$footerText = sprintf(
				/* translators: 1: Plugin Title, 2: Link to review */
				__( 'Enjoyed %1$s? Please leave us a %2$s rating. We really appreciate your support!', 'wp-duplicate-page' ),
				'<strong>' . esc_html__( 'WP Duplicate Page', 'wp-duplicate-page' ) . '</strong>',
				'<a href="https://wordpress.org/support/plugin/wp-duplicate-page/reviews/?filter=5/#new-post/" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
			);
			wp_enqueue_style( $scriptId, NJT_DUPLICATE_PLUGIN_URL . '/assets/css/admin-setting.css', array(), NJT_DUPLICATE_VERSION );
			wp_enqueue_script( $scriptId, NJT_DUPLICATE_PLUGIN_URL . '/assets/js/admin-setting.js', array( 'jquery' ), NJT_DUPLICATE_VERSION, true );
			wp_localize_script(
				$scriptId,
				'njt_duplicate_page',
				array(
					'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
					'ajaxNonce'  => wp_create_nonce( 'wp_rest' ),
					'footerText' => $footerText,
				)
			);

			$this->enqueueAdsToggle();
		}
	}

	/**
	 * Render the recommended-modules ads on/off toggle into #njt-duplicate-ads-toggle (markup in
	 * html-settings.php) via the shared njt-ads-toggle widget (recommended-modules/ads-toggle).
	 * Fail-open: if that module isn't bundled/loaded, njt_ads_toggle_consumer_is_enabled() won't
	 * exist and this silently no-ops — no toggle is shown (matches the guard in html-settings.php).
	 */
	private function enqueueAdsToggle() {
		if ( ! function_exists( 'njt_ads_toggle_consumer_is_enabled' ) || ! wp_script_is( 'njt-ads-toggle', 'registered' ) ) {
			return;
		}

		wp_enqueue_script( 'njt-ads-toggle' );
		wp_enqueue_style( 'njt-ads-toggle' );

		// Attached as an inline script on the 'njt-ads-toggle' handle (rather than echoed directly
		// into html-settings.php) so it prints after that handle's own script/jQuery, both in the
		// footer — echoing it inline in the page body would run before those footer scripts load.
		wp_add_inline_script(
			'njt-ads-toggle',
			sprintf(
				'jQuery(function ($) { if (window.njtAdsToggleRender) { njtAdsToggleRender("#njt-duplicate-ads-toggle", %s); } });',
				wp_json_encode(
					array(
						'consumerSlug' => NJT_DUPLICATE_DOMAIN,
						'title'        => __( 'Show Recommended Plugins', 'wp-duplicate-page' ),
						'description'  => __( 'Enable this to see handy plugin recommendations and occasional offers. Disable anytime to turn all of them off.', 'wp-duplicate-page' ),
						'checked'      => njt_ads_toggle_consumer_is_enabled( NJT_DUPLICATE_DOMAIN ),
					)
				)
			)
		);
	}

	public function getPageId() {
		if ( null == $this->pageId ) {
			$this->pageId = NJT_DUPLICATE_DOMAIN . '-settings';
		}
		return $this->pageId;
	}

	function sanitizeTextOrArrayField( $arrayOrString ) {
		if ( is_string( $arrayOrString ) ) {
			$arrayOrString = sanitize_text_field( $arrayOrString );
		} elseif ( is_array( $arrayOrString ) ) {
			foreach ( $arrayOrString as $key => &$value ) {
				if ( is_array( $value ) ) {
					$value = sanitizeTextOrArrayField( $value );
				} else {
					$value = sanitize_text_field( $value );
				}
			}
		}
		return $arrayOrString;
	}

	function trackReview() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_POST['njtDuplicateNonce'] ) || ! wp_verify_nonce( $_POST['njtDuplicateNonce'], 'wp_rest' ) ) {
			return;
		}
		update_option( 'njt_duplicate_reviewed', '1' );
		wp_die();
	}

	function saveSettings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_POST['njtDuplicateNonce'] ) || ! wp_verify_nonce( $_POST['njtDuplicateNonce'], 'wp_rest' ) ) {
			return;
		}

		$roles           = isset( $_POST['njtDuplicateRoles'] ) ? $this->sanitizeTextOrArrayField( (array) $_POST['njtDuplicateRoles'] ) : array();
		$postTypes       = isset( $_POST['njtDuplicatePostTypes'] ) ? $this->sanitizeTextOrArrayField( (array) $_POST['njtDuplicatePostTypes'] ) : array();
		$textLink        = isset( $_POST['njtDuplicateTextLink'] ) ? $this->sanitizeTextOrArrayField( $_POST['njtDuplicateTextLink'] ) : '';
		$editorDuplicate = isset( $_POST['njtDuplicateInEditor'] ) ? $this->sanitizeTextOrArrayField( $_POST['njtDuplicateInEditor'] ) : '1';

		update_option( 'njt_duplicate_roles', $roles );
		update_option( 'njt_duplicate_post_types', $postTypes );
		update_option( 'njt_duplicate_text_link', $textLink );
		update_option( 'njt_duplicate_in_editor', $editorDuplicate );
		global $wp_roles;
		$roles              = $wp_roles->get_names();
		$duplicateUserRoles = get_option( 'njt_duplicate_roles' );

		if ( $duplicateUserRoles == false || $duplicateUserRoles == '' ) {
			$duplicateUserRoles = array();
		}

		foreach ( $roles as $name => $displayName ) {
			$role = get_role( $name );

			/* If the role doesn't have the capability and it was selected, add it. */
			if ( ! $role->has_cap( 'njt_duplicate_page' ) && in_array( $name, $duplicateUserRoles ) ) {
				$role->add_cap( 'njt_duplicate_page' );
			}

			/* If the role has the capability and it wasn't selected, remove it. */
			elseif ( $role->has_cap( 'njt_duplicate_page' ) && ! in_array( $name, $duplicateUserRoles ) ) {
				$role->remove_cap( 'njt_duplicate_page' );
			}
		}
		// Optionally (if needed).
		wp_reset_query();
		wp_reset_postdata();

		// To avoid error 500 (don't forget this)
		wp_die();
	}
}

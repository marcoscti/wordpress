<?php

defined( 'ABSPATH' ) || exit;

/**
 * Public ads-toggle helper functions (global) for ad-rendering modules and consumer settings
 * pages.
 *
 * Self-contained: reads Registry registrations + stored options DIRECTLY, so it works the
 * instant this file loads (plugins_loaded:0) — with no dependency on the module's boot/hooks.
 * That is why an ad module can gate its own rendering during `plugins_loaded`.
 */

if ( ! function_exists( 'njt_ads_toggle_option_name' ) ) {
	/**
	 * @internal Shared option-key builder — keep in sync between the two public functions below.
	 */
	function njt_ads_toggle_option_name( $consumer_slug ) {
		return 'njt_ads_toggle_' . sanitize_key( $consumer_slug );
	}
}

if ( ! function_exists( 'njt_ads_toggle_consumer_is_enabled' ) ) {
	/**
	 * Is THIS SPECIFIC consumer's single ads switch on? Controls every ad module that consumer
	 * registered together — use this to populate that consumer's own toggle switch.
	 *
	 * @param string $consumer_slug This consumer's own registered slug.
	 * @return bool
	 */
	function njt_ads_toggle_consumer_is_enabled( $consumer_slug ) {
		// Stored as the string '0'/'1', never a raw boolean: update_option() treats a new value
		// of `false` as equal to its own "option not found" sentinel (also `false`), so writing
		// boolean false on the very first toggle-off silently no-ops and the option never gets
		// created. Strings sidestep that WP core footgun.
		return '0' !== get_option( njt_ads_toggle_option_name( $consumer_slug ), '1' );
	}
}

if ( ! function_exists( 'njt_ads_toggle_is_enabled' ) ) {
	/**
	 * Is the given ad module currently enabled (per at least one registered consumer)?
	 * Use this to gate whether an ad module renders — NOT to populate a toggle switch's
	 * checked state (that's a single consumer's own state; see
	 * njt_ads_toggle_consumer_is_enabled()).
	 *
	 * @param string $ad_slug Ad module's own Registry name.
	 * @return bool
	 */
	function njt_ads_toggle_is_enabled( $ad_slug ) {
		// Fail-open when an older Registry copy (from another consumer plugin) won the
		// class-definition race and doesn't have this method yet — see registry.php's
		// "FROZEN ABI" note. Without this guard, sites running an older sibling plugin
		if ( ! method_exists( '\YayRecommendedModules\Registry', 'get_ads_consumers' ) ) {
			return true;
		}

		$ad_slug   = sanitize_key( $ad_slug );
		$consumers = \YayRecommendedModules\Registry::get_ads_consumers( $ad_slug );

		if ( empty( $consumers ) ) {
			return true; // fail-open — nobody registered this ad at all
		}

		foreach ( $consumers as $consumer_slug ) {
			if ( njt_ads_toggle_consumer_is_enabled( $consumer_slug ) ) {
				return true; // at least one owning consumer still wants its ads on
			}
		}

		return false; // every registered consumer turned its ads off
	}
}

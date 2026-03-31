<?php
/**
 * Internationalization handler.
 *
 * Loads the plugin text domain for translations.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_I18n {

	/**
	 * Load the plugin text domain.
	 */
	public static function load_textdomain() {
		load_plugin_textdomain(
			'cocookie',
			false,
			dirname( plugin_basename( COCOOKIE_PLUGIN_DIR . 'cookie-consent-manager.php' ) ) . '/languages/'
		);
	}
}

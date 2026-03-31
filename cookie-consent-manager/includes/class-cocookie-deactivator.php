<?php
/**
 * Plugin deactivation handler.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_Deactivator {

	/**
	 * Run on plugin deactivation.
	 *
	 * Clears all scheduled cron events.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'ccm_central_daily_report' );
		wp_clear_scheduled_hook( 'cocookie_background_scan' );
	}
}

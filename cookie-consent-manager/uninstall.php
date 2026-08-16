<?php
/**
 * CoCookie Uninstall
 *
 * Removes all plugin data when uninstalled via WordPress admin.
 *
 * @package CoCookie
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop custom tables
$tables = array(
	$wpdb->prefix . 'cc_categories',
	$wpdb->prefix . 'cc_cookies',
	$wpdb->prefix . 'cc_consents',
	$wpdb->prefix . 'cc_scan_results',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// Delete all plugin options
$options = array(
	'ccm_db_version',
	'ccm_settings',
	'ccm_company_info',
	'ccm_blocked_cookies',
	'ccm_last_scan',
	'ccm_central_settings',
	'ccm_central_last_report',
	'ccm_central_last_error',
	'cocookie_db_version',
	'cocookie_needs_wizard',
	'cocookie_settings',
	'cocookie_company_info',
	'cocookie_blocked_cookies',
	'cocookie_last_scan',
	'cocookie_scan_error',
	'cocookie_central_settings',
	'cocookie_central_last_report',
	'cocookie_central_last_error',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Clear all plugin transients
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cocookie_%' OR option_name LIKE '_transient_timeout_cocookie_%' OR option_name LIKE '_transient_ccm_%' OR option_name LIKE '_transient_timeout_ccm_%'"
);

// Clear scheduled cron events
wp_clear_scheduled_hook( 'ccm_central_daily_report' );
wp_clear_scheduled_hook( 'cocookie_background_scan' );

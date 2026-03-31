<?php
/**
 * Database schema and seeding.
 *
 * Handles table creation and default category seeding.
 * Migrations are handled separately by CoCookie_Migrator.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_Database {

	/**
	 * Create all plugin tables using dbDelta.
	 */
	public static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$categories_table   = $wpdb->prefix . 'cc_categories';
		$cookies_table      = $wpdb->prefix . 'cc_cookies';
		$consents_table     = $wpdb->prefix . 'cc_consents';
		$scan_results_table = $wpdb->prefix . 'cc_scan_results';

		$sql = "CREATE TABLE {$categories_table} (
			id INT NOT NULL AUTO_INCREMENT,
			slug VARCHAR(50) NOT NULL,
			title VARCHAR(255) NOT NULL,
			description TEXT NOT NULL,
			is_required TINYINT(1) NOT NULL DEFAULT 0,
			sort_order INT NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			UNIQUE KEY slug (slug)
		) {$charset_collate};

		CREATE TABLE {$cookies_table} (
			id INT NOT NULL AUTO_INCREMENT,
			category_id INT NOT NULL,
			name VARCHAR(255) NOT NULL,
			provider VARCHAR(255) NOT NULL DEFAULT '',
			purpose TEXT NOT NULL,
			expiry VARCHAR(100) NOT NULL DEFAULT '',
			PRIMARY KEY (id),
			KEY category_id (category_id)
		) {$charset_collate};

		CREATE TABLE {$consents_table} (
			id BIGINT NOT NULL AUTO_INCREMENT,
			consent_uuid VARCHAR(36) NOT NULL,
			ip_hash VARCHAR(64) NOT NULL DEFAULT '',
			user_agent VARCHAR(500) NOT NULL DEFAULT '',
			categories TEXT NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY consent_uuid (consent_uuid),
			KEY created_at (created_at)
		) {$charset_collate};

		CREATE TABLE {$scan_results_table} (
			id INT NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			value_sample VARCHAR(255) NOT NULL DEFAULT '',
			domain VARCHAR(255) NOT NULL DEFAULT '',
			storage_type VARCHAR(20) NOT NULL DEFAULT 'cookie',
			suggested_category VARCHAR(50) NOT NULL DEFAULT '',
			suggested_provider VARCHAR(255) NOT NULL DEFAULT '',
			suggested_purpose TEXT NOT NULL,
			is_imported TINYINT(1) NOT NULL DEFAULT 0,
			scanned_at DATETIME NOT NULL,
			PRIMARY KEY (id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Seed default cookie categories if none exist.
	 */
	public static function seed_categories() {
		global $wpdb;
		$table = $wpdb->prefix . 'cc_categories';

		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $count > 0 ) {
			return;
		}

		$defaults = array(
			array(
				'slug'        => 'necessary',
				'title'       => 'Nödvändiga',
				'description' => 'Dessa cookies är nödvändiga för att webbplatsen ska fungera och kan inte stängas av.',
				'is_required' => 1,
				'sort_order'  => 1,
			),
			array(
				'slug'        => 'analytics',
				'title'       => 'Analys',
				'description' => 'Dessa cookies hjälper oss att förstå hur besökare använder webbplatsen.',
				'is_required' => 0,
				'sort_order'  => 2,
			),
			array(
				'slug'        => 'marketing',
				'title'       => 'Marknadsföring',
				'description' => 'Dessa cookies används för att visa relevanta annonser.',
				'is_required' => 0,
				'sort_order'  => 3,
			),
		);

		foreach ( $defaults as $cat ) {
			$wpdb->insert( $table, $cat );
		}
	}
}

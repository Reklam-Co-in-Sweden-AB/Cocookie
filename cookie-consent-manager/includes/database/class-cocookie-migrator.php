<?php
/**
 * Version-controlled database migrations.
 *
 * Only runs pending migrations by comparing the stored DB version
 * with COCOOKIE_VERSION. Each migration is a method named migrate_to_X_Y_Z().
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_Migrator {

	/**
	 * Run any pending migrations.
	 *
	 * Called on activation and on admin_init (but only when version mismatch detected).
	 */
	public static function run() {
		$current = get_option( 'cocookie_db_version', get_option( 'ccm_db_version', '0.0.0' ) );

		if ( version_compare( $current, COCOOKIE_VERSION, '>=' ) ) {
			return;
		}

		// Migration: add storage_type column to scan_results (from original plugin)
		if ( version_compare( $current, '1.1.0', '<' ) ) {
			self::migrate_to_1_1_0();
		}

		// Migration: migrate options from ccm_* to cocookie_* prefix
		if ( version_compare( $current, '2.0.0', '<' ) ) {
			self::migrate_to_2_0_0();
		}

		// Migration: lägg till unclassified-kategori och flytta okända scan-resultat
		if ( version_compare( $current, '2.1.0', '<' ) ) {
			self::migrate_to_2_1_0();
		}

		// Migration: nolla lagrade cookie-värden
		if ( version_compare( $current, '2.2.1', '<' ) ) {
			self::migrate_to_2_2_1();
		}

		update_option( 'cocookie_db_version', COCOOKIE_VERSION );
	}

	/**
	 * Check if migrations are needed and run them.
	 *
	 * Hooked to admin_init — only triggers when version mismatch is detected.
	 */
	public static function maybe_run() {
		$current = get_option( 'cocookie_db_version', '0.0.0' );
		if ( version_compare( $current, COCOOKIE_VERSION, '<' ) ) {
			self::run();
		}
	}

	/**
	 * v1.1.0: Add storage_type column to scan_results.
	 */
	private static function migrate_to_1_1_0() {
		global $wpdb;
		$table = $wpdb->prefix . 'cc_scan_results';

		$columns = $wpdb->get_col( "DESCRIBE {$table}", 0 );
		if ( is_array( $columns ) && ! in_array( 'storage_type', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN storage_type VARCHAR(20) NOT NULL DEFAULT 'cookie' AFTER domain" );
		}
	}

	/**
	 * v2.0.0: Migrate options from ccm_* to cocookie_* prefix.
	 *
	 * Copies old options to new prefix. Old options are preserved
	 * for one major version to allow rollback.
	 */
	private static function migrate_to_2_0_0() {
		$option_map = array(
			'ccm_settings'             => 'cocookie_settings',
			'ccm_company_info'         => 'cocookie_company_info',
			'ccm_blocked_cookies'      => 'cocookie_blocked_cookies',
			'ccm_last_scan'            => 'cocookie_last_scan',
			'ccm_central_settings'     => 'cocookie_central_settings',
			'ccm_central_last_report'  => 'cocookie_central_last_report',
			'ccm_central_last_error'   => 'cocookie_central_last_error',
		);

		foreach ( $option_map as $old_key => $new_key ) {
			$old_value = get_option( $old_key );
			if ( false !== $old_value && false === get_option( $new_key ) ) {
				update_option( $new_key, $old_value );
			}
		}

		// Existing installs skip the setup wizard
		update_option( 'cocookie_needs_wizard', false );
	}

	/**
	 * v2.1.0: Lägg till kategorin "unclassified" och flytta okända cookies dit.
	 *
	 * Idempotent — insert körs endast om slug saknas.
	 * Uppdaterar cc_scan_results men lämnar cc_cookies orört (admin-kurerad data).
	 */
	private static function migrate_to_2_1_0() {
		global $wpdb;
		$categories_table   = $wpdb->prefix . 'cc_categories';
		$scan_results_table = $wpdb->prefix . 'cc_scan_results';

		// 1. Säkerställ att unclassified-kategorin finns
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$categories_table} WHERE slug = %s",
			'unclassified'
		) );

		if ( ! $exists ) {
			$wpdb->insert( $categories_table, array(
				'slug'        => 'unclassified',
				'title'       => 'Okategoriserade',
				'description' => 'Cookies som ännu inte granskats och klassificerats. Kräver samtycke tills de flyttats till rätt kategori.',
				'is_required' => 0,
				'sort_order'  => 99,
			) );
		}

		// 2. Flytta okända scan-resultat från necessary till unclassified
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$scan_results_table}
			 SET suggested_category = %s
			 WHERE suggested_category = %s
			   AND suggested_provider = %s",
			'unclassified',
			'necessary',
			'Okänd'
		) );

		// 3. Invalidera pattern-cache så nya regler träder i kraft direkt
		if ( class_exists( 'CoCookie_Cookie_Patterns' ) ) {
			CoCookie_Cookie_Patterns::clear_cache();
		}

		// 4. Flagga för admin-notis
		set_transient( 'cocookie_show_rescan_notice', 1, DAY_IN_SECONDS );
	}

	/**
	 * v2.2.1: Nolla lagrade cookie-värden i scan-resultaten.
	 *
	 * Tidigare versioner sparade de 50 första tecknen av varje cookie- och
	 * localStorage-värde. Scanningen körs i en inloggad admins webbläsare, så
	 * värdena kunde innehålla sessionstokens från andra plugins. Kolumnen
	 * behålls (schemat är oförändrat) men töms och fylls inte längre.
	 */
	private static function migrate_to_2_2_1() {
		global $wpdb;
		$table = $wpdb->prefix . 'cc_scan_results';

		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET value_sample = %s WHERE value_sample != %s", '', '' ) );
	}
}

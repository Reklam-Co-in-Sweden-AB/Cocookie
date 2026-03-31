<?php
/**
 * Plugin activation handler.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_Activator {

	/**
	 * Run on plugin activation.
	 *
	 * Creates database tables, seeds default categories, and sets
	 * the wizard flag for fresh installs.
	 */
	public static function activate() {
		require_once COCOOKIE_PLUGIN_DIR . 'includes/database/class-cocookie-database.php';
		require_once COCOOKIE_PLUGIN_DIR . 'includes/database/class-cocookie-migrator.php';

		CoCookie_Database::create_tables();
		CoCookie_Database::seed_categories();
		CoCookie_Migrator::run();

		// Fresh install — show setup wizard
		if ( ! get_option( 'ccm_db_version' ) && ! get_option( 'cocookie_db_version' ) ) {
			update_option( 'cocookie_needs_wizard', true );
			set_transient( 'cocookie_wizard_redirect', true, 30 );
		}

		update_option( 'cocookie_db_version', COCOOKIE_VERSION );
	}
}

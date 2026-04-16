<?php
/**
 * Plugin Name: CoCookie
 * Description: GDPR-compliant cookie consent management for WordPress. Category-based consent with Google Consent Mode v2.
 * Version: 2.1.0
 * Author: CoCookie
 * License: GPL-2.0+
 * Text Domain: cocookie
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'COCOOKIE_VERSION', '2.1.0' );
define( 'COCOOKIE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'COCOOKIE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Backward compatibility constants
define( 'CCM_VERSION', COCOOKIE_VERSION );
define( 'CCM_PLUGIN_DIR', COCOOKIE_PLUGIN_DIR );
define( 'CCM_PLUGIN_URL', COCOOKIE_PLUGIN_URL );

// Activation and deactivation hooks
require_once COCOOKIE_PLUGIN_DIR . 'includes/class-cocookie-activator.php';
require_once COCOOKIE_PLUGIN_DIR . 'includes/class-cocookie-deactivator.php';

register_activation_hook( __FILE__, array( 'CoCookie_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CoCookie_Deactivator', 'deactivate' ) );

/**
 * Initialize the plugin on plugins_loaded.
 */
add_action( 'plugins_loaded', 'cocookie_init' );

function cocookie_init() {
	// Load text domain
	require_once COCOOKIE_PLUGIN_DIR . 'includes/class-cocookie-i18n.php';
	CoCookie_I18n::load_textdomain();

	// Run migrations if needed (version-controlled, not on every load)
	require_once COCOOKIE_PLUGIN_DIR . 'includes/database/class-cocookie-database.php';
	require_once COCOOKIE_PLUGIN_DIR . 'includes/database/class-cocookie-migrator.php';

	if ( is_admin() ) {
		CoCookie_Migrator::maybe_run();
	}

	// New model and API classes
	require_once COCOOKIE_PLUGIN_DIR . 'includes/models/class-cocookie-cookie-patterns.php';
	require_once COCOOKIE_PLUGIN_DIR . 'includes/models/class-cocookie-categories.php';
	require_once COCOOKIE_PLUGIN_DIR . 'includes/models/class-cocookie-consent.php';
	require_once COCOOKIE_PLUGIN_DIR . 'includes/api/class-cocookie-rest-consent.php';
	require_once COCOOKIE_PLUGIN_DIR . 'includes/api/class-cocookie-rest-config.php';
	require_once COCOOKIE_PLUGIN_DIR . 'includes/api/class-cocookie-rest-scanner.php';
	require_once COCOOKIE_PLUGIN_DIR . 'includes/api/class-cocookie-rest-stats.php';

	// Legacy classes (kept for backward compatibility during transition)
	require_once COCOOKIE_PLUGIN_DIR . 'includes/class-categories.php';
	require_once COCOOKIE_PLUGIN_DIR . 'includes/class-consent.php';
	require_once COCOOKIE_PLUGIN_DIR . 'includes/class-scanner.php';
	require_once COCOOKIE_PLUGIN_DIR . 'includes/class-cookie-scanner.php';
	require_once COCOOKIE_PLUGIN_DIR . 'includes/class-policy-generator.php';
	require_once COCOOKIE_PLUGIN_DIR . 'includes/class-central-reporter.php';
	require_once COCOOKIE_PLUGIN_DIR . 'admin/class-admin.php';
	require_once COCOOKIE_PLUGIN_DIR . 'public/class-public.php';

	// New admin, public and scanner system
	require_once COCOOKIE_PLUGIN_DIR . 'admin/class-cocookie-admin.php';
	require_once COCOOKIE_PLUGIN_DIR . 'admin/class-cocookie-setup-wizard.php';
	require_once COCOOKIE_PLUGIN_DIR . 'public/class-cocookie-public.php';
	require_once COCOOKIE_PLUGIN_DIR . 'includes/scanner/class-cocookie-background-scan.php';
	require_once COCOOKIE_PLUGIN_DIR . 'includes/class-cocookie-updater.php';

	// Register new REST API routes
	add_action( 'rest_api_init', array( 'CoCookie_REST_Consent', 'register_routes' ) );
	add_action( 'rest_api_init', array( 'CoCookie_REST_Config', 'register_routes' ) );
	add_action( 'rest_api_init', array( 'CoCookie_REST_Scanner', 'register_routes' ) );
	add_action( 'rest_api_init', array( 'CoCookie_REST_Stats', 'register_routes' ) );

	// Initialize new modules
	CoCookie_Admin::init();
	CoCookie_Setup_Wizard::init();
	CoCookie_Public::init();
	CoCookie_Background_Scan::init();
	CoCookie_Updater::init();

	// Initialize legacy modules (kept during transition — will be removed)
	CCM_Admin::init();
	CCM_Consent::init();
	CCM_Cookie_Scanner::init();
	CCM_Central_Reporter::init();
}

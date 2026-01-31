<?php
/**
 * Plugin Name: Cookie Consent Manager
 * Description: Server-side cookie consent management for WordPress. GDPR-compliant banner with category-based consent.
 * Version: 1.0.0
 * Author: Cookie Consent Manager
 * License: GPL-2.0+
 * Text Domain: cookie-consent-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CCM_VERSION', '1.1.0' );
define( 'CCM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CCM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once CCM_PLUGIN_DIR . 'includes/class-database.php';
require_once CCM_PLUGIN_DIR . 'includes/class-categories.php';
require_once CCM_PLUGIN_DIR . 'includes/class-consent.php';
require_once CCM_PLUGIN_DIR . 'includes/class-scanner.php';
require_once CCM_PLUGIN_DIR . 'includes/class-cookie-scanner.php';
require_once CCM_PLUGIN_DIR . 'includes/class-policy-generator.php';
require_once CCM_PLUGIN_DIR . 'includes/class-central-reporter.php';
require_once CCM_PLUGIN_DIR . 'admin/class-admin.php';
require_once CCM_PLUGIN_DIR . 'public/class-public.php';

register_activation_hook( __FILE__, array( 'CCM_Database', 'activate' ) );

add_action( 'plugins_loaded', 'ccm_init' );

function ccm_init() {
    CCM_Database::migrate();
    CCM_Admin::init();
    CCM_Public::init();
    CCM_Consent::init();
    CCM_Cookie_Scanner::init();
    CCM_Central_Reporter::init();
}

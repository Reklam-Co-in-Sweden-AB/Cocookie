<?php
/**
 * Plugin Name: CCM Central Dashboard
 * Description: Central dashboard for monitoring Cookie Consent Manager compliance across multiple sites. Includes PUB/DPA agreement generator.
 * Version: 1.0.0
 * Author: Cookie Consent Manager
 * License: GPL-2.0+
 * Text Domain: ccm-central
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CCMC_VERSION', '1.0.0' );
define( 'CCMC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CCMC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once CCMC_PLUGIN_DIR . 'includes/class-central-database.php';
require_once CCMC_PLUGIN_DIR . 'includes/class-central-api.php';
require_once CCMC_PLUGIN_DIR . 'includes/class-central-admin.php';
require_once CCMC_PLUGIN_DIR . 'includes/class-central-agreements.php';

register_activation_hook( __FILE__, array( 'CCMC_Database', 'activate' ) );

add_action( 'plugins_loaded', 'ccmc_init' );

function ccmc_init() {
    CCMC_API::init();
    CCMC_Admin::init();
}

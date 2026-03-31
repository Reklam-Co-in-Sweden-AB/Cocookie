<?php
/**
 * Admin router.
 *
 * Registers the CoCookie menu and delegates to page controllers.
 * This is a thin router — business logic lives in controllers.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_Admin {

	/**
	 * Initialize admin hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_global_assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_redirect_to_wizard' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_run_wizard' ) );
	}

	/**
	 * Register the CoCookie admin menu.
	 */
	public static function add_menu() {
		// Top-level menu
		add_menu_page(
			'CoCookie',
			'CoCookie',
			'manage_options',
			'cocookie',
			array( __CLASS__, 'render_page' ),
			'dashicons-shield',
			80
		);

		// Submenu pages
		$submenus = array(
			'cocookie'            => __( 'Dashboard', 'cocookie' ),
			'cocookie-cookies'    => __( 'Cookies', 'cocookie' ),
			'cocookie-banner'     => __( 'Banner', 'cocookie' ),
			'cocookie-compliance' => __( 'Compliance', 'cocookie' ),
			'cocookie-policies'   => __( 'Policyer', 'cocookie' ),
		);

		foreach ( $submenus as $slug => $title ) {
			add_submenu_page(
				'cocookie',
				$title . ' — CoCookie',
				$title,
				'manage_options',
				$slug,
				array( __CLASS__, 'render_page' )
			);
		}
	}

	/**
	 * Enqueue shared admin CSS on CoCookie pages.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_global_assets( $hook ) {
		if ( ! self::is_cocookie_page( $hook ) ) {
			return;
		}

		wp_enqueue_style(
			'cocookie-admin',
			COCOOKIE_PLUGIN_URL . 'admin/css/cocookie-admin.css',
			array(),
			COCOOKIE_VERSION
		);

		wp_enqueue_script(
			'cocookie-admin',
			COCOOKIE_PLUGIN_URL . 'admin/js/cocookie-admin.js',
			array(),
			COCOOKIE_VERSION,
			true
		);
	}

	/**
	 * Redirect to setup wizard on first activation.
	 *
	 * Two redirect paths:
	 * 1. Transient-based: fires immediately after plugin activation
	 * 2. Option-based: fires when navigating to any CoCookie page while wizard is pending
	 */
	public static function maybe_redirect_to_wizard() {
		if ( ! get_option( 'cocookie_needs_wizard' ) ) {
			return;
		}

		// Don't redirect during AJAX or bulk activation
		if ( wp_doing_ajax() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		// Already on the wizard page — don't redirect
		if ( isset( $_GET['page'] ) && 'cocookie-wizard' === $_GET['page'] ) {
			return;
		}

		// Path 1: Immediate redirect after activation (transient)
		if ( get_transient( 'cocookie_wizard_redirect' ) ) {
			delete_transient( 'cocookie_wizard_redirect' );
			wp_safe_redirect( admin_url( 'admin.php?page=cocookie-wizard' ) );
			exit;
		}

		// Path 2: Redirect when user navigates to a CoCookie page
		if ( isset( $_GET['page'] ) && strpos( $_GET['page'], 'cocookie' ) === 0 ) {
			wp_safe_redirect( admin_url( 'admin.php?page=cocookie-wizard' ) );
			exit;
		}
	}

	/**
	 * Render the current admin page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : 'cocookie';

		echo '<div class="wrap cocookie-admin-wrap">';

		// Load the appropriate controller
		switch ( $page ) {
			case 'cocookie-cookies':
				require_once COCOOKIE_PLUGIN_DIR . 'admin/controllers/class-cocookie-cookies-controller.php';
				CoCookie_Cookies_Controller::render();
				break;

			case 'cocookie-banner':
				require_once COCOOKIE_PLUGIN_DIR . 'admin/controllers/class-cocookie-banner-controller.php';
				CoCookie_Banner_Controller::render();
				break;

			case 'cocookie-compliance':
				require_once COCOOKIE_PLUGIN_DIR . 'admin/controllers/class-cocookie-compliance-controller.php';
				CoCookie_Compliance_Controller::render();
				break;

			case 'cocookie-policies':
				require_once COCOOKIE_PLUGIN_DIR . 'admin/controllers/class-cocookie-policies-controller.php';
				CoCookie_Policies_Controller::render();
				break;

			default:
				require_once COCOOKIE_PLUGIN_DIR . 'admin/controllers/class-cocookie-dashboard-controller.php';
				CoCookie_Dashboard_Controller::render();
				break;
		}

		echo '</div>';
	}

	/**
	 * Handle manual wizard trigger from dashboard.
	 */
	public static function handle_run_wizard() {
		if ( ! isset( $_GET['cocookie_run_wizard'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'cocookie_run_wizard' ) ) {
			return;
		}

		update_option( 'cocookie_needs_wizard', true );
		wp_safe_redirect( admin_url( 'admin.php?page=cocookie-wizard' ) );
		exit;
	}

	/**
	 * Check if the current admin page is a CoCookie page.
	 *
	 * @param string $hook Admin page hook.
	 * @return bool
	 */
	private static function is_cocookie_page( $hook ) {
		return strpos( $hook, 'cocookie' ) !== false;
	}
}

<?php
/**
 * Setup Wizard.
 *
 * 4-step first-run wizard: Scan -> Review -> Configure -> Done.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_Setup_Wizard {

	/**
	 * Initialize wizard hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
	}

	/**
	 * Add hidden wizard page (not in menu).
	 */
	public static function add_page() {
		add_submenu_page(
			null, // Hidden
			__( 'CoCookie Setup', 'cocookie' ),
			'',
			'manage_options',
			'cocookie-wizard',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Render the wizard.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$step = isset( $_GET['step'] ) ? intval( $_GET['step'] ) : 1;
		$step = max( 1, min( 4, $step ) );

		wp_enqueue_style( 'cocookie-admin', COCOOKIE_PLUGIN_URL . 'admin/css/cocookie-admin.css', array(), COCOOKIE_VERSION );
		wp_enqueue_script( 'cocookie-wizard', COCOOKIE_PLUGIN_URL . 'admin/js/cocookie-wizard.js', array(), COCOOKIE_VERSION, true );
		wp_localize_script( 'cocookie-wizard', 'cocookieWizard', array(
			'restUrl'        => esc_url_raw( rest_url() ),
			'nonce'          => wp_create_nonce( 'wp_rest' ),
			'siteUrl'        => esc_url( home_url( '/' ) ),
			'cleanScanNonce' => wp_create_nonce( 'ccm_clean_scan' ),
			'step2Url'       => admin_url( 'admin.php?page=cocookie-wizard&step=2' ),
			'step3Url'       => admin_url( 'admin.php?page=cocookie-wizard&step=3' ),
			'step4Url'       => admin_url( 'admin.php?page=cocookie-wizard&step=4' ),
		) );

		echo '<div class="wrap cocookie-admin-wrap">';
		echo '<div class="cocookie-wizard">';

		// Progress-indikator med spårningslinje
		$labels = array(
			1 => __( 'Skanna', 'cocookie' ),
			2 => __( 'Granska', 'cocookie' ),
			3 => __( 'Konfigurera', 'cocookie' ),
			4 => __( 'Klar', 'cocookie' ),
		);

		// Fyllnadsprocent: 0% vid steg 1, 33% vid 2, 66% vid 3, 100% vid 4
		$fill_percent = round( ( $step - 1 ) / 3 * 100 );

		echo '<div class="cocookie-wizard__progress">';
		echo '<div class="cocookie-wizard__progress-track">';
		echo '<div class="cocookie-wizard__progress-fill" style="width:' . esc_attr( $fill_percent ) . '%"></div>';
		echo '</div>';

		for ( $i = 1; $i <= 4; $i++ ) {
			$class = 'cocookie-wizard__step-indicator';
			if ( $i < $step ) {
				$class .= ' cocookie-wizard__step-indicator--done';
			} elseif ( $i === $step ) {
				$class .= ' cocookie-wizard__step-indicator--active';
			}
			echo '<div class="' . esc_attr( $class ) . '">';
			if ( $i < $step ) {
				// Avklarade steg visar bock-ikon
				echo '<span class="cocookie-wizard__step-number"><span class="dashicons dashicons-yes" style="font-size:16px;width:16px;height:16px;"></span></span>';
			} else {
				echo '<span class="cocookie-wizard__step-number">' . esc_html( $i ) . '</span>';
			}
			echo '<span class="cocookie-wizard__step-label">' . esc_html( $labels[ $i ] ) . '</span>';
			echo '</div>';
		}
		echo '</div>';

		// Step content
		switch ( $step ) {
			case 1:
				include COCOOKIE_PLUGIN_DIR . 'admin/views/wizard/step-scan.php';
				break;
			case 2:
				include COCOOKIE_PLUGIN_DIR . 'admin/views/wizard/step-review.php';
				break;
			case 3:
				require_once COCOOKIE_PLUGIN_DIR . 'admin/controllers/class-cocookie-banner-controller.php';
				include COCOOKIE_PLUGIN_DIR . 'admin/views/wizard/step-configure.php';
				break;
			case 4:
				include COCOOKIE_PLUGIN_DIR . 'admin/views/wizard/step-done.php';
				break;
		}

		echo '</div>';
		echo '</div>';
	}

	/**
	 * Handle wizard form submissions.
	 */
	public static function handle_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Step 3: Save banner settings
		if ( isset( $_POST['cocookie_wizard_save_banner'] ) && check_admin_referer( 'cocookie_wizard_banner' ) ) {
			$settings = array(
				'banner_title'       => sanitize_text_field( $_POST['banner_title'] ?? __( 'Vi använder cookies', 'cocookie' ) ),
				'banner_text'        => sanitize_textarea_field( $_POST['banner_text'] ?? '' ),
				'accept_all_text'    => sanitize_text_field( $_POST['accept_all_text'] ?? __( 'Acceptera alla', 'cocookie' ) ),
				'reject_all_text'    => sanitize_text_field( $_POST['reject_all_text'] ?? __( 'Avvisa alla', 'cocookie' ) ),
				'save_text'          => sanitize_text_field( $_POST['save_text'] ?? __( 'Spara inställningar', 'cocookie' ) ),
				'settings_text'      => sanitize_text_field( $_POST['settings_text'] ?? __( 'Inställningar', 'cocookie' ) ),
				'position'           => sanitize_text_field( $_POST['position'] ?? 'bottom' ),
				'primary_color'      => sanitize_hex_color( $_POST['primary_color'] ?? '#29A166' ),
				'primary_text_color' => '#ffffff',
				'banner_bg_color'    => '#ffffff',
				'banner_text_color'  => '#333333',
				'reject_bg_color'    => '#f0f0f0',
				'reject_text_color'  => '#333333',
				'logo_url'           => '',
				'cookie_lifetime'    => 365,
				'cookie_icon'        => '',
			);

			update_option( 'cocookie_settings', $settings );
			update_option( 'ccm_settings', $settings );

			wp_redirect( admin_url( 'admin.php?page=cocookie-wizard&step=4' ) );
			exit;
		}

		// Step 4: Complete wizard
		if ( isset( $_POST['cocookie_wizard_complete'] ) && check_admin_referer( 'cocookie_wizard_complete' ) ) {
			update_option( 'cocookie_needs_wizard', false );
			wp_redirect( admin_url( 'admin.php?page=cocookie' ) );
			exit;
		}
	}
}

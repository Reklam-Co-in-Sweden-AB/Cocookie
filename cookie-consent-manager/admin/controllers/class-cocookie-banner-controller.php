<?php
/**
 * Banner controller.
 *
 * Manages banner appearance settings with live preview.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_Banner_Controller {

	/**
	 * Default banner settings.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'banner_title'       => __( 'Vi använder cookies', 'cocookie' ),
			'banner_text'        => __( 'Denna webbplats använder cookies för att förbättra din upplevelse.', 'cocookie' ),
			'accept_all_text'    => __( 'Acceptera alla', 'cocookie' ),
			'reject_all_text'    => __( 'Avvisa alla', 'cocookie' ),
			'save_text'          => __( 'Spara inställningar', 'cocookie' ),
			'settings_text'      => __( 'Inställningar', 'cocookie' ),
			'position'           => 'bottom',
			'primary_color'      => '#29A166',
			'primary_text_color' => '#ffffff',
			'banner_bg_color'    => '#ffffff',
			'banner_text_color'  => '#333333',
			'reject_bg_color'    => '#f0f0f0',
			'reject_text_color'  => '#333333',
			'logo_url'           => '',
			'cookie_lifetime'    => 365,
			'cookie_icon'        => '',
		);
	}

	/**
	 * Render the banner settings page.
	 */
	public static function render() {
		self::handle_save();

		$settings = get_option( 'cocookie_settings', get_option( 'ccm_settings', array() ) );
		$s        = wp_parse_args( $settings, self::get_defaults() );

		wp_enqueue_media();

		$data = array( 's' => $s );
		include COCOOKIE_PLUGIN_DIR . 'admin/views/new/banner.php';
	}

	/**
	 * Handle settings save.
	 */
	private static function handle_save() {
		if ( ! isset( $_POST['cocookie_save_banner'] ) ) {
			return;
		}
		if ( ! check_admin_referer( 'cocookie_banner_action' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = array(
			'banner_title'       => sanitize_text_field( $_POST['banner_title'] ?? '' ),
			'banner_text'        => sanitize_textarea_field( $_POST['banner_text'] ?? '' ),
			'accept_all_text'    => sanitize_text_field( $_POST['accept_all_text'] ?? '' ),
			'reject_all_text'    => sanitize_text_field( $_POST['reject_all_text'] ?? '' ),
			'save_text'          => sanitize_text_field( $_POST['save_text'] ?? '' ),
			'settings_text'      => sanitize_text_field( $_POST['settings_text'] ?? '' ),
			'position'           => sanitize_text_field( $_POST['position'] ?? 'bottom' ),
			'primary_color'      => sanitize_hex_color( $_POST['primary_color'] ?? '#29A166' ),
			'primary_text_color' => sanitize_hex_color( $_POST['primary_text_color'] ?? '#ffffff' ),
			'banner_bg_color'    => sanitize_hex_color( $_POST['banner_bg_color'] ?? '#ffffff' ),
			'banner_text_color'  => sanitize_hex_color( $_POST['banner_text_color'] ?? '#333333' ),
			'reject_bg_color'    => sanitize_hex_color( $_POST['reject_bg_color'] ?? '#f0f0f0' ),
			'reject_text_color'  => sanitize_hex_color( $_POST['reject_text_color'] ?? '#333333' ),
			'logo_url'           => esc_url_raw( $_POST['logo_url'] ?? '' ),
			'cookie_lifetime'    => min( intval( $_POST['cookie_lifetime'] ?? 365 ), 395 ),
			'cookie_icon'        => esc_url_raw( $_POST['cookie_icon'] ?? '' ),
		);

		update_option( 'cocookie_settings', $settings );

		// Also update legacy option for backward compat
		update_option( 'ccm_settings', $settings );

		wp_redirect( admin_url( 'admin.php?page=cocookie-banner&msg=saved' ) );
		exit;
	}
}

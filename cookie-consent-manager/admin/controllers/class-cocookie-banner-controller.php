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
			'banner_language'    => 'auto',
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
			'cookie_icon'              => '',
			'float_button_enabled'     => true,
			'google_consent_enabled'   => false,
			'google_consent_intro'     => __( 'Vi använder Googles annonstjänster.', 'cocookie' ),
			'google_consent_link_text' => __( 'Läs hur Google hanterar dina personuppgifter.', 'cocookie' ),
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

		// WordPress lägger till snedstreck framför citattecken i $_POST.
		// Utan wp_unslash() sparas "you've" som "you\'ve" och visas så i bannern.
		$post = wp_unslash( $_POST );

		$language_options = CoCookie_REST_Config::get_language_options();
		$banner_language  = sanitize_key( $post['banner_language'] ?? 'auto' );
		if ( 'auto' !== $banner_language && ! isset( $language_options[ $banner_language ] ) ) {
			$banner_language = 'auto';
		}

		$settings = array(
			'banner_language'    => $banner_language,
			'banner_title'       => sanitize_text_field( $post['banner_title'] ?? '' ),
			'banner_text'        => sanitize_textarea_field( $post['banner_text'] ?? '' ),
			'accept_all_text'    => sanitize_text_field( $post['accept_all_text'] ?? '' ),
			'reject_all_text'    => sanitize_text_field( $post['reject_all_text'] ?? '' ),
			'save_text'          => sanitize_text_field( $post['save_text'] ?? '' ),
			'settings_text'      => sanitize_text_field( $post['settings_text'] ?? '' ),
			'position'           => sanitize_text_field( $post['position'] ?? 'bottom' ),
			'primary_color'      => sanitize_hex_color( $post['primary_color'] ?? '#29A166' ),
			'primary_text_color' => sanitize_hex_color( $post['primary_text_color'] ?? '#ffffff' ),
			'banner_bg_color'    => sanitize_hex_color( $post['banner_bg_color'] ?? '#ffffff' ),
			'banner_text_color'  => sanitize_hex_color( $post['banner_text_color'] ?? '#333333' ),
			'reject_bg_color'    => sanitize_hex_color( $post['reject_bg_color'] ?? '#f0f0f0' ),
			'reject_text_color'  => sanitize_hex_color( $post['reject_text_color'] ?? '#333333' ),
			'logo_url'           => esc_url_raw( $post['logo_url'] ?? '' ),
			'cookie_lifetime'    => min( intval( $post['cookie_lifetime'] ?? 365 ), 395 ),
			'cookie_icon'              => esc_url_raw( $post['cookie_icon'] ?? '' ),
			'float_button_enabled'     => ! empty( $post['float_button_enabled'] ),
			'google_consent_enabled'   => ! empty( $post['google_consent_enabled'] ),
			'google_consent_intro'     => sanitize_text_field( $post['google_consent_intro'] ?? '' ),
			'google_consent_link_text' => sanitize_text_field( $post['google_consent_link_text'] ?? '' ),
		);

		update_option( 'cocookie_settings', $settings );

		// Also update legacy option for backward compat
		update_option( 'ccm_settings', $settings );

		wp_redirect( admin_url( 'admin.php?page=cocookie-banner&msg=saved' ) );
		exit;
	}
}

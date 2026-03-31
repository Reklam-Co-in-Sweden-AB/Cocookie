<?php
/**
 * REST API controller for banner configuration.
 *
 * Serves the banner config JSON consumed by the frontend JS.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_REST_Config {

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		self::register_namespace( 'cocookie/v1' );
		self::register_namespace( 'cc/v1' );
	}

	/**
	 * Register routes under a given namespace.
	 *
	 * @param string $namespace REST namespace.
	 */
	private static function register_namespace( $namespace ) {
		register_rest_route( $namespace, '/config', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_config' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * Get the banner configuration.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function get_config( WP_REST_Request $request ) {
		$config = self::build_config();
		return new WP_REST_Response( $config, 200 );
	}

	/**
	 * Build the full banner configuration array.
	 *
	 * Used both by the REST endpoint and by wp_localize_script.
	 *
	 * @return array
	 */
	public static function build_config() {
		$categories      = class_exists( 'CoCookie_Categories' )
			? CoCookie_Categories::get_all()
			: CCM_Categories::get_all();

		$cookies_grouped = class_exists( 'CoCookie_Categories' )
			? CoCookie_Categories::get_cookies_grouped()
			: CCM_Categories::get_cookies_grouped();

		$settings = get_option( 'cocookie_settings', get_option( 'ccm_settings', array() ) );
		$blocked  = get_option( 'cocookie_blocked_cookies', get_option( 'ccm_blocked_cookies', array() ) );

		$config = array(
			'categories'     => array(),
			'blockedCookies' => array_values( $blocked ),
			'settings'       => array(
				'banner_title'       => $settings['banner_title'] ?? __( 'Vi använder cookies', 'cocookie' ),
				'banner_text'        => $settings['banner_text'] ?? __( 'Denna webbplats använder cookies för att förbättra din upplevelse.', 'cocookie' ),
				'accept_all_text'    => $settings['accept_all_text'] ?? __( 'Acceptera alla', 'cocookie' ),
				'reject_all_text'    => $settings['reject_all_text'] ?? __( 'Avvisa alla', 'cocookie' ),
				'save_text'          => $settings['save_text'] ?? __( 'Spara inställningar', 'cocookie' ),
				'settings_text'      => $settings['settings_text'] ?? __( 'Inställningar', 'cocookie' ),
				'position'           => $settings['position'] ?? 'bottom',
				'primary_color'      => $settings['primary_color'] ?? '#29A166',
				'primary_text_color' => $settings['primary_text_color'] ?? '#ffffff',
				'banner_bg_color'    => $settings['banner_bg_color'] ?? '#ffffff',
				'banner_text_color'  => $settings['banner_text_color'] ?? '#333333',
				'reject_bg_color'    => $settings['reject_bg_color'] ?? '#f0f0f0',
				'reject_text_color'  => $settings['reject_text_color'] ?? '#333333',
				'logo_url'           => $settings['logo_url'] ?? '',
				'cookie_lifetime'    => intval( $settings['cookie_lifetime'] ?? 365 ),
				'cookie_icon'        => $settings['cookie_icon'] ?? '',
				'privacy_policy_url' => function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '',
				'manage_title'       => $settings['manage_title'] ?? __( 'Hantera cookie-inställningar', 'cocookie' ),
				'manage_text'        => $settings['manage_text'] ?? __( 'Här kan du ändra eller återkalla ditt samtycke.', 'cocookie' ),
				'consent_date_label' => $settings['consent_date_label'] ?? __( 'Samtyckesdatum:', 'cocookie' ),
				'consent_id_label'   => $settings['consent_id_label'] ?? __( 'Ditt samtyckes-ID:', 'cocookie' ),
				'required_label'     => $settings['required_label'] ?? __( '(krävs alltid)', 'cocookie' ),
				'withdraw_text'      => $settings['withdraw_text'] ?? __( 'Dra tillbaka samtycke', 'cocookie' ),
				'hide_details_text'  => $settings['hide_details_text'] ?? __( 'Dölj detaljer', 'cocookie' ),
				'show_details_text'  => $settings['show_details_text'] ?? __( 'Visa detaljer', 'cocookie' ),
				'policy_link_text'   => $settings['policy_link_text'] ?? __( 'Läs vår integritetspolicy.', 'cocookie' ),
				'dnt_notice'         => $settings['dnt_notice'] ?? __( 'Din webbläsare har Do Not Track aktiverat. Analys- och marknadsföringscookies är automatiskt inaktiverade.', 'cocookie' ),
			),
		);

		foreach ( $categories as $cat ) {
			$cookies     = isset( $cookies_grouped[ $cat['id'] ] ) ? $cookies_grouped[ $cat['id'] ] : array();
			$cookie_list = array();
			foreach ( $cookies as $c ) {
				$cookie_list[] = array(
					'name'     => $c['name'],
					'provider' => $c['provider'],
					'purpose'  => $c['purpose'],
					'expiry'   => $c['expiry'],
				);
			}
			$config['categories'][] = array(
				'slug'        => $cat['slug'],
				'title'       => $cat['title'],
				'description' => $cat['description'],
				'is_required' => (bool) $cat['is_required'],
				'cookies'     => $cookie_list,
			);
		}

		/**
		 * Filter the banner configuration before it's sent to the frontend.
		 *
		 * Translation plugins can hook here to translate strings.
		 * Replaces the hardcoded Coscribe integration.
		 *
		 * @param array $config The full banner configuration.
		 */
		$config = apply_filters( 'cocookie_translate_config', $config );

		return $config;
	}
}

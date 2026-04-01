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
	 * Get built-in translations for supported languages.
	 *
	 * @return array Keyed by language prefix (e.g. 'en', 'sv').
	 */
	private static function get_translations() {
		return array(
			'en' => array(
				'banner_title'       => 'We use cookies',
				'banner_text'        => 'This website uses cookies to improve your experience.',
				'accept_all_text'    => 'Accept all',
				'reject_all_text'    => 'Reject all',
				'save_text'          => 'Save settings',
				'settings_text'      => 'Settings',
				'manage_title'       => 'Manage cookie settings',
				'manage_text'        => 'Here you can change or withdraw your consent.',
				'consent_date_label' => 'Consent date:',
				'consent_id_label'   => 'Your consent ID:',
				'required_label'     => '(always required)',
				'withdraw_text'      => 'Withdraw consent',
				'hide_details_text'  => 'Hide details',
				'show_details_text'  => 'Show details',
				'policy_link_text'   => 'Read our privacy policy.',
				'dnt_notice'         => 'Your browser has Do Not Track enabled. Analytics and marketing cookies are automatically disabled.',
				// Tabellrubriker
				'table_cookie'       => 'Cookie',
				'table_provider'     => 'Provider',
				'table_purpose'      => 'Purpose',
				'table_expiry'       => 'Expiry',
				// Flikar
				'tab_consent'        => 'Consent',
				'tab_details'        => 'Details',
				'tab_about'          => 'About cookies',
				// Om cookies
				'about_title'               => 'What are cookies?',
				'about_what'                => 'Cookies are small text files stored on your device (computer, phone or tablet) when you visit a website. They are used to make the website work properly, to analyse traffic and to personalise content and ads.',
				'about_types_title'         => 'Types of cookies',
				'about_type_necessary'      => 'Necessary cookies',
				'about_type_necessary_desc' => 'These cookies are required for the website to function and cannot be disabled. They are usually set in response to actions you take, such as logging in or filling in forms.',
				'about_type_analytics'      => 'Analytics cookies',
				'about_type_analytics_desc' => 'These cookies allow us to count visits and traffic sources so we can measure and improve website performance. They help us understand which pages are most and least popular.',
				'about_type_marketing'      => 'Marketing cookies',
				'about_type_marketing_desc' => 'These cookies may be set through our website by our advertising partners. They may be used to build a profile of your interests and show you relevant ads on other websites.',
				'about_manage_title'        => 'Manage your cookies',
				'about_manage_desc'         => 'You can change or withdraw your consent at any time by clicking the cookie icon in the bottom left corner. You can also delete cookies in your browser settings.',
				'about_rights_title'        => 'Your rights',
				'about_rights_desc'         => 'Under GDPR, you have the right to access, correct or delete your personal data. You also have the right to object to processing and to request data portability. Read more in our privacy policy.',
				// Kategoriöversättningar
				'cat_necessary'      => 'Necessary',
				'cat_analytics'      => 'Analytics',
				'cat_marketing'      => 'Marketing',
				'desc_necessary'     => 'These cookies are necessary for the website to function and cannot be disabled.',
				'desc_analytics'     => 'These cookies help us understand how visitors use the website.',
				'desc_marketing'     => 'These cookies are used to display relevant advertisements.',
			),
		);
	}

	/**
	 * Detect current language.
	 *
	 * Supports Polylang, WPML, and WordPress locale.
	 *
	 * @return string Two-letter language code (e.g. 'sv', 'en').
	 */
	private static function get_current_language() {
		// Polylang
		if ( function_exists( 'pll_current_language' ) ) {
			$lang = pll_current_language( 'slug' );
			if ( $lang ) {
				return substr( $lang, 0, 2 );
			}
		}

		// WPML
		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			return substr( ICL_LANGUAGE_CODE, 0, 2 );
		}

		// Coscribe Translator
		if ( function_exists( 'coscribe_translator' ) ) {
			$plugin = coscribe_translator();
			if ( $plugin ) {
				$lang = $plugin->get_current_language();
				if ( $lang ) {
					return substr( $lang, 0, 2 );
				}
			}
		}

		// WordPress locale fallback
		$locale = get_locale();
		return substr( $locale, 0, 2 );
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
				'table_cookie'       => __( 'Cookie', 'cocookie' ),
				'table_provider'     => __( 'Leverantör', 'cocookie' ),
				'table_purpose'      => __( 'Syfte', 'cocookie' ),
				'table_expiry'       => __( 'Livslängd', 'cocookie' ),
				// Flikar
				'tab_consent'        => __( 'Samtycke', 'cocookie' ),
				'tab_details'        => __( 'Detaljer', 'cocookie' ),
				'tab_about'          => __( 'Om cookies', 'cocookie' ),
				// Om cookies
				'about_title'               => __( 'Vad är cookies?', 'cocookie' ),
				'about_what'                => __( 'Cookies är små textfiler som lagras på din enhet (dator, telefon eller surfplatta) när du besöker en webbplats. De används för att webbplatsen ska fungera korrekt, för att analysera trafik och för att anpassa innehåll och annonser.', 'cocookie' ),
				'about_types_title'         => __( 'Typer av cookies', 'cocookie' ),
				'about_type_necessary'      => __( 'Nödvändiga cookies', 'cocookie' ),
				'about_type_necessary_desc' => __( 'Dessa cookies krävs för att webbplatsen ska fungera och kan inte stängas av. De sätts vanligtvis som svar på åtgärder du gör, som att logga in eller fylla i formulär.', 'cocookie' ),
				'about_type_analytics'      => __( 'Analyticscookies', 'cocookie' ),
				'about_type_analytics_desc' => __( 'Dessa cookies låter oss räkna besök och trafikkällor så att vi kan mäta och förbättra webbplatsens prestanda. De hjälper oss att veta vilka sidor som är mest och minst populära.', 'cocookie' ),
				'about_type_marketing'      => __( 'Marknadsföringscookies', 'cocookie' ),
				'about_type_marketing_desc' => __( 'Dessa cookies kan sättas via vår webbplats av våra annonspartners. De kan användas för att bygga en profil om dina intressen och visa dig relevanta annonser på andra webbplatser.', 'cocookie' ),
				'about_manage_title'        => __( 'Hantera dina cookies', 'cocookie' ),
				'about_manage_desc'         => __( 'Du kan när som helst ändra eller återkalla ditt samtycke genom att klicka på cookie-ikonen i nedre vänstra hörnet. Du kan också radera cookies i din webbläsares inställningar.', 'cocookie' ),
				'about_rights_title'        => __( 'Dina rättigheter', 'cocookie' ),
				'about_rights_desc'         => __( 'Enligt GDPR har du rätt att få tillgång till, korrigera eller radera dina personuppgifter. Du har också rätt att invända mot behandling och att begära dataportabilitet. Läs mer i vår integritetspolicy.', 'cocookie' ),
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

		// Apply built-in language translations if not Swedish
		$lang = self::get_current_language();
		if ( 'sv' !== $lang ) {
			$config = self::apply_translation( $config, $lang );
		}

		/**
		 * Filter the banner configuration before it's sent to the frontend.
		 *
		 * Translation plugins can hook here to translate strings.
		 * Replaces the hardcoded Coscribe integration.
		 *
		 * @param array  $config The full banner configuration.
		 * @param string $lang   Current two-letter language code.
		 */
		$config = apply_filters( 'cocookie_translate_config', $config, $lang );

		return $config;
	}

	/**
	 * Apply a built-in translation to the config.
	 *
	 * Only overrides default Swedish texts — if the admin has customized
	 * a text in settings, it's left unchanged.
	 *
	 * @param array  $config Banner configuration.
	 * @param string $lang   Two-letter language code.
	 * @return array Modified configuration.
	 */
	private static function apply_translation( $config, $lang ) {
		$translations = self::get_translations();
		if ( ! isset( $translations[ $lang ] ) ) {
			return $config;
		}

		$t        = $translations[ $lang ];
		$settings = get_option( 'cocookie_settings', get_option( 'ccm_settings', array() ) );

		// Swedish defaults — only override if admin hasn't customized the text
		$swedish_defaults = array(
			'banner_title'       => 'Vi använder cookies',
			'banner_text'        => 'Denna webbplats använder cookies för att förbättra din upplevelse.',
			'accept_all_text'    => 'Acceptera alla',
			'reject_all_text'    => 'Avvisa alla',
			'save_text'          => 'Spara inställningar',
			'settings_text'      => 'Inställningar',
			'manage_title'       => 'Hantera cookie-inställningar',
			'manage_text'        => 'Här kan du ändra eller återkalla ditt samtycke.',
			'consent_date_label' => 'Samtyckesdatum:',
			'consent_id_label'   => 'Ditt samtyckes-ID:',
			'required_label'     => '(krävs alltid)',
			'withdraw_text'      => 'Dra tillbaka samtycke',
			'hide_details_text'  => 'Dölj detaljer',
			'show_details_text'  => 'Visa detaljer',
			'policy_link_text'   => 'Läs vår integritetspolicy.',
			'dnt_notice'         => 'Din webbläsare har Do Not Track aktiverat. Analys- och marknadsföringscookies är automatiskt inaktiverade.',
			'table_cookie'       => 'Cookie',
			'table_provider'     => 'Leverantör',
			'table_purpose'      => 'Syfte',
			'table_expiry'       => 'Livslängd',
			'tab_consent'        => 'Samtycke',
			'tab_details'        => 'Detaljer',
			'tab_about'          => 'Om cookies',
			'about_title'               => 'Vad är cookies?',
			'about_what'                => 'Cookies är små textfiler som lagras på din enhet (dator, telefon eller surfplatta) när du besöker en webbplats. De används för att webbplatsen ska fungera korrekt, för att analysera trafik och för att anpassa innehåll och annonser.',
			'about_types_title'         => 'Typer av cookies',
			'about_type_necessary'      => 'Nödvändiga cookies',
			'about_type_necessary_desc' => 'Dessa cookies krävs för att webbplatsen ska fungera och kan inte stängas av. De sätts vanligtvis som svar på åtgärder du gör, som att logga in eller fylla i formulär.',
			'about_type_analytics'      => 'Analyticscookies',
			'about_type_analytics_desc' => 'Dessa cookies låter oss räkna besök och trafikkällor så att vi kan mäta och förbättra webbplatsens prestanda. De hjälper oss att veta vilka sidor som är mest och minst populära.',
			'about_type_marketing'      => 'Marknadsföringscookies',
			'about_type_marketing_desc' => 'Dessa cookies kan sättas via vår webbplats av våra annonspartners. De kan användas för att bygga en profil om dina intressen och visa dig relevanta annonser på andra webbplatser.',
			'about_manage_title'        => 'Hantera dina cookies',
			'about_manage_desc'         => 'Du kan när som helst ändra eller återkalla ditt samtycke genom att klicka på cookie-ikonen i nedre vänstra hörnet. Du kan också radera cookies i din webbläsares inställningar.',
			'about_rights_title'        => 'Dina rättigheter',
			'about_rights_desc'         => 'Enligt GDPR har du rätt att få tillgång till, korrigera eller radera dina personuppgifter. Du har också rätt att invända mot behandling och att begära dataportabilitet. Läs mer i vår integritetspolicy.',
		);

		// Override settings texts that haven't been customized
		foreach ( $t as $key => $value ) {
			// Skip category translations (handled below)
			if ( strpos( $key, 'cat_' ) === 0 || strpos( $key, 'desc_' ) === 0 ) {
				continue;
			}

			if ( ! isset( $config['settings'][ $key ] ) ) {
				continue;
			}

			// Only translate if the current value matches the Swedish default
			// (meaning the admin hasn't customized it)
			$current_value = $config['settings'][ $key ];
			$swedish_value = $swedish_defaults[ $key ] ?? '';

			if ( $current_value === $swedish_value || empty( $settings[ $key ] ) ) {
				$config['settings'][ $key ] = $value;
			}
		}

		// Translate category titles and descriptions
		$cat_map = array(
			'necessary' => array( 'title' => $t['cat_necessary'] ?? '', 'desc' => $t['desc_necessary'] ?? '' ),
			'analytics' => array( 'title' => $t['cat_analytics'] ?? '', 'desc' => $t['desc_analytics'] ?? '' ),
			'marketing' => array( 'title' => $t['cat_marketing'] ?? '', 'desc' => $t['desc_marketing'] ?? '' ),
		);

		// Swedish category defaults
		$swedish_cats = array(
			'necessary' => array( 'title' => 'Nödvändiga', 'desc' => 'Dessa cookies är nödvändiga för att webbplatsen ska fungera och kan inte stängas av.' ),
			'analytics' => array( 'title' => 'Analys',      'desc' => 'Dessa cookies hjälper oss att förstå hur besökare använder webbplatsen.' ),
			'marketing' => array( 'title' => 'Marknadsföring', 'desc' => 'Dessa cookies används för att visa relevanta annonser.' ),
		);

		foreach ( $config['categories'] as &$cat ) {
			$slug = $cat['slug'];
			if ( isset( $cat_map[ $slug ] ) ) {
				// Only translate if matching Swedish default
				if ( isset( $swedish_cats[ $slug ] ) ) {
					if ( $cat['title'] === $swedish_cats[ $slug ]['title'] && ! empty( $cat_map[ $slug ]['title'] ) ) {
						$cat['title'] = $cat_map[ $slug ]['title'];
					}
					if ( $cat['description'] === $swedish_cats[ $slug ]['desc'] && ! empty( $cat_map[ $slug ]['desc'] ) ) {
						$cat['description'] = $cat_map[ $slug ]['desc'];
					}
				}
			}
		}
		unset( $cat );

		return $config;
	}
}

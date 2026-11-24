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

		// Färsk nonce. Bannerns inbäddade nonce kan vara utgången när sidan
		// serverats från en full page cache — då hämtar JS:en en ny härifrån
		// och gör om samtyckesanropet, så loggningen inte tappas tyst.
		$config['nonce'] = wp_create_nonce( 'wp_rest' );

		$response = new WP_REST_Response( $config, 200 );

		// Svaret innehåller en nonce och får aldrig cachas av CDN eller
		// sidcache — då skulle en utgången nonce serveras till alla besökare.
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );

		return $response;
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
				'float_label'        => 'Cookies',
				'float_label_long'   => 'Cookie settings',
				'status_all'         => 'all accepted',
				'status_custom'      => 'custom choice',
				'status_necessary'   => 'necessary only',
				'manage_title'       => 'Manage cookie settings',
				'manage_text'        => 'Here you can change or withdraw your consent.',
				'consent_date_label' => 'Consent date:',
				'consent_id_label'   => 'Your consent ID:',
				'required_label'     => '(always required)',
				'withdraw_text'      => 'Withdraw consent',
				'hide_details_text'  => 'Hide details',
				'show_details_text'  => 'Show details',
				'policy_link_text'   => 'Read our privacy policy.',
				'google_consent_intro'     => "We use Google's advertising services.",
				'google_consent_link_text' => 'Learn how Google uses your data.',
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
			// Danska
			'da' => array(
				'banner_title' => 'Vi bruger cookies',
				'banner_text' => 'Dette websted bruger cookies for at forbedre din oplevelse.',
				'accept_all_text' => 'Accepter alle',
				'reject_all_text' => 'Afvis alle',
				'save_text' => 'Gem indstillinger',
				'settings_text' => 'Indstillinger',
				'float_label' => 'Cookies',
				'float_label_long' => 'Cookieindstillinger',
				'status_all' => 'alle accepteret',
				'status_custom' => 'eget valg',
				'status_necessary' => 'kun nødvendige',
				'manage_title' => 'Administrer cookieindstillinger',
				'manage_text' => 'Her kan du ændre eller trække dit samtykke tilbage.',
				'consent_date_label' => 'Samtykkedato:',
				'consent_id_label' => 'Dit samtykke-ID:',
				'required_label' => '(altid påkrævet)',
				'withdraw_text' => 'Træk samtykke tilbage',
				'hide_details_text' => 'Skjul detaljer',
				'show_details_text' => 'Vis detaljer',
				'policy_link_text' => 'Læs vores privatlivspolitik.',
				'google_consent_intro' => 'Vi bruger Googles annoncetjenester.',
				'google_consent_link_text' => 'Læs, hvordan Google bruger dine data.',
				'dnt_notice' => 'Din browser har Do Not Track aktiveret. Statistik- og marketingcookies er automatisk deaktiveret.',
				'table_cookie' => 'Cookie',
				'table_provider' => 'Udbyder',
				'table_purpose' => 'Formål',
				'table_expiry' => 'Udløb',
				'tab_consent' => 'Samtykke',
				'tab_details' => 'Detaljer',
				'tab_about' => 'Om cookies',
				'about_title' => 'Hvad er cookies?',
				'about_what' => 'Cookies er små tekstfiler, der gemmes på din enhed (computer, telefon eller tablet), når du besøger et websted. De bruges til at få webstedet til at fungere korrekt, til at analysere trafik og til at tilpasse indhold og annoncer.',
				'about_types_title' => 'Typer af cookies',
				'about_type_necessary' => 'Nødvendige cookies',
				'about_type_necessary_desc' => 'Disse cookies er nødvendige for, at webstedet kan fungere, og kan ikke slås fra. De sættes normalt som svar på handlinger, du foretager, f.eks. når du logger ind eller udfylder formularer.',
				'about_type_analytics' => 'Statistikcookies',
				'about_type_analytics_desc' => 'Disse cookies giver os mulighed for at tælle besøg og trafikkilder, så vi kan måle og forbedre webstedets ydeevne. De hjælper os med at se, hvilke sider der er mest og mindst populære.',
				'about_type_marketing' => 'Marketingcookies',
				'about_type_marketing_desc' => 'Disse cookies kan sættes via vores websted af vores annoncepartnere. De kan bruges til at opbygge en profil over dine interesser og vise dig relevante annoncer på andre websteder.',
				'about_manage_title' => 'Administrer dine cookies',
				'about_manage_desc' => 'Du kan til enhver tid ændre eller trække dit samtykke tilbage ved at klikke på cookieikonet nederst i venstre hjørne. Du kan også slette cookies i din browsers indstillinger.',
				'about_rights_title' => 'Dine rettigheder',
				'about_rights_desc' => 'I henhold til GDPR har du ret til at få indsigt i, berigtige eller slette dine personoplysninger. Du har også ret til at gøre indsigelse mod behandling og til at anmode om dataportabilitet. Læs mere i vores privatlivspolitik.',
				'cat_necessary' => 'Nødvendige',
				'cat_analytics' => 'Statistik',
				'cat_marketing' => 'Marketing',
				'desc_necessary' => 'Disse cookies er nødvendige for, at webstedet kan fungere, og kan ikke slås fra.',
				'desc_analytics' => 'Disse cookies hjælper os med at forstå, hvordan besøgende bruger webstedet.',
				'desc_marketing' => 'Disse cookies bruges til at vise relevante annoncer.',
			),
			// Norska (bokmål)
			'no' => array(
				'banner_title' => 'Vi bruker cookies',
				'banner_text' => 'Dette nettstedet bruker cookies for å forbedre opplevelsen din.',
				'accept_all_text' => 'Godta alle',
				'reject_all_text' => 'Avvis alle',
				'save_text' => 'Lagre innstillinger',
				'settings_text' => 'Innstillinger',
				'float_label' => 'Cookies',
				'float_label_long' => 'Cookie-innstillinger',
				'status_all' => 'alle godtatt',
				'status_custom' => 'eget valg',
				'status_necessary' => 'kun nødvendige',
				'manage_title' => 'Administrer cookie-innstillinger',
				'manage_text' => 'Her kan du endre eller trekke tilbake samtykket ditt.',
				'consent_date_label' => 'Samtykkedato:',
				'consent_id_label' => 'Din samtykke-ID:',
				'required_label' => '(alltid påkrevd)',
				'withdraw_text' => 'Trekk tilbake samtykke',
				'hide_details_text' => 'Skjul detaljer',
				'show_details_text' => 'Vis detaljer',
				'policy_link_text' => 'Les personvernerklæringen vår.',
				'google_consent_intro' => 'Vi bruker Googles annonsetjenester.',
				'google_consent_link_text' => 'Les hvordan Google bruker dataene dine.',
				'dnt_notice' => 'Nettleseren din har Do Not Track aktivert. Statistikk- og markedsføringscookies er automatisk deaktivert.',
				'table_cookie' => 'Cookie',
				'table_provider' => 'Leverandør',
				'table_purpose' => 'Formål',
				'table_expiry' => 'Utløper',
				'tab_consent' => 'Samtykke',
				'tab_details' => 'Detaljer',
				'tab_about' => 'Om cookies',
				'about_title' => 'Hva er cookies?',
				'about_what' => 'Cookies er små tekstfiler som lagres på enheten din (datamaskin, telefon eller nettbrett) når du besøker et nettsted. De brukes for at nettstedet skal fungere riktig, for å analysere trafikk og for å tilpasse innhold og annonser.',
				'about_types_title' => 'Typer cookies',
				'about_type_necessary' => 'Nødvendige cookies',
				'about_type_necessary_desc' => 'Disse cookiene er nødvendige for at nettstedet skal fungere, og kan ikke slås av. De settes vanligvis som svar på handlinger du gjør, for eksempel når du logger inn eller fyller ut skjemaer.',
				'about_type_analytics' => 'Statistikkcookies',
				'about_type_analytics_desc' => 'Disse cookiene lar oss telle besøk og trafikkilder slik at vi kan måle og forbedre nettstedets ytelse. De hjelper oss å se hvilke sider som er mest og minst populære.',
				'about_type_marketing' => 'Markedsføringscookies',
				'about_type_marketing_desc' => 'Disse cookiene kan settes via nettstedet vårt av annonsepartnerne våre. De kan brukes til å bygge en profil av interessene dine og vise deg relevante annonser på andre nettsteder.',
				'about_manage_title' => 'Administrer cookiene dine',
				'about_manage_desc' => 'Du kan når som helst endre eller trekke tilbake samtykket ditt ved å klikke på cookie-ikonet nederst i venstre hjørne. Du kan også slette cookies i innstillingene i nettleseren din.',
				'about_rights_title' => 'Dine rettigheter',
				'about_rights_desc' => 'I henhold til GDPR har du rett til innsyn i, retting av og sletting av personopplysningene dine. Du har også rett til å protestere mot behandling og til å be om dataportabilitet. Les mer i personvernerklæringen vår.',
				'cat_necessary' => 'Nødvendige',
				'cat_analytics' => 'Statistikk',
				'cat_marketing' => 'Markedsføring',
				'desc_necessary' => 'Disse cookiene er nødvendige for at nettstedet skal fungere, og kan ikke slås av.',
				'desc_analytics' => 'Disse cookiene hjelper oss å forstå hvordan besøkende bruker nettstedet.',
				'desc_marketing' => 'Disse cookiene brukes til å vise relevante annonser.',
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
	 * Språk som bannern kan visas på: svenska (källtexterna) plus de
	 * inbyggda översättningarna.
	 *
	 * @return array Språkkod => namn på språket.
	 */
	public static function get_language_options() {
		$names = array(
			'sv' => 'Svenska',
			'en' => 'English',
			'da' => 'Dansk',
			'no' => 'Norsk',
		);

		$options = array( 'sv' => $names['sv'] );
		foreach ( array_keys( self::get_translations() ) as $code ) {
			$options[ $code ] = $names[ $code ] ?? strtoupper( $code );
		}

		return $options;
	}

	/**
	 * Bannerns layouter. Nyckeln sparas i inställningen "position" så att
	 * äldre installationer (bottom/top/center) fungerar som tidigare.
	 *
	 * @return array Nyckel => array( label, floating ).
	 */
	public static function get_layout_options() {
		return array(
			'bottom'       => array( 'label' => __( 'Botten, full bredd', 'cocookie' ), 'floating' => false ),
			'top'          => array( 'label' => __( 'Topp, full bredd', 'cocookie' ), 'floating' => false ),
			'bottom-card'  => array( 'label' => __( 'Flytande kort nere i mitten', 'cocookie' ), 'floating' => true ),
			'bottom-left'  => array( 'label' => __( 'Ruta nere till vänster', 'cocookie' ), 'floating' => true ),
			'bottom-right' => array( 'label' => __( 'Ruta nere till höger', 'cocookie' ), 'floating' => true ),
			'center'       => array( 'label' => __( 'Center (modal)', 'cocookie' ), 'floating' => true ),
		);
	}

	/**
	 * Hörnradie för flytande layouter.
	 *
	 * @return array Nyckel => array( label, value ).
	 */
	public static function get_radius_options() {
		return array(
			'none'   => array( 'label' => __( 'Kantigt', 'cocookie' ), 'value' => '0px' ),
			'small'  => array( 'label' => __( 'Lätt rundat', 'cocookie' ), 'value' => '6px' ),
			'medium' => array( 'label' => __( 'Rundat', 'cocookie' ), 'value' => '10px' ),
			'large'  => array( 'label' => __( 'Mycket rundat', 'cocookie' ), 'value' => '16px' ),
		);
	}

	/**
	 * Skuggor. "soft" är bannerns ursprungliga skugga.
	 *
	 * @return array Nyckel => etikett.
	 */
	public static function get_shadow_options() {
		return array(
			'none'   => __( 'Ingen', 'cocookie' ),
			'soft'   => __( 'Mjuk', 'cocookie' ),
			'strong' => __( 'Kraftig', 'cocookie' ),
		);
	}

	/**
	 * Knapparnas form.
	 *
	 * @return array Nyckel => array( label, value ).
	 */
	public static function get_button_shape_options() {
		return array(
			'square'  => array( 'label' => __( 'Kantiga', 'cocookie' ), 'value' => '0px' ),
			'rounded' => array( 'label' => __( 'Rundade', 'cocookie' ), 'value' => '8px' ),
			'pill'    => array( 'label' => __( 'Piller', 'cocookie' ), 'value' => '999px' ),
		);
	}

	/**
	 * Bannerns uppställning: var loggan står och hur tätt innehållet ligger.
	 * "stacked" är den ursprungliga uppställningen med loggan på egen rad.
	 *
	 * @return array Nyckel => etikett.
	 */
	public static function get_content_layout_options() {
		return array(
			'stacked'     => __( 'Logga på egen rad', 'cocookie' ),
			'logo-beside' => __( 'Logga bredvid texten', 'cocookie' ),
			'logo-tabs'   => __( 'Logga i flikraden', 'cocookie' ),
			'compact'     => __( 'Kompakt rad', 'cocookie' ),
		);
	}

	/**
	 * Den flytande cookie-knappens stilar. "emoji" är den ursprungliga
	 * knappen, en vit cirkel med en emoji.
	 *
	 * @return array Nyckel => etikett.
	 */
	public static function get_float_style_options() {
		return array(
			'emoji'  => __( 'Emoji i vit cirkel', 'cocookie' ),
			'icon'   => __( 'Ritad ikon', 'cocookie' ),
			'status' => __( 'Ritad ikon med statusprick', 'cocookie' ),
			'pill'   => __( 'Piller med text', 'cocookie' ),
			'grow'   => __( 'Växer när man pekar', 'cocookie' ),
			'tab'    => __( 'Kantflik', 'cocookie' ),
			'side'   => __( 'Sidoflik', 'cocookie' ),
			'corner' => __( 'Hörn', 'cocookie' ),
		);
	}

	/**
	 * Stilar där knappen visar ordet "Cookies" i klartext. Där är texten
	 * knappens namn, så de får ingen aria-label.
	 *
	 * @return array
	 */
	public static function get_float_text_styles() {
		return array( 'pill', 'tab', 'side' );
	}

	/**
	 * Ritade ikoner till den flytande knappen.
	 *
	 * @return array Nyckel => etikett.
	 */
	public static function get_float_icon_options() {
		return array(
			'cookie'  => __( 'Kaka', 'cocookie' ),
			'sliders' => __( 'Reglage', 'cocookie' ),
			'shield'  => __( 'Sköld', 'cocookie' ),
		);
	}

	/**
	 * Vilken sida av skärmen den flytande knappen sitter på.
	 *
	 * @return array Nyckel => etikett.
	 */
	public static function get_float_side_options() {
		return array(
			'left'  => __( 'Vänster', 'cocookie' ),
			'right' => __( 'Höger', 'cocookie' ),
		);
	}

	/**
	 * SVG-kod för en ritad ikon. Linjeikoner som ärver textfärgen
	 * (currentColor), så de följer bannerns färger. Innehållet är statiskt
	 * och kan skrivas ut utan escaping.
	 *
	 * @param string $icon Nyckel från get_float_icon_options().
	 * @return string
	 */
	public static function get_float_icon_svg( $icon ) {
		$shapes = array(
			'cookie'  => '<path d="M13 3.06A9 9 0 1 0 20.94 11a3.4 3.4 0 0 1-4.44-3.5A3.4 3.4 0 0 1 13 3.06z"/><path d="M8.5 9h.01M12.5 12.5h.01M8 14.5h.01M14.5 17h.01"/>',
			'sliders' => '<path d="M4 7h10M18 7h2M4 12h4M12 12h8M4 17h9M17 17h3"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="15" cy="17" r="2"/>',
			'shield'  => '<path d="M12 3l7 3v5.5c0 4.4-2.9 8.1-7 9.5-4.1-1.4-7-5.1-7-9.5V6l7-3z"/><path d="M9 12l2 2 4-4"/>',
		);

		if ( ! isset( $shapes[ $icon ] ) ) {
			$icon = 'cookie';
		}

		return '<svg class="cocookie-float__icon" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $shapes[ $icon ] . '</svg>';
	}

	/**
	 * Standardvärden för designvalen. Motsvarar bannerns utseende före 2.8.0,
	 * och för de senare valen (uppställning och knappstil) utseendet innan
	 * de fanns.
	 *
	 * @return array
	 */
	public static function get_design_defaults() {
		return array(
			'position'       => 'bottom',
			'banner_radius'  => 'large',
			'banner_shadow'  => 'soft',
			'banner_border'  => false,
			'button_shape'   => 'rounded',
			'content_layout' => 'stacked',
			'float_style'    => 'emoji',
			'float_icon'     => 'cookie',
			'float_side'     => 'left',
		);
	}

	/**
	 * Sanerar designvalen mot listorna ovan. Okända värden ger standardvärdet.
	 *
	 * @param array $input Osanerade värden (t.ex. wp_unslash( $_POST )).
	 * @return array
	 */
	public static function sanitize_design_settings( $input ) {
		$defaults = self::get_design_defaults();
		$lists    = array(
			'position'       => self::get_layout_options(),
			'banner_radius'  => self::get_radius_options(),
			'banner_shadow'  => self::get_shadow_options(),
			'button_shape'   => self::get_button_shape_options(),
			'content_layout' => self::get_content_layout_options(),
			'float_style'    => self::get_float_style_options(),
			'float_icon'     => self::get_float_icon_options(),
			'float_side'     => self::get_float_side_options(),
		);

		$clean = array();
		foreach ( $lists as $key => $options ) {
			$value         = sanitize_key( $input[ $key ] ?? '' );
			$clean[ $key ] = isset( $options[ $value ] ) ? $value : $defaults[ $key ];
		}
		$clean['banner_border'] = ! empty( $input['banner_border'] );

		return $clean;
	}

	/**
	 * Räknar ut bannerns klasser och mått från inställningarna. Används av
	 * både frontend-mallen och förhandsvisningen i admin.
	 *
	 * @param array $s Bannerinställningar.
	 * @return array layout, classes, radius, button_radius, logo_position,
	 *               float_style, float_icon, float_classes.
	 */
	public static function get_banner_design( $s ) {
		$has_logo = ! empty( $s['logo_url'] );
		$s        = self::sanitize_design_settings( $s );
		$layouts  = self::get_layout_options();
		$layout   = $s['position'];
		$classes  = array( 'cocookie-banner', 'cocookie-banner--' . $layout );

		if ( $layouts[ $layout ]['floating'] ) {
			$classes[] = 'cocookie-banner--floating';
		}
		if ( 'soft' !== $s['banner_shadow'] ) {
			$classes[] = 'cocookie-banner--shadow-' . $s['banner_shadow'];
		}
		if ( $s['banner_border'] ) {
			$classes[] = 'cocookie-banner--bordered';
		}

		// Uppställning. Loggans plats får bara en klass när det finns en
		// logga, annars lämnar den en tom kolumn efter sig.
		$logo_position = 'top';
		if ( 'compact' === $s['content_layout'] ) {
			$classes[] = 'cocookie-banner--compact';
		} elseif ( $has_logo && 'logo-beside' === $s['content_layout'] ) {
			$classes[]     = 'cocookie-banner--logo-beside';
			$logo_position = 'beside';
		} elseif ( $has_logo && 'logo-tabs' === $s['content_layout'] ) {
			$classes[]     = 'cocookie-banner--logo-tabs';
			$logo_position = 'tabs';
		}

		// Den flytande knappen. Emoji-knappen är grundklassen utan tillägg.
		$float_classes = array( 'cocookie-float' );
		if ( 'emoji' !== $s['float_style'] ) {
			$float_classes[] = 'cocookie-float--drawn';
			$float_classes[] = 'cocookie-float--' . $s['float_style'];
		}
		if ( 'right' === $s['float_side'] ) {
			$float_classes[] = 'cocookie-float--right';
		}

		return array(
			'layout'        => $layout,
			'classes'       => implode( ' ', $classes ),
			'radius'        => self::get_radius_options()[ $s['banner_radius'] ]['value'],
			'button_radius' => self::get_button_shape_options()[ $s['button_shape'] ]['value'],
			'logo_position' => $logo_position,
			'float_style'   => $s['float_style'],
			'float_icon'    => $s['float_icon'],
			'float_classes' => implode( ' ', $float_classes ),
		);
	}

	/**
	 * Avgör vilket språk bannern ska visas på.
	 *
	 * Ett fast språk i inställningarna vinner alltid. Annars följer bannern
	 * sidans språk, och saknas översättning för det visas engelska. En
	 * besökare på en dansk eller tysk sida förstår engelska bättre än svenska.
	 *
	 * @param array  $settings  Sparade bannerinställningar.
	 * @param string $page_lang Sidans språk (tvåbokstavskod).
	 * @return string Tvåbokstavskod.
	 */
	private static function resolve_banner_language( $settings, $page_lang ) {
		$options = self::get_language_options();
		$chosen  = $settings['banner_language'] ?? 'auto';

		// Norska har flera koder: nb/nn i WordPress-locale, no i en del
		// översättningsplugin. Alla visas med den norska översättningen.
		if ( in_array( $page_lang, array( 'nb', 'nn' ), true ) ) {
			$page_lang = 'no';
		}

		if ( 'auto' !== $chosen && isset( $options[ $chosen ] ) ) {
			return $chosen;
		}

		return isset( $options[ $page_lang ] ) ? $page_lang : 'en';
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
				'banner_radius'      => $settings['banner_radius'] ?? 'large',
				'banner_shadow'      => $settings['banner_shadow'] ?? 'soft',
				'banner_border'      => ! empty( $settings['banner_border'] ),
				'button_shape'       => $settings['button_shape'] ?? 'rounded',
				'content_layout'     => $settings['content_layout'] ?? 'stacked',
				'float_style'        => $settings['float_style'] ?? 'emoji',
				'float_icon'         => $settings['float_icon'] ?? 'cookie',
				'float_side'         => $settings['float_side'] ?? 'left',
				'primary_color'      => $settings['primary_color'] ?? '#1f7a4d',
				'primary_text_color' => $settings['primary_text_color'] ?? '#ffffff',
				'banner_bg_color'    => $settings['banner_bg_color'] ?? '#ffffff',
				'banner_text_color'  => $settings['banner_text_color'] ?? '#333333',
				'reject_bg_color'    => $settings['reject_bg_color'] ?? '#f0f0f0',
				'reject_text_color'  => $settings['reject_text_color'] ?? '#333333',
				'logo_url'           => $settings['logo_url'] ?? '',
				'cookie_lifetime'    => intval( $settings['cookie_lifetime'] ?? 365 ),
				'cookie_icon'        => $settings['cookie_icon'] ?? '',
				// Saknas nyckeln (äldre installationer) ska knappen visas som tidigare.
				'float_button_enabled' => ! isset( $settings['float_button_enabled'] ) || ! empty( $settings['float_button_enabled'] ),
				// Den flytande knappens texter. Statusorden läses upp efter
				// knappens namn i stilen med statusprick.
				'float_label'        => __( 'Cookies', 'cocookie' ),
				'float_label_long'   => __( 'Cookie-inställningar', 'cocookie' ),
				'status_all'         => __( 'allt godkänt', 'cocookie' ),
				'status_custom'      => __( 'eget val', 'cocookie' ),
				'status_necessary'   => __( 'bara nödvändiga', 'cocookie' ),
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
				'google_consent_enabled'   => ! empty( $settings['google_consent_enabled'] ),
				'google_consent_intro'     => $settings['google_consent_intro'] ?? __( 'Vi använder Googles annonstjänster.', 'cocookie' ),
				'google_consent_link_text' => $settings['google_consent_link_text'] ?? __( 'Läs hur Google hanterar dina personuppgifter.', 'cocookie' ),
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

		// Sidans språk skickas vidare till filtret oförändrat, så att
		// översättningsplugin får veta vilket språk besökaren faktiskt är på.
		$lang        = self::get_current_language();
		$banner_lang = self::resolve_banner_language( $settings, $lang );

		if ( 'sv' !== $banner_lang ) {
			$config = self::apply_translation( $config, $banner_lang );
		}

		// "Om cookies" beskriver var knappen sitter. Sitter den till höger
		// byts hörnet i standardtexten, på alla inbyggda språk.
		if ( 'right' === $config['settings']['float_side'] ) {
			$config['settings']['about_manage_desc'] = str_replace(
				array( 'vänstra hörnet', 'bottom left corner', 'venstre hjørne' ),
				array( 'högra hörnet', 'bottom right corner', 'no' === $banner_lang ? 'høyre hjørne' : 'højre hjørne' ),
				$config['settings']['about_manage_desc']
			);
		}

		// Språket bannern visas på, för lang-attributet i mallen.
		$config['lang'] = $banner_lang;

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
			'float_label'        => 'Cookies',
			'float_label_long'   => 'Cookie-inställningar',
			'status_all'         => 'allt godkänt',
			'status_custom'      => 'eget val',
			'status_necessary'   => 'bara nödvändiga',
			'manage_title'       => 'Hantera cookie-inställningar',
			'manage_text'        => 'Här kan du ändra eller återkalla ditt samtycke.',
			'consent_date_label' => 'Samtyckesdatum:',
			'consent_id_label'   => 'Ditt samtyckes-ID:',
			'required_label'     => '(krävs alltid)',
			'withdraw_text'      => 'Dra tillbaka samtycke',
			'hide_details_text'  => 'Dölj detaljer',
			'show_details_text'  => 'Visa detaljer',
			'policy_link_text'   => 'Läs vår integritetspolicy.',
			'google_consent_intro'     => 'Vi använder Googles annonstjänster.',
			'google_consent_link_text' => 'Läs hur Google hanterar dina personuppgifter.',
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

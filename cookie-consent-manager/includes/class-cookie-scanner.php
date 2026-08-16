<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCM_Cookie_Scanner {

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes() {
        register_rest_route( 'cc/v1', '/scan', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'handle_scan' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ) );

        register_rest_route( 'cc/v1', '/scan/import', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'handle_import' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ) );

        register_rest_route( 'cc/v1', '/scan/import-all', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'handle_import_all' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ) );

        register_rest_route( 'cc/v1', '/audit', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'handle_audit' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ) );

        register_rest_route( 'cc/v1', '/audit/block', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'handle_block_cookie' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ) );

        register_rest_route( 'cc/v1', '/audit/unblock', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'handle_unblock_cookie' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ) );
    }

    public static function get_known_cookies() {
        return array(
            // Nödvändiga - WordPress
            array( 'pattern' => '/^wordpress_[a-f0-9]+$/i',  'category' => 'necessary', 'provider' => 'WordPress', 'purpose' => 'Autentisering och inloggningssession.' ),
            array( 'pattern' => '/^wordpress_logged_in_/i',   'category' => 'necessary', 'provider' => 'WordPress', 'purpose' => 'Identifierar inloggad användare.' ),
            array( 'pattern' => '/^wordpress_sec_/i',         'category' => 'necessary', 'provider' => 'WordPress', 'purpose' => 'Säker autentisering.' ),
            array( 'pattern' => '/^wp-settings-\d+$/i',       'category' => 'necessary', 'provider' => 'WordPress', 'purpose' => 'Sparar användarens adminpanelsinställningar.' ),
            array( 'pattern' => '/^wp-settings-time-\d+$/i',  'category' => 'necessary', 'provider' => 'WordPress', 'purpose' => 'Tidsstämpel för wp-settings.' ),
            array( 'pattern' => '/^wordpress_test_cookie$/i',  'category' => 'necessary', 'provider' => 'WordPress', 'purpose' => 'Testar om cookies är aktiverade i webbläsaren.' ),
            array( 'pattern' => '/^wp-postpass_/i',            'category' => 'necessary', 'provider' => 'WordPress', 'purpose' => 'Lösenordsskydd för inlägg.' ),
            array( 'pattern' => '/^comment_author_/i',         'category' => 'necessary', 'provider' => 'WordPress', 'purpose' => 'Sparar kommentarsförfattarens namn.' ),
            array( 'pattern' => '/^comment_author_email_/i',   'category' => 'necessary', 'provider' => 'WordPress', 'purpose' => 'Sparar kommentarsförfattarens e-post.' ),
            array( 'pattern' => '/^comment_author_url_/i',     'category' => 'necessary', 'provider' => 'WordPress', 'purpose' => 'Sparar kommentarsförfattarens webbplats.' ),
            array( 'pattern' => '/^PHPSESSID$/i',              'category' => 'necessary', 'provider' => 'PHP',       'purpose' => 'PHP-sessionsidentifierare.' ),
            array( 'pattern' => '/^cc_consent$/i',             'category' => 'necessary', 'provider' => 'Cookie Consent Manager', 'purpose' => 'Sparar besökarens cookie-samtycke.' ),

            // Nödvändiga - WooCommerce
            array( 'pattern' => '/^woocommerce_cart_hash$/i',        'category' => 'necessary', 'provider' => 'WooCommerce', 'purpose' => 'Hjälper WooCommerce att avgöra när kundvagnen ändras.' ),
            array( 'pattern' => '/^woocommerce_items_in_cart$/i',    'category' => 'necessary', 'provider' => 'WooCommerce', 'purpose' => 'Hjälper WooCommerce att avgöra när kundvagnen ändras.' ),
            array( 'pattern' => '/^woocommerce_recently_viewed$/i',  'category' => 'necessary', 'provider' => 'WooCommerce', 'purpose' => 'Sparar senast visade produkter.' ),
            array( 'pattern' => '/^wp_woocommerce_session_/i',       'category' => 'necessary', 'provider' => 'WooCommerce', 'purpose' => 'Unikt sessions-ID för varje kund.' ),
            array( 'pattern' => '/^wc_cart_/i',                      'category' => 'necessary', 'provider' => 'WooCommerce', 'purpose' => 'Kundvagnsinformation.' ),
            array( 'pattern' => '/^wc_fragments_/i',                 'category' => 'necessary', 'provider' => 'WooCommerce', 'purpose' => 'Cachefragment för kundvagn.' ),

            // Nödvändiga - Säkerhet
            array( 'pattern' => '/^tk_ai$/i',                   'category' => 'necessary', 'provider' => 'Jetpack',    'purpose' => 'Jetpack anti-spam-identifierare.' ),
            array( 'pattern' => '/^__cf_bm$/i',                 'category' => 'necessary', 'provider' => 'Cloudflare', 'purpose' => 'Bot-hantering och säkerhet.' ),
            array( 'pattern' => '/^cf_clearance$/i',             'category' => 'necessary', 'provider' => 'Cloudflare', 'purpose' => 'Clearance-cookie efter säkerhetskontroll.' ),
            array( 'pattern' => '/^__cfduid$/i',                 'category' => 'necessary', 'provider' => 'Cloudflare', 'purpose' => 'Identifierar betrodda webbtrafik.' ),
            array( 'pattern' => '/^_cfruid$/i',                  'category' => 'necessary', 'provider' => 'Cloudflare', 'purpose' => 'Rate limiting.' ),
            array( 'pattern' => '/^wordfence_verifiedHuman$/i',  'category' => 'necessary', 'provider' => 'Wordfence',  'purpose' => 'Verifiering av mänsklig besökare.' ),
            array( 'pattern' => '/^wfwaf-authcookie-/i',         'category' => 'necessary', 'provider' => 'Wordfence',  'purpose' => 'Brandväggsautentisering.' ),

            // Nödvändiga - Övrigt
            array( 'pattern' => '/^elementor$/i',              'category' => 'necessary', 'provider' => 'Elementor', 'purpose' => 'Elementor-sidbyggarens sessionsdata.' ),
            array( 'pattern' => '/^wpml_browser_redirect_test$/i', 'category' => 'necessary', 'provider' => 'WPML',  'purpose' => 'Testar webbläsaromdirigering.' ),
            array( 'pattern' => '/^icl_current_language$/i',   'category' => 'necessary', 'provider' => 'WPML',      'purpose' => 'Sparar valt språk.' ),
            array( 'pattern' => '/^pll_language$/i',           'category' => 'necessary', 'provider' => 'Polylang',   'purpose' => 'Sparar valt språk.' ),
            array( 'pattern' => '/^gdpr\[consent_types\]$/i',  'category' => 'necessary', 'provider' => 'GDPR',      'purpose' => 'Sparar GDPR-samtycke.' ),
            array( 'pattern' => '/^viewed_cookie_policy$/i',   'category' => 'necessary', 'provider' => 'Cookie Law Info', 'purpose' => 'Sparar att cookie-policyn har visats.' ),
            array( 'pattern' => '/^cookielawinfo-/i',          'category' => 'necessary', 'provider' => 'Cookie Law Info', 'purpose' => 'Cookie-samtyckesstatus.' ),

            // Analys - Google Analytics
            array( 'pattern' => '/^_ga$/i',           'category' => 'analytics', 'provider' => 'Google Analytics',   'purpose' => 'Unik besöksidentifierare för Google Analytics.' ),
            array( 'pattern' => '/^_ga_/i',           'category' => 'analytics', 'provider' => 'Google Analytics',   'purpose' => 'Används av GA4 för att särskilja sessioner.' ),
            array( 'pattern' => '/^_gid$/i',          'category' => 'analytics', 'provider' => 'Google Analytics',   'purpose' => 'Identifierar unika besökare under 24 timmar.' ),
            array( 'pattern' => '/^_gat$/i',          'category' => 'analytics', 'provider' => 'Google Analytics',   'purpose' => 'Begränsar antalet förfrågningar till Google Analytics.' ),
            array( 'pattern' => '/^_gat_/i',          'category' => 'analytics', 'provider' => 'Google Analytics',   'purpose' => 'Begränsar förfrågningar (anpassad tracker).' ),
            array( 'pattern' => '/^__utma$/i',        'category' => 'analytics', 'provider' => 'Google Analytics',   'purpose' => 'Unik besökare och sessionsspårning (klassisk).' ),
            array( 'pattern' => '/^__utmb$/i',        'category' => 'analytics', 'provider' => 'Google Analytics',   'purpose' => 'Sessionsidentifiering (klassisk).' ),
            array( 'pattern' => '/^__utmc$/i',        'category' => 'analytics', 'provider' => 'Google Analytics',   'purpose' => 'Session-cookie (klassisk).' ),
            array( 'pattern' => '/^__utmt$/i',        'category' => 'analytics', 'provider' => 'Google Analytics',   'purpose' => 'Begränsar förfrågningsfrekvens (klassisk).' ),
            array( 'pattern' => '/^__utmz$/i',        'category' => 'analytics', 'provider' => 'Google Analytics',   'purpose' => 'Spårar trafikkälla och kampanj (klassisk).' ),
            array( 'pattern' => '/^__utmv$/i',        'category' => 'analytics', 'provider' => 'Google Analytics',   'purpose' => 'Anpassade besökarvariabler (klassisk).' ),
            array( 'pattern' => '/^_dc_gtm_/i',       'category' => 'analytics', 'provider' => 'Google Tag Manager', 'purpose' => 'Spårar laddning av Google Tag Manager.' ),

            // Analys - Hotjar
            array( 'pattern' => '/^_hj[A-Z]/i',       'category' => 'analytics', 'provider' => 'Hotjar',  'purpose' => 'Hotjar-session och beteendespårning.' ),
            array( 'pattern' => '/^_hjid$/i',          'category' => 'analytics', 'provider' => 'Hotjar',  'purpose' => 'Unikt användar-ID för Hotjar.' ),
            array( 'pattern' => '/^_hjSession_/i',     'category' => 'analytics', 'provider' => 'Hotjar',  'purpose' => 'Hotjar-sessionsdata.' ),
            array( 'pattern' => '/^_hjSessionUser_/i', 'category' => 'analytics', 'provider' => 'Hotjar',  'purpose' => 'Identifierar Hotjar-användare.' ),
            array( 'pattern' => '/^_hjAbsoluteSessionInProgress$/i', 'category' => 'analytics', 'provider' => 'Hotjar', 'purpose' => 'Indikerar pågående Hotjar-session.' ),
            array( 'pattern' => '/^_hjFirstSeen$/i',   'category' => 'analytics', 'provider' => 'Hotjar',  'purpose' => 'Identifierar första besöket.' ),
            array( 'pattern' => '/^_hjIncludedInPageviewSample$/i', 'category' => 'analytics', 'provider' => 'Hotjar', 'purpose' => 'Avgör om besökaren inkluderas i sidvisningsurvalet.' ),
            array( 'pattern' => '/^_hjIncludedInSessionSample$/i',  'category' => 'analytics', 'provider' => 'Hotjar', 'purpose' => 'Avgör om besökaren inkluderas i sessionsurvalet.' ),

            // Analys - Matomo / Piwik
            array( 'pattern' => '/^_pk_id\./i',  'category' => 'analytics', 'provider' => 'Matomo', 'purpose' => 'Unikt besökar-ID för Matomo.' ),
            array( 'pattern' => '/^_pk_ses\./i', 'category' => 'analytics', 'provider' => 'Matomo', 'purpose' => 'Sessionsdata för Matomo.' ),
            array( 'pattern' => '/^_pk_ref\./i', 'category' => 'analytics', 'provider' => 'Matomo', 'purpose' => 'Referrer-information för Matomo.' ),
            array( 'pattern' => '/^_pk_cvar\./i','category' => 'analytics', 'provider' => 'Matomo', 'purpose' => 'Anpassade variabler för Matomo.' ),

            // Analys - Övriga
            array( 'pattern' => '/^amplitude_id/i',   'category' => 'analytics', 'provider' => 'Amplitude',    'purpose' => 'Besöksidentifiering för Amplitude.' ),
            array( 'pattern' => '/^mp_[a-f0-9]+_mixpanel$/i', 'category' => 'analytics', 'provider' => 'Mixpanel', 'purpose' => 'Besöksspårning för Mixpanel.' ),
            array( 'pattern' => '/^ajs_anonymous_id$/i',      'category' => 'analytics', 'provider' => 'Segment',  'purpose' => 'Anonym besöksidentifiering.' ),
            array( 'pattern' => '/^ajs_user_id$/i',            'category' => 'analytics', 'provider' => 'Segment',  'purpose' => 'Användareidentifiering.' ),
            array( 'pattern' => '/^ajs_group_id$/i',           'category' => 'analytics', 'provider' => 'Segment',  'purpose' => 'Gruppidentifiering.' ),
            array( 'pattern' => '/^hubspotutk$/i',             'category' => 'analytics', 'provider' => 'HubSpot',  'purpose' => 'Spårar besökarens identitet.' ),
            array( 'pattern' => '/^__hssc$/i',                 'category' => 'analytics', 'provider' => 'HubSpot',  'purpose' => 'Sessionsinformation för HubSpot.' ),
            array( 'pattern' => '/^__hssrc$/i',                'category' => 'analytics', 'provider' => 'HubSpot',  'purpose' => 'Avgör om ny session.' ),
            array( 'pattern' => '/^__hstc$/i',                 'category' => 'analytics', 'provider' => 'HubSpot',  'purpose' => 'Huvudspårning för HubSpot.' ),
            array( 'pattern' => '/^_clck$/i',                  'category' => 'analytics', 'provider' => 'Microsoft Clarity', 'purpose' => 'Besöksidentifierare för Clarity.' ),
            array( 'pattern' => '/^_clsk$/i',                  'category' => 'analytics', 'provider' => 'Microsoft Clarity', 'purpose' => 'Sessionsdata för Clarity.' ),
            array( 'pattern' => '/^CLID$/i',                   'category' => 'analytics', 'provider' => 'Microsoft Clarity', 'purpose' => 'Clarity-identifierare.' ),

            // Analys - Intercom
            array( 'pattern' => '/^intercom-id-/i',            'category' => 'analytics', 'provider' => 'Intercom', 'purpose' => 'Identifierar besökare för Intercom.' ),
            array( 'pattern' => '/^intercom-device-id-/i',     'category' => 'analytics', 'provider' => 'Intercom', 'purpose' => 'Identifierar enheten för Intercom.' ),
            array( 'pattern' => '/^intercom-session-/i',       'category' => 'analytics', 'provider' => 'Intercom', 'purpose' => 'Sessionsinformation för Intercom.' ),

            // Analys - Leadfeeder / Leadinfo
            array( 'pattern' => '/^_lfa$/i',                   'category' => 'analytics', 'provider' => 'Leadfeeder',  'purpose' => 'Identifierar företagsbesökare för Leadfeeder.' ),
            array( 'pattern' => '/^_li_ses/i',                 'category' => 'analytics', 'provider' => 'Leadinfo',    'purpose' => 'Sessionsspårning för Leadinfo.' ),
            array( 'pattern' => '/^_li_id/i',                  'category' => 'analytics', 'provider' => 'Leadinfo',    'purpose' => 'Besöksidentifierare för Leadinfo.' ),
            array( 'pattern' => '/^li_sesn$/i',                'category' => 'analytics', 'provider' => 'Leadinfo',    'purpose' => 'Sessionsnummer för Leadinfo.' ),
            array( 'pattern' => '/^li_sesd$/i',                'category' => 'analytics', 'provider' => 'Leadinfo',    'purpose' => 'Sessionsdata för Leadinfo.' ),
            array( 'pattern' => '/^snowplowOutQueue_/i',       'category' => 'analytics', 'provider' => 'Leadinfo',    'purpose' => 'Kö för statistikdata (Snowplow/Leadinfo).' ),

            // Nödvändiga - Cookiebot (consent-hantering)
            array( 'pattern' => '/^CookieConsent$/i',          'category' => 'necessary', 'provider' => 'Cookiebot',   'purpose' => 'Sparar besökarens cookie-samtycke.' ),
            array( 'pattern' => '/^CookieConsentBulkTicket$/i', 'category' => 'necessary', 'provider' => 'Cookiebot',  'purpose' => 'Möjliggör samtycke på flera domäner.' ),


            // Marknadsföring - Facebook / Meta
            array( 'pattern' => '/^_fbp$/i',    'category' => 'marketing', 'provider' => 'Meta (Facebook)', 'purpose' => 'Spårar besök för Facebook-annonsering.' ),
            array( 'pattern' => '/^_fbc$/i',    'category' => 'marketing', 'provider' => 'Meta (Facebook)', 'purpose' => 'Spårar klick från Facebook-annonser.' ),
            array( 'pattern' => '/^fbm_/i',     'category' => 'marketing', 'provider' => 'Meta (Facebook)', 'purpose' => 'Facebook Messenger-integration.' ),
            array( 'pattern' => '/^fbsr_/i',    'category' => 'marketing', 'provider' => 'Meta (Facebook)', 'purpose' => 'Facebook-inloggningstoken.' ),
            array( 'pattern' => '/^fr$/i',      'category' => 'marketing', 'provider' => 'Meta (Facebook)', 'purpose' => 'Annonsvisning och retargeting.' ),
            array( 'pattern' => '/^tr$/i',      'category' => 'marketing', 'provider' => 'Meta (Facebook)', 'purpose' => 'Facebook Pixel-spårning.' ),
            array( 'pattern' => '/^datr$/i',    'category' => 'marketing', 'provider' => 'Meta (Facebook)', 'purpose' => 'Webbläsaridentifiering för Facebook.' ),

            // Marknadsföring - Google Ads
            array( 'pattern' => '/^_gcl_au$/i', 'category' => 'marketing', 'provider' => 'Google Ads', 'purpose' => 'Google Ads konverteringsattribuering.' ),
            array( 'pattern' => '/^_gcl_aw$/i', 'category' => 'marketing', 'provider' => 'Google Ads', 'purpose' => 'Spårar klick från Google Ads.' ),
            array( 'pattern' => '/^_gcl_dc$/i', 'category' => 'marketing', 'provider' => 'Google Ads', 'purpose' => 'DoubleClick konverteringsspårning.' ),
            array( 'pattern' => '/^_gcl_gb$/i', 'category' => 'marketing', 'provider' => 'Google Ads', 'purpose' => 'Google Booking konvertering.' ),
            array( 'pattern' => '/^_gcl_gf$/i', 'category' => 'marketing', 'provider' => 'Google Ads', 'purpose' => 'Google Flights konvertering.' ),
            array( 'pattern' => '/^_gcl_ha$/i', 'category' => 'marketing', 'provider' => 'Google Ads', 'purpose' => 'Google Hotel Ads konvertering.' ),
            array( 'pattern' => '/^_gac_/i',    'category' => 'marketing', 'provider' => 'Google Ads', 'purpose' => 'Google Ads kampanjinformation.' ),
            array( 'pattern' => '/^goog_pem_mod$/i', 'category' => 'marketing', 'provider' => 'Google Ads', 'purpose' => 'Konverteringsmätning.' ),

            // Marknadsföring - DoubleClick
            array( 'pattern' => '/^IDE$/i',           'category' => 'marketing', 'provider' => 'Google DoubleClick', 'purpose' => 'Visar riktade annonser baserat på surfbeteende.' ),
            array( 'pattern' => '/^test_cookie$/i',    'category' => 'marketing', 'provider' => 'Google DoubleClick', 'purpose' => 'Kontrollerar om webbläsaren stöder cookies.' ),
            array( 'pattern' => '/^id$/i',             'category' => 'marketing', 'provider' => 'Google DoubleClick', 'purpose' => 'DoubleClick annonsidentifierare.' ),

            // Marknadsföring - LinkedIn
            array( 'pattern' => '/^li_sugr$/i',         'category' => 'marketing', 'provider' => 'LinkedIn', 'purpose' => 'LinkedIn Insight Tag besöksidentifiering.' ),
            array( 'pattern' => '/^li_fat_id$/i',        'category' => 'marketing', 'provider' => 'LinkedIn', 'purpose' => 'LinkedIn medlemsidentifiering.' ),
            array( 'pattern' => '/^lidc$/i',             'category' => 'marketing', 'provider' => 'LinkedIn', 'purpose' => 'LinkedIn routing-optimering.' ),
            array( 'pattern' => '/^bcookie$/i',          'category' => 'marketing', 'provider' => 'LinkedIn', 'purpose' => 'LinkedIn webbläsaridentifiering.' ),
            array( 'pattern' => '/^UserMatchHistory$/i', 'category' => 'marketing', 'provider' => 'LinkedIn', 'purpose' => 'LinkedIn annonssync.' ),
            array( 'pattern' => '/^AnalyticsSyncHistory$/i', 'category' => 'marketing', 'provider' => 'LinkedIn', 'purpose' => 'LinkedIn analysspårning.' ),
            array( 'pattern' => '/^ln_or$/i',            'category' => 'marketing', 'provider' => 'LinkedIn', 'purpose' => 'LinkedIn Insight-konvertering.' ),
            array( 'pattern' => '/^li_mc$/i',            'category' => 'marketing', 'provider' => 'LinkedIn', 'purpose' => 'LinkedIn marknadsföringscookie.' ),

            // Marknadsföring - Twitter/X
            array( 'pattern' => '/^muc_ads$/i',         'category' => 'marketing', 'provider' => 'Twitter/X',  'purpose' => 'Twitter annonsspårning.' ),
            array( 'pattern' => '/^personalization_id$/i', 'category' => 'marketing', 'provider' => 'Twitter/X', 'purpose' => 'Twitter personalisering.' ),
            array( 'pattern' => '/^guest_id$/i',         'category' => 'marketing', 'provider' => 'Twitter/X',  'purpose' => 'Twitter gäst-ID.' ),

            // Marknadsföring - TikTok
            array( 'pattern' => '/^_ttp$/i',   'category' => 'marketing', 'provider' => 'TikTok', 'purpose' => 'TikTok Pixel spårning.' ),
            array( 'pattern' => '/^tt_/i',     'category' => 'marketing', 'provider' => 'TikTok', 'purpose' => 'TikTok annonsspårning.' ),

            // Marknadsföring - Pinterest
            array( 'pattern' => '/^_pinterest_/i',   'category' => 'marketing', 'provider' => 'Pinterest', 'purpose' => 'Pinterest annonskonvertering.' ),
            array( 'pattern' => '/^_pin_unauth$/i',  'category' => 'marketing', 'provider' => 'Pinterest', 'purpose' => 'Pinterest gästidentifiering.' ),
            array( 'pattern' => '/^_epik$/i',        'category' => 'marketing', 'provider' => 'Pinterest', 'purpose' => 'Pinterest Tag-spårning.' ),

            // Marknadsföring - Övriga
            array( 'pattern' => '/^_uetsid$/i',      'category' => 'marketing', 'provider' => 'Microsoft Ads (Bing)', 'purpose' => 'Bing Ads konverteringsspårning.' ),
            array( 'pattern' => '/^_uetvid$/i',      'category' => 'marketing', 'provider' => 'Microsoft Ads (Bing)', 'purpose' => 'Bing Ads besökaridentifiering.' ),
            array( 'pattern' => '/^_rdt_uuid$/i',    'category' => 'marketing', 'provider' => 'Reddit',               'purpose' => 'Reddit Pixel konverteringsspårning.' ),
            array( 'pattern' => '/^_scid$/i',        'category' => 'marketing', 'provider' => 'Snapchat',             'purpose' => 'Snapchat Pixel identifiering.' ),
            array( 'pattern' => '/^sc_at$/i',        'category' => 'marketing', 'provider' => 'Snapchat',             'purpose' => 'Snapchat annonsspårning.' ),
            array( 'pattern' => '/^YSC$/i',          'category' => 'marketing', 'provider' => 'YouTube (Google)',      'purpose' => 'YouTube sessionsidentifiering.' ),
            array( 'pattern' => '/^VISITOR_INFO1_LIVE$/i', 'category' => 'marketing', 'provider' => 'YouTube (Google)', 'purpose' => 'YouTube besöksidentifiering.' ),
            array( 'pattern' => '/^CONSENT$/i',      'category' => 'marketing', 'provider' => 'Google',               'purpose' => 'Google samtyckessparning.' ),
            array( 'pattern' => '/^NID$/i',          'category' => 'marketing', 'provider' => 'Google',               'purpose' => 'Anpassar annonser på Google-egenskaper.' ),
            array( 'pattern' => '/^DV$/i',           'category' => 'marketing', 'provider' => 'Google',               'purpose' => 'Google annonsanpassning.' ),
            array( 'pattern' => '/^1P_JAR$/i',       'category' => 'marketing', 'provider' => 'Google',               'purpose' => 'Googles annonspreferenser.' ),
        );
    }

    /**
     * Classify a cookie by name.
     *
     * @deprecated 2.1.0 Använd CoCookie_Cookie_Patterns::match() istället. Tas bort i 2.2.0.
     * @param string $name Cookie name.
     * @return array Associative array with category_slug, provider, purpose.
     */
    public static function classify_cookie( $name ) {
        $known = self::get_known_cookies();

        foreach ( $known as $entry ) {
            if ( preg_match( $entry['pattern'], $name ) ) {
                return array(
                    'category_slug' => $entry['category'],
                    'provider'      => $entry['provider'],
                    'purpose'       => $entry['purpose'],
                );
            }
        }

        return array(
            'category_slug' => 'unclassified',
            'provider'      => 'Okänd',
            'purpose'       => 'Syftet med denna cookie är okänt. Granska manuellt och flytta till rätt kategori.',
        );
    }

    public static function ensure_table_exists() {
        global $wpdb;
        $table = $wpdb->prefix . 'cc_scan_results';
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
            CCM_Database::activate();
        }
    }

    public static function handle_scan( WP_REST_Request $request ) {
        $cookies = $request->get_param( 'cookies' );
        if ( ! is_array( $cookies ) ) {
            return new WP_Error( 'invalid_data', 'Cookies must be an array.', array( 'status' => 400 ) );
        }

        self::ensure_table_exists();

        global $wpdb;
        $table = $wpdb->prefix . 'cc_scan_results';

        // Clear previous scan results
        $wpdb->query( "TRUNCATE TABLE {$table}" );

        $results = array();
        $now     = current_time( 'mysql' );

        foreach ( $cookies as $cookie ) {
            $name         = sanitize_text_field( $cookie['name'] ?? '' );
            $storage_type = sanitize_text_field( $cookie['storage_type'] ?? 'cookie' );

            // Cookie-värden lagras aldrig — de kan innehålla sessionstokens.
            $value        = '';

            if ( ! in_array( $storage_type, array( 'cookie', 'localStorage', 'sessionStorage' ), true ) ) {
                $storage_type = 'cookie';
            }

            if ( empty( $name ) ) {
                continue;
            }

            $match = self::classify_cookie( $name );

            $wpdb->insert( $table, array(
                'name'               => $name,
                'value_sample'       => $value,
                'domain'             => sanitize_text_field( $cookie['domain'] ?? '' ),
                'storage_type'       => $storage_type,
                'suggested_category' => $match['category_slug'],
                'suggested_provider' => $match['provider'],
                'suggested_purpose'  => $match['purpose'],
                'is_imported'        => 0,
                'scanned_at'         => $now,
            ) );

            $results[] = array(
                'id'                 => $wpdb->insert_id,
                'name'               => $name,
                'value_sample'       => $value,
                'domain'             => $cookie['domain'] ?? '',
                'storage_type'       => $storage_type,
                'suggested_category' => $match['category_slug'],
                'suggested_provider' => $match['provider'],
                'suggested_purpose'  => $match['purpose'],
                'is_imported'        => 0,
            );
        }

        update_option( 'ccm_last_scan', $now );

        return new WP_REST_Response( array(
            'success' => true,
            'results' => $results,
        ), 200 );
    }

    public static function get_scan_results() {
        global $wpdb;
        $table = $wpdb->prefix . 'cc_scan_results';
        return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id ASC", ARRAY_A );
    }

    public static function handle_import( WP_REST_Request $request ) {
        $scan_id = intval( $request->get_param( 'scan_id' ) );
        if ( ! $scan_id ) {
            return new WP_Error( 'invalid_id', 'Invalid scan result ID.', array( 'status' => 400 ) );
        }

        $result = self::import_cookie( $scan_id );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    public static function handle_import_all( WP_REST_Request $request ) {
        $results  = self::get_scan_results();
        $imported = 0;
        $skipped  = 0;
        $errors   = array();

        foreach ( $results as $row ) {
            if ( $row['is_imported'] ) {
                $skipped++;
                continue;
            }
            $result = self::import_cookie( $row['id'] );
            if ( is_wp_error( $result ) ) {
                $code = $result->get_error_code();
                if ( $code === 'already_exists' || $code === 'already_imported' ) {
                    $skipped++;
                } else {
                    $errors[] = $row['name'] . ': ' . $result->get_error_message();
                }
            } else {
                $imported++;
            }
        }

        return new WP_REST_Response( array(
            'success'  => $imported > 0 || empty( $errors ),
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ), 200 );
    }

    public static function handle_audit( WP_REST_Request $request ) {
        $cookies = $request->get_param( 'cookies' );
        if ( ! is_array( $cookies ) ) {
            return new WP_Error( 'invalid_data', 'Cookies must be an array.', array( 'status' => 400 ) );
        }

        global $wpdb;
        $cookies_table    = $wpdb->prefix . 'cc_cookies';
        $categories_table = $wpdb->prefix . 'cc_categories';

        $registered = $wpdb->get_results(
            "SELECT c.*, cat.slug AS category_slug, cat.title AS category_title, cat.is_required
             FROM {$cookies_table} c
             JOIN {$categories_table} cat ON c.category_id = cat.id",
            ARRAY_A
        );

        // Build lookup of found cookie names
        $found_names = array();
        foreach ( $cookies as $cookie ) {
            $name = sanitize_text_field( $cookie['name'] ?? '' );
            if ( $name ) {
                $found_names[ $name ] = true;
            }
        }

        // Check each registered cookie
        $registered_results = array();
        $violations         = array();
        $registered_names   = array();

        foreach ( $registered as $row ) {
            $name        = $row['name'];
            $is_found    = isset( $found_names[ $name ] );
            $is_required = intval( $row['is_required'] );

            $registered_names[ $name ] = true;

            if ( $is_found && ! $is_required ) {
                // Non-required cookie found without consent = violation
                $status = 'violation';
                $violations[] = array(
                    'name'     => $name,
                    'category' => $row['category_title'],
                );
            } elseif ( $is_found && $is_required ) {
                $status = 'ok';
            } else {
                $status = 'warning';
            }

            $registered_results[] = array(
                'name'          => $name,
                'category'      => $row['category_title'],
                'category_slug' => $row['category_slug'],
                'is_required'   => $is_required,
                'found'         => $is_found,
                'status'        => $status,
            );
        }

        // Find unknown cookies
        $unknown = array();
        foreach ( $cookies as $cookie ) {
            $name = sanitize_text_field( $cookie['name'] ?? '' );
            if ( $name && ! isset( $registered_names[ $name ] ) ) {
                $unknown[] = array(
                    'name'   => $name,
                    'domain' => sanitize_text_field( $cookie['domain'] ?? '' ),
                );
            }
        }

        $blocked = get_option( 'ccm_blocked_cookies', array() );

        return new WP_REST_Response( array(
            'registered' => $registered_results,
            'violations' => $violations,
            'unknown'    => $unknown,
            'blocked'    => $blocked,
        ), 200 );
    }

    public static function handle_block_cookie( WP_REST_Request $request ) {
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        if ( empty( $name ) ) {
            return new WP_Error( 'invalid_name', 'Cookie name is required.', array( 'status' => 400 ) );
        }

        $blocked = get_option( 'ccm_blocked_cookies', array() );
        if ( ! in_array( $name, $blocked, true ) ) {
            $blocked[] = $name;
            update_option( 'ccm_blocked_cookies', $blocked );
        }

        return new WP_REST_Response( array( 'success' => true, 'blocked' => $blocked ), 200 );
    }

    public static function handle_unblock_cookie( WP_REST_Request $request ) {
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        if ( empty( $name ) ) {
            return new WP_Error( 'invalid_name', 'Cookie name is required.', array( 'status' => 400 ) );
        }

        $blocked = get_option( 'ccm_blocked_cookies', array() );
        $blocked = array_values( array_diff( $blocked, array( $name ) ) );
        update_option( 'ccm_blocked_cookies', $blocked );

        return new WP_REST_Response( array( 'success' => true, 'blocked' => $blocked ), 200 );
    }

    public static function import_cookie( $scan_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'cc_scan_results';

        $scan = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $scan_id ),
            ARRAY_A
        );

        if ( ! $scan ) {
            return new WP_Error( 'not_found', 'Scan result not found.', array( 'status' => 404 ) );
        }

        if ( $scan['is_imported'] ) {
            return new WP_Error( 'already_imported', 'Cookie already imported.', array( 'status' => 400 ) );
        }

        // Find category ID by slug
        $cat_table   = $wpdb->prefix . 'cc_categories';
        $category_id = $wpdb->get_var(
            $wpdb->prepare( "SELECT id FROM {$cat_table} WHERE slug = %s", $scan['suggested_category'] )
        );

        if ( ! $category_id ) {
            return new WP_Error( 'category_not_found', 'Category not found: ' . $scan['suggested_category'], array( 'status' => 400 ) );
        }

        // Check if cookie already exists
        $cookies_table = $wpdb->prefix . 'cc_cookies';
        $existing      = $wpdb->get_var(
            $wpdb->prepare( "SELECT id FROM {$cookies_table} WHERE name = %s", $scan['name'] )
        );

        if ( $existing ) {
            $wpdb->update( $table, array( 'is_imported' => 1 ), array( 'id' => $scan_id ) );
            return new WP_Error( 'already_exists', 'Cookie already exists in database.', array( 'status' => 400 ) );
        }

        $cookie_id = CCM_Categories::create_cookie( array(
            'category_id' => $category_id,
            'name'        => $scan['name'],
            'provider'    => $scan['suggested_provider'],
            'purpose'     => $scan['suggested_purpose'],
            'expiry'      => '',
        ) );

        if ( ! $cookie_id ) {
            return new WP_Error( 'insert_failed', 'Could not insert cookie into database.', array( 'status' => 500 ) );
        }

        $wpdb->update( $table, array( 'is_imported' => 1 ), array( 'id' => $scan_id ) );

        return true;
    }
}

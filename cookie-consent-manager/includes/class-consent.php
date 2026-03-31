<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCM_Consent {

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes() {
        register_rest_route( 'cc/v1', '/consent', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'save_consent' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( 'cc/v1', '/config', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'get_config' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( 'cc/v1', '/consent/erase', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'erase_consent' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ) );
    }

    public static function save_consent( WP_REST_Request $request ) {
        // Verify nonce from X-WP-Nonce header
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_Error( 'invalid_nonce', 'Ogiltig säkerhetstoken.', array( 'status' => 403 ) );
        }

        // Rate limiting: max 10 consent saves per minute per IP
        $ip         = self::get_client_ip();
        $rate_key   = 'ccm_rate_' . md5( $ip );
        $rate_count = (int) get_transient( $rate_key );
        if ( $rate_count >= 10 ) {
            return new WP_Error( 'rate_limited', 'För många förfrågningar.', array( 'status' => 429 ) );
        }
        set_transient( $rate_key, $rate_count + 1, 60 );

        $categories = $request->get_param( 'categories' );
        if ( ! is_array( $categories ) ) {
            return new WP_Error( 'invalid_data', 'Categories must be an object.', array( 'status' => 400 ) );
        }

        // Sanitize: only allow known category slugs with boolean values
        $all_categories = CCM_Categories::get_all();
        $valid_slugs    = wp_list_pluck( $all_categories, 'slug' );
        $clean          = array();
        foreach ( $all_categories as $cat ) {
            if ( $cat['is_required'] ) {
                $clean[ $cat['slug'] ] = true;
            } elseif ( in_array( $cat['slug'], $valid_slugs, true ) ) {
                $clean[ $cat['slug'] ] = ! empty( $categories[ $cat['slug'] ] );
            }
        }

        $uuid       = wp_generate_uuid4();
        $ip_salt    = wp_generate_password( 16, false );
        $ip_hash    = hash( 'sha256', $ip . $ip_salt );

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'cc_consents',
            array(
                'consent_uuid' => $uuid,
                'ip_hash'      => $ip_hash,
                'user_agent'   => sanitize_text_field( substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200 ) ),
                'categories'   => wp_json_encode( $clean ),
                'created_at'   => current_time( 'mysql' ),
            )
        );

        return new WP_REST_Response( array(
            'success' => true,
            'uuid'    => $uuid,
        ), 200 );
    }

    /**
     * GDPR Art. 17 — Right to Erasure. Delete consent records by UUID.
     */
    public static function erase_consent( WP_REST_Request $request ) {
        $uuid = sanitize_text_field( $request->get_param( 'uuid' ) );
        if ( empty( $uuid ) ) {
            return new WP_Error( 'missing_uuid', 'UUID krävs.', array( 'status' => 400 ) );
        }

        global $wpdb;
        $table   = $wpdb->prefix . 'cc_consents';
        $deleted = $wpdb->delete( $table, array( 'consent_uuid' => $uuid ) );

        if ( $deleted === false ) {
            return new WP_Error( 'delete_failed', 'Radering misslyckades.', array( 'status' => 500 ) );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'deleted' => (int) $deleted,
        ), 200 );
    }

    public static function get_config( WP_REST_Request $request ) {
        $categories      = CCM_Categories::get_all();
        $cookies_grouped = CCM_Categories::get_cookies_grouped();
        $settings        = get_option( 'ccm_settings', array() );

        $config = array(
            'categories' => array(),
            'settings'   => array(
                'banner_title'       => $settings['banner_title'] ?? 'Vi använder cookies',
                'banner_text'        => $settings['banner_text'] ?? 'Denna webbplats använder cookies för att förbättra din upplevelse.',
                'accept_all_text'    => $settings['accept_all_text'] ?? 'Acceptera alla',
                'reject_all_text'    => $settings['reject_all_text'] ?? 'Avvisa alla',
                'save_text'          => $settings['save_text'] ?? 'Spara inställningar',
                'settings_text'      => $settings['settings_text'] ?? 'Inställningar',
                'position'           => $settings['position'] ?? 'bottom',
                'primary_color'      => $settings['primary_color'] ?? '#2271b1',
                'cookie_lifetime'    => intval( $settings['cookie_lifetime'] ?? 365 ),
            ),
        );

        foreach ( $categories as $cat ) {
            $cookies = isset( $cookies_grouped[ $cat['id'] ] ) ? $cookies_grouped[ $cat['id'] ] : array();
            $config['categories'][] = array(
                'slug'        => $cat['slug'],
                'title'       => $cat['title'],
                'description' => $cat['description'],
                'is_required' => (bool) $cat['is_required'],
                'cookies'     => $cookies,
            );
        }

        return new WP_REST_Response( $config, 200 );
    }

    public static function get_consents( $page = 1, $per_page = 20 ) {
        global $wpdb;
        $table  = $wpdb->prefix . 'cc_consents';
        $offset = ( $page - 1 ) * $per_page;

        $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        return array(
            'items'    => $items,
            'total'    => intval( $total ),
            'pages'    => ceil( $total / $per_page ),
            'page'     => $page,
            'per_page' => $per_page,
        );
    }

    public static function get_statistics() {
        global $wpdb;
        $table = $wpdb->prefix . 'cc_consents';

        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
        if ( $total === 0 ) {
            return array(
                'total'      => 0,
                'categories' => array(),
                'recent'     => array(),
            );
        }

        // Get all category slugs
        $all_categories = CCM_Categories::get_all();
        $cat_stats      = array();
        foreach ( $all_categories as $cat ) {
            $cat_stats[ $cat['slug'] ] = array(
                'title'    => $cat['title'],
                'slug'     => $cat['slug'],
                'accepted' => 0,
                'rejected' => 0,
                'required' => (bool) $cat['is_required'],
            );
        }

        // Count per category from all consents
        $rows = $wpdb->get_col( "SELECT categories FROM {$table}" );
        foreach ( $rows as $json ) {
            $cats = json_decode( $json, true );
            if ( ! is_array( $cats ) ) continue;
            foreach ( $cat_stats as $slug => &$stat ) {
                if ( isset( $cats[ $slug ] ) && $cats[ $slug ] ) {
                    $stat['accepted']++;
                } else {
                    $stat['rejected']++;
                }
            }
            unset( $stat );
        }

        // Daily counts for last 30 days
        $recent = $wpdb->get_results(
            "SELECT DATE(created_at) as date, COUNT(*) as count
             FROM {$table}
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            ARRAY_A
        );

        return array(
            'total'      => $total,
            'categories' => $cat_stats,
            'recent'     => $recent,
        );
    }

    private static function get_client_ip() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        // Only use REMOTE_ADDR — X-Forwarded-For is user-controlled and spoofable
        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
    }
}

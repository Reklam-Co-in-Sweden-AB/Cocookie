<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCM_Central_Reporter {

    const CRON_HOOK = 'ccm_central_daily_report';

    public static function init() {
        add_action( self::CRON_HOOK, array( __CLASS__, 'send_report' ) );
        register_deactivation_hook( CCM_PLUGIN_DIR . 'cookie-consent-manager.php', array( __CLASS__, 'clear_cron' ) );
    }

    public static function schedule_cron() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time(), 'daily', self::CRON_HOOK );
        }
    }

    public static function clear_cron() {
        wp_clear_scheduled_hook( self::CRON_HOOK );
    }

    public static function is_enabled() {
        $settings = get_option( 'ccm_central_settings', array() );
        return ! empty( $settings['enabled'] ) && ! empty( $settings['api_url'] ) && ! empty( $settings['api_key'] );
    }

    public static function build_report() {
        global $wpdb;

        $categories     = CCM_Categories::get_all();
        $cookies        = CCM_Categories::get_cookies();
        $blocked        = get_option( 'ccm_blocked_cookies', array() );
        $company_info   = get_option( 'ccm_company_info', array() );
        $stats          = CCM_Consent::get_statistics();

        // Check policy pages
        $has_privacy = ! empty( $company_info['privacy_page_id'] ) && get_post( $company_info['privacy_page_id'] );
        $has_cookie  = ! empty( $company_info['cookie_page_id'] ) && get_post( $company_info['cookie_page_id'] );

        // Audit data from last scan
        $scan_results   = CCM_Cookie_Scanner::get_scan_results();
        $last_scan      = get_option( 'ccm_last_scan', '' );

        // Build audit summary from registered cookies vs scan
        $cookies_table    = $wpdb->prefix . 'cc_cookies';
        $categories_table = $wpdb->prefix . 'cc_categories';
        $registered = $wpdb->get_results(
            "SELECT c.name, cat.slug AS category_slug, cat.is_required
             FROM {$cookies_table} c
             JOIN {$categories_table} cat ON c.category_id = cat.id",
            ARRAY_A
        );

        $registered_names = array();
        foreach ( $registered as $row ) {
            $registered_names[ $row['name'] ] = $row;
        }

        // Scan results as "found" cookies
        $found_names = array();
        foreach ( $scan_results as $sr ) {
            $found_names[ $sr['name'] ] = true;
        }

        $ok_count        = 0;
        $warning_count   = 0;
        $violation_count = 0;
        $violations_list = array();

        foreach ( $registered as $row ) {
            $is_found    = isset( $found_names[ $row['name'] ] );
            $is_required = intval( $row['is_required'] );

            if ( $is_found && $is_required ) {
                $ok_count++;
            } elseif ( $is_found && ! $is_required ) {
                $violation_count++;
                $violations_list[] = array(
                    'cookie_name' => $row['name'],
                    'category'    => $row['category_slug'],
                );
            } else {
                if ( $is_required ) {
                    $warning_count++;
                } else {
                    $ok_count++;
                }
            }
        }

        // Unknown cookies
        $unknown_list = array();
        foreach ( $scan_results as $sr ) {
            if ( ! isset( $registered_names[ $sr['name'] ] ) ) {
                $unknown_list[] = $sr['name'];
            }
        }

        // Acceptance rates
        $acceptance = array();
        if ( ! empty( $stats['categories'] ) ) {
            foreach ( $stats['categories'] as $slug => $cat_stat ) {
                if ( $cat_stat['required'] ) {
                    continue;
                }
                $total = $cat_stat['accepted'] + $cat_stat['rejected'];
                $acceptance[ $slug ] = $total > 0 ? round( $cat_stat['accepted'] / $total, 2 ) : 0;
            }
        }

        return array(
            'domain'         => wp_parse_url( home_url(), PHP_URL_HOST ),
            'report_date'    => wp_date( 'Y-m-d' ),
            'plugin_version' => CCM_VERSION,
            'wp_version'     => get_bloginfo( 'version' ),
            'php_version'    => phpversion(),

            'configuration' => array(
                'categories_count'      => count( $categories ),
                'cookies_registered'    => count( $cookies ),
                'has_privacy_policy'    => $has_privacy,
                'has_cookie_policy'     => $has_cookie,
                'gcm_enabled'           => true,
                'banner_position'       => get_option( 'ccm_settings', array() )['position'] ?? 'bottom',
                'cookie_lifetime_days'  => intval( get_option( 'ccm_settings', array() )['cookie_lifetime'] ?? 365 ),
                'blocked_cookies_count' => count( $blocked ),
                'dnt_supported'         => true,
            ),

            'audit' => array(
                'last_audit_date'    => $last_scan,
                'registered_cookies' => count( $registered ),
                'found_cookies'      => count( $scan_results ),
                'ok_count'           => $ok_count,
                'warning_count'      => $warning_count,
                'violation_count'    => $violation_count,
                'unknown_count'      => count( $unknown_list ),
                'violations'         => $violations_list,
                'unknown_cookies'    => array_slice( $unknown_list, 0, 20 ),
            ),

            'consent_stats' => array(
                'total_consents'   => $stats['total'] ?? 0,
                'last_30_days'     => array_sum( array_column( $stats['recent'] ?? array(), 'count' ) ),
                'acceptance_rates' => $acceptance,
            ),
        );
    }

    public static function send_report() {
        if ( ! self::is_enabled() ) {
            return false;
        }

        $settings = get_option( 'ccm_central_settings', array() );
        $report   = self::build_report();

        $api_key  = CCM_Admin::decrypt_api_key( $settings['api_key'] ?? '' );
        $response = wp_remote_post( trailingslashit( $settings['api_url'] ) . 'ccm-central/v1/report', array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type'    => 'application/json',
                'X-CCM-Api-Key'   => $api_key,
            ),
            'body' => wp_json_encode( $report ),
        ) );

        if ( is_wp_error( $response ) ) {
            update_option( 'ccm_central_last_error', $response->get_error_message() );
            return false;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 200 && ! empty( $body['success'] ) ) {
            update_option( 'ccm_central_last_report', wp_date( 'Y-m-d H:i:s' ) );
            delete_option( 'ccm_central_last_error' );
            return true;
        }

        update_option( 'ccm_central_last_error', $body['message'] ?? 'HTTP ' . $code );
        return false;
    }

    public static function test_connection() {
        $settings = get_option( 'ccm_central_settings', array() );
        if ( empty( $settings['api_url'] ) || empty( $settings['api_key'] ) ) {
            return array( 'success' => false, 'message' => 'API-URL och API-nyckel krävs.' );
        }

        $api_key  = CCM_Admin::decrypt_api_key( $settings['api_key'] ?? '' );
        $response = wp_remote_get( trailingslashit( $settings['api_url'] ) . 'ccm-central/v1/ping', array(
            'timeout' => 10,
            'headers' => array(
                'X-CCM-Api-Key' => $api_key,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 200 && ! empty( $body['success'] ) ) {
            return array( 'success' => true, 'message' => 'Anslutning OK — ' . ( $body['site_name'] ?? 'Verifierad' ) );
        }

        return array( 'success' => false, 'message' => $body['message'] ?? 'HTTP ' . $code );
    }
}

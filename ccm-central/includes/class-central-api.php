<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCMC_API {

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes() {
        register_rest_route( 'ccm-central/v1', '/ping', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'handle_ping' ),
            'permission_callback' => array( __CLASS__, 'verify_api_key' ),
        ) );

        register_rest_route( 'ccm-central/v1', '/report', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'handle_report' ),
            'permission_callback' => array( __CLASS__, 'verify_api_key' ),
        ) );
    }

    public static function verify_api_key( WP_REST_Request $request ) {
        $key = $request->get_header( 'X-CCM-Api-Key' );
        if ( empty( $key ) ) {
            return new WP_Error( 'missing_api_key', 'API-nyckel saknas i X-CCM-Api-Key headern.', array( 'status' => 401 ) );
        }

        $site = self::get_site_by_key( $key );
        if ( empty( $site ) ) {
            return new WP_Error( 'invalid_api_key', 'Ogiltig API-nyckel.', array( 'status' => 403 ) );
        }

        return true;
    }

    public static function get_site_by_key( $key ) {
        global $wpdb;
        $hash  = hash( 'sha256', $key );
        $table = $wpdb->prefix . 'ccmc_sites';
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE api_key_hash = %s", $hash ),
            ARRAY_A
        );
    }

    public static function handle_ping( WP_REST_Request $request ) {
        $site = self::get_site_by_key( $request->get_header( 'X-CCM-Api-Key' ) );
        return new WP_REST_Response( array(
            'success'   => true,
            'site_name' => $site['display_name'] ?: $site['domain'],
        ), 200 );
    }

    public static function handle_report( WP_REST_Request $request ) {
        $key  = $request->get_header( 'X-CCM-Api-Key' );
        $site = self::get_site_by_key( $key );

        if ( ! $site ) {
            return new WP_Error( 'invalid_key', 'Invalid API key.', array( 'status' => 403 ) );
        }

        $data = $request->get_json_params();
        if ( empty( $data ) ) {
            return new WP_Error( 'invalid_data', 'No report data.', array( 'status' => 400 ) );
        }

        global $wpdb;
        $reports_table    = $wpdb->prefix . 'ccmc_reports';
        $violations_table = $wpdb->prefix . 'ccmc_violations';
        $alerts_table     = $wpdb->prefix . 'ccmc_alerts';
        $sites_table      = $wpdb->prefix . 'ccmc_sites';
        $now              = current_time( 'mysql' );

        $config = $data['configuration'] ?? array();
        $audit  = $data['audit'] ?? array();
        $stats  = $data['consent_stats'] ?? array();
        $rates  = $stats['acceptance_rates'] ?? array();

        // Insert report
        $wpdb->insert( $reports_table, array(
            'site_id'               => $site['id'],
            'report_date'           => sanitize_text_field( $data['report_date'] ?? wp_date( 'Y-m-d' ) ),
            'plugin_version'        => sanitize_text_field( $data['plugin_version'] ?? '' ),
            'wp_version'            => sanitize_text_field( $data['wp_version'] ?? '' ),
            'php_version'           => sanitize_text_field( $data['php_version'] ?? '' ),
            'categories_count'      => intval( $config['categories_count'] ?? 0 ),
            'cookies_registered'    => intval( $config['cookies_registered'] ?? 0 ),
            'has_privacy_policy'    => ! empty( $config['has_privacy_policy'] ) ? 1 : 0,
            'has_cookie_policy'     => ! empty( $config['has_cookie_policy'] ) ? 1 : 0,
            'gcm_enabled'           => ! empty( $config['gcm_enabled'] ) ? 1 : 0,
            'cookie_lifetime'       => intval( $config['cookie_lifetime_days'] ?? 365 ),
            'blocked_cookies_count' => intval( $config['blocked_cookies_count'] ?? 0 ),
            'audit_ok_count'        => intval( $audit['ok_count'] ?? 0 ),
            'audit_warning_count'   => intval( $audit['warning_count'] ?? 0 ),
            'audit_violation_count' => intval( $audit['violation_count'] ?? 0 ),
            'audit_unknown_count'   => intval( $audit['unknown_count'] ?? 0 ),
            'consent_total'         => intval( $stats['total_consents'] ?? 0 ),
            'consent_last_30d'      => intval( $stats['last_30_days'] ?? 0 ),
            'acceptance_analytics'  => floatval( $rates['analytics'] ?? 0 ),
            'acceptance_marketing'  => floatval( $rates['marketing'] ?? 0 ),
            'raw_data'              => wp_json_encode( $data ),
            'created_at'            => $now,
        ) );

        $report_id = $wpdb->insert_id;

        // Process violations
        $report_violations = $audit['violations'] ?? array();
        foreach ( $report_violations as $v ) {
            $cookie_name = sanitize_text_field( $v['cookie_name'] ?? '' );
            if ( empty( $cookie_name ) ) {
                continue;
            }

            // Check if existing unresolved violation
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$violations_table} WHERE site_id = %d AND cookie_name = %s AND resolved_at IS NULL",
                $site['id'],
                $cookie_name
            ) );

            if ( ! $existing ) {
                $wpdb->insert( $violations_table, array(
                    'report_id'     => $report_id,
                    'site_id'       => $site['id'],
                    'cookie_name'   => $cookie_name,
                    'category'      => sanitize_text_field( $v['category'] ?? '' ),
                    'first_seen_at' => $now,
                ) );
            }
        }

        // Resolve violations no longer present
        $current_violation_names = array_column( $report_violations, 'cookie_name' );
        $unresolved = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, cookie_name FROM {$violations_table} WHERE site_id = %d AND resolved_at IS NULL",
            $site['id']
        ), ARRAY_A );

        foreach ( $unresolved as $uv ) {
            if ( ! in_array( $uv['cookie_name'], $current_violation_names, true ) ) {
                $wpdb->update( $violations_table, array( 'resolved_at' => $now ), array( 'id' => $uv['id'] ) );
            }
        }

        // Determine site status and create alerts
        $violation_count = intval( $audit['violation_count'] ?? 0 );
        $unknown_count   = intval( $audit['unknown_count'] ?? 0 );

        $new_status = 'active';
        if ( $violation_count > 0 ) {
            $new_status = 'critical';
            self::create_alert( $site['id'], 'violation', 'critical',
                $violation_count . ' överträdelse(r): cookies sätts utan samtycke.' );
        } elseif ( $unknown_count > 3 || empty( $config['has_privacy_policy'] ) || empty( $config['has_cookie_policy'] ) ) {
            $new_status = 'warning';
            if ( $unknown_count > 3 ) {
                self::create_alert( $site['id'], 'unknown_cookies', 'warning',
                    $unknown_count . ' okända cookies hittade.' );
            }
            if ( empty( $config['has_privacy_policy'] ) ) {
                self::create_alert( $site['id'], 'missing_policy', 'warning', 'Integritetspolicy saknas.' );
            }
            if ( empty( $config['has_cookie_policy'] ) ) {
                self::create_alert( $site['id'], 'missing_policy', 'warning', 'Cookiepolicy saknas.' );
            }
        }

        // Resolve old alerts if status improved
        if ( $new_status === 'active' ) {
            $wpdb->query( $wpdb->prepare(
                "UPDATE {$alerts_table} SET resolved_at = %s WHERE site_id = %d AND resolved_at IS NULL",
                $now,
                $site['id']
            ) );
        }

        $wpdb->update( $sites_table, array(
            'status'         => $new_status,
            'last_report_at' => $now,
        ), array( 'id' => $site['id'] ) );

        return new WP_REST_Response( array( 'success' => true, 'report_id' => $report_id ), 200 );
    }

    private static function create_alert( $site_id, $type, $severity, $message ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ccmc_alerts';

        // Don't duplicate unresolved alerts of same type
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE site_id = %d AND type = %s AND resolved_at IS NULL",
            $site_id,
            $type
        ) );

        if ( $existing ) {
            return;
        }

        $wpdb->insert( $table, array(
            'site_id'    => $site_id,
            'type'       => $type,
            'severity'   => $severity,
            'message'    => $message,
            'created_at' => current_time( 'mysql' ),
        ) );
    }
}

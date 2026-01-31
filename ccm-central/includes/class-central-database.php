<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCMC_Database {

    public static function activate() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sites = $wpdb->prefix . 'ccmc_sites';
        $reports = $wpdb->prefix . 'ccmc_reports';
        $violations = $wpdb->prefix . 'ccmc_violations';
        $alerts = $wpdb->prefix . 'ccmc_alerts';
        $agreements = $wpdb->prefix . 'ccmc_agreements';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( "CREATE TABLE {$sites} (
            id INT NOT NULL AUTO_INCREMENT,
            domain VARCHAR(255) NOT NULL,
            display_name VARCHAR(255) NOT NULL DEFAULT '',
            api_key_hash VARCHAR(64) NOT NULL,
            contact_name VARCHAR(255) NOT NULL DEFAULT '',
            contact_email VARCHAR(255) NOT NULL DEFAULT '',
            company_name VARCHAR(255) NOT NULL DEFAULT '',
            org_number VARCHAR(50) NOT NULL DEFAULT '',
            status ENUM('active','warning','critical','inactive') NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL,
            last_report_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY domain (domain),
            KEY api_key_hash (api_key_hash)
        ) {$charset};" );

        dbDelta( "CREATE TABLE {$reports} (
            id INT NOT NULL AUTO_INCREMENT,
            site_id INT NOT NULL,
            report_date DATE NOT NULL,
            plugin_version VARCHAR(20) NOT NULL DEFAULT '',
            wp_version VARCHAR(20) NOT NULL DEFAULT '',
            php_version VARCHAR(20) NOT NULL DEFAULT '',
            categories_count INT NOT NULL DEFAULT 0,
            cookies_registered INT NOT NULL DEFAULT 0,
            has_privacy_policy TINYINT(1) NOT NULL DEFAULT 0,
            has_cookie_policy TINYINT(1) NOT NULL DEFAULT 0,
            gcm_enabled TINYINT(1) NOT NULL DEFAULT 0,
            cookie_lifetime INT NOT NULL DEFAULT 365,
            blocked_cookies_count INT NOT NULL DEFAULT 0,
            audit_ok_count INT NOT NULL DEFAULT 0,
            audit_warning_count INT NOT NULL DEFAULT 0,
            audit_violation_count INT NOT NULL DEFAULT 0,
            audit_unknown_count INT NOT NULL DEFAULT 0,
            consent_total INT NOT NULL DEFAULT 0,
            consent_last_30d INT NOT NULL DEFAULT 0,
            acceptance_analytics DECIMAL(3,2) NOT NULL DEFAULT 0,
            acceptance_marketing DECIMAL(3,2) NOT NULL DEFAULT 0,
            raw_data LONGTEXT,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY site_id (site_id),
            KEY report_date (report_date)
        ) {$charset};" );

        dbDelta( "CREATE TABLE {$violations} (
            id INT NOT NULL AUTO_INCREMENT,
            report_id INT NOT NULL,
            site_id INT NOT NULL,
            cookie_name VARCHAR(255) NOT NULL,
            category VARCHAR(100) NOT NULL DEFAULT '',
            first_seen_at DATETIME NOT NULL,
            resolved_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY site_id (site_id),
            KEY resolved_at (resolved_at)
        ) {$charset};" );

        dbDelta( "CREATE TABLE {$alerts} (
            id INT NOT NULL AUTO_INCREMENT,
            site_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            severity ENUM('critical','warning','info') NOT NULL DEFAULT 'warning',
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            resolved_at DATETIME DEFAULT NULL,
            notified_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY site_id (site_id),
            KEY resolved_at (resolved_at)
        ) {$charset};" );

        dbDelta( "CREATE TABLE {$agreements} (
            id INT NOT NULL AUTO_INCREMENT,
            site_id INT NOT NULL,
            type VARCHAR(20) NOT NULL DEFAULT 'pub',
            version VARCHAR(20) NOT NULL DEFAULT '1.0',
            controller_name VARCHAR(255) NOT NULL DEFAULT '',
            controller_org VARCHAR(100) NOT NULL DEFAULT '',
            controller_address TEXT,
            controller_email VARCHAR(255) NOT NULL DEFAULT '',
            controller_contact VARCHAR(255) NOT NULL DEFAULT '',
            processor_name VARCHAR(255) NOT NULL DEFAULT '',
            processor_org VARCHAR(100) NOT NULL DEFAULT '',
            processor_address TEXT,
            processor_email VARCHAR(255) NOT NULL DEFAULT '',
            sub_processors TEXT,
            purpose TEXT,
            data_types TEXT,
            storage_period VARCHAR(100) NOT NULL DEFAULT '12 månader',
            signed_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY site_id (site_id)
        ) {$charset};" );

        update_option( 'ccmc_db_version', CCMC_VERSION );
    }
}

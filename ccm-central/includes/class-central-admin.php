<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCMC_Admin {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
    }

    public static function add_menu() {
        add_menu_page(
            'CCM Central',
            'CCM Central',
            'manage_options',
            'ccm-central',
            array( __CLASS__, 'render_page' ),
            'dashicons-shield',
            80
        );
    }

    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'ccm-central' ) === false ) {
            return;
        }
        wp_enqueue_style( 'ccmc-admin', CCMC_PLUGIN_URL . 'admin/central.css', array(), CCMC_VERSION );
        wp_enqueue_style( 'dashicons' );
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $tab  = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'overview';
        $tabs = array(
            'overview'   => 'Översikt',
            'sites'      => 'Sajter',
            'alerts'     => 'Varningar',
            'agreements' => 'PUB/DPA-avtal',
            'settings'   => 'Inställningar',
        );

        echo '<div class="wrap">';
        echo '<h1>CCM Central Dashboard</h1>';
        echo '<nav class="nav-tab-wrapper">';
        foreach ( $tabs as $slug => $label ) {
            $active = ( $tab === $slug ) ? ' nav-tab-active' : '';
            $url    = admin_url( 'admin.php?page=ccm-central&tab=' . $slug );
            echo '<a href="' . esc_url( $url ) . '" class="nav-tab' . $active . '">' . esc_html( $label ) . '</a>';
        }
        echo '</nav>';
        echo '<div class="ccmc-tab-content">';

        switch ( $tab ) {
            case 'sites':
                include CCMC_PLUGIN_DIR . 'admin/views/sites.php';
                break;
            case 'alerts':
                include CCMC_PLUGIN_DIR . 'admin/views/alerts.php';
                break;
            case 'agreements':
                include CCMC_PLUGIN_DIR . 'admin/views/agreements.php';
                break;
            case 'settings':
                include CCMC_PLUGIN_DIR . 'admin/views/settings.php';
                break;
            default:
                include CCMC_PLUGIN_DIR . 'admin/views/dashboard.php';
                break;
        }

        echo '</div></div>';
    }

    public static function handle_actions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Add site
        if ( isset( $_POST['ccmc_add_site'] ) && check_admin_referer( 'ccmc_site_action' ) ) {
            global $wpdb;
            $api_key = 'ccm_ak_' . bin2hex( random_bytes( 24 ) );
            $wpdb->insert( $wpdb->prefix . 'ccmc_sites', array(
                'domain'        => sanitize_text_field( $_POST['domain'] ?? '' ),
                'display_name'  => sanitize_text_field( $_POST['display_name'] ?? '' ),
                'api_key_hash'  => hash( 'sha256', $api_key ),
                'contact_name'  => sanitize_text_field( $_POST['contact_name'] ?? '' ),
                'contact_email' => sanitize_email( $_POST['contact_email'] ?? '' ),
                'company_name'  => sanitize_text_field( $_POST['company_name'] ?? '' ),
                'org_number'    => sanitize_text_field( $_POST['org_number'] ?? '' ),
                'status'        => 'inactive',
                'created_at'    => current_time( 'mysql' ),
            ) );

            set_transient( 'ccmc_new_api_key', $api_key, 60 );
            wp_redirect( admin_url( 'admin.php?page=ccm-central&tab=sites&msg=added' ) );
            exit;
        }

        // Delete site
        if ( isset( $_GET['ccmc_delete_site'] ) && check_admin_referer( 'ccmc_delete_site' ) ) {
            global $wpdb;
            $site_id = intval( $_GET['ccmc_delete_site'] );
            $wpdb->delete( $wpdb->prefix . 'ccmc_reports', array( 'site_id' => $site_id ) );
            $wpdb->delete( $wpdb->prefix . 'ccmc_violations', array( 'site_id' => $site_id ) );
            $wpdb->delete( $wpdb->prefix . 'ccmc_alerts', array( 'site_id' => $site_id ) );
            $wpdb->delete( $wpdb->prefix . 'ccmc_agreements', array( 'site_id' => $site_id ) );
            $wpdb->delete( $wpdb->prefix . 'ccmc_sites', array( 'id' => $site_id ) );
            wp_redirect( admin_url( 'admin.php?page=ccm-central&tab=sites&msg=deleted' ) );
            exit;
        }

        // Create agreement
        if ( isset( $_POST['ccmc_create_agreement'] ) && check_admin_referer( 'ccmc_agreement_action' ) ) {
            $id = CCMC_Agreements::create( $_POST );
            wp_redirect( admin_url( 'admin.php?page=ccm-central&tab=agreements&msg=created&view=' . $id ) );
            exit;
        }

        // Sign agreement
        if ( isset( $_GET['ccmc_sign_agreement'] ) && check_admin_referer( 'ccmc_sign_agreement' ) ) {
            CCMC_Agreements::mark_signed( intval( $_GET['ccmc_sign_agreement'] ) );
            wp_redirect( admin_url( 'admin.php?page=ccm-central&tab=agreements&msg=signed' ) );
            exit;
        }

        // Delete agreement
        if ( isset( $_GET['ccmc_delete_agreement'] ) && check_admin_referer( 'ccmc_delete_agreement' ) ) {
            CCMC_Agreements::delete( intval( $_GET['ccmc_delete_agreement'] ) );
            wp_redirect( admin_url( 'admin.php?page=ccm-central&tab=agreements&msg=deleted' ) );
            exit;
        }

        // Save processor info
        if ( isset( $_POST['ccmc_save_processor'] ) && check_admin_referer( 'ccmc_processor_action' ) ) {
            CCMC_Agreements::save_processor_info( $_POST );
            wp_redirect( admin_url( 'admin.php?page=ccm-central&tab=settings&msg=saved' ) );
            exit;
        }
    }
}

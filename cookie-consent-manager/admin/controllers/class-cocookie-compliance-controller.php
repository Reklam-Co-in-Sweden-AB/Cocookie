<?php
/**
 * Compliance controller.
 *
 * Combines consent log, scanner, and audit into one view with sub-tabs.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_Compliance_Controller {

	/**
	 * Render the compliance page.
	 */
	public static function render() {
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'consents';

		$tabs = array(
			'consents' => __( 'Samtyckes-logg', 'cocookie' ),
			'scanner'  => __( 'Scanner', 'cocookie' ),
			'audit'    => __( 'Cookie-audit', 'cocookie' ),
		);

		// Load tab-specific data
		$data = array(
			'tab'  => $tab,
			'tabs' => $tabs,
		);

		switch ( $tab ) {
			case 'scanner':
				$data['last_scan']    = get_option( 'cocookie_last_scan', get_option( 'ccm_last_scan', '' ) );
				$data['scan_results'] = CoCookie_REST_Scanner::get_scan_results();

				wp_enqueue_style( 'dashicons' );
				wp_enqueue_script( 'cocookie-scanner', COCOOKIE_PLUGIN_URL . 'admin/js/cocookie-scanner.js', array(), COCOOKIE_VERSION, true );
				wp_localize_script( 'cocookie-scanner', 'cocookieScanner', array(
					'restUrl'        => esc_url_raw( rest_url() ),
					'nonce'          => wp_create_nonce( 'wp_rest' ),
					'siteUrl'        => esc_url( home_url( '/' ) ),
					'cleanScanNonce' => wp_create_nonce( 'ccm_clean_scan' ),
				) );
				break;

			case 'audit':
				$data['blocked'] = get_option( 'cocookie_blocked_cookies', get_option( 'ccm_blocked_cookies', array() ) );

				wp_enqueue_style( 'dashicons' );
				wp_enqueue_script( 'cocookie-audit', COCOOKIE_PLUGIN_URL . 'admin/js/cocookie-audit.js', array(), COCOOKIE_VERSION, true );
				wp_localize_script( 'cocookie-audit', 'cocookieAudit', array(
					'restUrl' => esc_url_raw( rest_url() ),
					'nonce'   => wp_create_nonce( 'wp_rest' ),
					'siteUrl' => esc_url( home_url( '/' ) ),
				) );
				break;

			default: // consents
				$page             = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
				$data['consents'] = CoCookie_Consent::get_paginated( $page );
				break;
		}

		include COCOOKIE_PLUGIN_DIR . 'admin/views/new/compliance.php';
	}
}

<?php
/**
 * REST API controller for statistics endpoints.
 *
 * Serves consent statistics for the admin dashboard.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_REST_Stats {

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
		register_rest_route( $namespace, '/stats', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_stats' ),
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		) );
	}

	/**
	 * Get consent statistics.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function get_stats( WP_REST_Request $request ) {
		$stats = CoCookie_Consent::get_statistics();

		// Calculate compliance score
		$scan_results = CoCookie_REST_Scanner::get_scan_results();
		$registered   = class_exists( 'CoCookie_Categories' )
			? CoCookie_Categories::get_cookies()
			: CCM_Categories::get_cookies();
		$blocked      = get_option( 'cocookie_blocked_cookies', get_option( 'ccm_blocked_cookies', array() ) );

		$registered_names = wp_list_pluck( $registered, 'name' );
		$unknown_count    = 0;
		foreach ( $scan_results as $sr ) {
			if ( ! in_array( $sr['name'], $registered_names, true ) ) {
				$unknown_count++;
			}
		}

		$total_found = count( $scan_results );
		$compliance  = $total_found > 0
			? round( ( ( $total_found - $unknown_count ) / $total_found ) * 100 )
			: 100;

		$stats['compliance_score']    = $compliance;
		$stats['unknown_cookies']     = $unknown_count;
		$stats['blocked_cookies']     = count( $blocked );
		$stats['registered_cookies']  = count( $registered );
		$stats['last_scan']           = get_option( 'cocookie_last_scan', get_option( 'ccm_last_scan', '' ) );

		return new WP_REST_Response( $stats, 200 );
	}
}

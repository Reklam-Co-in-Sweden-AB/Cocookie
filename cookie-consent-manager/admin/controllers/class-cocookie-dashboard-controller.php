<?php
/**
 * Dashboard controller.
 *
 * Shows compliance score, quick stats, alerts, and recent activity.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_Dashboard_Controller {

	/**
	 * Render the dashboard page.
	 */
	public static function render() {
		$stats = CoCookie_Consent::get_statistics();

		// Compliance score
		$scan_results   = CoCookie_REST_Scanner::get_scan_results();
		$registered     = CoCookie_Categories::get_cookies();
		$blocked        = get_option( 'cocookie_blocked_cookies', get_option( 'ccm_blocked_cookies', array() ) );
		$last_scan      = get_option( 'cocookie_last_scan', get_option( 'ccm_last_scan', '' ) );

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
			: ( count( $registered ) > 0 ? 100 : 0 );

		// Alerts
		$alerts = array();
		if ( empty( $last_scan ) ) {
			$alerts[] = array(
				'type'    => 'warning',
				'message' => __( 'Ingen cookie-skanning har körts ännu. Kör en skanning för att identifiera cookies.', 'cocookie' ),
				'action'  => admin_url( 'admin.php?page=cocookie-compliance&tab=scanner' ),
				'label'   => __( 'Skanna nu', 'cocookie' ),
			);
		}
		if ( $unknown_count > 0 ) {
			$alerts[] = array(
				'type'    => 'error',
				'message' => sprintf(
					/* translators: %d: number of unknown cookies */
					__( '%d okända cookies hittade som inte finns i ditt register.', 'cocookie' ),
					$unknown_count
				),
				'action'  => admin_url( 'admin.php?page=cocookie-compliance&tab=audit' ),
				'label'   => __( 'Granska', 'cocookie' ),
			);
		}

		// Uppdateringsmeddelande (sätts av handle_check_update)
		$update_message = get_transient( 'cocookie_update_message' );
		$update_type    = get_transient( 'cocookie_update_type' );
		if ( $update_message ) {
			delete_transient( 'cocookie_update_message' );
			delete_transient( 'cocookie_update_type' );
		}

		$data = array(
			'compliance'       => $compliance,
			'stats'            => $stats,
			'registered_count' => count( $registered ),
			'blocked_count'    => count( $blocked ),
			'unknown_count'    => $unknown_count,
			'last_scan'        => $last_scan,
			'alerts'           => $alerts,
			'update_message'   => $update_message ?: '',
			'update_type'      => $update_type ?: 'info',
		);

		include COCOOKIE_PLUGIN_DIR . 'admin/views/dashboard.php';
	}
}

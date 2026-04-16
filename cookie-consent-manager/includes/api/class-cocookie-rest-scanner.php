<?php
/**
 * REST API controller for scanner and audit endpoints.
 *
 * Handles cookie scanning, importing scan results, auditing,
 * and cookie blocking/unblocking.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_REST_Scanner {

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
		$admin_permission = function () {
			return current_user_can( 'manage_options' );
		};

		register_rest_route( $namespace, '/scan', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'handle_scan' ),
			'permission_callback' => $admin_permission,
		) );

		register_rest_route( $namespace, '/scan/import', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'handle_import' ),
			'permission_callback' => $admin_permission,
		) );

		register_rest_route( $namespace, '/scan/import-all', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'handle_import_all' ),
			'permission_callback' => $admin_permission,
		) );

		register_rest_route( $namespace, '/audit', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'handle_audit' ),
			'permission_callback' => $admin_permission,
		) );

		register_rest_route( $namespace, '/audit/block', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'handle_block_cookie' ),
			'permission_callback' => $admin_permission,
		) );

		register_rest_route( $namespace, '/audit/unblock', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'handle_unblock_cookie' ),
			'permission_callback' => $admin_permission,
		) );
	}

	/**
	 * Kontrollerar om en cookie är admin-only och inte ska rapporteras för anonyma besökare.
	 *
	 * Admin-only-cookies (t.ex. Beaver Builders fl-* debugcookies) sätts endast när
	 * admin är inloggad och läcker inte till publika besökare. Dessa filtreras bort
	 * från scanresultat så de inte felaktigt listas som "cookies sajten sätter".
	 *
	 * Filtret cocookie_admin_only_cookies låter tredjepartskod utöka listan.
	 *
	 * @param string $name Cookie-namn.
	 * @return bool True om cookien är admin-only.
	 */
	private static function is_admin_only_cookie( $name ) {
		$default = array(
			'fl-builder-settings',
			'fl-cache-updater',
			'fl-assistant',
			'fl-asset-cache',
		);

		/**
		 * Filter: lista av prefix för admin-only cookies som aldrig ska listas i publika scans.
		 *
		 * @param array $list Lista med cookie-namnprefix.
		 */
		$admin_only = apply_filters( 'cocookie_admin_only_cookies', $default );

		foreach ( (array) $admin_only as $needle ) {
			if ( ! is_string( $needle ) || '' === $needle ) {
				continue;
			}
			if ( 0 === stripos( $name, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Handle cookie scan results from the frontend scanner.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_scan( WP_REST_Request $request ) {
		$cookies = $request->get_param( 'cookies' );
		if ( ! is_array( $cookies ) ) {
			return new WP_Error( 'invalid_data', __( 'Ogiltig data.', 'cocookie' ), array( 'status' => 400 ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'cc_scan_results';

		// Clear previous results
		$wpdb->query( "TRUNCATE TABLE {$table}" );

		$results = array();
		foreach ( $cookies as $cookie ) {
			$name         = sanitize_text_field( $cookie['name'] ?? '' );
			$value_sample = sanitize_text_field( substr( $cookie['value'] ?? '', 0, 50 ) );
			$domain       = sanitize_text_field( $cookie['domain'] ?? '' );
			$storage_type = sanitize_text_field( $cookie['storage_type'] ?? 'cookie' );

			if ( empty( $name ) ) {
				continue;
			}

			// Hoppa över admin-only cookies — de läcker inte till publika besökare.
			if ( self::is_admin_only_cookie( $name ) ) {
				continue;
			}

			// Use JSON pattern matcher instead of hardcoded patterns
			$match = CoCookie_Cookie_Patterns::match( $name );

			$wpdb->insert( $table, array(
				'name'               => $name,
				'value_sample'       => $value_sample,
				'domain'             => $domain,
				'storage_type'       => $storage_type,
				'suggested_category' => $match['category_slug'],
				'suggested_provider' => $match['provider'],
				'suggested_purpose'  => $match['purpose'],
				'is_imported'        => 0,
				'scanned_at'         => current_time( 'mysql' ),
			) );

			$results[] = array(
				'id'                 => $wpdb->insert_id,
				'name'               => $name,
				'value_sample'       => $value_sample,
				'domain'             => $domain,
				'storage_type'       => $storage_type,
				'suggested_category' => $match['category_slug'],
				'suggested_provider' => $match['provider'],
				'suggested_purpose'  => $match['purpose'],
				'is_imported'        => false,
			);
		}

		update_option( 'cocookie_last_scan', current_time( 'mysql' ) );

		return new WP_REST_Response( array(
			'success' => true,
			'results' => $results,
		), 200 );
	}

	/**
	 * Import a single scan result to the cookie registry.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_import( WP_REST_Request $request ) {
		$scan_id = intval( $request->get_param( 'scan_id' ) );
		$result  = self::import_cookie( $scan_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	 * Bulk import all unimported scan results.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function handle_import_all( WP_REST_Request $request ) {
		global $wpdb;
		$table   = $wpdb->prefix . 'cc_scan_results';
		$results = $wpdb->get_results(
			"SELECT * FROM {$table} WHERE is_imported = 0",
			ARRAY_A
		);

		$imported = 0;
		$skipped  = 0;
		$errors   = 0;

		foreach ( $results as $row ) {
			$result = self::import_cookie( $row['id'] );
			if ( is_wp_error( $result ) ) {
				$code = $result->get_error_code();
				if ( 'already_exists' === $code || 'already_imported' === $code ) {
					$skipped++;
				} else {
					$errors++;
				}
			} else {
				$imported++;
			}
		}

		return new WP_REST_Response( array(
			'success'  => $imported > 0 || 0 === $errors,
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		), 200 );
	}

	/**
	 * Audit current cookies against registered cookies.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_audit( WP_REST_Request $request ) {
		$cookies = $request->get_param( 'cookies' );
		if ( ! is_array( $cookies ) ) {
			return new WP_Error( 'invalid_data', __( 'Ogiltig data.', 'cocookie' ), array( 'status' => 400 ) );
		}

		global $wpdb;
		$cookies_table    = $wpdb->prefix . 'cc_cookies';
		$categories_table = $wpdb->prefix . 'cc_categories';

		$registered = $wpdb->get_results(
			"SELECT c.name, cat.slug AS category_slug, cat.is_required
			 FROM {$cookies_table} c
			 JOIN {$categories_table} cat ON c.category_id = cat.id",
			ARRAY_A
		);

		// Build lookup of found cookie names
		$found_names = array();
		foreach ( $cookies as $c ) {
			$name = sanitize_text_field( $c['name'] ?? '' );
			if ( $name ) {
				$found_names[ $name ] = true;
			}
		}

		$registered_results = array();
		$violations         = array();

		foreach ( $registered as $row ) {
			$is_found    = isset( $found_names[ $row['name'] ] );
			$is_required = intval( $row['is_required'] );

			if ( $is_found && ! $is_required ) {
				$status       = 'violation';
				$violations[] = array(
					'name'     => $row['name'],
					'category' => $row['category_slug'],
				);
			} elseif ( $is_found && $is_required ) {
				$status = 'ok';
			} elseif ( ! $is_found && $is_required ) {
				$status = 'warning';
			} else {
				$status = 'ok';
			}

			$registered_results[] = array(
				'name'     => $row['name'],
				'category' => $row['category_slug'],
				'required' => (bool) $is_required,
				'found'    => $is_found,
				'status'   => $status,
			);
		}

		// Find unknown cookies
		$registered_names = wp_list_pluck( $registered, 'name' );
		$unknown          = array();
		foreach ( $cookies as $c ) {
			$name = sanitize_text_field( $c['name'] ?? '' );
			if ( $name && ! in_array( $name, $registered_names, true ) ) {
				$unknown[] = array(
					'name'   => $name,
					'domain' => sanitize_text_field( $c['domain'] ?? '' ),
				);
			}
		}

		$blocked = get_option( 'cocookie_blocked_cookies', get_option( 'ccm_blocked_cookies', array() ) );

		return new WP_REST_Response( array(
			'success'    => true,
			'registered' => $registered_results,
			'violations' => $violations,
			'unknown'    => $unknown,
			'blocked'    => array_values( $blocked ),
		), 200 );
	}

	/**
	 * Add a cookie to the block list.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function handle_block_cookie( WP_REST_Request $request ) {
		$name    = sanitize_text_field( $request->get_param( 'name' ) );
		$blocked = get_option( 'cocookie_blocked_cookies', get_option( 'ccm_blocked_cookies', array() ) );

		if ( ! in_array( $name, $blocked, true ) ) {
			$blocked[] = $name;
			update_option( 'cocookie_blocked_cookies', $blocked );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'blocked' => array_values( $blocked ),
		), 200 );
	}

	/**
	 * Remove a cookie from the block list.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function handle_unblock_cookie( WP_REST_Request $request ) {
		$name    = sanitize_text_field( $request->get_param( 'name' ) );
		$blocked = get_option( 'cocookie_blocked_cookies', get_option( 'ccm_blocked_cookies', array() ) );
		$blocked = array_values( array_diff( $blocked, array( $name ) ) );

		update_option( 'cocookie_blocked_cookies', $blocked );

		return new WP_REST_Response( array(
			'success' => true,
			'blocked' => $blocked,
		), 200 );
	}

	/**
	 * Get scan results from database.
	 *
	 * @return array
	 */
	public static function get_scan_results() {
		global $wpdb;
		$table = $wpdb->prefix . 'cc_scan_results';
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY scanned_at DESC", ARRAY_A );
	}

	/**
	 * Import a single scan result to the cookie registry.
	 *
	 * @param int $scan_id Scan result ID.
	 * @return true|WP_Error
	 */
	private static function import_cookie( $scan_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'cc_scan_results';

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $scan_id ), ARRAY_A );
		if ( ! $row ) {
			return new WP_Error( 'not_found', __( 'Skanningsresultat hittades inte.', 'cocookie' ) );
		}

		if ( $row['is_imported'] ) {
			return new WP_Error( 'already_imported', __( 'Redan importerad.', 'cocookie' ) );
		}

		// Find category ID from slug
		$categories = class_exists( 'CoCookie_Categories' )
			? CoCookie_Categories::get_all()
			: CCM_Categories::get_all();

		$cat_id = 0;
		foreach ( $categories as $cat ) {
			if ( $cat['slug'] === $row['suggested_category'] ) {
				$cat_id = $cat['id'];
				break;
			}
		}

		if ( ! $cat_id ) {
			return new WP_Error( 'no_category', __( 'Kategori hittades inte.', 'cocookie' ) );
		}

		// Check if cookie already exists in registry
		$cookies_table = $wpdb->prefix . 'cc_cookies';
		$exists        = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$cookies_table} WHERE name = %s",
			$row['name']
		) );

		if ( $exists ) {
			$wpdb->update( $table, array( 'is_imported' => 1 ), array( 'id' => $scan_id ) );
			return new WP_Error( 'already_exists', __( 'Cookie finns redan i registret.', 'cocookie' ) );
		}

		// Create the cookie
		$create_class = class_exists( 'CoCookie_Categories' ) ? 'CoCookie_Categories' : 'CCM_Categories';
		$create_class::create_cookie( array(
			'category_id' => $cat_id,
			'name'        => $row['name'],
			'provider'    => $row['suggested_provider'],
			'purpose'     => $row['suggested_purpose'],
			'expiry'      => '',
		) );

		// Mark as imported
		$wpdb->update( $table, array( 'is_imported' => 1 ), array( 'id' => $scan_id ) );

		return true;
	}
}

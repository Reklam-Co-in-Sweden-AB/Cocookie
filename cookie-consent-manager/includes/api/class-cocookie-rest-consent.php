<?php
/**
 * REST API controller for consent endpoints.
 *
 * Handles saving visitor consent and GDPR erasure requests.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_REST_Consent {

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		// New namespace
		self::register_namespace( 'cocookie/v1' );
		// Backward compatible namespace
		self::register_namespace( 'cc/v1' );
	}

	/**
	 * Register routes under a given namespace.
	 *
	 * @param string $namespace REST namespace.
	 */
	private static function register_namespace( $namespace ) {
		register_rest_route( $namespace, '/consent', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'save_consent' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( $namespace, '/consent/erase', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'erase_consent' ),
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		) );
	}

	/**
	 * Save a visitor's consent decision.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function save_consent( WP_REST_Request $request ) {
		// Verify nonce
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'invalid_nonce', __( 'Ogiltig säkerhetstoken.', 'cocookie' ), array( 'status' => 403 ) );
		}

		// Rate limiting: max 10 per minute per IP
		$ip       = CoCookie_Consent::get_client_ip();
		$rate_key = 'cocookie_rate_' . md5( $ip );
		$count    = (int) get_transient( $rate_key );
		if ( $count >= 10 ) {
			return new WP_Error( 'rate_limited', __( 'För många förfrågningar.', 'cocookie' ), array( 'status' => 429 ) );
		}
		set_transient( $rate_key, $count + 1, 60 );

		$categories = $request->get_param( 'categories' );
		if ( ! is_array( $categories ) ) {
			return new WP_Error( 'invalid_data', __( 'Kategorier måste vara ett objekt.', 'cocookie' ), array( 'status' => 400 ) );
		}

		// Sanitize: only allow known category slugs with boolean values
		$all_categories = class_exists( 'CoCookie_Categories' )
			? CoCookie_Categories::get_all()
			: CCM_Categories::get_all();

		$clean = array();
		foreach ( $all_categories as $cat ) {
			if ( $cat['is_required'] ) {
				$clean[ $cat['slug'] ] = true;
			} else {
				$clean[ $cat['slug'] ] = ! empty( $categories[ $cat['slug'] ] );
			}
		}

		$user_agent = sanitize_text_field( substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200 ) );
		$uuid       = CoCookie_Consent::store( $clean, $ip, $user_agent );

		if ( false === $uuid ) {
			return new WP_Error( 'save_failed', __( 'Kunde inte spara samtycke.', 'cocookie' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'uuid'    => $uuid,
		), 200 );
	}

	/**
	 * Erase consent records by UUID.
	 *
	 * GDPR Art. 17 — Right to Erasure.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function erase_consent( WP_REST_Request $request ) {
		$uuid = sanitize_text_field( $request->get_param( 'uuid' ) );
		if ( empty( $uuid ) ) {
			return new WP_Error( 'missing_uuid', __( 'UUID krävs.', 'cocookie' ), array( 'status' => 400 ) );
		}

		$deleted = CoCookie_Consent::delete_by_uuid( $uuid );

		if ( false === $deleted ) {
			return new WP_Error( 'delete_failed', __( 'Radering misslyckades.', 'cocookie' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'deleted' => (int) $deleted,
		), 200 );
	}
}

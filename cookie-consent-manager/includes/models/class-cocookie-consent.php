<?php
/**
 * Consent data model.
 *
 * Handles storage, retrieval and statistics for consent records.
 * No REST API registration — that's in the API layer.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_Consent {

	/**
	 * Get the consents table name.
	 *
	 * @return string
	 */
	private static function table() {
		global $wpdb;
		return $wpdb->prefix . 'cc_consents';
	}

	/**
	 * Store a new consent record.
	 *
	 * @param array  $categories Associative array of category_slug => bool.
	 * @param string $ip         Client IP address (will be hashed).
	 * @param string $user_agent User agent string (will be hashed).
	 * @return string|false UUID on success, false on failure.
	 */
	public static function store( $categories, $ip, $user_agent ) {
		global $wpdb;

		$uuid    = wp_generate_uuid4();
		$ip_salt = wp_generate_password( 16, false );
		$ip_hash = hash( 'sha256', $ip . $ip_salt );

		// Hash user agent instead of storing plaintext (privacy improvement)
		$ua_hash = hash( 'sha256', substr( $user_agent, 0, 200 ) );

		$result = $wpdb->insert(
			self::table(),
			array(
				'consent_uuid' => $uuid,
				'ip_hash'      => $ip_hash,
				'user_agent'   => $ua_hash,
				'categories'   => wp_json_encode( $categories ),
				'created_at'   => current_time( 'mysql' ),
			)
		);

		return false !== $result ? $uuid : false;
	}

	/**
	 * Delete consent records by UUID.
	 *
	 * GDPR Art. 17 — Right to Erasure.
	 *
	 * @param string $uuid Consent UUID.
	 * @return int|false Number of deleted rows, or false on error.
	 */
	public static function delete_by_uuid( $uuid ) {
		global $wpdb;
		return $wpdb->delete( self::table(), array( 'consent_uuid' => $uuid ) );
	}

	/**
	 * Get paginated consent records.
	 *
	 * @param int $page     Page number (1-based).
	 * @param int $per_page Items per page.
	 * @return array With keys: items, total, pages, page, per_page.
	 */
	public static function get_paginated( $page = 1, $per_page = 20 ) {
		global $wpdb;
		$table  = self::table();
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

	/**
	 * Get consent statistics.
	 *
	 * @return array With keys: total, categories, recent.
	 */
	public static function get_statistics() {
		global $wpdb;
		$table = self::table();

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( 0 === $total ) {
			return array(
				'total'      => 0,
				'categories' => array(),
				'recent'     => array(),
			);
		}

		// Get all category slugs
		$all_categories = class_exists( 'CoCookie_Categories' )
			? CoCookie_Categories::get_all()
			: CCM_Categories::get_all();

		$cat_stats = array();
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
			if ( ! is_array( $cats ) ) {
				continue;
			}
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

	/**
	 * Get client IP address.
	 *
	 * Only uses REMOTE_ADDR — X-Forwarded-For is user-controlled and spoofable.
	 *
	 * @return string
	 */
	public static function get_client_ip() {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
	}
}

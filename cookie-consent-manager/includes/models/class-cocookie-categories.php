<?php
/**
 * Categories and cookies data model.
 *
 * Pure CRUD operations for cookie categories and individual cookies.
 * No REST API registration, no HTML output.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_Categories {

	/**
	 * Get the categories table name.
	 *
	 * @return string
	 */
	private static function table() {
		global $wpdb;
		return $wpdb->prefix . 'cc_categories';
	}

	/**
	 * Get all categories ordered by sort_order.
	 *
	 * @return array
	 */
	public static function get_all() {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT * FROM " . self::table() . " ORDER BY sort_order ASC",
			ARRAY_A
		);
	}

	/**
	 * Get a single category by ID.
	 *
	 * @param int $id Category ID.
	 * @return array|null
	 */
	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE id = %d", $id ),
			ARRAY_A
		);
	}

	/**
	 * Create a new category.
	 *
	 * @param array $data Category data.
	 * @return int Insert ID.
	 */
	public static function create( $data ) {
		global $wpdb;
		$wpdb->insert( self::table(), array(
			'slug'        => sanitize_title( $data['slug'] ),
			'title'       => sanitize_text_field( $data['title'] ),
			'description' => sanitize_textarea_field( $data['description'] ),
			'is_required' => intval( $data['is_required'] ),
			'sort_order'  => intval( $data['sort_order'] ),
		) );
		return $wpdb->insert_id;
	}

	/**
	 * Update a category.
	 *
	 * @param int   $id   Category ID.
	 * @param array $data Category data.
	 * @return int|false
	 */
	public static function update( $id, $data ) {
		global $wpdb;
		return $wpdb->update(
			self::table(),
			array(
				'slug'        => sanitize_title( $data['slug'] ),
				'title'       => sanitize_text_field( $data['title'] ),
				'description' => sanitize_textarea_field( $data['description'] ),
				'is_required' => intval( $data['is_required'] ),
				'sort_order'  => intval( $data['sort_order'] ),
			),
			array( 'id' => intval( $id ) )
		);
	}

	/**
	 * Delete a category and all its cookies.
	 *
	 * @param int $id Category ID.
	 * @return int|false
	 */
	public static function delete( $id ) {
		global $wpdb;
		$wpdb->delete( self::cookies_table(), array( 'category_id' => intval( $id ) ) );
		return $wpdb->delete( self::table(), array( 'id' => intval( $id ) ) );
	}

	// --- Cookie methods ---

	/**
	 * Get the cookies table name.
	 *
	 * @return string
	 */
	public static function cookies_table() {
		global $wpdb;
		return $wpdb->prefix . 'cc_cookies';
	}

	/**
	 * Get cookies, optionally filtered by category.
	 *
	 * @param int|null $category_id Optional category ID.
	 * @return array
	 */
	public static function get_cookies( $category_id = null ) {
		global $wpdb;
		$table = self::cookies_table();
		if ( $category_id ) {
			return $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$table} WHERE category_id = %d", $category_id ),
				ARRAY_A
			);
		}
		return $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A );
	}

	/**
	 * Get a single cookie by ID.
	 *
	 * @param int $id Cookie ID.
	 * @return array|null
	 */
	public static function get_cookie( $id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM " . self::cookies_table() . " WHERE id = %d", $id ),
			ARRAY_A
		);
	}

	/**
	 * Create a new cookie.
	 *
	 * @param array $data Cookie data.
	 * @return int Insert ID.
	 */
	public static function create_cookie( $data ) {
		global $wpdb;
		$wpdb->insert( self::cookies_table(), array(
			'category_id' => intval( $data['category_id'] ),
			'name'        => sanitize_text_field( $data['name'] ),
			'provider'    => sanitize_text_field( $data['provider'] ),
			'purpose'     => sanitize_textarea_field( $data['purpose'] ),
			'expiry'      => sanitize_text_field( $data['expiry'] ?? '' ),
		) );
		return $wpdb->insert_id;
	}

	/**
	 * Update a cookie.
	 *
	 * @param int   $id   Cookie ID.
	 * @param array $data Cookie data.
	 * @return int|false
	 */
	public static function update_cookie( $id, $data ) {
		global $wpdb;
		return $wpdb->update(
			self::cookies_table(),
			array(
				'category_id' => intval( $data['category_id'] ),
				'name'        => sanitize_text_field( $data['name'] ),
				'provider'    => sanitize_text_field( $data['provider'] ),
				'purpose'     => sanitize_textarea_field( $data['purpose'] ),
				'expiry'      => sanitize_text_field( $data['expiry'] ?? '' ),
			),
			array( 'id' => intval( $id ) )
		);
	}

	/**
	 * Delete a cookie.
	 *
	 * @param int $id Cookie ID.
	 * @return int|false
	 */
	public static function delete_cookie( $id ) {
		global $wpdb;
		return $wpdb->delete( self::cookies_table(), array( 'id' => intval( $id ) ) );
	}

	/**
	 * Get all cookies grouped by category_id.
	 *
	 * @return array Associative array keyed by category_id.
	 */
	public static function get_cookies_grouped() {
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT * FROM " . self::cookies_table() . " ORDER BY category_id ASC",
			ARRAY_A
		);
		$grouped = array();
		foreach ( $rows as $row ) {
			$grouped[ $row['category_id'] ][] = $row;
		}
		return $grouped;
	}

	/**
	 * Get the cookies table name (backward compat alias).
	 *
	 * @return string
	 */
	public static function get_cookies_table() {
		return self::cookies_table();
	}
}

<?php
/**
 * Cookie pattern matching engine.
 *
 * Loads patterns from data/cookie-patterns.json, matches cookie names
 * against known patterns, and caches parsed JSON in a transient.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_Cookie_Patterns {

	/**
	 * In-memory cache for loaded patterns.
	 *
	 * @var array|null
	 */
	private static $patterns = null;

	/**
	 * Transient key for cached patterns.
	 */
	const CACHE_KEY = 'cocookie_patterns';

	/**
	 * Cache TTL in seconds (1 hour).
	 */
	const CACHE_TTL = 3600;

	/**
	 * Get all patterns from JSON file.
	 *
	 * @return array Array of pattern entries.
	 */
	public static function get_all() {
		if ( null !== self::$patterns ) {
			return self::$patterns;
		}

		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			self::$patterns = $cached;
			return self::$patterns;
		}

		self::$patterns = self::load_from_file();
		set_transient( self::CACHE_KEY, self::$patterns, self::CACHE_TTL );

		return self::$patterns;
	}

	/**
	 * Match a cookie name against known patterns.
	 *
	 * @param string $name Cookie name to classify.
	 * @return array Associative array with category_slug, provider, purpose, duration.
	 */
	public static function match( $name ) {
		$patterns = self::get_all();

		foreach ( $patterns as $entry ) {
			$regex = '/' . $entry['pattern'] . '/i';
			if ( preg_match( $regex, $name ) ) {
				return array(
					'category_slug' => $entry['category'],
					'provider'      => $entry['service'],
					'purpose'       => $entry['purpose_sv'],
					'duration'      => $entry['duration'] ?? '',
				);
			}
		}

		return array(
			'category_slug' => 'unclassified',
			'provider'      => __( 'Okänd', 'cocookie' ),
			'purpose'       => __( 'Syftet med denna cookie är okänt. Granska manuellt och flytta till rätt kategori.', 'cocookie' ),
			'duration'      => '',
		);
	}

	/**
	 * Get patterns filtered by category.
	 *
	 * @param string $category Category slug (necessary, analytics, marketing).
	 * @return array Filtered patterns.
	 */
	public static function get_by_category( $category ) {
		$patterns = self::get_all();
		return array_filter( $patterns, function ( $entry ) use ( $category ) {
			return $entry['category'] === $category;
		} );
	}

	/**
	 * Get the pattern file version.
	 *
	 * @return string Version string.
	 */
	public static function get_version() {
		$data = self::load_raw();
		return $data['version'] ?? '0.0.0';
	}

	/**
	 * Clear the pattern cache.
	 *
	 * Call this when the JSON file is updated.
	 */
	public static function clear_cache() {
		delete_transient( self::CACHE_KEY );
		self::$patterns = null;
	}

	/**
	 * Load patterns from the JSON file.
	 *
	 * @return array Array of pattern entries.
	 */
	private static function load_from_file() {
		$data = self::load_raw();
		return $data['patterns'] ?? array();
	}

	/**
	 * Load and parse the raw JSON file.
	 *
	 * @return array Parsed JSON data.
	 */
	private static function load_raw() {
		$file = COCOOKIE_PLUGIN_DIR . 'data/cookie-patterns.json';
		if ( ! file_exists( $file ) ) {
			return array( 'patterns' => array() );
		}

		$json = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = json_decode( $json, true );

		if ( ! is_array( $data ) ) {
			return array( 'patterns' => array() );
		}

		return $data;
	}
}

<?php
/**
 * Background cookie scanner via WP-Cron.
 *
 * Fetches the site homepage server-side and analyzes the HTML
 * for script tags, tracking pixels, and known cookie patterns.
 * Does not require an admin to be on-page (unlike the iframe scanner).
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_Background_Scan {

	const CRON_HOOK = 'cocookie_background_scan';

	/**
	 * Schedule a one-time background scan.
	 */
	public static function schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( time() + 5, self::CRON_HOOK );
		}
	}

	/**
	 * Initialize cron hook.
	 */
	public static function init() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'run' ) );
	}

	/**
	 * Run the background scan.
	 *
	 * Fetches the homepage, parses for known script patterns,
	 * and stores results in the scan_results table.
	 *
	 * @return array|false Scan results or false on failure.
	 */
	public static function run() {
		$url = home_url( '/' );

		// TLS-verifiering ska alltid vara på — annars kan en MITM mata scannern
		// med godtycklig HTML och styra vilka cookies som registreras.
		$response = wp_remote_get( $url, array(
			'timeout'    => 30,
			'user-agent' => 'CoCookie Background Scanner/' . COCOOKIE_VERSION,
		) );

		if ( is_wp_error( $response ) ) {
			update_option( 'cocookie_scan_error', $response->get_error_message() );
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			update_option( 'cocookie_scan_error', __( 'Tom svarskropp från webbplatsen.', 'cocookie' ) );
			return false;
		}

		$found_items = self::analyze_html( $body );

		if ( empty( $found_items ) ) {
			update_option( 'cocookie_last_scan', current_time( 'mysql' ) );
			delete_option( 'cocookie_scan_error' );
			return array();
		}

		// Store results
		global $wpdb;
		$table = $wpdb->prefix . 'cc_scan_results';
		$wpdb->query( "TRUNCATE TABLE {$table}" );

		$results = array();
		foreach ( $found_items as $item ) {
			$match = CoCookie_Cookie_Patterns::match( $item['name'] );

			$wpdb->insert( $table, array(
				'name'               => sanitize_text_field( $item['name'] ),
				'value_sample'       => '',
				'domain'             => sanitize_text_field( $item['domain'] ?? '' ),
				'storage_type'       => 'script',
				'suggested_category' => $match['category_slug'],
				'suggested_provider' => $match['provider'],
				'suggested_purpose'  => $match['purpose'],
				'is_imported'        => 0,
				'scanned_at'         => current_time( 'mysql' ),
			) );

			$results[] = array(
				'name'     => $item['name'],
				'category' => $match['category_slug'],
				'provider' => $match['provider'],
			);
		}

		update_option( 'cocookie_last_scan', current_time( 'mysql' ) );
		delete_option( 'cocookie_scan_error' );

		/**
		 * Fires when a background scan completes.
		 *
		 * @param array $results Array of classified scan results.
		 */
		do_action( 'cocookie_scan_complete', $results );

		return $results;
	}

	/**
	 * Analyze HTML for known tracking scripts and services.
	 *
	 * Looks for script src URLs, inline tracking code, and
	 * meta tags that indicate cookie-setting services.
	 *
	 * @param string $html Page HTML.
	 * @return array Array of found items with 'name' and 'domain'.
	 */
	private static function analyze_html( $html ) {
		$found = array();
		$seen  = array();

		// Known script domains → cookie names they typically set
		$script_patterns = array(
			'googletagmanager.com'          => array( '_ga', '_gid', '_dc_gtm_' ),
			'google-analytics.com'          => array( '_ga', '_gid', '_gat' ),
			'gtag/js'                       => array( '_ga', '_gid' ),
			'analytics.google.com'          => array( '_ga', '_gid' ),
			'connect.facebook.net'          => array( '_fbp', '_fbc' ),
			'facebook.com/tr'               => array( '_fbp' ),
			'hotjar.com'                    => array( '_hjid', '_hjSession_' ),
			'clarity.ms'                    => array( '_clck', '_clsk' ),
			'linkedin.com/insight'          => array( 'li_sugr', 'bcookie' ),
			'snap.licdn.com'                => array( 'li_sugr', 'li_fat_id' ),
			'ads.linkedin.com'              => array( 'li_sugr', 'li_mc' ),
			'tiktok.com'                    => array( '_ttp' ),
			'analytics.tiktok.com'          => array( '_ttp', 'tt_' ),
			'pinterest.com/ct'              => array( '_pinterest_', '_pin_unauth' ),
			'bat.bing.com'                  => array( '_uetsid', '_uetvid' ),
			'twitter.com/i/adsct'           => array( 'muc_ads', 'personalization_id' ),
			'platform.twitter.com'          => array( 'muc_ads', 'guest_id' ),
			'youtube.com/iframe_api'        => array( 'YSC', 'VISITOR_INFO1_LIVE' ),
			'googleads.g.doubleclick.net'   => array( 'IDE', '_gcl_au' ),
			'googlesyndication.com'         => array( '__gads', '__gpi' ),
			'static.ads-twitter.com'        => array( 'muc_ads' ),
			'snap.licdn.com'                => array( 'li_sugr' ),
			'js.hs-scripts.com'             => array( 'hubspotutk', '__hssc', '__hstc' ),
			'js.hs-analytics.net'           => array( 'hubspotutk' ),
			'matomo'                        => array( '_pk_id', '_pk_ses' ),
			'cdn.segment.com'               => array( 'ajs_anonymous_id', 'ajs_user_id' ),
			'cdn.amplitude.com'             => array( 'amplitude_id' ),
			'cdn.mxpnl.com'                 => array( 'mp_mixpanel' ),
			'intercom.io'                   => array( 'intercom-id-', 'intercom-session-' ),
			'widget.intercom.io'            => array( 'intercom-id-' ),
			'js.intercomcdn.com'            => array( 'intercom-id-' ),
			'static.hotjar.com'             => array( '_hjid', '_hjSession_' ),
			'sc-static.net'                 => array( '_scid' ),
			'alb.reddit.com'                => array( '_rdt_uuid' ),
			'ct.pinterest.com'              => array( '_epik' ),
			'adroll.com'                    => array( '__adroll' ),
			'js.driftt.com'                 => array( 'driftt_aid' ),
			'embed.tawk.to'                 => array( 'tawk_', '__tawkuuid' ),
			'cdn.livechatinc.com'           => array( '__lc_cid' ),
			'crisp.chat'                    => array( 'crisp-client' ),
			'recaptcha/api.js'              => array( '_GRECAPTCHA' ),
			'www.google.com/recaptcha'      => array( '_GRECAPTCHA', 'rc::a' ),
		);

		// Find all script src URLs
		preg_match_all( '/<script[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $script_matches );
		if ( ! empty( $script_matches[1] ) ) {
			foreach ( $script_matches[1] as $src ) {
				foreach ( $script_patterns as $domain => $cookie_names ) {
					if ( stripos( $src, $domain ) !== false ) {
						foreach ( $cookie_names as $name ) {
							if ( ! isset( $seen[ $name ] ) ) {
								$seen[ $name ] = true;
								$found[] = array(
									'name'   => $name,
									'domain' => wp_parse_url( $src, PHP_URL_HOST ) ?: $domain,
								);
							}
						}
					}
				}
			}
		}

		// Check for Set-Cookie headers from response
		$cookies = wp_remote_retrieve_cookies( $GLOBALS['cocookie_scan_response'] ?? null );
		// Note: $cookies from wp_remote_get are not accessible here.
		// The iframe scanner remains the definitive source for actual cookies.

		// Always include WordPress core cookies
		$wp_core = array( 'wordpress_test_cookie', 'PHPSESSID' );
		foreach ( $wp_core as $name ) {
			if ( ! isset( $seen[ $name ] ) ) {
				$seen[ $name ] = true;
				$found[] = array(
					'name'   => $name,
					'domain' => wp_parse_url( home_url(), PHP_URL_HOST ),
				);
			}
		}

		// Check for inline gtag/fbq/ttq calls
		if ( preg_match( '/gtag\s*\(/', $html ) && ! isset( $seen['_ga'] ) ) {
			$found[] = array( 'name' => '_ga', 'domain' => 'google-analytics.com' );
			$seen['_ga'] = true;
		}
		if ( preg_match( '/fbq\s*\(/', $html ) && ! isset( $seen['_fbp'] ) ) {
			$found[] = array( 'name' => '_fbp', 'domain' => 'facebook.com' );
			$seen['_fbp'] = true;
		}
		if ( preg_match( '/ttq\.load\s*\(/', $html ) && ! isset( $seen['_ttp'] ) ) {
			$found[] = array( 'name' => '_ttp', 'domain' => 'tiktok.com' );
			$seen['_ttp'] = true;
		}
		if ( preg_match( '/hj\s*\(\s*[\'"]init[\'"]/', $html ) && ! isset( $seen['_hjid'] ) ) {
			$found[] = array( 'name' => '_hjid', 'domain' => 'hotjar.com' );
			$seen['_hjid'] = true;
		}

		return $found;
	}
}

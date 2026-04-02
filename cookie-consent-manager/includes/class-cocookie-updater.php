<?php
/**
 * GitHub-baserad auto-uppdatering.
 *
 * Kollar mot GitHub Releases efter nya versioner och integrerar
 * med WordPress inbyggda uppdateringssystem.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_Updater {

	const GITHUB_REPO    = 'Reklam-Co-in-Sweden-AB/Cocookie';
	const PLUGIN_BASENAME = 'cookie-consent-manager/cookie-consent-manager.php';
	const PLUGIN_SLUG    = 'cookie-consent-manager';
	const CACHE_KEY      = 'cocookie_github_release';
	const CACHE_TTL      = 43200; // 12 timmar

	/**
	 * Initiera uppdateringshooken.
	 */
	public static function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'check_for_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_source_dir' ), 10, 4 );
	}

	/**
	 * Kolla om det finns en ny version på GitHub.
	 */
	public static function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = self::get_latest_release();
		if ( ! $release ) {
			return $transient;
		}

		$remote_version  = ltrim( $release['tag_name'], 'v' );
		$current_version = COCOOKIE_VERSION;

		if ( version_compare( $remote_version, $current_version, '>' ) ) {
			$transient->response[ self::PLUGIN_BASENAME ] = (object) array(
				'slug'         => self::PLUGIN_SLUG,
				'plugin'       => self::PLUGIN_BASENAME,
				'new_version'  => $remote_version,
				'url'          => 'https://github.com/' . self::GITHUB_REPO,
				'package'      => self::get_download_url( $release ),
				'icons'        => array(),
				'banners'      => array(),
				'tested'       => '',
				'requires'     => '5.0',
				'requires_php' => '7.4',
			);
		}

		return $transient;
	}

	/**
	 * Visa plugin-info i WordPress uppdateringsdialoger.
	 */
	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}
		if ( ! isset( $args->slug ) || self::PLUGIN_SLUG !== $args->slug ) {
			return $result;
		}

		$release = self::get_latest_release();
		if ( ! $release ) {
			return $result;
		}

		return (object) array(
			'name'              => 'CoCookie',
			'slug'              => self::PLUGIN_SLUG,
			'version'           => ltrim( $release['tag_name'], 'v' ),
			'author'            => '<a href="https://github.com/' . self::GITHUB_REPO . '">CoCookie</a>',
			'homepage'          => 'https://github.com/' . self::GITHUB_REPO,
			'short_description' => 'GDPR-compliant cookie consent management for WordPress.',
			'sections'          => array(
				'description' => 'CoCookie — GDPR-kompatibel cookie consent med Google Consent Mode v2.',
				'changelog'   => nl2br( esc_html( $release['body'] ?? '' ) ),
			),
			'download_link'     => self::get_download_url( $release ),
			'requires'          => '5.0',
			'tested'            => '',
			'requires_php'      => '7.4',
			'last_updated'      => $release['published_at'] ?? '',
		);
	}

	/**
	 * Fixa GitHub zipball-mappnamnet.
	 *
	 * GitHub zipball packar upp till t.ex.:
	 *   Reklam-Co-in-Sweden-AB-Cocookie-abc1234/cookie-consent-manager/
	 *
	 * WordPress förväntar sig att källan direkt innehåller plugin-filer.
	 * Den här filtret byter namn på mappen till rätt slug.
	 *
	 * @param string      $source        Sökväg till uppackad källa.
	 * @param string      $remote_source Sökväg till fjärrkälla.
	 * @param WP_Upgrader $upgrader      Upgrader-instans.
	 * @param array       $hook_extra    Extra data.
	 * @return string|WP_Error
	 */
	public static function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra ) {
		// Kontrollera att det är vårt plugin som uppdateras
		if ( ! isset( $hook_extra['plugin'] ) || self::PLUGIN_BASENAME !== $hook_extra['plugin'] ) {
			return $source;
		}

		global $wp_filesystem;

		// Kolla om plugin-filen finns direkt i källmappen
		if ( $wp_filesystem->exists( $source . 'cookie-consent-manager.php' ) ) {
			// Filen finns direkt — byt bara mappnamn
			$new_source = trailingslashit( $remote_source ) . self::PLUGIN_SLUG . '/';
			if ( $source !== $new_source ) {
				$wp_filesystem->move( $source, $new_source );
			}
			return $new_source;
		}

		// Kolla om det finns en undermapp cookie-consent-manager/
		if ( $wp_filesystem->exists( $source . 'cookie-consent-manager/cookie-consent-manager.php' ) ) {
			// Flytta undermappen till rätt plats
			$new_source = trailingslashit( $remote_source ) . self::PLUGIN_SLUG . '/';
			$wp_filesystem->move( $source . 'cookie-consent-manager/', $new_source );
			// Ta bort den tomma GitHub-mappen
			$wp_filesystem->delete( $source, true );
			return $new_source;
		}

		// Sök rekursivt efter cookie-consent-manager.php
		$dirlist = $wp_filesystem->dirlist( $source );
		if ( is_array( $dirlist ) ) {
			foreach ( $dirlist as $name => $info ) {
				if ( 'd' !== $info['type'] ) {
					continue;
				}
				$subdir = trailingslashit( $source ) . $name . '/';
				// Kolla direkt i undermappen
				if ( $wp_filesystem->exists( $subdir . 'cookie-consent-manager.php' ) ) {
					$new_source = trailingslashit( $remote_source ) . self::PLUGIN_SLUG . '/';
					$wp_filesystem->move( $subdir, $new_source );
					$wp_filesystem->delete( $source, true );
					return $new_source;
				}
				// Kolla en nivå djupare
				if ( $wp_filesystem->exists( $subdir . 'cookie-consent-manager/cookie-consent-manager.php' ) ) {
					$new_source = trailingslashit( $remote_source ) . self::PLUGIN_SLUG . '/';
					$wp_filesystem->move( $subdir . 'cookie-consent-manager/', $new_source );
					$wp_filesystem->delete( $source, true );
					return $new_source;
				}
			}
		}

		return $source;
	}

	/**
	 * Hämta nedladdnings-URL för en release.
	 *
	 * Föredrar en uppladdad asset (cocookie-*.zip) framför GitHub zipball,
	 * eftersom zipball har fel mappstruktur.
	 *
	 * @param array $release GitHub release-data.
	 * @return string Nedladdnings-URL.
	 */
	private static function get_download_url( $release ) {
		// Kolla om det finns en uppladdad zip-asset
		if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if ( isset( $asset['name'] ) && preg_match( '/cocookie.*\.zip$/i', $asset['name'] ) ) {
					return $asset['browser_download_url'];
				}
			}
		}

		// Fallback: använd zipball (kräver fix_source_dir)
		return $release['zipball_url'] ?? '';
	}

	/**
	 * Hämta senaste releasen från GitHub API. Cachar i transient.
	 */
	private static function get_latest_release() {
		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			return $cached;
		}

		$url  = 'https://api.github.com/repos/' . self::GITHUB_REPO . '/releases/latest';
		$args = array(
			'timeout' => 10,
			'headers' => array(
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'CoCookie/' . COCOOKIE_VERSION,
			),
		);

		$token = defined( 'COCOOKIE_GITHUB_TOKEN' ) ? COCOOKIE_GITHUB_TOKEN : '';
		if ( $token ) {
			$args['headers']['Authorization'] = 'token ' . $token;
		}

		$response = wp_remote_get( $url, $args );
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['tag_name'] ) ) {
			return false;
		}

		set_transient( self::CACHE_KEY, $body, self::CACHE_TTL );
		return $body;
	}
}

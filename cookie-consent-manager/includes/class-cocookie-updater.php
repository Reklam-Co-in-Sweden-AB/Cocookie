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

	/**
	 * GitHub-repo i formatet owner/repo.
	 */
	const GITHUB_REPO = 'Reklam-Co-in-Sweden-AB/Cocookie';

	/**
	 * Plugin-filens basename (relativt till plugins/).
	 */
	const PLUGIN_BASENAME = 'cookie-consent-manager/cookie-consent-manager.php';

	/**
	 * Transient-nyckel för cachad release-data.
	 */
	const CACHE_KEY = 'cocookie_github_release';

	/**
	 * Cache-livslängd i sekunder (12 timmar).
	 */
	const CACHE_TTL = 43200;

	/**
	 * Initiera uppdateringshooken.
	 */
	public static function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'check_for_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_post_install', array( __CLASS__, 'after_install' ), 10, 3 );
	}

	/**
	 * Kolla om det finns en ny version på GitHub.
	 *
	 * @param object $transient WordPress update transient.
	 * @return object
	 */
	public static function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = self::get_latest_release();
		if ( ! $release ) {
			return $transient;
		}

		$remote_version = ltrim( $release['tag_name'], 'v' );
		$current_version = COCOOKIE_VERSION;

		if ( version_compare( $remote_version, $current_version, '>' ) ) {
			$transient->response[ self::PLUGIN_BASENAME ] = (object) array(
				'slug'        => 'cookie-consent-manager',
				'plugin'      => self::PLUGIN_BASENAME,
				'new_version' => $remote_version,
				'url'         => 'https://github.com/' . self::GITHUB_REPO,
				'package'     => $release['zipball_url'],
				'icons'       => array(),
				'banners'     => array(),
				'tested'      => '',
				'requires'    => '5.0',
				'requires_php' => '7.4',
			);
		}

		return $transient;
	}

	/**
	 * Visa plugin-info i WordPress uppdateringsdialoger.
	 *
	 * @param false|object|array $result Befintligt resultat.
	 * @param string             $action API-action.
	 * @param object             $args   Argument.
	 * @return false|object
	 */
	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || 'cookie-consent-manager' !== $args->slug ) {
			return $result;
		}

		$release = self::get_latest_release();
		if ( ! $release ) {
			return $result;
		}

		$remote_version = ltrim( $release['tag_name'], 'v' );

		return (object) array(
			'name'            => 'CoCookie',
			'slug'            => 'cookie-consent-manager',
			'version'         => $remote_version,
			'author'          => '<a href="https://github.com/' . self::GITHUB_REPO . '">CoCookie</a>',
			'homepage'        => 'https://github.com/' . self::GITHUB_REPO,
			'short_description' => 'GDPR-compliant cookie consent management for WordPress.',
			'sections'        => array(
				'description'  => 'CoCookie — GDPR-kompatibel cookie consent med Google Consent Mode v2.',
				'changelog'    => nl2br( esc_html( $release['body'] ?? '' ) ),
			),
			'download_link'   => $release['zipball_url'],
			'requires'        => '5.0',
			'tested'          => '',
			'requires_php'    => '7.4',
			'last_updated'    => $release['published_at'] ?? '',
		);
	}

	/**
	 * Fixa mappnamnet efter installation.
	 *
	 * GitHub zipball har formatet "owner-repo-hash/" som mappnamn.
	 * Vi byter namn till "cookie-consent-manager/" så WordPress hittar pluginet.
	 *
	 * @param bool  $response   Install response.
	 * @param array $hook_extra Extra hook-data.
	 * @param array $result     Install result.
	 * @return array
	 */
	public static function after_install( $response, $hook_extra, $result ) {
		global $wp_filesystem;

		if ( ! isset( $hook_extra['plugin'] ) || self::PLUGIN_BASENAME !== $hook_extra['plugin'] ) {
			return $result;
		}

		$install_dir = $result['destination'];
		$proper_dir  = WP_PLUGIN_DIR . '/cookie-consent-manager';

		// Byt namn från GitHub-format till rätt mappnamn
		if ( $install_dir !== $proper_dir ) {
			$wp_filesystem->move( $install_dir, $proper_dir );
			$result['destination'] = $proper_dir;
		}

		// Aktivera pluginet igen
		activate_plugin( self::PLUGIN_BASENAME );

		return $result;
	}

	/**
	 * Hämta senaste releasen från GitHub API.
	 *
	 * Cachar resultatet i en transient.
	 *
	 * @return array|false Release-data eller false vid fel.
	 */
	private static function get_latest_release() {
		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			return $cached;
		}

		$url = 'https://api.github.com/repos/' . self::GITHUB_REPO . '/releases/latest';

		$args = array(
			'timeout' => 10,
			'headers' => array(
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'CoCookie/' . COCOOKIE_VERSION,
			),
		);

		// Lägg till access token om konfigurerat (för privata repon)
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

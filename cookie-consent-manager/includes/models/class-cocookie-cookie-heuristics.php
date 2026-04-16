<?php
/**
 * Heuristik-baserade rekommendationer för cookies.
 *
 * Tittar på cookienamnet och gissar om cookien är kopplad till inloggning,
 * admin-verktyg eller utveckling. Används som komplement till den regelstyrda
 * pattern-matchningen — heuristiken hjälper administratören att avgöra om en
 * okategoriserad cookie faktiskt ska vara synlig i publika scans.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_Cookie_Heuristics {

	/**
	 * Föreslå en roll-hint baserat på cookienamnet.
	 *
	 * Returnerar en kort svensk mening eller null om ingen regel matchar.
	 * Matchning är icke-skiftlägeskänslig och använder substring-sökning.
	 *
	 * @param string $name Cookie-namn.
	 * @return string|null En kort beskrivande hint, eller null.
	 */
	public static function suggest( $name ) {
		if ( ! is_string( $name ) || '' === $name ) {
			return null;
		}

		$name_lower = strtolower( $name );

		$rules = self::get_rules();

		foreach ( $rules as $rule ) {
			foreach ( $rule['patterns'] as $needle ) {
				if ( strpos( $name_lower, $needle ) !== false ) {
					return $rule['hint'];
				}
			}
		}

		return null;
	}

	/**
	 * Hämtar heuristikreglerna, filtrerbara via cocookie_cookie_heuristics.
	 *
	 * Ordningen spelar roll — första träff vinner. Mest specifika regler först.
	 *
	 * @return array Lista med regler i formatet array( 'patterns' => [...], 'hint' => '...' ).
	 */
	private static function get_rules() {
		$rules = array(
			array(
				'patterns' => array(
					'logged_in',
					'loggedin',
					'wordpress_sec',
					'wordpress_test',
				),
				'hint'     => __( 'Troligen kopplad till WordPress-inloggning — granska om den ska listas publikt.', 'cocookie' ),
			),
			array(
				'patterns' => array(
					'_auth',
					'authtoken',
					'auth_token',
					'jwt',
					'bearer',
					'_token',
				),
				'hint'     => __( 'Troligen en autentiseringstoken — granska om den är publik.', 'cocookie' ),
			),
			array(
				'patterns' => array(
					'fl-',
					'elementor',
					'wp-stream-',
					'rank_math_',
					'yoast_',
					'acf_',
					'jetpack_admin',
					'wp_beaver',
					'fusionpanel',
					'fusionbuilder',
					'avada_',
					'litespeed_',
					'wpforms_',
					'redirection-',
					'googlesitekit',
					'wp-api-',
					'wp-json',
				),
				'hint'     => __( 'Troligen från admin-verktyg eller sidbyggare — sätts bara när du är inloggad.', 'cocookie' ),
			),
			array(
				'patterns' => array(
					'wp-admin',
					'_admin_',
					'admin-session',
				),
				'hint'     => __( 'Troligen kopplad till admin-sessionen — sätts bara för inloggade användare.', 'cocookie' ),
			),
			array(
				'patterns' => array(
					'debug',
					'_test_',
					'staging_',
					'_dev_',
				),
				'hint'     => __( 'Troligen från utvecklings- eller testmiljö — bör inte finnas i produktion.', 'cocookie' ),
			),
			array(
				'patterns' => array(
					'session',
					'sessid',
				),
				'hint'     => __( 'Troligen en sessionscookie — granska om besökare eller admin sätter den.', 'cocookie' ),
			),
			array(
				'patterns' => array(
					'history.store',
					'.store',
					'-store',
					'redux',
					'vuex',
				),
				'hint'     => __( 'Troligen JavaScript-state från en admin-app — inte en besökarcookie.', 'cocookie' ),
			),
		);

		/**
		 * Filter: anpassa heuristikreglerna.
		 *
		 * @param array $rules Lista med regler. Varje regel är en array med 'patterns' och 'hint'.
		 */
		return apply_filters( 'cocookie_cookie_heuristics', $rules );
	}
}

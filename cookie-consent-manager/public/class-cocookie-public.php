<?php
/**
 * CoCookie public-facing functionality.
 *
 * Handles: GCM default in <head>, banner template rendering,
 * asset enqueueing, cookie blocking, and shortcode.
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CoCookie_Public {

	/**
	 * Initialize public hooks.
	 */
	public static function init() {
		add_shortcode( 'cocookie_cookie_list', array( __CLASS__, 'render_cookie_list' ) );
		add_shortcode( 'ccm_cookie_list', array( __CLASS__, 'render_cookie_list' ) );
		add_shortcode( 'cocookie_settings', array( __CLASS__, 'render_settings_button' ) );
		add_shortcode( 'ccm_settings', array( __CLASS__, 'render_settings_button' ) );

		// These hooks only fire on the frontend, so no is_admin() check needed
		add_action( 'wp_head', array( __CLASS__, 'maybe_output_gcm_default' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

		// Render banner HTML early in body (before scripts) so JS can find it
		add_action( 'wp_body_open', array( __CLASS__, 'render_banner_template' ), 1 );
		// Fallback for themes that don't support wp_body_open
		add_action( 'wp_footer', array( __CLASS__, 'render_banner_template' ), 5 );

		// Output buffering for script/iframe blocking (only on frontend)
		add_action( 'template_redirect', array( __CLASS__, 'start_output_buffering' ) );

		// Ren skanning: sidan ska bete sig som för en besökare som accepterat allt,
		// så att skannern hittar alla cookies som faktiskt kan sättas.
		// Filtren kontrollerar själva om det är en ren skanning (nonce kräver
		// inloggad användare, så det går inte att avgöra redan här).
		add_filter( 'googlesitekit_analytics_tracking_disabled', array( __CLASS__, 'clean_scan_enable_sitekit_tracking' ) );
		add_filter( 'googlesitekit_consent_defaults', array( __CLASS__, 'clean_scan_grant_sitekit_consent' ) );
		add_action( 'wp_head', array( __CLASS__, 'maybe_output_clean_scan_consent' ), PHP_INT_MAX );
		add_action( 'wp_footer', array( __CLASS__, 'maybe_output_clean_scan_consent' ), 1 );
	}

	/**
	 * Site Kit undantar inloggade användare från Analytics som standard, men
	 * laddar ändå gtag.js och sätter window["ga-disable-G-XXXX"] = true. Då
	 * finns skriptet i skannern utan att några cookies sätts. Under ren
	 * skanning slås undantaget av så att skannern ser samma cookies som en
	 * besökare får.
	 *
	 * @param bool $disabled Site Kits beslut.
	 * @return bool
	 */
	public static function clean_scan_enable_sitekit_tracking( $disabled ) {
		return self::is_clean_scan() ? false : $disabled;
	}

	/**
	 * Site Kit skriver gtag('consent','default', denied) i head när Consent
	 * Mode är på. Under ren skanning byts alla "denied" till "granted" så
	 * att Analytics sätter sina cookies.
	 *
	 * @param array $defaults Site Kits consent-default.
	 * @return array
	 */
	public static function clean_scan_grant_sitekit_consent( $defaults ) {
		if ( ! self::is_clean_scan() || ! is_array( $defaults ) ) {
			return $defaults;
		}
		foreach ( $defaults as $key => $value ) {
			if ( 'denied' === $value ) {
				$defaults[ $key ] = 'granted';
			}
		}
		return $defaults;
	}

	/**
	 * Skriver ut gtag('consent','update', allt granted) under ren skanning.
	 *
	 * Täcker consent mode-defaults som andra plugins eller GTM-containrar
	 * sätter. Skrivs ut sist i head (efter deras default) och först i footer
	 * (för containrar som laddat under tiden).
	 */
	public static function maybe_output_clean_scan_consent() {
		if ( ! self::is_clean_scan() ) {
			return;
		}
		?>
<script data-cfasync="false">
window.dataLayer=window.dataLayer||[];window.gtag=window.gtag||function(){dataLayer.push(arguments);};
gtag('consent','update',{ad_storage:'granted',ad_user_data:'granted',ad_personalization:'granted',analytics_storage:'granted',functionality_storage:'granted',personalization_storage:'granted',security_storage:'granted'});
</script>
		<?php
	}

	/**
	 * Avgör om sidan laddas av cookie-skannern i "ren skanning"-läge.
	 *
	 * I det läget ska CoCookie inte påverka sidan alls: ingen blockering,
	 * ingen Consent Mode-default och ingen banner. Annars kan bannerns JS
	 * skicka ett tidigare "avvisa"-val vidare till gtag inne i skannerns
	 * iframe, och då sätter Google Analytics aldrig sina cookies.
	 *
	 * @return bool
	 */
	private static function is_clean_scan() {
		return isset( $_GET['ccm_clean_scan'] ) && wp_verify_nonce( $_GET['ccm_clean_scan'], 'ccm_clean_scan' );
	}

	/**
	 * Start output buffering for script/iframe blocking.
	 * Runs on template_redirect which is frontend-only.
	 */
	public static function start_output_buffering() {
		if ( self::is_clean_scan() ) {
			return;
		}

		require_once COCOOKIE_PLUGIN_DIR . 'includes/class-scanner.php';
		ob_start( array( 'CCM_Scanner', 'process_buffer' ) );
	}

	/**
	 * Output GCM default only on frontend (not clean scan).
	 */
	public static function maybe_output_gcm_default() {
		if ( self::is_clean_scan() ) {
			return;
		}
		self::output_gcm_default();
	}

	/**
	 * Render the cookie list shortcode.
	 *
	 * @param array $atts Shortcode-attribut: consent och heading.
	 * @return string HTML table of cookies grouped by category.
	 */
	public static function render_cookie_list( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'consent' => 'yes',
				'heading' => 'h3',
			),
			$atts,
			'cocookie_cookie_list'
		);

		// Rubriknivån är valbar så att listan kan läggas in under en befintlig
		// rubrik utan att hoppa över nivåer (policygeneratorn använder h4).
		$heading = strtolower( $atts['heading'] );
		if ( ! in_array( $heading, array( 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ) {
			$heading = 'h3';
		}

		$show_consent = ! in_array(
			strtolower( (string) $atts['consent'] ),
			array( 'no', 'nej', 'false', '0', '' ),
			true
		);

		$categories = CoCookie_Categories::get_all();
		if ( empty( $categories ) ) {
			return '<p>' . esc_html__( 'Inga cookies registrerade.', 'cocookie' ) . '</p>';
		}

		$cookies_grouped = CoCookie_Categories::get_cookies_grouped();
		$html = '<div class="cocookie-declaration">';

		if ( $show_consent ) {
			$html .= self::render_consent_status( $categories );
		}

		foreach ( $categories as $cat ) {
			$cookies = isset( $cookies_grouped[ $cat['id'] ] ) ? $cookies_grouped[ $cat['id'] ] : array();
			if ( empty( $cookies ) ) {
				continue;
			}

			$count = count( $cookies );

			$html .= '<' . $heading . ' class="cocookie-declaration__title">' . esc_html( $cat['title'] );
			if ( $cat['is_required'] ) {
				$html .= ' <small>(' . esc_html__( 'krävs alltid', 'cocookie' ) . ')</small>';
			}
			$html .= ' <small class="cocookie-declaration__count">' . esc_html(
				sprintf(
					/* translators: %d: antal cookies i kategorin. */
					_n( '%d cookie', '%d cookies', $count, 'cocookie' ),
					$count
				)
			) . '</small>';
			$html .= '</' . $heading . '>';
			$html .= '<p>' . esc_html( $cat['description'] ) . '</p>';
			$html .= '<table class="cocookie-declaration-table">';
			$html .= '<thead><tr>';
			$html .= '<th>' . esc_html__( 'Cookie', 'cocookie' ) . '</th>';
			$html .= '<th>' . esc_html__( 'Leverantör', 'cocookie' ) . '</th>';
			$html .= '<th>' . esc_html__( 'Syfte', 'cocookie' ) . '</th>';
			$html .= '<th>' . esc_html__( 'Livslängd', 'cocookie' ) . '</th>';
			$html .= '</tr></thead><tbody>';

			foreach ( $cookies as $c ) {
				$html .= '<tr>';
				$html .= '<td><code>' . esc_html( $c['name'] ) . '</code></td>';
				$html .= '<td>' . esc_html( $c['provider'] ) . '</td>';
				$html .= '<td>' . esc_html( $c['purpose'] ) . '</td>';
				$html .= '<td>' . esc_html( $c['expiry'] ?: '—' ) . '</td>';
				$html .= '</tr>';
			}

			$html .= '</tbody></table>';
		}

		$last_scan = get_option( 'cocookie_last_scan', get_option( 'ccm_last_scan', '' ) );
		if ( ! empty( $last_scan ) ) {
			$timestamp = strtotime( $last_scan );
			if ( $timestamp ) {
				$html .= '<p class="cocookie-declaration__scan">' . esc_html(
					sprintf(
						/* translators: %s: datum för senaste cookie-skanningen. */
						__( 'Listan uppdaterades senast vid skanning av webbplatsen %s.', 'cocookie' ),
						date_i18n( get_option( 'date_format' ), $timestamp )
					)
				) . '</p>';
			}
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Render the visitor's own consent status.
	 *
	 * Samtycket ligger bara i besökarens cookie, och sidan kan vara cachad,
	 * så servern kan inte veta vad just den besökaren har valt. Här skrivs
	 * därför bara stommen ut — bannerns JS fyller i värdena. Texterna skickas
	 * med som data-attribut så att de går att översätta i PHP.
	 *
	 * @param array $categories Alla kategorier.
	 * @return string HTML.
	 */
	private static function render_consent_status( $categories ) {
		$settings     = get_option( 'cocookie_settings', get_option( 'ccm_settings', array() ) );
		$accent       = ! empty( $settings['primary_color'] ) ? $settings['primary_color'] : '#29A166';
		$accent_text  = ! empty( $settings['primary_text_color'] ) ? $settings['primary_text_color'] : '#ffffff';
		$button_label = ! empty( $settings['settings_text'] ) ? $settings['settings_text'] : __( 'Cookie-inställningar', 'cocookie' );

		$html = sprintf(
			'<div class="cocookie-status" data-cocookie-status hidden
				data-allowed="%1$s" data-denied="%2$s"
				data-has-consent="%3$s" data-no-consent="%4$s"
				data-date="%5$s" data-id="%6$s">',
			esc_attr__( 'Tillåten', 'cocookie' ),
			esc_attr__( 'Inte tillåten', 'cocookie' ),
			esc_attr__( 'Ditt nuvarande val:', 'cocookie' ),
			esc_attr__( 'Du har inte gjort något val ännu. Cookie-bannern visas nästa gång du laddar om sidan.', 'cocookie' ),
			/* translators: %s: datum och tid då samtycket sparades. */
			esc_attr__( 'Sparat %s', 'cocookie' ),
			/* translators: %s: samtyckes-ID. */
			esc_attr__( 'Samtyckes-ID: %s', 'cocookie' )
		);

		$html .= '<p class="cocookie-status__intro" data-cocookie-status-intro></p>';

		$html .= '<ul class="cocookie-status__list" data-cocookie-status-list hidden>';
		foreach ( $categories as $cat ) {
			$html .= sprintf(
				'<li class="cocookie-status__item" data-cocookie-status-category="%1$s">
					<span class="cocookie-status__name">%2$s</span>
					<span class="cocookie-status__value" data-cocookie-status-value></span>
				</li>',
				esc_attr( $cat['slug'] ),
				esc_html( $cat['title'] )
			);
		}
		$html .= '</ul>';

		$html .= '<p class="cocookie-status__meta" data-cocookie-status-meta hidden></p>';

		$html .= sprintf(
			'<p class="cocookie-status__actions">
				<button type="button" class="cocookie-settings-link cocookie-open-settings" style="--cocookie-accent: %1$s; --cocookie-accent-text: %2$s;">%3$s</button>
				<button type="button" class="cocookie-status__withdraw" data-cocookie-withdraw hidden>%4$s</button>
			</p>',
			esc_attr( $accent ),
			esc_attr( $accent_text ),
			esc_html( $button_label ),
			esc_html__( 'Dra tillbaka samtycke', 'cocookie' )
		);

		$html .= '<noscript><p class="cocookie-status__intro">'
			. esc_html__( 'Ditt val av cookies visas bara om JavaScript är aktiverat.', 'cocookie' )
			. '</p></noscript>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render the settings button shortcode.
	 *
	 * Ger sajter ett alternativ till den flytande knappen: en vanlig knapp
	 * som öppnar samtyckespanelen, till exempel på cookie-policy-sidan.
	 *
	 * @param array $atts Shortcode-attribut: text och class.
	 * @return string Knapp-HTML.
	 */
	public static function render_settings_button( $atts ) {
		$settings = get_option( 'cocookie_settings', get_option( 'ccm_settings', array() ) );

		$default_text = ! empty( $settings['settings_text'] )
			? $settings['settings_text']
			: __( 'Cookie-inställningar', 'cocookie' );

		$atts = shortcode_atts(
			array(
				'text'  => $default_text,
				'class' => '',
			),
			$atts,
			'cocookie_settings'
		);

		// Bannerns färgvariabler sitter på #cocookie-banner och ärvs inte hit,
		// därför sätts de om inline så att knappen matchar bannerns primärfärg.
		$accent      = ! empty( $settings['primary_color'] ) ? $settings['primary_color'] : '#29A166';
		$accent_text = ! empty( $settings['primary_text_color'] ) ? $settings['primary_text_color'] : '#ffffff';

		$classes = trim( 'cocookie-settings-link cocookie-open-settings ' . $atts['class'] );

		return sprintf(
			'<button type="button" class="%1$s" style="--cocookie-accent: %2$s; --cocookie-accent-text: %3$s;">%4$s</button>',
			esc_attr( $classes ),
			esc_attr( $accent ),
			esc_attr( $accent_text ),
			esc_html( $atts['text'] )
		);
	}

	/**
	 * Output Google Consent Mode v2 default + cookie interceptor in <head>.
	 *
	 * This is identical to the original implementation — it must run
	 * before any GTM/gtag.js scripts to prevent flicker.
	 */
	public static function output_gcm_default() {
		$consent_json = isset( $_COOKIE['cc_consent'] ) ? stripslashes( $_COOKIE['cc_consent'] ) : '';
		$consent      = $consent_json ? json_decode( $consent_json, true ) : null;

		$has_marketing = $consent && ! empty( $consent['marketing'] );
		$has_analytics = $consent && ! empty( $consent['analytics'] );

		$cookie_category_map = self::get_cookie_category_map();
		$cookie_map_json     = wp_json_encode( $cookie_category_map );

		$blocked_cookies = get_option( 'cocookie_blocked_cookies', get_option( 'ccm_blocked_cookies', array() ) );
		$blocked_json    = wp_json_encode( array_values( $blocked_cookies ) );

		$consent_state_json = $consent ? wp_json_encode( $consent ) : 'null';
		?>
<script data-cfasync="false">
window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}
gtag('consent','default',{
  ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',
  analytics_storage:'denied',functionality_storage:'granted',
  personalization_storage:'denied',security_storage:'granted',
  wait_for_update:500
});
gtag('set','ads_data_redaction',true);
gtag('set','url_passthrough',true);
<?php if ( $consent ) : ?>
gtag('consent','update',{
  ad_storage:<?php echo $has_marketing ? "'granted'" : "'denied'"; ?>,
  ad_user_data:<?php echo $has_marketing ? "'granted'" : "'denied'"; ?>,
  ad_personalization:<?php echo $has_marketing ? "'granted'" : "'denied'"; ?>,
  analytics_storage:<?php echo $has_analytics ? "'granted'" : "'denied'"; ?>,
  functionality_storage:'granted',
  personalization_storage:<?php echo $has_analytics ? "'granted'" : "'denied'"; ?>,
  security_storage:'granted'
});
<?php if ( $has_marketing ) : ?>
gtag('set','ads_data_redaction',false);
<?php endif; ?>
<?php endif; ?>
(function(){
  var cookieMap=<?php echo $cookie_map_json; ?>;
  var manualBlocked=<?php echo $blocked_json; ?>;
  var consent=<?php echo $consent_state_json; ?>;
  window.__ccmUpdateConsent=function(c){consent=c;};
  function isBlocked(name){
    for(var i=0;i<manualBlocked.length;i++){if(name===manualBlocked[i])return true;}
    var cat=cookieMap[name];if(!cat)return false;if(!consent)return true;return !consent[cat];
  }
  function getBlockedNames(){
    var names=manualBlocked.slice();
    for(var n in cookieMap){if(cookieMap.hasOwnProperty(n)&&isBlocked(n)&&names.indexOf(n)===-1){names.push(n);}}
    return names;
  }
  var desc=Object.getOwnPropertyDescriptor(Document.prototype,'cookie')||Object.getOwnPropertyDescriptor(HTMLDocument.prototype,'cookie');
  if(desc&&desc.set){
    var origSet=desc.set;var origGet=desc.get;
    Object.defineProperty(document,'cookie',{
      get:function(){return origGet.call(document);},
      set:function(v){var name=(v.split('=')[0]||'').trim();if(isBlocked(name))return;origSet.call(document,v);},
      configurable:true
    });
    function deleteCookies(){
      var blocked=getBlockedNames();if(!blocked.length)return;
      var paths=['/',location.pathname];var h=location.hostname;var parts=h.split('.');
      var domains=['',h];if(parts.length>1){domains.push('.'+h);domains.push('.'+parts.slice(-2).join('.'));}
      for(var i=0;i<blocked.length;i++){for(var p=0;p<paths.length;p++){for(var d=0;d<domains.length;d++){
        var c=blocked[i]+'=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path='+paths[p];
        if(domains[d])c+=';domain='+domains[d];origSet.call(document,c);
      }}}
    }
    deleteCookies();
    document.addEventListener('DOMContentLoaded',deleteCookies);
    var n=0;var iv=setInterval(function(){deleteCookies();if(++n>=5)clearInterval(iv);},2000);
  }
})();
</script>
		<?php
	}

	/**
	 * Enqueue frontend assets.
	 */
	public static function enqueue_assets() {
		if ( self::is_clean_scan() ) {
			return;
		}

		wp_enqueue_style( 'cocookie-banner', COCOOKIE_PLUGIN_URL . 'public/css/cocookie-banner.css', array(), COCOOKIE_VERSION );
		wp_enqueue_script( 'cocookie-banner', COCOOKIE_PLUGIN_URL . 'public/js/cocookie-banner.js', array(), COCOOKIE_VERSION, true );

		$config = CoCookie_REST_Config::build_config();
		$config['restUrl'] = esc_url_raw( rest_url( 'cocookie/v1' ) );
		$config['nonce']   = wp_create_nonce( 'wp_rest' );

		wp_localize_script( 'cocookie-banner', 'cocookieConfig', $config );
	}

	/**
	 * Render the banner PHP template. Called from wp_body_open or wp_footer.
	 * Only renders once.
	 */
	private static $banner_rendered = false;

	public static function render_banner_template() {
		if ( self::$banner_rendered || self::is_clean_scan() ) {
			return;
		}
		self::$banner_rendered = true;

		$config = CoCookie_REST_Config::build_config();

		echo "\n<!-- CoCookie Banner Start -->\n";
		include COCOOKIE_PLUGIN_DIR . 'public/templates/banner.php';
		echo "\n<!-- CoCookie Banner End -->\n";
	}

	/**
	 * Get a map of cookie_name => category_slug for non-required cookies.
	 *
	 * @return array
	 */
	private static function get_cookie_category_map() {
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT c.name, cat.slug
			 FROM {$wpdb->prefix}cc_cookies c
			 JOIN {$wpdb->prefix}cc_categories cat ON c.category_id = cat.id
			 WHERE cat.is_required = 0",
			ARRAY_A
		);

		$map = array();
		foreach ( $rows as $row ) {
			$map[ $row['name'] ] = $row['slug'];
		}
		return $map;
	}
}

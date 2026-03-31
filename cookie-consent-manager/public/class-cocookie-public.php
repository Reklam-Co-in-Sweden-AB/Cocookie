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

		// These hooks only fire on the frontend, so no is_admin() check needed
		add_action( 'wp_head', array( __CLASS__, 'maybe_output_gcm_default' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

		// Render banner HTML early in body (before scripts) so JS can find it
		add_action( 'wp_body_open', array( __CLASS__, 'render_banner_template' ), 1 );
		// Fallback for themes that don't support wp_body_open
		add_action( 'wp_footer', array( __CLASS__, 'render_banner_template' ), 5 );

		// Output buffering for script/iframe blocking (only on frontend)
		add_action( 'template_redirect', array( __CLASS__, 'start_output_buffering' ) );
	}

	/**
	 * Start output buffering for script/iframe blocking.
	 * Runs on template_redirect which is frontend-only.
	 */
	public static function start_output_buffering() {
		$is_clean_scan = isset( $_GET['ccm_clean_scan'] ) && wp_verify_nonce( $_GET['ccm_clean_scan'], 'ccm_clean_scan' );
		if ( $is_clean_scan ) {
			return;
		}

		require_once COCOOKIE_PLUGIN_DIR . 'includes/class-scanner.php';
		ob_start( array( 'CCM_Scanner', 'process_buffer' ) );
	}

	/**
	 * Output GCM default only on frontend (not clean scan).
	 */
	public static function maybe_output_gcm_default() {
		$is_clean_scan = isset( $_GET['ccm_clean_scan'] ) && wp_verify_nonce( $_GET['ccm_clean_scan'], 'ccm_clean_scan' );
		if ( $is_clean_scan ) {
			return;
		}
		self::output_gcm_default();
	}

	/**
	 * Render the cookie list shortcode.
	 *
	 * @return string HTML table of cookies grouped by category.
	 */
	public static function render_cookie_list() {
		$categories = CoCookie_Categories::get_all();
		if ( empty( $categories ) ) {
			return '<p>' . esc_html__( 'Inga cookies registrerade.', 'cocookie' ) . '</p>';
		}

		$cookies_grouped = CoCookie_Categories::get_cookies_grouped();
		$html = '<div class="cocookie-declaration">';

		foreach ( $categories as $cat ) {
			$cookies = isset( $cookies_grouped[ $cat['id'] ] ) ? $cookies_grouped[ $cat['id'] ] : array();
			if ( empty( $cookies ) ) {
				continue;
			}

			$html .= '<h3>' . esc_html( $cat['title'] );
			if ( $cat['is_required'] ) {
				$html .= ' <small>(' . esc_html__( 'krävs alltid', 'cocookie' ) . ')</small>';
			}
			$html .= '</h3>';
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

		$html .= '</div>';
		return $html;
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
		if ( self::$banner_rendered ) {
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

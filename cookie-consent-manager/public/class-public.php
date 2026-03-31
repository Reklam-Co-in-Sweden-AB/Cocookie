<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCM_Public {

    public static function init() {
        add_shortcode( 'ccm_cookie_list', array( __CLASS__, 'render_cookie_list' ) );
        if ( is_admin() ) {
            return;
        }

        $is_clean_scan = isset( $_GET['ccm_clean_scan'] ) && wp_verify_nonce( $_GET['ccm_clean_scan'], 'ccm_clean_scan' );

        if ( ! $is_clean_scan ) {
            add_action( 'wp_head', array( __CLASS__, 'output_gcm_default' ), 1 );
        }

        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        CCM_Scanner::init();
    }

    public static function render_cookie_list( $atts ) {
        $categories = CCM_Categories::get_all();
        if ( empty( $categories ) ) {
            return '<p>Inga cookies registrerade.</p>';
        }

        $cookies_grouped = CCM_Categories::get_cookies_grouped();
        $html = '<div class="ccm-cookie-declaration">';

        foreach ( $categories as $cat ) {
            $cookies = isset( $cookies_grouped[ $cat['id'] ] ) ? $cookies_grouped[ $cat['id'] ] : array();
            if ( empty( $cookies ) ) continue;

            $html .= '<h3>' . esc_html( $cat['title'] );
            if ( $cat['is_required'] ) {
                $html .= ' <small>(krävs alltid)</small>';
            }
            $html .= '</h3>';
            $html .= '<p>' . esc_html( $cat['description'] ) . '</p>';
            $html .= '<table class="ccm-declaration-table">';
            $html .= '<thead><tr><th>Cookie</th><th>Leverantör</th><th>Syfte</th><th>Livslängd</th></tr></thead>';
            $html .= '<tbody>';

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

    public static function output_gcm_default() {
        // Read consent cookie server-side for returning visitors
        $consent_json = isset( $_COOKIE['cc_consent'] ) ? stripslashes( $_COOKIE['cc_consent'] ) : '';
        $consent      = $consent_json ? json_decode( $consent_json, true ) : null;

        $has_marketing = $consent && ! empty( $consent['marketing'] );
        $has_analytics = $consent && ! empty( $consent['analytics'] );

        // Build map of cookie_name => category_slug for all non-required cookies
        $cookie_category_map = self::get_cookie_category_map();
        $cookie_map_json     = wp_json_encode( $cookie_category_map );

        // Manually blocked cookies (from audit)
        $blocked_cookies = get_option( 'ccm_blocked_cookies', array() );
        $blocked_json    = wp_json_encode( array_values( $blocked_cookies ) );

        // Server-side consent as JSON for the interceptor
        $consent_state_json = $consent ? wp_json_encode( $consent ) : 'null';

        ?>
<script data-cfasync="false">
window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}
gtag('consent','default',{
  ad_storage:'denied',
  ad_user_data:'denied',
  ad_personalization:'denied',
  analytics_storage:'denied',
  functionality_storage:'granted',
  personalization_storage:'denied',
  security_storage:'granted',
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
    for(var i=0;i<manualBlocked.length;i++){
      if(name===manualBlocked[i])return true;
    }
    var cat=cookieMap[name];
    if(!cat)return false;
    if(!consent)return true;
    return !consent[cat];
  }
  function getBlockedNames(){
    var names=manualBlocked.slice();
    for(var n in cookieMap){
      if(cookieMap.hasOwnProperty(n)&&isBlocked(n)&&names.indexOf(n)===-1){
        names.push(n);
      }
    }
    return names;
  }
  var desc=Object.getOwnPropertyDescriptor(Document.prototype,'cookie')||
           Object.getOwnPropertyDescriptor(HTMLDocument.prototype,'cookie');
  if(desc&&desc.set){
    var origSet=desc.set;
    var origGet=desc.get;
    Object.defineProperty(document,'cookie',{
      get:function(){return origGet.call(document);},
      set:function(v){
        var name=(v.split('=')[0]||'').trim();
        if(isBlocked(name))return;
        origSet.call(document,v);
      },
      configurable:true
    });
    function deleteCookies(){
      var blocked=getBlockedNames();
      if(!blocked.length)return;
      var paths=['/',location.pathname];
      var h=location.hostname;
      var parts=h.split('.');
      var domains=['',h];
      if(parts.length>1){domains.push('.'+h);domains.push('.'+parts.slice(-2).join('.'));}
      for(var i=0;i<blocked.length;i++){
        for(var p=0;p<paths.length;p++){
          for(var d=0;d<domains.length;d++){
            var c=blocked[i]+'=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path='+paths[p];
            if(domains[d])c+=';domain='+domains[d];
            origSet.call(document,c);
          }
        }
      }
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
     * Returns a map of cookie_name => category_slug for all non-required cookies.
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

    public static function enqueue_assets() {
        wp_enqueue_style( 'ccm-banner', CCM_PLUGIN_URL . 'public/cookie-banner.css', array(), CCM_VERSION );
        wp_enqueue_script( 'ccm-banner', CCM_PLUGIN_URL . 'public/cookie-banner.js', array(), CCM_VERSION, true );

        $categories      = CCM_Categories::get_all();
        $cookies_grouped = CCM_Categories::get_cookies_grouped();
        $settings        = get_option( 'ccm_settings', array() );

        $blocked_cookies = get_option( 'ccm_blocked_cookies', array() );

        $config = array(
            'restUrl'        => esc_url_raw( rest_url( 'cc/v1' ) ),
            'nonce'          => wp_create_nonce( 'wp_rest' ),
            'categories'     => array(),
            'blockedCookies' => array_values( $blocked_cookies ),
            'settings'       => array(
                'banner_title'       => $settings['banner_title'] ?? 'Vi använder cookies',
                'banner_text'        => $settings['banner_text'] ?? 'Denna webbplats använder cookies för att förbättra din upplevelse.',
                'accept_all_text'    => $settings['accept_all_text'] ?? 'Acceptera alla',
                'reject_all_text'    => $settings['reject_all_text'] ?? 'Avvisa alla',
                'save_text'          => $settings['save_text'] ?? 'Spara inställningar',
                'settings_text'      => $settings['settings_text'] ?? 'Inställningar',
                'position'           => $settings['position'] ?? 'bottom',
                'primary_color'      => $settings['primary_color'] ?? '#2271b1',
                'primary_text_color' => $settings['primary_text_color'] ?? '#ffffff',
                'banner_bg_color'    => $settings['banner_bg_color'] ?? '#ffffff',
                'banner_text_color'  => $settings['banner_text_color'] ?? '#333333',
                'reject_bg_color'    => $settings['reject_bg_color'] ?? '#f0f0f0',
                'reject_text_color'  => $settings['reject_text_color'] ?? '#333333',
                'logo_url'           => $settings['logo_url'] ?? '',
                'cookie_lifetime'    => intval( $settings['cookie_lifetime'] ?? 365 ),
                'cookie_icon'        => $settings['cookie_icon'] ?? '',
                'privacy_policy_url' => function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '',
                // Översättbara strängar som tidigare var hårdkodade i JS
                'manage_title'       => $settings['manage_title'] ?? 'Manage cookie settings',
                'manage_text'        => $settings['manage_text'] ?? 'Here you can change or withdraw your consent.',
                'consent_date_label' => $settings['consent_date_label'] ?? 'Consent date:',
                'consent_id_label'   => $settings['consent_id_label'] ?? 'Your consent ID:',
                'required_label'     => $settings['required_label'] ?? '(always required)',
                'withdraw_text'      => $settings['withdraw_text'] ?? 'Withdraw consent',
                'hide_details_text'  => $settings['hide_details_text'] ?? 'Hide details',
                'show_details_text'  => $settings['show_details_text'] ?? 'Show details',
                'policy_link_text'   => $settings['policy_link_text'] ?? 'Read our privacy policy.',
                'dnt_notice'         => $settings['dnt_notice'] ?? 'Your browser has Do Not Track enabled. Analytics and marketing cookies are automatically disabled.',
            ),
        );

        foreach ( $categories as $cat ) {
            $cookies = isset( $cookies_grouped[ $cat['id'] ] ) ? $cookies_grouped[ $cat['id'] ] : array();
            $cookie_list = array();
            foreach ( $cookies as $c ) {
                $cookie_list[] = array(
                    'name'     => $c['name'],
                    'provider' => $c['provider'],
                    'purpose'  => $c['purpose'],
                    'expiry'   => $c['expiry'],
                );
            }
            $config['categories'][] = array(
                'slug'        => $cat['slug'],
                'title'       => $cat['title'],
                'description' => $cat['description'],
                'is_required' => (bool) $cat['is_required'],
                'cookies'     => $cookie_list,
            );
        }

        // Översätt cookie-bannerns texter via Coscribe Translator om det finns
        $config = self::maybe_translate_config( $config );

        wp_localize_script( 'ccm-banner', 'ccmConfig', $config );
    }

    /**
     * Kör textfält genom Coscribe Translator om pluginet är aktivt
     * och besökaren är på en översatt sida (t.ex. /no/ eller /nl/)
     */
    private static function maybe_translate_config( $config ) {
        if ( ! function_exists( 'coscribe_translator' ) ) {
            return $config;
        }

        $plugin = coscribe_translator();
        if ( ! $plugin || ! $plugin->translator ) {
            return $config;
        }

        $current_lang = $plugin->get_current_language();
        $default_lang = $plugin->get_default_language();

        // Bara på översatta sidor
        if ( $current_lang === $default_lang ) {
            return $config;
        }

        $translator  = $plugin->translator;
        $source_lang = $default_lang;

        // Översätt bannerns textfält
        $text_fields = array(
            'banner_title', 'banner_text', 'accept_all_text',
            'reject_all_text', 'save_text', 'settings_text',
            'manage_title', 'manage_text', 'consent_date_label',
            'consent_id_label', 'required_label', 'withdraw_text',
            'hide_details_text', 'show_details_text', 'policy_link_text',
            'dnt_notice',
        );
        foreach ( $text_fields as $field ) {
            if ( ! empty( $config['settings'][ $field ] ) ) {
                $translated = $translator->translate(
                    $config['settings'][ $field ],
                    $source_lang,
                    $current_lang
                );
                if ( $translated ) {
                    $config['settings'][ $field ] = $translated;
                }
            }
        }

        // Översätt kategori-titlar och beskrivningar
        if ( ! empty( $config['categories'] ) ) {
            foreach ( $config['categories'] as &$cat ) {
                if ( ! empty( $cat['title'] ) ) {
                    $translated = $translator->translate( $cat['title'], $source_lang, $current_lang );
                    if ( $translated ) {
                        $cat['title'] = $translated;
                    }
                }
                if ( ! empty( $cat['description'] ) ) {
                    $translated = $translator->translate( $cat['description'], $source_lang, $current_lang );
                    if ( $translated ) {
                        $cat['description'] = $translated;
                    }
                }
            }
            unset( $cat );
        }

        return $config;
    }
}

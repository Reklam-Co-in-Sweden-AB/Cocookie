<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCM_Scanner {

    /**
     * Known third-party iframe providers mapped to consent categories.
     */
    public static function get_iframe_providers() {
        return array(
            'player.vimeo.com'   => array( 'category' => 'analytics', 'provider' => 'Vimeo',       'icon' => '&#9654;' ),
            'vimeo.com'          => array( 'category' => 'analytics', 'provider' => 'Vimeo',       'icon' => '&#9654;' ),
            'youtube.com'        => array( 'category' => 'marketing', 'provider' => 'YouTube',     'icon' => '&#9654;' ),
            'youtube-nocookie.com' => array( 'category' => 'marketing', 'provider' => 'YouTube',   'icon' => '&#9654;' ),
            'youtu.be'           => array( 'category' => 'marketing', 'provider' => 'YouTube',     'icon' => '&#9654;' ),
            'maps.google.com'    => array( 'category' => 'analytics', 'provider' => 'Google Maps', 'icon' => '&#128205;' ),
            'google.com/maps'    => array( 'category' => 'analytics', 'provider' => 'Google Maps', 'icon' => '&#128205;' ),
            'open.spotify.com'   => array( 'category' => 'analytics', 'provider' => 'Spotify',     'icon' => '&#9654;' ),
            'facebook.com/plugins' => array( 'category' => 'marketing', 'provider' => 'Facebook',  'icon' => '&#128172;' ),
            'platform.twitter.com' => array( 'category' => 'marketing', 'provider' => 'Twitter/X', 'icon' => '&#128172;' ),
            'twitter.com'        => array( 'category' => 'marketing', 'provider' => 'Twitter/X',   'icon' => '&#128172;' ),
            'linkedin.com'       => array( 'category' => 'marketing', 'provider' => 'LinkedIn',    'icon' => '&#128172;' ),
            'intercom.io'        => array( 'category' => 'analytics', 'provider' => 'Intercom',    'icon' => '&#128172;' ),
            'intercomcdn.com'    => array( 'category' => 'analytics', 'provider' => 'Intercom',    'icon' => '&#128172;' ),
            'hubspot.com'        => array( 'category' => 'analytics', 'provider' => 'HubSpot',     'icon' => '&#128172;' ),
            'hs-scripts.com'     => array( 'category' => 'analytics', 'provider' => 'HubSpot',     'icon' => '&#128172;' ),
            'hsforms.com'        => array( 'category' => 'analytics', 'provider' => 'HubSpot',     'icon' => '&#128172;' ),
        );
    }

    public static function init() {
        if ( is_admin() ) {
            return;
        }

        // Clean scan mode: skip all script/iframe blocking so the scanner sees real cookies.
        if ( isset( $_GET['ccm_clean_scan'] ) && wp_verify_nonce( $_GET['ccm_clean_scan'], 'ccm_clean_scan' ) ) {
            return;
        }

        add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ) );
    }

    public static function start_buffer() {
        ob_start( array( __CLASS__, 'process_buffer' ) );
    }

    public static function process_buffer( $html ) {
        if ( empty( $html ) ) {
            return $html;
        }

        // Convert <script data-cc-category="..." src="..."> to type="text/plain"
        // This ensures scripts are blocked until consent is given.
        // If consent already exists for the category, leave the script unchanged.
        $consent = self::get_server_consent();

        $html = preg_replace_callback(
            '/<script([^>]*?)data-cc-category=["\']([^"\']+)["\']([^>]*?)>/i',
            function ( $matches ) use ( $consent ) {
                $before   = $matches[1];
                $category = $matches[2];
                $after    = $matches[3];
                $full     = $before . $after;

                // Already consented — don't block this script
                if ( $consent && ! empty( $consent[ $category ] ) ) {
                    return $matches[0];
                }

                // Don't modify if already type="text/plain"
                if ( stripos( $full, 'type="text/plain"' ) !== false || stripos( $full, "type='text/plain'" ) !== false ) {
                    return $matches[0];
                }

                // Remove any existing type attribute
                $full = preg_replace( '/\s*type\s*=\s*["\'][^"\']*["\']/i', '', $full );

                return '<script type="text/plain" data-cc-category="' . esc_attr( $category ) . '"' . $full . '>';
            },
            $html
        );

        // Auto-block known tracking scripts (GTM, GA, Meta Pixel, etc.)
        // These are blocked even without data-cc-category attribute.
        $auto_block_patterns = array(
            // Analytics
            'googletagmanager.com/gtag/js'   => 'analytics',
            'google-analytics.com/analytics' => 'analytics',
            'googletagmanager.com/gtm.js'    => 'analytics',
            'static.hotjar.com'              => 'analytics',
            'clarity.ms'                     => 'analytics',
            'js.hs-scripts.com'              => 'analytics',
            'js.hs-analytics.net'            => 'analytics',
            'cdn.segment.com'                => 'analytics',
            'cdn.amplitude.com'              => 'analytics',
            'matomo'                         => 'analytics',
            // Marketing
            'connect.facebook.net'           => 'marketing',
            'snap.licdn.com'                 => 'marketing',
            'ads.linkedin.com'               => 'marketing',
            'analytics.tiktok.com'           => 'marketing',
            'static.ads-twitter.com'         => 'marketing',
            'bat.bing.com'                   => 'marketing',
            'ct.pinterest.com'               => 'marketing',
            'sc-static.net'                  => 'marketing',
            'alb.reddit.com'                 => 'marketing',
        );

        $html = preg_replace_callback(
            '/<script([^>]*?)src=["\']([^"\']+)["\']([^>]*?)>/i',
            function ( $matches ) use ( $consent, $auto_block_patterns ) {
                $before = $matches[1];
                $src    = $matches[2];
                $after  = $matches[3];
                $full   = $before . $after;

                // Skip if already has data-cc-category (handled by the earlier regex)
                if ( stripos( $full, 'data-cc-category' ) !== false ) {
                    return $matches[0];
                }

                // Skip if already type="text/plain"
                if ( stripos( $full, 'type="text/plain"' ) !== false ) {
                    return $matches[0];
                }

                // Check if src matches a known tracking script
                $category = '';
                foreach ( $auto_block_patterns as $pattern => $cat ) {
                    if ( stripos( $src, $pattern ) !== false ) {
                        $category = $cat;
                        break;
                    }
                }

                if ( ! $category ) {
                    return $matches[0];
                }

                // Already consented — don't block
                if ( $consent && ! empty( $consent[ $category ] ) ) {
                    return $matches[0];
                }

                // Block by changing type and adding category attribute
                $full = preg_replace( '/\s*type\s*=\s*["\'][^"\']*["\']/i', '', $full );

                return '<script type="text/plain" data-cc-category="' . esc_attr( $category ) . '" src="' . esc_attr( $src ) . '"' . $full . '>';
            },
            $html
        );

        // Block third-party iframes until consent is given
        $html = preg_replace_callback(
            '/<iframe\b([^>]*?)>/is',
            array( __CLASS__, 'replace_iframe' ),
            $html
        );

        return $html;
    }

    /**
     * Read consent cookie server-side. Cached per request.
     *
     * @return array|null Consent data or null if no consent.
     */
    private static function get_server_consent() {
        static $consent = false;
        if ( false === $consent ) {
            $raw = isset( $_COOKIE['cc_consent'] ) ? stripslashes( $_COOKIE['cc_consent'] ) : '';
            $consent = $raw ? json_decode( $raw, true ) : null;
        }
        return $consent;
    }

    /**
     * Replace a matched iframe tag with a consent placeholder if it matches
     * a known third-party provider or has a manual data-cc-category attribute.
     * If the visitor has already consented to the category, leave the iframe unchanged.
     */
    private static function replace_iframe( $matches ) {
        $full_tag   = $matches[0];
        $attributes = $matches[1];

        // Extract src
        $src = '';
        if ( preg_match( '/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $attributes, $src_match ) ) {
            $src = $src_match[1];
        }

        // Check for manual data-cc-category
        $manual_category = '';
        if ( preg_match( '/\bdata-cc-category\s*=\s*["\']([^"\']+)["\']/i', $attributes, $cat_match ) ) {
            $manual_category = $cat_match[1];
        }

        // Determine provider info
        $category = '';
        $provider = '';
        $icon     = '&#9654;';

        if ( $manual_category ) {
            $category = $manual_category;
            $provider = '';
        } elseif ( $src ) {
            $providers = self::get_iframe_providers();
            foreach ( $providers as $domain => $info ) {
                if ( stripos( $src, $domain ) !== false ) {
                    $category = $info['category'];
                    $provider = $info['provider'];
                    $icon     = $info['icon'];
                    break;
                }
            }
        }

        // Not a known provider and no manual category — leave unchanged
        if ( ! $category ) {
            return $full_tag;
        }

        // If visitor already consented to this category, don't block the iframe
        $consent = self::get_server_consent();
        if ( $consent && ! empty( $consent[ $category ] ) ) {
            return $full_tag;
        }

        // Neutralisera iframen istället för att ersätta med div.
        // Behåller <iframe>-taggen (så tredjeparts-JS som PowerPack inte kraschar)
        // men tar bort src och lägger det i data-cc-src istället.
        $neutralized = $attributes;

        // Flytta src till data-cc-src
        if ( $src ) {
            $neutralized = preg_replace( '/\bsrc\s*=\s*["\'][^"\']+["\']/i', 'src="about:blank"', $neutralized );
        }

        // Lägg till data-attribut för consent-hantering
        $neutralized .= ' data-cc-src="' . esc_attr( $src ) . '"';
        $neutralized .= ' data-cc-category="' . esc_attr( $category ) . '"';

        // Bygg placeholder-overlay som visas ovanpå den tomma iframen
        $category_label = 'analytics' === $category
            ? __( 'analys', 'cocookie' )
            : __( 'marknadsförings', 'cocookie' );

        $provider_text = $provider
            ? '<p>' . sprintf( __( 'Det här innehållet tillhandahålls av %s.', 'cocookie' ), '<strong>' . esc_html( $provider ) . '</strong>' ) . '</p>'
            : '';

        $placeholder_html = '<div class="ccm-iframe-placeholder cocookie-iframe-placeholder" data-cc-category="' . esc_attr( $category ) . '" data-cc-src="' . esc_attr( $src ) . '" style="position:relative;">'
            . '<div class="ccm-iframe-placeholder-inner cocookie-iframe-placeholder__inner" style="position:absolute;top:0;left:0;right:0;bottom:0;display:flex;align-items:center;justify-content:center;background:rgba(240,241,243,0.95);z-index:1;">'
            . '<div style="text-align:center;padding:20px;">'
            . '<span class="ccm-iframe-icon cocookie-iframe-placeholder__icon">' . $icon . '</span>'
            . $provider_text
            . '<p>' . sprintf( __( 'Klicka för att godkänna %s-cookies och ladda innehållet.', 'cocookie' ), '<strong>' . esc_html( $category_label ) . '</strong>' ) . '</p>'
            . '<button class="ccm-iframe-accept cocookie-iframe-placeholder__btn" type="button">' . __( 'Godkänn och visa', 'cocookie' ) . '</button>'
            . '</div>'
            . '</div>'
            . '</div>';

        // Wrappa iframen i en container med overlay
        return '<div style="position:relative;">'
            . '<iframe' . $neutralized . '></iframe>'
            . $placeholder_html
            . '</div>';

    }
}

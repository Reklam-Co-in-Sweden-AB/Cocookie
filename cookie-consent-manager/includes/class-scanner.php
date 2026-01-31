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
        // This ensures scripts are blocked until consent is given
        $html = preg_replace_callback(
            '/<script([^>]*?)data-cc-category=["\']([^"\']+)["\']([^>]*?)>/i',
            function ( $matches ) {
                $before   = $matches[1];
                $category = $matches[2];
                $after    = $matches[3];
                $full     = $before . $after;

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

        // Block third-party iframes until consent is given
        $html = preg_replace_callback(
            '/<iframe\b([^>]*?)>/is',
            array( __CLASS__, 'replace_iframe' ),
            $html
        );

        return $html;
    }

    /**
     * Replace a matched iframe tag with a consent placeholder if it matches
     * a known third-party provider or has a manual data-cc-category attribute.
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

        // Extract width, height, style, class from original iframe
        $extra_attrs = '';
        $style_parts = array();

        if ( preg_match( '/\bwidth\s*=\s*["\']([^"\']+)["\']/i', $attributes, $w ) ) {
            $style_parts[] = 'width:' . ( is_numeric( $w[1] ) ? $w[1] . 'px' : $w[1] );
        }
        if ( preg_match( '/\bheight\s*=\s*["\']([^"\']+)["\']/i', $attributes, $h ) ) {
            $style_parts[] = 'height:' . ( is_numeric( $h[1] ) ? $h[1] . 'px' : $h[1] );
        }
        if ( preg_match( '/\bstyle\s*=\s*["\']([^"\']+)["\']/i', $attributes, $s ) ) {
            $style_parts[] = $s[1];
        }

        $class = 'ccm-iframe-placeholder';
        if ( preg_match( '/\bclass\s*=\s*["\']([^"\']+)["\']/i', $attributes, $c ) ) {
            $class .= ' ' . $c[1];
        }

        $style_attr = ! empty( $style_parts ) ? ' style="' . esc_attr( implode( ';', $style_parts ) ) . '"' : '';

        $category_label = $category === 'analytics' ? 'analys' : 'marknadsförings';

        $provider_text = $provider
            ? '<p>Det här innehållet tillhandahålls av <strong>' . esc_html( $provider ) . '</strong>.</p>'
            : '';

        $placeholder = '<div class="' . esc_attr( $class ) . '" data-cc-category="' . esc_attr( $category ) . '" data-cc-src="' . esc_attr( $src ) . '"' . $style_attr . '>'
            . '<div class="ccm-iframe-placeholder-inner">'
            . '<span class="ccm-iframe-icon">' . $icon . '</span>'
            . $provider_text
            . '<p>Klicka för att godkänna <strong>' . esc_html( $category_label ) . '</strong>-cookies och ladda innehållet.</p>'
            . '<button class="ccm-iframe-accept" type="button">Godkänn och visa</button>'
            . '</div>'
            . '</div>';

        return $placeholder;
    }
}

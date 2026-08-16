<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCM_Policy_Generator {

    public static function get_company_info() {
        $defaults = array(
            'company_name'    => '',
            'org_number'      => '',
            'address'         => '',
            'email'           => get_option( 'admin_email', '' ),
            'phone'           => '',
            'dpo_name'        => '',
            'dpo_email'       => '',
            'privacy_page_id' => 0,
            'cookie_page_id'  => 0,
        );
        return wp_parse_args( get_option( 'ccm_company_info', array() ), $defaults );
    }

    public static function save_company_info( $data ) {
        $clean = array(
            'company_name'    => sanitize_text_field( $data['company_name'] ?? '' ),
            'org_number'      => sanitize_text_field( $data['org_number'] ?? '' ),
            'address'         => sanitize_textarea_field( $data['address'] ?? '' ),
            'email'           => sanitize_email( $data['email'] ?? '' ),
            'phone'           => sanitize_text_field( $data['phone'] ?? '' ),
            'dpo_name'        => sanitize_text_field( $data['dpo_name'] ?? '' ),
            'dpo_email'       => sanitize_email( $data['dpo_email'] ?? '' ),
            'privacy_page_id' => intval( $data['privacy_page_id'] ?? 0 ),
            'cookie_page_id'  => intval( $data['cookie_page_id'] ?? 0 ),
        );
        update_option( 'ccm_company_info', $clean );
        return $clean;
    }

    private static function get_cookie_data() {
        $categories = CCM_Categories::get_all();
        $grouped    = array();

        foreach ( $categories as $cat ) {
            $cookies = CCM_Categories::get_cookies( $cat['id'] );
            if ( ! empty( $cookies ) ) {
                $grouped[] = array(
                    'title'       => $cat['title'],
                    'slug'        => $cat['slug'],
                    'description' => $cat['description'],
                    'is_required' => (bool) $cat['is_required'],
                    'cookies'     => $cookies,
                );
            }
        }

        return $grouped;
    }

    private static function get_providers( $grouped ) {
        $providers = array();
        foreach ( $grouped as $group ) {
            foreach ( $group['cookies'] as $cookie ) {
                $p = trim( $cookie['provider'] );
                if ( $p && ! in_array( $p, $providers, true ) ) {
                    $providers[] = $p;
                }
            }
        }
        sort( $providers );
        return $providers;
    }

    public static function generate_privacy_policy() {
        $info    = self::get_company_info();
        $grouped = self::get_cookie_data();
        $site    = get_bloginfo( 'name' );
        $url     = home_url();
        $date    = wp_date( 'Y-m-d' );

        $company = ! empty( $info['company_name'] ) ? $info['company_name'] : $site;
        $providers = self::get_providers( $grouped );

        $has_analytics  = false;
        $has_marketing  = false;
        foreach ( $grouped as $g ) {
            if ( $g['slug'] === 'analytics' )  $has_analytics = true;
            if ( $g['slug'] === 'marketing' )  $has_marketing = true;
        }

        ob_start();
        ?>
<h2>Integritetspolicy</h2>
<p><em>Senast uppdaterad: <?php echo $date; ?></em></p>

<h3>1. Personuppgiftsansvarig</h3>
<p><?php echo esc_html( $company ); ?><?php
if ( ! empty( $info['org_number'] ) ) echo ', org.nr ' . esc_html( $info['org_number'] );
?> ("vi", "oss", "vår") ansvarar för behandlingen av dina personuppgifter i enlighet med EU:s dataskyddsförordning (GDPR) och tillämplig svensk lagstiftning.</p>
<?php if ( ! empty( $info['address'] ) ) : ?>
<p>Adress: <?php echo nl2br( esc_html( $info['address'] ) ); ?></p>
<?php endif; ?>
<?php if ( ! empty( $info['email'] ) ) : ?>
<p>E-post: <?php echo esc_html( $info['email'] ); ?></p>
<?php endif; ?>
<?php if ( ! empty( $info['phone'] ) ) : ?>
<p>Telefon: <?php echo esc_html( $info['phone'] ); ?></p>
<?php endif; ?>

<?php if ( ! empty( $info['dpo_name'] ) || ! empty( $info['dpo_email'] ) ) : ?>
<h3>2. Dataskyddsombud</h3>
<p><?php
if ( ! empty( $info['dpo_name'] ) ) echo esc_html( $info['dpo_name'] );
if ( ! empty( $info['dpo_name'] ) && ! empty( $info['dpo_email'] ) ) echo ', ';
if ( ! empty( $info['dpo_email'] ) ) echo esc_html( $info['dpo_email'] );
?></p>
<?php endif; ?>

<h3><?php echo ( ! empty( $info['dpo_name'] ) || ! empty( $info['dpo_email'] ) ) ? '3' : '2'; ?>. Vilka personuppgifter vi samlar in</h3>
<p>Via vår webbplats <strong><?php echo esc_html( $url ); ?></strong> kan vi komma att samla in följande typer av personuppgifter:</p>
<ul>
<li><strong>Tekniska data</strong> — IP-adress (hashad), webbläsartyp, operativsystem, besökta sidor och tidsstämplar.</li>
<li><strong>Samtyckesinformation</strong> — Dina cookie-val och samtyckes-ID.</li>
<?php if ( $has_analytics ) : ?>
<li><strong>Analysdata</strong> — Anonymiserad statistik om hur du använder webbplatsen (sidvisningar, sessionslängd, trafikkälla).</li>
<?php endif; ?>
<?php if ( $has_marketing ) : ?>
<li><strong>Marknadsföringsdata</strong> — Identifierare som används av tredjepartstjänster för riktad annonsering och konverteringsmätning.</li>
<?php endif; ?>
</ul>

<h3><?php echo ( ! empty( $info['dpo_name'] ) || ! empty( $info['dpo_email'] ) ) ? '4' : '3'; ?>. Rättslig grund för behandling</h3>
<p>Vi behandlar personuppgifter baserat på följande rättsliga grunder:</p>
<ul>
<li><strong>Berättigat intresse (Art. 6.1.f GDPR)</strong> — Nödvändiga cookies som krävs för att webbplatsen ska fungera tekniskt.</li>
<?php if ( $has_analytics || $has_marketing ) : ?>
<li><strong>Samtycke (Art. 6.1.a GDPR)</strong> — Analys- och marknadsföringscookies aktiveras först efter att du gett ditt aktiva samtycke via vår cookie-banner.</li>
<?php endif; ?>
</ul>

<h3><?php echo ( ! empty( $info['dpo_name'] ) || ! empty( $info['dpo_email'] ) ) ? '5' : '4'; ?>. Cookies och spårningsteknik</h3>
<p>Vår webbplats använder cookies. En fullständig förteckning över vilka cookies vi använder, deras syfte och livslängd finns i vår <a href="<?php
$cookie_page_id = $info['cookie_page_id'];
echo $cookie_page_id ? esc_url( get_permalink( $cookie_page_id ) ) : '#';
?>">cookiepolicy</a>.</p>

<?php if ( ! empty( $providers ) ) : ?>
<h3><?php echo ( ! empty( $info['dpo_name'] ) || ! empty( $info['dpo_email'] ) ) ? '6' : '5'; ?>. Tredjeparter</h3>
<p>Vi delar uppgifter med följande tredjepartsleverantörer som en del av webbplatsens funktionalitet:</p>
<ul>
<?php foreach ( $providers as $p ) : ?>
<li><?php echo esc_html( $p ); ?></li>
<?php endforeach; ?>
</ul>
<p>Varje tredjepartsleverantör behandlar uppgifter i enlighet med sin egen integritetspolicy. Vi rekommenderar att du läser deras respektive policyer.</p>
<?php endif; ?>

<h3><?php echo ( ! empty( $info['dpo_name'] ) || ! empty( $info['dpo_email'] ) ) ? '7' : '6'; ?>. Dina rättigheter</h3>
<p>Enligt GDPR har du följande rättigheter:</p>
<ul>
<li><strong>Tillgång</strong> — Du har rätt att begära ett utdrag av de personuppgifter vi behandlar om dig.</li>
<li><strong>Rättelse</strong> — Du har rätt att begära att felaktiga uppgifter korrigeras.</li>
<li><strong>Radering</strong> — Du har rätt att begära att dina uppgifter raderas ("rätten att bli glömd").</li>
<li><strong>Begränsning</strong> — Du har rätt att begära att behandlingen av dina uppgifter begränsas.</li>
<li><strong>Dataportabilitet</strong> — Du har rätt att få dina uppgifter i ett strukturerat, maskinläsbart format.</li>
<li><strong>Invändning</strong> — Du har rätt att invända mot behandling baserad på berättigat intresse.</li>
<li><strong>Återkallande av samtycke</strong> — Du kan när som helst ändra eller återkalla ditt cookie-samtycke via vår cookie-banner.</li>
</ul>
<p>Kontakta oss på <strong><?php echo esc_html( ! empty( $info['email'] ) ? $info['email'] : get_option( 'admin_email' ) ); ?></strong> för att utöva dina rättigheter.</p>

<h3><?php echo ( ! empty( $info['dpo_name'] ) || ! empty( $info['dpo_email'] ) ) ? '8' : '7'; ?>. Tillsynsmyndighet</h3>
<p>Om du anser att vi behandlar dina personuppgifter i strid med GDPR har du rätt att lämna klagomål till <a href="https://www.imy.se" target="_blank" rel="noopener">Integritetsskyddsmyndigheten (IMY)</a>.</p>

<h3><?php echo ( ! empty( $info['dpo_name'] ) || ! empty( $info['dpo_email'] ) ) ? '9' : '8'; ?>. Ändringar</h3>
<p>Vi kan komma att uppdatera denna integritetspolicy. Den senaste versionen finns alltid tillgänglig på denna sida med angivet uppdateringsdatum.</p>
        <?php
        return trim( ob_get_clean() );
    }

    public static function generate_cookie_policy() {
        $info    = self::get_company_info();
        $grouped = self::get_cookie_data();
        $site    = get_bloginfo( 'name' );
        $date    = wp_date( 'Y-m-d' );

        $company = ! empty( $info['company_name'] ) ? $info['company_name'] : $site;

        ob_start();
        ?>
<h2>Cookiepolicy</h2>
<p><em>Senast uppdaterad: <?php echo $date; ?></em></p>

<h3>Vad är cookies?</h3>
<p>Cookies är små textfiler som lagras på din enhet (dator, telefon eller surfplatta) när du besöker en webbplats. De används för att webbplatsen ska fungera korrekt, för att analysera trafik och för att anpassa innehåll och annonser.</p>

<h3>Hur vi använder cookies</h3>
<p><?php echo esc_html( $company ); ?> använder cookies på denna webbplats. Nedan hittar du en fullständig förteckning över alla cookies vi använder, grupperade efter kategori.</p>

<h3>Hantera dina samtycken</h3>
<p>När du besöker vår webbplats visas en cookie-banner där du kan välja vilka kategorier av cookies du godkänner. Du kan när som helst ändra dina val genom att klicka på cookie-ikonen eller rensa dina cookies i webbläsaren.</p>
<p>Nödvändiga cookies kräver inget samtycke eftersom de behövs för att webbplatsen ska fungera. Alla övriga cookies aktiveras först efter ditt aktiva samtycke.</p>

<?php if ( ! empty( $grouped ) ) : ?>
<h3>Cookies vi använder</h3>
<?php foreach ( $grouped as $group ) : ?>
<h4><?php echo esc_html( $group['title'] ); ?><?php echo $group['is_required'] ? ' (krävs alltid)' : ''; ?></h4>
<p><?php echo esc_html( $group['description'] ); ?></p>
<table>
<thead>
<tr>
<th>Cookie</th>
<th>Leverantör</th>
<th>Syfte</th>
<th>Livslängd</th>
</tr>
</thead>
<tbody>
<?php foreach ( $group['cookies'] as $cookie ) : ?>
<tr>
<td><code><?php echo esc_html( $cookie['name'] ); ?></code></td>
<td><?php echo esc_html( $cookie['provider'] ); ?></td>
<td><?php echo esc_html( $cookie['purpose'] ); ?></td>
<td><?php echo esc_html( $cookie['expiry'] ?: '—' ); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endforeach; ?>
<?php else : ?>
<p><em>Inga cookies har registrerats ännu. Använd cookie-skannern för att identifiera och importera cookies.</em></p>
<?php endif; ?>

<h3>Tredjepartscookies</h3>
<p>Vissa cookies sätts av tredjepartstjänster som vi använder. Vi kontrollerar inte dessa cookies och hänvisar till respektive leverantörs integritetspolicy för mer information.</p>

<h3>Dina rättigheter</h3>
<p>Mer information om hur vi behandlar personuppgifter finns i vår <?php
$privacy_page_id = $info['privacy_page_id'];
if ( $privacy_page_id ) {
    echo '<a href="' . esc_url( get_permalink( $privacy_page_id ) ) . '">integritetspolicy</a>';
} else {
    echo 'integritetspolicy';
}
?>.</p>

<h3>Kontakt</h3>
<p>Har du frågor om vår användning av cookies? Kontakta oss på <strong><?php echo esc_html( ! empty( $info['email'] ) ? $info['email'] : get_option( 'admin_email' ) ); ?></strong>.</p>

<h3>Ändringar</h3>
<p>Denna cookiepolicy kan komma att uppdateras när vi ändrar vilka cookies som används. Kontrollera denna sida regelbundet för den senaste versionen.</p>
        <?php
        return trim( ob_get_clean() );
    }

    public static function create_page( $type ) {
        $info = self::get_company_info();

        if ( $type === 'privacy' ) {
            $title   = 'Integritetspolicy';
            $content = self::generate_privacy_policy();
            $key     = 'privacy_page_id';
        } elseif ( $type === 'cookie' ) {
            $title   = 'Cookiepolicy';
            $content = self::generate_cookie_policy();
            $key     = 'cookie_page_id';
        } else {
            return new WP_Error( 'invalid_type', 'Ogiltig sidtyp.' );
        }

        $existing_id = intval( $info[ $key ] );

        // Skriv bara över om ID:t faktiskt pekar på en sida — annars riskerar
        // ett felaktigt sparat ID att skriva över ett inlägg eller en produkt.
        if ( $existing_id && 'page' === get_post_type( $existing_id ) && get_post_status( $existing_id ) ) {
            wp_update_post( array(
                'ID'           => $existing_id,
                'post_content' => $content,
            ) );
            return $existing_id;
        }

        $page_id = wp_insert_post( array(
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'draft',
            'post_type'    => 'page',
        ) );

        if ( is_wp_error( $page_id ) ) {
            return $page_id;
        }

        $info[ $key ] = $page_id;
        update_option( 'ccm_company_info', $info );

        return $page_id;
    }
}

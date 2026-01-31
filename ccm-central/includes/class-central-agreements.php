<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCMC_Agreements {

    public static function get_all( $site_id = null ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ccmc_agreements';
        if ( $site_id ) {
            return $wpdb->get_results(
                $wpdb->prepare( "SELECT * FROM {$table} WHERE site_id = %d ORDER BY created_at DESC", $site_id ),
                ARRAY_A
            );
        }
        return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC", ARRAY_A );
    }

    public static function get( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ccmc_agreements';
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
            ARRAY_A
        );
    }

    public static function create( $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ccmc_agreements';
        $wpdb->insert( $table, array(
            'site_id'              => intval( $data['site_id'] ),
            'type'                 => sanitize_text_field( $data['type'] ?? 'pub' ),
            'version'              => sanitize_text_field( $data['version'] ?? '1.0' ),
            'controller_name'      => sanitize_text_field( $data['controller_name'] ?? '' ),
            'controller_org'       => sanitize_text_field( $data['controller_org'] ?? '' ),
            'controller_address'   => sanitize_textarea_field( $data['controller_address'] ?? '' ),
            'controller_email'     => sanitize_email( $data['controller_email'] ?? '' ),
            'controller_contact'   => sanitize_text_field( $data['controller_contact'] ?? '' ),
            'processor_name'       => sanitize_text_field( $data['processor_name'] ?? '' ),
            'processor_org'        => sanitize_text_field( $data['processor_org'] ?? '' ),
            'processor_address'    => sanitize_textarea_field( $data['processor_address'] ?? '' ),
            'processor_email'      => sanitize_email( $data['processor_email'] ?? '' ),
            'sub_processors'       => sanitize_textarea_field( $data['sub_processors'] ?? '' ),
            'purpose'              => sanitize_textarea_field( $data['purpose'] ?? '' ),
            'data_types'           => sanitize_textarea_field( $data['data_types'] ?? '' ),
            'storage_period'       => sanitize_text_field( $data['storage_period'] ?? '12 månader' ),
            'signed_at'            => null,
            'created_at'           => current_time( 'mysql' ),
        ) );
        return $wpdb->insert_id;
    }

    public static function mark_signed( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ccmc_agreements';
        return $wpdb->update( $table, array( 'signed_at' => current_time( 'mysql' ) ), array( 'id' => intval( $id ) ) );
    }

    public static function delete( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ccmc_agreements';
        return $wpdb->delete( $table, array( 'id' => intval( $id ) ) );
    }

    public static function generate_html( $id ) {
        $a = self::get( $id );
        if ( ! $a ) {
            return '';
        }

        global $wpdb;
        $site = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ccmc_sites WHERE id = %d", $a['site_id'] ),
            ARRAY_A
        );

        $processor_info = get_option( 'ccmc_processor_info', array() );
        $date = $a['signed_at'] ? wp_date( 'j F Y', strtotime( $a['signed_at'] ) ) : wp_date( 'j F Y' );

        ob_start();
        ?>
<div class="ccmc-agreement">

<h1>Personuppgiftsbiträdesavtal (PUB/DPA)</h1>
<p><strong>Version:</strong> <?php echo esc_html( $a['version'] ); ?> | <strong>Datum:</strong> <?php echo esc_html( $date ); ?></p>

<hr>

<h2>1. Parter</h2>

<h3>1.1 Personuppgiftsansvarig (den Ansvarige)</h3>
<table class="ccmc-agreement-table">
<tr><td><strong>Företag/Organisation:</strong></td><td><?php echo esc_html( $a['controller_name'] ); ?></td></tr>
<tr><td><strong>Organisationsnummer:</strong></td><td><?php echo esc_html( $a['controller_org'] ); ?></td></tr>
<tr><td><strong>Adress:</strong></td><td><?php echo esc_html( $a['controller_address'] ); ?></td></tr>
<tr><td><strong>E-post:</strong></td><td><?php echo esc_html( $a['controller_email'] ); ?></td></tr>
<tr><td><strong>Kontaktperson:</strong></td><td><?php echo esc_html( $a['controller_contact'] ); ?></td></tr>
</table>

<h3>1.2 Personuppgiftsbiträde (Biträdet)</h3>
<table class="ccmc-agreement-table">
<tr><td><strong>Företag/Organisation:</strong></td><td><?php echo esc_html( $a['processor_name'] ); ?></td></tr>
<tr><td><strong>Organisationsnummer:</strong></td><td><?php echo esc_html( $a['processor_org'] ); ?></td></tr>
<tr><td><strong>Adress:</strong></td><td><?php echo esc_html( $a['processor_address'] ); ?></td></tr>
<tr><td><strong>E-post:</strong></td><td><?php echo esc_html( $a['processor_email'] ); ?></td></tr>
</table>

<h2>2. Bakgrund och syfte</h2>
<p>Detta avtal reglerar Biträdets behandling av personuppgifter för den Ansvariges räkning i enlighet med Europaparlamentets och rådets förordning (EU) 2016/679 (GDPR), artikel 28.</p>
<p>Biträdet tillhandahåller tjänsten Cookie Consent Manager Central Dashboard, som övervakar den Ansvariges webbplats compliance-status avseende cookie-samtycken och GDPR-efterlevnad.</p>

<?php if ( ! empty( $a['purpose'] ) ) : ?>
<p><strong>Specifikt ändamål:</strong> <?php echo esc_html( $a['purpose'] ); ?></p>
<?php endif; ?>

<h2>3. Behandlingens omfattning</h2>

<h3>3.1 Typer av uppgifter som behandlas</h3>
<?php if ( ! empty( $a['data_types'] ) ) : ?>
<p><?php echo nl2br( esc_html( $a['data_types'] ) ); ?></p>
<?php else : ?>
<p>Biträdet behandlar enbart följande aggregerade och anonymiserade data från den Ansvariges webbplats:</p>
<ul>
<li>Domännamn och teknisk konfiguration (plugin-version, WordPress-version)</li>
<li>Antal registrerade cookie-kategorier och cookies</li>
<li>Huruvida integritetspolicy och cookiepolicy finns publicerade</li>
<li>Audit-resultat: antal cookies som matchar/inte matchar registret (inga cookie-värden)</li>
<li>Aggregerad samtyckes-statistik: totalt antal samtycken, acceptansgrad per kategori</li>
<li>Cookie-namn som utgör överträdelser eller är okända (inga personuppgifter)</li>
</ul>
<p><strong>Följande data behandlas INTE:</strong></p>
<ul>
<li>Besökares IP-adresser (ej ens hashade)</li>
<li>Samtyckes-UUID eller andra identifierare kopplade till enskilda besökare</li>
<li>Cookie-värden eller sessionsdata</li>
<li>User-agent eller webbläsarinformation</li>
<li>Någon form av personuppgifter från den Ansvariges besökare</li>
</ul>
<?php endif; ?>

<h3>3.2 Kategorier av registrerade</h3>
<p>Inga kategorier av registrerade personer berörs. All data som behandlas är aggregerad metadata om webbplatsens konfiguration och compliance-status, utan koppling till identifierbara fysiska personer.</p>

<h2>4. Biträdets skyldigheter</h2>

<h3>4.1 Instruktioner</h3>
<p>Biträdet ska enbart behandla personuppgifter i enlighet med den Ansvariges dokumenterade instruktioner, inklusive vad gäller överföring till tredjeland. Om Biträdet anser att en instruktion strider mot GDPR eller annan dataskyddslagstiftning ska den Ansvarige omedelbart informeras.</p>

<h3>4.2 Sekretess</h3>
<p>Biträdet ska säkerställa att personer som har behörighet att behandla personuppgifterna har åtagit sig att iaktta sekretess eller omfattas av lämplig lagstadgad tystnadsplikt.</p>

<h3>4.3 Säkerhetsåtgärder (Art. 32)</h3>
<p>Biträdet ska vidta lämpliga tekniska och organisatoriska åtgärder för att säkerställa en säkerhetsnivå som är lämplig i förhållande till risken, inbegripet:</p>
<ul>
<li>Kryptering av data vid överföring (TLS/HTTPS)</li>
<li>Kryptering av data vid lagring</li>
<li>Åtkomstkontroll med stark autentisering</li>
<li>Regelbundna säkerhetskopior</li>
<li>Loggning av åtkomst till systemet</li>
<li>Lagring inom EU/EES</li>
</ul>

<h3>4.4 Underbiträden</h3>
<?php if ( ! empty( $a['sub_processors'] ) ) : ?>
<p>Den Ansvarige godkänner följande underbiträden:</p>
<p><?php echo nl2br( esc_html( $a['sub_processors'] ) ); ?></p>
<?php else : ?>
<p>Biträdet ska inte anlita ett annat biträde (underbiträde) utan den Ansvariges föregående specifika eller allmänna skriftliga godkännande. Vid allmänt godkännande ska Biträdet informera den Ansvarige om eventuella planerade ändringar avseende tillägg eller ersättning av underbiträden, så att den Ansvarige ges möjlighet att göra invändningar.</p>
<?php endif; ?>

<h3>4.5 Bistånd vid rättighetsutövning</h3>
<p>Med hänsyn till behandlingens art ska Biträdet bistå den Ansvarige genom lämpliga tekniska och organisatoriska åtgärder, i den mån detta är möjligt, för fullgörandet av den Ansvariges skyldighet att svara på begäranden om utövande av registrerades rättigheter enligt kapitel III i GDPR.</p>

<h3>4.6 Bistånd vid säkerhetsincidenter</h3>
<p>Biträdet ska utan onödigt dröjsmål, och senast inom 24 timmar, underrätta den Ansvarige efter att ha fått kännedom om en personuppgiftsincident.</p>

<h2>5. Lagringsperiod</h2>
<p>Rapportdata lagras i <strong><?php echo esc_html( $a['storage_period'] ); ?></strong> efter att rapporten mottagits. Vid avtalets upphörande ska Biträdet, efter den Ansvariges val, radera eller återlämna samtliga uppgifter och radera befintliga kopior, såvida inte unionsrätten eller medlemsstaternas nationella rätt kräver lagring.</p>

<h2>6. Granskning och revision</h2>
<p>Biträdet ska ge den Ansvarige tillgång till all information som krävs för att visa att de skyldigheter som fastställs i artikel 28 i GDPR har fullgjorts, samt möjliggöra och bidra till granskningar, inbegripet inspektioner, som genomförs av den Ansvarige eller en av denne utsedd revisor.</p>

<h2>7. Avtalstid och uppsägning</h2>
<p>Detta avtal gäller så länge Biträdet behandlar uppgifter för den Ansvariges räkning. Vid uppsägning av det underliggande tjänsteavtalet ska detta biträdesavtal automatiskt upphöra att gälla, varvid punkt 5 om radering ska tillämpas.</p>

<h2>8. Tillämplig lag och tvistelösning</h2>
<p>Detta avtal ska tolkas i enlighet med svensk lag. Tvister ska i första hand lösas genom förhandling. Om parterna inte kan enas ska tvisten avgöras av svensk allmän domstol.</p>

<hr>

<h2>Underskrifter</h2>

<table class="ccmc-agreement-signatures">
<tr>
<td style="width:50%;">
<p><strong>Personuppgiftsansvarig</strong></p>
<p>&nbsp;</p>
<p>_________________________________</p>
<p><?php echo esc_html( $a['controller_name'] ); ?></p>
<p><?php echo esc_html( $a['controller_contact'] ); ?></p>
<p>Datum: _____________</p>
</td>
<td style="width:50%;">
<p><strong>Personuppgiftsbiträde</strong></p>
<p>&nbsp;</p>
<p>_________________________________</p>
<p><?php echo esc_html( $a['processor_name'] ); ?></p>
<p>Datum: _____________</p>
</td>
</tr>
</table>

</div>
        <?php
        return ob_get_clean();
    }

    public static function get_processor_defaults() {
        return get_option( 'ccmc_processor_info', array(
            'name'    => '',
            'org'     => '',
            'address' => '',
            'email'   => '',
        ) );
    }

    public static function save_processor_info( $data ) {
        update_option( 'ccmc_processor_info', array(
            'name'    => sanitize_text_field( $data['processor_name'] ?? '' ),
            'org'     => sanitize_text_field( $data['processor_org'] ?? '' ),
            'address' => sanitize_textarea_field( $data['processor_address'] ?? '' ),
            'email'   => sanitize_email( $data['processor_email'] ?? '' ),
        ) );
    }
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$sites_table = $wpdb->prefix . 'ccmc_sites';
$sites       = $wpdb->get_results( "SELECT id, domain, display_name, company_name, org_number, contact_name, contact_email FROM {$sites_table} ORDER BY domain", ARRAY_A );
$processor   = CCMC_Agreements::get_processor_defaults();

if ( isset( $_GET['msg'] ) ) {
    $msgs = array(
        'created' => 'Avtal skapat.',
        'signed'  => 'Avtal markerat som signerat.',
        'deleted' => 'Avtal borttaget.',
    );
    if ( isset( $msgs[ $_GET['msg'] ] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msgs[ $_GET['msg'] ] ) . '</p></div>';
    }
}

// View single agreement
if ( isset( $_GET['view'] ) ) {
    $agreement = CCMC_Agreements::get( intval( $_GET['view'] ) );
    if ( $agreement ) {
        $html = CCMC_Agreements::generate_html( $agreement['id'] );
        $site = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$sites_table} WHERE id = %d", $agreement['site_id'] ), ARRAY_A );
        ?>
        <p>
            <a href="<?php echo admin_url( 'admin.php?page=ccm-central&tab=agreements' ); ?>">&larr; Tillbaka till avtal</a>
            &nbsp;|&nbsp;
            <button type="button" class="button" onclick="window.print();">Skriv ut / Spara PDF</button>
            <?php if ( ! $agreement['signed_at'] ) : ?>
                &nbsp;|&nbsp;
                <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=ccm-central&tab=agreements&ccmc_sign_agreement=' . $agreement['id'] ), 'ccmc_sign_agreement' ); ?>" class="button button-primary">Markera som signerat</a>
            <?php else : ?>
                &nbsp;|&nbsp;
                <span class="ccmc-badge ccmc-status-ok">Signerat <?php echo esc_html( wp_date( 'Y-m-d', strtotime( $agreement['signed_at'] ) ) ); ?></span>
            <?php endif; ?>
        </p>
        <div class="ccmc-agreement-preview">
            <?php echo $html; ?>
        </div>
        <?php
        return;
    }
}

// List agreements
$agreements = CCMC_Agreements::get_all();
?>

<h2>PUB/DPA-avtal</h2>
<p>Skapa och hantera personuppgiftsbiträdesavtal för varje sajt som rapporterar till CCM Central.</p>

<?php if ( ! empty( $agreements ) ) : ?>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>Sajt</th>
            <th>Ansvarig</th>
            <th>Version</th>
            <th>Skapad</th>
            <th>Status</th>
            <th>Åtgärd</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $agreements as $ag ) :
            $site = $wpdb->get_row( $wpdb->prepare( "SELECT domain FROM {$sites_table} WHERE id = %d", $ag['site_id'] ), ARRAY_A );
        ?>
            <tr>
                <td><?php echo esc_html( $site['domain'] ?? '—' ); ?></td>
                <td><?php echo esc_html( $ag['controller_name'] ); ?></td>
                <td><?php echo esc_html( $ag['version'] ); ?></td>
                <td><?php echo esc_html( wp_date( 'Y-m-d', strtotime( $ag['created_at'] ) ) ); ?></td>
                <td>
                    <?php if ( $ag['signed_at'] ) : ?>
                        <span class="ccmc-badge ccmc-status-ok">Signerat</span>
                    <?php else : ?>
                        <span class="ccmc-badge ccmc-status-warn">Ej signerat</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?php echo admin_url( 'admin.php?page=ccm-central&tab=agreements&view=' . $ag['id'] ); ?>" class="button button-small">Visa</a>
                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=ccm-central&tab=agreements&ccmc_delete_agreement=' . $ag['id'] ), 'ccmc_delete_agreement' ); ?>" class="button button-small" onclick="return confirm('Radera avtalet?');">Ta bort</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<hr>
<h3>Skapa nytt avtal</h3>

<?php if ( empty( $processor['name'] ) ) : ?>
    <div class="notice notice-warning"><p>Du behöver ange biträdets uppgifter under <a href="<?php echo admin_url( 'admin.php?page=ccm-central&tab=settings' ); ?>">Inställningar</a> innan du skapar avtal.</p></div>
<?php endif; ?>

<form method="post">
    <?php wp_nonce_field( 'ccmc_agreement_action' ); ?>

    <table class="form-table">
        <tr>
            <th><label for="site_id">Sajt</label></th>
            <td>
                <select id="site_id" name="site_id" required>
                    <option value="">— Välj sajt —</option>
                    <?php foreach ( $sites as $site ) : ?>
                        <option value="<?php echo esc_attr( $site['id'] ); ?>"
                                data-company="<?php echo esc_attr( $site['company_name'] ); ?>"
                                data-org="<?php echo esc_attr( $site['org_number'] ); ?>"
                                data-contact="<?php echo esc_attr( $site['contact_name'] ); ?>"
                                data-email="<?php echo esc_attr( $site['contact_email'] ); ?>">
                            <?php echo esc_html( $site['domain'] ); ?>
                            <?php if ( $site['display_name'] ) echo ' — ' . esc_html( $site['display_name'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>

    <h4>Personuppgiftsansvarig (kunden)</h4>
    <table class="form-table">
        <tr>
            <th><label for="controller_name">Företagsnamn</label></th>
            <td><input type="text" id="controller_name" name="controller_name" class="regular-text" required></td>
        </tr>
        <tr>
            <th><label for="controller_org">Org.nummer</label></th>
            <td><input type="text" id="controller_org" name="controller_org" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="controller_address">Adress</label></th>
            <td><textarea id="controller_address" name="controller_address" rows="2" class="large-text"></textarea></td>
        </tr>
        <tr>
            <th><label for="controller_email">E-post</label></th>
            <td><input type="email" id="controller_email" name="controller_email" class="regular-text" required></td>
        </tr>
        <tr>
            <th><label for="controller_contact">Kontaktperson</label></th>
            <td><input type="text" id="controller_contact" name="controller_contact" class="regular-text"></td>
        </tr>
    </table>

    <h4>Personuppgiftsbiträde (du)</h4>
    <table class="form-table">
        <tr>
            <th>Företagsnamn</th>
            <td><input type="text" name="processor_name" class="regular-text" value="<?php echo esc_attr( $processor['name'] ?? '' ); ?>"></td>
        </tr>
        <tr>
            <th>Org.nummer</th>
            <td><input type="text" name="processor_org" class="regular-text" value="<?php echo esc_attr( $processor['org'] ?? '' ); ?>"></td>
        </tr>
        <tr>
            <th>Adress</th>
            <td><textarea name="processor_address" rows="2" class="large-text"><?php echo esc_textarea( $processor['address'] ?? '' ); ?></textarea></td>
        </tr>
        <tr>
            <th>E-post</th>
            <td><input type="email" name="processor_email" class="regular-text" value="<?php echo esc_attr( $processor['email'] ?? '' ); ?>"></td>
        </tr>
    </table>

    <h4>Avtalsinformation</h4>
    <table class="form-table">
        <tr>
            <th><label for="purpose">Specifikt ändamål</label></th>
            <td><textarea id="purpose" name="purpose" rows="2" class="large-text" placeholder="Övervakning av cookie-compliance och GDPR-efterlevnad via CCM Central Dashboard."></textarea></td>
        </tr>
        <tr>
            <th><label for="sub_processors">Underbiträden</label></th>
            <td><textarea id="sub_processors" name="sub_processors" rows="3" class="large-text" placeholder="Hostingbolag AB — Serverhosting, Stockholm, Sverige&#10;Backup AB — Säkerhetskopiering, Göteborg, Sverige"></textarea></td>
        </tr>
        <tr>
            <th><label for="storage_period">Lagringsperiod</label></th>
            <td><input type="text" id="storage_period" name="storage_period" class="regular-text" value="12 månader"></td>
        </tr>
    </table>

    <?php submit_button( 'Skapa avtal', 'primary', 'ccmc_create_agreement' ); ?>
</form>

<script>
document.getElementById('site_id').addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    if (opt.value) {
        document.getElementById('controller_name').value = opt.dataset.company || '';
        document.getElementById('controller_org').value = opt.dataset.org || '';
        document.getElementById('controller_email').value = opt.dataset.email || '';
        document.getElementById('controller_contact').value = opt.dataset.contact || '';
    }
});
</script>

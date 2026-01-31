<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$sites_table = $wpdb->prefix . 'ccmc_sites';

// Site detail view
if ( isset( $_GET['view'] ) ) {
    $site_id = intval( $_GET['view'] );
    $site    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$sites_table} WHERE id = %d", $site_id ), ARRAY_A );

    if ( ! $site ) {
        echo '<p>Sajten hittades inte.</p>';
        return;
    }

    $reports = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ccmc_reports WHERE site_id = %d ORDER BY created_at DESC LIMIT 10",
        $site_id
    ), ARRAY_A );

    $open_violations = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ccmc_violations WHERE site_id = %d AND resolved_at IS NULL ORDER BY first_seen_at DESC",
        $site_id
    ), ARRAY_A );

    $latest = ! empty( $reports ) ? $reports[0] : null;
    $raw    = $latest && $latest['raw_data'] ? json_decode( $latest['raw_data'], true ) : array();
    ?>

    <p><a href="<?php echo admin_url( 'admin.php?page=ccm-central&tab=sites' ); ?>">&larr; Tillbaka till sajter</a></p>

    <h2><?php echo esc_html( $site['domain'] ); ?></h2>
    <?php if ( $site['display_name'] ) : ?>
        <p><?php echo esc_html( $site['display_name'] ); ?> — <?php echo esc_html( $site['company_name'] ); ?> (<?php echo esc_html( $site['org_number'] ); ?>)</p>
    <?php endif; ?>

    <?php if ( $latest ) : ?>
    <div class="ccmc-detail-grid">
        <div class="ccmc-detail-section">
            <h3>Konfiguration</h3>
            <table class="form-table">
                <tr><th>Plugin-version</th><td><?php echo esc_html( $latest['plugin_version'] ); ?></td></tr>
                <tr><th>WordPress</th><td><?php echo esc_html( $latest['wp_version'] ); ?></td></tr>
                <tr><th>PHP</th><td><?php echo esc_html( $latest['php_version'] ); ?></td></tr>
                <tr><th>Kategorier</th><td><?php echo intval( $latest['categories_count'] ); ?></td></tr>
                <tr><th>Registrerade cookies</th><td><?php echo intval( $latest['cookies_registered'] ); ?></td></tr>
                <tr><th>Integritetspolicy</th><td><?php echo $latest['has_privacy_policy'] ? '<span class="ccmc-status-ok">&#10003; Ja</span>' : '<span class="ccmc-status-crit">&#10007; Saknas</span>'; ?></td></tr>
                <tr><th>Cookiepolicy</th><td><?php echo $latest['has_cookie_policy'] ? '<span class="ccmc-status-ok">&#10003; Ja</span>' : '<span class="ccmc-status-crit">&#10007; Saknas</span>'; ?></td></tr>
                <tr><th>Consent Mode v2</th><td><?php echo $latest['gcm_enabled'] ? '<span class="ccmc-status-ok">&#10003; Aktiv</span>' : '<span class="ccmc-status-warn">&#10007; Inaktiv</span>'; ?></td></tr>
                <tr><th>Cookie-livslängd</th><td><?php echo intval( $latest['cookie_lifetime'] ); ?> dagar</td></tr>
                <tr><th>Blockerade cookies</th><td><?php echo intval( $latest['blocked_cookies_count'] ); ?></td></tr>
            </table>
        </div>

        <div class="ccmc-detail-section">
            <h3>Audit-resultat</h3>
            <table class="form-table">
                <tr><th>OK</th><td><span class="ccmc-status-ok"><?php echo intval( $latest['audit_ok_count'] ); ?></span></td></tr>
                <tr><th>Varningar</th><td><span class="ccmc-status-warn"><?php echo intval( $latest['audit_warning_count'] ); ?></span></td></tr>
                <tr><th>Överträdelser</th><td><span class="ccmc-status-crit"><?php echo intval( $latest['audit_violation_count'] ); ?></span></td></tr>
                <tr><th>Okända cookies</th><td><?php echo intval( $latest['audit_unknown_count'] ); ?></td></tr>
            </table>

            <?php if ( ! empty( $open_violations ) ) : ?>
                <h4>Aktiva överträdelser</h4>
                <ul class="ccmc-violation-list">
                    <?php foreach ( $open_violations as $v ) : ?>
                        <li>
                            <span class="dashicons dashicons-dismiss ccmc-status-crit"></span>
                            <strong><?php echo esc_html( $v['cookie_name'] ); ?></strong>
                            (<?php echo esc_html( $v['category'] ); ?>)
                            — sedan <?php echo esc_html( human_time_diff( strtotime( $v['first_seen_at'] ) ) ); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php
            $unknown_list = $raw['audit']['unknown_cookies'] ?? array();
            if ( ! empty( $unknown_list ) ) : ?>
                <h4>Okända cookies</h4>
                <ul>
                    <?php foreach ( $unknown_list as $name ) : ?>
                        <li><code><?php echo esc_html( $name ); ?></code></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="ccmc-detail-section">
            <h3>Samtycken</h3>
            <table class="form-table">
                <tr><th>Totalt</th><td><?php echo intval( $latest['consent_total'] ); ?></td></tr>
                <tr><th>Senaste 30 dagar</th><td><?php echo intval( $latest['consent_last_30d'] ); ?></td></tr>
                <tr><th>Analys-acceptans</th><td><?php echo round( $latest['acceptance_analytics'] * 100 ); ?>%</td></tr>
                <tr><th>Marknadsförings-acceptans</th><td><?php echo round( $latest['acceptance_marketing'] * 100 ); ?>%</td></tr>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <h3>Rapporthistorik</h3>
    <?php if ( empty( $reports ) ) : ?>
        <p>Inga rapporter mottagna ännu.</p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Övertr.</th>
                    <th>Okända</th>
                    <th>Samtycken (30d)</th>
                    <th>Plugin</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $reports as $r ) : ?>
                    <tr>
                        <td><?php echo esc_html( $r['report_date'] ); ?></td>
                        <td><?php echo intval( $r['audit_violation_count'] ); ?></td>
                        <td><?php echo intval( $r['audit_unknown_count'] ); ?></td>
                        <td><?php echo intval( $r['consent_last_30d'] ); ?></td>
                        <td><?php echo esc_html( $r['plugin_version'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php
    return;
}

// Sites list view
$sites = $wpdb->get_results( "SELECT * FROM {$sites_table} ORDER BY domain ASC", ARRAY_A );
$new_key = get_transient( 'ccmc_new_api_key' );
if ( $new_key ) {
    delete_transient( 'ccmc_new_api_key' );
}

if ( isset( $_GET['msg'] ) ) {
    $msgs = array(
        'added'   => 'Sajt tillagd.',
        'deleted' => 'Sajt borttagen.',
    );
    if ( isset( $msgs[ $_GET['msg'] ] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msgs[ $_GET['msg'] ] ) . '</p></div>';
    }
}

if ( $new_key ) {
    echo '<div class="notice notice-warning"><p><strong>API-nyckel (visas bara en gång):</strong> <code>' . esc_html( $new_key ) . '</code></p><p>Kopiera nyckeln och ange den i sajtens CCM-plugin under Inställningar &gt; Central rapportering.</p></div>';
}
?>

<h2>Registrerade sajter</h2>

<?php if ( ! empty( $sites ) ) : ?>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>Domän</th>
            <th>Namn</th>
            <th>Kontakt</th>
            <th>Status</th>
            <th>Senaste rapport</th>
            <th>Åtgärd</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $sites as $site ) : ?>
            <tr>
                <td>
                    <a href="<?php echo admin_url( 'admin.php?page=ccm-central&tab=sites&view=' . $site['id'] ); ?>">
                        <strong><?php echo esc_html( $site['domain'] ); ?></strong>
                    </a>
                </td>
                <td><?php echo esc_html( $site['display_name'] ); ?></td>
                <td><?php echo esc_html( $site['contact_name'] ); ?><br><small><?php echo esc_html( $site['contact_email'] ); ?></small></td>
                <td>
                    <?php
                    $labels = array( 'active' => 'OK', 'warning' => 'Varning', 'critical' => 'Kritisk', 'inactive' => 'Inaktiv' );
                    $classes = array( 'active' => 'ccmc-status-ok', 'warning' => 'ccmc-status-warn', 'critical' => 'ccmc-status-crit', 'inactive' => 'ccmc-status-inactive' );
                    ?>
                    <span class="ccmc-badge <?php echo esc_attr( $classes[ $site['status'] ] ?? '' ); ?>"><?php echo esc_html( $labels[ $site['status'] ] ?? $site['status'] ); ?></span>
                </td>
                <td><?php echo $site['last_report_at'] ? esc_html( $site['last_report_at'] ) : '—'; ?></td>
                <td>
                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=ccm-central&tab=sites&ccmc_delete_site=' . $site['id'] ), 'ccmc_delete_site' ); ?>" class="button button-small" onclick="return confirm('Radera sajt och all tillhörande data?');">Ta bort</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<hr>
<h3>Lägg till sajt</h3>
<form method="post">
    <?php wp_nonce_field( 'ccmc_site_action' ); ?>
    <table class="form-table">
        <tr>
            <th><label for="domain">Domän</label></th>
            <td><input type="text" id="domain" name="domain" class="regular-text" placeholder="example.com" required></td>
        </tr>
        <tr>
            <th><label for="display_name">Visningsnamn</label></th>
            <td><input type="text" id="display_name" name="display_name" class="regular-text" placeholder="Min Webbshop"></td>
        </tr>
        <tr>
            <th><label for="company_name">Företagsnamn</label></th>
            <td><input type="text" id="company_name" name="company_name" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="org_number">Org.nummer</label></th>
            <td><input type="text" id="org_number" name="org_number" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="contact_name">Kontaktperson</label></th>
            <td><input type="text" id="contact_name" name="contact_name" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="contact_email">Kontakt-e-post</label></th>
            <td><input type="email" id="contact_email" name="contact_email" class="regular-text"></td>
        </tr>
    </table>
    <?php submit_button( 'Lägg till sajt', 'primary', 'ccmc_add_site' ); ?>
</form>

<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$sites_table  = $wpdb->prefix . 'ccmc_sites';
$alerts_table = $wpdb->prefix . 'ccmc_alerts';

$total_sites    = intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$sites_table}" ) );
$active_sites   = intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$sites_table} WHERE status = 'active'" ) );
$warning_sites  = intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$sites_table} WHERE status = 'warning'" ) );
$critical_sites = intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$sites_table} WHERE status = 'critical'" ) );
$inactive_sites = intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$sites_table} WHERE status = 'inactive'" ) );
$active_alerts  = intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$alerts_table} WHERE resolved_at IS NULL" ) );

$sites = $wpdb->get_results(
    "SELECT s.*,
            (SELECT COUNT(*) FROM {$wpdb->prefix}ccmc_violations v WHERE v.site_id = s.id AND v.resolved_at IS NULL) AS open_violations,
            (SELECT r.audit_unknown_count FROM {$wpdb->prefix}ccmc_reports r WHERE r.site_id = s.id ORDER BY r.created_at DESC LIMIT 1) AS latest_unknown
     FROM {$sites_table} s
     ORDER BY FIELD(s.status, 'critical', 'warning', 'active', 'inactive'), s.domain ASC",
    ARRAY_A
);
?>

<div class="ccmc-dashboard">
    <div class="ccmc-stats-row">
        <div class="ccmc-stat-card">
            <span class="ccmc-stat-number"><?php echo $total_sites; ?></span>
            <span class="ccmc-stat-label">Sajter totalt</span>
        </div>
        <div class="ccmc-stat-card ccmc-stat-ok">
            <span class="ccmc-stat-number"><?php echo $active_sites; ?></span>
            <span class="ccmc-stat-label">OK</span>
        </div>
        <div class="ccmc-stat-card ccmc-stat-warn">
            <span class="ccmc-stat-number"><?php echo $warning_sites; ?></span>
            <span class="ccmc-stat-label">Varning</span>
        </div>
        <div class="ccmc-stat-card ccmc-stat-crit">
            <span class="ccmc-stat-number"><?php echo $critical_sites; ?></span>
            <span class="ccmc-stat-label">Kritisk</span>
        </div>
        <div class="ccmc-stat-card">
            <span class="ccmc-stat-number"><?php echo $active_alerts; ?></span>
            <span class="ccmc-stat-label">Aktiva varningar</span>
        </div>
    </div>

    <?php if ( empty( $sites ) ) : ?>
        <p>Inga sajter registrerade ännu. <a href="<?php echo admin_url( 'admin.php?page=ccm-central&tab=sites' ); ?>">Lägg till en sajt</a>.</p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:30px;"></th>
                    <th>Sajt</th>
                    <th>Status</th>
                    <th>Överträdelser</th>
                    <th>Okända cookies</th>
                    <th>Senaste rapport</th>
                    <th>Åtgärd</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $sites as $site ) : ?>
                    <?php
                    $status_icon  = '';
                    $status_label = '';
                    $status_class = '';
                    switch ( $site['status'] ) {
                        case 'active':
                            $status_icon  = 'dashicons-yes-alt';
                            $status_class = 'ccmc-status-ok';
                            $status_label = 'OK';
                            break;
                        case 'warning':
                            $status_icon  = 'dashicons-warning';
                            $status_class = 'ccmc-status-warn';
                            $status_label = 'Varning';
                            break;
                        case 'critical':
                            $status_icon  = 'dashicons-dismiss';
                            $status_class = 'ccmc-status-crit';
                            $status_label = 'Kritisk';
                            break;
                        case 'inactive':
                            $status_icon  = 'dashicons-clock';
                            $status_class = 'ccmc-status-inactive';
                            $status_label = 'Inaktiv';
                            break;
                    }
                    $detail_url = admin_url( 'admin.php?page=ccm-central&tab=sites&view=' . $site['id'] );
                    $last_report = $site['last_report_at'] ? human_time_diff( strtotime( $site['last_report_at'] ) ) . ' sedan' : '—';
                    ?>
                    <tr>
                        <td><span class="dashicons <?php echo esc_attr( $status_icon ); ?> <?php echo esc_attr( $status_class ); ?>"></span></td>
                        <td>
                            <a href="<?php echo esc_url( $detail_url ); ?>"><strong><?php echo esc_html( $site['domain'] ); ?></strong></a>
                            <?php if ( $site['display_name'] && $site['display_name'] !== $site['domain'] ) : ?>
                                <br><small><?php echo esc_html( $site['display_name'] ); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="ccmc-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
                        <td><?php echo intval( $site['open_violations'] ); ?></td>
                        <td><?php echo intval( $site['latest_unknown'] ?? 0 ); ?></td>
                        <td><?php echo esc_html( $last_report ); ?></td>
                        <td><a href="<?php echo esc_url( $detail_url ); ?>" class="button button-small">Detaljer</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

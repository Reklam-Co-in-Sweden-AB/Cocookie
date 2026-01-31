<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$alerts_table = $wpdb->prefix . 'ccmc_alerts';
$sites_table  = $wpdb->prefix . 'ccmc_sites';

$show_resolved = isset( $_GET['show_resolved'] );

if ( $show_resolved ) {
    $alerts = $wpdb->get_results(
        "SELECT a.*, s.domain, s.display_name
         FROM {$alerts_table} a
         JOIN {$sites_table} s ON a.site_id = s.id
         ORDER BY a.resolved_at IS NULL DESC, a.created_at DESC
         LIMIT 100",
        ARRAY_A
    );
} else {
    $alerts = $wpdb->get_results(
        "SELECT a.*, s.domain, s.display_name
         FROM {$alerts_table} a
         JOIN {$sites_table} s ON a.site_id = s.id
         WHERE a.resolved_at IS NULL
         ORDER BY FIELD(a.severity, 'critical', 'warning', 'info'), a.created_at DESC",
        ARRAY_A
    );
}
?>

<h2>Varningar</h2>
<p>
    <?php if ( $show_resolved ) : ?>
        <a href="<?php echo admin_url( 'admin.php?page=ccm-central&tab=alerts' ); ?>">Visa bara aktiva</a>
    <?php else : ?>
        <a href="<?php echo admin_url( 'admin.php?page=ccm-central&tab=alerts&show_resolved=1' ); ?>">Visa även lösta</a>
    <?php endif; ?>
</p>

<?php if ( empty( $alerts ) ) : ?>
    <p>Inga aktiva varningar.</p>
<?php else : ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:30px;"></th>
                <th>Sajt</th>
                <th>Typ</th>
                <th>Meddelande</th>
                <th>Skapad</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $alerts as $alert ) :
                $icon_class = '';
                switch ( $alert['severity'] ) {
                    case 'critical':
                        $icon_class = 'ccmc-status-crit';
                        break;
                    case 'warning':
                        $icon_class = 'ccmc-status-warn';
                        break;
                    default:
                        $icon_class = 'ccmc-status-inactive';
                        break;
                }
                $type_labels = array(
                    'violation'       => 'Överträdelse',
                    'missing_policy'  => 'Saknad policy',
                    'inactive'        => 'Inaktiv',
                    'outdated'        => 'Föråldrad',
                    'unknown_cookies' => 'Okända cookies',
                );
            ?>
                <tr<?php echo $alert['resolved_at'] ? ' style="opacity:0.6;"' : ''; ?>>
                    <td><span class="dashicons dashicons-<?php echo $alert['severity'] === 'critical' ? 'dismiss' : 'warning'; ?> <?php echo esc_attr( $icon_class ); ?>"></span></td>
                    <td>
                        <a href="<?php echo admin_url( 'admin.php?page=ccm-central&tab=sites&view=' . $alert['site_id'] ); ?>">
                            <?php echo esc_html( $alert['domain'] ); ?>
                        </a>
                    </td>
                    <td><?php echo esc_html( $type_labels[ $alert['type'] ] ?? $alert['type'] ); ?></td>
                    <td><?php echo esc_html( $alert['message'] ); ?></td>
                    <td><?php echo esc_html( human_time_diff( strtotime( $alert['created_at'] ) ) ); ?> sedan</td>
                    <td>
                        <?php if ( $alert['resolved_at'] ) : ?>
                            <span class="ccmc-badge ccmc-status-ok">Löst</span>
                        <?php else : ?>
                            <span class="ccmc-badge <?php echo esc_attr( $icon_class ); ?>">Aktiv</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

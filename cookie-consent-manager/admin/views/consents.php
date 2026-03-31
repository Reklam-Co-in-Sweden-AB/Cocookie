<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$page     = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$per_page = 20;
$result   = CCM_Consent::get_consents( $page, $per_page );
$items    = $result['items'];
$total    = $result['total'];
$pages    = $result['pages'];
?>

<h2>Samtyckes-logg <span class="ccm-count">(<?php echo esc_html( $total ); ?> totalt)</span></h2>

<table class="widefat fixed striped">
    <thead>
        <tr>
            <th>UUID</th>
            <th>IP-hash</th>
            <th>Kategorier</th>
            <th>User Agent</th>
            <th>Tidpunkt</th>
        </tr>
    </thead>
    <tbody>
        <?php if ( empty( $items ) ) : ?>
            <tr><td colspan="5">Inga samtycken registrerade ännu.</td></tr>
        <?php else : ?>
            <?php foreach ( $items as $item ) : ?>
                <?php $cats = json_decode( $item['categories'], true ); ?>
                <tr>
                    <td><code><?php echo esc_html( $item['consent_uuid'] ); ?></code></td>
                    <td><code title="<?php echo esc_attr( $item['ip_hash'] ); ?>"><?php echo esc_html( substr( $item['ip_hash'], 0, 12 ) . '…' ); ?></code></td>
                    <td>
                        <?php if ( is_array( $cats ) ) : ?>
                            <?php foreach ( $cats as $slug => $accepted ) : ?>
                                <span class="ccm-consent-badge <?php echo $accepted ? 'ccm-accepted' : 'ccm-rejected'; ?>">
                                    <?php echo esc_html( $slug ); ?>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td><span title="<?php echo esc_attr( $item['user_agent'] ); ?>"><?php echo esc_html( substr( $item['user_agent'], 0, 40 ) ); ?><?php echo strlen( $item['user_agent'] ) > 40 ? '…' : ''; ?></span></td>
                    <td><?php echo esc_html( $item['created_at'] ); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php if ( $pages > 1 ) : ?>
    <div class="tablenav">
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo esc_html( $total ); ?> objekt</span>
            <span class="pagination-links">
                <?php if ( $page > 1 ) : ?>
                    <a class="prev-page button" href="<?php echo esc_url( admin_url( 'options-general.php?page=cookie-consent&tab=consents&paged=' . ( $page - 1 ) ) ); ?>">&lsaquo;</a>
                <?php endif; ?>
                <span class="paging-input"><?php echo esc_html( $page ); ?> av <?php echo esc_html( $pages ); ?></span>
                <?php if ( $page < $pages ) : ?>
                    <a class="next-page button" href="<?php echo esc_url( admin_url( 'options-general.php?page=cookie-consent&tab=consents&paged=' . ( $page + 1 ) ) ); ?>">&rsaquo;</a>
                <?php endif; ?>
            </span>
        </div>
    </div>
<?php endif; ?>

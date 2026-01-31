<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$stats = CCM_Consent::get_statistics();
?>

<div class="ccm-statistics-wrap">
    <h2>Samtyckes-statistik</h2>

    <?php if ( $stats['total'] === 0 ) : ?>
        <p>Inga samtycken registrerade ännu.</p>
    <?php else : ?>

        <div class="ccm-stats-overview">
            <div class="ccm-stat-card">
                <span class="ccm-stat-number"><?php echo number_format_i18n( $stats['total'] ); ?></span>
                <span class="ccm-stat-label">Totalt antal samtycken</span>
            </div>
        </div>

        <h3>Acceptansgrad per kategori</h3>
        <table class="widefat fixed striped ccm-stats-table">
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>Accepterade</th>
                    <th>Avvisade</th>
                    <th>Acceptansgrad</th>
                    <th style="width: 40%;">Fördelning</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $stats['categories'] as $slug => $cat ) :
                    $rate = $stats['total'] > 0 ? round( ( $cat['accepted'] / $stats['total'] ) * 100, 1 ) : 0;
                ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html( $cat['title'] ); ?></strong>
                            <?php if ( $cat['required'] ) : ?>
                                <span class="ccm-stats-required">krävs</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format_i18n( $cat['accepted'] ); ?></td>
                        <td><?php echo number_format_i18n( $cat['rejected'] ); ?></td>
                        <td><?php echo esc_html( $rate ); ?>%</td>
                        <td>
                            <div class="ccm-progress-bar">
                                <div class="ccm-progress-fill ccm-progress-accepted" style="width: <?php echo esc_attr( $rate ); ?>%;"></div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ( ! empty( $stats['recent'] ) ) : ?>
            <h3>Samtycken senaste 30 dagarna</h3>
            <div class="ccm-chart-wrap">
                <div class="ccm-bar-chart">
                    <?php
                    $max_count = max( array_column( $stats['recent'], 'count' ) );
                    foreach ( $stats['recent'] as $day ) :
                        $height = $max_count > 0 ? round( ( $day['count'] / $max_count ) * 100 ) : 0;
                        $label  = wp_date( 'j/n', strtotime( $day['date'] ) );
                    ?>
                        <div class="ccm-bar-col" title="<?php echo esc_attr( $day['date'] . ': ' . $day['count'] . ' samtycken' ); ?>">
                            <div class="ccm-bar-value"><?php echo intval( $day['count'] ); ?></div>
                            <div class="ccm-bar" style="height: <?php echo esc_attr( $height ); ?>%;"></div>
                            <div class="ccm-bar-label"><?php echo esc_html( $label ); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

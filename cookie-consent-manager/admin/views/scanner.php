<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$last_scan = get_option( 'ccm_last_scan', '' );
$results   = CCM_Cookie_Scanner::get_scan_results();
?>

<div class="ccm-scanner-wrap">
    <h2>Cookie-skanner</h2>
    <p>Skanna din webbplats för att hitta cookies som sätts. Scanningen körs utan blockering — alla skript och iframes laddas fritt så att resultatet visar de cookies en ny besökare faktiskt skulle få. Hittade cookies matchas automatiskt mot kända cookie-mönster och föreslår kategori, leverantör och syfte.</p>

    <div class="ccm-scanner-actions">
        <button type="button" id="ccm-start-scan" class="button button-primary">Starta skanning</button>
        <?php if ( $last_scan ) : ?>
            <span class="ccm-last-scan">Senaste skanning: <?php echo esc_html( $last_scan ); ?></span>
        <?php endif; ?>
    </div>

    <div id="ccm-scan-status" class="ccm-scan-status" style="display:none;">
        <span class="spinner is-active"></span>
        <span class="ccm-scan-status-text">Skannar webbplatsen...</span>
    </div>

    <div id="ccm-scan-results" class="ccm-scan-results" <?php echo empty( $results ) ? 'style="display:none;"' : ''; ?>>
        <?php if ( ! empty( $results ) ) : ?>
            <div class="ccm-scan-toolbar">
                <span class="ccm-count"><?php echo count( $results ); ?> cookies hittade</span>
                <button type="button" id="ccm-import-all" class="button">Importera alla</button>
            </div>
        <?php endif; ?>

        <table class="wp-list-table widefat fixed striped" id="ccm-scan-table">
            <thead>
                <tr>
                    <th class="column-name">Cookie-namn</th>
                    <th class="column-type">Typ</th>
                    <th class="column-category">Föreslagen kategori</th>
                    <th class="column-provider">Leverantör</th>
                    <th class="column-purpose">Syfte</th>
                    <th class="column-status">Status</th>
                    <th class="column-actions">Åtgärd</th>
                </tr>
            </thead>
            <tbody id="ccm-scan-tbody">
                <?php if ( ! empty( $results ) ) : ?>
                    <?php foreach ( $results as $row ) : ?>
                        <tr data-id="<?php echo esc_attr( $row['id'] ); ?>">
                            <td class="column-name">
                                <strong><?php echo esc_html( $row['name'] ); ?></strong>
                                <?php if ( ! empty( $row['domain'] ) ) : ?>
                                    <br><small class="ccm-domain"><?php echo esc_html( $row['domain'] ); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="column-type">
                                <?php
                                $type_labels = array( 'cookie' => 'Cookie', 'localStorage' => 'localStorage', 'sessionStorage' => 'sessionStorage' );
                                $st = $row['storage_type'] ?? 'cookie';
                                echo esc_html( $type_labels[ $st ] ?? $st );
                                ?>
                            </td>
                            <td class="column-category">
                                <span class="ccm-category-badge ccm-cat-<?php echo esc_attr( $row['suggested_category'] ); ?>">
                                    <?php
                                    $labels = array( 'necessary' => 'Nödvändig', 'analytics' => 'Analys', 'marketing' => 'Marknadsföring' );
                                    echo esc_html( $labels[ $row['suggested_category'] ] ?? $row['suggested_category'] );
                                    ?>
                                </span>
                            </td>
                            <td class="column-provider"><?php echo esc_html( $row['suggested_provider'] ); ?></td>
                            <td class="column-purpose"><?php echo esc_html( $row['suggested_purpose'] ); ?></td>
                            <td class="column-status">
                                <?php if ( $row['is_imported'] ) : ?>
                                    <span class="ccm-import-status ccm-imported">Importerad</span>
                                <?php else : ?>
                                    <span class="ccm-import-status ccm-not-imported">Ej importerad</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <?php if ( ! $row['is_imported'] ) : ?>
                                    <button type="button" class="button button-small ccm-import-cookie" data-id="<?php echo esc_attr( $row['id'] ); ?>">Importera</button>
                                <?php else : ?>
                                    <span class="dashicons dashicons-yes-alt ccm-imported-icon"></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<iframe id="ccm-scan-iframe" style="display:none; width:0; height:0; border:none;" sandbox="allow-same-origin allow-scripts"></iframe>

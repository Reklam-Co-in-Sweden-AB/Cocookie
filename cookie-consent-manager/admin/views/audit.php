<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$blocked_cookies = get_option( 'ccm_blocked_cookies', array() );
?>

<div class="ccm-audit-wrap">
    <h2>Cookie-audit</h2>
    <p>Granska vilka cookies som faktiskt sätts på din webbplats och jämför mot registrerade cookies. Identifiera överträdelser och okända cookies.</p>

    <div class="ccm-audit-actions">
        <button type="button" id="ccm-start-audit" class="button button-primary">Kör audit</button>
    </div>

    <div id="ccm-audit-status" class="ccm-audit-status" style="display:none;">
        <span class="spinner is-active"></span>
        <span class="ccm-audit-status-text">Startar audit...</span>
    </div>

    <div id="ccm-audit-registered" class="ccm-audit-section" style="display:none;">
        <h3>Cookie-status</h3>
        <table class="wp-list-table widefat fixed striped" id="ccm-audit-table">
            <thead>
                <tr>
                    <th>Namn</th>
                    <th>Kategori</th>
                    <th>Förväntat</th>
                    <th>Hittad</th>
                    <th>Status</th>
                    <th>Åtgärd</th>
                </tr>
            </thead>
            <tbody id="ccm-audit-tbody"></tbody>
        </table>
    </div>

    <div id="ccm-audit-violations" class="ccm-audit-section" style="display:none;">
        <h3>Överträdelser</h3>
        <div class="ccm-violation-box">
            <p>Följande cookies sätts utan att användaren har gett samtycke för kategorin:</p>
            <ul id="ccm-audit-violation-list"></ul>
        </div>
    </div>

    <div id="ccm-audit-unknown" class="ccm-audit-section" style="display:none;">
        <h3>Okända cookies</h3>
        <div class="ccm-unknown-box">
            <p>Följande cookies hittades men är inte registrerade i systemet:</p>
            <ul id="ccm-audit-unknown-list"></ul>
        </div>
    </div>

    <div id="ccm-audit-blocked" class="ccm-audit-section">
        <h3>Blockerade cookies</h3>
        <?php if ( empty( $blocked_cookies ) ) : ?>
            <p id="ccm-no-blocked">Inga cookies är blockerade.</p>
        <?php endif; ?>
        <ul id="ccm-blocked-list" class="ccm-blocked-box">
            <?php foreach ( $blocked_cookies as $name ) : ?>
                <li data-name="<?php echo esc_attr( $name ); ?>">
                    <span class="dashicons dashicons-no ccm-status-violation"></span>
                    <strong><?php echo esc_html( $name ); ?></strong>
                    <button type="button" class="button button-small ccm-unblock-cookie" data-name="<?php echo esc_attr( $name ); ?>">Avblockera</button>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<iframe id="ccm-audit-iframe" style="display:none;" sandbox="allow-same-origin allow-scripts"></iframe>

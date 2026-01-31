<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$info = CCM_Policy_Generator::get_company_info();

if ( isset( $_GET['msg'] ) ) {
    $messages = array(
        'saved'   => 'Företagsuppgifter sparade.',
        'created' => 'Sida skapad som utkast.',
        'updated' => 'Sida uppdaterad.',
    );
    $msg = sanitize_text_field( $_GET['msg'] );
    if ( isset( $messages[ $msg ] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $messages[ $msg ] ) . '</p></div>';
    }
}

$privacy_page = $info['privacy_page_id'] ? get_post( $info['privacy_page_id'] ) : null;
$cookie_page  = $info['cookie_page_id'] ? get_post( $info['cookie_page_id'] ) : null;
?>

<div class="ccm-policies-wrap">
    <h2>Policygenerator</h2>
    <div class="notice notice-warning inline ccm-policy-disclaimer">
        <p><strong>Observera:</strong> De genererade texterna är mallar baserade på dina importerade cookies och företagsuppgifter. De utgör inte juridisk rådgivning. Granska och anpassa texterna efter din verksamhets specifika behov, eller konsultera en jurist.</p>
    </div>

    <!-- Företagsuppgifter -->
    <div class="ccm-policy-section">
        <h3>Företagsuppgifter</h3>
        <p class="description">Dessa uppgifter används i de genererade policytexterna.</p>
        <form method="post">
            <?php wp_nonce_field( 'ccm_policy_company' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="company_name">Företagsnamn</label></th>
                    <td><input type="text" id="company_name" name="company_name" value="<?php echo esc_attr( $info['company_name'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></td>
                </tr>
                <tr>
                    <th><label for="org_number">Organisationsnummer</label></th>
                    <td><input type="text" id="org_number" name="org_number" value="<?php echo esc_attr( $info['org_number'] ); ?>" class="regular-text" placeholder="XXXXXX-XXXX"></td>
                </tr>
                <tr>
                    <th><label for="ccm_address">Adress</label></th>
                    <td><textarea id="ccm_address" name="address" rows="2" class="regular-text"><?php echo esc_textarea( $info['address'] ); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="ccm_email">E-post</label></th>
                    <td><input type="email" id="ccm_email" name="email" value="<?php echo esc_attr( $info['email'] ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="ccm_phone">Telefon</label></th>
                    <td><input type="text" id="ccm_phone" name="phone" value="<?php echo esc_attr( $info['phone'] ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="dpo_name">Dataskyddsombud (namn)</label></th>
                    <td><input type="text" id="dpo_name" name="dpo_name" value="<?php echo esc_attr( $info['dpo_name'] ); ?>" class="regular-text" placeholder="Valfritt"></td>
                </tr>
                <tr>
                    <th><label for="dpo_email">Dataskyddsombud (e-post)</label></th>
                    <td><input type="email" id="dpo_email" name="dpo_email" value="<?php echo esc_attr( $info['dpo_email'] ); ?>" class="regular-text" placeholder="Valfritt"></td>
                </tr>
            </table>
            <input type="hidden" name="privacy_page_id" value="<?php echo esc_attr( $info['privacy_page_id'] ); ?>">
            <input type="hidden" name="cookie_page_id" value="<?php echo esc_attr( $info['cookie_page_id'] ); ?>">
            <?php submit_button( 'Spara uppgifter', 'primary', 'ccm_save_company' ); ?>
        </form>
    </div>

    <!-- Integritetspolicy -->
    <div class="ccm-policy-section">
        <h3>Integritetspolicy</h3>
        <?php if ( $privacy_page ) : ?>
            <p class="ccm-page-status">
                Kopplad till sida: <a href="<?php echo esc_url( get_edit_post_link( $privacy_page->ID ) ); ?>"><strong><?php echo esc_html( $privacy_page->post_title ); ?></strong></a>
                (<?php echo esc_html( get_post_status_object( $privacy_page->post_status )->label ); ?>)
                — <a href="<?php echo esc_url( get_permalink( $privacy_page->ID ) ); ?>" target="_blank">Visa</a>
            </p>
        <?php endif; ?>

        <div class="ccm-policy-preview">
            <div class="ccm-policy-preview-header">
                <strong>Förhandsgranskning</strong>
                <button type="button" class="button ccm-toggle-preview" data-target="ccm-privacy-preview">Visa/dölj</button>
            </div>
            <div id="ccm-privacy-preview" class="ccm-policy-preview-content" style="display:none;">
                <?php echo CCM_Policy_Generator::generate_privacy_policy(); ?>
            </div>
        </div>

        <form method="post" class="ccm-policy-actions">
            <?php wp_nonce_field( 'ccm_policy_create' ); ?>
            <input type="hidden" name="policy_type" value="privacy">
            <?php if ( $privacy_page ) : ?>
                <?php submit_button( 'Uppdatera sida med ny text', 'secondary', 'ccm_create_policy_page', false ); ?>
            <?php else : ?>
                <?php submit_button( 'Skapa sida (utkast)', 'secondary', 'ccm_create_policy_page', false ); ?>
            <?php endif; ?>
        </form>
    </div>

    <!-- Cookiepolicy -->
    <div class="ccm-policy-section">
        <h3>Cookiepolicy</h3>
        <?php if ( $cookie_page ) : ?>
            <p class="ccm-page-status">
                Kopplad till sida: <a href="<?php echo esc_url( get_edit_post_link( $cookie_page->ID ) ); ?>"><strong><?php echo esc_html( $cookie_page->post_title ); ?></strong></a>
                (<?php echo esc_html( get_post_status_object( $cookie_page->post_status )->label ); ?>)
                — <a href="<?php echo esc_url( get_permalink( $cookie_page->ID ) ); ?>" target="_blank">Visa</a>
            </p>
        <?php endif; ?>

        <div class="ccm-policy-preview">
            <div class="ccm-policy-preview-header">
                <strong>Förhandsgranskning</strong>
                <button type="button" class="button ccm-toggle-preview" data-target="ccm-cookie-preview">Visa/dölj</button>
            </div>
            <div id="ccm-cookie-preview" class="ccm-policy-preview-content" style="display:none;">
                <?php echo CCM_Policy_Generator::generate_cookie_policy(); ?>
            </div>
        </div>

        <form method="post" class="ccm-policy-actions">
            <?php wp_nonce_field( 'ccm_policy_create' ); ?>
            <input type="hidden" name="policy_type" value="cookie">
            <?php if ( $cookie_page ) : ?>
                <?php submit_button( 'Uppdatera sida med ny text', 'secondary', 'ccm_create_policy_page', false ); ?>
            <?php else : ?>
                <?php submit_button( 'Skapa sida (utkast)', 'secondary', 'ccm_create_policy_page', false ); ?>
            <?php endif; ?>
        </form>
    </div>

    <!-- Juridisk info -->
    <div class="ccm-policy-section ccm-legal-links">
        <h3>Juridiska resurser</h3>
        <p>Håll dig uppdaterad om aktuella regler genom dessa myndigheter och organisationer:</p>
        <ul>
            <li><a href="https://www.imy.se" target="_blank" rel="noopener">Integritetsskyddsmyndigheten (IMY)</a> — Svensk tillsynsmyndighet för dataskydd och GDPR.</li>
            <li><a href="https://www.imy.se/verksamhet/dataskydd/det-har-galler-enligt-gdpr/cookies-och-liknande-tekniker/" target="_blank" rel="noopener">IMY om cookies</a> — Specifik vägledning om cookies och spårningsteknik.</li>
            <li><a href="https://edpb.europa.eu/" target="_blank" rel="noopener">European Data Protection Board (EDPB)</a> — EU-nivå riktlinjer och beslut om dataskydd.</li>
            <li><a href="https://commission.europa.eu/law/law-topic/data-protection_en" target="_blank" rel="noopener">EU-kommissionen om dataskydd</a> — GDPR-förordningen och ePrivacy-direktivet.</li>
        </ul>
        <p class="description">Mallarna i denna plugin baseras på GDPR (EU 2016/679) och ePrivacy-direktivet (2002/58/EG). Lagstiftning kan ändras — kontrollera källorna ovan regelbundet och anpassa dina texter vid behov.</p>
    </div>
</div>

<script>
document.querySelectorAll('.ccm-toggle-preview').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var target = document.getElementById(this.getAttribute('data-target'));
        target.style.display = target.style.display === 'none' ? 'block' : 'none';
    });
});
</script>

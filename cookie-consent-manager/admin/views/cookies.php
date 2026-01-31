<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$categories = CCM_Categories::get_all();
$cookies    = CCM_Categories::get_cookies();
$edit_id    = isset( $_GET['edit_cookie'] ) ? intval( $_GET['edit_cookie'] ) : 0;
$editing    = $edit_id ? CCM_Categories::get_cookie( $edit_id ) : null;

if ( isset( $_GET['msg'] ) ) {
    $messages = array(
        'saved'        => 'Cookie sparad.',
        'deleted'      => 'Cookie borttagen.',
        'imported'     => 'Cookies importerade.',
        'import_error' => 'Importen misslyckades. Kontrollera att filen är giltig JSON.',
    );
    $msg_key  = sanitize_text_field( $_GET['msg'] );
    $msg_text = $messages[ $msg_key ] ?? 'Klart.';
    $type     = $msg_key === 'import_error' ? 'error' : 'success';
    echo '<div class="notice notice-' . $type . ' is-dismissible"><p>' . esc_html( $msg_text ) . '</p></div>';
}

// Index categories by id for display
$cat_map = array();
foreach ( $categories as $cat ) {
    $cat_map[ $cat['id'] ] = $cat['title'];
}
?>

<h2><?php echo $editing ? 'Redigera cookie' : 'Lägg till cookie'; ?></h2>
<form method="post" class="ccm-form">
    <?php wp_nonce_field( 'ccm_cookie_action' ); ?>
    <?php if ( $editing ) : ?>
        <input type="hidden" name="cookie_id" value="<?php echo esc_attr( $editing['id'] ); ?>">
    <?php endif; ?>
    <table class="form-table">
        <tr>
            <th><label for="category_id">Kategori</label></th>
            <td>
                <select id="category_id" name="category_id" required>
                    <option value="">— Välj kategori —</option>
                    <?php foreach ( $categories as $cat ) : ?>
                        <option value="<?php echo esc_attr( $cat['id'] ); ?>" <?php selected( $editing['category_id'] ?? '', $cat['id'] ); ?>>
                            <?php echo esc_html( $cat['title'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="name">Cookie-namn</label></th>
            <td><input type="text" id="name" name="name" value="<?php echo esc_attr( $editing['name'] ?? '' ); ?>" class="regular-text" required></td>
        </tr>
        <tr>
            <th><label for="provider">Leverantör</label></th>
            <td><input type="text" id="provider" name="provider" value="<?php echo esc_attr( $editing['provider'] ?? '' ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="purpose">Syfte</label></th>
            <td><textarea id="purpose" name="purpose" rows="3" class="large-text"><?php echo esc_textarea( $editing['purpose'] ?? '' ); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="expiry">Livslängd</label></th>
            <td><input type="text" id="expiry" name="expiry" value="<?php echo esc_attr( $editing['expiry'] ?? '' ); ?>" class="regular-text" placeholder="T.ex. 1 år"></td>
        </tr>
    </table>
    <?php submit_button( $editing ? 'Uppdatera' : 'Lägg till', 'primary', 'ccm_save_cookie' ); ?>
    <?php if ( $editing ) : ?>
        <a href="<?php echo esc_url( admin_url( 'options-general.php?page=cookie-consent&tab=cookies' ) ); ?>" class="button">Avbryt</a>
    <?php endif; ?>
</form>

<h2>Cookies</h2>

<div class="ccm-export-import">
    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ccm_export_cookies' ), 'ccm_export_cookies' ) ); ?>" class="button">Exportera JSON</a>

    <form method="post" enctype="multipart/form-data" class="ccm-import-form">
        <?php wp_nonce_field( 'ccm_import_cookies' ); ?>
        <input type="file" name="ccm_import_file" accept=".json">
        <button type="submit" name="ccm_import_cookies" class="button">Importera JSON</button>
    </form>
</div>

<table class="widefat fixed striped">
    <thead>
        <tr>
            <th>Namn</th>
            <th>Kategori</th>
            <th>Leverantör</th>
            <th>Syfte</th>
            <th>Livslängd</th>
            <th>Åtgärder</th>
        </tr>
    </thead>
    <tbody>
        <?php if ( empty( $cookies ) ) : ?>
            <tr><td colspan="6">Inga cookies registrerade ännu.</td></tr>
        <?php else : ?>
            <?php foreach ( $cookies as $cookie ) : ?>
                <tr>
                    <td><code><?php echo esc_html( $cookie['name'] ); ?></code></td>
                    <td><?php echo esc_html( $cat_map[ $cookie['category_id'] ] ?? '—' ); ?></td>
                    <td><?php echo esc_html( $cookie['provider'] ); ?></td>
                    <td><?php echo esc_html( wp_trim_words( $cookie['purpose'], 10 ) ); ?></td>
                    <td><?php echo esc_html( $cookie['expiry'] ); ?></td>
                    <td>
                        <a href="<?php echo esc_url( admin_url( 'options-general.php?page=cookie-consent&tab=cookies&edit_cookie=' . $cookie['id'] ) ); ?>">Redigera</a>
                        |
                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'options-general.php?page=cookie-consent&tab=cookies&ccm_delete_cookie=' . $cookie['id'] ), 'ccm_delete_cookie' ) ); ?>" onclick="return confirm('Är du säker?');">Ta bort</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

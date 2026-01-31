<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$categories = CCM_Categories::get_all();
$edit_id    = isset( $_GET['edit'] ) ? intval( $_GET['edit'] ) : 0;
$editing    = $edit_id ? CCM_Categories::get( $edit_id ) : null;

if ( isset( $_GET['msg'] ) ) {
    $msg = $_GET['msg'] === 'deleted' ? 'Kategori borttagen.' : 'Kategori sparad.';
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
}
?>

<h2><?php echo $editing ? 'Redigera kategori' : 'Lägg till kategori'; ?></h2>
<form method="post" class="ccm-form">
    <?php wp_nonce_field( 'ccm_category_action' ); ?>
    <?php if ( $editing ) : ?>
        <input type="hidden" name="category_id" value="<?php echo esc_attr( $editing['id'] ); ?>">
    <?php endif; ?>
    <table class="form-table">
        <tr>
            <th><label for="slug">Slug</label></th>
            <td><input type="text" id="slug" name="slug" value="<?php echo esc_attr( $editing['slug'] ?? '' ); ?>" class="regular-text" required></td>
        </tr>
        <tr>
            <th><label for="title">Titel</label></th>
            <td><input type="text" id="title" name="title" value="<?php echo esc_attr( $editing['title'] ?? '' ); ?>" class="regular-text" required></td>
        </tr>
        <tr>
            <th><label for="description">Beskrivning</label></th>
            <td><textarea id="description" name="description" rows="3" class="large-text"><?php echo esc_textarea( $editing['description'] ?? '' ); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="is_required">Nödvändig</label></th>
            <td><input type="checkbox" id="is_required" name="is_required" value="1" <?php checked( $editing['is_required'] ?? 0, 1 ); ?>></td>
        </tr>
        <tr>
            <th><label for="sort_order">Sorteringsordning</label></th>
            <td><input type="number" id="sort_order" name="sort_order" value="<?php echo esc_attr( $editing['sort_order'] ?? 0 ); ?>" class="small-text"></td>
        </tr>
    </table>
    <?php submit_button( $editing ? 'Uppdatera' : 'Lägg till', 'primary', 'ccm_save_category' ); ?>
    <?php if ( $editing ) : ?>
        <a href="<?php echo esc_url( admin_url( 'options-general.php?page=cookie-consent&tab=categories' ) ); ?>" class="button">Avbryt</a>
    <?php endif; ?>
</form>

<h2>Kategorier</h2>
<table class="widefat fixed striped">
    <thead>
        <tr>
            <th>Slug</th>
            <th>Titel</th>
            <th>Beskrivning</th>
            <th>Nödvändig</th>
            <th>Ordning</th>
            <th>Åtgärder</th>
        </tr>
    </thead>
    <tbody>
        <?php if ( empty( $categories ) ) : ?>
            <tr><td colspan="6">Inga kategorier ännu.</td></tr>
        <?php else : ?>
            <?php foreach ( $categories as $cat ) : ?>
                <tr>
                    <td><?php echo esc_html( $cat['slug'] ); ?></td>
                    <td><?php echo esc_html( $cat['title'] ); ?></td>
                    <td><?php echo esc_html( wp_trim_words( $cat['description'], 10 ) ); ?></td>
                    <td><?php echo $cat['is_required'] ? 'Ja' : 'Nej'; ?></td>
                    <td><?php echo esc_html( $cat['sort_order'] ); ?></td>
                    <td>
                        <a href="<?php echo esc_url( admin_url( 'options-general.php?page=cookie-consent&tab=categories&edit=' . $cat['id'] ) ); ?>">Redigera</a>
                        |
                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'options-general.php?page=cookie-consent&tab=categories&ccm_delete_category=' . $cat['id'] ), 'ccm_delete_category' ) ); ?>" onclick="return confirm('Är du säker?');">Ta bort</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$processor = CCMC_Agreements::get_processor_defaults();

if ( isset( $_GET['msg'] ) && $_GET['msg'] === 'saved' ) {
    echo '<div class="notice notice-success is-dismissible"><p>Inställningar sparade.</p></div>';
}
?>

<h2>Inställningar</h2>

<h3>Biträdets uppgifter</h3>
<p>Dessa uppgifter används som standard i PUB/DPA-avtal och identifierar dig som personuppgiftsbiträde.</p>

<form method="post">
    <?php wp_nonce_field( 'ccmc_processor_action' ); ?>
    <table class="form-table">
        <tr>
            <th><label for="processor_name">Företagsnamn</label></th>
            <td><input type="text" id="processor_name" name="processor_name" class="regular-text" value="<?php echo esc_attr( $processor['name'] ?? '' ); ?>"></td>
        </tr>
        <tr>
            <th><label for="processor_org">Organisationsnummer</label></th>
            <td><input type="text" id="processor_org" name="processor_org" class="regular-text" value="<?php echo esc_attr( $processor['org'] ?? '' ); ?>"></td>
        </tr>
        <tr>
            <th><label for="processor_address">Adress</label></th>
            <td><textarea id="processor_address" name="processor_address" rows="2" class="large-text"><?php echo esc_textarea( $processor['address'] ?? '' ); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="processor_email">E-post</label></th>
            <td><input type="email" id="processor_email" name="processor_email" class="regular-text" value="<?php echo esc_attr( $processor['email'] ?? '' ); ?>"></td>
        </tr>
    </table>
    <?php submit_button( 'Spara biträdesuppgifter', 'primary', 'ccmc_save_processor' ); ?>
</form>

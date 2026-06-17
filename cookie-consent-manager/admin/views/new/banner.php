<?php
/**
 * Banner-inställningar.
 *
 * @package CoCookie
 * @var array $data Controller data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$s   = $data['s'];
$msg = isset( $_GET['msg'] ) ? sanitize_text_field( $_GET['msg'] ) : '';
?>

<!-- Sidhuvud -->
<div class="cocookie-page-header">
	<div class="cocookie-page-header__title">
		<div class="cocookie-page-header__icon">
			<span class="dashicons dashicons-admin-appearance"></span>
		</div>
		<div class="cocookie-page-header__text">
			<h1><?php esc_html_e( 'Banner-inställningar', 'cocookie' ); ?></h1>
			<p><?php esc_html_e( 'Anpassa utseende och texter för din cookie-consent-banner.', 'cocookie' ); ?></p>
		</div>
	</div>
</div>

<?php if ( 'saved' === $msg ) : ?>
	<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Inställningar sparade.', 'cocookie' ); ?></p></div>
<?php endif; ?>

<form method="post">
	<?php wp_nonce_field( 'cocookie_banner_action' ); ?>

	<!-- Sektionen Texter -->
	<div class="cocookie-settings-section">
		<div class="cocookie-settings-section__header">
			<span class="dashicons dashicons-editor-textcolor"></span>
			<h2><?php esc_html_e( 'Texter', 'cocookie' ); ?></h2>
		</div>
		<div class="cocookie-settings-section__body">
			<table class="form-table">
				<tr>
					<th><label for="banner_title"><?php esc_html_e( 'Titel', 'cocookie' ); ?></label></th>
					<td>
						<input type="text" id="banner_title" name="banner_title" value="<?php echo esc_attr( $s['banner_title'] ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'Visas som rubrik i cookie-bannern.', 'cocookie' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="banner_text"><?php esc_html_e( 'Brödtext', 'cocookie' ); ?></label></th>
					<td>
						<textarea id="banner_text" name="banner_text" rows="3" class="large-text"><?php echo esc_textarea( $s['banner_text'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Förklaringstext som visas i bannern. Bör vara kortfattad och tydlig.', 'cocookie' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="accept_all_text"><?php esc_html_e( 'Acceptera-knapp', 'cocookie' ); ?></label></th>
					<td><input type="text" id="accept_all_text" name="accept_all_text" value="<?php echo esc_attr( $s['accept_all_text'] ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="reject_all_text"><?php esc_html_e( 'Avvisa-knapp', 'cocookie' ); ?></label></th>
					<td><input type="text" id="reject_all_text" name="reject_all_text" value="<?php echo esc_attr( $s['reject_all_text'] ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="save_text"><?php esc_html_e( 'Spara val-knapp', 'cocookie' ); ?></label></th>
					<td>
						<input type="text" id="save_text" name="save_text" value="<?php echo esc_attr( $s['save_text'] ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'Visas i inställnings-panelen när användaren sparar sitt val.', 'cocookie' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="settings_text"><?php esc_html_e( 'Inställnings-knapp', 'cocookie' ); ?></label></th>
					<td>
						<input type="text" id="settings_text" name="settings_text" value="<?php echo esc_attr( $s['settings_text'] ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'Länk som öppnar den detaljerade inställnings-panelen.', 'cocookie' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="google_consent_enabled"><?php esc_html_e( 'Googles samtyckeslänk', 'cocookie' ); ?></label></th>
					<td>
						<label>
							<input type="checkbox" id="google_consent_enabled" name="google_consent_enabled" value="1" <?php checked( ! empty( $s['google_consent_enabled'] ) ); ?>>
							<?php esc_html_e( 'Visa länk till Googles hantering av personuppgifter i bannern', 'cocookie' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Aktivera om sajten använder Googles annonstjänster (Google Ads, AdSense). Länken pekar på business.safety.google/privacy och krävs av Googles EU-policy för användarsamtycke.', 'cocookie' ); ?></p>
					</td>
				</tr>
				<tr class="cocookie-google-field">
					<th><label for="google_consent_intro"><?php esc_html_e( 'Google – inledande text', 'cocookie' ); ?></label></th>
					<td>
						<input type="text" id="google_consent_intro" name="google_consent_intro" value="<?php echo esc_attr( $s['google_consent_intro'] ); ?>" class="regular-text">
					</td>
				</tr>
				<tr class="cocookie-google-field">
					<th><label for="google_consent_link_text"><?php esc_html_e( 'Google – länktext', 'cocookie' ); ?></label></th>
					<td>
						<input type="text" id="google_consent_link_text" name="google_consent_link_text" value="<?php echo esc_attr( $s['google_consent_link_text'] ); ?>" class="regular-text">
					</td>
				</tr>
			</table>
		</div>
	</div>

	<!-- Sektionen Utseende -->
	<div class="cocookie-settings-section">
		<div class="cocookie-settings-section__header">
			<span class="dashicons dashicons-art"></span>
			<h2><?php esc_html_e( 'Utseende', 'cocookie' ); ?></h2>
		</div>
		<div class="cocookie-settings-section__body">
			<table class="form-table">
				<tr>
					<th><label for="position"><?php esc_html_e( 'Position', 'cocookie' ); ?></label></th>
					<td>
						<select id="position" name="position">
							<option value="bottom" <?php selected( $s['position'], 'bottom' ); ?>><?php esc_html_e( 'Botten', 'cocookie' ); ?></option>
							<option value="top"    <?php selected( $s['position'], 'top' ); ?>><?php esc_html_e( 'Topp', 'cocookie' ); ?></option>
							<option value="center" <?php selected( $s['position'], 'center' ); ?>><?php esc_html_e( 'Center (modal)', 'cocookie' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Var på skärmen bannern visas för besökaren.', 'cocookie' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Färger', 'cocookie' ); ?></th>
					<td>
						<div class="cocookie-color-grid">
							<div class="cocookie-color-pair">
								<label for="banner_bg_color"><?php esc_html_e( 'Bakgrund', 'cocookie' ); ?></label>
								<input type="color" id="banner_bg_color" name="banner_bg_color" value="<?php echo esc_attr( $s['banner_bg_color'] ); ?>">
							</div>
							<div class="cocookie-color-pair">
								<label for="banner_text_color"><?php esc_html_e( 'Text', 'cocookie' ); ?></label>
								<input type="color" id="banner_text_color" name="banner_text_color" value="<?php echo esc_attr( $s['banner_text_color'] ); ?>">
							</div>
							<div class="cocookie-color-pair">
								<label for="primary_color"><?php esc_html_e( 'Acceptera-knapp', 'cocookie' ); ?></label>
								<input type="color" id="primary_color" name="primary_color" value="<?php echo esc_attr( $s['primary_color'] ); ?>">
							</div>
							<div class="cocookie-color-pair">
								<label for="primary_text_color"><?php esc_html_e( 'Acceptera-text', 'cocookie' ); ?></label>
								<input type="color" id="primary_text_color" name="primary_text_color" value="<?php echo esc_attr( $s['primary_text_color'] ); ?>">
							</div>
							<div class="cocookie-color-pair">
								<label for="reject_bg_color"><?php esc_html_e( 'Avvisa-knapp', 'cocookie' ); ?></label>
								<input type="color" id="reject_bg_color" name="reject_bg_color" value="<?php echo esc_attr( $s['reject_bg_color'] ); ?>">
							</div>
							<div class="cocookie-color-pair">
								<label for="reject_text_color"><?php esc_html_e( 'Avvisa-text', 'cocookie' ); ?></label>
								<input type="color" id="reject_text_color" name="reject_text_color" value="<?php echo esc_attr( $s['reject_text_color'] ); ?>">
							</div>
						</div>
					</td>
				</tr>
				<tr>
					<th><label for="logo_url"><?php esc_html_e( 'Logotyp', 'cocookie' ); ?></label></th>
					<td>
						<div class="cocookie-media-field">
							<input type="hidden" id="logo_url" name="logo_url" value="<?php echo esc_attr( $s['logo_url'] ); ?>">
							<button type="button" id="cocookie-upload-logo" class="button">
								<span class="dashicons dashicons-upload" style="vertical-align:middle;font-size:15px;width:15px;height:15px;margin-right:4px;"></span>
								<?php esc_html_e( 'Välj bild', 'cocookie' ); ?>
							</button>
							<button type="button" id="cocookie-remove-logo" class="button" <?php echo empty( $s['logo_url'] ) ? 'style="display:none;"' : ''; ?>>
								<?php esc_html_e( 'Ta bort', 'cocookie' ); ?>
							</button>
							<div id="cocookie-logo-preview" class="cocookie-media-preview">
								<?php if ( ! empty( $s['logo_url'] ) ) : ?>
									<img src="<?php echo esc_url( $s['logo_url'] ); ?>" alt="">
								<?php endif; ?>
							</div>
						</div>
						<p class="description"><?php esc_html_e( 'Visas ovanför titeln i bannern. Rekommenderad höjd: 40px.', 'cocookie' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="cookie_icon"><?php esc_html_e( 'Cookie-ikon', 'cocookie' ); ?></label></th>
					<td>
						<div class="cocookie-media-field">
							<input type="hidden" id="cookie_icon" name="cookie_icon" value="<?php echo esc_attr( $s['cookie_icon'] ); ?>">
							<button type="button" id="cocookie-upload-icon" class="button">
								<span class="dashicons dashicons-upload" style="vertical-align:middle;font-size:15px;width:15px;height:15px;margin-right:4px;"></span>
								<?php esc_html_e( 'Välj ikon', 'cocookie' ); ?>
							</button>
							<button type="button" id="cocookie-remove-icon" class="button" <?php echo empty( $s['cookie_icon'] ) ? 'style="display:none;"' : ''; ?>>
								<?php esc_html_e( 'Ta bort', 'cocookie' ); ?>
							</button>
							<div id="cocookie-icon-preview" class="cocookie-media-preview">
								<?php if ( ! empty( $s['cookie_icon'] ) ) : ?>
									<img src="<?php echo esc_url( $s['cookie_icon'] ); ?>" alt="" style="max-height:40px;">
								<?php endif; ?>
							</div>
						</div>
						<p class="description"><?php esc_html_e( 'Dekorativ ikon som visas i bannern. Rekommenderat: 32×32px SVG.', 'cocookie' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
	</div>

	<!-- Sektionen Övrigt -->
	<div class="cocookie-settings-section">
		<div class="cocookie-settings-section__header">
			<span class="dashicons dashicons-admin-settings"></span>
			<h2><?php esc_html_e( 'Övrigt', 'cocookie' ); ?></h2>
		</div>
		<div class="cocookie-settings-section__body">
			<table class="form-table">
				<tr>
					<th><label for="cookie_lifetime"><?php esc_html_e( 'Cookie-livslängd (dagar)', 'cocookie' ); ?></label></th>
					<td>
						<input type="number" id="cookie_lifetime" name="cookie_lifetime" value="<?php echo esc_attr( $s['cookie_lifetime'] ); ?>" min="1" max="395" class="small-text">
						<p class="description"><?php esc_html_e( 'Hur länge samtycket sparas. Max 395 dagar enligt EDPB-riktlinjerna (~13 månader).', 'cocookie' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
	</div>

	<script>
		( function () {
			// Visa/dölj Google-textfälten beroende på kryssrutans läge
			var toggle = document.getElementById( 'google_consent_enabled' );
			var rows   = document.querySelectorAll( '.cocookie-google-field' );
			if ( ! toggle ) {
				return;
			}
			function sync() {
				rows.forEach( function ( row ) {
					row.style.display = toggle.checked ? '' : 'none';
				} );
			}
			toggle.addEventListener( 'change', sync );
			sync();
		} )();
	</script>

	<?php submit_button( __( 'Spara inställningar', 'cocookie' ), 'primary', 'cocookie_save_banner' ); ?>

</form>

<script>
jQuery(function($){
	// Logotyp-uppladdning
	var logoFrame;
	$('#cocookie-upload-logo').on('click', function(e){
		e.preventDefault();
		if (logoFrame) { logoFrame.open(); return; }
		logoFrame = wp.media({ title: '<?php echo esc_js( __( 'Välj logotyp', 'cocookie' ) ); ?>', button: { text: '<?php echo esc_js( __( 'Använd', 'cocookie' ) ); ?>' }, multiple: false });
		logoFrame.on('select', function(){
			var a = logoFrame.state().get('selection').first().toJSON();
			$('#logo_url').val(a.url);
			$('#cocookie-logo-preview').html('<img src="'+a.url+'" alt="">');
			$('#cocookie-remove-logo').show();
		});
		logoFrame.open();
	});
	$('#cocookie-remove-logo').on('click', function(e){
		e.preventDefault();
		$('#logo_url').val('');
		$('#cocookie-logo-preview').html('');
		$(this).hide();
	});

	// Ikon-uppladdning
	var iconFrame;
	$('#cocookie-upload-icon').on('click', function(e){
		e.preventDefault();
		if (iconFrame) { iconFrame.open(); return; }
		iconFrame = wp.media({ title: '<?php echo esc_js( __( 'Välj ikon', 'cocookie' ) ); ?>', button: { text: '<?php echo esc_js( __( 'Använd', 'cocookie' ) ); ?>' }, multiple: false, library: { type: 'image' } });
		iconFrame.on('select', function(){
			var a = iconFrame.state().get('selection').first().toJSON();
			$('#cookie_icon').val(a.url);
			$('#cocookie-icon-preview').html('<img src="'+a.url+'" alt="" style="max-height:40px;">');
			$('#cocookie-remove-icon').show();
		});
		iconFrame.open();
	});
	$('#cocookie-remove-icon').on('click', function(e){
		e.preventDefault();
		$('#cookie_icon').val('');
		$('#cocookie-icon-preview').html('');
		$(this).hide();
	});
});
</script>

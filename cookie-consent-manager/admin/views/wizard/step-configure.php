<?php
/**
 * Wizard Steg 3: Konfigurera banner
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$defaults = CoCookie_Banner_Controller::get_defaults();
$settings = get_option( 'cocookie_settings', array() );
$s        = wp_parse_args( $settings, $defaults );
?>

<div class="cocookie-wizard__content">

	<!-- Hero-ikon -->
	<div class="cocookie-wizard__icon-hero cocookie-wizard__icon-hero--configure">
		<span class="dashicons dashicons-admin-appearance"></span>
	</div>

	<h2><?php esc_html_e( 'Konfigurera din banner', 'cocookie' ); ?></h2>
	<p class="cocookie-wizard__desc">
		<?php esc_html_e( 'Välj hur din cookie-banner ska se ut och vad den ska säga. Dessa inställningar kan alltid ändras senare.', 'cocookie' ); ?>
	</p>

	<form method="post">
		<?php wp_nonce_field( 'cocookie_wizard_banner' ); ?>

		<!-- Position-val med visuella knappar -->
		<div style="margin-bottom:24px;">
			<label style="display:block;font-size:13px;font-weight:600;color:var(--cocookie-neutral-700);margin-bottom:10px;">
				<?php esc_html_e( 'Bannerposition', 'cocookie' ); ?>
			</label>
			<div style="display:flex;gap:10px;flex-wrap:wrap;">
				<?php
				$positions = array(
					'bottom' => array(
						'label' => __( 'Botten', 'cocookie' ),
						'desc'  => __( 'Vanligast, visas längs nederkanten', 'cocookie' ),
						'icon'  => 'dashicons-arrow-down-alt',
					),
					'top'    => array(
						'label' => __( 'Topp', 'cocookie' ),
						'desc'  => __( 'Visas längs överkanten', 'cocookie' ),
						'icon'  => 'dashicons-arrow-up-alt',
					),
					'center' => array(
						'label' => __( 'Center (modal)', 'cocookie' ),
						'desc'  => __( 'Centrat med bakgrundstäckning', 'cocookie' ),
						'icon'  => 'dashicons-align-center',
					),
				);
				foreach ( $positions as $val => $pos ) : ?>
					<label style="flex:1;min-width:140px;cursor:pointer;">
						<input type="radio" name="position" value="<?php echo esc_attr( $val ); ?>"
							   <?php checked( $s['position'], $val ); ?>
							   style="display:none;"
							   class="cocookie-position-radio">
						<div class="cocookie-position-option" data-value="<?php echo esc_attr( $val ); ?>"
							 style="padding:14px;border:2px solid <?php echo $s['position'] === $val ? 'var(--cocookie-green-500)' : 'var(--cocookie-neutral-200)'; ?>;border-radius:var(--cocookie-radius-md);background:<?php echo $s['position'] === $val ? 'var(--cocookie-green-50)' : '#fff'; ?>;transition:all 0.15s;">
							<span class="dashicons <?php echo esc_attr( $pos['icon'] ); ?>" style="color:<?php echo $s['position'] === $val ? 'var(--cocookie-green-500)' : 'var(--cocookie-neutral-400)'; ?>;font-size:20px;width:20px;height:20px;display:block;margin-bottom:6px;"></span>
							<strong style="display:block;font-size:13px;color:var(--cocookie-neutral-800);margin-bottom:2px;"><?php echo esc_html( $pos['label'] ); ?></strong>
							<span style="font-size:11.5px;color:var(--cocookie-neutral-500);"><?php echo esc_html( $pos['desc'] ); ?></span>
						</div>
					</label>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Primärfärg -->
		<div style="margin-bottom:24px;">
			<label style="display:block;font-size:13px;font-weight:600;color:var(--cocookie-neutral-700);margin-bottom:10px;">
				<?php esc_html_e( 'Primärfärg', 'cocookie' ); ?>
				<span style="font-size:12px;font-weight:400;color:var(--cocookie-neutral-500);margin-left:6px;"><?php esc_html_e( '(acceptera-knappens färg)', 'cocookie' ); ?></span>
			</label>
			<div class="cocookie-color-presets">
				<button type="button" class="cocookie-color-preset <?php echo '#29A166' === $s['primary_color'] ? 'is-selected' : ''; ?>" data-color="#29A166" style="background:#29A166;" title="<?php esc_attr_e( 'CoCookie Grön', 'cocookie' ); ?>"></button>
				<button type="button" class="cocookie-color-preset <?php echo '#2271b1' === $s['primary_color'] ? 'is-selected' : ''; ?>" data-color="#2271b1" style="background:#2271b1;" title="<?php esc_attr_e( 'Blå', 'cocookie' ); ?>"></button>
				<button type="button" class="cocookie-color-preset <?php echo '#7C3AED' === $s['primary_color'] ? 'is-selected' : ''; ?>" data-color="#7C3AED" style="background:#7C3AED;" title="<?php esc_attr_e( 'Lila', 'cocookie' ); ?>"></button>
				<button type="button" class="cocookie-color-preset <?php echo '#DC2626' === $s['primary_color'] ? 'is-selected' : ''; ?>" data-color="#DC2626" style="background:#DC2626;" title="<?php esc_attr_e( 'Röd', 'cocookie' ); ?>"></button>
				<button type="button" class="cocookie-color-preset <?php echo '#1e1e1e' === $s['primary_color'] ? 'is-selected' : ''; ?>" data-color="#1e1e1e" style="background:#1e1e1e;" title="<?php esc_attr_e( 'Svart', 'cocookie' ); ?>"></button>
				<span style="color:var(--cocookie-neutral-400);font-size:13px;"><?php esc_html_e( 'eller välj:', 'cocookie' ); ?></span>
				<input type="color" id="primary_color" name="primary_color" value="<?php echo esc_attr( $s['primary_color'] ); ?>"
					   style="width:40px;height:40px;border-radius:50%;border:2px solid var(--cocookie-neutral-200);padding:2px;cursor:pointer;">
			</div>
		</div>

		<!-- Logotyp -->
		<div style="margin-bottom:24px;">
			<label style="display:block;font-size:13px;font-weight:600;color:var(--cocookie-neutral-700);margin-bottom:10px;">
				<?php esc_html_e( 'Logotyp i bannern', 'cocookie' ); ?>
				<span style="font-size:12px;font-weight:400;color:var(--cocookie-neutral-500);margin-left:6px;"><?php esc_html_e( '(valfritt)', 'cocookie' ); ?></span>
			</label>
			<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
				<input type="hidden" id="logo_url" name="logo_url" value="<?php echo esc_attr( $s['logo_url'] ); ?>">
				<button type="button" id="cocookie-wizard-upload-logo" class="button">
					<span class="dashicons dashicons-upload" style="vertical-align:middle;font-size:15px;width:15px;height:15px;margin-right:4px;"></span>
					<?php esc_html_e( 'Välj logotyp', 'cocookie' ); ?>
				</button>
				<button type="button" id="cocookie-wizard-remove-logo" class="button" <?php echo empty( $s['logo_url'] ) ? 'style="display:none;"' : ''; ?>>
					<?php esc_html_e( 'Ta bort', 'cocookie' ); ?>
				</button>
			</div>
			<div id="cocookie-wizard-logo-preview" style="margin-top:10px;">
				<?php if ( ! empty( $s['logo_url'] ) ) : ?>
					<img src="<?php echo esc_url( $s['logo_url'] ); ?>" alt="" style="max-height:50px;border:1px solid var(--cocookie-neutral-200);padding:6px;border-radius:6px;background:#fff;">
				<?php endif; ?>
			</div>
			<p style="font-size:12px;color:var(--cocookie-neutral-500);margin-top:6px;">
				<?php esc_html_e( 'Visas ovanför titeln i bannern. Rekommenderad höjd: 40px.', 'cocookie' ); ?>
			</p>
		</div>

		<!-- Texter -->
		<div style="background:var(--cocookie-neutral-50);border:1px solid var(--cocookie-neutral-200);border-radius:var(--cocookie-radius-md);padding:16px 20px;margin-bottom:24px;">
			<p style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--cocookie-neutral-500);margin:0 0 16px;"><?php esc_html_e( 'Texter', 'cocookie' ); ?></p>

			<div style="margin-bottom:14px;">
				<label for="banner_title" style="display:block;font-size:13px;font-weight:500;color:var(--cocookie-neutral-700);margin-bottom:5px;"><?php esc_html_e( 'Rubrik', 'cocookie' ); ?></label>
				<input type="text" id="banner_title" name="banner_title" value="<?php echo esc_attr( $s['banner_title'] ); ?>" class="large-text">
			</div>

			<div style="margin-bottom:14px;">
				<label for="banner_text" style="display:block;font-size:13px;font-weight:500;color:var(--cocookie-neutral-700);margin-bottom:5px;"><?php esc_html_e( 'Brödtext', 'cocookie' ); ?></label>
				<textarea id="banner_text" name="banner_text" rows="2" class="large-text"><?php echo esc_textarea( $s['banner_text'] ); ?></textarea>
			</div>

			<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
				<div>
					<label for="accept_all_text" style="display:block;font-size:13px;font-weight:500;color:var(--cocookie-neutral-700);margin-bottom:5px;"><?php esc_html_e( 'Acceptera-knapp', 'cocookie' ); ?></label>
					<input type="text" id="accept_all_text" name="accept_all_text" value="<?php echo esc_attr( $s['accept_all_text'] ); ?>" class="widefat">
				</div>
				<div>
					<label for="reject_all_text" style="display:block;font-size:13px;font-weight:500;color:var(--cocookie-neutral-700);margin-bottom:5px;"><?php esc_html_e( 'Avvisa-knapp', 'cocookie' ); ?></label>
					<input type="text" id="reject_all_text" name="reject_all_text" value="<?php echo esc_attr( $s['reject_all_text'] ); ?>" class="widefat">
				</div>
			</div>
		</div>

		<input type="hidden" name="save_text" value="<?php echo esc_attr( $s['save_text'] ); ?>">
		<input type="hidden" name="settings_text" value="<?php echo esc_attr( $s['settings_text'] ); ?>">

		<div class="cocookie-wizard__nav">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cocookie-wizard&step=2' ) ); ?>" class="button">
				&larr; <?php esc_html_e( 'Tillbaka', 'cocookie' ); ?>
			</a>
			<?php submit_button( __( 'Spara och fortsätt', 'cocookie' ) . ' &rarr;', 'primary', 'cocookie_wizard_save_banner', false ); ?>
		</div>

	</form>

</div>

<script>
(function(){
	// Färgförinställningar — uppdatera color input och markering
	document.querySelectorAll('.cocookie-color-preset').forEach(function(btn){
		btn.addEventListener('click', function(){
			document.getElementById('primary_color').value = this.dataset.color;
			document.querySelectorAll('.cocookie-color-preset').forEach(function(b){ b.classList.remove('is-selected'); });
			this.classList.add('is-selected');
		});
	});

	// Positions-radioknapp — visuell markering
	document.querySelectorAll('.cocookie-position-radio').forEach(function(radio){
		radio.addEventListener('change', function(){
			document.querySelectorAll('.cocookie-position-option').forEach(function(el){
				var isActive = el.dataset.value === radio.value;
				el.style.borderColor  = isActive ? 'var(--cocookie-green-500)' : 'var(--cocookie-neutral-200)';
				el.style.background   = isActive ? 'var(--cocookie-green-50)' : '#fff';
				var icon = el.querySelector('.dashicons');
				if (icon) {
					icon.style.color = isActive ? 'var(--cocookie-green-500)' : 'var(--cocookie-neutral-400)';
				}
			});
		});
	});

	// Klick på position-option aktiverar rätt radio
	document.querySelectorAll('.cocookie-position-option').forEach(function(el){
		el.addEventListener('click', function(){
			var val = this.dataset.value;
			var radio = document.querySelector('input[name="position"][value="' + val + '"]');
			if (radio) {
				radio.checked = true;
				radio.dispatchEvent(new Event('change'));
			}
		});
	});

	// Logotyp-uppladdning via WordPress mediabiblioteket (jQuery-baserat)
	jQuery(function($){
		var logoFrame;

		$('#cocookie-wizard-upload-logo').on('click', function(e){
			e.preventDefault();

			if (typeof wp === 'undefined' || !wp.media) {
				alert('Mediabiblioteket kunde inte laddas. Försök ladda om sidan.');
				return;
			}

			if (logoFrame) { logoFrame.open(); return; }

			logoFrame = wp.media({
				title: '<?php echo esc_js( __( 'Välj logotyp', 'cocookie' ) ); ?>',
				button: { text: '<?php echo esc_js( __( 'Använd denna bild', 'cocookie' ) ); ?>' },
				multiple: false,
				library: { type: 'image' }
			});

			logoFrame.on('select', function(){
				var attachment = logoFrame.state().get('selection').first().toJSON();
				$('#logo_url').val(attachment.url);
				$('#cocookie-wizard-logo-preview').html('<img src="' + attachment.url + '" alt="" style="max-height:50px;border:1px solid #e5e7eb;padding:6px;border-radius:6px;background:#fff;">');
				$('#cocookie-wizard-remove-logo').show();
			});

			logoFrame.open();
		});

		$('#cocookie-wizard-remove-logo').on('click', function(e){
			e.preventDefault();
			$('#logo_url').val('');
			$('#cocookie-wizard-logo-preview').html('');
			$(this).hide();
		});
	});
})();
</script>

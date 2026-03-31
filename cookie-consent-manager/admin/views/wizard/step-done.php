<?php
/**
 * Wizard Steg 4: Klar!
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$registered = CoCookie_Categories::get_cookies();
$categories = CoCookie_Categories::get_all();
$settings   = get_option( 'cocookie_settings', array() );

$position_labels = array(
	'bottom' => __( 'Botten', 'cocookie' ),
	'top'    => __( 'Topp', 'cocookie' ),
	'center' => __( 'Modal', 'cocookie' ),
);
$position_label = $position_labels[ $settings['position'] ?? 'bottom' ] ?? ucfirst( $settings['position'] ?? 'bottom' );
?>

<div class="cocookie-wizard__content cocookie-wizard__content--centered">

	<!-- Framgångsikon med animation -->
	<div class="cocookie-wizard__success-icon">
		<svg viewBox="0 0 96 96" width="96" height="96" aria-hidden="true">
			<defs>
				<radialGradient id="cc-success-grad" cx="50%" cy="50%" r="50%">
					<stop offset="0%" stop-color="#3DB87A"/>
					<stop offset="100%" stop-color="#29A166"/>
				</radialGradient>
			</defs>
			<circle cx="48" cy="48" r="46" fill="url(#cc-success-grad)" opacity="0.12"/>
			<circle cx="48" cy="48" r="38" fill="none" stroke="#29A166" stroke-width="2.5" stroke-dasharray="6 3" opacity="0.4"/>
			<circle cx="48" cy="48" r="30" fill="url(#cc-success-grad)"/>
			<path d="M33 48 L43 58 L63 36" fill="none" stroke="#fff" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>
	</div>

	<h2><?php esc_html_e( 'CoCookie är redo!', 'cocookie' ); ?></h2>
	<p class="cocookie-wizard__desc">
		<?php esc_html_e( 'Din cookie consent-banner är nu konfigurerad och aktiv på din webbplats. Besökare kommer att se bannern och kunna hantera sina samtycken.', 'cocookie' ); ?>
	</p>

	<!-- Sammanfattnings-statistik -->
	<div class="cocookie-wizard__summary">
		<div class="cocookie-wizard__summary-item">
			<strong><?php echo esc_html( count( $registered ) ); ?></strong>
			<span><?php esc_html_e( 'registrerade cookies', 'cocookie' ); ?></span>
		</div>
		<div class="cocookie-wizard__summary-item">
			<strong><?php echo esc_html( count( $categories ) ); ?></strong>
			<span><?php esc_html_e( 'kategorier', 'cocookie' ); ?></span>
		</div>
		<div class="cocookie-wizard__summary-item">
			<strong><?php echo esc_html( $position_label ); ?></strong>
			<span><?php esc_html_e( 'bannerposition', 'cocookie' ); ?></span>
		</div>
	</div>

	<!-- Nästa steg -->
	<div style="background:var(--cocookie-neutral-50);border:1px solid var(--cocookie-neutral-200);border-radius:var(--cocookie-radius-md);padding:20px;margin-bottom:28px;text-align:left;">
		<p style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--cocookie-neutral-500);margin:0 0 12px;"><?php esc_html_e( 'Rekommenderade nästa steg', 'cocookie' ); ?></p>
		<ul style="list-style:none;margin:0;padding:0;">
			<li style="display:flex;align-items:center;gap:10px;padding:8px 0;font-size:13px;color:var(--cocookie-neutral-700);border-bottom:1px solid var(--cocookie-neutral-200);">
				<span class="dashicons dashicons-media-document" style="color:var(--cocookie-green-500);font-size:16px;width:16px;height:16px;flex-shrink:0;"></span>
				<span><strong><?php esc_html_e( 'Generera policydokument', 'cocookie' ); ?></strong> — <a href="<?php echo esc_url( admin_url( 'admin.php?page=cocookie-policies' ) ); ?>" style="color:var(--cocookie-green-500);"><?php esc_html_e( 'Gå till Policyer', 'cocookie' ); ?></a></span>
			</li>
			<li style="display:flex;align-items:center;gap:10px;padding:8px 0;font-size:13px;color:var(--cocookie-neutral-700);border-bottom:1px solid var(--cocookie-neutral-200);">
				<span class="dashicons dashicons-admin-appearance" style="color:var(--cocookie-green-500);font-size:16px;width:16px;height:16px;flex-shrink:0;"></span>
				<span><strong><?php esc_html_e( 'Anpassa bannern mer', 'cocookie' ); ?></strong> — <a href="<?php echo esc_url( admin_url( 'admin.php?page=cocookie-banner' ) ); ?>" style="color:var(--cocookie-green-500);"><?php esc_html_e( 'Gå till Banner-inställningar', 'cocookie' ); ?></a></span>
			</li>
			<li style="display:flex;align-items:center;gap:10px;padding:8px 0;font-size:13px;color:var(--cocookie-neutral-700);">
				<span class="dashicons dashicons-search" style="color:var(--cocookie-green-500);font-size:16px;width:16px;height:16px;flex-shrink:0;"></span>
				<span><strong><?php esc_html_e( 'Kör regelbundna skanningar', 'cocookie' ); ?></strong> — <?php esc_html_e( 'Håll registret uppdaterat när du lägger till nya tjänster.', 'cocookie' ); ?></span>
			</li>
		</ul>
	</div>

	<form method="post">
		<?php wp_nonce_field( 'cocookie_wizard_complete' ); ?>
		<button type="submit" name="cocookie_wizard_complete" class="button button-primary button-hero">
			<span class="dashicons dashicons-dashboard" style="font-size:18px;width:18px;height:18px;vertical-align:middle;margin-right:6px;margin-top:-2px;"></span>
			<?php esc_html_e( 'Gå till Dashboard', 'cocookie' ); ?>
		</button>
	</form>

	<p class="cocookie-wizard__tip">
		<span class="dashicons dashicons-lightbulb" style="font-size:14px;width:14px;height:14px;color:var(--cocookie-amber-500);"></span>
		<?php esc_html_e( 'Tips: Kör regelbundna cookie-skanningar för att hålla ditt register uppdaterat när du lägger till nya plugins eller tredjepartstjänster.', 'cocookie' ); ?>
	</p>

</div>

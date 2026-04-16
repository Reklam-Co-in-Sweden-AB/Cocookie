<?php
/**
 * Cookie consent banner template.
 *
 * Rendered server-side via wp_footer. The JS only handles
 * show/hide, toggles, and consent submission.
 *
 * @package CoCookie
 * @var array $config Banner configuration from CoCookie_REST_Config::build_config().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$s          = $config['settings'];
$categories = $config['categories'];
$position   = $s['position'] ?? 'bottom';
?>

<div id="cocookie-banner"
	class="cocookie-banner cocookie-banner--<?php echo esc_attr( $position ); ?>"
	role="dialog"
	aria-label="<?php echo esc_attr( $s['banner_title'] ); ?>"
	aria-modal="<?php echo 'center' === $position ? 'true' : 'false'; ?>"
	style="visibility:hidden; opacity:0; pointer-events:none;
		--cocookie-bg: <?php echo esc_attr( $s['banner_bg_color'] ); ?>;
		--cocookie-text: <?php echo esc_attr( $s['banner_text_color'] ); ?>;
		--cocookie-accent: <?php echo esc_attr( $s['primary_color'] ); ?>;
		--cocookie-accent-text: <?php echo esc_attr( $s['primary_text_color'] ); ?>;
		--cocookie-reject-bg: <?php echo esc_attr( $s['reject_bg_color'] ); ?>;
		--cocookie-reject-text: <?php echo esc_attr( $s['reject_text_color'] ); ?>;">

	<?php if ( 'center' === $position ) : ?>
		<div class="cocookie-banner__overlay" data-cocookie-close></div>
	<?php endif; ?>

	<div class="cocookie-banner__inner">

		<?php if ( ! empty( $s['logo_url'] ) ) : ?>
			<img class="cocookie-banner__logo" src="<?php echo esc_url( $s['logo_url'] ); ?>" alt="" loading="lazy">
		<?php endif; ?>

		<!-- Fliknavigering -->
		<div class="cocookie-banner__tabs" id="cocookie-tabs">
			<button type="button" class="cocookie-banner__tab cocookie-banner__tab--active" data-cocookie-tab="consent">
				<?php echo esc_html( $s['tab_consent'] ?? __( 'Samtycke', 'cocookie' ) ); ?>
			</button>
			<button type="button" class="cocookie-banner__tab" data-cocookie-tab="details">
				<?php echo esc_html( $s['tab_details'] ?? __( 'Detaljer', 'cocookie' ) ); ?>
			</button>
			<button type="button" class="cocookie-banner__tab" data-cocookie-tab="about">
				<?php echo esc_html( $s['tab_about'] ?? __( 'Om cookies', 'cocookie' ) ); ?>
			</button>
		</div>

		<!-- FLIK: Samtycke -->
		<div class="cocookie-banner__panel cocookie-banner__panel--active" id="cocookie-panel-consent" data-cocookie-panel="consent">

			<h2 class="cocookie-banner__title" id="cocookie-banner-title">
				<?php echo esc_html( $s['banner_title'] ); ?>
			</h2>

			<p class="cocookie-banner__text" id="cocookie-banner-text">
				<?php echo esc_html( $s['banner_text'] ); ?>
				<?php if ( ! empty( $s['privacy_policy_url'] ) ) : ?>
					<a href="<?php echo esc_url( $s['privacy_policy_url'] ); ?>" class="cocookie-banner__policy-link" target="_blank" rel="noopener">
						<?php echo esc_html( $s['policy_link_text'] ); ?>
					</a>
				<?php endif; ?>
			</p>

			<!-- Consent info (visas vid återöppning) -->
			<div class="cocookie-banner__consent-info" id="cocookie-consent-info" style="display:none;">
				<p><strong><?php echo esc_html( $s['consent_date_label'] ); ?></strong> <span id="cocookie-consent-date"></span></p>
				<p><strong><?php echo esc_html( $s['consent_id_label'] ); ?></strong> <span id="cocookie-consent-id"></span></p>
			</div>

			<!-- DNT notice -->
			<div class="cocookie-banner__dnt" id="cocookie-dnt-notice" style="display:none;">
				<?php echo esc_html( $s['dnt_notice'] ); ?>
			</div>

		</div>

		<!-- FLIK: Detaljer (kategori-inställningar) -->
		<div class="cocookie-banner__panel" id="cocookie-panel-details" data-cocookie-panel="details" style="display:none;">
			<div class="cocookie-banner__details" id="cocookie-details">
			<?php foreach ( $categories as $cat ) : ?>
				<div class="cocookie-category" data-slug="<?php echo esc_attr( $cat['slug'] ); ?>">
					<div class="cocookie-category__header">
						<label class="cocookie-category__label">
							<span class="cocookie-toggle">
								<input type="checkbox"
									class="cocookie-toggle__input"
									data-cocookie-category="<?php echo esc_attr( $cat['slug'] ); ?>"
									<?php echo $cat['is_required'] ? 'checked disabled' : ''; ?>>
								<span class="cocookie-toggle__slider"></span>
							</span>
							<span class="cocookie-category__name">
								<?php echo esc_html( $cat['title'] ); ?>
								<?php if ( $cat['is_required'] ) : ?>
									<span class="cocookie-category__required"><?php echo esc_html( $s['required_label'] ); ?></span>
								<?php endif; ?>
							</span>
						</label>
						<?php if ( ! empty( $cat['cookies'] ) ) : ?>
							<button type="button" class="cocookie-category__expand" aria-expanded="false">
								<span class="cocookie-category__arrow">&#9654;</span>
								<span class="cocookie-category__count"><?php echo count( $cat['cookies'] ); ?> cookies</span>
							</button>
						<?php endif; ?>
					</div>

					<div class="cocookie-category__body" style="display:none;">
						<p class="cocookie-category__desc"><?php echo esc_html( $cat['description'] ); ?></p>
						<?php if ( ! empty( $cat['cookies'] ) ) : ?>
							<table class="cocookie-category__table">
								<thead>
									<tr>
										<th><?php echo esc_html( $s['table_cookie'] ?? __( 'Cookie', 'cocookie' ) ); ?></th>
										<th><?php echo esc_html( $s['table_provider'] ?? __( 'Leverantör', 'cocookie' ) ); ?></th>
										<th><?php echo esc_html( $s['table_purpose'] ?? __( 'Syfte', 'cocookie' ) ); ?></th>
										<th><?php echo esc_html( $s['table_expiry'] ?? __( 'Livslängd', 'cocookie' ) ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $cat['cookies'] as $cookie ) : ?>
										<tr>
											<td><code><?php echo esc_html( $cookie['name'] ); ?></code></td>
											<td><?php echo esc_html( $cookie['provider'] ); ?></td>
											<td><?php echo esc_html( $cookie['purpose'] ); ?></td>
											<td><?php echo esc_html( $cookie['expiry'] ?: '—' ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		</div>

		<!-- FLIK: Om cookies -->
		<div class="cocookie-banner__panel" id="cocookie-panel-about" data-cocookie-panel="about" style="display:none;">
			<div class="cocookie-banner__about">
				<h3 class="cocookie-banner__about-title">
					<?php echo esc_html( $s['about_title'] ?? __( 'Vad är cookies?', 'cocookie' ) ); ?>
				</h3>
				<p><?php echo esc_html( $s['about_what'] ?? __( 'Cookies är små textfiler som lagras på din enhet (dator, telefon eller surfplatta) när du besöker en webbplats. De används för att webbplatsen ska fungera korrekt, för att analysera trafik och för att anpassa innehåll och annonser.', 'cocookie' ) ); ?></p>

				<h3 class="cocookie-banner__about-title">
					<?php echo esc_html( $s['about_types_title'] ?? __( 'Typer av cookies', 'cocookie' ) ); ?>
				</h3>
				<div class="cocookie-banner__about-types">
					<div class="cocookie-banner__about-type">
						<strong><?php echo esc_html( $s['about_type_necessary'] ?? __( 'Nödvändiga cookies', 'cocookie' ) ); ?></strong>
						<p><?php echo esc_html( $s['about_type_necessary_desc'] ?? __( 'Dessa cookies krävs för att webbplatsen ska fungera och kan inte stängas av. De sätts vanligtvis som svar på åtgärder du gör, som att logga in eller fylla i formulär.', 'cocookie' ) ); ?></p>
					</div>
					<div class="cocookie-banner__about-type">
						<strong><?php echo esc_html( $s['about_type_analytics'] ?? __( 'Analyticscookies', 'cocookie' ) ); ?></strong>
						<p><?php echo esc_html( $s['about_type_analytics_desc'] ?? __( 'Dessa cookies låter oss räkna besök och trafikkällor så att vi kan mäta och förbättra webbplatsens prestanda. De hjälper oss att veta vilka sidor som är mest och minst populära.', 'cocookie' ) ); ?></p>
					</div>
					<div class="cocookie-banner__about-type">
						<strong><?php echo esc_html( $s['about_type_marketing'] ?? __( 'Marknadsföringscookies', 'cocookie' ) ); ?></strong>
						<p><?php echo esc_html( $s['about_type_marketing_desc'] ?? __( 'Dessa cookies kan sättas via vår webbplats av våra annonspartners. De kan användas för att bygga en profil om dina intressen och visa dig relevanta annonser på andra webbplatser.', 'cocookie' ) ); ?></p>
					</div>
				</div>

				<h3 class="cocookie-banner__about-title">
					<?php echo esc_html( $s['about_manage_title'] ?? __( 'Hantera dina cookies', 'cocookie' ) ); ?>
				</h3>
				<p><?php echo esc_html( $s['about_manage_desc'] ?? __( 'Du kan när som helst ändra eller återkalla ditt samtycke genom att klicka på cookie-ikonen i nedre vänstra hörnet. Du kan också radera cookies i din webbläsares inställningar.', 'cocookie' ) ); ?></p>

				<h3 class="cocookie-banner__about-title">
					<?php echo esc_html( $s['about_rights_title'] ?? __( 'Dina rättigheter', 'cocookie' ) ); ?>
				</h3>
				<p><?php echo esc_html( $s['about_rights_desc'] ?? __( 'Enligt GDPR har du rätt att få tillgång till, korrigera eller radera dina personuppgifter. Du har också rätt att invända mot behandling och att begära dataportabilitet. Läs mer i vår integritetspolicy.', 'cocookie' ) ); ?></p>
			</div>
		</div>

		<!-- Buttons -->
		<div class="cocookie-banner__buttons">
			<button type="button" class="cocookie-btn cocookie-btn--accept" id="cocookie-accept-all">
				<?php echo esc_html( $s['accept_all_text'] ); ?>
			</button>
			<button type="button" class="cocookie-btn cocookie-btn--reject" id="cocookie-reject-all">
				<?php echo esc_html( $s['reject_all_text'] ); ?>
			</button>
			<button type="button" class="cocookie-btn cocookie-btn--settings" id="cocookie-toggle-details">
				<?php echo esc_html( $s['settings_text'] ); ?>
			</button>
			<button type="button" class="cocookie-btn cocookie-btn--save" id="cocookie-save" style="display:none;">
				<?php echo esc_html( $s['save_text'] ); ?>
			</button>
			<button type="button" class="cocookie-btn cocookie-btn--reject" id="cocookie-withdraw" style="display:none;">
				<?php echo esc_html( $s['withdraw_text'] ); ?>
			</button>
		</div>
	</div>
</div>

<!-- Floating settings button -->
<button type="button" id="cocookie-float-btn" class="cocookie-float" style="display:none;" aria-label="<?php esc_attr_e( 'Cookie-inställningar', 'cocookie' ); ?>">
	<?php if ( ! empty( $s['cookie_icon'] ) ) : ?>
		<img src="<?php echo esc_url( $s['cookie_icon'] ); ?>" alt="" width="24" height="24">
	<?php else : ?>
		&#127850;
	<?php endif; ?>
</button>

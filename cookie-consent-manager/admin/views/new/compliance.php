<?php
/**
 * Compliance-vy — samtycken, skanner och audit i sub-flikar.
 *
 * @package CoCookie
 * @var array $data Controller data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tab  = $data['tab'];
$tabs = $data['tabs'];

// Ikon per flik
$tab_icons = array(
	'consents' => 'dashicons-id-alt',
	'scanner'  => 'dashicons-search',
	'audit'    => 'dashicons-yes-alt',
);
?>

<!-- Sidhuvud -->
<div class="cocookie-page-header">
	<div class="cocookie-page-header__title">
		<div class="cocookie-page-header__icon">
			<span class="dashicons dashicons-shield-alt"></span>
		</div>
		<div class="cocookie-page-header__text">
			<h1><?php esc_html_e( 'Compliance', 'cocookie' ); ?></h1>
			<p><?php esc_html_e( 'Samtyckes-logg, cookie-skanner och audit-verktyg.', 'cocookie' ); ?></p>
		</div>
	</div>
</div>

<nav class="nav-tab-wrapper cocookie-subtabs">
	<?php foreach ( $tabs as $slug => $label ) : ?>
		<?php $active = ( $tab === $slug ) ? ' nav-tab-active' : ''; ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=cocookie-compliance&tab=' . $slug ) ); ?>" class="nav-tab<?php echo $active; ?>">
			<?php if ( isset( $tab_icons[ $slug ] ) ) : ?>
				<span class="dashicons <?php echo esc_attr( $tab_icons[ $slug ] ); ?>" style="font-size:14px;width:14px;height:14px;vertical-align:middle;margin-right:5px;"></span>
			<?php endif; ?>
			<?php echo esc_html( $label ); ?>
		</a>
	<?php endforeach; ?>
</nav>

<div class="cocookie-tab-content">
<?php
switch ( $tab ) :

	/* ================================================================
	 * Flik: Scanner
	 * ================================================================ */
	case 'scanner':
		?>
		<div class="cocookie-card" style="margin-bottom:20px;">
			<div class="cocookie-card__header">
				<h3 class="cocookie-card__title">
					<span class="dashicons dashicons-search"></span>
					<?php esc_html_e( 'Cookie-scanner', 'cocookie' ); ?>
				</h3>
			</div>
			<p style="font-size:13px;color:var(--cocookie-neutral-500);margin-bottom:20px;line-height:1.6;">
				<?php esc_html_e( 'Skannern besöker din webbplats och identifierar alla cookies som sätts i besökarens webbläsare. Resultaten kan sedan importeras direkt till ditt cookie-register.', 'cocookie' ); ?>
			</p>

			<div class="cocookie-scanner-actions">
				<button type="button" id="cocookie-start-scan" class="button button-primary">
					<span class="dashicons dashicons-search" style="font-size:15px;width:15px;height:15px;vertical-align:middle;margin-right:4px;"></span>
					<?php esc_html_e( 'Starta skanning', 'cocookie' ); ?>
				</button>
				<?php if ( ! empty( $data['last_scan'] ) ) : ?>
					<span class="cocookie-meta" style="margin-top:0;">
						<span class="dashicons dashicons-clock"></span>
						<?php printf( esc_html__( 'Senaste skanning: %s', 'cocookie' ), esc_html( $data['last_scan'] ) ); ?>
					</span>
				<?php endif; ?>
			</div>

			<div id="cocookie-scan-status" style="display:none;" class="cocookie-status-bar">
				<span class="spinner is-active"></span>
				<span id="cocookie-scan-status-text"><?php esc_html_e( 'Skannar din webbplats...', 'cocookie' ); ?></span>
			</div>
		</div>

		<?php
		// Varningar som skannern visar när analytics-cookies saknas.
		// Rutorna är dolda tills JavaScript avgjort vilken som gäller.
		?>
		<div id="cocookie-scan-hint-ga_logged_in" class="cocookie-notice cocookie-notice--warning" style="display:none;">
			<span class="dashicons dashicons-warning"></span>
			<p>
				<strong><?php esc_html_e( 'Google Analytics hittades inte, men finns på sajten.', 'cocookie' ); ?></strong><br>
				<?php esc_html_e( 'Skanningen körs i din inloggade webbläsare, och ditt analytics-plugin undantar inloggade administratörer från spårning. Besökare får cookien ändå. Site Kit hanteras automatiskt av CoCookie; för andra plugins (MonsterInsights, GA4WP m.fl.) stänger du tillfälligt av undantaget i pluginens inställningar och skannar igen, eller lägger till _ga och _ga_* manuellt under Cookies.', 'cocookie' ); ?>
			</p>
		</div>
		<div id="cocookie-scan-hint-ga_blocked" class="cocookie-notice cocookie-notice--warning" style="display:none;">
			<span class="dashicons dashicons-warning"></span>
			<p>
				<strong><?php esc_html_e( 'Google Analytics laddades men satte inga cookies.', 'cocookie' ); ?></strong><br>
				<?php esc_html_e( 'Vanliga orsaker: ett cache-plugin fördröjer JavaScript tills besökaren rör musen (WP Rocket, Perfmatters, FlyingPress), en GTM-container väntar på samtycke via en egen consent-mall, eller en adblocker i din webbläsare. Stäng tillfälligt av fördröjningen respektive adblockern och skanna igen. Du kan också lägga till _ga och _ga_* manuellt under Cookies.', 'cocookie' ); ?>
			</p>
		</div>
		<div id="cocookie-scan-hint-cross_origin" class="cocookie-notice cocookie-notice--error" style="display:none;">
			<span class="dashicons dashicons-dismiss"></span>
			<p>
				<strong><?php esc_html_e( 'Skannern kunde inte läsa webbplatsen.', 'cocookie' ); ?></strong><br>
				<?php esc_html_e( 'Webbplatsadressen och adminadressen skiljer sig åt (t.ex. www mot utan www, eller http mot https), så endast adminsidans egna cookies kunde läsas. Kontrollera att Webbplatsadress och WordPress-adress under Inställningar → Allmänt har samma domän och protokoll.', 'cocookie' ); ?>
			</p>
		</div>

		<div id="cocookie-scan-results"></div>

		<div id="cocookie-scan-toolbar" style="display:none;">
			<span id="cocookie-scan-count" style="font-size:13px;font-weight:500;color:var(--cocookie-neutral-700);"></span>
			<button type="button" id="cocookie-import-all" class="button button-secondary">
				<span class="dashicons dashicons-download" style="font-size:15px;width:15px;height:15px;vertical-align:middle;margin-right:4px;"></span>
				<?php esc_html_e( 'Importera alla', 'cocookie' ); ?>
			</button>
		</div>

		<div class="cocookie-card" style="padding:0;overflow:hidden;">
			<table id="cocookie-scan-table" class="cocookie-table" style="display:none;width:100%;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Namn', 'cocookie' ); ?></th>
						<th><?php esc_html_e( 'Typ', 'cocookie' ); ?></th>
						<th><?php esc_html_e( 'Kategori', 'cocookie' ); ?></th>
						<th><?php esc_html_e( 'Leverantör', 'cocookie' ); ?></th>
						<th><?php esc_html_e( 'Syfte', 'cocookie' ); ?></th>
						<th><?php esc_html_e( 'Status', 'cocookie' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody id="cocookie-scan-tbody"></tbody>
			</table>
		</div>

		<?php
		// Ignorerade cookies — sådana som bara sätts för inloggade admins
		// och som inte ska dyka upp vid nästa skanning.
		$ignored = $data['ignored'];
		?>
		<div class="cocookie-card" id="cocookie-ignored-card" style="margin-top:20px;<?php echo empty( $ignored ) ? 'display:none;' : ''; ?>">
			<div class="cocookie-card__header">
				<h3 class="cocookie-card__title">
					<span class="dashicons dashicons-hidden"></span>
					<?php esc_html_e( 'Ignorerade cookies', 'cocookie' ); ?>
				</h3>
				<span class="cocookie-badge cocookie-badge--muted" id="cocookie-ignored-count"><?php echo count( $ignored ); ?></span>
			</div>
			<p style="font-size:13px;color:var(--cocookie-neutral-500);margin-bottom:16px;">
				<?php esc_html_e( 'Dessa cookies visas inte i skanningar och finns inte i registret. Vanligtvis inloggnings- eller adminverktygscookies som bara du får. Klicka "Återställ" för att ta med dem igen.', 'cocookie' ); ?>
			</p>
			<ul class="cocookie-blocked-list" id="cocookie-ignored-list">
				<?php foreach ( $ignored as $name ) : ?>
					<li>
						<span class="dashicons dashicons-hidden"></span>
						<code><?php echo esc_html( $name ); ?></code>
						<button type="button" class="button button-small cocookie-unignore-btn" data-name="<?php echo esc_attr( $name ); ?>">
							<?php esc_html_e( 'Återställ', 'cocookie' ); ?>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
		break;

	/* ================================================================
	 * Flik: Audit
	 * ================================================================ */
	case 'audit':
		$blocked = $data['blocked'];
		?>
		<div class="cocookie-card" style="margin-bottom:20px;">
			<div class="cocookie-card__header">
				<h3 class="cocookie-card__title">
					<span class="dashicons dashicons-yes-alt"></span>
					<?php esc_html_e( 'Cookie-audit', 'cocookie' ); ?>
				</h3>
			</div>
			<p style="font-size:13px;color:var(--cocookie-neutral-500);margin-bottom:20px;line-height:1.6;">
				<?php esc_html_e( 'Auditverktyget jämför cookies som faktiskt sätts på din webbplats mot ditt register. Ookategoriserade eller blockrerade cookies markeras för åtgärd.', 'cocookie' ); ?>
			</p>

			<div class="cocookie-audit-actions">
				<button type="button" id="cocookie-start-audit" class="button button-primary">
					<span class="dashicons dashicons-yes-alt" style="font-size:15px;width:15px;height:15px;vertical-align:middle;margin-right:4px;"></span>
					<?php esc_html_e( 'Kör audit', 'cocookie' ); ?>
				</button>
			</div>

			<div id="cocookie-audit-status" style="display:none;" class="cocookie-status-bar">
				<span class="spinner is-active"></span>
				<span id="cocookie-audit-status-text"><?php esc_html_e( 'Kör audit...', 'cocookie' ); ?></span>
			</div>
		</div>

		<div id="cocookie-audit-results"></div>

		<?php if ( ! empty( $blocked ) ) : ?>
			<div class="cocookie-card">
				<div class="cocookie-card__header">
					<h3 class="cocookie-card__title">
						<span class="dashicons dashicons-lock"></span>
						<?php esc_html_e( 'Blockerade cookies', 'cocookie' ); ?>
					</h3>
					<span class="cocookie-badge cocookie-badge--error"><?php echo count( $blocked ); ?></span>
				</div>
				<p style="font-size:13px;color:var(--cocookie-neutral-500);margin-bottom:16px;">
					<?php esc_html_e( 'Dessa cookies är aktivt blockerade. Klicka "Avblockera" för att tillåta dem igen.', 'cocookie' ); ?>
				</p>
				<ul class="cocookie-blocked-list" id="cocookie-blocked-list">
					<?php foreach ( $blocked as $name ) : ?>
						<li>
							<span class="dashicons dashicons-dismiss" style="color:var(--cocookie-red-500);font-size:16px;width:16px;height:16px;"></span>
							<code><?php echo esc_html( $name ); ?></code>
							<button type="button" class="button button-small cocookie-unblock-btn" data-name="<?php echo esc_attr( $name ); ?>">
								<?php esc_html_e( 'Avblockera', 'cocookie' ); ?>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		<?php
		break;

	/* ================================================================
	 * Flik: Samtyckes-logg (standard)
	 * ================================================================ */
	default:
		$consents = $data['consents'];
		?>
		<div class="cocookie-card" style="margin-bottom:20px;">
			<div class="cocookie-card__header">
				<h3 class="cocookie-card__title">
					<span class="dashicons dashicons-id-alt"></span>
					<?php esc_html_e( 'Samtyckes-logg', 'cocookie' ); ?>
				</h3>
				<?php if ( ! empty( $consents['items'] ) ) : ?>
					<span class="cocookie-badge cocookie-badge--success"><?php echo esc_html( number_format_i18n( $consents['total'] ?? count( $consents['items'] ) ) ); ?> <?php esc_html_e( 'samtycken', 'cocookie' ); ?></span>
				<?php endif; ?>
			</div>
			<p style="font-size:13px;color:var(--cocookie-neutral-500);margin-bottom:0;line-height:1.6;">
				<?php esc_html_e( 'Loggen visar alla registrerade samtyckesbeslut. IP-adresser lagras hashade för GDPR-compliance.', 'cocookie' ); ?>
			</p>
		</div>

		<?php if ( empty( $consents['items'] ) ) : ?>
			<div class="cocookie-card">
				<div class="cocookie-empty">
					<div class="cocookie-empty__icon">
						<span class="dashicons dashicons-id-alt"></span>
					</div>
					<p class="cocookie-empty__title"><?php esc_html_e( 'Inga samtycken ännu', 'cocookie' ); ?></p>
					<p class="cocookie-empty__desc"><?php esc_html_e( 'Samtycken loggas när besökare interagerar med din cookie-banner. Kontrollera att bannern är aktiv på webbplatsen.', 'cocookie' ); ?></p>
				</div>
			</div>
		<?php else : ?>
			<div class="cocookie-card" style="padding:0;overflow:hidden;">
				<table class="cocookie-table" style="width:100%;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Samtyckes-ID', 'cocookie' ); ?></th>
							<th><?php esc_html_e( 'IP-hash', 'cocookie' ); ?></th>
							<th><?php esc_html_e( 'Kategorier', 'cocookie' ); ?></th>
							<th><?php esc_html_e( 'Datum', 'cocookie' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $consents['items'] as $item ) : ?>
							<?php $cats = json_decode( $item['categories'], true ); ?>
							<tr>
								<td><code><?php echo esc_html( substr( $item['consent_uuid'], 0, 8 ) ); ?>…</code></td>
								<td><code style="font-size:11px;"><?php echo esc_html( substr( $item['ip_hash'], 0, 12 ) ); ?>…</code></td>
								<td>
									<?php if ( is_array( $cats ) ) : ?>
										<?php foreach ( $cats as $slug => $accepted ) : ?>
											<span class="cocookie-badge <?php echo $accepted ? 'cocookie-badge--success' : 'cocookie-badge--muted'; ?>" style="margin:1px;">
												<?php echo esc_html( $slug ); ?>
											</span>
										<?php endforeach; ?>
									<?php endif; ?>
								</td>
								<td style="color:var(--cocookie-neutral-500);font-size:12px;"><?php echo esc_html( $item['created_at'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<?php if ( $consents['pages'] > 1 ) : ?>
				<div class="cocookie-pagination">
					<?php for ( $i = 1; $i <= $consents['pages']; $i++ ) : ?>
						<?php if ( $i === $consents['page'] ) : ?>
							<strong><?php echo esc_html( $i ); ?></strong>
						<?php else : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=cocookie-compliance&tab=consents&paged=' . $i ) ); ?>"><?php echo esc_html( $i ); ?></a>
						<?php endif; ?>
					<?php endfor; ?>
				</div>
			<?php endif; ?>

		<?php endif; ?>
		<?php
		break;

endswitch;
?>
</div>

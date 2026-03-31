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

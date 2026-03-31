<?php
/**
 * Dashboard view.
 *
 * @package CoCookie
 * @var array $data Controller data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$compliance       = $data['compliance'];
$stats            = $data['stats'];
$registered_count = $data['registered_count'];
$blocked_count    = $data['blocked_count'];
$unknown_count    = $data['unknown_count'];
$last_scan        = $data['last_scan'];
$alerts           = $data['alerts'];

// Bestäm status baserat på poäng
if ( $compliance >= 80 ) {
	$score_color  = '#29A166';
	$score_status = 'good';
	$score_label  = __( 'God compliance', 'cocookie' );
} elseif ( $compliance >= 50 ) {
	$score_color  = '#F59E0B';
	$score_status = 'warning';
	$score_label  = __( 'Behöver förbättras', 'cocookie' );
} else {
	$score_color  = '#EF4444';
	$score_status = 'bad';
	$score_label  = __( 'Åtgärd krävs', 'cocookie' );
}

// Beräkna Getting Started-checklista
$has_cookies    = $registered_count > 0;
$has_scan       = ! empty( $last_scan );
$has_banner_set = ! empty( get_option( 'cocookie_settings' ) );
$checklist_done = array_sum( array( $has_cookies, $has_scan, $has_banner_set ) );
?>

<!-- Sidhuvud -->
<div class="cocookie-page-header">
	<div class="cocookie-page-header__title">
		<div class="cocookie-page-header__icon">
			<span class="dashicons dashicons-shield-alt"></span>
		</div>
		<div class="cocookie-page-header__text">
			<h1><?php esc_html_e( 'CoCookie Dashboard', 'cocookie' ); ?></h1>
			<p><?php esc_html_e( 'Översikt över din cookie-compliance och samtyckesstatus.', 'cocookie' ); ?></p>
		</div>
	</div>
</div>

<?php if ( ! empty( $alerts ) ) : ?>
	<div class="cocookie-alerts">
		<?php foreach ( $alerts as $alert ) : ?>
			<div class="cocookie-alert cocookie-alert--<?php echo esc_attr( $alert['type'] ); ?>">
				<span class="cocookie-alert__icon dashicons <?php echo 'warning' === $alert['type'] ? 'dashicons-warning' : 'dashicons-dismiss'; ?>"></span>
				<span class="cocookie-alert__message"><?php echo esc_html( $alert['message'] ); ?></span>
				<?php if ( ! empty( $alert['action'] ) ) : ?>
					<a href="<?php echo esc_url( $alert['action'] ); ?>" class="cocookie-alert__action">
						<?php echo esc_html( $alert['label'] ); ?> &rarr;
					</a>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<div class="cocookie-dashboard-grid">

	<!-- Compliance-poäng — hero-element -->
	<div class="cocookie-card cocookie-card--score">
		<div class="cocookie-score">
			<div class="cocookie-score__circle" data-score="<?php echo esc_attr( $compliance ); ?>">
				<svg viewBox="0 0 148 148" class="cocookie-score__svg" aria-hidden="true">
					<!-- Bakgrundscirkel -->
					<circle cx="74" cy="74" r="62" fill="none" stroke="#E5E7EB" stroke-width="9"/>
					<!-- Poäng-arc -->
					<circle cx="74" cy="74" r="62"
						fill="none"
						stroke="<?php echo esc_attr( $score_color ); ?>"
						stroke-width="9"
						stroke-dasharray="<?php echo esc_attr( round( 389.557 * $compliance / 100 ) ); ?> 389.557"
						stroke-linecap="round"
						transform="rotate(-90 74 74)"
						class="cocookie-score__arc"/>
				</svg>
				<span class="cocookie-score__number"><?php echo esc_html( $compliance ); ?><span class="cocookie-score__unit">%</span></span>
			</div>
			<p class="cocookie-score__label"><?php esc_html_e( 'Compliance-poäng', 'cocookie' ); ?></p>
			<div class="cocookie-score__status cocookie-score__status--<?php echo esc_attr( $score_status ); ?>">
				<span class="dashicons <?php echo 'good' === $score_status ? 'dashicons-yes-alt' : ( 'warning' === $score_status ? 'dashicons-warning' : 'dashicons-dismiss' ); ?>"></span>
				<?php echo esc_html( $score_label ); ?>
			</div>
		</div>
	</div>

	<!-- Snabbstatistik -->
	<div class="cocookie-card cocookie-card--stats">
		<div class="cocookie-card__header">
			<h3 class="cocookie-card__title">
				<span class="dashicons dashicons-chart-bar"></span>
				<?php esc_html_e( 'Snabbstatistik', 'cocookie' ); ?>
			</h3>
		</div>
		<div class="cocookie-stats-grid">
			<div class="cocookie-stat">
				<span class="cocookie-stat__icon dashicons dashicons-groups"></span>
				<span class="cocookie-stat__number"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></span>
				<span class="cocookie-stat__label"><?php esc_html_e( 'Totala samtycken', 'cocookie' ); ?></span>
			</div>
			<div class="cocookie-stat">
				<span class="cocookie-stat__icon dashicons dashicons-list-view"></span>
				<span class="cocookie-stat__number cocookie-stat__number--neutral"><?php echo esc_html( $registered_count ); ?></span>
				<span class="cocookie-stat__label"><?php esc_html_e( 'Registrerade cookies', 'cocookie' ); ?></span>
			</div>
			<div class="cocookie-stat">
				<span class="cocookie-stat__icon dashicons dashicons-lock"></span>
				<span class="cocookie-stat__number cocookie-stat__number--neutral"><?php echo esc_html( $blocked_count ); ?></span>
				<span class="cocookie-stat__label"><?php esc_html_e( 'Blockerade cookies', 'cocookie' ); ?></span>
			</div>
			<div class="cocookie-stat">
				<span class="cocookie-stat__icon dashicons dashicons-search"></span>
				<span class="cocookie-stat__number <?php echo $unknown_count > 0 ? 'cocookie-stat__number--warning' : 'cocookie-stat__number--neutral'; ?>">
					<?php echo esc_html( $unknown_count ); ?>
				</span>
				<span class="cocookie-stat__label"><?php esc_html_e( 'Okända cookies', 'cocookie' ); ?></span>
			</div>
		</div>
	</div>

	<!-- Acceptansgrad per kategori -->
	<?php if ( ! empty( $stats['categories'] ) ) : ?>
		<div class="cocookie-card cocookie-card--categories">
			<div class="cocookie-card__header">
				<h3 class="cocookie-card__title">
					<span class="dashicons dashicons-tag"></span>
					<?php esc_html_e( 'Acceptansgrad per kategori', 'cocookie' ); ?>
				</h3>
			</div>
			<table class="cocookie-table" style="width:100%;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Kategori', 'cocookie' ); ?></th>
						<th><?php esc_html_e( 'Accepterad', 'cocookie' ); ?></th>
						<th><?php esc_html_e( 'Avvisad', 'cocookie' ); ?></th>
						<th><?php esc_html_e( 'Acceptansgrad', 'cocookie' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $stats['categories'] as $slug => $cat_stat ) : ?>
						<?php
						$total_cat = $cat_stat['accepted'] + $cat_stat['rejected'];
						$rate      = $total_cat > 0 ? round( ( $cat_stat['accepted'] / $total_cat ) * 100 ) : 0;
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( $cat_stat['title'] ); ?></strong>
								<?php if ( $cat_stat['required'] ) : ?>
									<span class="cocookie-badge cocookie-badge--info" style="margin-left:6px;"><?php esc_html_e( 'Obligatorisk', 'cocookie' ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( number_format_i18n( $cat_stat['accepted'] ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $cat_stat['rejected'] ) ); ?></td>
							<td style="white-space:nowrap;">
								<div class="cocookie-progress">
									<div class="cocookie-progress__bar" style="width: <?php echo esc_attr( $rate ); ?>%"></div>
								</div>
								<span class="cocookie-progress__label"><?php echo esc_html( $rate ); ?>%</span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<!-- Getting Started + Snabbåtgärder (full bredd) -->
	<div class="cocookie-card cocookie-card--actions">

		<!-- Snabbåtgärder -->
		<div class="cocookie-card__header">
			<h3 class="cocookie-card__title">
				<span class="dashicons dashicons-controls-play"></span>
				<?php esc_html_e( 'Snabbåtgärder', 'cocookie' ); ?>
			</h3>
		</div>
		<div class="cocookie-quick-actions" style="margin-bottom: 28px;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cocookie-compliance&tab=scanner' ) ); ?>" class="cocookie-quick-action-btn cocookie-quick-action-btn--primary">
				<span class="dashicons dashicons-search"></span>
				<?php esc_html_e( 'Skanna cookies', 'cocookie' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cocookie-compliance&tab=audit' ) ); ?>" class="cocookie-quick-action-btn">
				<span class="dashicons dashicons-yes-alt"></span>
				<?php esc_html_e( 'Kör audit', 'cocookie' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cocookie-banner' ) ); ?>" class="cocookie-quick-action-btn">
				<span class="dashicons dashicons-admin-appearance"></span>
				<?php esc_html_e( 'Redigera banner', 'cocookie' ); ?>
			</a>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?cocookie_run_wizard=1' ), 'cocookie_run_wizard' ) ); ?>" class="cocookie-quick-action-btn">
				<span class="dashicons dashicons-welcome-learn-more"></span>
				<?php esc_html_e( 'Kör Setup Wizard', 'cocookie' ); ?>
			</a>
		</div>

		<!-- Getting Started checklista -->
		<div class="cocookie-card__section">
			<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
				<h3 class="cocookie-card__title">
					<span class="dashicons dashicons-clipboard"></span>
					<?php esc_html_e( 'Kom igång', 'cocookie' ); ?>
				</h3>
				<span class="cocookie-badge <?php echo $checklist_done >= 3 ? 'cocookie-badge--success' : 'cocookie-badge--warning'; ?>">
					<?php echo esc_html( $checklist_done ); ?>/3 <?php esc_html_e( 'klart', 'cocookie' ); ?>
				</span>
			</div>
			<ul class="cocookie-checklist">
				<li class="<?php echo $has_scan ? 'done' : ''; ?>">
					<span class="cocookie-checklist__check <?php echo $has_scan ? 'cocookie-checklist__check--done' : 'cocookie-checklist__check--todo'; ?>">
						<span class="dashicons <?php echo $has_scan ? 'dashicons-yes' : 'dashicons-minus'; ?>"></span>
					</span>
					<span class="cocookie-checklist__text">
						<strong><?php esc_html_e( 'Skanna din webbplats', 'cocookie' ); ?></strong>
						<span><?php esc_html_e( 'Identifiera vilka cookies som används', 'cocookie' ); ?></span>
					</span>
					<?php if ( ! $has_scan ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=cocookie-compliance&tab=scanner' ) ); ?>" class="cocookie-checklist__action"><?php esc_html_e( 'Skanna nu', 'cocookie' ); ?> &rarr;</a>
					<?php endif; ?>
				</li>
				<li class="<?php echo $has_cookies ? 'done' : ''; ?>">
					<span class="cocookie-checklist__check <?php echo $has_cookies ? 'cocookie-checklist__check--done' : 'cocookie-checklist__check--todo'; ?>">
						<span class="dashicons <?php echo $has_cookies ? 'dashicons-yes' : 'dashicons-minus'; ?>"></span>
					</span>
					<span class="cocookie-checklist__text">
						<strong><?php esc_html_e( 'Registrera cookies', 'cocookie' ); ?></strong>
						<span><?php esc_html_e( 'Lägg till cookies i ditt register per kategori', 'cocookie' ); ?></span>
					</span>
					<?php if ( ! $has_cookies ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=cocookie-cookies' ) ); ?>" class="cocookie-checklist__action"><?php esc_html_e( 'Lägg till', 'cocookie' ); ?> &rarr;</a>
					<?php endif; ?>
				</li>
				<li class="<?php echo $has_banner_set ? 'done' : ''; ?>">
					<span class="cocookie-checklist__check <?php echo $has_banner_set ? 'cocookie-checklist__check--done' : 'cocookie-checklist__check--todo'; ?>">
						<span class="dashicons <?php echo $has_banner_set ? 'dashicons-yes' : 'dashicons-minus'; ?>"></span>
					</span>
					<span class="cocookie-checklist__text">
						<strong><?php esc_html_e( 'Konfigurera banner', 'cocookie' ); ?></strong>
						<span><?php esc_html_e( 'Anpassa utseende och texter för cookie-bannern', 'cocookie' ); ?></span>
					</span>
					<?php if ( ! $has_banner_set ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=cocookie-banner' ) ); ?>" class="cocookie-checklist__action"><?php esc_html_e( 'Konfigurera', 'cocookie' ); ?> &rarr;</a>
					<?php endif; ?>
				</li>
			</ul>
		</div>

		<?php if ( $last_scan ) : ?>
			<p class="cocookie-meta">
				<span class="dashicons dashicons-clock"></span>
				<?php
				printf(
					/* translators: %s: datum och tid för senaste skanning */
					esc_html__( 'Senaste skanning: %s', 'cocookie' ),
					esc_html( $last_scan )
				);
				?>
			</p>
		<?php endif; ?>
	</div>

</div>

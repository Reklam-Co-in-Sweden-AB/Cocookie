<?php
/**
 * Wizard Steg 2: Granska skanningsresultat
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$scan_results = CoCookie_REST_Scanner::get_scan_results();
$categories   = CoCookie_Categories::get_all();
$total_found  = count( $scan_results );
$imported     = array_filter( $scan_results, fn( $sr ) => $sr['is_imported'] );
$not_imported = $total_found - count( $imported );
?>

<div class="cocookie-wizard__content">

	<!-- Hero-ikon -->
	<div class="cocookie-wizard__icon-hero cocookie-wizard__icon-hero--review">
		<span class="dashicons dashicons-clipboard"></span>
	</div>

	<h2><?php esc_html_e( 'Granska hittade cookies', 'cocookie' ); ?></h2>
	<p class="cocookie-wizard__desc">
		<?php
		printf(
			esc_html__( 'Vi hittade %d cookies på din webbplats. De har automatiskt kategoriserats baserat på kända mönster. Granska och importera dem till ditt register.', 'cocookie' ),
			$total_found
		);
		?>
	</p>

	<?php if ( ! empty( $scan_results ) ) : ?>

		<!-- Summering -->
		<div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
			<div style="display:flex;align-items:center;gap:6px;padding:8px 14px;background:var(--cocookie-green-50);border:1px solid var(--cocookie-green-100);border-radius:var(--cocookie-radius-md);font-size:13px;font-weight:600;color:var(--cocookie-green-600);">
				<span class="dashicons dashicons-yes-alt" style="font-size:15px;width:15px;height:15px;"></span>
				<?php echo esc_html( count( $imported ) ); ?> <?php esc_html_e( 'importerade', 'cocookie' ); ?>
			</div>
			<?php if ( $not_imported > 0 ) : ?>
				<div style="display:flex;align-items:center;gap:6px;padding:8px 14px;background:var(--cocookie-amber-50);border:1px solid #FCD34D;border-radius:var(--cocookie-radius-md);font-size:13px;font-weight:600;color:#92400E;">
					<span class="dashicons dashicons-warning" style="font-size:15px;width:15px;height:15px;"></span>
					<?php echo esc_html( $not_imported ); ?> <?php esc_html_e( 'ej importerade', 'cocookie' ); ?>
				</div>
			<?php endif; ?>
		</div>

		<div style="padding:0;overflow:hidden;background:#fff;border:1px solid var(--cocookie-neutral-200);border-radius:var(--cocookie-radius-md);margin-bottom:20px;">
			<table class="cocookie-table" style="width:100%;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Cookie', 'cocookie' ); ?></th>
						<th><?php esc_html_e( 'Kategori', 'cocookie' ); ?></th>
						<th><?php esc_html_e( 'Leverantör', 'cocookie' ); ?></th>
						<th><?php esc_html_e( 'Syfte', 'cocookie' ); ?></th>
						<th><?php esc_html_e( 'Status', 'cocookie' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $scan_results as $sr ) : ?>
						<tr>
							<td><code><?php echo esc_html( $sr['name'] ); ?></code></td>
							<td>
								<span class="cocookie-badge cocookie-badge--<?php echo esc_attr( $sr['suggested_category'] ); ?>">
									<?php echo esc_html( $sr['suggested_category'] ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $sr['suggested_provider'] ?: '—' ); ?></td>
							<td style="max-width:200px;font-size:12px;color:var(--cocookie-neutral-600);">
								<?php echo esc_html( $sr['suggested_purpose'] ?: '—' ); ?>
							</td>
							<td>
								<?php if ( $sr['is_imported'] ) : ?>
									<span class="cocookie-badge cocookie-badge--success">
										<span class="dashicons dashicons-yes" style="font-size:12px;width:12px;height:12px;margin-right:2px;"></span>
										<?php esc_html_e( 'Importerad', 'cocookie' ); ?>
									</span>
								<?php else : ?>
									<span class="cocookie-badge cocookie-badge--muted"><?php esc_html_e( 'Ej importerad', 'cocookie' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $not_imported > 0 ) : ?>
			<div style="margin-bottom:20px;">
				<button type="button" id="cocookie-wizard-import-all" class="button button-secondary">
					<span class="dashicons dashicons-download" style="font-size:15px;width:15px;height:15px;vertical-align:middle;margin-right:4px;"></span>
					<?php esc_html_e( 'Importera alla till registret', 'cocookie' ); ?>
				</button>
			</div>
		<?php endif; ?>

	<?php else : ?>

		<div style="text-align:center;padding:32px 0;">
			<div style="width:56px;height:56px;background:var(--cocookie-amber-100);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
				<span class="dashicons dashicons-warning" style="color:#92400E;font-size:26px;width:26px;height:26px;"></span>
			</div>
			<p style="font-size:15px;font-weight:600;color:var(--cocookie-neutral-700);margin-bottom:8px;"><?php esc_html_e( 'Inga skanningsresultat', 'cocookie' ); ?></p>
			<p style="font-size:13px;color:var(--cocookie-neutral-500);"><?php esc_html_e( 'Gå tillbaka och kör en skanning först.', 'cocookie' ); ?></p>
		</div>

	<?php endif; ?>

	<div class="cocookie-wizard__nav">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=cocookie-wizard&step=1' ) ); ?>" class="button">
			&larr; <?php esc_html_e( 'Tillbaka', 'cocookie' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=cocookie-wizard&step=3' ) ); ?>" class="button button-primary">
			<?php esc_html_e( 'Konfigurera banner', 'cocookie' ); ?> &rarr;
		</a>
	</div>

</div>

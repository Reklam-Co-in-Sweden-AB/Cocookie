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
	style="display:none;
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

		<!-- Consent info (shown when reopening) -->
		<div class="cocookie-banner__consent-info" id="cocookie-consent-info" style="display:none;">
			<p><strong><?php echo esc_html( $s['consent_date_label'] ); ?></strong> <span id="cocookie-consent-date"></span></p>
			<p><strong><?php echo esc_html( $s['consent_id_label'] ); ?></strong> <span id="cocookie-consent-id"></span></p>
		</div>

		<!-- DNT notice -->
		<div class="cocookie-banner__dnt" id="cocookie-dnt-notice" style="display:none;">
			<?php echo esc_html( $s['dnt_notice'] ); ?>
		</div>

		<!-- Category details (expandable) -->
		<div class="cocookie-banner__details" id="cocookie-details" style="display:none;">
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

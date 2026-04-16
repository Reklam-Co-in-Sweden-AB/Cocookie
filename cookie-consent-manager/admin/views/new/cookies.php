<?php
/**
 * Cookies-vy — kategorier i sidofält, cookies i huvud.
 *
 * @package CoCookie
 * @var array $data Controller data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$categories      = $data['categories'];
$selected_cat_id = $data['selected_cat_id'];
$selected_cat    = $data['selected_cat'];
$cookies         = $data['cookies'];
$editing_cat     = $data['editing_cat'];
$editing_cookie  = $data['editing_cookie'];
$maintenance     = $data['maintenance'] ?? array( 'unknown_in_necessary' => 0, 'missing' => 0, 'has_scan' => false );
$msg             = isset( $_GET['msg'] ) ? sanitize_text_field( $_GET['msg'] ) : '';
$msg_n           = isset( $_GET['n'] ) ? intval( $_GET['n'] ) : 0;
?>

<!-- Sidhuvud -->
<div class="cocookie-page-header">
	<div class="cocookie-page-header__title">
		<div class="cocookie-page-header__icon">
			<span class="dashicons dashicons-list-view"></span>
		</div>
		<div class="cocookie-page-header__text">
			<h1><?php esc_html_e( 'Cookies', 'cocookie' ); ?></h1>
			<p><?php esc_html_e( 'Hantera ditt cookie-register och kategorier.', 'cocookie' ); ?></p>
		</div>
	</div>
</div>

<?php if ( 'saved' === $msg ) : ?>
	<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Sparat.', 'cocookie' ); ?></p></div>
<?php elseif ( 'deleted' === $msg ) : ?>
	<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Borttaget.', 'cocookie' ); ?></p></div>
<?php elseif ( 'imported' === $msg ) : ?>
	<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Import klar.', 'cocookie' ); ?></p></div>
<?php elseif ( 'import_error' === $msg ) : ?>
	<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Importfel. Kontrollera att filen är giltig JSON.', 'cocookie' ); ?></p></div>
<?php elseif ( 'reclassified' === $msg ) : ?>
	<div class="notice notice-success is-dismissible"><p>
		<?php
		printf(
			/* translators: %d = antal flyttade cookies */
			esc_html( _n( '%d okänd cookie flyttad till Okategoriserade.', '%d okända cookies flyttade till Okategoriserade.', $msg_n, 'cocookie' ) ),
			intval( $msg_n )
		);
		?>
	</p></div>
<?php elseif ( 'cleaned' === $msg ) : ?>
	<div class="notice notice-success is-dismissible"><p>
		<?php
		printf(
			/* translators: %d = antal borttagna cookies */
			esc_html( _n( '%d cookie som inte längre finns togs bort.', '%d cookies som inte längre finns togs bort.', $msg_n, 'cocookie' ) ),
			intval( $msg_n )
		);
		?>
	</p></div>
<?php endif; ?>

<div class="cocookie-cookies-layout">

	<!-- Sidofält: Kategorier -->
	<div class="cocookie-sidebar">

		<div class="cocookie-sidebar__header">
			<h3><?php esc_html_e( 'Kategorier', 'cocookie' ); ?></h3>
		</div>

		<div class="cocookie-sidebar__body">
			<ul class="cocookie-category-list">
				<?php foreach ( $categories as $cat ) : ?>
					<li class="<?php echo (int) $cat['id'] === $selected_cat_id ? 'cocookie-category-list__item--active' : ''; ?>">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=cocookie-cookies&cat=' . $cat['id'] ) ); ?>">
							<span class="dashicons dashicons-category" style="font-size:14px;width:14px;height:14px;opacity:0.5;"></span>
							<?php echo esc_html( $cat['title'] ); ?>
							<?php if ( $cat['is_required'] ) : ?>
								<span class="cocookie-badge cocookie-badge--small cocookie-badge--info"><?php esc_html_e( 'Krävs', 'cocookie' ); ?></span>
							<?php endif; ?>
						</a>
						<span class="cocookie-category-list__actions">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=cocookie-cookies&cat=' . $selected_cat_id . '&edit_cat=' . $cat['id'] ) ); ?>" title="<?php esc_attr_e( 'Redigera kategori', 'cocookie' ); ?>">
								<span class="dashicons dashicons-edit"></span>
							</a>
							<?php if ( ! $cat['is_required'] ) : ?>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cocookie-cookies&delete_cat=' . $cat['id'] ), 'cocookie_delete_category' ) ); ?>"
								   onclick="return confirm('<?php esc_attr_e( 'Är du säker? Alla cookies i denna kategori raderas.', 'cocookie' ); ?>');"
								   title="<?php esc_attr_e( 'Radera kategori', 'cocookie' ); ?>"
								   style="color: var(--cocookie-red-500);">
									<span class="dashicons dashicons-trash"></span>
								</a>
							<?php endif; ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<!-- Lägg till / redigera kategori-formulär -->
		<div class="cocookie-sidebar-form">
			<h4><?php echo $editing_cat ? esc_html__( 'Redigera kategori', 'cocookie' ) : esc_html__( 'Ny kategori', 'cocookie' ); ?></h4>
			<form method="post">
				<?php wp_nonce_field( 'cocookie_category_action' ); ?>
				<?php if ( $editing_cat ) : ?>
					<input type="hidden" name="category_id" value="<?php echo esc_attr( $editing_cat['id'] ); ?>">
				<?php endif; ?>
				<p>
					<label><?php esc_html_e( 'Slug', 'cocookie' ); ?></label>
					<input type="text" name="slug" value="<?php echo esc_attr( $editing_cat['slug'] ?? '' ); ?>" class="widefat" required>
				</p>
				<p>
					<label><?php esc_html_e( 'Titel', 'cocookie' ); ?></label>
					<input type="text" name="title" value="<?php echo esc_attr( $editing_cat['title'] ?? '' ); ?>" class="widefat" required>
				</p>
				<p>
					<label><?php esc_html_e( 'Beskrivning', 'cocookie' ); ?></label>
					<textarea name="description" class="widefat" rows="2"><?php echo esc_textarea( $editing_cat['description'] ?? '' ); ?></textarea>
				</p>
				<p>
					<label><input type="checkbox" name="is_required" value="1" <?php checked( ! empty( $editing_cat['is_required'] ) ); ?>>
					<?php esc_html_e( 'Obligatorisk (kan ej avvisas)', 'cocookie' ); ?></label>
				</p>
				<p>
					<label><?php esc_html_e( 'Sorteringsordning', 'cocookie' ); ?></label>
					<input type="number" name="sort_order" value="<?php echo esc_attr( $editing_cat['sort_order'] ?? 0 ); ?>" class="small-text">
				</p>
				<?php submit_button( $editing_cat ? __( 'Uppdatera kategori', 'cocookie' ) : __( 'Lägg till kategori', 'cocookie' ), 'secondary', 'cocookie_save_category', false ); ?>
			</form>
		</div>

		<!-- Import / Export -->
		<div class="cocookie-sidebar-form">
			<h4><?php esc_html_e( 'Import / Export', 'cocookie' ); ?></h4>
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'cocookie_import_cookies' ); ?>
				<p>
					<label><?php esc_html_e( 'Importera JSON-fil', 'cocookie' ); ?></label>
					<input type="file" name="cocookie_import_file" accept=".json">
				</p>
				<?php submit_button( __( 'Importera', 'cocookie' ), 'secondary', 'cocookie_import_cookies', false ); ?>
			</form>
			<form method="post" style="margin-top:10px;">
				<?php wp_nonce_field( 'cocookie_export_cookies' ); ?>
				<?php submit_button( __( 'Exportera alla cookies', 'cocookie' ), 'secondary', 'cocookie_export_cookies', false ); ?>
			</form>
		</div>

		<?php if ( $maintenance['unknown_in_necessary'] > 0 || ( $maintenance['has_scan'] && $maintenance['missing'] > 0 ) ) : ?>
			<!-- Underhåll -->
			<div class="cocookie-sidebar-form">
				<h4><?php esc_html_e( 'Underhåll', 'cocookie' ); ?></h4>

				<?php if ( $maintenance['unknown_in_necessary'] > 0 ) : ?>
					<form method="post" style="margin-bottom:10px;"
						onsubmit="return confirm('<?php
							printf(
								/* translators: %d = antal cookies */
								esc_attr( _n( '%d okänd cookie kommer flyttas från Nödvändiga till Okategoriserade. Fortsätta?', '%d okända cookies kommer flyttas från Nödvändiga till Okategoriserade. Fortsätta?', $maintenance['unknown_in_necessary'], 'cocookie' ) ),
								intval( $maintenance['unknown_in_necessary'] )
							);
						?>');">
						<?php wp_nonce_field( 'cocookie_reclassify_unknown' ); ?>
						<p class="description" style="margin-top:0;">
							<?php
							printf(
								/* translators: %d = antal */
								esc_html( _n( '%d cookie i Nödvändiga saknar leverantör och är troligen felklassad.', '%d cookies i Nödvändiga saknar leverantör och är troligen felklassade.', $maintenance['unknown_in_necessary'], 'cocookie' ) ),
								intval( $maintenance['unknown_in_necessary'] )
							);
							?>
						</p>
						<?php submit_button( __( 'Flytta okända till Okategoriserade', 'cocookie' ), 'secondary', 'cocookie_reclassify_unknown', false ); ?>
					</form>
				<?php endif; ?>

				<?php if ( $maintenance['has_scan'] && $maintenance['missing'] > 0 ) : ?>
					<form method="post"
						onsubmit="return confirm('<?php
							printf(
								/* translators: %d = antal cookies */
								esc_attr( _n( '%d cookie saknades i senaste scanningen och kommer tas bort permanent. Fortsätta?', '%d cookies saknades i senaste scanningen och kommer tas bort permanent. Fortsätta?', $maintenance['missing'], 'cocookie' ) ),
								intval( $maintenance['missing'] )
							);
						?>');">
						<?php wp_nonce_field( 'cocookie_cleanup_missing' ); ?>
						<p class="description" style="margin-top:0;">
							<?php
							printf(
								/* translators: %d = antal */
								esc_html( _n( '%d cookie i registret syntes inte i senaste scanningen.', '%d cookies i registret syntes inte i senaste scanningen.', $maintenance['missing'], 'cocookie' ) ),
								intval( $maintenance['missing'] )
							);
							?>
						</p>
						<?php submit_button( __( 'Städa bort försvunna cookies', 'cocookie' ), 'secondary', 'cocookie_cleanup_missing', false ); ?>
					</form>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	</div><!-- .cocookie-sidebar -->

	<!-- Huvud: Cookies i vald kategori -->
	<div class="cocookie-main">

		<?php if ( $selected_cat ) : ?>

			<h2>
				<?php echo esc_html( $selected_cat['title'] ); ?>
				<span class="cocookie-count">(<?php echo count( $cookies ); ?>)</span>
			</h2>

			<?php if ( ! empty( $selected_cat['description'] ) ) : ?>
				<p class="description" style="margin-bottom:20px; color:var(--cocookie-neutral-500); font-size:13px;">
					<?php echo esc_html( $selected_cat['description'] ); ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $cookies ) ) : ?>
				<div class="cocookie-card" style="padding:0; overflow:hidden;">
					<table class="cocookie-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Namn', 'cocookie' ); ?></th>
								<th><?php esc_html_e( 'Leverantör', 'cocookie' ); ?></th>
								<th><?php esc_html_e( 'Syfte', 'cocookie' ); ?></th>
								<th><?php esc_html_e( 'Livslängd', 'cocookie' ); ?></th>
								<th><?php esc_html_e( 'Åtgärder', 'cocookie' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $cookies as $c ) : ?>
								<tr>
									<td><code><?php echo esc_html( $c['name'] ); ?></code></td>
									<td>
										<?php if ( ! empty( $c['provider'] ) ) : ?>
											<?php echo esc_html( $c['provider'] ); ?>
										<?php else : ?>
											<span style="color:var(--cocookie-neutral-400);">—</span>
										<?php endif; ?>
									</td>
									<td style="max-width:220px;">
										<span style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
											<?php echo esc_html( $c['purpose'] ); ?>
										</span>
									</td>
									<td>
										<?php if ( ! empty( $c['expiry'] ) ) : ?>
											<span class="cocookie-badge cocookie-badge--muted"><?php echo esc_html( $c['expiry'] ); ?></span>
										<?php else : ?>
											<span style="color:var(--cocookie-neutral-400);">—</span>
										<?php endif; ?>
									</td>
									<td class="cocookie-actions">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=cocookie-cookies&cat=' . $selected_cat_id . '&edit_cookie=' . $c['id'] ) ); ?>"><?php esc_html_e( 'Redigera', 'cocookie' ); ?></a>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cocookie-cookies&delete_cookie=' . $c['id'] ), 'cocookie_delete_cookie' ) ); ?>"
										   onclick="return confirm('<?php esc_attr_e( 'Radera cookie?', 'cocookie' ); ?>');"
										   class="cocookie-link--danger"><?php esc_html_e( 'Radera', 'cocookie' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div><!-- .cocookie-card -->

			<?php else : ?>

				<div class="cocookie-card">
					<div class="cocookie-empty">
						<div class="cocookie-empty__icon">
							<span class="dashicons dashicons-list-view"></span>
						</div>
						<p class="cocookie-empty__title"><?php esc_html_e( 'Inga cookies ännu', 'cocookie' ); ?></p>
						<p class="cocookie-empty__desc"><?php esc_html_e( 'Lägg till cookies manuellt nedan eller importera via JSON.', 'cocookie' ); ?></p>
					</div>
				</div>

			<?php endif; ?>

			<!-- Lägg till / redigera cookie -->
			<div class="cocookie-form-card">
				<h3>
					<span class="dashicons <?php echo $editing_cookie ? 'dashicons-edit' : 'dashicons-plus-alt2'; ?>"></span>
					<?php echo $editing_cookie ? esc_html__( 'Redigera cookie', 'cocookie' ) : esc_html__( 'Lägg till cookie', 'cocookie' ); ?>
				</h3>
				<form method="post">
					<?php wp_nonce_field( 'cocookie_cookie_action' ); ?>
					<?php if ( $editing_cookie ) : ?>
						<input type="hidden" name="cookie_id" value="<?php echo esc_attr( $editing_cookie['id'] ); ?>">
					<?php endif; ?>
					<input type="hidden" name="category_id" value="<?php echo esc_attr( $editing_cookie['category_id'] ?? $selected_cat_id ); ?>">
					<table class="form-table">
						<tr>
							<th><label for="cookie_name"><?php esc_html_e( 'Namn', 'cocookie' ); ?> <span style="color:var(--cocookie-red-500);">*</span></label></th>
							<td>
								<input type="text" id="cookie_name" name="name" value="<?php echo esc_attr( $editing_cookie['name'] ?? '' ); ?>" class="regular-text" required placeholder="t.ex. _ga">
								<p class="description"><?php esc_html_e( 'Det exakta cookie-namnet som det sätts i webbläsaren.', 'cocookie' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="cookie_provider"><?php esc_html_e( 'Leverantör', 'cocookie' ); ?></label></th>
							<td>
								<input type="text" id="cookie_provider" name="provider" value="<?php echo esc_attr( $editing_cookie['provider'] ?? '' ); ?>" class="regular-text" placeholder="t.ex. Google">
							</td>
						</tr>
						<tr>
							<th><label for="cookie_purpose"><?php esc_html_e( 'Syfte', 'cocookie' ); ?></label></th>
							<td>
								<textarea id="cookie_purpose" name="purpose" class="large-text" rows="2" placeholder="<?php esc_attr_e( 'Beskriv vad denna cookie används till.', 'cocookie' ); ?>"><?php echo esc_textarea( $editing_cookie['purpose'] ?? '' ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th><label for="cookie_expiry"><?php esc_html_e( 'Livslängd', 'cocookie' ); ?></label></th>
							<td>
								<input type="text" id="cookie_expiry" name="expiry" value="<?php echo esc_attr( $editing_cookie['expiry'] ?? '' ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 't.ex. 2 år, Session', 'cocookie' ); ?>">
							</td>
						</tr>
					</table>
					<?php submit_button( $editing_cookie ? __( 'Uppdatera cookie', 'cocookie' ) : __( 'Lägg till cookie', 'cocookie' ), 'primary', 'cocookie_save_cookie' ); ?>
				</form>
			</div><!-- .cocookie-form-card -->

		<?php else : ?>

			<div class="cocookie-card">
				<div class="cocookie-empty">
					<div class="cocookie-empty__icon">
						<span class="dashicons dashicons-category"></span>
					</div>
					<p class="cocookie-empty__title"><?php esc_html_e( 'Välj en kategori', 'cocookie' ); ?></p>
					<p class="cocookie-empty__desc"><?php esc_html_e( 'Klicka på en kategori i sidofältet för att visa och hantera dess cookies.', 'cocookie' ); ?></p>
				</div>
			</div>

		<?php endif; ?>

	</div><!-- .cocookie-main -->

</div><!-- .cocookie-cookies-layout -->

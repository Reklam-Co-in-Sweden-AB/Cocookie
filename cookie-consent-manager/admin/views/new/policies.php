<?php
/**
 * Policyer-vy.
 *
 * @package CoCookie
 * @var array $data Controller data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$info            = $data['company_info'];
$privacy_content = $data['privacy_content'];
$cookie_content  = $data['cookie_content'];
$msg             = isset( $_GET['msg'] ) ? sanitize_text_field( $_GET['msg'] ) : '';
?>

<!-- Sidhuvud -->
<div class="cocookie-page-header">
	<div class="cocookie-page-header__title">
		<div class="cocookie-page-header__icon">
			<span class="dashicons dashicons-media-document"></span>
		</div>
		<div class="cocookie-page-header__text">
			<h1><?php esc_html_e( 'Policyer', 'cocookie' ); ?></h1>
			<p><?php esc_html_e( 'Generera och hantera integritetspolicy och cookiepolicy.', 'cocookie' ); ?></p>
		</div>
	</div>
</div>

<?php if ( 'saved' === $msg ) : ?>
	<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Sparat.', 'cocookie' ); ?></p></div>
<?php elseif ( 'created' === $msg ) : ?>
	<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Sida skapad som utkast.', 'cocookie' ); ?></p></div>
<?php endif; ?>

<div class="cocookie-notice cocookie-notice--info">
	<span class="dashicons dashicons-info" style="flex-shrink:0;font-size:18px;width:18px;height:18px;"></span>
	<p><?php esc_html_e( 'Policyer genereras automatiskt baserat på dina registrerade cookies och företagsinformation. De är utkast — granska alltid med juridisk expertis innan publicering.', 'cocookie' ); ?></p>
</div>

<!-- Företagsinformation -->
<div class="cocookie-settings-section" style="margin-bottom:24px;">
	<div class="cocookie-settings-section__header">
		<span class="dashicons dashicons-building"></span>
		<h2><?php esc_html_e( 'Företagsinformation', 'cocookie' ); ?></h2>
	</div>
	<div class="cocookie-settings-section__body">
		<p style="font-size:13px;color:var(--cocookie-neutral-500);margin-bottom:16px;margin-top:12px;line-height:1.6;">
			<?php esc_html_e( 'Denna information används för att personanpassa de genererade policydokumenten.', 'cocookie' ); ?>
		</p>
		<form method="post">
			<?php wp_nonce_field( 'cocookie_policy_company' ); ?>
			<table class="form-table">
				<tr>
					<th><label for="company_name"><?php esc_html_e( 'Företagsnamn', 'cocookie' ); ?></label></th>
					<td><input type="text" id="company_name" name="company_name" value="<?php echo esc_attr( $info['company_name'] ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="org_number"><?php esc_html_e( 'Organisationsnummer', 'cocookie' ); ?></label></th>
					<td><input type="text" id="org_number" name="org_number" value="<?php echo esc_attr( $info['org_number'] ); ?>" class="regular-text" placeholder="556000-0000"></td>
				</tr>
				<tr>
					<th><label for="address"><?php esc_html_e( 'Adress', 'cocookie' ); ?></label></th>
					<td><textarea id="address" name="address" class="regular-text" rows="2" placeholder="<?php esc_attr_e( 'Gatuadress, postnummer, stad', 'cocookie' ); ?>"><?php echo esc_textarea( $info['address'] ); ?></textarea></td>
				</tr>
				<tr>
					<th><label for="email"><?php esc_html_e( 'E-post', 'cocookie' ); ?></label></th>
					<td><input type="email" id="email" name="email" value="<?php echo esc_attr( $info['email'] ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="phone"><?php esc_html_e( 'Telefon', 'cocookie' ); ?></label></th>
					<td><input type="text" id="phone" name="phone" value="<?php echo esc_attr( $info['phone'] ); ?>" class="regular-text"></td>
				</tr>
			</table>

			<div style="background:var(--cocookie-neutral-50);border:1px solid var(--cocookie-neutral-200);border-radius:var(--cocookie-radius-md);padding:16px 20px;margin-top:12px;margin-bottom:16px;">
				<p style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--cocookie-neutral-500);margin:0 0 12px;"><?php esc_html_e( 'Dataskyddsombud (DPO)', 'cocookie' ); ?></p>
				<table class="form-table" style="margin-top:0;">
					<tr>
						<th><label for="dpo_name"><?php esc_html_e( 'Namn', 'cocookie' ); ?></label></th>
						<td><input type="text" id="dpo_name" name="dpo_name" value="<?php echo esc_attr( $info['dpo_name'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Lämna tomt om ej tillämpligt', 'cocookie' ); ?>"></td>
					</tr>
					<tr>
						<th><label for="dpo_email"><?php esc_html_e( 'E-post', 'cocookie' ); ?></label></th>
						<td><input type="email" id="dpo_email" name="dpo_email" value="<?php echo esc_attr( $info['dpo_email'] ); ?>" class="regular-text"></td>
					</tr>
				</table>
			</div>

			<input type="hidden" name="privacy_page_id" value="<?php echo esc_attr( $info['privacy_page_id'] ); ?>">
			<input type="hidden" name="cookie_page_id" value="<?php echo esc_attr( $info['cookie_page_id'] ); ?>">
			<?php submit_button( __( 'Spara företagsinformation', 'cocookie' ), 'primary', 'cocookie_save_company' ); ?>
		</form>
	</div>
</div>

<!-- Integritetspolicy -->
<div class="cocookie-settings-section" style="margin-bottom:24px;">
	<div class="cocookie-settings-section__header">
		<span class="dashicons dashicons-lock"></span>
		<h2><?php esc_html_e( 'Integritetspolicy', 'cocookie' ); ?></h2>
	</div>
	<div class="cocookie-settings-section__body" style="padding-top:16px;">

		<?php if ( $info['privacy_page_id'] && get_post( $info['privacy_page_id'] ) ) : ?>
			<p class="cocookie-page-status" style="margin-bottom:16px;">
				<span class="dashicons dashicons-yes-alt" style="color:var(--cocookie-green-500);font-size:16px;width:16px;height:16px;"></span>
				<?php printf( esc_html__( 'Sida: %s (%s)', 'cocookie' ), esc_html( get_the_title( $info['privacy_page_id'] ) ), esc_html( get_post_status( $info['privacy_page_id'] ) ) ); ?>
				— <a href="<?php echo esc_url( get_edit_post_link( $info['privacy_page_id'] ) ); ?>"><?php esc_html_e( 'Redigera sida', 'cocookie' ); ?></a>
			</p>
		<?php endif; ?>

		<details class="cocookie-preview">
			<summary>
				<span class="dashicons dashicons-visibility" style="font-size:14px;width:14px;height:14px;"></span>
				<?php esc_html_e( 'Förhandsgranska genererad policy', 'cocookie' ); ?>
			</summary>
			<div class="cocookie-preview__content"><?php echo wp_kses_post( $privacy_content ); ?></div>
		</details>

		<form method="post" style="margin-top:16px;">
			<?php wp_nonce_field( 'cocookie_policy_create' ); ?>
			<input type="hidden" name="policy_type" value="privacy">
			<?php submit_button(
				$info['privacy_page_id'] ? __( 'Uppdatera WordPress-sida', 'cocookie' ) : __( 'Skapa WordPress-sida (utkast)', 'cocookie' ),
				'secondary',
				'cocookie_create_policy',
				false
			); ?>
			<p class="description" style="margin-top:8px;"><?php esc_html_e( 'Skapar eller uppdaterar en WordPress-sida med policytexten.', 'cocookie' ); ?></p>
		</form>

	</div>
</div>

<!-- Cookiepolicy -->
<div class="cocookie-settings-section">
	<div class="cocookie-settings-section__header">
		<span class="dashicons dashicons-visibility"></span>
		<h2><?php esc_html_e( 'Cookiepolicy', 'cocookie' ); ?></h2>
	</div>
	<div class="cocookie-settings-section__body" style="padding-top:16px;">

		<?php if ( $info['cookie_page_id'] && get_post( $info['cookie_page_id'] ) ) : ?>
			<p class="cocookie-page-status" style="margin-bottom:16px;">
				<span class="dashicons dashicons-yes-alt" style="color:var(--cocookie-green-500);font-size:16px;width:16px;height:16px;"></span>
				<?php printf( esc_html__( 'Sida: %s (%s)', 'cocookie' ), esc_html( get_the_title( $info['cookie_page_id'] ) ), esc_html( get_post_status( $info['cookie_page_id'] ) ) ); ?>
				— <a href="<?php echo esc_url( get_edit_post_link( $info['cookie_page_id'] ) ); ?>"><?php esc_html_e( 'Redigera sida', 'cocookie' ); ?></a>
			</p>
		<?php endif; ?>

		<details class="cocookie-preview">
			<summary>
				<span class="dashicons dashicons-visibility" style="font-size:14px;width:14px;height:14px;"></span>
				<?php esc_html_e( 'Förhandsgranska genererad policy', 'cocookie' ); ?>
			</summary>
			<div class="cocookie-preview__content"><?php echo wp_kses_post( $cookie_content ); ?></div>
		</details>

		<form method="post" style="margin-top:16px;">
			<?php wp_nonce_field( 'cocookie_policy_create' ); ?>
			<input type="hidden" name="policy_type" value="cookie">
			<?php submit_button(
				$info['cookie_page_id'] ? __( 'Uppdatera WordPress-sida', 'cocookie' ) : __( 'Skapa WordPress-sida (utkast)', 'cocookie' ),
				'secondary',
				'cocookie_create_policy',
				false
			); ?>
			<p class="description" style="margin-top:8px;"><?php esc_html_e( 'Skapar eller uppdaterar en WordPress-sida med policytexten.', 'cocookie' ); ?></p>
		</form>

	</div>
</div>

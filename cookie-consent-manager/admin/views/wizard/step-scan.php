<?php
/**
 * Wizard Steg 1: Skanna
 *
 * @package CoCookie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="cocookie-wizard__content">

	<!-- Hero-ikon -->
	<div class="cocookie-wizard__icon-hero cocookie-wizard__icon-hero--scan">
		<span class="dashicons dashicons-search"></span>
	</div>

	<h2><?php esc_html_e( 'Välkommen till CoCookie', 'cocookie' ); ?></h2>
	<p class="cocookie-wizard__desc">
		<?php esc_html_e( 'Vi börjar med att skanna din webbplats för att identifiera vilka cookies som sätts på dina besökare. Detta tar normalt 10–15 sekunder.', 'cocookie' ); ?>
	</p>

	<div class="cocookie-wizard__action-area">

		<button type="button" id="cocookie-wizard-scan" class="button button-primary button-hero">
			<span class="dashicons dashicons-search" style="font-size:18px;width:18px;height:18px;vertical-align:middle;margin-right:6px;margin-top:-2px;"></span>
			<?php esc_html_e( 'Starta skanning', 'cocookie' ); ?>
		</button>

		<div id="cocookie-wizard-scan-status" style="display:none;" class="cocookie-status-bar" style="margin-top:20px;">
			<span class="spinner is-active"></span>
			<span id="cocookie-wizard-scan-text"><?php esc_html_e( 'Skannar din webbplats...', 'cocookie' ); ?></span>
		</div>

		<div id="cocookie-wizard-scan-done" style="display:none;" class="cocookie-wizard__result">
			<span class="dashicons dashicons-yes-alt cocookie-icon--success" style="font-size:52px;width:52px;height:52px;display:block;margin:0 auto 12px;"></span>
			<p id="cocookie-wizard-scan-summary" style="font-size:15px;font-weight:600;color:var(--cocookie-neutral-800);margin-bottom:20px;"></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cocookie-wizard&step=2' ) ); ?>" class="button button-primary button-hero">
				<?php esc_html_e( 'Granska resultat', 'cocookie' ); ?> &rarr;
			</a>
		</div>

	</div>

	<p class="cocookie-wizard__skip">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=cocookie-wizard&step=3' ) ); ?>">
			<?php esc_html_e( 'Hoppa över skanning', 'cocookie' ); ?> &rarr;
		</a>
	</p>

</div>

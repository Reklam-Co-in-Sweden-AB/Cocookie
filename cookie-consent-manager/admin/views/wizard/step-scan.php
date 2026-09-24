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

			<?php // Varningar när analytics-cookies saknas — dolda tills JavaScript avgjort vilken som gäller. ?>
			<div id="cocookie-wizard-hint-ga_logged_in" class="cocookie-notice cocookie-notice--warning" style="display:none;text-align:left;">
				<span class="dashicons dashicons-warning"></span>
				<p>
					<strong><?php esc_html_e( 'Google Analytics hittades inte, men finns på sajten.', 'cocookie' ); ?></strong><br>
					<?php esc_html_e( 'Ditt analytics-plugin undantar inloggade administratörer, och skanningen körs i din inloggade webbläsare. Besökare får cookien ändå. Stäng tillfälligt av undantaget i pluginens inställningar och skanna igen, eller lägg till _ga och _ga_* manuellt under Cookies när du är klar med guiden.', 'cocookie' ); ?>
				</p>
			</div>
			<div id="cocookie-wizard-hint-ga_blocked" class="cocookie-notice cocookie-notice--warning" style="display:none;text-align:left;">
				<span class="dashicons dashicons-warning"></span>
				<p>
					<strong><?php esc_html_e( 'Google Analytics laddades men satte inga cookies.', 'cocookie' ); ?></strong><br>
					<?php esc_html_e( 'Vanliga orsaker: ett cache-plugin fördröjer JavaScript tills besökaren rör musen (WP Rocket, Perfmatters, FlyingPress), en GTM-container väntar på samtycke, eller en adblocker. Stäng tillfälligt av fördröjningen respektive adblockern och skanna igen.', 'cocookie' ); ?>
				</p>
			</div>
			<div id="cocookie-wizard-hint-cross_origin" class="cocookie-notice cocookie-notice--error" style="display:none;text-align:left;">
				<span class="dashicons dashicons-dismiss"></span>
				<p>
					<strong><?php esc_html_e( 'Skannern kunde inte läsa webbplatsen.', 'cocookie' ); ?></strong><br>
					<?php esc_html_e( 'Webbplatsadressen och adminadressen skiljer sig åt (www/utan www eller http/https). Kontrollera Inställningar → Allmänt och skanna igen.', 'cocookie' ); ?>
				</p>
			</div>
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

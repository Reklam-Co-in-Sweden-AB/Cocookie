<?php
/**
 * Iframe placeholder template.
 *
 * Replaces blocked third-party iframes until consent is given.
 *
 * @package CoCookie
 * @var string $category    Consent category slug.
 * @var string $src         Original iframe src.
 * @var string $provider    Provider name (e.g. "YouTube").
 * @var string $icon        HTML entity for icon.
 * @var string $style_attr  Inline style attribute for dimensions.
 * @var string $extra_class Extra CSS classes from original iframe.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$category_label = 'analytics' === $category
	? __( 'analys', 'cocookie' )
	: __( 'marknadsförings', 'cocookie' );
?>
<div class="cocookie-iframe-placeholder <?php echo esc_attr( $extra_class ); ?>"
	data-cc-category="<?php echo esc_attr( $category ); ?>"
	data-cc-src="<?php echo esc_attr( $src ); ?>"
	<?php echo $style_attr; ?>>
	<div class="cocookie-iframe-placeholder__inner">
		<span class="cocookie-iframe-placeholder__icon"><?php echo $icon; ?></span>
		<?php if ( $provider ) : ?>
			<p><?php
				printf(
					/* translators: %s: provider name */
					esc_html__( 'Det här innehållet tillhandahålls av %s.', 'cocookie' ),
					'<strong>' . esc_html( $provider ) . '</strong>'
				);
			?></p>
		<?php endif; ?>
		<p><?php
			printf(
				/* translators: %s: category name */
				esc_html__( 'Klicka för att godkänna %s-cookies och ladda innehållet.', 'cocookie' ),
				'<strong>' . esc_html( $category_label ) . '</strong>'
			);
		?></p>
		<button class="cocookie-iframe-placeholder__btn" type="button">
			<?php esc_html_e( 'Godkänn och visa', 'cocookie' ); ?>
		</button>
	</div>
</div>

<?php
/**
 * Main glossary shortcode template.
 *
 * @package CloudGlossary
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php
$layout_style = sprintf(
	'--cg-width-desktop:%1$s;--cg-padding-desktop:%2$s;--cg-width-tablet:%3$s;--cg-padding-tablet:%4$s;--cg-width-mobile:%5$s;--cg-padding-mobile:%6$s;',
	esc_attr( $layout['desktop_width'] ),
	esc_attr( $layout['desktop_padding'] ),
	esc_attr( $layout['tablet_width'] ),
	esc_attr( $layout['tablet_padding'] ),
	esc_attr( $layout['mobile_width'] ),
	esc_attr( $layout['mobile_padding'] )
);
?>
<div class="cg-glossary-wrapper" data-theme="light" style="<?php echo esc_attr( $layout_style ); ?>">
	<div class="cg-toolbar cg-raised">
		<input type="search" class="cg-search cg-sunken" id="cg-search" />
		<button type="button" id="cg-theme-toggle" class="cg-theme-toggle cg-raised"></button>
	</div>
	<div id="cg-root" class="cg-root" data-endpoint="<?php echo esc_url( $endpoint ); ?>" aria-live="polite"></div>
</div>

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
<div class="cg-glossary-wrapper" data-theme="light">
	<div class="cg-toolbar cg-raised">
		<input type="search" class="cg-search cg-sunken" id="cg-search" />
		<button type="button" id="cg-theme-toggle" class="cg-theme-toggle cg-raised"></button>
	</div>
	<div id="cg-root" class="cg-root" data-endpoint="<?php echo esc_url( $endpoint ); ?>" aria-live="polite"></div>
</div>

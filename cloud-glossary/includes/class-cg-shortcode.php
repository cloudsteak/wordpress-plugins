<?php
/**
 * Shortcode renderer for the Cloud Glossary frontend.
 *
 * @package CloudGlossary
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CG_Shortcode {

	/**
	 * Register hooks.
	 */
	public function init() {
		add_shortcode( 'cloud_glossary', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render shortcode output and enqueue assets.
	 *
	 * @return string
	 */
	public function render_shortcode() {
		wp_enqueue_style( 'cg-glossary', CG_PLUGIN_URL . 'assets/css/glossary.css', array(), CG_VERSION );
		wp_enqueue_script( 'cg-glossary', CG_PLUGIN_URL . 'assets/js/glossary.js', array(), CG_VERSION, true );
		wp_localize_script(
			'cg-glossary',
			'cgGlossary',
			array(
				'themeStorageKey' => 'cg-theme',
				'i18n'            => array(
					'loading'          => __( 'Betöltés...', 'cloud-glossary' ),
					'error'            => __( 'Nem sikerült betölteni a szolgáltatásokat.', 'cloud-glossary' ),
					'searchPlaceholder' => __( 'Keresés szolgáltatásnév vagy leírás alapján...', 'cloud-glossary' ),
					'expand'           => __( 'Kategória megnyitása', 'cloud-glossary' ),
					'collapse'         => __( 'Kategória bezárása', 'cloud-glossary' ),
					'noPosts'          => __( 'Nincs kapcsolódó bejegyzés', 'cloud-glossary' ),
					'morePosts'        => __( '+%d további', 'cloud-glossary' ),
					'light'            => __( 'Világos', 'cloud-glossary' ),
					'dark'             => __( 'Sötét', 'cloud-glossary' ),
					'providerAws'      => __( 'AWS', 'cloud-glossary' ),
					'providerAzure'    => __( 'Azure', 'cloud-glossary' ),
					'providerGcp'      => __( 'GCP', 'cloud-glossary' ),
					'providerGeneric'  => __( 'Általános', 'cloud-glossary' ),
				),
			)
		);

		$endpoint = rest_url( 'cloud-glossary/v1/services' );

		ob_start();
		require CG_PLUGIN_DIR . 'templates/glossary-main.php';
		return (string) ob_get_clean();
	}
}

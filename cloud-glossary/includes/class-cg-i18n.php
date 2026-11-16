<?php
/**
 * Internationalization loader.
 *
 * @package CloudGlossary
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CG_I18n {

	/**
	 * Register i18n hooks.
	 */
	public function init() {
		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );
	}

	/**
	 * Load plugin translations.
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'cloud-glossary', false, dirname( plugin_basename( CG_PLUGIN_FILE ) ) . '/languages' );
	}
}

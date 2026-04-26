<?php
/**
 * Main plugin bootstrap class.
 *
 * @package CloudGlossary
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CG_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var CG_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return CG_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register hooks.
	 */
	public function init() {
		$cpt   = new CG_CPT();
		$i18n  = new CG_I18n();
		$meta  = new CG_Meta();
		$admin = new CG_Admin();
		$rest  = new CG_Rest();

		$cpt->init();
		$i18n->init();
		$meta->init();
		$admin->init();
		$rest->init();
	}

	/**
	 * Prevent direct construction.
	 */
	private function __construct() {
	}
}

<?php
/**
 * Plugin Name:       Cloud Glossary
 * Plugin URI:        https://cloudmentor.hu/
 * Description:       Interactive cloud services glossary for AWS, Azure, and GCP.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Cloudmentor
 * Author URI:        https://cloudmentor.hu/
 * Text Domain:       cloud-glossary
 * Domain Path:       /languages
 *
 * @package CloudGlossary
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CG_VERSION', '0.1.0' );
define( 'CG_PLUGIN_FILE', __FILE__ );
define( 'CG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoload CG_* classes from includes/.
 *
 * @param string $class_name Class name.
 */
function cg_autoload( $class_name ) {
	if ( 0 !== strpos( $class_name, 'CG_' ) ) {
		return;
	}

	$file_name = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
	$file_path = CG_PLUGIN_DIR . 'includes/' . $file_name;

	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
}

spl_autoload_register( 'cg_autoload' );

add_action(
	'plugins_loaded',
	static function() {
		CG_Plugin::instance()->init();
	}
);

/**
 * Activation hook callback.
 */
function cg_activate_plugin() {
	CG_CPT::register();
	CG_CPT::seed_default_terms();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'cg_activate_plugin' );

/**
 * Deactivation hook callback.
 */
function cg_deactivate_plugin() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'cg_deactivate_plugin' );

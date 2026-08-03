<?php
/**
 * Uninstall Registration Guard.
 *
 * @package Registration_Guard
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once plugin_dir_path( __FILE__ ) . 'includes/class-rgsp-logger.php';

delete_option( 'reg_guard_enabled' );
delete_option( 'reg_guard_time_trap_seconds' );
delete_option( 'reg_guard_time_trap_max_age' );
delete_option( 'reg_guard_rate_limit' );
delete_option( 'reg_guard_rate_limit_window' );
delete_option( 'reg_guard_honeypot_field' );
delete_option( 'reg_guard_logging_enabled' );

RGSP_Logger::drop_table();

global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_reg_guard_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_reg_guard_' ) . '%'
	)
);

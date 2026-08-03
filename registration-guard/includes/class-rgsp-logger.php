<?php
/**
 * Rejected registration attempt logger.
 *
 * @package Registration_Guard
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles logging of rejected signup attempts to a custom database table.
 */
class RGSP_Logger {

	/**
	 * Maximum number of log entries to retain.
	 *
	 * @var int
	 */
	const MAX_ENTRIES = 500;

	/**
	 * Database table name suffix (without prefix).
	 *
	 * @var string
	 */
	const TABLE_SUFFIX = 'reg_guard_logs';

	/**
	 * Get the fully qualified log table name.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . self::TABLE_SUFFIX;
	}

	/**
	 * Create the log table on plugin activation.
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			logged_at datetime NOT NULL,
			ip_address varchar(45) NOT NULL,
			reason varchar(50) NOT NULL,
			PRIMARY KEY  (id),
			KEY logged_at (logged_at)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Drop the log table (used during uninstall).
	 *
	 * @return void
	 */
	public static function drop_table() {
		global $wpdb;

		$table_name = self::get_table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is controlled internally.
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
	}

	/**
	 * Check whether logging is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) get_option( 'reg_guard_logging_enabled', true );
	}

	/**
	 * Log a rejected registration attempt.
	 *
	 * @param string $reason Rejection reason slug (honeypot, time-trap, rate-limit, token).
	 * @param string $ip     Client IP address.
	 * @return void
	 */
	public function log( $reason, $ip ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		global $wpdb;

		$table_name = self::get_table_name();

		$wpdb->insert(
			$table_name,
			array(
				'logged_at'  => current_time( 'mysql', true ),
				'ip_address' => sanitize_text_field( $ip ),
				'reason'     => sanitize_key( $reason ),
			),
			array( '%s', '%s', '%s' )
		);

		$this->trim_old_entries();
	}

	/**
	 * Remove oldest entries when the log exceeds the maximum size.
	 *
	 * @return void
	 */
	private function trim_old_entries() {
		global $wpdb;

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is controlled internally.
		$count = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_name}" );

		if ( $count <= self::MAX_ENTRIES ) {
			return;
		}

		$excess = $count - self::MAX_ENTRIES;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is controlled internally.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} ORDER BY id ASC LIMIT %d",
				$excess
			)
		);
	}

	/**
	 * Retrieve recent log entries for the admin table.
	 *
	 * @param int $limit Maximum number of entries to return.
	 * @return array<int, object>
	 */
	public function get_entries( $limit = 500 ) {
		global $wpdb;

		$table_name = self::get_table_name();
		$limit      = max( 1, min( self::MAX_ENTRIES, absint( $limit ) ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is controlled internally.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, logged_at, ip_address, reason FROM {$table_name} ORDER BY id DESC LIMIT %d",
				$limit
			)
		);

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Clear all log entries.
	 *
	 * @return void
	 */
	public function clear_log() {
		global $wpdb;

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is controlled internally.
		$wpdb->query( "TRUNCATE TABLE {$table_name}" );
	}

	/**
	 * Return a human-readable label for a rejection reason.
	 *
	 * @param string $reason Reason slug.
	 * @return string
	 */
	public static function get_reason_label( $reason ) {
		$labels = array(
			'honeypot'   => __( 'Honeypot', 'registration-guard' ),
			'time-trap'  => __( 'Time trap', 'registration-guard' ),
			'rate-limit' => __( 'Rate limit', 'registration-guard' ),
			'token'      => __( 'Invalid token', 'registration-guard' ),
		);

		return isset( $labels[ $reason ] ) ? $labels[ $reason ] : $reason;
	}
}

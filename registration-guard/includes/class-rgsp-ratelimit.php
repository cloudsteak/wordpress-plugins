<?php
/**
 * IP-based registration rate limiting.
 *
 * @package Registration_Guard
 */

defined( 'ABSPATH' ) || exit;

/**
 * Limits registration attempts per IP address using WordPress transients.
 */
class RGSP_RateLimit {

	/**
	 * Transient key prefix for rate limit counters.
	 *
	 * @var string
	 */
	const TRANSIENT_PREFIX = 'reg_guard_rl_';

	/**
	 * Logger instance.
	 *
	 * @var RGSP_Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param RGSP_Logger $logger Logger instance.
	 */
	public function __construct( RGSP_Logger $logger ) {
		$this->logger = $logger;

		add_filter( 'registration_errors', array( $this, 'validate' ), 5, 3 );
		add_filter( 'registration_errors', array( $this, 'record_attempt' ), 99, 3 );
	}

	/**
	 * Get the configured maximum attempts per window.
	 *
	 * @return int
	 */
	public function get_limit() {
		return max( 1, absint( get_option( 'reg_guard_rate_limit', 3 ) ) );
	}

	/**
	 * Get the rate limit window in seconds.
	 *
	 * @return int
	 */
	public function get_window() {
		return max( 60, absint( get_option( 'reg_guard_rate_limit_window', HOUR_IN_SECONDS ) ) );
	}

	/**
	 * Build the transient key for an IP address.
	 *
	 * @param string $ip Client IP address.
	 * @return string
	 */
	private function get_transient_key( $ip ) {
		return self::TRANSIENT_PREFIX . md5( $ip );
	}

	/**
	 * Get the current attempt count for an IP address.
	 *
	 * @param string $ip Client IP address.
	 * @return int
	 */
	public function get_attempt_count( $ip ) {
		$count = get_transient( $this->get_transient_key( $ip ) );

		return false === $count ? 0 : absint( $count );
	}

	/**
	 * Increment the attempt counter for an IP address.
	 *
	 * @param string $ip Client IP address.
	 * @return void
	 */
	public function increment_attempt( $ip ) {
		$key   = $this->get_transient_key( $ip );
		$count = $this->get_attempt_count( $ip ) + 1;

		set_transient( $key, $count, $this->get_window() );
	}

	/**
	 * Validate whether the IP address has exceeded the rate limit.
	 *
	 * @param WP_Error $errors               Registration errors object.
	 * @param string   $sanitized_user_login Sanitized username.
	 * @param string   $user_email           User email address.
	 * @return WP_Error
	 */
	public function validate( $errors, $sanitized_user_login, $user_email ) {
		unset( $sanitized_user_login, $user_email );

		if ( ! reg_guard_is_protection_enabled() ) {
			return $errors;
		}

		$ip    = reg_guard_get_client_ip();
		$count = $this->get_attempt_count( $ip );
		$limit = $this->get_limit();

		if ( $count >= $limit ) {
			$this->logger->log( 'rate-limit', $ip );

			$errors->add(
				'reg_guard_rate_limit',
				sprintf(
					/* translators: %d: maximum number of registration attempts allowed per hour per IP address. */
					__( 'Too many registration attempts from your IP address. Please wait before trying again. Maximum allowed: %d attempts per hour.', 'registration-guard' ),
					$limit
				)
			);
		}

		return $errors;
	}

	/**
	 * Record a registration attempt after other validations run.
	 *
	 * @param WP_Error $errors               Registration errors object.
	 * @param string   $sanitized_user_login Sanitized username.
	 * @param string   $user_email           User email address.
	 * @return WP_Error
	 */
	public function record_attempt( $errors, $sanitized_user_login, $user_email ) {
		unset( $sanitized_user_login, $user_email );

		if ( ! reg_guard_is_protection_enabled() ) {
			return $errors;
		}

		$ip = reg_guard_get_client_ip();
		$this->increment_attempt( $ip );

		return $errors;
	}
}

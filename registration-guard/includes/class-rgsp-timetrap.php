<?php
/**
 * Time-trap and token verification for the registration form.
 *
 * @package Registration_Guard
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds signed timestamp and session token fields, then validates them on submit.
 */
class RGSP_TimeTrap {

	/**
	 * Hidden field name for the signed timestamp payload.
	 *
	 * @var string
	 */
	const TIMESTAMP_FIELD = 'reg_guard_ts';

	/**
	 * Hidden field name for the form token.
	 *
	 * @var string
	 */
	const TOKEN_FIELD = 'reg_guard_token';

	/**
	 * Transient key prefix for stored form tokens.
	 *
	 * @var string
	 */
	const TOKEN_TRANSIENT_PREFIX = 'reg_guard_tk_';

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

		add_action( 'register_form', array( $this, 'render_fields' ) );
		add_filter( 'registration_errors', array( $this, 'validate' ), 10, 3 );
	}

	/**
	 * Get the minimum seconds a user must spend on the form.
	 *
	 * @return int
	 */
	public function get_min_seconds() {
		return max( 1, absint( get_option( 'reg_guard_time_trap_seconds', 3 ) ) );
	}

	/**
	 * Get the maximum age of a form submission in seconds.
	 *
	 * @return int
	 */
	public function get_max_age_seconds() {
		return max( 60, absint( get_option( 'reg_guard_time_trap_max_age', HOUR_IN_SECONDS ) ) );
	}

	/**
	 * Build a transient key for the current client token.
	 *
	 * @param string $ip Client IP address.
	 * @return string
	 */
	private function get_token_transient_key( $ip ) {
		return self::TOKEN_TRANSIENT_PREFIX . md5( $ip );
	}

	/**
	 * Generate a new form token and store it for the current client.
	 *
	 * @param string $ip Client IP address.
	 * @return string
	 */
	private function generate_token( $ip ) {
		$token = wp_generate_password( 32, false, false );
		$key   = $this->get_token_transient_key( $ip );

		set_transient( $key, $token, $this->get_max_age_seconds() );

		return $token;
	}

	/**
	 * Create a signed timestamp payload.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @return string
	 */
	private function sign_timestamp( $timestamp ) {
		$payload   = (string) absint( $timestamp );
		$signature = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );

		return base64_encode( $payload . '|' . $signature );
	}

	/**
	 * Verify and decode a signed timestamp payload.
	 *
	 * @param string $encoded Signed payload from the form.
	 * @return int|false Unix timestamp on success, false on failure.
	 */
	private function verify_timestamp( $encoded ) {
		$decoded = base64_decode( $encoded, true );

		if ( false === $decoded || false === strpos( $decoded, '|' ) ) {
			return false;
		}

		list( $payload, $signature ) = explode( '|', $decoded, 2 );

		if ( ! hash_equals( hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) ), $signature ) ) {
			return false;
		}

		$timestamp = absint( $payload );

		return $timestamp > 0 ? $timestamp : false;
	}

	/**
	 * Render hidden timestamp and token fields.
	 *
	 * @return void
	 */
	public function render_fields() {
		if ( ! reg_guard_is_protection_enabled() ) {
			return;
		}

		$ip        = reg_guard_get_client_ip();
		$token     = $this->generate_token( $ip );
		$timestamp = $this->sign_timestamp( time() );
		?>
		<input type="hidden" name="<?php echo esc_attr( self::TIMESTAMP_FIELD ); ?>" value="<?php echo esc_attr( $timestamp ); ?>" />
		<input type="hidden" name="<?php echo esc_attr( self::TOKEN_FIELD ); ?>" value="<?php echo esc_attr( $token ); ?>" />
		<?php
	}

	/**
	 * Validate token and time-trap fields on registration submission.
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

		$ip = reg_guard_get_client_ip();

		if ( ! $this->validate_token( $ip, $errors ) ) {
			return $errors;
		}

		if ( $errors->get_error_code() ) {
			return $errors;
		}

		$this->validate_time_trap( $ip, $errors );

		return $errors;
	}

	/**
	 * Validate the form token against the stored transient value.
	 *
	 * @param string   $ip     Client IP address.
	 * @param WP_Error $errors Registration errors object.
	 * @return bool True when valid, false when rejected.
	 */
	private function validate_token( $ip, $errors ) {
		if ( ! isset( $_POST[ self::TOKEN_FIELD ] ) ) {
			$this->logger->log( 'token', $ip );
			$errors->add(
				'reg_guard_token',
				__( 'Registration could not be completed. Please try again.', 'registration-guard' )
			);
			return false;
		}

		$submitted = sanitize_text_field( wp_unslash( $_POST[ self::TOKEN_FIELD ] ) );
		$key       = $this->get_token_transient_key( $ip );
		$stored    = get_transient( $key );

		delete_transient( $key );

		if ( false === $stored || ! hash_equals( (string) $stored, $submitted ) ) {
			$this->logger->log( 'token', $ip );
			$errors->add(
				'reg_guard_token',
				__( 'Registration could not be completed. Please try again.', 'registration-guard' )
			);
			return false;
		}

		return true;
	}

	/**
	 * Validate the signed timestamp against configured thresholds.
	 *
	 * @param string   $ip     Client IP address.
	 * @param WP_Error $errors Registration errors object.
	 * @return void
	 */
	private function validate_time_trap( $ip, $errors ) {
		if ( ! isset( $_POST[ self::TIMESTAMP_FIELD ] ) ) {
			$this->logger->log( 'time-trap', $ip );
			$errors->add(
				'reg_guard_time_trap',
				__( 'Registration could not be completed. Please try again.', 'registration-guard' )
			);
			return;
		}

		$encoded   = sanitize_text_field( wp_unslash( $_POST[ self::TIMESTAMP_FIELD ] ) );
		$loaded_at = $this->verify_timestamp( $encoded );

		if ( false === $loaded_at ) {
			$this->logger->log( 'time-trap', $ip );
			$errors->add(
				'reg_guard_time_trap',
				__( 'Registration could not be completed. Please try again.', 'registration-guard' )
			);
			return;
		}

		$elapsed = time() - $loaded_at;
		$min     = $this->get_min_seconds();
		$max     = $this->get_max_age_seconds();

		if ( $elapsed < $min ) {
			$this->logger->log( 'time-trap', $ip );
			$errors->add(
				'reg_guard_time_trap',
				__( 'Registration could not be completed. Please try again.', 'registration-guard' )
			);
			return;
		}

		if ( $elapsed > $max ) {
			$this->logger->log( 'time-trap', $ip );
			$errors->add(
				'reg_guard_time_trap_expired',
				__( 'This registration form has expired. Please reload the page and try again.', 'registration-guard' )
			);
		}
	}
}

<?php
/**
 * Honeypot field handler for the registration form.
 *
 * @package Registration_Guard
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds and validates a hidden honeypot field on the registration form.
 */
class RGSP_Honeypot {

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

		add_action( 'register_form', array( $this, 'render_field' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_filter( 'registration_errors', array( $this, 'validate' ), 10, 3 );
	}

	/**
	 * Get the configured honeypot field name.
	 *
	 * @return string
	 */
	public function get_field_name() {
		$name = get_option( 'reg_guard_honeypot_field', 'reg_guard_contact_url' );
		$name = sanitize_key( $name );

		if ( empty( $name ) ) {
			$name = 'reg_guard_contact_url';
		}

		return $name;
	}

	/**
	 * Enqueue CSS that hides the honeypot field from humans and many bots.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		if ( ! $this->should_load_assets() ) {
			return;
		}

		wp_enqueue_style(
			'registration-guard-hidden-field',
			RGSP_PLUGIN_URL . 'assets/css/hidden-field.css',
			array(),
			RGSP_VERSION
		);
	}

	/**
	 * Determine whether registration assets should load on the current screen.
	 *
	 * @return bool
	 */
	private function should_load_assets() {
		if ( ! reg_guard_is_protection_enabled() ) {
			return false;
		}

		global $pagenow;

		if ( 'wp-login.php' === $pagenow ) {
			$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'login';
			return 'register' === $action;
		}

		return true;
	}

	/**
	 * Render the honeypot field on the registration form.
	 *
	 * @return void
	 */
	public function render_field() {
		if ( ! reg_guard_is_protection_enabled() ) {
			return;
		}

		$field_name = $this->get_field_name();
		$field_id   = 'reg-guard-hp-' . $field_name;
		?>
		<p class="reg-guard-hp-wrap" aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;display:none;">
			<label for="<?php echo esc_attr( $field_id ); ?>">
				<?php esc_html_e( 'Website', 'registration-guard' ); ?>
			</label>
			<input
				type="text"
				name="<?php echo esc_attr( $field_name ); ?>"
				id="<?php echo esc_attr( $field_id ); ?>"
				value=""
				tabindex="-1"
				autocomplete="off"
			/>
		</p>
		<?php
	}

	/**
	 * Validate the honeypot field on registration submission.
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

		$field_name = $this->get_field_name();

		if ( ! isset( $_POST[ $field_name ] ) ) {
			return $errors;
		}

		$value = sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) );

		if ( '' !== $value ) {
			$ip = reg_guard_get_client_ip();
			$this->logger->log( 'honeypot', $ip );

			$errors->add(
				'reg_guard_honeypot',
				__( 'Registration could not be completed. Please try again.', 'registration-guard' )
			);
		}

		return $errors;
	}
}

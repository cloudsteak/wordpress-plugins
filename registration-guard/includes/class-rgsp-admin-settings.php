<?php
/**
 * Admin settings page for Registration Guard.
 *
 * @package Registration_Guard
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders plugin settings under Settings > Registration Guard.
 */
class RGSP_Admin_Settings {

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'registration-guard';

	/**
	 * Settings group identifier.
	 *
	 * @var string
	 */
	const SETTINGS_GROUP = 'reg_guard_settings';

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

		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_reg_guard_clear_log', array( $this, 'handle_clear_log' ) );
	}

	/**
	 * Add the settings page under the Settings menu.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Registration Guard', 'registration-guard' ),
			__( 'Registration Guard', 'registration-guard' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings, sections, and fields via the Settings API.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::SETTINGS_GROUP,
			'reg_guard_enabled',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				'default'           => true,
			)
		);

		register_setting(
			self::SETTINGS_GROUP,
			'reg_guard_time_trap_seconds',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_time_trap_seconds' ),
				'default'           => 3,
			)
		);

		register_setting(
			self::SETTINGS_GROUP,
			'reg_guard_rate_limit',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_rate_limit' ),
				'default'           => 3,
			)
		);

		register_setting(
			self::SETTINGS_GROUP,
			'reg_guard_honeypot_field',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_honeypot_field' ),
				'default'           => 'reg_guard_contact_url',
			)
		);

		register_setting(
			self::SETTINGS_GROUP,
			'reg_guard_logging_enabled',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				'default'           => true,
			)
		);

		add_settings_section(
			'reg_guard_section_protection',
			__( 'Protection Settings', 'registration-guard' ),
			array( $this, 'render_protection_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'reg_guard_enabled',
			__( 'Enable protection', 'registration-guard' ),
			array( $this, 'field_enabled' ),
			self::PAGE_SLUG,
			'reg_guard_section_protection'
		);

		add_settings_field(
			'reg_guard_time_trap_seconds',
			__( 'Time-trap threshold (seconds)', 'registration-guard' ),
			array( $this, 'field_time_trap_seconds' ),
			self::PAGE_SLUG,
			'reg_guard_section_protection'
		);

		add_settings_field(
			'reg_guard_rate_limit',
			__( 'Rate limit (attempts per hour per IP)', 'registration-guard' ),
			array( $this, 'field_rate_limit' ),
			self::PAGE_SLUG,
			'reg_guard_section_protection'
		);

		add_settings_field(
			'reg_guard_honeypot_field',
			__( 'Honeypot field name', 'registration-guard' ),
			array( $this, 'field_honeypot_field' ),
			self::PAGE_SLUG,
			'reg_guard_section_protection'
		);

		add_settings_field(
			'reg_guard_logging_enabled',
			__( 'Enable logging', 'registration-guard' ),
			array( $this, 'field_logging_enabled' ),
			self::PAGE_SLUG,
			'reg_guard_section_protection'
		);
	}

	/**
	 * Sanitize a checkbox value to boolean.
	 *
	 * @param mixed $value Submitted value.
	 * @return bool
	 */
	public function sanitize_checkbox( $value ) {
		return ! empty( $value );
	}

	/**
	 * Sanitize the time-trap threshold.
	 *
	 * @param mixed $value Submitted value.
	 * @return int
	 */
	public function sanitize_time_trap_seconds( $value ) {
		$value = absint( $value );

		return max( 1, min( 60, $value ) );
	}

	/**
	 * Sanitize the rate limit value.
	 *
	 * @param mixed $value Submitted value.
	 * @return int
	 */
	public function sanitize_rate_limit( $value ) {
		$value = absint( $value );

		return max( 1, min( 100, $value ) );
	}

	/**
	 * Sanitize the honeypot field name.
	 *
	 * @param mixed $value Submitted value.
	 * @return string
	 */
	public function sanitize_honeypot_field( $value ) {
		$value = sanitize_key( $value );

		if ( empty( $value ) ) {
			return 'reg_guard_contact_url';
		}

		return $value;
	}

	/**
	 * Render the protection settings section description.
	 *
	 * @return void
	 */
	public function render_protection_section() {
		echo '<p>' . esc_html__( 'Configure honeypot, time-trap, and rate limiting for the WordPress registration form.', 'registration-guard' ) . '</p>';
	}

	/**
	 * Render the enable protection checkbox field.
	 *
	 * @return void
	 */
	public function field_enabled() {
		$value = (bool) get_option( 'reg_guard_enabled', true );
		?>
		<input type="hidden" name="reg_guard_enabled" value="0" />
		<label for="reg_guard_enabled">
			<input type="checkbox" name="reg_guard_enabled" id="reg_guard_enabled" value="1" <?php checked( $value ); ?> />
			<?php esc_html_e( 'Block bot registrations on the signup form', 'registration-guard' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'When disabled, all protection checks are bypassed.', 'registration-guard' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the time-trap threshold field.
	 *
	 * @return void
	 */
	public function field_time_trap_seconds() {
		$value = absint( get_option( 'reg_guard_time_trap_seconds', 3 ) );
		?>
		<input type="number" name="reg_guard_time_trap_seconds" id="reg_guard_time_trap_seconds" value="<?php echo esc_attr( $value ); ?>" min="1" max="60" step="1" class="small-text" />
		<p class="description">
			<?php esc_html_e( 'Minimum seconds a visitor must spend on the form before submitting. Submissions faster than this are rejected.', 'registration-guard' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the rate limit field.
	 *
	 * @return void
	 */
	public function field_rate_limit() {
		$value = absint( get_option( 'reg_guard_rate_limit', 3 ) );
		?>
		<input type="number" name="reg_guard_rate_limit" id="reg_guard_rate_limit" value="<?php echo esc_attr( $value ); ?>" min="1" max="100" step="1" class="small-text" />
		<p class="description">
			<?php esc_html_e( 'Maximum registration attempts allowed from a single IP address within one hour.', 'registration-guard' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the honeypot field name input.
	 *
	 * @return void
	 */
	public function field_honeypot_field() {
		$value = get_option( 'reg_guard_honeypot_field', 'reg_guard_contact_url' );
		?>
		<input type="text" name="reg_guard_honeypot_field" id="reg_guard_honeypot_field" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description">
			<?php esc_html_e( 'The HTML name attribute of the hidden honeypot field. Use a neutral name that does not reveal its purpose.', 'registration-guard' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the enable logging checkbox field.
	 *
	 * @return void
	 */
	public function field_logging_enabled() {
		$value = (bool) get_option( 'reg_guard_logging_enabled', true );
		?>
		<input type="hidden" name="reg_guard_logging_enabled" value="0" />
		<label for="reg_guard_logging_enabled">
			<input type="checkbox" name="reg_guard_logging_enabled" id="reg_guard_logging_enabled" value="1" <?php checked( $value ); ?> />
			<?php esc_html_e( 'Log rejected registration attempts', 'registration-guard' ); ?>
		</label>
		<?php
	}

	/**
	 * Handle the clear log form submission.
	 *
	 * @return void
	 */
	public function handle_clear_log() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'registration-guard' ) );
		}

		check_admin_referer( 'reg_guard_clear_log' );

		$this->logger->clear_log();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => self::PAGE_SLUG,
					'reg_guard_cleared' => '1',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Render the complete settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['reg_guard_cleared'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['reg_guard_cleared'] ) ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'The rejection log has been cleared.', 'registration-guard' ) . '</p></div>';
		}

		if ( isset( $_GET['settings-updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'registration-guard' ) . '</p></div>';
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button( __( 'Save Settings', 'registration-guard' ) );
				?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Rejected Registration Attempts', 'registration-guard' ); ?></h2>
			<p><?php esc_html_e( 'The most recent rejected signup attempts are listed below (up to 500 entries).', 'registration-guard' ); ?></p>

			<?php $this->render_log_table(); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 1em;">
				<?php wp_nonce_field( 'reg_guard_clear_log' ); ?>
				<input type="hidden" name="action" value="reg_guard_clear_log" />
				<?php submit_button( __( 'Clear Log', 'registration-guard' ), 'delete', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the rejected attempts log table.
	 *
	 * @return void
	 */
	private function render_log_table() {
		$entries = $this->logger->get_entries();
		?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Date (UTC)', 'registration-guard' ); ?></th>
					<th scope="col"><?php esc_html_e( 'IP Address', 'registration-guard' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Reason', 'registration-guard' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $entries ) ) : ?>
					<tr>
						<td colspan="3"><?php esc_html_e( 'No rejected attempts logged yet.', 'registration-guard' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $entries as $entry ) : ?>
						<tr>
							<td><?php echo esc_html( $entry->logged_at ); ?></td>
							<td><?php echo esc_html( $entry->ip_address ); ?></td>
							<td><?php echo esc_html( RGSP_Logger::get_reason_label( $entry->reason ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}
}

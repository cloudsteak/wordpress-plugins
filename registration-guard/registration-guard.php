<?php
/**
 * Plugin Name:       Registration Guard – Anti-Spam Signup Protection
 * Plugin URI:        https://github.com/cloudsteak/wordpress-plugins/registration-guard
 * Description:       Blocks bot registrations on the WordPress signup form using honeypot, time-trap, token verification, and IP rate limiting — no external API keys required.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Tested up to:      6.7.2
 * Requires PHP:      8.0
 * Author:            CloudMentor
 * Author URI:        https://cloudmentor.hu
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       registration-guard
 * Domain Path:       /languages
 *
 * @package Registration_Guard
 */

defined( 'ABSPATH' ) || exit;

define( 'RGSP_VERSION', '1.0.0' );
define( 'RGSP_PLUGIN_FILE', __FILE__ );
define( 'RGSP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RGSP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RGSP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once RGSP_PLUGIN_DIR . 'includes/class-rgsp-logger.php';
require_once RGSP_PLUGIN_DIR . 'includes/class-rgsp-honeypot.php';
require_once RGSP_PLUGIN_DIR . 'includes/class-rgsp-timetrap.php';
require_once RGSP_PLUGIN_DIR . 'includes/class-rgsp-ratelimit.php';
require_once RGSP_PLUGIN_DIR . 'includes/class-rgsp-admin-settings.php';

/**
 * Return the client IP address, preferring Cloudflare when present.
 *
 * @return string Valid IP address or 0.0.0.0 if unavailable.
 */
function reg_guard_get_client_ip() {
	if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}
	}

	$ip = isset( $_SERVER['REMOTE_ADDR'] )
		? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
		: '';

	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * Check whether signup protection is enabled.
 *
 * @return bool
 */
function reg_guard_is_protection_enabled() {
	return (bool) get_option( 'reg_guard_enabled', true );
}

/**
 * Main plugin bootstrap class.
 */
final class RGSP_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var RGSP_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Logger instance.
	 *
	 * @var RGSP_Logger
	 */
	public $logger;

	/**
	 * Honeypot handler instance.
	 *
	 * @var RGSP_Honeypot
	 */
	public $honeypot;

	/**
	 * Time-trap and token handler instance.
	 *
	 * @var RGSP_TimeTrap
	 */
	public $timetrap;

	/**
	 * Rate limit handler instance.
	 *
	 * @var RGSP_RateLimit
	 */
	public $ratelimit;

	/**
	 * Admin settings instance.
	 *
	 * @var RGSP_Admin_Settings
	 */
	public $admin;

	/**
	 * Get the singleton instance.
	 *
	 * @return RGSP_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		register_activation_hook( RGSP_PLUGIN_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( RGSP_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'registration-guard',
			false,
			dirname( RGSP_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Initialize plugin components.
	 *
	 * @return void
	 */
	public function init() {
		$this->logger    = new RGSP_Logger();
		$this->honeypot  = new RGSP_Honeypot( $this->logger );
		$this->timetrap  = new RGSP_TimeTrap( $this->logger );
		$this->ratelimit = new RGSP_RateLimit( $this->logger );

		if ( is_admin() ) {
			$this->admin = new RGSP_Admin_Settings( $this->logger );
		}
	}

	/**
	 * Plugin activation callback.
	 *
	 * @return void
	 */
	public static function activate() {
		RGSP_Logger::create_table();

		add_option( 'reg_guard_enabled', true );
		add_option( 'reg_guard_time_trap_seconds', 3 );
		add_option( 'reg_guard_time_trap_max_age', HOUR_IN_SECONDS );
		add_option( 'reg_guard_rate_limit', 3 );
		add_option( 'reg_guard_rate_limit_window', HOUR_IN_SECONDS );
		add_option( 'reg_guard_logging_enabled', true );
		add_option( 'reg_guard_honeypot_field', 'reg_guard_contact_url' );
	}

	/**
	 * Plugin deactivation callback.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Intentionally leave options and log table intact for reactivation.
	}
}

/**
 * Return the main plugin instance.
 *
 * @return RGSP_Plugin
 */
function reg_guard() {
	return RGSP_Plugin::instance();
}

reg_guard();

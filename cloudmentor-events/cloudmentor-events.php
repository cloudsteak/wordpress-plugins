<?php
/**
 * Plugin Name: CloudMentor Events
 * Plugin URI: https://github.com/cloudsteak/wordpress-plugins/cloudmentor-events
 * Description: Kompakt eseménylista Cloud és AI technológiai határidők megjelenítéséhez. Themify kompatibilis.
 * Version: 1.0.8
 * Author: CloudMentor
 * Author URI: https://cloudmentor.hu
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cloudmentor-events
 * Domain Path: /languages
 * Requires at least: 6.2
 * Tested up to: 6.7.2
 * Requires PHP: 8.0
 *
 * @package CloudMentor_Events
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'CME_VERSION', '1.0.8' );
define( 'CME_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CME_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CME_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class
 */
final class CloudMentor_Events {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once CME_PLUGIN_DIR . 'includes/class-cme-post-type.php';
        require_once CME_PLUGIN_DIR . 'includes/class-cme-meta-boxes.php';
        require_once CME_PLUGIN_DIR . 'includes/class-cme-shortcode.php';
        require_once CME_PLUGIN_DIR . 'includes/class-cme-widget.php';
        require_once CME_PLUGIN_DIR . 'includes/class-cme-settings.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Load textdomain
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // Enqueue assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // Register widget
        add_action( 'widgets_init', array( $this, 'register_widget' ) );

        // Activation hook
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'cloudmentor-events',
            false,
            dirname( CME_PLUGIN_BASENAME ) . '/languages'
        );
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'cloudmentor-events',
            CME_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            CME_VERSION
        );

        wp_enqueue_script(
            'cloudmentor-events',
            CME_PLUGIN_URL . 'assets/js/frontend.js',
            array( 'jquery' ),
            CME_VERSION,
            true
        );

        wp_localize_script( 'cloudmentor-events', 'cmeData', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'cme_nonce' ),
            'i18n'    => array(
                'showDetails' => __( 'Részletek megjelenítése', 'cloudmentor-events' ),
                'hideDetails' => __( 'Részletek elrejtése', 'cloudmentor-events' ),
            ),
        ) );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        global $post_type;

        if ( 'cloud_event' === $post_type || 'settings_page_cloudmentor-events' === $hook ) {
            wp_enqueue_style(
                'cloudmentor-events-admin',
                CME_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                CME_VERSION
            );

            // Date picker
            wp_enqueue_script( 'jquery-ui-datepicker' );
            wp_enqueue_style( 'jquery-ui-datepicker-style',
                'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css',
                array(),
                '1.13.2'
            );

            wp_enqueue_script(
                'cloudmentor-events-admin',
                CME_PLUGIN_URL . 'assets/js/admin.js',
                array( 'jquery', 'jquery-ui-datepicker' ),
                CME_VERSION,
                true
            );
        }
    }

    /**
     * Register widget
     */
    public function register_widget() {
        register_widget( 'CME_Events_Widget' );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Register post type and taxonomies
        CME_Post_Type::get_instance()->register_post_type();
        CME_Post_Type::get_instance()->register_taxonomies();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set default options
        $defaults = array(
            'events_count'    => 5,
            'date_format'     => 'hungarian',
            'show_category'   => true,
            'show_type'       => true,
            'animation'       => 'slide',
            'color_scheme'    => 'default',
        );

        if ( false === get_option( 'cme_settings' ) ) {
            add_option( 'cme_settings', $defaults );
        }
    }
}

// Initialize plugin
function cloudmentor_events() {
    return CloudMentor_Events::get_instance();
}

// Start the plugin
cloudmentor_events();

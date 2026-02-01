<?php
/**
 * Settings page for CloudMentor Events
 *
 * @package CloudMentor_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Settings class
 */
class CME_Settings {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Settings key
     */
    private $option_name = 'cme_settings';

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
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_filter( 'plugin_action_links_' . CME_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
    }

    /**
     * Add settings page to menu
     */
    public function add_settings_page() {
        add_options_page(
            __( 'CloudMentor Events Beállítások', 'cloudmentor-events' ),
            __( 'CloudMentor Events', 'cloudmentor-events' ),
            'manage_options',
            'cloudmentor-events',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'cme_settings_group',
            $this->option_name,
            array( $this, 'sanitize_settings' )
        );

        // Display section
        add_settings_section(
            'cme_display_section',
            __( 'Megjelenítési beállítások', 'cloudmentor-events' ),
            array( $this, 'render_display_section' ),
            'cloudmentor-events'
        );

        add_settings_field(
            'events_count',
            __( 'Események száma', 'cloudmentor-events' ),
            array( $this, 'render_events_count_field' ),
            'cloudmentor-events',
            'cme_display_section'
        );

        add_settings_field(
            'date_format',
            __( 'Dátum formátum', 'cloudmentor-events' ),
            array( $this, 'render_date_format_field' ),
            'cloudmentor-events',
            'cme_display_section'
        );

        add_settings_field(
            'show_category',
            __( 'Kategória megjelenítése', 'cloudmentor-events' ),
            array( $this, 'render_show_category_field' ),
            'cloudmentor-events',
            'cme_display_section'
        );

        add_settings_field(
            'show_type',
            __( 'Típus megjelenítése', 'cloudmentor-events' ),
            array( $this, 'render_show_type_field' ),
            'cloudmentor-events',
            'cme_display_section'
        );

        // Style section
        add_settings_section(
            'cme_style_section',
            __( 'Stílus beállítások', 'cloudmentor-events' ),
            array( $this, 'render_style_section' ),
            'cloudmentor-events'
        );

        add_settings_field(
            'color_scheme',
            __( 'Színséma', 'cloudmentor-events' ),
            array( $this, 'render_color_scheme_field' ),
            'cloudmentor-events',
            'cme_style_section'
        );

        add_settings_field(
            'animation',
            __( 'Animáció', 'cloudmentor-events' ),
            array( $this, 'render_animation_field' ),
            'cloudmentor-events',
            'cme_style_section'
        );

        // Past events section
        add_settings_section(
            'cme_past_events_section',
            __( 'Múltbeli események', 'cloudmentor-events' ),
            array( $this, 'render_past_events_section' ),
            'cloudmentor-events'
        );

        add_settings_field(
            'past_events_count',
            __( 'Múltbeli események száma', 'cloudmentor-events' ),
            array( $this, 'render_past_events_count_field' ),
            'cloudmentor-events',
            'cme_past_events_section'
        );

        add_settings_field(
            'past_events_days',
            __( 'Megjelenítési időszak', 'cloudmentor-events' ),
            array( $this, 'render_past_events_days_field' ),
            'cloudmentor-events',
            'cme_past_events_section'
        );
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();

        $sanitized['events_count'] = isset( $input['events_count'] ) ?
            absint( $input['events_count'] ) : 5;

        if ( $sanitized['events_count'] < 1 ) {
            $sanitized['events_count'] = 1;
        }
        if ( $sanitized['events_count'] > 20 ) {
            $sanitized['events_count'] = 20;
        }

        $sanitized['date_format'] = isset( $input['date_format'] ) &&
            in_array( $input['date_format'], array( 'hungarian', 'iso', 'relative' ), true ) ?
            $input['date_format'] : 'hungarian';

        $sanitized['show_category'] = isset( $input['show_category'] ) ? 1 : 0;
        $sanitized['show_type']     = isset( $input['show_type'] ) ? 1 : 0;

        $sanitized['color_scheme'] = isset( $input['color_scheme'] ) &&
            in_array( $input['color_scheme'], array( 'default', 'dark', 'minimal', 'colorful' ), true ) ?
            $input['color_scheme'] : 'default';

        $sanitized['animation'] = isset( $input['animation'] ) &&
            in_array( $input['animation'], array( 'slide', 'fade', 'none' ), true ) ?
            $input['animation'] : 'slide';

        // Past events settings
        $sanitized['past_events_count'] = isset( $input['past_events_count'] ) ?
            absint( $input['past_events_count'] ) : 0;
        if ( $sanitized['past_events_count'] > 10 ) {
            $sanitized['past_events_count'] = 10;
        }

        $sanitized['past_events_days'] = isset( $input['past_events_days'] ) ?
            absint( $input['past_events_days'] ) : 7;
        if ( ! in_array( $sanitized['past_events_days'], array( 0, 7, 14, 30 ), true ) ) {
            $sanitized['past_events_days'] = 7;
        }

        return $sanitized;
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap cme-settings-wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <div class="cme-settings-container">
                <div class="cme-settings-main">
                    <form action="options.php" method="post">
                        <?php
                        settings_fields( 'cme_settings_group' );
                        do_settings_sections( 'cloudmentor-events' );
                        submit_button( __( 'Beállítások mentése', 'cloudmentor-events' ) );
                        ?>
                    </form>
                </div>

                <div class="cme-settings-sidebar">
                    <div class="cme-settings-box">
                        <h3><?php esc_html_e( 'Shortcode használata', 'cloudmentor-events' ); ?></h3>
                        <p><?php esc_html_e( 'Alapértelmezett megjelenítés:', 'cloudmentor-events' ); ?></p>
                        <code>[cloud-events]</code>

                        <p><?php esc_html_e( 'Testreszabott megjelenítés:', 'cloudmentor-events' ); ?></p>
                        <code>[cloud-events count="3" category="azure" show_type="true"]</code>

                        <h4><?php esc_html_e( 'Elérhető paraméterek:', 'cloudmentor-events' ); ?></h4>
                        <ul>
                            <li><code>count</code> - <?php esc_html_e( 'Események száma (1-20)', 'cloudmentor-events' ); ?></li>
                            <li><code>category</code> - <?php esc_html_e( 'Platform szűrő (azure, aws, gcp)', 'cloudmentor-events' ); ?></li>
                            <li><code>type</code> - <?php esc_html_e( 'Típus szűrő (biztonsag, kivezetes, stb.)', 'cloudmentor-events' ); ?></li>
                            <li><code>show_category</code> - <?php esc_html_e( 'Kategória mutatása (true/false)', 'cloudmentor-events' ); ?></li>
                            <li><code>show_type</code> - <?php esc_html_e( 'Típus mutatása (true/false)', 'cloudmentor-events' ); ?></li>
                            <li><code>date_format</code> - <?php esc_html_e( 'Dátum formátum (hungarian/iso/relative)', 'cloudmentor-events' ); ?></li>
                            <li><code>show_past</code> - <?php esc_html_e( 'Múltbeli események (true/false)', 'cloudmentor-events' ); ?></li>
                        </ul>
                    </div>

                    <div class="cme-settings-box">
                        <h3><?php esc_html_e( 'Themify integráció', 'cloudmentor-events' ); ?></h3>
                        <p><?php esc_html_e( 'A plugin teljes mértékben kompatibilis a Themify témákkal. Használd a shortcode-ot bármely Themify Builder modulban.', 'cloudmentor-events' ); ?></p>
                    </div>

                    <div class="cme-settings-box">
                        <h3><?php esc_html_e( 'Támogatás', 'cloudmentor-events' ); ?></h3>
                        <p>
                            <a href="https://github.com/cloudsteak/wordpress-plugins/cloudmentor-events" target="_blank" rel="noopener">
                                <?php esc_html_e( 'GitHub Repository', 'cloudmentor-events' ); ?>
                            </a>
                        </p>
                        <p>
                            <small><?php esc_html_e( 'Verzió:', 'cloudmentor-events' ); ?> <?php echo esc_html( CME_VERSION ); ?></small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render display section description
     */
    public function render_display_section() {
        echo '<p>' . esc_html__( 'Alapértelmezett megjelenítési beállítások a shortcode és widget számára.', 'cloudmentor-events' ) . '</p>';
    }

    /**
     * Render style section description
     */
    public function render_style_section() {
        echo '<p>' . esc_html__( 'Vizuális megjelenés testreszabása.', 'cloudmentor-events' ) . '</p>';
    }

    /**
     * Render events count field
     */
    public function render_events_count_field() {
        $options = get_option( $this->option_name );
        $value   = $options['events_count'] ?? 5;
        ?>
        <input type="number"
               name="<?php echo esc_attr( $this->option_name ); ?>[events_count]"
               value="<?php echo esc_attr( $value ); ?>"
               min="1"
               max="20"
               class="small-text">
        <p class="description">
            <?php esc_html_e( 'Alapértelmezett események száma (1-20)', 'cloudmentor-events' ); ?>
        </p>
        <?php
    }

    /**
     * Render date format field
     */
    public function render_date_format_field() {
        $options = get_option( $this->option_name );
        $value   = $options['date_format'] ?? 'hungarian';
        ?>
        <select name="<?php echo esc_attr( $this->option_name ); ?>[date_format]">
            <option value="hungarian" <?php selected( $value, 'hungarian' ); ?>>
                <?php esc_html_e( 'Magyar (2026.02.01.)', 'cloudmentor-events' ); ?>
            </option>
            <option value="iso" <?php selected( $value, 'iso' ); ?>>
                <?php esc_html_e( 'ISO (2026-02-01)', 'cloudmentor-events' ); ?>
            </option>
            <option value="relative" <?php selected( $value, 'relative' ); ?>>
                <?php esc_html_e( 'Relatív (5 nap múlva)', 'cloudmentor-events' ); ?>
            </option>
        </select>
        <?php
    }

    /**
     * Render show category field
     */
    public function render_show_category_field() {
        $options = get_option( $this->option_name );
        $value   = $options['show_category'] ?? true;
        ?>
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr( $this->option_name ); ?>[show_category]"
                   value="1"
                   <?php checked( $value ); ?>>
            <?php esc_html_e( 'Platform kategória megjelenítése (Azure, AWS, GCP)', 'cloudmentor-events' ); ?>
        </label>
        <?php
    }

    /**
     * Render show type field
     */
    public function render_show_type_field() {
        $options = get_option( $this->option_name );
        $value   = $options['show_type'] ?? true;
        ?>
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr( $this->option_name ); ?>[show_type]"
                   value="1"
                   <?php checked( $value ); ?>>
            <?php esc_html_e( 'Esemény típus megjelenítése (Biztonság, Kivezetés, stb.)', 'cloudmentor-events' ); ?>
        </label>
        <?php
    }

    /**
     * Render color scheme field
     */
    public function render_color_scheme_field() {
        $options = get_option( $this->option_name );
        $value   = $options['color_scheme'] ?? 'default';
        ?>
        <select name="<?php echo esc_attr( $this->option_name ); ?>[color_scheme]">
            <option value="default" <?php selected( $value, 'default' ); ?>>
                <?php esc_html_e( 'Alapértelmezett (világos)', 'cloudmentor-events' ); ?>
            </option>
            <option value="dark" <?php selected( $value, 'dark' ); ?>>
                <?php esc_html_e( 'Sötét mód', 'cloudmentor-events' ); ?>
            </option>
            <option value="minimal" <?php selected( $value, 'minimal' ); ?>>
                <?php esc_html_e( 'Minimalista', 'cloudmentor-events' ); ?>
            </option>
            <option value="colorful" <?php selected( $value, 'colorful' ); ?>>
                <?php esc_html_e( 'Színes', 'cloudmentor-events' ); ?>
            </option>
        </select>
        <?php
    }

    /**
     * Render animation field
     */
    public function render_animation_field() {
        $options = get_option( $this->option_name );
        $value   = $options['animation'] ?? 'slide';
        ?>
        <select name="<?php echo esc_attr( $this->option_name ); ?>[animation]">
            <option value="slide" <?php selected( $value, 'slide' ); ?>>
                <?php esc_html_e( 'Csúsztatás', 'cloudmentor-events' ); ?>
            </option>
            <option value="fade" <?php selected( $value, 'fade' ); ?>>
                <?php esc_html_e( 'Halványítás', 'cloudmentor-events' ); ?>
            </option>
            <option value="none" <?php selected( $value, 'none' ); ?>>
                <?php esc_html_e( 'Nincs animáció', 'cloudmentor-events' ); ?>
            </option>
        </select>
        <?php
    }

    /**
     * Render past events section description
     */
    public function render_past_events_section() {
        echo '<p>' . esc_html__( 'Lejárt események megjelenítési beállításai.', 'cloudmentor-events' ) . '</p>';
    }

    /**
     * Render past events count field
     */
    public function render_past_events_count_field() {
        $options = get_option( $this->option_name );
        $value   = $options['past_events_count'] ?? 0;
        ?>
        <select name="<?php echo esc_attr( $this->option_name ); ?>[past_events_count]">
            <option value="0" <?php selected( $value, 0 ); ?>>
                <?php esc_html_e( 'Ne jelenjen meg', 'cloudmentor-events' ); ?>
            </option>
            <option value="1" <?php selected( $value, 1 ); ?>>1 db</option>
            <option value="2" <?php selected( $value, 2 ); ?>>2 db</option>
            <option value="3" <?php selected( $value, 3 ); ?>>3 db</option>
            <option value="5" <?php selected( $value, 5 ); ?>>5 db</option>
        </select>
        <p class="description">
            <?php esc_html_e( 'Hány lejárt esemény jelenjen meg', 'cloudmentor-events' ); ?>
        </p>
        <?php
    }

    /**
     * Render past events days field
     */
    public function render_past_events_days_field() {
        $options = get_option( $this->option_name );
        $value   = $options['past_events_days'] ?? 7;
        ?>
        <select name="<?php echo esc_attr( $this->option_name ); ?>[past_events_days]">
            <option value="7" <?php selected( $value, 7 ); ?>>
                <?php esc_html_e( '7 napig', 'cloudmentor-events' ); ?>
            </option>
            <option value="14" <?php selected( $value, 14 ); ?>>
                <?php esc_html_e( '14 napig', 'cloudmentor-events' ); ?>
            </option>
            <option value="30" <?php selected( $value, 30 ); ?>>
                <?php esc_html_e( '30 napig', 'cloudmentor-events' ); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e( 'Lejárt események megjelenítése ennyi napig', 'cloudmentor-events' ); ?>
        </p>
        <?php
    }

    /**
     * Add settings link to plugins page
     */
    public function add_settings_link( $links ) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url( 'options-general.php?page=cloudmentor-events' ),
            __( 'Beállítások', 'cloudmentor-events' )
        );

        array_unshift( $links, $settings_link );

        return $links;
    }
}

// Initialize
CME_Settings::get_instance();

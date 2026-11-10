<?php
/**
 * Meta Boxes for Cloud Events
 *
 * @package CloudMentor_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Meta Boxes class
 */
class CME_Meta_Boxes {

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
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_cloud_event', array( $this, 'save_meta_boxes' ), 10, 2 );
        add_action( 'admin_notices', array( $this, 'todo_missing_notice' ) );
    }

    /**
     * Notice when publishing was blocked because the todo field is empty
     */
    public function todo_missing_notice() {
        $key = 'cme_todo_missing_' . get_current_user_id();
        if ( get_transient( $key ) ) {
            delete_transient( $key );
            echo '<div class="notice notice-error"><p>' . esc_html__( 'A „Mi a teendő?” mező kötelező, ezért az esemény piszkozat maradt.', 'cloudmentor-events' ) . '</p></div>';
        }
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'cme_event_details',
            __( 'Esemény részletei', 'cloudmentor-events' ),
            array( $this, 'render_event_details_meta_box' ),
            'cloud_event',
            'normal',
            'high'
        );
    }

    /**
     * Render event details meta box
     */
    public function render_event_details_meta_box( $post ) {
        // Get saved values
        $event_date    = get_post_meta( $post->ID, '_cme_event_date', true );
        $description   = get_post_meta( $post->ID, '_cme_description', true );
        $source_url    = get_post_meta( $post->ID, '_cme_source_url', true );
        $deadline_type = get_post_meta( $post->ID, '_cme_deadline_type', true );
        $todo          = get_post_meta( $post->ID, '_cme_todo', true );
        $comment       = get_post_meta( $post->ID, '_cme_comment', true );

        // Nonce field
        wp_nonce_field( 'cme_save_meta_boxes', 'cme_meta_nonce' );
        ?>
        <div class="cme-meta-box">
            <table class="form-table cme-form-table">
                <tr>
                    <th>
                        <label for="cme_deadline_type">
                            <?php esc_html_e( 'Változás jellege', 'cloudmentor-events' ); ?>
                        </label>
                    </th>
                    <td>
                        <select id="cme_deadline_type" name="cme_deadline_type" class="regular-text">
                            <option value=""><?php esc_html_e( '— Válassz —', 'cloudmentor-events' ); ?></option>
                            <option value="hard" <?php selected( $deadline_type, 'hard' ); ?>>
                                <?php esc_html_e( 'Kritikus', 'cloudmentor-events' ); ?> 🔴
                            </option>
                            <option value="soft" <?php selected( $deadline_type, 'soft' ); ?>>
                                <?php esc_html_e( 'Ajánlott', 'cloudmentor-events' ); ?> 🟡
                            </option>
                            <option value="optional" <?php selected( $deadline_type, 'optional' ); ?>>
                                <?php esc_html_e( 'Opcionális', 'cloudmentor-events' ); ?> 🟢
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Kritikus = fontos (piros), Ajánlott = javasolt (sárga), Opcionális = kényelmi (zöld)', 'cloudmentor-events' ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="cme_description">
                            <?php esc_html_e( 'Leírás (felvezető szöveg)', 'cloudmentor-events' ); ?>
                        </label>
                    </th>
                    <td>
                        <?php
                        wp_editor(
                            $description,
                            'cme_description',
                            array(
                                'textarea_name' => 'cme_description',
                                'textarea_rows' => 8,
                                'media_buttons' => false,
                                'teeny'         => true,
                                'quicktags'     => true,
                            )
                        );
                        ?>
                        <p class="description">
                            <?php esc_html_e( 'Rövid felvezető: mi változik és miért. A határidő és a teendők külön mezőben jelennek meg.', 'cloudmentor-events' ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="cme_event_date">
                            <?php esc_html_e( 'Esemény dátuma', 'cloudmentor-events' ); ?>
                            <span class="required">*</span>
                        </label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="cme_event_date"
                            name="cme_event_date"
                            value="<?php echo esc_attr( $event_date ); ?>"
                            class="cme-datepicker regular-text"
                            placeholder="ÉÉÉÉ.HH.NN"
                            required
                        />
                        <p class="description">
                            <?php esc_html_e( 'A határidő vagy esemény dátuma (ÉÉÉÉ.HH.NN formátumban). A leírás után „Határidő:” sorként jelenik meg.', 'cloudmentor-events' ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="cme_todo">
                            <?php esc_html_e( 'Mi a teendő?', 'cloudmentor-events' ); ?>
                            <span class="required">*</span>
                        </label>
                    </th>
                    <td>
                        <?php
                        wp_editor(
                            $todo,
                            'cme_todo',
                            array(
                                'textarea_name' => 'cme_todo',
                                'textarea_rows' => 6,
                                'media_buttons' => false,
                                'teeny'         => true,
                                'quicktags'     => true,
                            )
                        );
                        ?>
                        <p class="description">
                            <?php esc_html_e( 'Kötelező. Szöveg, felsorolás vagy számozott lista is lehet (HTML).', 'cloudmentor-events' ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="cme_comment">
                            <?php esc_html_e( 'Megjegyzés', 'cloudmentor-events' ); ?>
                        </label>
                    </th>
                    <td>
                        <?php
                        wp_editor(
                            $comment,
                            'cme_comment',
                            array(
                                'textarea_name' => 'cme_comment',
                                'textarea_rows' => 4,
                                'media_buttons' => false,
                                'teeny'         => true,
                                'quicktags'     => true,
                            )
                        );
                        ?>
                        <p class="description">
                            <?php esc_html_e( 'Opcionális kiegészítés, a teendők után jelenik meg.', 'cloudmentor-events' ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="cme_source_url">
                            <?php esc_html_e( 'Forrás URL', 'cloudmentor-events' ); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            type="url"
                            id="cme_source_url"
                            name="cme_source_url"
                            value="<?php echo esc_url( $source_url ); ?>"
                            class="large-text"
                            placeholder="https://docs.microsoft.com/..."
                        />
                        <p class="description">
                            <?php esc_html_e( 'Link a hivatalos dokumentációhoz vagy bejelentéshez', 'cloudmentor-events' ); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <div class="cme-meta-box-tips">
                <h4><?php esc_html_e( 'Tippek:', 'cloudmentor-events' ); ?></h4>
                <ul>
                    <li><?php esc_html_e( 'A cím legyen rövid és tömör (max. 50 karakter ajánlott)', 'cloudmentor-events' ); ?></li>
                    <li><?php esc_html_e( 'Használd a Platform Kategóriát (Azure, AWS, GCP) a szűréshez', 'cloudmentor-events' ); ?></li>
                    <li><?php esc_html_e( 'Adj meg Esemény Típust (Biztonság, Kivezetés, stb.) a gyors azonosításhoz', 'cloudmentor-events' ); ?></li>
                    <li><?php esc_html_e( 'A „Mi a teendő?” mező kötelező az új eseményeknél', 'cloudmentor-events' ); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Save meta boxes
     */
    public function save_meta_boxes( $post_id, $post ) {
        // Check nonce
        if ( ! isset( $_POST['cme_meta_nonce'] ) ||
             ! wp_verify_nonce( $_POST['cme_meta_nonce'], 'cme_save_meta_boxes' ) ) {
            return;
        }

        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Legacy events (description only, no todo) are not forced to have a todo
        $is_legacy = '' === (string) get_post_meta( $post_id, '_cme_todo', true )
            && '' !== (string) get_post_meta( $post_id, '_cme_description', true );

        // Save event date
        if ( isset( $_POST['cme_event_date'] ) ) {
            $event_date = sanitize_text_field( $_POST['cme_event_date'] );
            // Convert Hungarian date format to ISO
            $event_date = str_replace( '.', '-', rtrim( $event_date, '.' ) );
            update_post_meta( $post_id, '_cme_event_date', $event_date );
        }

        // Save deadline type
        if ( isset( $_POST['cme_deadline_type'] ) ) {
            $deadline_type = sanitize_text_field( $_POST['cme_deadline_type'] );
            if ( in_array( $deadline_type, array( 'soft', 'hard', 'optional', '' ), true ) ) {
                update_post_meta( $post_id, '_cme_deadline_type', $deadline_type );
            }
        }

        // Save description
        if ( isset( $_POST['cme_description'] ) ) {
            $description = wp_kses_post( $_POST['cme_description'] );
            update_post_meta( $post_id, '_cme_description', $description );
        }

        // Save todo (required for non-legacy events)
        if ( isset( $_POST['cme_todo'] ) ) {
            $todo = wp_kses_post( $_POST['cme_todo'] );
            update_post_meta( $post_id, '_cme_todo', $todo );

            if ( '' === trim( wp_strip_all_tags( $todo ) ) && ! $is_legacy && 'publish' === $post->post_status ) {
                // Required field missing: fall back to draft and tell the editor
                remove_action( 'save_post_cloud_event', array( $this, 'save_meta_boxes' ), 10 );
                wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
                add_action( 'save_post_cloud_event', array( $this, 'save_meta_boxes' ), 10, 2 );
                set_transient( 'cme_todo_missing_' . get_current_user_id(), 1, 60 );
            }
        }

        // Save comment
        if ( isset( $_POST['cme_comment'] ) ) {
            update_post_meta( $post_id, '_cme_comment', wp_kses_post( $_POST['cme_comment'] ) );
        }

        // Save source URL
        if ( isset( $_POST['cme_source_url'] ) ) {
            $source_url = esc_url_raw( $_POST['cme_source_url'] );
            update_post_meta( $post_id, '_cme_source_url', $source_url );
        }
    }
}

// Initialize
CME_Meta_Boxes::get_instance();

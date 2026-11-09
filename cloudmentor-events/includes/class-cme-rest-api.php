<?php
/**
 * REST API for creating and listing events (MCP / automation integration)
 *
 * Namespace: cloudmentor-events/v1
 *
 * Authentication (either):
 *  - dedicated API key: `X-API-Key: <key>` or `Authorization: Bearer <key>`
 *  - regular WordPress auth (e.g. Application Password) of a user who can edit posts
 *
 * @package CloudMentor_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CME_REST_API {

    const NAMESPACE_V1   = 'cloudmentor-events/v1';
    const KEY_HASH_OPTION = 'cme_api_key_hash';
    const KEY_META_OPTION = 'cme_api_key_meta';

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        add_action( 'admin_post_cme_generate_api_key', array( $this, 'handle_generate_key' ) );
        add_action( 'admin_post_cme_revoke_api_key', array( $this, 'handle_revoke_key' ) );
    }

    /* ------------------------------------------------------------------
     * Routes
     * ---------------------------------------------------------------- */

    public function register_routes() {
        register_rest_route( self::NAMESPACE_V1, '/events', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_event' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => $this->create_args(),
            ),
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'list_events' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'from'     => array( 'type' => 'string' ),
                    'to'       => array( 'type' => 'string' ),
                    'category' => array( 'type' => 'string' ),
                    'type'     => array( 'type' => 'string' ),
                    'search'   => array( 'type' => 'string' ),
                    'limit'    => array( 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ),
                ),
            ),
        ) );

        register_rest_route( self::NAMESPACE_V1, '/terms', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_terms_list' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );
    }

    private function create_args() {
        return array(
            'title'         => array( 'type' => 'string', 'required' => true ),
            'event_date'    => array( 'type' => 'string', 'required' => true ),
            'category'      => array( 'type' => 'string' ),
            'type'          => array( 'type' => 'string' ),
            'deadline_type' => array( 'type' => 'string', 'enum' => array( 'hard', 'soft', 'optional' ) ),
            'description'   => array( 'type' => 'string' ),
            'source_url'    => array( 'type' => 'string' ),
            'status'        => array( 'type' => 'string', 'enum' => array( 'publish', 'draft' ), 'default' => 'publish' ),
        );
    }

    /* ------------------------------------------------------------------
     * Authentication
     * ---------------------------------------------------------------- */

    public function check_permission( WP_REST_Request $request ) {
        // 1) Dedicated API key
        $provided = $this->extract_key( $request );
        if ( '' !== $provided ) {
            $hash = get_option( self::KEY_HASH_OPTION, '' );
            if ( $hash && hash_equals( $hash, hash( 'sha256', $provided ) ) ) {
                return true;
            }
            return new WP_Error( 'cme_invalid_api_key', __( 'Érvénytelen API kulcs.', 'cloudmentor-events' ), array( 'status' => 401 ) );
        }

        // 2) Regular WordPress authentication (e.g. Application Password)
        if ( current_user_can( 'edit_posts' ) ) {
            return true;
        }

        return new WP_Error( 'cme_unauthorized', __( 'Hitelesítés szükséges.', 'cloudmentor-events' ), array( 'status' => rest_authorization_required_code() ) );
    }

    private function extract_key( WP_REST_Request $request ) {
        $key = $request->get_header( 'x-api-key' );
        if ( ! $key ) {
            $auth = $request->get_header( 'authorization' );
            if ( $auth && 0 === stripos( $auth, 'Bearer ' ) ) {
                $key = trim( substr( $auth, 7 ) );
            }
        }
        return is_string( $key ) ? trim( $key ) : '';
    }

    /* ------------------------------------------------------------------
     * Handlers
     * ---------------------------------------------------------------- */

    public function create_event( WP_REST_Request $request ) {
        $title = trim( sanitize_text_field( (string) $request->get_param( 'title' ) ) );
        if ( '' === $title ) {
            return new WP_Error( 'cme_invalid_title', __( 'A cím kötelező.', 'cloudmentor-events' ), array( 'status' => 400 ) );
        }

        $date = $this->normalize_date( (string) $request->get_param( 'event_date' ) );
        if ( ! $date ) {
            return new WP_Error( 'cme_invalid_date', __( 'Érvénytelen dátum. Használj ÉÉÉÉ-HH-NN vagy ÉÉÉÉ.HH.NN formátumot.', 'cloudmentor-events' ), array( 'status' => 400 ) );
        }

        $source_url = '';
        if ( $request->get_param( 'source_url' ) ) {
            $source_url = esc_url_raw( (string) $request->get_param( 'source_url' ), array( 'http', 'https' ) );
            if ( '' === $source_url ) {
                return new WP_Error( 'cme_invalid_url', __( 'Érvénytelen forrás URL.', 'cloudmentor-events' ), array( 'status' => 400 ) );
            }
        }

        $terms = array();
        foreach ( array( 'category' => 'event_category', 'type' => 'event_type' ) as $param => $taxonomy ) {
            $value = $request->get_param( $param );
            if ( null === $value || '' === $value ) {
                continue;
            }
            $term = get_term_by( 'slug', sanitize_title( (string) $value ), $taxonomy );
            if ( ! $term ) {
                $term = get_term_by( 'name', (string) $value, $taxonomy );
            }
            if ( ! $term ) {
                return new WP_Error(
                    'cme_unknown_term',
                    sprintf( /* translators: %s: parameter name */ __( 'Ismeretlen érték: %s', 'cloudmentor-events' ), $param ),
                    array( 'status' => 400, 'valid' => wp_list_pluck( $this->terms_for( $taxonomy ), 'slug' ) )
                );
            }
            $terms[ $taxonomy ] = (int) $term->term_id;
        }

        // Duplicate protection: same title and date
        $existing = get_posts( array(
            'post_type'      => 'cloud_event',
            'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
            'title'          => $title,
            'meta_key'       => '_cme_event_date',
            'meta_value'     => $date,
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ) );
        if ( $existing ) {
            return new WP_Error(
                'cme_duplicate',
                __( 'Ilyen című és dátumú esemény már létezik.', 'cloudmentor-events' ),
                array( 'status' => 409, 'id' => (int) $existing[0] )
            );
        }

        $status  = 'draft' === $request->get_param( 'status' ) ? 'draft' : 'publish';
        $post_id = wp_insert_post( array(
            'post_type'   => 'cloud_event',
            'post_title'  => $title,
            'post_status' => $status,
        ), true );
        if ( is_wp_error( $post_id ) ) {
            return new WP_Error( 'cme_create_failed', $post_id->get_error_message(), array( 'status' => 500 ) );
        }

        update_post_meta( $post_id, '_cme_event_date', $date );
        $deadline = $request->get_param( 'deadline_type' );
        if ( in_array( $deadline, array( 'hard', 'soft', 'optional' ), true ) ) {
            update_post_meta( $post_id, '_cme_deadline_type', $deadline );
        }
        if ( null !== $request->get_param( 'description' ) ) {
            update_post_meta( $post_id, '_cme_description', wp_kses_post( (string) $request->get_param( 'description' ) ) );
        }
        if ( $source_url ) {
            update_post_meta( $post_id, '_cme_source_url', $source_url );
        }
        foreach ( $terms as $taxonomy => $term_id ) {
            wp_set_object_terms( $post_id, array( $term_id ), $taxonomy );
        }

        /**
         * Fires after an event was created through the REST API.
         */
        do_action( 'cme_event_created_via_api', $post_id, $request );

        $response = rest_ensure_response( $this->format_event( get_post( $post_id ) ) );
        $response->set_status( 201 );
        return $response;
    }

    public function list_events( WP_REST_Request $request ) {
        $args = array(
            'post_type'      => 'cloud_event',
            'post_status'    => array( 'publish', 'draft' ),
            'posts_per_page' => min( 100, max( 1, (int) ( $request->get_param( 'limit' ) ?: 20 ) ) ),
            'meta_key'       => '_cme_event_date',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
        );

        $meta = array();
        foreach ( array( 'from' => '>=', 'to' => '<=' ) as $param => $compare ) {
            $d = $this->normalize_date( (string) $request->get_param( $param ) );
            if ( $d ) {
                $meta[] = array( 'key' => '_cme_event_date', 'value' => $d, 'compare' => $compare, 'type' => 'DATE' );
            }
        }
        if ( $meta ) {
            $args['meta_query'] = $meta;
        }
        if ( $request->get_param( 'search' ) ) {
            $args['s'] = sanitize_text_field( (string) $request->get_param( 'search' ) );
        }
        $tax = array();
        if ( $request->get_param( 'category' ) ) {
            $tax[] = array( 'taxonomy' => 'event_category', 'field' => 'slug', 'terms' => sanitize_title( (string) $request->get_param( 'category' ) ) );
        }
        if ( $request->get_param( 'type' ) ) {
            $tax[] = array( 'taxonomy' => 'event_type', 'field' => 'slug', 'terms' => sanitize_title( (string) $request->get_param( 'type' ) ) );
        }
        if ( $tax ) {
            $args['tax_query'] = $tax;
        }

        $items = array_map( array( $this, 'format_event' ), get_posts( $args ) );
        return rest_ensure_response( array( 'count' => count( $items ), 'events' => $items ) );
    }

    public function get_terms_list() {
        return rest_ensure_response( array(
            'categories'     => $this->terms_for( 'event_category' ),
            'types'          => $this->terms_for( 'event_type' ),
            'deadline_types' => array(
                array( 'value' => 'hard', 'label' => 'Kritikus' ),
                array( 'value' => 'soft', 'label' => 'Ajánlott' ),
                array( 'value' => 'optional', 'label' => 'Opcionális' ),
            ),
        ) );
    }

    /* ------------------------------------------------------------------
     * Helpers
     * ---------------------------------------------------------------- */

    private function terms_for( $taxonomy ) {
        $terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
        if ( is_wp_error( $terms ) ) {
            return array();
        }
        return array_map( static function ( $t ) {
            return array( 'slug' => $t->slug, 'name' => $t->name );
        }, $terms );
    }

    /**
     * Accepts YYYY-MM-DD, YYYY.MM.DD, YYYY.MM.DD. and returns YYYY-MM-DD or false.
     */
    private function normalize_date( $input ) {
        $input = trim( rtrim( trim( $input ), '.' ) );
        if ( ! preg_match( '/^(\d{4})[-.\/\s]+(\d{1,2})[-.\/\s]+(\d{1,2})$/', $input, $m ) ) {
            return false;
        }
        if ( ! checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
            return false;
        }
        return sprintf( '%04d-%02d-%02d', $m[1], $m[2], $m[3] );
    }

    private function format_event( $post ) {
        $cat  = wp_get_post_terms( $post->ID, 'event_category', array( 'fields' => 'slugs' ) );
        $type = wp_get_post_terms( $post->ID, 'event_type', array( 'fields' => 'slugs' ) );
        return array(
            'id'            => (int) $post->ID,
            'title'         => $post->post_title,
            'status'        => $post->post_status,
            'event_date'    => get_post_meta( $post->ID, '_cme_event_date', true ),
            'category'      => is_wp_error( $cat ) || ! $cat ? null : $cat[0],
            'type'          => is_wp_error( $type ) || ! $type ? null : $type[0],
            'deadline_type' => get_post_meta( $post->ID, '_cme_deadline_type', true ) ?: null,
            'description'   => get_post_meta( $post->ID, '_cme_description', true ),
            'source_url'    => get_post_meta( $post->ID, '_cme_source_url', true ),
            'edit_url'      => current_user_can( 'edit_post', $post->ID ) ? get_edit_post_link( $post->ID, 'raw' ) : null,
        );
    }

    /* ------------------------------------------------------------------
     * API key management (admin)
     * ---------------------------------------------------------------- */

    public function handle_generate_key() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Nincs jogosultságod.', 'cloudmentor-events' ), 403 );
        }
        check_admin_referer( 'cme_generate_api_key' );

        $key = 'cme_' . bin2hex( random_bytes( 24 ) );
        update_option( self::KEY_HASH_OPTION, hash( 'sha256', $key ), false );
        update_option( self::KEY_META_OPTION, array( 'created' => time(), 'hint' => substr( $key, -4 ) ), false );
        // Show once (short-lived, per-user transient)
        set_transient( 'cme_new_api_key_' . get_current_user_id(), $key, 5 * MINUTE_IN_SECONDS );

        wp_safe_redirect( admin_url( 'options-general.php?page=cloudmentor-events' ) );
        exit;
    }

    public function handle_revoke_key() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Nincs jogosultságod.', 'cloudmentor-events' ), 403 );
        }
        check_admin_referer( 'cme_revoke_api_key' );
        delete_option( self::KEY_HASH_OPTION );
        delete_option( self::KEY_META_OPTION );
        wp_safe_redirect( admin_url( 'options-general.php?page=cloudmentor-events' ) );
        exit;
    }

    /**
     * Render API key box on the settings page.
     */
    public static function render_settings_box() {
        $has_key = (bool) get_option( self::KEY_HASH_OPTION );
        $meta    = get_option( self::KEY_META_OPTION, array() );
        $new_key = get_transient( 'cme_new_api_key_' . get_current_user_id() );
        if ( $new_key ) {
            delete_transient( 'cme_new_api_key_' . get_current_user_id() );
        }
        ?>
        <div class="cme-settings-box">
            <h3><?php esc_html_e( 'MCP / REST API', 'cloudmentor-events' ); ?></h3>
            <p><code><?php echo esc_html( rest_url( self::NAMESPACE_V1 . '/events' ) ); ?></code></p>
            <?php if ( $new_key ) : ?>
                <p><strong><?php esc_html_e( 'Új API kulcs (csak most látható, mentsd el!):', 'cloudmentor-events' ); ?></strong></p>
                <p><input type="text" readonly class="large-text code" value="<?php echo esc_attr( $new_key ); ?>" onclick="this.select()" /></p>
            <?php elseif ( $has_key ) : ?>
                <p><?php
                    printf(
                        /* translators: 1: last 4 chars, 2: date */
                        esc_html__( 'Aktív API kulcs: …%1$s (létrehozva: %2$s)', 'cloudmentor-events' ),
                        esc_html( $meta['hint'] ?? '????' ),
                        esc_html( ! empty( $meta['created'] ) ? wp_date( 'Y.m.d. H:i', $meta['created'] ) : '—' )
                    );
                ?></p>
            <?php else : ?>
                <p><?php esc_html_e( 'Nincs API kulcs beállítva, az API csak WordPress hitelesítéssel (Application Password) érhető el.', 'cloudmentor-events' ); ?></p>
            <?php endif; ?>
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" style="display:inline">
                <input type="hidden" name="action" value="cme_generate_api_key" />
                <?php wp_nonce_field( 'cme_generate_api_key' ); ?>
                <button type="submit" class="button button-primary"><?php echo $has_key ? esc_html__( 'Kulcs újragenerálása', 'cloudmentor-events' ) : esc_html__( 'API kulcs generálása', 'cloudmentor-events' ); ?></button>
            </form>
            <?php if ( $has_key ) : ?>
                <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" style="display:inline">
                    <input type="hidden" name="action" value="cme_revoke_api_key" />
                    <?php wp_nonce_field( 'cme_revoke_api_key' ); ?>
                    <button type="submit" class="button"><?php esc_html_e( 'Visszavonás', 'cloudmentor-events' ); ?></button>
                </form>
            <?php endif; ?>
            <p class="description"><?php esc_html_e( 'Küldd X-API-Key fejlécben (vagy Authorization: Bearer). Csak HTTPS-en használd.', 'cloudmentor-events' ); ?></p>
        </div>
        <?php
    }
}

CME_REST_API::get_instance();

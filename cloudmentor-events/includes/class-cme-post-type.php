<?php
/**
 * Custom Post Type registration
 *
 * @package CloudMentor_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Post Type class
 */
class CME_Post_Type {

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
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        add_filter( 'manage_cloud_event_posts_columns', array( $this, 'add_admin_columns' ) );
        add_action( 'manage_cloud_event_posts_custom_column', array( $this, 'render_admin_columns' ), 10, 2 );
        add_filter( 'manage_edit-cloud_event_sortable_columns', array( $this, 'sortable_columns' ) );
        add_action( 'pre_get_posts', array( $this, 'orderby_event_date' ) );
    }

    /**
     * Register custom post type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x( 'Cloud Események', 'Post type general name', 'cloudmentor-events' ),
            'singular_name'         => _x( 'Cloud Esemény', 'Post type singular name', 'cloudmentor-events' ),
            'menu_name'             => _x( 'Cloud Események', 'Admin Menu text', 'cloudmentor-events' ),
            'name_admin_bar'        => _x( 'Cloud Esemény', 'Add New on Toolbar', 'cloudmentor-events' ),
            'add_new'               => __( 'Új hozzáadása', 'cloudmentor-events' ),
            'add_new_item'          => __( 'Új esemény hozzáadása', 'cloudmentor-events' ),
            'new_item'              => __( 'Új esemény', 'cloudmentor-events' ),
            'edit_item'             => __( 'Esemény szerkesztése', 'cloudmentor-events' ),
            'view_item'             => __( 'Esemény megtekintése', 'cloudmentor-events' ),
            'all_items'             => __( 'Összes esemény', 'cloudmentor-events' ),
            'search_items'          => __( 'Események keresése', 'cloudmentor-events' ),
            'parent_item_colon'     => __( 'Szülő esemény:', 'cloudmentor-events' ),
            'not_found'             => __( 'Nem található esemény.', 'cloudmentor-events' ),
            'not_found_in_trash'    => __( 'Nincs esemény a kukában.', 'cloudmentor-events' ),
            'featured_image'        => _x( 'Esemény kép', 'Overrides the "Featured Image" phrase', 'cloudmentor-events' ),
            'set_featured_image'    => _x( 'Esemény kép beállítása', 'Overrides the "Set featured image" phrase', 'cloudmentor-events' ),
            'remove_featured_image' => _x( 'Esemény kép eltávolítása', 'Overrides the "Remove featured image" phrase', 'cloudmentor-events' ),
            'use_featured_image'    => _x( 'Használd esemény képként', 'Overrides the "Use as featured image" phrase', 'cloudmentor-events' ),
            'archives'              => _x( 'Esemény archívum', 'The post type archive label', 'cloudmentor-events' ),
            'insert_into_item'      => _x( 'Beillesztés az eseménybe', 'Overrides the "Insert into post" phrase', 'cloudmentor-events' ),
            'uploaded_to_this_item' => _x( 'Feltöltve ehhez az eseményhez', 'Overrides the "Uploaded to this post" phrase', 'cloudmentor-events' ),
            'filter_items_list'     => _x( 'Események szűrése', 'Screen reader text', 'cloudmentor-events' ),
            'items_list_navigation' => _x( 'Események navigáció', 'Screen reader text', 'cloudmentor-events' ),
            'items_list'            => _x( 'Események lista', 'Screen reader text', 'cloudmentor-events' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'cloud-event' ),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 25,
            'menu_icon'          => 'dashicons-cloud',
            'supports'           => array( 'title' ),
            'show_in_rest'       => true,
        );

        register_post_type( 'cloud_event', $args );
    }

    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Event Category (Azure, AWS, GCP, etc.)
        $category_labels = array(
            'name'                       => _x( 'Platform Kategóriák', 'taxonomy general name', 'cloudmentor-events' ),
            'singular_name'              => _x( 'Platform Kategória', 'taxonomy singular name', 'cloudmentor-events' ),
            'search_items'               => __( 'Kategóriák keresése', 'cloudmentor-events' ),
            'popular_items'              => __( 'Népszerű kategóriák', 'cloudmentor-events' ),
            'all_items'                  => __( 'Összes kategória', 'cloudmentor-events' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Kategória szerkesztése', 'cloudmentor-events' ),
            'update_item'                => __( 'Kategória frissítése', 'cloudmentor-events' ),
            'add_new_item'               => __( 'Új kategória hozzáadása', 'cloudmentor-events' ),
            'new_item_name'              => __( 'Új kategória neve', 'cloudmentor-events' ),
            'separate_items_with_commas' => __( 'Kategóriák vesszővel elválasztva', 'cloudmentor-events' ),
            'add_or_remove_items'        => __( 'Kategóriák hozzáadása/eltávolítása', 'cloudmentor-events' ),
            'choose_from_most_used'      => __( 'Válassz a leggyakoribbakból', 'cloudmentor-events' ),
            'not_found'                  => __( 'Nem található kategória.', 'cloudmentor-events' ),
            'menu_name'                  => __( 'Platform Kategóriák', 'cloudmentor-events' ),
        );

        $category_args = array(
            'hierarchical'          => true,
            'labels'                => $category_labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
            'rewrite'               => array( 'slug' => 'event-category' ),
            'show_in_rest'          => true,
        );

        register_taxonomy( 'event_category', array( 'cloud_event' ), $category_args );

        // Event Type (Új, Beállítás, Biztonság, etc.)
        $type_labels = array(
            'name'                       => _x( 'Esemény Típusok', 'taxonomy general name', 'cloudmentor-events' ),
            'singular_name'              => _x( 'Esemény Típus', 'taxonomy singular name', 'cloudmentor-events' ),
            'search_items'               => __( 'Típusok keresése', 'cloudmentor-events' ),
            'popular_items'              => __( 'Népszerű típusok', 'cloudmentor-events' ),
            'all_items'                  => __( 'Összes típus', 'cloudmentor-events' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Típus szerkesztése', 'cloudmentor-events' ),
            'update_item'                => __( 'Típus frissítése', 'cloudmentor-events' ),
            'add_new_item'               => __( 'Új típus hozzáadása', 'cloudmentor-events' ),
            'new_item_name'              => __( 'Új típus neve', 'cloudmentor-events' ),
            'separate_items_with_commas' => __( 'Típusok vesszővel elválasztva', 'cloudmentor-events' ),
            'add_or_remove_items'        => __( 'Típusok hozzáadása/eltávolítása', 'cloudmentor-events' ),
            'choose_from_most_used'      => __( 'Válassz a leggyakoribbakból', 'cloudmentor-events' ),
            'not_found'                  => __( 'Nem található típus.', 'cloudmentor-events' ),
            'menu_name'                  => __( 'Esemény Típusok', 'cloudmentor-events' ),
        );

        $type_args = array(
            'hierarchical'          => true,
            'labels'                => $type_labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
            'rewrite'               => array( 'slug' => 'event-type' ),
            'show_in_rest'          => true,
        );

        register_taxonomy( 'event_type', array( 'cloud_event' ), $type_args );

        // Add default terms on activation
        $this->add_default_terms();
    }

    /**
     * Add default taxonomy terms
     */
    private function add_default_terms() {
        // Default categories
        $default_categories = array(
            'azure' => array(
                'name'        => 'Azure',
                'description' => 'Microsoft Azure események',
            ),
            'aws' => array(
                'name'        => 'AWS',
                'description' => 'Amazon Web Services események',
            ),
            'gcp' => array(
                'name'        => 'GCP',
                'description' => 'Google Cloud Platform események',
            ),
            'general' => array(
                'name'        => 'Általános',
                'description' => 'Általános cloud események',
            ),
        );

        foreach ( $default_categories as $slug => $term ) {
            if ( ! term_exists( $slug, 'event_category' ) ) {
                wp_insert_term(
                    $term['name'],
                    'event_category',
                    array(
                        'slug'        => $slug,
                        'description' => $term['description'],
                    )
                );
            }
        }

        // Default types
        $default_types = array(
            'uj'         => array( 'name' => 'Új', 'description' => 'Új funkció vagy szolgáltatás' ),
            'beallitas'  => array( 'name' => 'Beállítás', 'description' => 'Konfiguráció változás szükséges' ),
            'biztonsag'  => array( 'name' => 'Biztonság', 'description' => 'Biztonsági frissítés vagy változás' ),
            'kivezetes'  => array( 'name' => 'Kivezetés', 'description' => 'Szolgáltatás kivezetés alatt' ),
            'megszunik'  => array( 'name' => 'Megszűnik', 'description' => 'Szolgáltatás megszűnése' ),
            'frissites'  => array( 'name' => 'Frissítés', 'description' => 'Verzió frissítés' ),
            'migracio'   => array( 'name' => 'Migráció', 'description' => 'Migráció szükséges' ),
        );

        foreach ( $default_types as $slug => $term ) {
            if ( ! term_exists( $slug, 'event_type' ) ) {
                wp_insert_term(
                    $term['name'],
                    'event_type',
                    array(
                        'slug'        => $slug,
                        'description' => $term['description'],
                    )
                );
            }
        }
    }

    /**
     * Add custom admin columns
     */
    public function add_admin_columns( $columns ) {
        $new_columns = array();

        foreach ( $columns as $key => $value ) {
            if ( 'title' === $key ) {
                $new_columns[ $key ] = $value;
                $new_columns['event_date'] = __( 'Esemény dátuma', 'cloudmentor-events' );
                $new_columns['deadline_type'] = __( 'Változás jellege', 'cloudmentor-events' );
            } else {
                $new_columns[ $key ] = $value;
            }
        }

        // Remove default date column
        unset( $new_columns['date'] );

        return $new_columns;
    }

    /**
     * Render custom admin columns
     */
    public function render_admin_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'event_date':
                $event_date = get_post_meta( $post_id, '_cme_event_date', true );
                if ( $event_date ) {
                    $timestamp = strtotime( $event_date );
                    $formatted = date_i18n( 'Y.m.d.', $timestamp );

                    // Color coding based on proximity
                    $days_until = ( $timestamp - time() ) / DAY_IN_SECONDS;
                    $class = '';

                    if ( $days_until < 0 ) {
                        $class = 'cme-date-past';
                    } elseif ( $days_until <= 7 ) {
                        $class = 'cme-date-urgent';
                    } elseif ( $days_until <= 30 ) {
                        $class = 'cme-date-soon';
                    }

                    echo '<span class="' . esc_attr( $class ) . '">' . esc_html( $formatted ) . '</span>';
                } else {
                    echo '—';
                }
                break;

            case 'deadline_type':
                $deadline_type = get_post_meta( $post_id, '_cme_deadline_type', true );
                if ( $deadline_type ) {
                    $types = array(
                        'hard'     => __( 'Kritikus', 'cloudmentor-events' ),
                        'soft'     => __( 'Ajánlott', 'cloudmentor-events' ),
                        'optional' => __( 'Opcionális', 'cloudmentor-events' ),
                    );
                    $class = 'cme-deadline-' . $deadline_type;
                    echo '<span class="' . esc_attr( $class ) . '">' . esc_html( $types[ $deadline_type ] ?? $deadline_type ) . '</span>';
                } else {
                    echo '—';
                }
                break;
        }
    }

    /**
     * Make columns sortable
     */
    public function sortable_columns( $columns ) {
        $columns['event_date'] = 'event_date';
        return $columns;
    }

    /**
     * Handle column sorting
     */
    public function orderby_event_date( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if ( 'event_date' === $query->get( 'orderby' ) ) {
            $query->set( 'meta_key', '_cme_event_date' );
            $query->set( 'orderby', 'meta_value' );
        }
    }
}

// Initialize
CME_Post_Type::get_instance();

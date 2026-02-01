<?php
/**
 * Widget for displaying events
 *
 * @package CloudMentor_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Events Widget class
 */
class CME_Events_Widget extends WP_Widget {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'cme_events_widget',
            __( 'CloudMentor Események', 'cloudmentor-events' ),
            array(
                'description'                 => __( 'Közelgő cloud események megjelenítése kompakt nézetben.', 'cloudmentor-events' ),
                'customize_selective_refresh' => true,
                'classname'                   => 'cme-widget',
            )
        );
    }

    /**
     * Front-end display of widget
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {
        $title = ! empty( $instance['title'] ) ?
            apply_filters( 'widget_title', $instance['title'] ) :
            __( 'Közelgő események', 'cloudmentor-events' );

        $count         = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 5;
        $category      = ! empty( $instance['category'] ) ? $instance['category'] : '';
        $type          = ! empty( $instance['type'] ) ? $instance['type'] : '';
        $show_category = isset( $instance['show_category'] ) ? (bool) $instance['show_category'] : true;
        $show_type     = isset( $instance['show_type'] ) ? (bool) $instance['show_type'] : true;

        echo $args['before_widget'];

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        // Build shortcode attributes
        $shortcode_atts = array(
            'count'         => $count,
            'show_category' => $show_category ? 'true' : 'false',
            'show_type'     => $show_type ? 'true' : 'false',
            'class'         => 'cme-widget-list',
        );

        if ( ! empty( $category ) ) {
            $shortcode_atts['category'] = $category;
        }

        if ( ! empty( $type ) ) {
            $shortcode_atts['type'] = $type;
        }

        // Build shortcode string
        $shortcode = '[cloud-events';
        foreach ( $shortcode_atts as $key => $value ) {
            $shortcode .= ' ' . $key . '="' . esc_attr( $value ) . '"';
        }
        $shortcode .= ']';

        echo do_shortcode( $shortcode );

        echo $args['after_widget'];
    }

    /**
     * Back-end widget form
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {
        $title         = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Közelgő események', 'cloudmentor-events' );
        $count         = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 5;
        $category      = ! empty( $instance['category'] ) ? $instance['category'] : '';
        $type          = ! empty( $instance['type'] ) ? $instance['type'] : '';
        $show_category = isset( $instance['show_category'] ) ? (bool) $instance['show_category'] : true;
        $show_type     = isset( $instance['show_type'] ) ? (bool) $instance['show_type'] : true;

        // Get taxonomies for dropdowns
        $categories = get_terms( array(
            'taxonomy'   => 'event_category',
            'hide_empty' => false,
        ) );

        $types = get_terms( array(
            'taxonomy'   => 'event_type',
            'hide_empty' => false,
        ) );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Cím:', 'cloudmentor-events' ); ?>
            </label>
            <input class="widefat"
                   id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>">
                <?php esc_html_e( 'Megjelenített események száma:', 'cloudmentor-events' ); ?>
            </label>
            <input class="tiny-text"
                   id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>"
                   type="number"
                   min="1"
                   max="20"
                   value="<?php echo esc_attr( $count ); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>">
                <?php esc_html_e( 'Platform kategória szűrés:', 'cloudmentor-events' ); ?>
            </label>
            <select class="widefat"
                    id="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>"
                    name="<?php echo esc_attr( $this->get_field_name( 'category' ) ); ?>">
                <option value=""><?php esc_html_e( 'Összes platform', 'cloudmentor-events' ); ?></option>
                <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                    <?php foreach ( $categories as $cat ) : ?>
                        <option value="<?php echo esc_attr( $cat->slug ); ?>"
                                <?php selected( $category, $cat->slug ); ?>>
                            <?php echo esc_html( $cat->name ); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'type' ) ); ?>">
                <?php esc_html_e( 'Esemény típus szűrés:', 'cloudmentor-events' ); ?>
            </label>
            <select class="widefat"
                    id="<?php echo esc_attr( $this->get_field_id( 'type' ) ); ?>"
                    name="<?php echo esc_attr( $this->get_field_name( 'type' ) ); ?>">
                <option value=""><?php esc_html_e( 'Összes típus', 'cloudmentor-events' ); ?></option>
                <?php if ( ! empty( $types ) && ! is_wp_error( $types ) ) : ?>
                    <?php foreach ( $types as $t ) : ?>
                        <option value="<?php echo esc_attr( $t->slug ); ?>"
                                <?php selected( $type, $t->slug ); ?>>
                            <?php echo esc_html( $t->name ); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </p>

        <p>
            <input type="checkbox"
                   class="checkbox"
                   id="<?php echo esc_attr( $this->get_field_id( 'show_category' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'show_category' ) ); ?>"
                   <?php checked( $show_category ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_category' ) ); ?>">
                <?php esc_html_e( 'Platform kategória megjelenítése', 'cloudmentor-events' ); ?>
            </label>
        </p>

        <p>
            <input type="checkbox"
                   class="checkbox"
                   id="<?php echo esc_attr( $this->get_field_id( 'show_type' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'show_type' ) ); ?>"
                   <?php checked( $show_type ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_type' ) ); ?>">
                <?php esc_html_e( 'Esemény típus megjelenítése', 'cloudmentor-events' ); ?>
            </label>
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     * @return array Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();

        $instance['title']         = sanitize_text_field( $new_instance['title'] ?? '' );
        $instance['count']         = absint( $new_instance['count'] ?? 5 );
        $instance['category']      = sanitize_text_field( $new_instance['category'] ?? '' );
        $instance['type']          = sanitize_text_field( $new_instance['type'] ?? '' );
        $instance['show_category'] = isset( $new_instance['show_category'] ) ? 1 : 0;
        $instance['show_type']     = isset( $new_instance['show_type'] ) ? 1 : 0;

        return $instance;
    }
}

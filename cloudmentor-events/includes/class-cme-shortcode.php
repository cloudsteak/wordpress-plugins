<?php
/**
 * Shortcode for displaying events
 *
 * @package CloudMentor_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode class
 */
class CME_Shortcode {

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
        add_shortcode( 'cloud-events', array( $this, 'render_shortcode' ) );
        add_shortcode( 'cloudmentor-events', array( $this, 'render_shortcode' ) );
    }

    /**
     * Render shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_shortcode( $atts ) {
        $settings = get_option( 'cme_settings', array() );

        $atts = shortcode_atts(
            array(
                'count'         => $settings['events_count'] ?? 5,
                'category'      => '',
                'type'          => '',
                'show_category' => $settings['show_category'] ?? true,
                'show_type'     => $settings['show_type'] ?? true,
                'date_format'   => $settings['date_format'] ?? 'hungarian',
                'class'         => '',
                'show_past'     => false,
            ),
            $atts,
            'cloud-events'
        );

        // Convert string booleans
        $atts['show_category'] = filter_var( $atts['show_category'], FILTER_VALIDATE_BOOLEAN );
        $atts['show_type']     = filter_var( $atts['show_type'], FILTER_VALIDATE_BOOLEAN );
        $atts['show_past']     = filter_var( $atts['show_past'], FILTER_VALIDATE_BOOLEAN );

        // Get events
        $events = $this->get_events( $atts );

        if ( empty( $events ) ) {
            return '<div class="cme-no-events">' .
                   esc_html__( 'Nincs közelgő esemény.', 'cloudmentor-events' ) .
                   '</div>';
        }

        return $this->render_events_list( $events, $atts );
    }

    /**
     * Get events from database
     *
     * @param array $atts Query parameters.
     * @return array WP_Post objects.
     */
    public function get_events( $atts ) {
        $settings = get_option( 'cme_settings', array() );
        $past_events_count = $settings['past_events_count'] ?? 0;
        $past_events_days  = $settings['past_events_days'] ?? 7;
        $today = current_time( 'Y-m-d' );

        // Base tax query
        $tax_query = array();

        if ( ! empty( $atts['category'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'event_category',
                'field'    => 'slug',
                'terms'    => array_map( 'trim', explode( ',', $atts['category'] ) ),
            );
        }

        if ( ! empty( $atts['type'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'event_type',
                'field'    => 'slug',
                'terms'    => array_map( 'trim', explode( ',', $atts['type'] ) ),
            );
        }

        if ( count( $tax_query ) > 1 ) {
            $tax_query['relation'] = 'AND';
        }

        $all_events = array();

        // Get future events
        $future_args = array(
            'post_type'      => 'cloud_event',
            'posts_per_page' => absint( $atts['count'] ),
            'post_status'    => 'publish',
            'meta_key'       => '_cme_event_date',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'     => '_cme_event_date',
                    'value'   => $today,
                    'compare' => '>=',
                    'type'    => 'DATE',
                ),
            ),
        );

        if ( ! empty( $tax_query ) ) {
            $future_args['tax_query'] = $tax_query;
        }

        $future_query = new WP_Query( $future_args );
        $all_events = $future_query->posts;

        // Get past events if enabled
        if ( $past_events_count > 0 && ! $atts['show_past'] ) {
            $min_date = date( 'Y-m-d', strtotime( "-{$past_events_days} days" ) );

            $past_args = array(
                'post_type'      => 'cloud_event',
                'posts_per_page' => absint( $past_events_count ),
                'post_status'    => 'publish',
                'meta_key'       => '_cme_event_date',
                'orderby'        => 'meta_value',
                'order'          => 'DESC',
                'meta_query'     => array(
                    'relation' => 'AND',
                    array(
                        'key'     => '_cme_event_date',
                        'value'   => $today,
                        'compare' => '<',
                        'type'    => 'DATE',
                    ),
                    array(
                        'key'     => '_cme_event_date',
                        'value'   => $min_date,
                        'compare' => '>=',
                        'type'    => 'DATE',
                    ),
                ),
            );

            if ( ! empty( $tax_query ) ) {
                $past_args['tax_query'] = $tax_query;
            }

            $past_query = new WP_Query( $past_args );

            // Merge and sort by date
            $all_events = array_merge( $past_query->posts, $all_events );
        }

        // If show_past is explicitly true, get all events without date filter
        if ( $atts['show_past'] ) {
            $all_args = array(
                'post_type'      => 'cloud_event',
                'posts_per_page' => absint( $atts['count'] ),
                'post_status'    => 'publish',
                'meta_key'       => '_cme_event_date',
                'orderby'        => 'meta_value',
                'order'          => 'ASC',
            );

            if ( ! empty( $tax_query ) ) {
                $all_args['tax_query'] = $tax_query;
            }

            $all_query = new WP_Query( $all_args );
            return $all_query->posts;
        }

        // Sort all events by date
        usort( $all_events, function( $a, $b ) {
            $date_a = get_post_meta( $a->ID, '_cme_event_date', true );
            $date_b = get_post_meta( $b->ID, '_cme_event_date', true );
            return strtotime( $date_a ) - strtotime( $date_b );
        } );

        return $all_events;
    }

    /**
     * Render events list HTML
     *
     * @param array $events WP_Post objects.
     * @param array $atts   Display attributes.
     * @return string HTML output.
     */
    public function render_events_list( $events, $atts ) {
        $settings     = get_option( 'cme_settings', array() );
        $color_scheme = $settings['color_scheme'] ?? 'default';
        $animation    = $settings['animation'] ?? 'slide';

        $classes = array(
            'cme-events-list',
            'cme-scheme-' . sanitize_html_class( $color_scheme ),
            'cme-animation-' . sanitize_html_class( $animation ),
        );

        if ( ! empty( $atts['class'] ) ) {
            $classes[] = sanitize_html_class( $atts['class'] );
        }

        // Themify compatibility
        $classes[] = 'themify-compat';

        ob_start();
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <?php foreach ( $events as $event ) : ?>
                <?php echo $this->render_event_item( $event, $atts ); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single event item
     *
     * @param WP_Post $event Event post object.
     * @param array   $atts  Display attributes.
     * @return string HTML output.
     */
    public function render_event_item( $event, $atts ) {
        // Get meta data
        $event_date    = get_post_meta( $event->ID, '_cme_event_date', true );
        $description   = get_post_meta( $event->ID, '_cme_description', true );
        $source_url    = get_post_meta( $event->ID, '_cme_source_url', true );
        $deadline_type = get_post_meta( $event->ID, '_cme_deadline_type', true );

        // Get terms
        $categories = wp_get_post_terms( $event->ID, 'event_category' );
        $types      = wp_get_post_terms( $event->ID, 'event_type' );

        // Format date
        $formatted_date = $this->format_date( $event_date, $atts['date_format'] );

        // Calculate urgency class
        $urgency_class = $this->get_urgency_class( $event_date );

        // Deadline type class
        $deadline_class = $deadline_type ? 'cme-deadline-' . $deadline_type : '';

        // Get relative time tooltip
        $relative_time_tooltip = $this->get_relative_time_tooltip( $event_date );

        ob_start();
        ?>
        <div class="cme-event-item <?php echo esc_attr( $urgency_class . ' ' . $deadline_class ); ?>"
             data-event-id="<?php echo esc_attr( $event->ID ); ?>"
             title="<?php echo esc_attr( $relative_time_tooltip ); ?>">

            <div class="cme-event-header" role="button" tabindex="0" aria-expanded="false">
                <div class="cme-event-date">
                    <span class="cme-date-text" title="<?php echo esc_attr( $relative_time_tooltip ); ?>"><?php echo esc_html( $formatted_date ); ?></span>
                    <?php if ( $deadline_type ) : ?>
                        <span class="cme-deadline-indicator cme-deadline-<?php echo esc_attr( $deadline_type ); ?>" title="<?php
                            switch ( $deadline_type ) {
                                case 'hard':
                                    esc_attr_e( 'Kritikus változás', 'cloudmentor-events' );
                                    break;
                                case 'soft':
                                    esc_attr_e( 'Ajánlott változás', 'cloudmentor-events' );
                                    break;
                                case 'optional':
                                    esc_attr_e( 'Opcionális változás', 'cloudmentor-events' );
                                    break;
                            }
                        ?>">
                            <?php if ( 'hard' === $deadline_type ) : ?>
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                                </svg>
                            <?php elseif ( 'soft' === $deadline_type ) : ?>
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                    <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                                </svg>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="cme-event-meta">
                    <?php if ( $atts['show_category'] && ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                        <span class="cme-event-category cme-category-<?php echo esc_attr( $categories[0]->slug ); ?>">
                            <?php echo esc_html( $categories[0]->name ); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ( $atts['show_type'] && ! empty( $types ) && ! is_wp_error( $types ) ) : ?>
                        <?php foreach ( $types as $type ) : ?>
                            <span class="cme-event-type cme-type-<?php echo esc_attr( $type->slug ); ?>">
                                <?php echo esc_html( $type->name ); ?>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="cme-event-title">
                    <?php echo esc_html( $event->post_title ); ?>
                </div>

                <div class="cme-event-toggle">
                    <span class="cme-toggle-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </span>
                </div>
            </div>

            <div class="cme-event-details" aria-hidden="true">
                <?php if ( ! empty( $description ) ) : ?>
                    <div class="cme-event-description">
                        <?php echo wp_kses_post( $description ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $source_url ) ) : ?>
                    <div class="cme-event-source">
                        <a href="<?php echo esc_url( $source_url ); ?>"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cme-source-link">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" style="margin-right: 4px; vertical-align: middle;">
                                <path d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5z"/>
                                <path d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0v-5z"/>
                            </svg>
                            <?php esc_html_e( 'Hasznos link', 'cloudmentor-events' ); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ( $deadline_type ) : ?>
                    <div class="cme-deadline-info cme-deadline-info-<?php echo esc_attr( $deadline_type ); ?>">
                        <?php if ( 'hard' === $deadline_type ) : ?>
                            <strong><?php esc_html_e( 'Kritikus', 'cloudmentor-events' ); ?>:</strong>
                            <?php esc_html_e( 'Ez egy fontos változás, a szolgáltatás/funkció ezen a napon megszűnik vagy megváltozik. A zavartalan működéshez beavatkozás szükséges.', 'cloudmentor-events' ); ?>
                        <?php elseif ( 'soft' === $deadline_type ) : ?>
                            <strong><?php esc_html_e( 'Ajánlott', 'cloudmentor-events' ); ?>:</strong>
                            <?php esc_html_e( 'Ez egy javasolt módosítás, vagy egy új funkció. A szolgáltatás működését közvetlenül nem befolyásolja.', 'cloudmentor-events' ); ?>
                        <?php elseif ( 'optional' === $deadline_type ) : ?>
                            <strong><?php esc_html_e( 'Opcionális', 'cloudmentor-events' ); ?>:</strong>
                            <?php esc_html_e( 'Ez egy opcionális változás vagy kényelmi funkció, nem kötelező alkalmazni.', 'cloudmentor-events' ); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Format date based on setting
     *
     * @param string $date   Date in Y-m-d format.
     * @param string $format Format type.
     * @return string Formatted date.
     */
    public function format_date( $date, $format = 'hungarian' ) {
        if ( empty( $date ) ) {
            return '';
        }

        $timestamp = strtotime( $date );

        switch ( $format ) {
            case 'iso':
                return date( 'Y-m-d', $timestamp );

            case 'relative':
                $days = round( ( $timestamp - time() ) / DAY_IN_SECONDS );
                if ( $days < 0 ) {
                    return sprintf(
                        _n( '%d napja', '%d napja', abs( $days ), 'cloudmentor-events' ),
                        abs( $days )
                    );
                } elseif ( 0 === $days ) {
                    return __( 'Ma', 'cloudmentor-events' );
                } elseif ( 1 === $days ) {
                    return __( 'Holnap', 'cloudmentor-events' );
                } else {
                    return sprintf(
                        _n( '%d nap múlva', '%d nap múlva', $days, 'cloudmentor-events' ),
                        $days
                    );
                }

            case 'hungarian':
            default:
                return date( 'Y.m.d.', $timestamp );
        }
    }

    /**
     * Get urgency class based on date
     *
     * @param string $date Event date.
     * @return string CSS class.
     */
    public function get_urgency_class( $date ) {
        if ( empty( $date ) ) {
            return '';
        }

        $event_date  = strtotime( $date );
        $today       = strtotime( 'today' );
        $days_until  = round( ( $event_date - $today ) / DAY_IN_SECONDS );

        if ( $days_until < 0 ) {
            // Archived: yesterday or earlier
            return 'cme-urgency-past';
        } elseif ( $days_until === 0 ) {
            // Today: special warning, not past!
            return 'cme-urgency-today';
        } elseif ( $days_until <= 7 ) {
            return 'cme-urgency-critical';
        } elseif ( $days_until <= 30 ) {
            return 'cme-urgency-soon';
        } elseif ( $days_until <= 90 ) {
            return 'cme-urgency-upcoming';
        }

        return 'cme-urgency-future';
    }

    /**
     * Get relative time tooltip text
     *
     * @param string $date Event date.
     * @return string Tooltip text.
     */
    public function get_relative_time_tooltip( $date ) {
        if ( empty( $date ) ) {
            return '';
        }

        $timestamp  = strtotime( $date );
        $now        = strtotime( 'today' );
        $days       = round( ( $timestamp - $now ) / DAY_IN_SECONDS );

        if ( $days < 0 ) {
            $abs_days = abs( $days );
            if ( $abs_days === 1 ) {
                return __( 'Tegnap lejárt', 'cloudmentor-events' );
            }
            return sprintf(
                /* translators: %d: number of days */
                __( '%d napja lejárt', 'cloudmentor-events' ),
                $abs_days
            );
        } elseif ( $days === 0 ) {
            return __( 'Mai határidő', 'cloudmentor-events' );
        } elseif ( $days === 1 ) {
            return __( 'Holnap', 'cloudmentor-events' );
        } else {
            return sprintf(
                /* translators: %d: number of days */
                __( '%d nap múlva', 'cloudmentor-events' ),
                $days
            );
        }
    }
}

// Initialize
CME_Shortcode::get_instance();

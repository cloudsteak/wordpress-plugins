<?php
/**
 * Plugin uninstall handler.
 *
 * @package CloudGlossary
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! get_option( 'cg_delete_data_on_uninstall', false ) ) {
	return;
}

$post_ids = get_posts(
	array(
		'post_type'      => 'cloud_service',
		'post_status'    => 'any',
		'numberposts'    => -1,
		'fields'         => 'ids',
		'suppress_filters' => true,
	)
);

foreach ( $post_ids as $post_id ) {
	wp_delete_post( $post_id, true );
}

$taxonomies = array( 'cloud_category' );

foreach ( $taxonomies as $taxonomy ) {
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $terms ) ) {
		continue;
	}

	foreach ( $terms as $term ) {
		wp_delete_term( $term->term_id, $taxonomy );
	}
}

global $wpdb;

$option_names = $wpdb->get_col(
	"SELECT option_name
	FROM {$wpdb->options}
	WHERE option_name LIKE 'cg\\_%'
	OR option_name LIKE '_transient_cg\\_%'
	OR option_name LIKE '_transient_timeout_cg\\_%'"
);

foreach ( $option_names as $option_name ) {
	if ( 0 === strpos( $option_name, '_transient_timeout_' ) ) {
		continue;
	}

	if ( 0 === strpos( $option_name, '_transient_' ) ) {
		$transient_key = substr( $option_name, strlen( '_transient_' ) );
		delete_transient( $transient_key );
		continue;
	}

	delete_option( $option_name );
}

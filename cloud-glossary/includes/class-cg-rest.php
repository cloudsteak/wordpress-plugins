<?php
/**
 * Custom REST API endpoints.
 *
 * @package CloudGlossary
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CG_Rest {

	/**
	 * Cache key for serialized services payload.
	 */
	const SERVICES_CACHE_KEY = 'cg_services_cache';

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'save_post_cloud_service', array( __CLASS__, 'invalidate_services_cache' ) );
		add_action( 'set_object_terms', array( $this, 'invalidate_on_object_terms' ), 10, 6 );
		add_action( 'created_term', array( $this, 'invalidate_on_term_event' ), 10, 4 );
		add_action( 'edited_term', array( $this, 'invalidate_on_term_event' ), 10, 4 );
		add_action( 'delete_term', array( $this, 'invalidate_on_term_delete' ), 10, 5 );
	}

	/**
	 * Register custom REST routes.
	 */
	public function register_routes() {
		register_rest_route(
			'cloud-glossary/v1',
			'/services',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_services' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'cloud-glossary/v1',
			'/services/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_service' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Return all published services.
	 *
	 * @return WP_REST_Response
	 */
	public function get_services() {
		$services = get_transient( self::SERVICES_CACHE_KEY );

		if ( ! is_array( $services ) ) {
			$services = $this->build_services_payload();
			set_transient( self::SERVICES_CACHE_KEY, $services, HOUR_IN_SECONDS );
		}

		$response = new WP_REST_Response( $services, 200 );
		$response->header( 'Content-Type', 'application/json; charset=utf-8' );
		return $response;
	}

	/**
	 * Return single service by ID.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_service( WP_REST_Request $request ) {
		$service_id = (int) $request->get_param( 'id' );
		$services   = get_transient( self::SERVICES_CACHE_KEY );

		if ( ! is_array( $services ) ) {
			$services = $this->build_services_payload();
			set_transient( self::SERVICES_CACHE_KEY, $services, HOUR_IN_SECONDS );
		}

		foreach ( $services as $service ) {
			if ( (int) $service['id'] === $service_id ) {
				$response = new WP_REST_Response( $service, 200 );
				$response->header( 'Content-Type', 'application/json; charset=utf-8' );
				return $response;
			}
		}

		return new WP_Error(
			'cg_service_not_found',
			__( 'A kért szolgáltatás nem található.', 'cloud-glossary' ),
			array( 'status' => 404 )
		);
	}

	/**
	 * Build serialized services payload for REST responses.
	 *
	 * @return array
	 */
	private function build_services_payload() {
		$posts = get_posts(
			array(
				'post_type'              => CG_CPT::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => true,
			)
		);

		$services = array_map( array( $this, 'serialize_service' ), $posts );

		usort(
			$services,
			static function( $a, $b ) {
				$order_compare = (int) $a['order'] <=> (int) $b['order'];
				if ( 0 !== $order_compare ) {
					return $order_compare;
				}

				return strnatcasecmp( (string) $a['title'], (string) $b['title'] );
			}
		);

		return $services;
	}

	/**
	 * Convert a cloud service post object into API payload.
	 *
	 * @param WP_Post $post Service post.
	 * @return array
	 */
	private function serialize_service( $post ) {
		$service_id = (int) $post->ID;

		return array(
			'id'          => $service_id,
			'slug'        => $post->post_name,
			'title'       => get_the_title( $post ),
			'description' => wp_strip_all_tags( (string) $post->post_content ),
			'category'    => CG_Admin::get_single_term_slug( $service_id, CG_CPT::TAX_CATEGORY ),
			'order'       => (int) get_post_meta( $service_id, '_cg_order', true ),
			'providers'   => array(
				'aws'   => $this->provider_payload( $service_id, 'aws' ),
				'azure' => $this->provider_payload( $service_id, 'azure' ),
				'gcp'   => $this->provider_payload( $service_id, 'gcp' ),
			),
		);
	}

	/**
	 * Build provider payload.
	 *
	 * @param int    $service_id Service ID.
	 * @param string $provider Provider slug.
	 * @return array
	 */
	private function provider_payload( $service_id, $provider ) {
		$name        = (string) get_post_meta( $service_id, '_cg_' . $provider . '_name', true );
		$description = (string) get_post_meta( $service_id, '_cg_' . $provider . '_short_description', true );
		$docs_url    = (string) get_post_meta( $service_id, '_cg_' . $provider . '_official_docs_url', true );
		$related_raw = get_post_meta( $service_id, '_cg_' . $provider . '_related_posts', true );
		$related_raw = is_array( $related_raw ) ? $related_raw : array();

		$related_posts = array();
		foreach ( $related_raw as $item ) {
			if ( ! is_array( $item ) || empty( $item['post_id'] ) ) {
				continue;
			}

			$related_post = get_post( (int) $item['post_id'] );
			if ( ! $related_post || 'post' !== $related_post->post_type || 'publish' !== $related_post->post_status ) {
				continue;
			}

			$custom_title = sanitize_text_field( (string) ( $item['custom_title'] ?? '' ) );
			$related_posts[] = array(
				'url'   => get_permalink( $related_post ),
				'title' => '' !== $custom_title ? $custom_title : get_the_title( $related_post ),
			);
		}

		return array(
			'name'              => $name,
			'short_description' => $description,
			'official_docs_url' => $docs_url,
			'related_posts'     => $related_posts,
		);
	}

	/**
	 * Invalidate services cache.
	 */
	public static function invalidate_services_cache() {
		delete_transient( self::SERVICES_CACHE_KEY );
	}

	/**
	 * Invalidate cache when category terms are changed on cloud_service.
	 */
	public function invalidate_on_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		unset( $terms, $tt_ids, $append, $old_tt_ids );

		if ( CG_CPT::POST_TYPE !== get_post_type( (int) $object_id ) ) {
			return;
		}

		if ( CG_CPT::TAX_CATEGORY === $taxonomy ) {
			self::invalidate_services_cache();
		}
	}

	/**
	 * Invalidate cache on term create/edit.
	 */
	public function invalidate_on_term_event( $term_id, $tt_id, $taxonomy, $args ) {
		unset( $term_id, $tt_id, $args );

		if ( CG_CPT::TAX_CATEGORY === $taxonomy ) {
			self::invalidate_services_cache();
		}
	}

	/**
	 * Invalidate cache on term delete.
	 */
	public function invalidate_on_term_delete( $term, $tt_id, $taxonomy, $deleted_term, $object_ids ) {
		unset( $term, $tt_id, $deleted_term, $object_ids );

		if ( CG_CPT::TAX_CATEGORY === $taxonomy ) {
			self::invalidate_services_cache();
		}
	}
}

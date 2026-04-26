<?php
/**
 * Meta boxes, meta save and autocomplete endpoints.
 *
 * @package CloudGlossary
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CG_Meta {

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		add_action( 'save_post_cloud_service', array( $this, 'save_meta' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'wp_ajax_cg_search_services', array( $this, 'ajax_search_services' ) );
		add_action( 'wp_ajax_cg_search_posts', array( $this, 'ajax_search_posts' ) );
	}

	/**
	 * Register meta box.
	 */
	public function register_meta_box() {
		add_meta_box(
			'cg_service_details',
			__( 'Szolgáltatás részletei', 'cloud-glossary' ),
			array( $this, 'render_meta_box' ),
			CG_CPT::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render meta box markup.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_meta_box( $post ) {
		$short_description = (string) get_post_meta( $post->ID, '_cg_short_description', true );
		$docs_url          = (string) get_post_meta( $post->ID, '_cg_official_docs_url', true );
		$equivalents       = get_post_meta( $post->ID, '_cg_equivalents', true );
		$related_posts     = get_post_meta( $post->ID, '_cg_related_posts', true );
		$order             = (int) get_post_meta( $post->ID, '_cg_order', true );

		$equivalents   = is_array( $equivalents ) ? array_values( array_map( 'intval', $equivalents ) ) : array();
		$related_posts = is_array( $related_posts ) ? $related_posts : array();
		$equivalent_ui = array();
		$related_ui    = array();

		$provider_slug = CG_Admin::get_single_term_slug( $post->ID, CG_CPT::TAX_PROVIDER );

		foreach ( $equivalents as $service_id ) {
			$service = get_post( $service_id );
			if ( ! $service || CG_CPT::POST_TYPE !== $service->post_type ) {
				continue;
			}

			$equivalent_ui[] = array(
				'id'    => (int) $service->ID,
				'title' => $service->post_title,
			);
		}

		foreach ( $related_posts as $item ) {
			if ( ! is_array( $item ) || empty( $item['post_id'] ) ) {
				continue;
			}

			$target = get_post( (int) $item['post_id'] );
			if ( ! $target || 'post' !== $target->post_type ) {
				continue;
			}

			$related_ui[] = array(
				'post_id'      => (int) $target->ID,
				'title'        => $target->post_title,
				'custom_title' => sanitize_text_field( (string) ( $item['custom_title'] ?? '' ) ),
			);
		}

		wp_nonce_field( 'cg_meta_save', 'cg_meta_nonce' );
		?>
		<div class="cg-meta">
			<p>
				<label for="cg_short_description"><strong><?php echo esc_html__( 'Rövid leírás', 'cloud-glossary' ); ?></strong></label><br />
				<textarea id="cg_short_description" name="cg_short_description" rows="4" class="widefat"><?php echo esc_textarea( $short_description ); ?></textarea>
				<span class="cg-char-counter" data-target="cg_short_description" data-max="500">0 / 500</span>
			</p>

			<p>
				<label for="cg_official_docs_url"><strong><?php echo esc_html__( 'Hivatalos dokumentáció URL', 'cloud-glossary' ); ?></strong></label><br />
				<input id="cg_official_docs_url" name="cg_official_docs_url" type="url" class="widefat" value="<?php echo esc_attr( $docs_url ); ?>" />
			</p>

			<div class="cg-field-group cg-autocomplete" data-action="cg_search_services" data-hidden="cg_equivalents_json" data-exclude-provider="<?php echo esc_attr( $provider_slug ); ?>" data-selected="<?php echo esc_attr( wp_json_encode( $equivalent_ui ) ); ?>">
				<label for="cg_equivalents_input"><strong><?php echo esc_html__( 'Ekvivalensek', 'cloud-glossary' ); ?></strong></label>
				<input id="cg_equivalents_input" type="text" class="widefat cg-ac-input" autocomplete="off" placeholder="<?php echo esc_attr__( 'Keresés cloud szolgáltatások között...', 'cloud-glossary' ); ?>" />
				<ul class="cg-ac-results" hidden></ul>
				<ul class="cg-selected-list" data-kind="equivalents"></ul>
				<input type="hidden" id="cg_equivalents_json" name="cg_equivalents_json" value="<?php echo esc_attr( wp_json_encode( $equivalents ) ); ?>" />
			</div>

			<div class="cg-field-group cg-autocomplete" data-action="cg_search_posts" data-hidden="cg_related_posts_json" data-selected="<?php echo esc_attr( wp_json_encode( $related_ui ) ); ?>">
				<label for="cg_related_posts_input"><strong><?php echo esc_html__( 'Kapcsolódó bejegyzések', 'cloud-glossary' ); ?></strong></label>
				<input id="cg_related_posts_input" type="text" class="widefat cg-ac-input" autocomplete="off" placeholder="<?php echo esc_attr__( 'Keresés bejegyzések között...', 'cloud-glossary' ); ?>" />
				<ul class="cg-ac-results" hidden></ul>
				<ul class="cg-selected-list" data-kind="related"></ul>
				<input type="hidden" id="cg_related_posts_json" name="cg_related_posts_json" value="<?php echo esc_attr( wp_json_encode( $related_ui ) ); ?>" />
			</div>

			<p>
				<label for="cg_order"><strong><?php echo esc_html__( 'Megjelenítési sorrend', 'cloud-glossary' ); ?></strong></label><br />
				<input id="cg_order" name="cg_order" type="number" value="<?php echo esc_attr( (string) $order ); ?>" />
			</p>
		</div>
		<?php
	}

	/**
	 * Save meta values.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_meta( $post_id ) {
		$nonce = filter_input( INPUT_POST, 'cg_meta_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'cg_meta_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$short_description = (string) filter_input( INPUT_POST, 'cg_short_description', FILTER_UNSAFE_RAW );
		$short_description = sanitize_textarea_field( wp_unslash( $short_description ) );
		$short_description = mb_substr( $short_description, 0, 500 );
		update_post_meta( $post_id, '_cg_short_description', $short_description );

		$docs_url = (string) filter_input( INPUT_POST, 'cg_official_docs_url', FILTER_UNSAFE_RAW );
		update_post_meta( $post_id, '_cg_official_docs_url', esc_url_raw( wp_unslash( $docs_url ) ) );

		$order = filter_input( INPUT_POST, 'cg_order', FILTER_VALIDATE_INT );
		update_post_meta( $post_id, '_cg_order', false === $order ? 0 : (int) $order );

		$equivalents_json = (string) filter_input( INPUT_POST, 'cg_equivalents_json', FILTER_UNSAFE_RAW );
		$equivalents      = json_decode( wp_unslash( $equivalents_json ), true );
		$equivalents      = is_array( $equivalents ) ? $equivalents : array();
		$provider_slug    = CG_Admin::get_single_term_slug( $post_id, CG_CPT::TAX_PROVIDER );
		$validated_eq     = array();

		foreach ( $equivalents as $service_id ) {
			$service_id = (int) $service_id;
			if ( $service_id <= 0 || (int) $post_id === $service_id ) {
				continue;
			}

			$service = get_post( $service_id );
			if ( ! $service || CG_CPT::POST_TYPE !== $service->post_type || 'publish' !== $service->post_status ) {
				continue;
			}

			if ( $provider_slug ) {
				$eq_provider = CG_Admin::get_single_term_slug( $service_id, CG_CPT::TAX_PROVIDER );
				if ( $eq_provider && $eq_provider === $provider_slug ) {
					continue;
				}
			}

			$validated_eq[] = $service_id;
		}
		update_post_meta( $post_id, '_cg_equivalents', array_values( array_unique( $validated_eq ) ) );

		$related_json = (string) filter_input( INPUT_POST, 'cg_related_posts_json', FILTER_UNSAFE_RAW );
		$related      = json_decode( wp_unslash( $related_json ), true );
		$related      = is_array( $related ) ? $related : array();
		$validated    = array();

		foreach ( $related as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$post_ref = isset( $item['post_id'] ) ? (int) $item['post_id'] : 0;
			if ( $post_ref <= 0 ) {
				continue;
			}

			$target = get_post( $post_ref );
			if ( ! $target || 'post' !== $target->post_type || 'publish' !== $target->post_status ) {
				continue;
			}

			$validated[] = array(
				'post_id'      => $post_ref,
				'custom_title' => sanitize_text_field( (string) ( $item['custom_title'] ?? '' ) ),
			);
		}

		update_post_meta( $post_id, '_cg_related_posts', $validated );
	}

	/**
	 * Register meta keys for REST.
	 */
	public function register_meta() {
		$auth = static function() {
			return current_user_can( 'edit_posts' );
		};

		register_post_meta(
			CG_CPT::POST_TYPE,
			'_cg_short_description',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => $auth,
			)
		);

		register_post_meta(
			CG_CPT::POST_TYPE,
			'_cg_official_docs_url',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => $auth,
			)
		);

		register_post_meta(
			CG_CPT::POST_TYPE,
			'_cg_order',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'integer',
				'auth_callback' => $auth,
			)
		);

		register_post_meta(
			CG_CPT::POST_TYPE,
			'_cg_equivalents',
			array(
				'show_in_rest'  => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type' => 'integer',
						),
					),
				),
				'single'        => true,
				'type'          => 'array',
				'auth_callback' => $auth,
			)
		);

		register_post_meta(
			CG_CPT::POST_TYPE,
			'_cg_related_posts',
			array(
				'show_in_rest'  => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'post_id'      => array( 'type' => 'integer' ),
								'custom_title' => array( 'type' => 'string' ),
							),
						),
					),
				),
				'single'        => true,
				'type'          => 'array',
				'auth_callback' => $auth,
			)
		);
	}

	/**
	 * Search cloud services for equivalents.
	 */
	public function ajax_search_services() {
		$this->authorize_ajax();

		$query            = sanitize_text_field( (string) filter_input( INPUT_GET, 'q', FILTER_UNSAFE_RAW ) );
		$exclude_provider = sanitize_key( (string) filter_input( INPUT_GET, 'exclude_provider', FILTER_UNSAFE_RAW ) );

		$args = array(
			'post_type'      => CG_CPT::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			's'              => $query,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		if ( $exclude_provider ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => CG_CPT::TAX_PROVIDER,
					'field'    => 'slug',
					'terms'    => array( $exclude_provider ),
					'operator' => 'NOT IN',
				),
			);
		}

		$posts    = get_posts( $args );
		$payloads = array();

		foreach ( $posts as $post ) {
			$payloads[] = array(
				'id'    => (int) $post->ID,
				'title' => $post->post_title,
				'meta'  => array(
					'provider' => CG_Admin::get_single_term_slug( $post->ID, CG_CPT::TAX_PROVIDER ),
				),
			);
		}

		wp_send_json( $payloads );
	}

	/**
	 * Search blog posts for related posts.
	 */
	public function ajax_search_posts() {
		$this->authorize_ajax();

		$query = sanitize_text_field( (string) filter_input( INPUT_GET, 'q', FILTER_UNSAFE_RAW ) );
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				's'              => $query,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$payloads = array();
		foreach ( $posts as $post ) {
			$payloads[] = array(
				'id'    => (int) $post->ID,
				'title' => $post->post_title,
				'meta'  => array(),
			);
		}

		wp_send_json( $payloads );
	}

	/**
	 * Guard admin ajax endpoints.
	 */
	private function authorize_ajax() {
		$nonce = (string) filter_input( INPUT_GET, 'nonce', FILTER_UNSAFE_RAW );
		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'cg_autocomplete' ) ) {
			wp_send_json_error( array( 'message' => __( 'Érvénytelen nonce.', 'cloud-glossary' ) ), 403 );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Nincs jogosultságod.', 'cloud-glossary' ) ), 403 );
		}
	}
}

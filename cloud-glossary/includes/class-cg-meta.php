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
	 * Providers handled in the concept editor.
	 *
	 * @var string[]
	 */
	private $providers = array( 'aws', 'azure', 'gcp' );

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		add_action( 'save_post_cloud_service', array( $this, 'save_meta' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'wp_ajax_cg_search_posts', array( $this, 'ajax_search_posts' ) );
	}

	/**
	 * Register meta box.
	 */
	public function register_meta_box() {
		add_meta_box(
			'cg_service_details',
			__( 'Szolgáltatás részletei (szolgáltatónként)', 'cloud-glossary' ),
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
		$order = (int) get_post_meta( $post->ID, '_cg_order', true );

		wp_nonce_field( 'cg_meta_save', 'cg_meta_nonce' );
		?>
		<div class="cg-meta">
			<p>
				<label for="cg_order"><strong><?php echo esc_html__( 'Megjelenítési sorrend (központi)', 'cloud-glossary' ); ?></strong></label><br />
				<input id="cg_order" name="cg_order" type="number" value="<?php echo esc_attr( (string) $order ); ?>" />
			</p>

			<hr />

			<?php foreach ( $this->providers as $provider ) : ?>
				<?php
				$label       = strtoupper( $provider );
				$name_key    = '_cg_' . $provider . '_name';
				$desc_key    = '_cg_' . $provider . '_short_description';
				$docs_key    = '_cg_' . $provider . '_official_docs_url';
				$related_key = '_cg_' . $provider . '_related_posts';

				$name         = (string) get_post_meta( $post->ID, $name_key, true );
				$description  = (string) get_post_meta( $post->ID, $desc_key, true );
				$docs_url     = (string) get_post_meta( $post->ID, $docs_key, true );
				$related_meta = get_post_meta( $post->ID, $related_key, true );
				$related_ui   = $this->build_related_ui( is_array( $related_meta ) ? $related_meta : array() );
				?>
				<div class="cg-provider-block cg-provider-block--<?php echo esc_attr( $provider ); ?>">
					<h3><?php echo esc_html( $label ); ?></h3>
					<p>
						<label for="cg_<?php echo esc_attr( $provider ); ?>_name"><strong><?php echo esc_html__( 'Szolgáltatás neve', 'cloud-glossary' ); ?></strong></label><br />
						<input id="cg_<?php echo esc_attr( $provider ); ?>_name" name="cg_<?php echo esc_attr( $provider ); ?>_name" type="text" class="widefat" value="<?php echo esc_attr( $name ); ?>" />
					</p>
					<p>
						<label for="cg_<?php echo esc_attr( $provider ); ?>_short_description"><strong><?php echo esc_html__( 'Rövid leírás', 'cloud-glossary' ); ?></strong></label><br />
						<textarea id="cg_<?php echo esc_attr( $provider ); ?>_short_description" name="cg_<?php echo esc_attr( $provider ); ?>_short_description" rows="3" class="widefat"><?php echo esc_textarea( $description ); ?></textarea>
						<span class="cg-char-counter" data-target="cg_<?php echo esc_attr( $provider ); ?>_short_description" data-max="500">0 / 500</span>
					</p>
					<p>
						<label for="cg_<?php echo esc_attr( $provider ); ?>_official_docs_url"><strong><?php echo esc_html__( 'Hivatalos dokumentáció URL', 'cloud-glossary' ); ?></strong></label><br />
						<input id="cg_<?php echo esc_attr( $provider ); ?>_official_docs_url" name="cg_<?php echo esc_attr( $provider ); ?>_official_docs_url" type="url" class="widefat" value="<?php echo esc_attr( $docs_url ); ?>" />
					</p>
					<div class="cg-field-group cg-autocomplete" data-action="cg_search_posts" data-hidden="cg_<?php echo esc_attr( $provider ); ?>_related_posts_json" data-selected="<?php echo esc_attr( wp_json_encode( $related_ui ) ); ?>">
						<label for="cg_<?php echo esc_attr( $provider ); ?>_related_posts_input"><strong><?php echo esc_html__( 'Kapcsolódó bejegyzések', 'cloud-glossary' ); ?></strong></label>
						<input id="cg_<?php echo esc_attr( $provider ); ?>_related_posts_input" type="text" class="widefat cg-ac-input" autocomplete="off" placeholder="<?php echo esc_attr__( 'Keresés bejegyzések között...', 'cloud-glossary' ); ?>" />
						<p class="description"><?php echo esc_html__( 'Link hozzáadásához kezdj el gépelni egy már publikált bejegyzés címéből, majd válaszd ki a listából.', 'cloud-glossary' ); ?></p>
						<ul class="cg-ac-results" hidden></ul>
						<ul class="cg-selected-list" data-kind="related"></ul>
						<input type="hidden" id="cg_<?php echo esc_attr( $provider ); ?>_related_posts_json" name="cg_<?php echo esc_attr( $provider ); ?>_related_posts_json" value="<?php echo esc_attr( wp_json_encode( $related_ui ) ); ?>" />
					</div>
				</div>
				<hr />
			<?php endforeach; ?>
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

		$order = filter_input( INPUT_POST, 'cg_order', FILTER_VALIDATE_INT );
		update_post_meta( $post_id, '_cg_order', false === $order ? 0 : (int) $order );

		foreach ( $this->providers as $provider ) {
			$name_key        = 'cg_' . $provider . '_name';
			$desc_key        = 'cg_' . $provider . '_short_description';
			$docs_key        = 'cg_' . $provider . '_official_docs_url';
			$related_json_key = 'cg_' . $provider . '_related_posts_json';

			$name = (string) filter_input( INPUT_POST, $name_key, FILTER_UNSAFE_RAW );
			update_post_meta( $post_id, '_' . $name_key, sanitize_text_field( wp_unslash( $name ) ) );

			$description = (string) filter_input( INPUT_POST, $desc_key, FILTER_UNSAFE_RAW );
			$description = sanitize_textarea_field( wp_unslash( $description ) );
			$description = mb_substr( $description, 0, 500 );
			update_post_meta( $post_id, '_' . $desc_key, $description );

			$docs_url = (string) filter_input( INPUT_POST, $docs_key, FILTER_UNSAFE_RAW );
			update_post_meta( $post_id, '_' . $docs_key, esc_url_raw( wp_unslash( $docs_url ) ) );

			$related_json = (string) filter_input( INPUT_POST, $related_json_key, FILTER_UNSAFE_RAW );
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

			update_post_meta( $post_id, '_cg_' . $provider . '_related_posts', $validated );
		}
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
			'_cg_order',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'integer',
				'auth_callback' => $auth,
			)
		);

		foreach ( $this->providers as $provider ) {
			register_post_meta(
				CG_CPT::POST_TYPE,
				'_cg_' . $provider . '_name',
				array(
					'show_in_rest'  => true,
					'single'        => true,
					'type'          => 'string',
					'auth_callback' => $auth,
				)
			);

			register_post_meta(
				CG_CPT::POST_TYPE,
				'_cg_' . $provider . '_short_description',
				array(
					'show_in_rest'  => true,
					'single'        => true,
					'type'          => 'string',
					'auth_callback' => $auth,
				)
			);

			register_post_meta(
				CG_CPT::POST_TYPE,
				'_cg_' . $provider . '_official_docs_url',
				array(
					'show_in_rest'  => true,
					'single'        => true,
					'type'          => 'string',
					'auth_callback' => $auth,
				)
			);

			register_post_meta(
				CG_CPT::POST_TYPE,
				'_cg_' . $provider . '_related_posts',
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

	/**
	 * Build UI-oriented related post payload.
	 *
	 * @param array $related_posts Raw saved meta array.
	 * @return array
	 */
	private function build_related_ui( $related_posts ) {
		$related_ui = array();

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

		return $related_ui;
	}
}

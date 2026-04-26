<?php
/**
 * Admin list table, filters, duplicate and assets.
 *
 * @package CloudGlossary
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CG_Admin {

	/**
	 * Register hooks.
	 */
	public function init() {
		add_filter( 'manage_cloud_service_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_cloud_service_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-cloud_service_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'apply_sorting' ) );
		add_filter( 'posts_clauses', array( $this, 'taxonomy_sort_clauses' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'filters' ) );
		add_filter( 'parse_query', array( $this, 'apply_filters' ) );
		add_filter( 'post_row_actions', array( $this, 'duplicate_action_link' ), 10, 2 );
		add_action( 'admin_action_cg_duplicate', array( $this, 'handle_duplicate_action' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'render_usage_notice' ) );
		add_action( 'admin_menu', array( $this, 'register_usage_submenu' ) );
	}

	/**
	 * Get single term slug.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return string
	 */
	public static function get_single_term_slug( $post_id, $taxonomy ) {
		$terms = wp_get_object_terms( (int) $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		return (string) $terms[0];
	}

	/**
	 * Columns for list table.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function columns( $columns ) {
		return array(
			'cb'          => $columns['cb'] ?? '<input type="checkbox" />',
			'title'       => __( 'Fogalom', 'cloud-glossary' ),
			'cg_category' => __( 'Kategória', 'cloud-glossary' ),
			'cg_related'  => __( 'Kapcsolódó linkek', 'cloud-glossary' ),
			'cg_order'    => __( 'Sorrend', 'cloud-glossary' ),
			'date'        => __( 'Dátum', 'cloud-glossary' ),
		);
	}

	/**
	 * Render custom column values.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public function render_column( $column, $post_id ) {
		if ( 'cg_category' === $column ) {
			$this->render_term_with_dot( $post_id, CG_CPT::TAX_CATEGORY );
			return;
		}

		if ( 'cg_related' === $column ) {
			$total = 0;
			foreach ( array( 'aws', 'azure', 'gcp' ) as $provider ) {
				$related = get_post_meta( $post_id, '_cg_' . $provider . '_related_posts', true );
				$total  += is_array( $related ) ? count( $related ) : 0;
			}
			echo esc_html( (string) $total );
			return;
		}

		if ( 'cg_order' === $column ) {
			echo esc_html( (string) (int) get_post_meta( $post_id, '_cg_order', true ) );
		}
	}

	/**
	 * Define sortable columns.
	 *
	 * @param array $columns Sortable columns.
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		$columns['cg_category'] = 'cg_category';
		$columns['cg_order']    = 'cg_order';
		return $columns;
	}

	/**
	 * Apply sortable behavior.
	 *
	 * @param WP_Query $query Query object.
	 */
	public function apply_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || CG_CPT::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = (string) $query->get( 'orderby' );
		if ( 'cg_order' === $orderby ) {
			$query->set( 'meta_key', '_cg_order' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * Apply taxonomy sorting SQL clauses.
	 *
	 * @param array    $clauses Clauses.
	 * @param WP_Query $query Query.
	 * @return array
	 */
	public function taxonomy_sort_clauses( $clauses, $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || CG_CPT::POST_TYPE !== $query->get( 'post_type' ) ) {
			return $clauses;
		}

		if ( 'cg_category' !== (string) $query->get( 'orderby' ) ) {
			return $clauses;
		}

		global $wpdb;
		$order = 'DESC' === strtoupper( (string) $query->get( 'order' ) ) ? 'DESC' : 'ASC';

		$clauses['join']   .= " LEFT JOIN {$wpdb->term_relationships} cg_tr ON {$wpdb->posts}.ID = cg_tr.object_id";
		$clauses['join']   .= " LEFT JOIN {$wpdb->term_taxonomy} cg_tt ON cg_tr.term_taxonomy_id = cg_tt.term_taxonomy_id AND cg_tt.taxonomy = '" . esc_sql( CG_CPT::TAX_CATEGORY ) . "'";
		$clauses['join']   .= " LEFT JOIN {$wpdb->terms} cg_t ON cg_tt.term_id = cg_t.term_id";
		$clauses['groupby'] = "{$wpdb->posts}.ID";
		$clauses['orderby'] = "cg_t.name {$order}, {$wpdb->posts}.post_title ASC";

		return $clauses;
	}

	/**
	 * Render category filter.
	 */
	public function filters() {
		global $typenow;
		if ( CG_CPT::POST_TYPE !== $typenow ) {
			return;
		}

		$this->render_filter_dropdown( CG_CPT::TAX_CATEGORY, 'cg_category_filter', __( 'Összes kategória', 'cloud-glossary' ) );
	}

	/**
	 * Apply list filters.
	 *
	 * @param WP_Query $query Query.
	 * @return WP_Query
	 */
	public function apply_filters( $query ) {
		global $pagenow;
		if ( ! is_admin() || 'edit.php' !== $pagenow || CG_CPT::POST_TYPE !== $query->get( 'post_type' ) ) {
			return $query;
		}

		$category = sanitize_key( (string) filter_input( INPUT_GET, 'cg_category_filter', FILTER_UNSAFE_RAW ) );
		if ( $category ) {
			$query->set(
				'tax_query',
				array(
					array(
						'taxonomy' => CG_CPT::TAX_CATEGORY,
						'field'    => 'slug',
						'terms'    => array( $category ),
					),
				)
			);
		}

		return $query;
	}

	/**
	 * Add duplicate row action.
	 *
	 * @param array   $actions Actions.
	 * @param WP_Post $post Post.
	 * @return array
	 */
	public function duplicate_action_link( $actions, $post ) {
		if ( CG_CPT::POST_TYPE !== $post->post_type || ! current_user_can( 'edit_posts' ) ) {
			return $actions;
		}

		$url               = wp_nonce_url( admin_url( 'admin.php?action=cg_duplicate&post=' . (int) $post->ID ), 'cg_duplicate_' . (int) $post->ID );
		$actions['cg_dup'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Duplikálás', 'cloud-glossary' ) . '</a>';
		return $actions;
	}

	/**
	 * Handle duplicate action request.
	 */
	public function handle_duplicate_action() {
		$post_id = (int) filter_input( INPUT_GET, 'post', FILTER_VALIDATE_INT );
		$nonce   = (string) filter_input( INPUT_GET, '_wpnonce', FILTER_UNSAFE_RAW );

		if ( $post_id <= 0 || ! current_user_can( 'edit_posts' ) || ! wp_verify_nonce( sanitize_text_field( $nonce ), 'cg_duplicate_' . $post_id ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod a művelethez.', 'cloud-glossary' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || CG_CPT::POST_TYPE !== $post->post_type ) {
			wp_die( esc_html__( 'Érvénytelen szolgáltatás.', 'cloud-glossary' ) );
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => CG_CPT::POST_TYPE,
				'post_title'   => $post->post_title . ' (' . __( 'másolat', 'cloud-glossary' ) . ')',
				'post_content' => $post->post_content,
				'post_status'  => 'draft',
			)
		);

		if ( ! $new_id || is_wp_error( $new_id ) ) {
			wp_die( esc_html__( 'A másolás nem sikerült.', 'cloud-glossary' ) );
		}

		foreach ( get_post_meta( $post_id ) as $key => $values ) {
			if ( 0 !== strpos( $key, '_cg_' ) ) {
				continue;
			}

			update_post_meta( $new_id, $key, get_post_meta( $post_id, $key, true ) );
		}

		$categories = wp_get_object_terms( $post_id, CG_CPT::TAX_CATEGORY, array( 'fields' => 'ids' ) );
		wp_set_object_terms( $new_id, is_wp_error( $categories ) ? array() : $categories, CG_CPT::TAX_CATEGORY, false );

		wp_safe_redirect( admin_url( 'post.php?post=' . (int) $new_id . '&action=edit' ) );
		exit;
	}

	/**
	 * Enqueue admin assets on cloud_service screens.
	 *
	 * @param string $hook Hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || CG_CPT::POST_TYPE !== $screen->post_type ) {
			return;
		}

		if ( 'edit.php' !== $hook && 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'cg-admin', CG_PLUGIN_URL . 'assets/css/admin.css', array(), CG_VERSION );
		wp_enqueue_script( 'cg-admin', CG_PLUGIN_URL . 'assets/js/admin.js', array(), CG_VERSION, true );
		wp_localize_script(
			'cg-admin',
			'cgAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cg_autocomplete' ),
				'i18n'    => array(
					'remove'      => __( 'Eltávolítás', 'cloud-glossary' ),
					'customTitle' => __( 'Egyedi cím (opcionális)', 'cloud-glossary' ),
				),
			)
		);
	}

	/**
	 * Render usage help notice on Cloud Glossary admin pages.
	 */
	public function render_usage_notice() {
		if ( ! $this->is_cloud_glossary_screen() ) {
			return;
		}

		echo '<div class="notice notice-info">';
		echo '<p><strong>' . esc_html__( 'Beágyazás oldalba vagy bejegyzésbe', 'cloud-glossary' ) . '</strong></p>';
		echo '<p>' . esc_html__( 'Nyiss meg egy oldalt vagy bejegyzést szerkesztésre, és illeszd be ezt a shortcode-ot:', 'cloud-glossary' ) . ' <code>[cloud_glossary]</code></p>';
		echo '<p>' . esc_html__( 'A shortcode automatikusan betölti a Cloud Szótár felületet az adott oldalon.', 'cloud-glossary' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Register usage submenu under Cloud Services.
	 */
	public function register_usage_submenu() {
		add_submenu_page(
			'edit.php?post_type=' . CG_CPT::POST_TYPE,
			__( 'Használat', 'cloud-glossary' ),
			__( 'Használat', 'cloud-glossary' ),
			'edit_posts',
			'cg-usage',
			array( $this, 'render_usage_page' )
		);
	}

	/**
	 * Render usage admin page.
	 */
	public function render_usage_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod az oldal megtekintéséhez.', 'cloud-glossary' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Cloud Glossary használat', 'cloud-glossary' ); ?></h1>
			<p><?php echo esc_html__( 'A Cloud Szótár megjelenítéséhez oldalban vagy bejegyzésben használd az alábbi shortcode-ot:', 'cloud-glossary' ); ?></p>
			<p><code>[cloud_glossary]</code></p>
		</div>
		<?php
	}

	/**
	 * Render term with color dot.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $taxonomy Taxonomy.
	 */
	private function render_term_with_dot( $post_id, $taxonomy ) {
		$terms = wp_get_post_terms( $post_id, $taxonomy );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			echo '&mdash;';
			return;
		}

		$term  = $terms[0];
		$map   = array(
			'halozat'          => 'var(--cg-cat-network,#5B9BD5)',
			'biztonsag'        => 'var(--cg-cat-security,#ED7D31)',
			'terheleselosztas' => 'var(--cg-cat-load,#70AD47)',
			'compute'          => 'var(--cg-cat-compute,#7B68EE)',
			'adat'             => 'var(--cg-cat-data,#E8A33D)',
			'egyeb'            => 'var(--cg-cat-other,#6A7A8E)',
		);
		$color = $map[ $term->slug ] ?? 'var(--cg-cat-other,#6A7A8E)';
		echo '<span class="cg-term-dot" style="background:' . esc_attr( $color ) . ';"></span>';
		echo esc_html( $term->name );
	}

	/**
	 * Render taxonomy filter dropdown.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $name Field name.
	 * @param string $all_label Placeholder label.
	 */
	private function render_filter_dropdown( $taxonomy, $name, $all_label ) {
		$current = sanitize_key( (string) filter_input( INPUT_GET, $name, FILTER_UNSAFE_RAW ) );
		$terms   = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return;
		}

		echo '<select name="' . esc_attr( $name ) . '">';
		echo '<option value="">' . esc_html( $all_label ) . '</option>';
		foreach ( $terms as $term ) {
			echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( $current, $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Check if the current admin screen belongs to Cloud Glossary.
	 *
	 * @return bool
	 */
	private function is_cloud_glossary_screen() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}

		if ( CG_CPT::POST_TYPE === $screen->post_type ) {
			return true;
		}

		return 'edit-' . CG_CPT::TAX_CATEGORY === $screen->id;
	}
}

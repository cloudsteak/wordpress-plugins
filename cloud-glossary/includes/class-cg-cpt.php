<?php
/**
 * Custom post type and taxonomies.
 *
 * @package CloudGlossary
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CG_CPT {

	const POST_TYPE    = 'cloud_service';
	const TAX_CATEGORY = 'cloud_category';
	const ARCHIVE_SLUG = 'cloud-szolgaltatasok';

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type_and_taxonomies' ) );
	}

	/**
	 * Register post type and taxonomies.
	 */
	public static function register_post_type_and_taxonomies() {
		$labels = array(
			'name'                  => __( 'Cloud Szolgáltatások', 'cloud-glossary' ),
			'singular_name'         => __( 'Cloud Szolgáltatás', 'cloud-glossary' ),
			'menu_name'             => __( 'Cloud Szolgáltatások', 'cloud-glossary' ),
			'name_admin_bar'        => __( 'Cloud Szolgáltatás', 'cloud-glossary' ),
			'add_new'               => __( 'Új hozzáadása', 'cloud-glossary' ),
			'add_new_item'          => __( 'Új cloud fogalom hozzáadása', 'cloud-glossary' ),
			'new_item'              => __( 'Új cloud fogalom', 'cloud-glossary' ),
			'edit_item'             => __( 'Cloud fogalom szerkesztése', 'cloud-glossary' ),
			'view_item'             => __( 'Cloud fogalom megtekintése', 'cloud-glossary' ),
			'all_items'             => __( 'Összes cloud fogalom', 'cloud-glossary' ),
			'search_items'          => __( 'Cloud fogalmak keresése', 'cloud-glossary' ),
			'not_found'             => __( 'Nem található cloud fogalom.', 'cloud-glossary' ),
			'not_found_in_trash'    => __( 'A kukában sincs cloud fogalom.', 'cloud-glossary' ),
			'featured_image'        => __( 'Cloud fogalom képe', 'cloud-glossary' ),
			'set_featured_image'    => __( 'Cloud fogalom képének beállítása', 'cloud-glossary' ),
			'remove_featured_image' => __( 'Cloud fogalom képének eltávolítása', 'cloud-glossary' ),
			'use_featured_image'    => __( 'Beállítás cloud fogalom képeként', 'cloud-glossary' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => $labels,
				'public'             => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'rest_base'          => 'cloud-services',
				'has_archive'        => self::ARCHIVE_SLUG,
				'rewrite'            => array(
					'slug'       => self::ARCHIVE_SLUG,
					'with_front' => false,
				),
				'menu_position'      => 25,
				'menu_icon'          => 'dashicons-cloud',
				'supports'           => array( 'title', 'editor', 'custom-fields', 'revisions' ),
				'capability_type'    => 'post',
				'publicly_queryable' => true,
				'query_var'          => true,
			)
		);

		register_taxonomy(
			self::TAX_CATEGORY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Kategóriák', 'cloud-glossary' ),
					'singular_name' => __( 'Kategória', 'cloud-glossary' ),
				),
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rest_base'         => 'cloud-categories',
				'hierarchical'      => true,
				'meta_box_cb'       => 'post_categories_meta_box',
				'rewrite'           => array(
					'slug' => 'cloud-kategoria',
				),
			)
		);
	}

	/**
	 * Register post type and taxonomies for activation flow.
	 */
	public static function register() {
		self::register_post_type_and_taxonomies();
	}

	/**
	 * Seed default taxonomy terms.
	 */
	public static function seed_default_terms() {
		$category_terms = array(
			'halozat'          => __( 'Hálózat', 'cloud-glossary' ),
			'biztonsag'        => __( 'Biztonság', 'cloud-glossary' ),
			'terheleselosztas' => __( 'Terheléselosztás', 'cloud-glossary' ),
			'compute'          => __( 'Compute', 'cloud-glossary' ),
			'adat'             => __( 'Adat', 'cloud-glossary' ),
			'egyeb'            => __( 'Egyéb', 'cloud-glossary' ),
		);

		self::insert_terms( self::TAX_CATEGORY, $category_terms );
	}

	/**
	 * Insert terms if they do not already exist.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param array  $terms    Array in slug => name form.
	 */
	private static function insert_terms( $taxonomy, $terms ) {
		foreach ( $terms as $slug => $name ) {
			if ( ! term_exists( $slug, $taxonomy ) ) {
				wp_insert_term(
					$name,
					$taxonomy,
					array(
						'slug' => $slug,
					)
				);
			}
		}
	}
}

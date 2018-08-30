<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WDS_Page_Builder_Layouts' ) ) {

	class WDS_Page_Builder_Layouts {
		/**
		 * Constructor
		 * @since 0.1.0
		 */
		public function __construct( $plugin ) {
			$this->plugin = $plugin;
		}

		public function register_layout( $slug, $templates = array(), $args = array() ) {
			// don't register anything if no layout name or templates were passed
			if ( '' == $slug || empty( $templates ) ) {
				return false;
			}

			$defaults = array(
				'name'        => ucwords( str_replace( '-', ' ', $slug ) ),
				'description' => '',
			);
			$args = wp_parse_args( $args, $defaults );
			$this->registered_areas[ $slug ] = array(
				'name'        => esc_attr( $args['name'] ),
				'description' => esc_html( $args['description'] ),
				'templates'   => $templates,
			);
		}

		public function hooks() {
			add_action( 'init', array( $this, 'layouts_cpt') );
			if ( is_admin() ) {
				add_action( 'cmb2_init', array( $this, 'add_options_page_metabox' ) );
				add_action( 'cmb2_init', array( $this, 'register_fields' ) );
			}
		}

		public function layouts_cpt() {
			$labels = array(
				'name'               => _x( 'Saved Layouts', 'post type general name', 'wds-simple-page-builder' ),
				'singular_name'      => _x( 'Saved Layout', 'post type singular name', 'wds-simple-page-builder' ),
				'menu_name'          => _x( 'Saved Layouts', 'admin menu', 'wds-simple-page-builder' ),
				'name_admin_bar'     => _x( 'Saved Layout', 'add new on admin bar', 'wds-simple-page-builder' ),
				'add_new'            => _x( 'Add New Layout', 'page builder layout', 'wds-simple-page-builder' ),
				'add_new_item'       => __( 'Add New Layout', 'wds-simple-page-builder' ),
				'new_item'           => __( 'New Layout', 'wds-simple-page-builder' ),
				'edit_item'          => __( 'Edit Layout', 'wds-simple-page-builder' ),
				'view_item'          => __( 'View Layout', 'wds-simple-page-builder' ),
				'all_items'          => __( 'Saved Layouts', 'wds-simple-page-builder' ),
				'search_items'       => __( 'Search Layouts', 'wds-simple-page-builder' ),
				'not_found'          => __( 'No layouts found.', 'wds-simple-page-builder' ),
				'not_found_in_trash' => __( 'No layouts found in Trash.', 'wds-simple-page-builder' )
			);
			$args = array(
				'labels'        => $labels,
				'public'        => false,
				'show_ui'       => true,
				'has_archive'   => false,
				'hierarchical'  => false,
				'supports'      => array( 'title' ),
				'show_in_menu' => 'edit.php?post_type=wds_pb_layouts',
			);
			register_post_type( 'wds_pb_layouts', $args );
		}

		public function register_fields() {
			$cmb = new_cmb2_box( array(
				'id'           => 'wds_simple_page_builder_layout',
				'title'        => __( 'Page Builder Templates', 'wds-simple-page-builder' ),
				'object_types' => array( 'wds_pb_layouts' ),
				'show_on_cb'   => array( $this->plugin->admin, 'maybe_enqueue_builder_js' ),
			) );

			$group_field = $cmb->add_field( array(
				'id'       => '_wds_builder_layout_template',
				'type'     => 'group',
				'options'  => array(
					'group_title'   => __( 'Template Part {#}', 'wds-simple-page-builder' ),
					'add_button'    => __( 'Add another template part', 'wds-simple-page-builder' ),
					'remove_button' => __( 'Remove template part', 'wds-simple-page-builder' ),
					'sortable'      => true,
				)
			) );

			foreach ( $this->plugin->admin->get_group_fields() as $field ) {
				$cmb->add_group_field( $group_field, $field );
			}

			$advanced = new_cmb2_box( array(
				'id'           => 'wds_simple_page_builder_layout_advanced',
				'title'        => __( 'Layout Defaults', 'wds-simple-page-builder' ),
				'object_types' => array( 'wds_pb_layouts' ),
				'show_on_cb'   => array( $this->plugin->admin, 'maybe_enqueue_builder_js' ),
			) );

			$advanced->add_field( array(
				'id'      => '_wds_builder_default_post_type',
				'name'    => __( 'Post Type', 'wds-simple-page-builder' ),
				'desc'    => __( 'Set this layout as the default layout for the these post types.', 'wds-simple-page-builder' ),
				'type'    => 'multicheck',
				'options' => $this->plugin->options->get_post_types(),
			) );

			$advanced->add_field( array(
				'id'      => '_wds_builder_default_area',
				'name'    => __( 'Area', 'wds-simple-page-builder' ),
				'desc'    => __( 'If the layout is set as a default layout for a post type, select what area the layout should be the default in.', 'wds-simple-page-builder' ),
				'type'    => 'radio',
				'default' => 'page_builder_default',
				'options' => $this->get_area_list(),
			) );

			$advanced->add_field( array(
				'id'      => '_wds_builder_default_hide_metabox',
				'name'    => __( 'Hide Metabox', 'wds-simple-page-builder' ),
				'desc'    => __( 'If checked, the metabox for this area will be hidden on these post types.', 'wds-simple-page-builder' ),
				'type'    => 'checkbox',
			) );
		}

		function add_options_page_metabox() {
			$cmb = new_cmb2_box( array(
				'id'         => $this->plugin->options->metabox_id . '_default_area_layouts',
				'hookup'     => false,
				'cmb_styles' => false,
				'show_on'    => array(
					'key'   => 'options-page',
					'value' => array( $this->plugin->options->key ),
				),
			) );
			$registered_areas = $this->plugin->areas->get_registered_areas();
			foreach ( $registered_areas as $key => $area ) {
				$cmb->add_field( array(
					'name'             => esc_html( $area['name'] ),
					'id'               => $key,
					'type'             => 'select',
					'show_option_none' => true,
					'options'          => $this->get_saved_layouts(),
				) );
			}
		}

		/**
		 * Returns an array of saved layout ids/titles
		 * @param  array $meta_query Optional. You can pass WP_Query meta query args to
		 *                            filter your results by post type or area (or both).
		 * @return array              An array that's ready for a CMB2 field.
		 */
		public function get_saved_layouts( $meta_query = array() ) {
			$args = array(
				'post_type'      => 'wds_pb_layouts',
				'posts_per_page' => 9999,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				'meta_query'     => array( $meta_query ),
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			);
			$layouts = new WP_Query( $args );
			$return = array();
			while ( $layouts->have_posts() ) : $layouts->the_post();
				$return[ get_the_ID() ] = get_the_title();
			endwhile;
			wp_reset_postdata();

			return $return;
		}

		/**
		 * Returns an array of all the areas registered.
		 * @return array A list of registered areas and area names.
		 */
		public function get_area_list() {
			$areas = $this->plugin->areas->get_registered_areas();

			$output['page_builder_default'] = __( 'Page Builder Default', 'wds-simple-page-builder' );
			foreach ( $areas as $key => $values ) {
				$output[ $key ] = $values['name'];
			}

			return $output;
		}

		/**
		 * Function to return the layout object by a passed slug.
		 * @param  string $layout_name The slug of the layout.
		 * @return object              The WP_Post object for the layout post.
		 */
		public function get_saved_layout( $layout_name = '' ) {
			if ( ! $layout_name ) {
				return false;
			}

			$layout = get_page_by_path( $layout_name, OBJECT, 'wds_pb_layouts' );

			return $layout;
		}


		/**
		 * Return the default layouts for the given post type.
		 * @todo   This method is a stub. It's a WIP and does not do anything yet.
		 * @param  string $post_type The post type to get layouts from.
		 * @return mixed             An array of layouts (maybe?) or false if there are no default layouts for the post type.
		 */
		public function get_default_layouts_for_post_type( $post_type = '' ) {
			if ( ! $post_type ) {
				return false;
			}


		}
	}

}
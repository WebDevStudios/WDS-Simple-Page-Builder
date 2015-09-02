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
		}

		public function layouts_cpt() {
			$labels = array(
				'name'               => _x( 'Saved Layouts', 'post type general name', 'wds-simple-page-builder' ),
				'singular_name'      => _x( 'Saved Layout', 'post type singular name', 'wds-simple-page-builder' ),
				'menu_name'          => _x( 'Saved Layouts', 'admin menu', 'wds-simple-page-builder' ),
				'name_admin_bar'     => _x( 'Saved Layout', 'add new on admin bar', 'wds-simple-page-builder' ),
				'add_new'            => _x( 'Add New', 'page builder layout', 'wds-simple-page-builder' ),
				'add_new_item'       => __( 'Add New Layout', 'wds-simple-page-builder' ),
				'new_item'           => __( 'New Layout', 'wds-simple-page-builder' ),
				'edit_item'          => __( 'Edit Layout', 'wds-simple-page-builder' ),
				'view_item'          => __( 'View Layout', 'wds-simple-page-builder' ),
				'all_items'          => __( 'All Layouts', 'wds-simple-page-builder' ),
				'search_items'       => __( 'Search Layouts', 'wds-simple-page-builder' ),
				'not_found'          => __( 'No layouts found.', 'wds-simple-page-builder' ),
				'not_found_in_trash' => __( 'No layouts found in Trash.', 'wds-simple-page-builder' )
			);
			$args = array(
				'labels'       => $labels,
				'public'       => false,
				'show_ui'      => true,
				'has_archive'  => false,
				'hierarchical' => false,
				'supports'     => array( 'title' ),
			);
			register_post_type( 'wds_pb_saved_layout', $args );
		}
	}

}
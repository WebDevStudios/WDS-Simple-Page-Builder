<?php
/**
 * Actually add the functionality. Create a CMB repeatable group field to
 * add/remove/rearrange template parts from the theme and append those to the page
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WDS_Page_Builder' ) ) {

	class WDS_Page_Builder {

		/**
		 * Construct function to get things started.
		 */
		public function __construct() {
			// Setup some base variables for the plugin
			$this->basename       = wds_page_builder()->basename;
			$this->directory_path = wds_page_builder()->directory_path;
			$this->directory_url  = wds_page_builder()->directory_url;

			add_action( 'cmb2_init', array( $this, 'do_meta_boxes' ) );
			add_filter( 'loop_end', array( $this, 'add_template_parts' ) );
		}

		/**
		 * Run our hooks
		 */
		public function do_hooks() {

		}

		/**
		 * Build our meta boxes
		 */
		public function do_meta_boxes( $meta_boxes ) {

			$prefix = '_wds_builder_';

			$cmb = new_cmb2_box( array(
				'id'           => 'wds_simple_page_builder',
				'title'        => __( 'Page Builder', 'wds-simple-page-builder' ),
				'object_types' => array( 'page' ),
				'context'      => 'normal',
				'priority'     => 'high',
				'show_names'   => true,
			) );

			$group_field_id = $cmb->add_field( array(
				'id'           => $prefix . 'template',
				'type'         => 'group',
				'options'      => array(
					'group_title'   => __( 'Template Part {#}', 'wds-simple-page-builder' ),
					'add_button'    => __( 'Add another template part', 'wds-simple-page-builder' ),
					'remove_button' => __( 'Remove template part', 'wds-simple-page-builder' ),
					'sortable'      => true
				)
			) );

			$cmb->add_group_field( $group_field_id, array(
				'name'         => __( 'Template', 'wds-simple-page-builder' ),
				'id'           => 'template_group',
				'type'         => 'select',
				'options'      => $this->get_template_parts()
			) );
		}

		/**
		 * Get a list of the template parts in the current theme, return them
		 * in an array.
		 *
		 * @return array An array of template parts
		 */
		public function get_template_parts() {

			$parts        = array();
			$parts_dir    = ( wds_page_builder_get_option( 'parts_dir' ) ) ?get_stylesheet_directory() . wds_page_builder_get_option( 'parts_dir' ) : get_stylesheet_directory() . '/parts';
			$parts_prefix = ( wds_page_builder_get_option( 'parts_prefix' ) ) ? wds_page_builder_get_option( 'parts-prefix' ) . '-' : 'part';

			foreach( glob( $parts_dir . '/' . $parts_prefix . '-*.php' ) as $part ) {
				$part_slug = str_replace( array( $parts_dir . '/' . $parts_prefix . '-', '.php' ), '', $part );
				$parts[$part_slug] = ucwords( str_replace( '-', ' ', $part_slug ) );
			}

			return $parts;
		}

		public function add_template_parts( $template ) {

			if ( ! is_page() ) {
				return $template;
			}

			$parts = get_post_meta( get_the_ID(), '_wds_builder_template', true );

			if ( ! $parts ) {
				return $template;
			}

			foreach( $parts as $part ) {
				load_template( get_template_directory() . '/' . wds_template_parts_dir() . '/' . wds_template_part_prefix() . '-' . $part['template_group'] . '.php' );
			}


		}

	}

	$_GLOBALS['WDS_Page_Builder'] = new WDS_Page_Builder;
	$_GLOBALS['WDS_Page_Builder']->do_hooks();
}
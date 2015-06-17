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
			add_action( 'wds_page_builder_load_parts', array( $this, 'add_template_parts' ) );
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
				'options'      => wds_page_builder_get_parts()
			) );
		}

		/**
		 * Handle identifying the template parts to use and trigger loading those parts
		 */
		public function add_template_parts() {

			if ( ! is_page() || wds_page_builder_get_option( 'post_types' ) && ! in_array( get_post_type(), wds_page_builder_get_option( 'post_types' ) ) ) {
				return;
			}

			$parts        = get_post_meta( get_queried_object()->ID, '_wds_builder_template', true );
			$global_parts = wds_page_builder_get_option( 'parts_global_templates' );

			// if there are no parts saved for this post and there are no global parts
			if ( ! $parts && ! $global_parts ) {
				return;
			}

			// check for locally set template parts first, make sure that the part isn't set to none, default to the globals if they aren't set
			if ( ! $parts || in_array( 'none', $parts[0] ) ) {

				$parts = $global_parts;

			}

			// loop through each part and load the template parts
			foreach( $parts as $part ) {

				$this->load_template_part( $part );

			}

		}

		/**
		 * Helper function to keep things DRY, takes care of loading the specific template
		 * part requested
		 *
		 * @param array $part A template part array from either the global option or the
		 *                    post meta for the current page.
		 */
		public function load_template_part( $part = array() ) {

			// bail if nothing was passed
			if ( empty( $part ) ) {
				return;
			}

			// bail if, for some reason, there is no template_group array key
			if ( ! isset( $part['template_group'] ) ) {
				return;
			}

			// bail if no parts were set
			if ( 'none' == $part['template_group'] ) {
				return;
			}

			load_template( get_template_directory() . '/' . wds_template_parts_dir() . '/' . wds_template_part_prefix() . '-' . $part['template_group'] . '.php' );

		}

	}

	$_GLOBALS['WDS_Page_Builder'] = new WDS_Page_Builder;
	$_GLOBALS['WDS_Page_Builder']->do_hooks();
}
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

		public $part_slug;

		/**
		 * Construct function to get things started.
		 */
		public function __construct() {
			// Setup some base variables for the plugin
			$this->basename       = wds_page_builder()->basename;
			$this->directory_path = wds_page_builder()->directory_path;
			$this->directory_url  = wds_page_builder()->directory_url;
			$this->part_slug      = '';
			$this->templates_loaded = false;

			add_action( 'cmb2_init', array( $this, 'do_meta_boxes' ) );
			add_action( 'cmb2_after_init', array( $this, 'wrapper_init' ) );
			add_action( 'wds_page_builder_load_parts', array( $this, 'add_template_parts' ), 10, 3 );
			add_action( 'wds_page_builder_after_load_parts', array( $this, 'templates_loaded' ) );

		}

		/**
		 * If we've set the option to use a wrapper around the page builder parts, add the actions
		 * to display those parts
		 * @since  1.5
		 * @return void
		 */
		public function wrapper_init() {
			if ( wds_page_builder_get_option( 'use_wrap' ) ) {
				add_action( 'wds_page_builder_before_load_template', array( $this, 'before_parts' ), 10, 2 );
				add_action( 'wds_page_builder_after_load_template', array( $this, 'after_parts' ), 10, 2 );
			}
		}

		public function templates_loaded() {
			if ( $this->templates_loaded === false ) {
				$this->templates_loaded = true;
			}
		}

		/**
		 * Build our meta boxes
		 */
		public function do_meta_boxes( $meta_boxes ) {

			$prefix = '_wds_builder_';

			$object_types = ( wds_page_builder_get_option( 'post_types' ) ) ? wds_page_builder_get_option( 'post_types' ) : array( 'page' );

			$cmb = new_cmb2_box( array(
				'id'           => 'wds_simple_page_builder',
				'title'        => esc_html__( 'Page Builder', 'wds-simple-page-builder' ),
				'object_types' => $object_types,
				'context'      => 'normal',
				'priority'     => 'high',
				'show_names'   => true,
			) );

			$group_field_id = $cmb->add_field( array(
				'id'           => $prefix . 'template',
				'type'         => 'group',
				'options'      => array(
					'group_title'   => esc_html__( 'Template Part {#}', 'wds-simple-page-builder' ),
					'add_button'    => esc_html__( 'Add another template part', 'wds-simple-page-builder' ),
					'remove_button' => esc_html__( 'Remove template part', 'wds-simple-page-builder' ),
					'sortable'      => true
				)
			) );

			$cmb->add_group_field( $group_field_id, array(
				'name'         => esc_html__( 'Template', 'wds-simple-page-builder' ),
				'id'           => 'template_group',
				'type'         => 'select',
				'options'      => wds_page_builder_get_parts()
			) );
		}

		/**
		 * Handle identifying the template parts to use and trigger loading those parts
		 *
		 * @param string $layout Optional parameter to specify a specific layout to use
		 */
		public function add_template_parts( $layout = '', $container = '', $class = '' ) {

			if ( '' == $layout ) {
				if ( ! wds_page_builder_get_option( 'parts_saved_layouts' ) && ( ! is_page() || wds_page_builder_get_option( 'post_types' ) && ! in_array( get_post_type(), wds_page_builder_get_option( 'post_types' ) ) ) ) {
					return;
				}
			}

			$post_id            = ( is_singular() ) ? get_queried_object()->ID : 0;
			$parts              = get_post_meta( $post_id, '_wds_builder_template', true );
			$global_parts       = wds_page_builder_get_option( 'parts_global_templates' );
			$saved_layouts      = wds_page_builder_get_option( 'parts_saved_layouts' );
			$registered_layouts = get_option( 'wds_page_builder_layouts' );

			// if there are no parts saved for this post, no global parts, no saved layouts, and no layout passed to the action
			if ( ! $parts && ! $global_parts && ! $saved_layouts && $layout == '' ) {
				return;
			}

			// if a layout was passed or a layout is being used by default for this post type, we're going to check that first
			if ( ! $parts && $saved_layouts || ! $parts && $registered_layouts ) {

				// check if the layout requested is one that was registered
				if ( $registered_layouts ) {

					$saved_layouts = $registered_layouts;

				}

				// loop through the saved layouts, we'll check for the one we're looking for
				foreach( $saved_layouts as $saved_layout ) {

					// is the layout the one that was named or one that was set for this post type?
					if ( isset( $saved_layout['layouts_name'] ) && $layout == $saved_layout['layouts_name'] ) {

						$parts = array();
						foreach( $saved_layout['template_group'] as $template_group ) {
							$parts[] = array( 'template_group' => $template_group );
						}

					} elseif ( isset( $saved_layout['default_layout'] ) && is_array( $saved_layout['default_layout'] ) && in_array( get_post_type( $post_id ), $saved_layout['default_layout'] ) ) {

						// loop through the template parts and prepare the $parts variable for the load_template_part method
						foreach( $saved_layout['template_group'] as $template_group ) {
							$parts[] = array( 'template_group' => $template_group );
						} // end template part loop

					} // end layout check

				} // end saved layouts loop

			} // done checking saved layouts

			// check for locally set template parts, make sure that the part isn't set to none, default to the globals if they aren't set
			elseif ( ! $parts || in_array( 'none', $parts[0] ) ) {

				$parts = $global_parts;

			}

			// loop through each part and load the template parts
			if ( is_array( $parts ) && ! $this->templates_loaded ) {
				do_action( 'wds_page_builder_before_load_parts' );
				foreach( $parts as $part ) {
					$this->load_template_part( $part, $container, $class );
				}
				do_action( 'wds_page_builder_after_load_parts' );
			}

		}

		/**
		 * Helper function to keep things DRY, takes care of loading the specific template
		 * part requested
		 *
		 * @param array $part A template part array from either the global option or the
		 *                    post meta for the current page.
		 */
		public function load_template_part( $part = array(), $container = '', $class = '' ) {

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

			$this->set_part( $part['template_group'] );
			$classes = ( $class ) ? $class . ' ' . $this->part_slug : $this->part_slug;

			// bail if the file doesn't exist
			if ( ! file_exists( trailingslashit( get_template_directory() ) . trailingslashit( wds_page_builder_template_parts_dir() ) . wds_page_builder_template_part_prefix() . '-' . $this->part_slug . '.php' ) ) {
				return;
			}

			do_action( 'wds_page_builder_before_load_template', $container, $classes );
			load_template( get_template_directory() . '/' . wds_page_builder_template_parts_dir() . '/' . wds_page_builder_template_part_prefix() . '-' . $this->part_slug . '.php' );
			do_action( 'wds_page_builder_after_load_template', $container, $this->part_slug );

		}

		/**
		 * Get the current part_slug class variable
		 * @since  1.5
		 * @return string The current value of part_slug
		 */
		public function get_part() {
			return $this->part_slug;
		}

		/**
		 * Set the current part_slug class variable
		 * @since  1.5
		 * @param string $part Sets a new value for the part_slug class variable
		 */
		public function set_part( $part ) {
			$this->part_slug = $part;
		}

		/**
		 * Returns an array of all the page builder template part slugs on the current page
		 * @since  1.5
		 * @return array The page builder part slugs
		 */
		public function page_builder_parts() {
			$some_files = array_filter(get_included_files(), array( $this, 'match_parts' ) );
			$the_files  = array();
			foreach ( $some_files as $file ) {
				$the_files[] = stripslashes( str_replace( array(
					get_template_directory(),
					wds_page_builder_template_parts_dir(),
					wds_page_builder_template_part_prefix() . '-',
					'.php',
					'//'
				), '', $file ) );
			}
			return $the_files;
		}

		/**
		 * array_filter callback to match template parts
		 * @since  1.5
		 * @param  string $var The thing to check
		 * @return bool        Whether the string was found
		 */
		private function match_parts($var) {
			return strpos($var, 'part-');
		}

		public function before_parts( $container = '', $class = '' ) {
			$container = ( ! $container ) ? wds_page_builder_container() : sanitize_title( $container );
			$classes = get_the_page_builder_classes( $class );
			$before = "<$container class=\"$classes\">";

			/**
			 * Filter the wrapper markup.
			 *
			 * Note, there's no filter for what the closing markup would look like, so if the
			 * container element is being changed, make sure to only change the container by
			 * filtering wds_page_builder_container.
			 *
			 * @since 1.5
			 * @param string $before The full opening container markup
			 */
			echo apply_filters( 'wds_page_builder_wrapper', $before );
		}

		public function after_parts( $container = '', $class = '' ) {
			$container = ( ! $container ) ? wds_page_builder_container() : sanitize_title( $container );
			echo "</$container>";
			echo ( $class ) ? '<!-- .' . sanitize_title( $class ) . ' -->' : '';
		}

	}

	$_GLOBALS['WDS_Page_Builder'] = new WDS_Page_Builder;
}

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
		protected $parts_index = 0;
		protected $builder_js_required = false;
		protected $cmb = null;
		protected $data_fields = null;
		protected $parts = array();
		protected $prefix = '_wds_builder_';

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
			$this->area           = '';

			if ( is_admin() ) {
				add_action( 'cmb2_init', array( $this, 'do_meta_boxes' ) );
			}
			add_action( 'cmb2_after_init', array( $this, 'wrapper_init' ) );
			add_action( 'wds_page_builder_load_parts', array( $this, 'add_template_parts' ), 10, 3 );
			add_action( 'wds_page_builder_after_load_parts', array( $this, 'templates_loaded' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_css' ) );

		}

		/**
		 * Handles conditionally loading the SPB admin css
		 * @since  1.6
		 * @param  string $hook Current page hook
		 * @return null
		 */
		public function load_admin_css( $hook ) {
			if ( in_array( $hook, array( 'post-new.php', 'post.php' ) ) && in_array( get_post_type(), wds_page_builder_get_option( 'post_types' ) ) ) {
				wp_enqueue_style( 'wds-simple-page-builder-admin', $this->directory_url . '/assets/css/admin.css', '', WDS_Simple_Page_Builder::VERSION );
			}
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

		/**
		 * Toggles the templates-loaded status, triggered by the wds_page_builder_after_load_parts hook
		 * @since  1.5
		 * @return null
		 */
		public function templates_loaded() {
			if ( $this->templates_loaded === false ) {
				$this->templates_loaded = true;
			}
		}

		/**
		 * Build our meta boxes
		 */
		public function do_meta_boxes() {

			$option = wds_page_builder_get_option( 'post_types' );
			$object_types = $option ? $option : array( 'page' );

			$this->cmb = new_cmb2_box( array(
				'id'           => 'wds_simple_page_builder',
				'title'        => __( 'Page Builder', 'wds-simple-page-builder' ),
				'object_types' => $object_types,
				'show_on_cb'   => array( $this, 'maybe_enqueue_builder_js' ),
			) );

			$this->cmb->add_field( array(
				'id'           => $this->prefix . 'template_group_title',
				'type'         => 'title',
				'name'         => __( 'Content Area Templates', 'wds-simple-page-builder' )
			) );

			$group_field_id = $this->cmb->add_field( array(
				'id'           => $this->prefix . 'template',
				'type'         => 'group',
				'options'      => array(
					'group_title'   => __( 'Template Part {#}', 'wds-simple-page-builder' ),
					'add_button'    => __( 'Add another template part', 'wds-simple-page-builder' ),
					'remove_button' => __( 'Remove template part', 'wds-simple-page-builder' ),
					'sortable'      => true
				)
			) );

			foreach ( $this->get_group_fields() as $field ) {
				$this->cmb->add_group_field( $group_field_id, $field );
			}

			$this->register_all_area_fields();
		}

		/**
		 * Gets the fields for each group set.
		 *
		 * Has an internal filter to allow for the addition of fields based on the part slug.
		 * ie: To add fields for the template part-sample.php you would add_filter( 'wds_page_builder_fields_sample', 'myfunc' )
		 * The added fields will then only show up if that template part is selected within the group.
		 *
		 * @since 1.6
		 *
		 * @return array    A list CMB2 field types
		 */
		public function get_group_fields( $id = 'template_group' ) {

			$fields = array(
				array(
					'name'       => __( 'Template', 'wds-simple-page-builder' ),
					'id'         => $id,
					'type'       => 'select',
					'options'    => $this->get_parts(),
					'attributes' => array( 'class' => 'cmb2_select wds-simple-page-builder-template-select' ),
				),
			);

			return array_merge( $fields, $this->get_data_fields() );
		}

		/**
		 * Retrieve all registered (via filters) additional data fields
		 * @since  1.6
		 * @return array  Array of additional fields
		 */
		public function get_data_fields() {
			if ( ! is_null( $this->data_fields ) ) {
				return $this->data_fields;
			}

			$this->data_fields = array();

			foreach ( $this->get_parts() as $part_slug => $part_value ) {
				$new_fields = apply_filters( "wds_page_builder_fields_$part_slug", array() );

				if ( ! empty( $new_fields ) && is_array( $new_fields ) ) {

					$this->builder_js_required = true;

					foreach ( $new_fields as $new_field ) {
						$this->data_fields[] = $this->add_wrap_to_field_args( $part_slug, $new_field );
					}
				}
			}

			return $this->data_fields;
		}

		/**
		 * Modify fields to have a before_row/after_row wrap
		 * @since 1.6
		 * @param  string  $part_slug  The template part slug
		 * @param  array   $field_args The field arguments array
		 * @return array               The modified field arguments array
		 */
		public function add_wrap_to_field_args( $part_slug, $field_args ) {

			$field_args['_builder_group'] = $part_slug;

			// Add before wrap
			$field_args['before_row'] = isset( $field_args['before_row'] ) ? $field_args['before_row'] : '<div class="hidden-parts-fields hidden-parts-'. $part_slug .' hidden" >';

			// Add after wrap
			$field_args['after_row'] = isset( $field_args['after_row'] ) ? $field_args['after_row'] : '</div><!-- .hidden-parts-'. $part_slug .' -->';

			return $field_args;
		}

		/**
		 * Handles registering get_page_builder_areas fields
		 * @since  1.6
		 * @return null
		 */
		public function register_all_area_fields() {

			$areas = get_page_builder_areas();

			if ( ! $areas ) {
				return;
			}

			foreach( $areas as $area => $layout ) {
				// only show these meta fields if there's no defined layout for the area
				if ( empty( $layout['template_group'] ) ) {
					$this->register_area_fields( $area );
				}

			}

		}

		/**
		 * Handles registering fields for a single area
		 * @since  1.6
		 * @param  string $area   Area slug
		 * @return null
		 */
		public function register_area_fields( $area ) {

			$area_group_field_id = $area . '_group_field_id';

			$this->cmb->add_field( array(
				'id'       => $this->prefix . $area . '_' . 'title',
				'type'     => 'title',
				'name'     => sprintf( __( '%s Area Templates', 'wds-simple-page-builder' ), ucfirst( $area ) ),
			) );

			$area_group_field_id = $this->cmb->add_field( array(
				'id'       => $this->prefix . $area . '_' . 'template',
				'type'     => 'group',
				'options'  => array(
					'group_title'   => sprintf( __( '%s Template Part {#}', 'wds-simple-page-builder' ), ucfirst( $area ) ),
					'add_button'    => __( 'Add another template part', 'wds-simple-page-builder' ),
					'remove_button' => __( 'Remove template part', 'wds-simple-page-builder' ),
					'sortable'      => true,
				)
			) );

			foreach ( $this->get_group_fields() as $field ) {
				$this->cmb->add_group_field( $area_group_field_id, $field );
			}
		}

		/**
		 * Enqueue builder JS if it's needed (based on additional fields being present)
		 * @since  1.6
		 * @return bool  Whether box should show (it should)
		 */
		public function maybe_enqueue_builder_js() {
			if ( $this->builder_js_required ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_builder_js' ) );
			}

			// We're just using this hook for adding the admin_enqueue_scripts hook.. return true to display the metabox
			return true;
		}

		/**
		 * Enqueue the builder JS
		 * @since  1.6
		 * @return null
		 */
		public function enqueue_builder_js() {
			wp_enqueue_script( 'wds-simple-page-builder', wds_page_builder()->directory_url . '/assets/js/builder.js', array( 'cmb2-scripts' ), WDS_Simple_Page_Builder::VERSION, true );
		}

		/**
		 * Handle identifying the template parts to use and trigger loading those parts
		 *
		 * @param string $layout Optional parameter to specify a specific layout to use
		 */
		public function add_template_parts( $layout = '', $container = '', $class = '' ) {

			if ( '' == $layout ) {
				$this->templates_loaded = false;
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

					if ( in_array( $layout, $registered_layouts ) ) {
						$saved_layouts = $registered_layouts;
					}

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
				foreach( $parts as $this->parts_index => $part ) {

					// check if the current part was loaded already
					if ( $this->get_part() && $this->get_part() !== $part['template_group'] ) {

						$this->load_template_part( $part, $container, $class );

					}

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

			// bail if the filter returns false
			if ( ! (bool) apply_filters( 'wds_page_builder_load_template_part', true, $part, $container, $class ) ) {
				return;
			}

			$this->set_part( $part['template_group'] );
			$classes = ( $class ) ? $class . ' ' . $this->part_slug : $this->part_slug;

			$filename = trailingslashit( wds_page_builder_template_parts_dir() ) . wds_page_builder_template_part_prefix() . '-' . $this->part_slug . '.php';
			$filepath = trailingslashit( get_template_directory() ) . $filename;

			// bail if the file doesn't exist
			if ( ! file_exists( $filepath ) ) {
				return;
			}

			do_action( 'wds_page_builder_before_load_template', $container, $classes );
			load_template( $filepath, false );
			do_action( 'wds_page_builder_after_load_template', $container, $this->part_slug );

		}

		/**
		 * Get the current parts index class variable
		 * @since  1.5
		 * @return string The current value of index
		 */
		public function get_parts_index() {
			return $this->parts_index;
		}

		/**
		 * Set the current parts index class variable
		 *
		 * @param $index The index value to set.
		 */
		public function set_parts_index( $index ) {
			$this->parts_index = $index;
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

			/**
			 * Filter to change the part slug of a part. Could be used to allow multiple
			 * instances of the same part to be loaded on a page.
			 */
			$this->part_slug = apply_filters( 'wds_page_builder_set_part', $part );

		}

		/**
		 * Get the current area variable
		 *
		 * @return string The area slug.
		 */
		public function get_area() {
			return $this->area;
		}

		/**
		 * Set the current area variable
		 *
		 * @param $area The slug of the area you are setting.
		 */
		public function set_area ( $area ) {
			$this->area = $area;
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

		/**
		 * Adds opening wrap markup
		 * @since  1.5
		 * @param  string  $container
		 * @param  string  $class
		 * @return null
		 */
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

		/**
		 * Adds closing wrap markup
		 * @since  1.5
		 * @param  string  $container
		 * @param  string  $class
		 * @return null
		 */
		public function after_parts( $container = '', $class = '' ) {
			$container = ( ! $container ) ? wds_page_builder_container() : sanitize_title( $container );
			echo "</$container>";
			echo ( $class ) ? '<!-- .' . sanitize_title( $class ) . ' -->' : '';
		}

		/**
		 * Wrapper for wds_page_builder_get_parts which stores it's result
		 * @since  1.6
		 * @return array  Array of parts options
		 */
		public function get_parts() {
			if ( ! empty( $this->parts ) ) {
				return $this->parts;
			}

			$this->parts = wds_page_builder_get_parts();
			return $this->parts;
		}

	}

	$GLOBALS['WDS_Page_Builder'] = new WDS_Page_Builder;
}

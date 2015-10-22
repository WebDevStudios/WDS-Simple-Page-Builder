<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WDS_Page_Builder_Areas' ) ) {

	class WDS_Page_Builder_Areas {
		/**
		 * Constructor
		 * @since 0.1.0
		 */
		public function __construct( $plugin ) {
			$this->plugin = $plugin;

			$this->registered_areas = array();
			$this->current_area = '';
			$this->parts = array();
			$this->area = '';

			$this->hooks();
		}

		public function hooks() {
			add_action( 'init', array( $this, 'register_default_area' ) );
		}

		public function register_default_area() {
			$this->register_area(
				'page_builder_default',
				array(
					'name'        => __( 'Default Page Builder Area', 'wds-simple-page-builder' ),
					'description' => __( 'This is the default area. Place the template tag wds_page_builder_area() in your theme file to display. You can also create custom areas.', 'wds-simple-page-builder' ),
				)
			);
		}

		public function register_area( $slug, $args = array(), $templates = array() ) {
			$defaults = array(
				'name'         => ucwords( str_replace( array( '-', '_' ), ' ', $slug ) ),
				'description'  => '',
				'edit_on_page' => true,
			);
			$args = wp_parse_args( $args, $defaults );
			$this->registered_areas[ $slug ] = array(
				'name'         => esc_attr( $args['name'] ),
				'description'  => esc_html( $args['description'] ),
				'templates'    => $templates,
				'edit_on_page' => $args['edit_on_page'],
			);
		}

		public function get_registered_areas() {
			return $this->registered_areas;
		}

		public function get_registered_area( $slug ) {
			$area = isset( $this->registered_areas[ $slug ] ) ? $this->registered_areas[ $slug ] : false;
			return $area;
		}

		/**
		 * Get the current area variable
		 *
		 * @return string The area slug.
		 */
		public function get_current_area() {
			return $this->current_area;
		}

		/**
		 * Set the current area variable
		 *
		 * @param $area The slug of the area you are setting.
		 */
		public function set_current_area ( $area ) {
			$this->current_area = $area;
		}


		/**
		 * Return a saved layout object by its slug.
		 * Note: This only works with layouts created after 1.6.
		 * @since  1.6.0
		 * @param  string $layout_name The post slug of the pagebuilder layout.
		 * @return object              The WP_Post object for the pagebuilder layout.
		 */
		public function get_saved_layout_by_slug( $layout_name = '' ) {
			if ( '' == $layout_name ) {
				return false;
			}

			$layout = get_posts( array(
				'post_type'      => 'wds_pb_layouts',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'name'           => sanitize_title( $layout_name ),
			) );

			return $layout[0];
		}

		/**
		 * Return all the saved layouts for a given area and post type.
		 * @since  1.6.0
		 * @param  string  $area    The pagebuilder area to query by.
		 * @param  integer $post_id The post ID of the post displaying the area for determining the post type.
		 * @return object           The WP_Post object for the most recent pagebuilder layout.
		 */
		public function get_saved_layout( $area, $post_id = 0 ) {
			if ( 0 == $post_id ) {
				$post_id = get_queried_object_id();
			}

			$post_type = get_post_type( $post_id );

			$layout = get_posts( array(
				'post_type'      => 'wds_pb_layouts',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_query'     => array(
					'relation'   => 'AND',
					array(
						'key'     => '_wds_builder_default_area',
						'value'   => $area,
						'compare' => '=',
					),
					array(
						'key'     => '_wds_builder_default_post_type',
						'value'   => $post_type,
						'compare' => '=',
					),
				),
			) );

			return ( ! empty( $layout ) ) ? $layout[0] : false;
		}

		public function get_area( $area, $post_id = 0 ) {
			$area_data = $this->get_registered_area( $area );

			// if there were no page builder areas, bail
			if ( ! $area_data ) {
				return;
			}

			// if no post ID was passed, try to get one
			if ( 0 == $post_id ) {
				$post_id = get_queried_object_id();
			}

			// if it's not singular -- like an archive or a 404 or something -- you can only add template
			// parts by registering the area
			if ( ! is_singular() && ( ! is_home() && ! $post_id ) ) {
				return isset( $area_data['template_group'] ) ? $area_data['template_group'] : false;
			}

			$area_key = $area ? $area . '_' : '';
			if ( 'page_builder_default' == $area ) {
				$area_key = '';
			}

			if ( $templates = get_post_meta( $post_id, '_wds_builder_' . esc_attr( $area_key ) . 'template', true ) ) {
				foreach( $templates as $template ) {
					$out[] = isset( $template['template_group'] ) ? $template['template_group'] : '';
				}

				return $out;
			}

			return;
		}

		public function do_area( $area = '', $post_id = 0 ) {
			// bail if no area was specified
			if ( '' == $area ) {
				return false;
			}

			// if no post ID was passed, try to get one
			if ( 0 == $post_id ) {
				$post_id = get_queried_object_id();
			}

			/**
			 * Filer allowing you to short-circuit and not display the area.
			 */
			$do = apply_filters( 'wds_page_builder_do_area', true, $area, $post_id );
			if ( ! $do ) {
				return false;
			}

			$parts = $this->get_area( $area, $post_id );

			// If the area's parts are not set, use the default layout.
			if ( ! $parts || 'none' == $parts[0] ) {
				$option = get_option( $this->plugin->options->key . '_default_area_layouts' );
				if ( ! isset( $option[ $area ] ) ) {
					return false;
				}
				$metas = get_post_meta( $option[ $area ], '_wds_builder_layout_template', true );
				if ( ! $metas ) {
					return false;
				}
				$parts = array();
				foreach ( $metas as $meta ) {
					$parts[] = $meta['template_group'];
				}
			}

			$this->parts = $parts;
			$this->area = $area;

			do_action( 'wds_page_builder_load_parts', $parts, $area, $post_id );

			do_action( 'wds_page_builder_before_load_parts', $parts, $area, $post_id );
			$this->plugin->functions->load_parts( $parts, '', '', $area );
			do_action( 'wds_page_builder_after_load_parts', $parts, $area, $post_id );

			return true;
		}
	}

}

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
					'description' => __( 'This is the default area. Place the template tag page_builder_area() in your theme file to display. You can also create custom areas.', 'wds-simple-page-builder' ),
				)
			);
		}

		public function register_area( $slug, $args = array(), $templates = array() ) {
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

		public function get_registered_areas() {
			return $this->registered_areas;
		}

		public function get_registered_area( $slug ) {
			$area = isset( $this->registered_areas[$slug] ) ? $this->registered_areas[$slug] : false;
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
				return $area_data['template_group'];
			}

			if ( $templates = get_post_meta( $post_id, '_wds_builder_' . esc_attr( $area ) . '_template', true ) ) {
				foreach( $templates as $template ) {
					$out[] = $template['template_group'];
				}

				return $out;
			}

			return;
		}

		public function do_area( $area = '', $post_id = 0 ) {
			// bail if no area was specified
			if ( '' == $area ) {
				return;
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
				return;
			}

			$parts = $this->get_area( $area, $post_id );

			if ( $parts ) {
				do_action( 'wds_page_builder_before_load_parts', $parts, $area, $post_id );
				$this->plugin->functions->load_parts( $parts, '', '', $area );
				do_action( 'wds_page_builder_after_load_parts', $parts, $area, $post_id );
			} else {
				$this->plugin->functions->load_parts( $area );
			}
		}

	}

}
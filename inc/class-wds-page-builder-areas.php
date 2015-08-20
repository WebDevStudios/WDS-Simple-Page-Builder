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
		}

		public function register_area( $slug, $name = '', $templates = array() ) {
			$this->registered_areas[ $slug ] = array(
				'name'      => esc_attr( $name ),
				'templates' => $templates
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

	}

}
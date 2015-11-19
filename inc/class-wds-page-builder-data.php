<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WDS_Page_Builder_Data' ) ) {

	class WDS_Page_Builder_Data {
		/**
		 * Constructor
		 * @since 0.1.0
		 */
		public function __construct( $plugin ) {
			$this->plugin = $plugin;
		}


		public function get( $part, $meta_key, $post_id = 0, $area = '' ) {
			// Can specify the index if parts are used multiple times on a page
			if ( is_array( $part ) ) {

				// Oops? you're doing it wrong!
				if ( ! isset( $part['index'], $part['slug'] ) ) {
					return new WP_Error( 'index_slug_defined_incorrectly', 'The index/slug array was defined incorrectly. Try array( \'index\' => 0, \'slug\' => \'slug-name\' )' );
				}

				$part_index = $part['index'];
				$part_slug  = $part['slug'];

			} else {
				// Get current part index
				$part_index = wds_page_builder()->functions->get_parts_index();
				$part_slug  = $part;
			}

			$area     = ( $area ) ? $area : wds_page_builder()->areas->get_current_area();
			$area_key = $area ? $area . '_' : '';
			if ( 'page_builder_default' == $area ) {
				$area_key = '';
			}
			$post_id = $post_id ? $post_id : get_queried_object_id();
			$meta    = get_post_meta( $post_id, '_wds_builder_' . esc_attr( $area_key ) . 'template', 1 );

			if ( ! $meta || 'none' == $meta[0]['template_group'] ) {
				// Get default layout for this area.
				$option = get_option( $this->plugin->options->key . '_default_area_layouts' );
				$meta = get_post_meta( $option[$area], '_wds_builder_layout_template', true );
			}
			if (
				// if index exists and the template_group index is there
				isset( $meta[ $part_index ]['template_group'] )
				// and the template group is rthe same we're looking for
				&& $part_slug == $meta[ $part_index ]['template_group']
				// And we have the meta_key they're looking for
				&& isset( $meta[ $part_index ][ $meta_key ] )
			) {
				// Send it back.
				return $meta[ $part_index ][ $meta_key ];
			}

			return null;
		}

	}
}
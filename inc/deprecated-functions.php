<?php
/**
 * Deprecated functions
 * All the functions here are deprecated. Please use their alternatives.
 * @package WDS Simple Page Builder
 */

/**
 * Load an array of template parts (by slug). If no array is passed, used as a wrapper
 * for the wds_page_builder_load_parts action.
 *
 * @deprecated               We're no longer loading all the parts in one place, parts are saved to areas, so use wds_page_builder_area instead.
 *
 * @since  1.3
 * @param  mixed  $parts     Optional. A specific layout or an array of parts to display.
 * @param  string $container Optional. Container HTML element.
 * @param  string $class     Optional. Custom container class to wrap around individual parts.
 * @param  string $area      Optional. The area which these parts belong to.
 * @return void
 */
function wds_page_builder_load_parts( $parts = '', $container = '', $class = '', $area = '' ) {
	_deprecated_function( 'wds_page_builder_load_parts', '1.6', 'wds_page_builder_area' );
	wds_page_builder()->functions->load_parts( $parts, $container, $class, $area );
}
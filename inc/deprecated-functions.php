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

/**
 * Helper function to display page builder with a full wrap.
 *
 * Note, this should be used only if the option to use a wrapper is _disabled_, otherwise, you'll get the page builder contents twice
 *
 * @deprecated               Deprecated with wds_page_builder_load_parts.
 *
 * @param  string $container Optional. Unique container html element or use the default.
 * @param  string $class     Optional. Unique class to pass to the wrapper -- this is the only way to change the container classes without a filter.
 * @param  string $layout    Optional. The specific layout name to load, or the default.
 * @return void
 */
function wds_page_builder_wrap( $container = '', $class = '', $layout = '' ) {
	_deprecated_function( 'wds_page_builder_wrap', '1.6' );
	$page_builder = wds_page_builder()->functions;
	add_action( 'wds_page_builder_before_load_template', array( $page_builder, 'before_parts' ), 10, 2 );
	add_action( 'wds_page_builder_after_load_template', array( $page_builder, 'after_parts' ), 10, 2 );

	// Do the page builder stuff.
	wds_page_builder_load_parts( $layout, $container, $class );

}


/**
 * Helper function to get the template part prefix
 *
 * @deprecated    Since 1.6 prefixes aren't used.
 *
 * @return string The template part prefix (without the hyphen)
 */
function wds_page_builder_template_part_prefix() {
	_deprecated_function( 'wds_page_builder_template_part_prefix', '1.6' );
	return wds_page_builder()->options->get_parts_prefix();
}

/**
 * Helper function to return the template parts directory
 *
 * @deprecated    Since 1.6 template part directory doesn't need to be hard-coded.
 * @link          https://github.com/WebDevStudios/WDS-Simple-Page-Builder/wiki/Page-Builder-Template-Stack
 *
 * @return string The template part directory name
 */
function wds_page_builder_template_parts_dir() {
	_deprecated_function( 'wds_page_builder_template_parts_dir', '1.6' );
	return wds_page_builder()->options->get_parts_dir();
}

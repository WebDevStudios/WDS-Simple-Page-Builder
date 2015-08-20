<?php

/**
 * Wrapper function around cmb2_get_option
 * @since  0.1.0
 * @param  string  $key Options array key
 * @return mixed        Option value
 */
function wds_page_builder_get_option( $key = '' ) {
//	return cmb2_get_option( WDS_Page_Builder_Options()->key, $key );
	return array();
}

/**
 * Helper function to get the template part prefix
 * @return string The template part prefix (without the hyphen)
 */
function wds_page_builder_template_part_prefix() {
	$prefix = ( wds_page_builder_get_option( 'parts_prefix' ) ) ? wds_page_builder_get_option( 'parts_prefix' ) : 'part';
	return apply_filters( 'wds_page_builder_parts_prefix', $prefix );
}

/**
 * Helper function to return the template parts directory
 * @return string The template part directory name
 */
function wds_page_builder_template_parts_dir() {
	return wds_page_builder()->options->get_parts_dir();
}

/**
 * Helper function to return the main page builder container class
 * @return string The class name
 */
function wds_page_builder_container_class() {
	return wds_page_builder()->options->get_parts_prefix();
}

/**
 * Helper function to return the main page builder container element
 * @return string The container type
 */
function wds_page_builder_container() {
	$container = ( wds_page_builder_get_option( 'container' ) ) ? wds_page_builder_get_option( 'container' ) : 'section';
	return sanitize_title( apply_filters( 'wds_page_builder_container', $container ) );
}

/**
 * Get a list of the template parts in the current theme, return them
 * in an array.
 *
 * @return array An array of template parts
 */
function wds_page_builder_get_parts() {
	$parts        = array();
	$parts_dir    = trailingslashit( get_template_directory() ) . wds_page_builder_template_parts_dir();
	$parts_prefix = wds_page_builder_template_part_prefix();

	// add a generic 'none' option
	$parts['none'] = __( '- No Template Parts -', 'wds-simple-page-builder' );

	foreach( glob( $parts_dir . '/' . $parts_prefix . '-*.php' ) as $part ) {
		$part_slug = str_replace( array( $parts_dir . '/' . $parts_prefix . '-', '.php' ), '', $part );
		$parts[$part_slug] = ucwords( str_replace( '-', ' ', $part_slug ) );
	}

	if ( empty( $parts ) ) {
		return __( 'No template parts found', 'wds-simple-page-builder' );
	}

	return $parts;
}

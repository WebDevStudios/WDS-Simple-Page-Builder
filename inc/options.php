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
	return wds_page_builder()->options->get_parts_prefix();
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
	$class = ( wds_page_builder_get_option( 'container_class' ) ) ? wds_page_builder_get_option( 'container_class' ) : 'pagebuilder-part';
	return sanitize_title( apply_filters( 'wds_page_builder_container_class', $class ) );
}

/**
 * Get a list of the template parts in the current theme, return them
 * in an array.
 *
 * @return array An array of template parts
 */
function wds_page_builder_get_parts() {
	$parts = wds_page_builder()->options->get_parts();

	return $parts;
}

<?php

/**
 * Function to register a new layout programmatically
 * @param  string $name       The layout name
 * @param  array  $templates  An array of templates to add to the layout
 * @param  bool   $allow_edit If false, layout will not appear in the Page Builder Options
 *                            Saved Layouts. If true, users can edit the layout after it's
 *                            registered.
 * @return null
 */
function register_page_builder_layout( $name = '', $templates = array(), $allow_edit = false ) {
	// don't register anything if no layout name or templates were passed
	if ( '' == $name || empty( $templates ) ) {
		return false;
	}


}

/**
 * Load an array of template parts (by slug). If no array is passed, used as a wrapper
 * for the wds_page_builder_load_parts action.
 * @param  array  $parts (Optional) Array of specific parts to display
 * @return null
 */
function wds_page_builder_load_parts( $parts = array() ) {
	if ( empty( $parts ) ) {
		do_action( 'wds_page_builder_load_parts' );
		return;
	}

	// parts are specified by their slugs, we pass them to the load_part function which uses the load_template_part method in the WDS_Page_Builder class
	foreach ( $parts as $part ) {
		wds_page_builder_load_part( $part );
	}

	return;
}

/**
 * Helper function for loading a single template part
 * @param  string $part The part slug
 * @return null
 */
function wds_page_builder_load_part( $part = '' ) {
	// bail if no part was specified
	if ( '' == $part ) {
		return;
	}

	$page_builder = new WDS_Page_Builder;
	$page_builder->load_template_part( array( 'template_group' => $part ) );
}
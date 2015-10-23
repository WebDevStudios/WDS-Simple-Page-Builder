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
 * @deprecated    Since 1.6 prefixes aren't used. Instead of using file names to determine part names in the dropdown, use the template part header.
 * @link          https://github.com/WebDevStudios/WDS-Simple-Page-Builder/issues/30#issuecomment-133083700
 * @todo          This needs documentation.
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

/**
 * Function to register a new layout programmatically
 *
 * @deprecated                Since 1.6, layouts are a post type.
 *
 * @since  1.3
 * @param  string $name       The layout name.
 * @param  array  $templates  An array of templates to add to the layout.
 * @param  bool   $allow_edit If false, layout will not appear in the Page Builder Options
 *                            Saved Layouts. If true, users can edit the layout after it's
 *                            registered.
 * @return null
 */
function register_page_builder_layout( $name = '', $templates = array(), $allow_edit = false ) {

	_deprecated_function( 'register_page_builder_layout', '1.6' );

	// Don't register anything if no layout name or templates were passed.
	if ( '' == $name || empty( $templates ) ) {
		return false;
	}

	wp_cache_delete( 'alloptions', 'options' );

	// If allow edit is true, add the template to the same options group as the other templates. This will enable users to update the layout after it's registered.
	if ( $allow_edit ) {

		$old_options = get_option( 'wds_page_builder_options' );
		$new_options = $old_options;
		$new_options['parts_saved_layouts'][] = array(
			'layouts_name'   => esc_attr( $name ),
			'default_layout' => false,
			'template_group' => $templates,
		);

		// Check existing layouts for the one we're trying to add to see if it exists.
		$existing_layouts = isset( $old_options['parts_saved_layouts'] ) ? $old_options['parts_saved_layouts'] : array();
		$layout_exists    = saved_page_builder_layout_exists( esc_attr( $name ) );

		// If the layout doesn't exist already, add it. this allows that layout to be edited.
		if ( ! $layout_exists ) {
			update_option( 'wds_page_builder_options', $new_options );
		}

		return;

	}

	// This is a hard coded layout.
	$options = get_option( 'wds_page_builder_layouts' );

	// Check existing layouts for the one we're trying to add to see if it exists.
	$layout_exists   = false;
	$updated_options = false;
	if ( is_array( $options ) ) {
		$i = 0;
		foreach ( $options as $layout ) {
			if ( saved_page_builder_layout_exists( esc_attr( $name ), false ) ) {
				// Check if the group has changed. if it hasn't, this layout exists.
				if ( $templates !== $layout['template_group'] ) {
					$layout_exists = true;
				} else {
					// If the group is different, delete the option, then insert the new templates into the template group.
					delete_option( 'wds_page_builder_layouts' );
					unset( $options[ $i ] );
					$options[ $i ]['layouts_name']   = esc_attr( $name );
					$options[ $i ]['template_group'] = $templates;
					$updated_options = true;
				}
			}
			$i++;
		}
	}

	if ( $updated_options ) {
		$new_options = $options;
	} else {
		$new_options = $options;
		$new_options[] = array(
			'layouts_name'   => esc_attr( $name ),
			'template_group' => $templates,
		);
	}

	// Only run update_option if the layout doesn't exist already.
	if ( ! $layout_exists ) {
		update_option( 'wds_page_builder_layouts', $new_options );
	}

	return;

}


/**
 * Check if a given layout exists
 *
 * @deprecated                  Since 1.6 layouts are a post type. Most of this function has to do with the pre-1.6 layouts when they were saved in the options table. Use get_saved_page_builder_layout_by_slug or get_saved_page_builder_layout instead.
 *
 * @since  1.4.2
 * @param  string  $layout_name The name of the saved layout.
 * @param  boolean $editable    Whether the layout is editable or hard-coded.
 * @return boolean              True if it exists, false if it doesn't
 */
function saved_page_builder_layout_exists( $layout_name = '', $editable = true ) {
	_deprecated_function( 'saved_page_builder_layout_exists', '1.6', 'get_saved_page_builder_layout_by_slug' );

	if ( '' == $layout_name ) {
		return false;
	}

	// Check for new saved layouts. Just see if there's any at all for this check.
	if ( get_saved_page_builder_layout() ) {
		return true;
	}
	// @todo Deprecate all this.
	elseif ( $editable ) {
		$options          = get_option( 'wds_page_builder_options' );
		$existing_layouts = isset( $options['parts_saved_layouts'] ) ? $options['parts_saved_layouts'] : array();
		$layout_exists    = false;

		if ( ! $options ) {
			return $layout_exists;
		}

		foreach ( $existing_layouts as $layout ) {
			if ( esc_attr( $layout_name ) == $layout['layouts_name'] ) {
				$layout_exists = true;
			}
		}
	} else {
		$options       = get_option( 'wds_page_builder_layouts' );
		$layout_exists = false;

		if ( ! $options  ) {
			return $layout_exists;
		}

		foreach ( $options as $layout ) {
			if ( esc_attr( $layout_name ) == $layout['layouts_name'] ) {
				$layout_exists = true;
			}
		}
	}

	return $layout_exists;

}


/**
 * Function to remove a registered layout. Best used in a deactivation hook.
 *
 * @deprecated               Since 1.6 layouts are a post type.
 *
 * @since  1.4
 * @param  string $name      The layout name. Pass 'all' to delete all registered layouts.
 * @return null
 */
function unregister_page_builder_layout( $name = '' ) {
	_deprecated_function( 'unregister_page_builder_layout', '1.6' );
	// Bail if no name was passed.
	if ( '' == $name ) {
		return;
	}

	wp_cache_delete( 'alloptions', 'options' );

	// If 'all' is passed, delete the option entirely.
	if ( 'all' == $name ) {
		delete_option( 'wds_page_builder_layouts' );
		return;
	}

	$old_options = ( is_array( get_option( 'wds_page_builder_layouts' ) ) ) ? get_option( 'wds_page_builder_layouts' ) : false;

	if ( $old_options ) {
		foreach ( $old_options as $layout ) {
			// Check for the passed layout name. Save the layout as long as it does NOT match.
			if ( esc_attr( $name ) !== $layout['layouts_name'] ) {
				$new_options[] = $layout;
			}
		}

		// Delete the saved layout before updating.
		delete_option( 'wds_page_builder_layouts' );
		update_option( 'wds_page_builder_layouts', $new_options );

	}

	return;

}

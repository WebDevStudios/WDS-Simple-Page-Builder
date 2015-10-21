<?php

/**
 * Load an array of template parts (by slug). If no array is passed, used as a wrapper
 * for the wds_page_builder_load_parts action.
 * @since  1.3
 * @param  mixed  $parts     Optional. A specific layout or an array of parts to
 *                           display
 * @param  string $container Optional. Container HTML element.
 * @param  string $class     Optional. Custom container class to wrap around individual parts
 * @param  string $area      Optional. The area which these parts belong to.
 *
 * @return null
 */
function wds_page_builder_load_parts( $parts = '', $container = '', $class = '', $area = '' ) {
	wds_page_builder()->functions->load_parts( $parts, $container, $class, $area );
}

/**
 * Helper function for loading a single template part
 * @since  1.3
 * @param  string $part The part slug
 * @return null
 */
function wds_page_builder_load_part( $part = '' ) {
	// bail if no part was specified
	if ( '' == $part ) {
		return;
	}

	wds_page_builder()->functions->load_part( array( 'template_group' => $part ) );
}

/**
 * Gets an array of page builder parts.
 *
 * Note, this function ONLY returns values AFTER the parts have been loaded, so hook into
 * wds_page_builder_after_load_parts or later for this to be populated
 * @since  1.5
 * @return array An array of template parts in use on the page
 */
function get_page_builder_parts() {
	return wds_page_builder()->functions->page_builder_parts();
}

/**
 * Function to register a new page builder "area"
 *
 * @param  string $slug      The slug of the area
 * @param  string $name      The descriptive name of the area
 * @param  array  $templates You can define the templates that go in this area the same way you
 *                           would with register_page_builder_layout
 * @return void
 */
function register_page_builder_area( $slug = '', $name = '', $templates = array() ) {
	// bail if no name was passed
	if ( ! $slug ) {
		return;
	}

	wds_page_builder()->areas->register_area( $slug, $name, $templates );
}

/**
 * Gets the page builder areas
 * @return mixed False if there are no areas or an array of layouts if there's more than one.
 */
function get_page_builder_areas() {
	$areas = wds_page_builder()->areas->get_registered_areas();

	if ( ! $areas ) {
		return false;
	}

	return $areas;
}

/**
 * Function that can be used to return a specific page builder area
 * @param  string  $area    The area by slug/name
 * @param  integer $post_id Optional. The post id. If none is passed, we will try to get one if
 *                          it's necessary.
 * @return void
 */
function get_page_builder_area( $area = '', $post_id = 0 ) {
	wds_page_builder()->areas->get_area( $area, $post_id );
}

/**
 * The function to load a specific page builder area
 * @param  string  $area    Which area to load. If no page builder area is found, will
 *                          look for a saved layout with the same name.
 * @param  integer $post_id Optional. The post id.
 * @return void
 */
function wds_page_builder_area( $area = 'page_builder_default', $post_id = 0 ) {
	wds_page_builder()->areas->do_area( $area, $post_id );
}

/**
 * Helper function to display page builder with a full wrap.
 *
 * Note, this should be used only if the option to use a wrapper is _disabled_, otherwise, you'll
 * get the page builder contents twice
 * @param  string $container Optional. Unique container html element or use the default
 * @param  string $class     Optional. Unique class to pass to the wrapper -- this is the only way
 *                           to change the container classes without a filter.
 * @param  string $layout    Optional. The specific layout name to load, or the default.
 * @return void
 */
function wds_page_builder_wrap( $container = '', $class = '', $layout = '' ) {
	$page_builder = wds_page_builder()->functions;
	add_action( 'wds_page_builder_before_load_template', array( $page_builder, 'before_parts' ), 10, 2 );
	add_action( 'wds_page_builder_after_load_template', array( $page_builder, 'after_parts' ), 10, 2 );

	// do the page builder stuff
	wds_page_builder_load_parts( $layout, $container, $class );

}

/**
 * Function to programmatically set certain Page Builder options
 * @param  array  $args An array of arguments matching Page Builder settings in the options table.
 *                      'parts_dir'       The directory that template parts are saved in
 *                      'parts_prefix'    The template part prefix being used
 *                      'use_wrap'        'on' to use the container wrap, empty string to omit.
 *                      'container'       A valid HTML container type.
 *                      'container_class' The container class
 *                      'post_types'      A post type name as a string or array of post types
 *                      'hide_options'    True to hide options that have been set, disabled to
 *                                        display them as uneditable fields
 * @return void
 */
function wds_register_page_builder_options( $args = array() ) {
	$defaults = array(
		'hide_options'    => true,
	);

	$args = wp_parse_args( $args, $defaults );

	do_action( 'wds_register_page_builder_options', $args );
}

/**
 * Helper function to add Page Builder theme support
 *
 * Because theme features are all hard-coded, we can't pass arguments directly to
 * add_theme_supports (at least, not that I'm aware of...). This helper function MUST be used in
 * combination with `add_theme_support( 'wds-simple-page-builder' )` in order to pass the correct
 * values to the Page Builder options.
 * @since  1.5
 * @param  array  $args An array of arguments matching Page Builder settings in the options table.
 *                      'parts_dir'       The directory that template parts are saved in
 *                      'parts_prefix'    The template part prefix being used
 *                      'use_wrap'        'on' to use the container wrap, empty string to omit.
 *                      'container'       A valid HTML container type.
 *                      'container_class' The container class
 *                      'post_types'      A post type name as a string or array of post types
 *                      'hide_options'    True to hide options that have been set, disabled to
 *                                        display them as uneditable fields
 * @return void
 */
function wds_page_builder_theme_support( $args = array() ) {
	$defaults = array(
		'hide_options'    => true,
	);

	$args = wp_parse_args( $args, $defaults );
	do_action( 'wds_page_builder_add_theme_support', $args );
}

/**
 * Grabs the value of the current template part's meta key.
 *
 * @since 1.6
 * @param string $meta_key  The meta key to find the value of.
 *
 * @return mixed|null       Null on failure or the value of the meta key on success.
 */
function wds_page_builder_get_this_part_data( $meta_key ) {
	$part_slug = wds_page_builder()->functions->get_part();

	if ( $part_slug ) {
		return wds_page_builder_get_part_data( $part_slug, $meta_key );
	}

	return null;
}

/**
 * Grabs the value of specific meta keys for specific template parts.
 *
 * $part_slug should be the slug of the template part, for instance if the template
 * part is `part-sample.php` where part is the prefix, the slug would be `sample` excluding
 * the .php extension.
 *
 * @since 1.6
 * @param string $part          The template part slug or index/slug array
 * @param string $meta_key      The meta to find the value of.
 * @param int    $post_id       The Post ID to retrieve the data for (optional)
 *
 * @return null|mixed           Null on failure, the stored meta value on success.
 */
function wds_page_builder_get_part_data( $part, $meta_key, $post_id = 0 ) {
	return wds_page_builder()->data->get( $part, $meta_key, $post_id );
}

/**
 * Wrapper function around WDS_Page_Builder_Options::get()
 * @since  0.1.0
 * @param  string  $key Options array key
 * @return mixed        Option value
 */
function wds_page_builder_get_option( $key = '', $default = false ) {
	return wds_page_builder()->options->get( $key, $default );
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
 * Helper function to return the main page builder container element
 * @return string The class name
 */
function wds_page_builder_container() {
	$container = wds_page_builder_get_option( 'container' );
	return esc_attr( apply_filters( 'wds_page_builder_container_class', $container ) );
}

/**
 * Helper function to return the main page builder container class
 * @return string The class name
 */
function wds_page_builder_container_class() {
	$class = wds_page_builder_get_option( 'container_class' );
	return esc_attr( apply_filters( 'wds_page_builder_container_class', $class ) );
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

/**
 * Function to register a new layout programmatically
 * @since  1.3
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

	wp_cache_delete( 'alloptions', 'options' );

	// if allow edit is true, add the template to the same options group as the other templates. this will enable users to update the layout after it's registered.
	if ( $allow_edit ) {

		$old_options = get_option( 'wds_page_builder_options' );
		$new_options = $old_options;
		$new_options['parts_saved_layouts'][] = array(
			'layouts_name'   => esc_attr( $name ),
			'default_layout' => false,
			'template_group' => $templates,
		);

		// check existing layouts for the one we're trying to add to see if it exists
		$existing_layouts = isset( $old_options['parts_saved_layouts'] ) ? $old_options['parts_saved_layouts'] : array();
		$layout_exists    = saved_page_builder_layout_exists( esc_attr( $name ) );

		// if the layout doesn't exist already, add it. this allows that layout to be edited
		if ( ! $layout_exists ) {
			update_option( 'wds_page_builder_options', $new_options );
		}

		return;

	}

	// This is a hard coded layout

	$options = get_option( 'wds_page_builder_layouts' );

	// check existing layouts for the one we're trying to add to see if it exists
	$layout_exists   = false;
	$updated_options = false;
	if ( is_array( $options ) ) {
		$i = 0;
		foreach ( $options as $layout ) {
			if ( saved_page_builder_layout_exists( esc_attr( $name ), false ) ) {
				// check if the group has changed. if it hasn't, this layout exists
				if ( $templates !== $layout['template_group'] ) {
					$layout_exists = true;
				} else {
					// if the group is different, delete the option, then insert the new templates into the template group
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

	// only run update_option if the layout doesn't exist already
	if ( ! $layout_exists ) {
		update_option( 'wds_page_builder_layouts', $new_options );
	}

	return;

}

/**
 * Return a saved layout object by its slug.
 * Note: This only works with layouts created after 1.6.
 * @since  1.6.0
 * @param  string $layout_name The post slug of the pagebuilder layout.
 * @return object              The WP_Post object for the pagebuilder layout.
 */
function get_saved_page_builder_layout( $layout_name = '' ) {
	if ( '' == $layout_name ) {
		return false;
	}

	$layout = get_posts( array(
		'post_type'      => 'wds_pb_layout',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'name'           => sanitize_title( $layout_name ),
	) );

	return $layout[0];
}

/**
 * Check if a given layout exists
 * @since  1.4.2
 * @param  string  $layout_name The name of the saved layout
 * @param  boolean $editable    Whether the layout is editable or hard-coded
 * @return boolean              True if it exists, false if it doesn't
 */
function saved_page_builder_layout_exists( $layout_name = '', $editable = true ) {
	if ( '' == $layout_name ) {
		return false;
	}

	if ( $editable ) {
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
 * @since  1.4
 * @param  string $name      The layout name. Pass 'all' to delete all registered layouts.
 * @return null
 */
function unregister_page_builder_layout( $name = '' ) {
	// bail if no name was passed
	if ( '' == $name ) {
		return;
	}

	wp_cache_delete( 'alloptions', 'options' );

	// if 'all' is passed, delete the option entirely
	if ( 'all' == $name ) {
		delete_option( 'wds_page_builder_layouts' );
		return;
	}

	$old_options = ( is_array( get_option( 'wds_page_builder_layouts' ) ) ) ? get_option( 'wds_page_builder_layouts' ) : false;

	if ( $old_options ) {
		foreach ( $old_options as $layout ) {
			// check for the passed layout name. save the layout as long as it does NOT match.
			if ( esc_attr( $name ) !== $layout['layouts_name'] ) {
				$new_options[] = $layout;
			}
		}

		// delete the saved layout before updating
		delete_option( 'wds_page_builder_layouts' );
		update_option( 'wds_page_builder_layouts', $new_options );

	}

	return;

}

/**
 * spb_register_template_stack function.
 *
 * @access public
 * @param string $location_callback (default: '')
 * @param int $priority (default: 10)
 * @return void
 */
function spb_register_template_stack( $location_callback = '', $priority = 10 ) {

	// Bail if no location, or function/method is not callable
	if ( empty( $location_callback ) || ! is_callable( $location_callback ) ) {
		return false;
	}

	// Add location callback to template stack
	return add_filter( 'spb_template_stack', $location_callback, (int) $priority );
}


/**
 * spb_get_template_stack function.
 *
 * @access public
 * @return array
 */
function spb_get_template_stack() {
	global $wp_filter, $merged_filters, $wp_current_filter;

	// Setup some default variables
	$tag  = 'spb_template_stack';
	$args = $stack = array();

	// Add 'spb_template_stack' to the current filter array
	$wp_current_filter[] = $tag;

	// Sort
	if ( ! isset( $merged_filters[ $tag ] ) ) {
		ksort( $wp_filter[$tag] );
		$merged_filters[ $tag ] = true;
	}

	// Ensure we're always at the beginning of the filter array
	reset( $wp_filter[ $tag ] );

	// Loop through 'spb_template_stack' filters, and call callback functions
	do {
		foreach( (array) current( $wp_filter[$tag] ) as $the_ ) {
			if ( ! is_null( $the_['function'] ) ) {
				$args[1] = $stack;
				$stack[] = call_user_func_array( $the_['function'], array_slice( $args, 1, (int) $the_['accepted_args'] ) );
			}
		}
	} while ( next( $wp_filter[$tag] ) !== false );

	// Remove 'spb_template_stack' from the current filter array
	array_pop( $wp_current_filter );

	// Remove empties and duplicates
	$stack = array_unique( array_filter( $stack ) );

	/**
	 * Filters the "template stack" list of registered directories where templates can be found.
	 *
	 * @param array $stack Array of registered directories for template locations.
	 */
	return (array) apply_filters( 'spb_get_template_stack', $stack ) ;
}


/**
 * has_page_builder_part function.
 *
 * pass template slug returns true if loaded on page
 *
 * @access public
 * @param mixed $template (default: null)
 * @return boolean
 */
function has_page_builder_part( $template = null ) {

	$parts = wds_page_builder()->areas->parts;

	if( in_array( $template, $parts ) ) {
		return true;
	}

	return false;

}



/**
 * is_page_builder_page function.
 *
 * @access public
 * @return string
 */
function is_page_builder_page() {

	if( wds_page_builder()->areas->area ) return wds_page_builder()->areas->area;

	return false;

}


/**
 * Get all the page builder part files across all Pagebuilder-based plugins/themes.
 * @return array An array of Pagebuilder template parts.
 */
function get_page_builder_part_files() {
	if ( wds_page_builder()->options->get_part_files() ) {
		return wds_page_builder()->options->get_part_files();
	}
	return false;
}

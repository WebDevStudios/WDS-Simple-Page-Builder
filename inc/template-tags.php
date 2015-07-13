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

	wp_cache_delete ( 'alloptions', 'options' );

	// if allow edit is true, add the template to the same options group as the other templates. this will enable users to update the layout after it's registered.
	if ( $allow_edit ) {

		$old_options = get_option( 'wds_page_builder_options' );
		$new_options = $old_options;
		$new_options['parts_saved_layouts'][] = array(
			'layouts_name'   => esc_attr( $name ),
			'default_layout' => false,
			'template_group' => $templates
		);

		// check existing layouts for the one we're trying to add to see if it exists
		$existing_layouts = $old_options['parts_saved_layouts'];
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
		foreach( $options as $layout ) {
			if ( saved_page_builder_layout_exists( esc_attr( $name ), false ) ) {
				// check if the group has changed. if it hasn't, this layout exists
				if ( $templates !== $layout['template_group'] ) {
					$layout_exists = true;
				} else {
					// if the group is different, delete the option, then insert the new templates into the template group
					delete_option( 'wds_page_builder_layouts' );
					unset( $options[$i] );
					$options[$i]['layouts_name']   = esc_attr( $name );
					$options[$i]['template_group'] = $templates;
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
			'layouts_name'   =>  esc_attr( $name ),
			'template_group' => $templates
		);
	}

	// only run update_option if the layout doesn't exist already
	if ( ! $layout_exists ) {
		update_option( 'wds_page_builder_layouts', $new_options );
	}

	return;

}

/**
 * Check if a given layout exists
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
		$existing_layouts = $options['parts_saved_layouts'];
		$layout_exists    = false;
		foreach( $existing_layouts as $layout ) {
			if ( esc_attr( $layout_name ) == $layout['layouts_name'] ) {
				$layout_exists = true;
			}
		}
	} else {
		$options       = get_option( 'wds_page_builder_layouts' );
		$layout_exists = false;
		foreach( $options as $layout ) {
			if ( esc_attr( $layout_name ) == $layout['layouts_name'] ) {
				$layout_exists = true;
			}
		}
	}

	return $layout_exists;

}

/**
 * Function to remove a registered layout. Best used in a deactivation hook.
 * @param  string $name      The layout name. Pass 'all' to delete all registered layouts.
 * @return null
 */
function unregister_page_builder_layout( $name = '' ) {
	// bail if no name was passed
	if ( '' == $name ) {
		return;
	}

	wp_cache_delete ( 'alloptions', 'options' );

	// if 'all' is passed, delete the option entirely
	if ( 'all' == $name ) {
		delete_option( 'wds_page_builder_layouts' );
		return;
	}

	$old_options = ( is_array( get_option( 'wds_page_builder_layouts' ) ) ) ? get_option( 'wds_page_builder_layouts' ) : false;

	if ( $old_options ) {
		foreach( $old_options as $layout ) {
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
 * Load an array of template parts (by slug). If no array is passed, used as a wrapper
 * for the wds_page_builder_load_parts action.
 * @param  string|array  $parts (Optional) A specific layout or an array of parts to
 *                              display
 * @return null
 */
function wds_page_builder_load_parts( $parts = '' ) {
	if ( ! is_array( $parts ) ) {
		do_action( 'wds_page_builder_before_load_parts' );
		do_action( 'wds_page_builder_load_parts', $parts );
		do_action( 'wds_page_builder_after_load_parts' );
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
<?php
/**
 * Add an options page. We want to define a template parts directory and a
 * file prefix that identifies template parts as such.
 */

class WDS_Page_Builder_Options {

	/**
 	 * Option key, and option page slug
 	 * @var string
 	 */
	private $key = 'wds_page_builder_options';

	/**
 	 * Options page metabox id
 	 * @var string
 	 */
	private $metabox_id = 'wds_page_builder_option_metabox';

	/**
	 * Options Page title
	 * @var string
	 */
	protected $title = '';

	/**
	 * Options Page hook
	 * @var string
	 */
	protected $options_page = '';

	/**
	 * Constructor
	 * @since 0.1.0
	 */
	public function __construct() {
		// Set our title
		$this->title = apply_filters( 'wds_page_builder_options_title', __( 'Page Builder Options', 'wds-simple-page-builder' ) );

		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'cmb2_init', array( $this, 'add_options_page_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
	}

	public function load_scripts( $hook ) {
		if ( 'settings_page_wds_page_builder_options' == $hook ) {
			wp_enqueue_script( 'page-builder', wds_page_builder()->directory_url . '/assets/js/page-builder.js', array( 'jquery' ), '1.4.1', true );
		}
	}

	/**
	 * Register our setting to WP
	 * @since  0.1.0
	 */
	public function init() {
		register_setting( $this->key, $this->key );
		add_filter( 'pre_update_option_wds_page_builder_options', array( $this, 'prevent_blank_templates' ), 10, 2 );
	}

	/**
	 * Hooks to pre_update_option_{option name} to prevent empty templates from being saved
	 * to the Saved Layouts
	 * @param  mixed $new_value The new value
	 * @param  mixed $old_value The old value
	 * @return mixed            The filtered setting
	 * @link   https://codex.wordpress.org/Plugin_API/Filter_Reference/pre_update_option_(option_name)
	 * @since  1.4.1
	 */
	public function prevent_blank_templates( $new_value, $old_value ) {
		$saved_layouts = $new_value['parts_saved_layouts'];
		$i = 0;
		foreach( $saved_layouts as $layout ) {
			$layout['template_group'] = array_diff( $layout['template_group'], array('none'));
			$saved_layouts[$i] = $layout;
			$i++;
		}
		$new_value['parts_saved_layouts'] = $saved_layouts;
		return $new_value;
	}


	/**
	 * Add menu options page
	 * @since 0.1.0
	 */
	public function add_options_page() {
		$this->options_page = add_submenu_page( 'options-general.php', $this->title, $this->title, 'manage_options', $this->key, array( $this, 'admin_page_display' ) );
	}

	/**
	 * Admin page markup. Mostly handled by CMB2
	 * @since  0.1.0
	 */
	public function admin_page_display() {
		?>
		<div class="wrap cmb2_options_page <?php echo $this->key; ?>">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<?php cmb2_metabox_form( $this->metabox_id, $this->key ); ?>
		</div>
		<?php
	}

	/**
	 * Add the options metabox to the array of metaboxes
	 * @since  0.1.0
	 */
	function add_options_page_metabox() {

		$cmb = new_cmb2_box( array(
			'id'      => $this->metabox_id,
			'hookup'  => false,
			'show_on' => array(
				// These are important, don't remove
				'key'   => 'options-page',
				'value' => array( $this->key, )
			),
		) );

		// Set our CMB2 fields

		$cmb->add_field( array(
			'name' => __( 'Template Parts Directory', 'wds-simple-page-builder' ),
			'desc' => __( 'Where the template parts are located in the theme. Default is /parts', 'wds-simple-page-builder' ),
			'id'   => 'parts_dir',
			'type' => 'text_small',
			'default' => 'parts',
		) );

		$cmb->add_field( array(
			'name' => __( 'Template Parts Prefix', 'wds-simple-page-builder' ),
			'desc' => __( 'File prefix that identifies template parts. Default is part-', 'wds-simple-page-builder' ),
			'id'   => 'parts_prefix',
			'type' => 'text_small',
			'default' => 'part',
		) );

		$cmb->add_field( array(
			'name' => __( 'Allowed Post Types', 'wds-simple-page-builder' ),
			'desc' => __( 'Post types that can use the page builder. Default is Page.', 'wds-simple-page-builder' ),
			'id'   => 'post_types',
			'type' => 'multicheck',
			'default' => 'page',
			'options' => $this->get_post_types()
		) );

		$group_field_id = $cmb->add_field( array(
			'name'         => __( 'Global Template Parts', 'wds-simple-page-builder' ),
			'desc'         => __( 'These can be used on pages that don\'t have template parts added to them.', 'wds-simple-page-builder' ),
			'id'           => 'parts_global_templates',
			'type'         => 'group',
			'options'      => array(
				'group_title'   => __( 'Template Part {#}', 'wds-simple-page-builder' ),
				'add_button'    => __( 'Add another template part', 'wds-simple-page-builder' ),
				'remove_button' => __( 'Remove template part', 'wds-simple-page-builder' ),
				'sortable'      => true
			)
		) );

		$cmb->add_group_field( $group_field_id, array(
			'name'         => __( 'Template', 'wds-simple-page-builder' ),
			'id'           => 'template_group',
			'type'         => 'select',
			'options'      => wds_page_builder_get_parts(),
			'default'      => 'none'
		) );

		$layouts_group_id = $cmb->add_field( array(
			'name'         => __( 'Saved Layouts', 'wds-simple-page-builder' ),
			'desc'         => __( 'Use saved layouts to enable multiple custom page layouts that can be used on different types of pages or post types. Useful to create default layouts for different post types or for having multiple "global" layouts.', 'wds-simple-page-builder' ),
			'id'           => 'parts_saved_layouts',
			'type'         => 'group',
			'options'      => array(
				'group_title'   => __( 'Layout {#}', 'wds-simple-page-builder' ),
				'add_button'    => __( 'Add another layout', 'wds-simple-page-builder' ),
				'remove_button' => __( 'Remove layout', 'wds-simple-page-builder' ),
				'sortable'      => true
			)
		) );

		$cmb->add_group_field( $layouts_group_id, array(
			'name'         => __( 'Layout Name', 'wds-simple-page-builder' ),
			'desc'         => __( 'This should be a unique name used to identify this layout.', 'wds-simple-page-builder' ),
			'id'           => 'layouts_name',
			'type'         => 'text_medium',
			'attributes'   => array( 'required' => 'required' )
		) );

		$cmb->add_group_field( $layouts_group_id, array(
			'name'         => __( 'Use as Default Layout', 'wds-simple-page-builder' ),
			'desc'         => __( 'If you\'d like to use this layout as the default layout for all posts of a type, check the post type to make this layout the default for. If you do not want to set this as the default layout for any post type, leave all types unchecked. The layout can still be called manually in the <code>do_action</code>.', 'wds-simple-page-builder' ),
			'id'           => 'default_layout',
			'type'         => 'multicheck',
			'options'      => $this->get_post_types()
		) );

		$cmb->add_group_field( $layouts_group_id, array(
			'name'         => __( 'Template', 'wds-simple-page-builder' ),
			'id'           => 'template_group',
			'type'         => 'select',
			'options'      => array_merge( wds_page_builder_get_parts(), array( 'add_row_text' => __( 'Add another template part', 'wds-simple-page-builder' ) ) ),
			'default'      => 'none',
			'repeatable'   => true,
		) );

	}

	/**
	 * Get an array of post types for the options page multicheck array
	 * @uses   get_post_types
	 * @return array 			An array of public post types
	 */
	public function get_post_types() {

		$post_types = apply_filters( 'wds_page_builder_post_types', get_post_types( array( 'public' => true ), 'objects' ) );

		foreach ( $post_types as $post_type ) {
			$types[$post_type->name] = $post_type->labels->name;
		}

		return $types;

	}

	/**
	 * Public getter method for retrieving protected/private variables
	 * @since  0.1.0
	 * @param  string  $field Field to retrieve
	 * @return mixed          Field value or exception is thrown
	 */
	public function __get( $field ) {
		// Allowed fields to retrieve
		if ( in_array( $field, array( 'key', 'metabox_id', 'title', 'options_page' ), true ) ) {
			return $this->{$field};
		}

		throw new Exception( 'Invalid property: ' . $field );
	}

}

/**
 * Helper function to get/return the WDS_Page_Builder_Options object
 * @since  0.1.0
 * @return WDS_Page_Builder_Options object
 */
function WDS_Page_Builder_Options() {
	static $object = null;
	if ( is_null( $object ) ) {
		$object = new WDS_Page_Builder_Options();
	}

	return $object;
}

/**
 * Wrapper function around cmb2_get_option
 * @since  0.1.0
 * @param  string  $key Options array key
 * @return mixed        Option value
 */
function wds_page_builder_get_option( $key = '' ) {
	return cmb2_get_option( WDS_Page_Builder_Options()->key, $key );
}

/**
 * Helper function to get the template part prefix
 */
function wds_page_builder_template_part_prefix() {
	$prefix = ( wds_page_builder_get_option( 'parts_prefix' ) ) ? wds_page_builder_get_option( 'parts_prefix' ) : 'part';
	return apply_filters( 'wds_page_builder_parts_prefix', $prefix );
}

/**
 * Helper function to return the template parts directory
 */
function wds_page_builder_template_parts_dir() {
	$directory = ( wds_page_builder_get_option( 'parts_dir' ) ) ? wds_page_builder_get_option( 'parts_dir' ) : 'parts';
	return apply_filters( 'wds_page_builder_parts_directory', $directory );
}

/**
 * Get a list of the template parts in the current theme, return them
 * in an array.
 *
 * @return array An array of template parts
 */
function wds_page_builder_get_parts() {
	$parts        = array();
	$parts_dir    = trailingslashit( get_stylesheet_directory() ) . wds_page_builder_template_parts_dir();
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

// Get it started
WDS_Page_Builder_Options();

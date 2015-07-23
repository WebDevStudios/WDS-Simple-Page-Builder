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
		$this->title   = apply_filters( 'wds_page_builder_options_title', __( 'Page Builder Options', 'wds-simple-page-builder' ) );
		$this->options = $this->get_page_builder_options();

		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'cmb2_init', array( $this, 'add_options_page_metabox' ) );
		add_action( 'wds_register_page_builder_options', array( $this, 'register_settings' ) );
		add_action( 'wds_page_builder_add_theme_support', array( $this, 'add_theme_support' ) );
	}

	/**
	 * Load the admin js
	 * @param  string $hook The admin page we're on
	 * @return void
	 */
	public function load_scripts( $hook ) {
		if ( 'settings_page_wds_page_builder_options' == $hook ) {
			wp_enqueue_script( 'admin', wds_page_builder()->directory_url . '/assets/js/admin.js', array( 'jquery' ), '20150721', true );
		}
	}

	/**
	 * Registers the settings via wds_register_page_builder_options
	 * @since  1.5
	 * @param  array  $args The options to update/register
	 * @return void
	 */
	public function register_settings( $args = array() ) {
		if ( ! empty( $args ) ) {
			wp_cache_delete( 'alloptions', 'options' );
			update_option( 'wds_page_builder_options', $args );
		}
	}

	/**
	 * Helper function to get the current Page Builder Options
	 * @since  1.5
	 * @return array The Page Builder options array
	 */
	public function get_page_builder_options() {
		return get_option( 'wds_page_builder_options' );
	}

	/**
	 * Register our setting to WP
	 * @since  0.1.0
	 */
	public function init() {
		register_setting( $this->key, $this->key );
		add_filter( 'pre_update_option_wds_page_builder_options', array( $this, 'prevent_blank_templates' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
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
	 * Support WordPress add_theme_support feature
	 * @since  1.5
	 * @uses   current_theme_supports
	 * @param  array $args            Array of Page Builder options to set
	 * @link   http://justintadlock.com/archives/2010/11/01/theme-supported-features
	 */
	public function add_theme_support( $args ) {
		if ( current_theme_supports( 'wds-simple-page-builder' ) ) {
			wds_register_page_builder_options( $args );
		}
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

		$disabled = ( 'disabled' == $this->options['hide_options'] ) ? array( 'disabled' => '' ) : array();

		$cmb = new_cmb2_box( array(
			'id'      => $this->metabox_id,
			'hookup'  => false,
			'show_on' => array(
				// These are important, don't remove
				'key'   => 'options-page',
				'value' => array( $this->key, )
			),
		) );

		$cmb->add_field( array(
			'name'       => __( 'Template Parts Directory', 'wds-simple-page-builder' ),
			'desc'       => __( 'Where the template parts are located in the theme. Default is /parts', 'wds-simple-page-builder' ),
			'id'         => 'parts_dir',
			'type'       => 'text_small',
			'default'    => 'parts',
			'show_on_cb' => array( $this, 'show_parts_dir' ),
			'attributes' => $disabled,
		) );

		$cmb->add_field( array(
			'name'       => __( 'Template Parts Prefix', 'wds-simple-page-builder' ),
			'desc'       => __( 'File prefix that identifies template parts. Default is part-', 'wds-simple-page-builder' ),
			'id'         => 'parts_prefix',
			'type'       => 'text_small',
			'default'    => 'part',
			'show_on_cb' => array( $this, 'show_parts_prefix' ),
			'attributes' => $disabled,
		) );

		$cmb->add_field( array(
			'name'       => __( 'Use Wrapper', 'wds-simple-page-builder' ),
			'desc'       => __( 'If checked, a wrapper HTML container will be added around each individual template part.', 'wds-simple-page-builder' ),
			'id'         => 'use_wrap',
			'type'       => 'checkbox',
			'show_on_cb' => array( $this, 'show_use_wrap' ),
			'attributes' => $disabled,
		) );

		$cmb->add_field( array(
			'name'       => __( 'Container Type', 'wds-simple-page-builder' ),
			'desc'       => __( 'The type of HTML container wrapper to use, if Use Wrapper is selected.', 'wds-simple-page-builder' ),
			'id'         => 'container',
			'type'       => 'select',
			'options'    => array(
				'section' => __( 'Section', 'wds-simple-page-builder' ),
				'div'     => __( 'Div', 'wds-simple-page-builder' ),
				'aside'   => __( 'Aside', 'wds-simple-page-builder' ),
				'article' => __( 'Article', 'wds-simple-page-builder' ),
			),
			'default'    => 'section',
			'show_on_cb' => array( $this, 'show_container' ),
			'attributes' => $disabled,
		) );

		$cmb->add_field( array(
			'name'       => __( 'Container Class', 'wds-simple-page-builder' ),
			'desc'       => sprintf( __( '%1$sThe default class to use for all template part wrappers. Specific classes will be added to each wrapper in addition to this. %2$sMultiple classes, separated by a space, can be added here.%3$s', 'wds-simple-page-builder' ), '<p>', '<br />', '</p>' ),
			'id'         => 'container_class',
			'type'       => 'text_medium',
			'default'    => 'pagebuilder-part',
			'show_on_cb' => array( $this, 'show_container_class' ),
			'attributes' => $disabled,
		) );

		$cmb->add_field( array(
			'name'       => __( 'Allowed Post Types', 'wds-simple-page-builder' ),
			'desc'       => __( 'Post types that can use the page builder. Default is Page.', 'wds-simple-page-builder' ),
			'id'         => 'post_types',
			'type'       => 'multicheck',
			'default'    => 'page',
			'options'    => $this->get_post_types(),
			'show_on_cb' => array( $this, 'show_post_types' ),
			'attributes' => $disabled,
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
			// 'attributes'   => array( 'required' => 'required' )
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

	/**
	 * CMB2 show_on callback function for parts_dir option.
	 * @since  1.5
	 * @return bool Whether to show or hide the option.
	 */
	public function show_parts_dir() {
		if ( 'disabled' === $this->options['hide_options'] ) {
			return true;
		}

		return ! ( $this->options['hide_options'] && isset( $this->options['parts_dir'] ) );
	}

	/**
	 * CMB2 show_on callback function for parts_prefix option.
	 * @since  1.5
	 * @return bool Whether to show or hide the option.
	 */
	public function show_parts_prefix() {
		if ( 'disabled' === $this->options['hide_options'] ) {
			return true;
		}

		return ! ( $this->options['hide_options'] && isset( $this->options['parts_prefix'] ) );
	}

	/**
	 * CMB2 show_on callback function for use_wrap option.
	 * @since  1.5
	 * @return bool Whether to show or hide the option.
	 */
	public function show_use_wrap() {
		if ( 'disabled' === $this->options['hide_options'] ) {
			return true;
		}

		return ! ( $this->options['hide_options'] && isset( $this->options['use_wrap'] ) );
	}

	/**
	 * CMB2 show_on callback function for container option.
	 * @since  1.5
	 * @return bool Whether to show or hide the option.
	 */
	public function show_container() {
		if ( 'disabled' === $this->options['hide_options'] ) {
			return true;
		}

		return ! ( $this->options['hide_options'] && isset( $this->options['container'] ) );
	}

	/**
	 * CMB2 show_on callback function for container_class option.
	 * @since  1.5
	 * @return bool Whether to show or hide the option.
	 */
	public function show_container_class() {
		if ( 'disabled' === $this->options['hide_options'] ) {
			return true;
		}

		return ! ( $this->options['hide_options'] && isset( $this->options['container_class'] ) );
	}

	/**
	 * CMB2 show_on callback function for post_types option.
	 * @since  1.5
	 * @return bool Whether to show or hide the option.
	 */
	public function show_post_types() {
		if ( 'disabled' === $this->options['hide_options'] ) {
			return true;
		}

		return ! ( $this->options['hide_options'] && isset( $this->options['post_types'] ) );
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
	$directory = ( wds_page_builder_get_option( 'parts_dir' ) ) ? wds_page_builder_get_option( 'parts_dir' ) : 'parts';
	return apply_filters( 'wds_page_builder_parts_directory', $directory );
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

<?php
/**
 * Plugin Name: WDS Simple Page Builder
 * Plugin URI: https://github.com/WebDevStudios/WDS-Simple-Page-Builder/wiki
 * Description: Uses existing template parts in the currently-active theme to build a customized page with rearrangeable elements.
 * Author: WebDevStudios
 * Author URI: http://webdevstudios.com
 * Version: 1.6
 * License: GPLv2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WDS_Simple_Page_Builder' ) ) {

	class WDS_Simple_Page_Builder {

		/**
		 * Current version number
		 * @var   string
		 * @since 1.5
		 */
		const VERSION = '1.6';

		/**
		 * Singleton instance of plugin
		 *
		 * @var
		 * @since  0.1.0
		 */
		protected static $single_instance = null;

		/**
		 * Creates or returns an instance of this class.
		 *
		 * @since  0.1.0
		 * @return A single instance of this class.
		 */
		public static function get_instance() {
			if ( null === self::$single_instance ) {
				self::$single_instance = new self();
			}

			return self::$single_instance;
		}

		/**
		 * Construct function to get things started.
		 */
		public function __construct() {
			// Setup some base variables for the plugin
			$this->basename       = plugin_basename( __FILE__ );
			$this->directory_path = plugin_dir_path( __FILE__ );
			$this->directory_url  = plugins_url( dirname( $this->basename ) );

			// Include any required files
			require_once( $this->directory_path . 'inc/class-wds-page-builder-options.php' );
			require_once( $this->directory_path . 'inc/class-wds-page-builder-admin.php' );
			require_once( $this->directory_path . 'inc/options.php' );
			require_once( $this->directory_path . 'inc/functions.php' );
			require_once( $this->directory_path . 'inc/template-tags.php' );

			// CMB2 takes care of figuring out which version to run internally
			require_once( $this->directory_path . 'inc/cmb2/init.php' );

			$this->plugin_classes();
			$this->hooks();
		}

		/**
		 * Attach other plugin classes to the base plugin class.
		 *
		 * @since 0.1.0
		 * @return  null
		 */
		function plugin_classes() {
			$this->admin = new WDS_Page_Builder_Admin( $this );
			$this->options = new WDS_Page_Builder_Options( $this );
			$this->builder = new WDS_Page_Builder( $this );
			$GLOBALS['WDS_Page_Builder'] = $this->builder;
		}

		/**
		 * Add hooks and filters
		 *
		 * @return null
		 */
		public function hooks() {
			add_action( 'init', array( $this, 'init' ) );

			// Make sure we have our requirements, and disable the plugin if we do not have them.
			add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );
			// Run our options hooks
			$this->options->hooks();
			// Run our admin hooks
			$this->admin->hooks();
		}

		/**
		 * Init hooks
		 *
		 * @since  0.1.0
		 * @return null
		 */
		public function init() {
			// Load Textdomain
			load_plugin_textdomain( 'wds-simple-page-builder', false, dirname( $this->basename ) . '/languages' );
		}

		/**
		 * Check that all plugin requirements are met
		 *
		 * @return boolean
		 */
		public static function meets_requirements() {
			// Make sure we have CMB so we can use it
			if ( ! defined( 'CMB2_LOADED' ) ) {
				return false;
			}

			// We have met all requirements
			return true;
		}

		/**
		 * Check if the plugin meets requirements and
		 * disable it if they are not present.
		 */
		public function maybe_disable_plugin() {
			if ( ! $this->meets_requirements() ) {
				// Display our error
				echo '<div id="message" class="error">';
				echo '<p>' . sprintf( esc_html__( 'WDS Simple Page Builder requires CMB2 but could not find it. The plugin has been <a href="%s">deactivated</a>. Please make sure all requirements are available.', 'wds-simple-page-builder' ), admin_url( 'plugins.php' ) ) . '</p>';
				echo '</div>';

				// Deactivate our plugin
				deactivate_plugins( $this->basename );
			}
		}

	}

	$GLOBALS['WDS_Simple_Page_Builder'] = new WDS_Simple_Page_Builder;
}

/**
 * Public wrapper function
 */
function wds_page_builder() {
	return WDS_Simple_Page_Builder::get_instance();
}
wds_page_builder();
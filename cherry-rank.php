<?php
/**
 * Plugin Name: Cherry Rank
 * Plugin URI:  http://www.cherryframework.com/
 * Description: Adds rating, likes and views count for posts and cutom post types
 * Version:     1.0.2
 * Author:      Cherry Team
 * Author URI:  http://www.cherryframework.com/
 * Text Domain: cherry-rank
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 *
 * @package  Cherry Rank
 * @category Core
 * @author   Cherry Team
 * @license  GPL-2.0+
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// If class 'Cherry_rank' not exists.
if ( ! class_exists( 'Cherry_Rank' ) ) {

	/**
	 * Sets up and initializes the Cherry Team plugin.
	 *
	 * @since 1.0.0
	 */
	class Cherry_Rank {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		/**
		 * Sets up needed actions/filters for the plugin to initialize.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			$this->constants();
			$this->includes();

			// Internationalize the text strings used.
			add_action( 'plugins_loaded', array( $this, 'lang' ), 2 );

			// Load the admin files.
			add_action( 'plugins_loaded', array( $this, 'admin' ), 4 );

			// Load public-facing style sheet and JavaScript.
			add_action( 'wp_enqueue_scripts', array( $this, 'public_assets' ), 20 );
			add_filter( 'cherry_compiler_static_css', array( $this, 'add_style_to_compiler' ) );

			// Enqueue public JS only on specific action
			add_action( 'cherry_rank_enqueue_assets', array( $this, 'enqueue_assets' ) );

		}

		/**
		 * Defines constants for the plugin.
		 *
		 * @since 1.0.0
		 */
		function constants() {

			/**
			 * Set the version number of the plugin.
			 *
			 * @since 1.0.0
			 */
			define( 'CHERRY_RANK_VERSION', '1.0.2' );

			/**
			 * Set constant path to the plugin directory.
			 *
			 * @since 1.0.0
			 */
			define( 'CHERRY_RANK_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );

			/**
			 * Set constant path to the plugin URI.
			 *
			 * @since 1.0.0
			 */
			define( 'CHERRY_RANK_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) );
		}

		/**
		 * Loads files from the '/inc' folder.
		 *
		 * @since 1.0.0
		 */
		function includes() {
			require_once( CHERRY_RANK_DIR . 'public/includes/class-cherry-rank-options.php' );
			require_once( CHERRY_RANK_DIR . 'public/includes/class-cherry-rank-init.php' );
		}

		/**
		 * Loads the translation files.
		 *
		 * @since 1.0.0
		 */
		function lang() {
			load_plugin_textdomain( 'cherry-rank', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Loads admin files.
		 *
		 * @since 1.0.0
		 */
		function admin() {

			if ( ! is_admin() ) {
				return;
			}

			require_once( CHERRY_RANK_DIR . 'admin/includes/class-cherry-update/class-cherry-plugin-update.php' );

			$Cherry_Plugin_Update = new Cherry_Plugin_Update();
			$Cherry_Plugin_Update -> init( array(
					'version'			=> CHERRY_RANK_VERSION,
					'slug'				=> 'cherry-rank',
					'repository_name'	=> 'cherry-rank',
			));
		}

		/**
		 * Register and enqueue public-facing style sheet.
		 *
		 * @since 1.0.0
		 */
		public function public_assets() {

			wp_enqueue_style(
				'cherry-rank',
				CHERRY_RANK_URI . 'public/assets/css/style.css', '', CHERRY_RANK_VERSION
			);

			wp_register_script(
				'cherry-rank',
				CHERRY_RANK_URI . 'public/assets/js/min/script.min.js', array( 'jquery' ), CHERRY_RANK_VERSION, true
			);

		}

		/**
		 * Enqueue required assets only on call
		 *
		 * @since  1.0.1
		 *
		 * @return void
		 */
		function enqueue_assets() {
			wp_enqueue_script( 'cherry-rank' );
			wp_localize_script(
				'cherry-rank',
				'cherry_rank',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'cherry_rank' ),
				)
			);
		}

		/**
		 * Pass style handle to CSS compiler.
		 *
		 * @since 1.0.0
		 *
		 * @param array $handles CSS handles to compile.
		 */
		function add_style_to_compiler( $handles ) {
			$handles = array_merge(
				array( 'cherry-rank' => plugins_url( 'public/assets/css/style.css', __FILE__ ) ),
				$handles
			);

			return $handles;
		}

		/**
		 * Returns the instance.
		 *
		 * @since  1.0.0
		 * @return object
		 */
		public static function get_instance() {

			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance;
		}
	}

	/**
	 * Gets main class instance
	 *
	 * @since  1.0.1
	 * @return object
	 */
	function cherry_rank() {
		return Cherry_Rank::get_instance();
	}

	cherry_rank();
}

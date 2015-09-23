<?php
/**
 * Init macros callbacks for additional meta
 *
 * @package    Cherry_Rank
 * @subpackage Class
 * @author     Cherry Team <support@cherryframework.com>
 * @copyright  Copyright (c) 2012 - 2015, Cherry Team
 * @link       http://www.cherryframework.com/
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


// If class 'Cherry_Rank_Init' not exists.
if ( ! class_exists( 'Cherry_Rank_Init' ) ) {

	/**
	 * Define init class
	 *
	 * @since 1.0.0
	 */
	class Cherry_Rank_Init {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		/**
		 * Allowed rank-related meta array
		 *
		 * @since 1.0.0
		 * @var   array
		 */
		public $allowed_meta = array( 'rating', 'likes', 'dislikes', 'views' );

		/**
		 * Constructor for the class
		 */
		function __construct() {
			$this->init_callbacks();
		}

		/**
		 * Init macros callbacks
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function init_callbacks() {

			foreach ( $this->allowed_meta as $meta ) {

				$callback_file = CHERRY_RANK_DIR . 'public/includes/class-cherry-callback-' . $meta . '.php';

				if ( ! file_exists( $callback_file ) ) {
					continue;
				}
				require $callback_file;
			}

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

	Cherry_Rank_Init::get_instance();

}

<?php
/**
 * Add plugin-realted options to Cherry options page
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


// If class 'Cherry_Rank_Options' not exists.
if ( ! class_exists( 'Cherry_Rank_Options' ) ) {

	/**
	 * Add options manager class
	 *
	 * @since 1.0.0
	 */
	class Cherry_Rank_Options {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		/**
		 * Constructor for the class
		 */
		function __construct() {
			add_filter( 'cherry_post_meta_options_list', array( $this, 'add_blog_options' ) );
			add_filter( 'cherry_post_single_options_list', array( $this, 'add_single_options' ) );
		}

		/**
		 * Add blog options
		 *
		 * @since  1.0.0
		 * @param  array $options default options array.
		 * @return array
		 */
		public function add_blog_options( $options ) {
			$options['rank_add_blog_meta'] = array(
				'type'			=> 'checkbox',
				'title'			=> __( 'Additional post meta', 'cherry-rank' ),
				'description'	=> __( 'Enable/disable additional post meta.', 'cherry-rank' ),
				'class'			=> '',
				'value'			=> array( 'rating', 'likes', 'dislikes', 'views' ),
				'options'		=> array(
					'rating'	=> __( 'Rating', 'cherry-rank' ),
					'likes'		=> __( 'Likes', 'cherry-rank' ),
					'dislikes'	=> __( 'Dislikes', 'cherry-rank' ),
					'views'		=> __( 'Views', 'cherry-rank' ),
				),
			);

			return $options;
		}

		/**
		 * Add single page options
		 *
		 * @since  1.0.0
		 * @param  array $options default options array.
		 * @return array
		 */
		public function add_single_options( $options ) {
			$options['rank_add_single_meta'] = array(
				'type'			=> 'checkbox',
				'title'			=> __( 'Additional post meta', 'cherry-rank' ),
				'description'	=> __( 'Enable/disable additional post meta.', 'cherry-rank' ),
				'class'			=> '',
				'value'			=> array( 'rating', 'likes', 'dislikes', 'views' ),
				'options'		=> array(
					'rating'	=> __( 'Rating', 'cherry-rank' ),
					'likes'		=> __( 'Likes', 'cherry-rank' ),
					'dislikes'	=> __( 'Dislikes', 'cherry-rank' ),
					'views'		=> __( 'Views', 'cherry-rank' ),
				),
			);

			return $options;
		}

		/**
		 * Get option by name from theme options
		 *
		 * @since  1.0.0
		 *
		 * @uses   cherry_get_option  use cherry_get_option from Cherry framework if exist
		 *
		 * @param  string $name option name to get.
		 * @param  mixed  $default default option value.
		 * @return mixed
		 */
		public static function get_option( $name, $default = false ) {

			if ( function_exists( 'cherry_get_option' ) ) {
				$result = cherry_get_option( $name, $default );
				return $result;
			}

			return $default;

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

	Cherry_Rank_Options::get_instance();

}

<?php
/**
 * Init macros callbacks for additional meta
 *
 * @package    Cherry_Ratings
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


// If class 'Cherry_Callback_Views' not exists.
if ( ! class_exists( 'Cherry_Callback_Views' ) ) {

	/**
	 * Add rating system and callback
	 *
	 * @since 1.0.0
	 */
	class Cherry_Callback_Views {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		/**
		 * Meta field name for rating storing
		 *
		 * @since 1.0.0
		 * @var   string
		 */
		public $meta_key = 'cherry_views';

		/**
		 * Sinle page meta visibility
		 *
		 * @since 1.0.0
		 * @var   array
		 */
		public $show_single = array();

		/**
		 * Loop page meta visibility
		 *
		 * @since 1.0.0
		 * @var   array
		 */
		public $show_loop = array();

		/**
		 * Constructor for the class
		 */
		function __construct() {

			add_filter( 'cherry_pre_get_the_post_views', array( $this, 'macros_callback' ), 10, 2 );
			add_filter( 'cherry_shortcodes_data_callbacks', array( $this, 'register_views_macros' ), 10, 2 );
			add_action( 'wp_head', array( $this, 'save_views' ) );

			$this->show_single = Cherry_Rank_Options::get_option(
				'ratings_add_single_meta', array( 'rating', 'likes', 'dislikes', 'views' )
			);

			$this->show_loop = Cherry_Rank_Options::get_option(
				'ratings_add_blog_meta', array( 'rating', 'likes', 'dislikes', 'views' )
			);

			if ( ! session_id() ) {
				session_start();
			}

		}

		/**
		 * Register callback for views macros to process it in shortcodes
		 *
		 * @since  1.0.2
		 * @param  array $data existing callbacks.
		 * @param  array $atts shortcode attributes.
		 * @return array
		 */
		public function register_views_macros( $data, $atts ) {
			$data['views'] = array( $this, 'shortcode_macros_callback' );
			return $data;
		}

		/**
		 * Init macros callbacks
		 *
		 * @since  1.0.0
		 * @return string
		 */
		public function macros_callback( $pre, $attr ) {

			global $post;

			if ( ! empty( $attr['where'] ) ) {
				// if need to show on loop, but now is single page
				if ( ( ( 'loop' === $attr['where'] ) && is_singular() ) ) {
					return '';
				}

				// if need to show on single, but now is loop page
				if ( ( 'single' === $attr['where'] ) && ! is_singular() ) {
					return '';
				}
			}

			return $this->get_views();

		}

		/**
		 * Callback for shortcode macros
		 *
		 * @since  1.0.2
		 * @return string
		 */
		public function shortcode_macros_callback() {

			global $post;
			$result = $this->get_views_html( $post->ID );
			return '<div class="meta-rank-views">' . $result . '</div>';

		}

		/**
		 * Get clean views output
		 *
		 * @since  1.0.0
		 * @return string
		 */
		public function get_views() {

			global $post;

			if ( ! in_array( 'views', $this->show_single ) && is_singular() ) {
				return '';
			}

			if ( ! in_array( 'views', $this->show_loop ) && ! is_singular() ) {
				return '';
			}

			$result = $this->get_views_html( $post->ID );

			return '<div class="meta-rank-views">' . $result . '</div>';

		}

		/**
		 * Get post Rating HTML
		 *
		 * @since  1.0.0
		 * @since  1.0.2 pass additional parameters to cherry_meta_views_format
		 * @return string
		 */
		public function get_views_html( $post_id ) {

			/**
			 * Fires this action to enqueue rank assets
			 */
			do_action( 'cherry_rank_enqueue_assets' );

			$views = get_post_meta( $post_id, $this->meta_key, true );
			$views = absint( $views );

			$format = apply_filters(
				'cherry_meta_views_format',
				'<span class="meta-rank-views-count">%s</span>',
				$views, $post_id
			);

			return sprintf(
				$format, $views
			);

		}

		/**
		 * Handle view
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function save_views() {

			global $post;

			if ( ! is_object( $post ) ) {
				return;
			}

			if ( isset( $_SESSION['cherry-views'] ) && isset( $_SESSION['cherry-views'][ $post->ID ] ) ) {
				return;
			}

			if ( ! isset( $_SESSION['cherry-views'] ) ) {
				$_SESSION['cherry-views'] = array();
			}

			$_SESSION['cherry-views'][ $post->ID ] = $post->ID;
			$views = get_post_meta( $post->ID, $this->meta_key, true );
			$views = absint( $views );
			$views++;
			update_post_meta( $post->ID, $this->meta_key, $views );

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

	Cherry_Callback_Views::get_instance();

}

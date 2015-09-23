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


// If class 'Cherry_Callback_Likes' not exists.
if ( ! class_exists( 'Cherry_Callback_Likes' ) ) {

	/**
	 * Add rating system and callback
	 *
	 * @since 1.0.0
	 */
	class Cherry_Callback_Likes {

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
		public $meta_key = 'cherry_likes';

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
		 * Contructor for the class
		 */
		function __construct() {

			add_filter( 'cherry_pre_get_the_post_likes', array( $this, 'macros_callback' ), 10, 2 );
			add_filter( 'cherry_shortcodes_data_callbacks', array( $this, 'register_likes_macros' ), 10, 2 );
			add_action( 'wp_ajax_cherry_handle_like', array( $this, 'ajax_handle' ) );
			add_action( 'wp_ajax_nopriv_cherry_handle_like', array( $this, 'ajax_handle' ) );

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
		 * Register callback for likes macros to process it in shortcodes
		 *
		 * @since  1.0.2
		 * @param  array $data existing callbacks.
		 * @param  array $atts shortcode attributes.
		 * @return array
		 */
		public function register_likes_macros( $data, $atts ) {
			$data['likes'] = array( $this, 'shortcode_macros_callback' );
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

			return $this->get_likes();

		}

		/**
		 * Callback for shortcode macros
		 *
		 * @since  1.0.2
		 * @return string
		 */
		public function shortcode_macros_callback() {

			global $post;

			$result = $this->get_likes_html( $post->ID );
			return '<div class="meta-rank-likes" id="like-' . $post->ID . '">' . $result . '</div>';
		}

		/**
		 * Get clean likes output
		 *
		 * @since  1.0.0
		 * @return string
		 */
		public function get_likes() {

			global $post;

			if ( ! in_array( 'likes', $this->show_single ) && is_singular() ) {
				return '';
			}

			if ( ! in_array( 'likes', $this->show_loop ) && ! is_singular() ) {
				return '';
			}

			$result = $this->get_likes_html( $post->ID );

			return '<div class="meta-rank-likes" id="like-' . $post->ID . '">' . $result . '</div>';
		}

		/**
		 * Get post Rating HTML
		 *
		 * @since  1.0.0
		 * @since  1.0.2 pass additional parameters to cherry_meta_views_format
		 * @return string
		 */
		public function get_likes_html( $post_id ) {

			/**
			 * Fires this action to enqueue rank assets
			 */
			do_action( 'cherry_rank_enqueue_assets' );

			$likes_count = get_post_meta( $post_id, $this->meta_key, true );
			$likes_count = absint( $likes_count );

			$liked = '';
			if ( isset( $_SESSION['cherry-likes'] )
				&& is_array( $_SESSION['cherry-likes'] )
				&& in_array( $post_id, $_SESSION['cherry-likes'] )
			) {
				$liked = 'action-done';
			}

			$format = apply_filters(
				'cherry_meta_likes_format',
				'<a href="#" class="meta-rank-like-this %3$s" data-post="%2$s">%1$s</a>',
				$likes_count, $post_id, $liked
			);

			return sprintf(
				$format, $likes_count, $post_id, $liked
			);

		}

		/**
		 * Ajax handler for rating processing
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function ajax_handle() {

			if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'cherry_rank' ) ) {
				die();
			}

			$post_id = ( ! empty( $_REQUEST['post'] ) ) ? absint( $_REQUEST['post'] ) : false;
			$liked   = ( ! empty( $_REQUEST['done'] ) && 'true' == $_REQUEST['done'] ) ? true : false;

			if ( ! $post_id ) {
				die();
			}

			if ( ! isset( $_SESSION['cherry-likes'] ) ) {
				$_SESSION['cherry-likes'] = array();
			}

			$likes_count = get_post_meta( $post_id, $this->meta_key, true );
			$likes_count = absint( $likes_count );

			if ( false == $liked ) {
				$_SESSION['cherry-likes'][ $post_id ] = $post_id;
				$this->maybe_remove_dislike( $post_id );
				$likes_count++;
			} else {
				unset( $_SESSION['cherry-likes'][ $post_id ] );
				$likes_count = $likes_count - 1;
			}

			if ( $likes_count < 0 ) {
				$likes_count = 0;
			}

			update_post_meta( $post_id, $this->meta_key, $likes_count );

			echo $likes_count;

			do_action( 'cherry_likes_ajax_handle', $post_id );

			die();

		}

		/**
		 * Check if user already disliked this post and remove dislike
		 *
		 * @since  1.0.0
		 * @param  int $post_id post ID to check.
		 */
		public function maybe_remove_dislike( $post_id ) {

			if ( ! isset( $_SESSION['cherry-dislikes'] ) ) {
				return;
			}

			if ( ! isset( $_SESSION['cherry-dislikes'][ $post_id ] ) ) {
				return;
			}

			unset( $_SESSION['cherry-dislikes'][ $post_id ] );

			$dislikes = get_post_meta( $post_id, 'cherry_dislikes', true );
			$dislikes = $dislikes - 1;
			if ( $dislikes < 0 ) {
				$dislikes = 0;
			}

			update_post_meta( $post_id, 'cherry_dislikes', $dislikes );
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

	Cherry_Callback_Likes::get_instance();

}

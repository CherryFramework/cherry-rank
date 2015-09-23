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


// If class 'Cherry_Callback_Rating' not exists.
if ( ! class_exists( 'Cherry_Callback_Rating' ) ) {

	/**
	 * Add rating system and callback
	 *
	 * @since 1.0.0
	 */
	class Cherry_Callback_Rating {

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
		public $meta_key = 'cherry_rating';

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
		 * Constructor for the classs
		 */
		function __construct() {

			add_filter( 'cherry_pre_get_the_post_rating', array( $this, 'macros_callback' ), 10, 2 );
			add_filter( 'cherry_shortcodes_data_callbacks', array( $this, 'register_rating_macros' ), 10, 2 );
			add_action( 'wp_ajax_cherry_handle_rating', array( $this, 'ajax_handle' ) );
			add_action( 'wp_ajax_nopriv_cherry_handle_rating', array( $this, 'ajax_handle' ) );

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
		public function register_rating_macros( $data, $atts ) {
			$data['rating'] = array( $this, 'shortcode_macros_callback' );
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

			return $this->get_rating();

		}

		/**
		 * Callback for shortcode macros
		 *
		 * @since  1.0.2
		 * @return string
		 */
		public function shortcode_macros_callback() {

			global $post;

			$result = $this->get_rating_html( $post->ID );
			return '<div class="meta-rank-rating" id="rating-' . $post->ID . '">' . $result . '</div>';
		}

		/**
		 * Get clean rating output
		 *
		 * @since  1.0.0
		 * @return string
		 */
		public function get_rating() {

			global $post;

			if ( ! in_array( 'rating', $this->show_single ) && is_singular() ) {
				return '';
			}

			if ( ! in_array( 'rating', $this->show_loop ) && ! is_singular() ) {
				return '';
			}

			$result = $this->get_rating_html( $post->ID );

			return '<div class="meta-rank-rating" id="rating-' . $post->ID . '">' . $result . '</div>';

		}

		/**
		 * Get post Rating HTML
		 *
		 * @since  1.0.0
		 * @since  1.0.2 pass additional parameters to cherry_meta_views_format
		 * @return string
		 */
		public function get_rating_html( $post_id ) {

			/**
			 * Fires this action to enqueue rank assets
			 */
			do_action( 'cherry_rank_enqueue_assets' );

			$rating_meta = get_post_meta( $post_id, $this->meta_key, true );

			if ( ! $rating_meta ) {
				$rating_meta = array();
			}

			$rating_meta = wp_parse_args( $rating_meta, array(
				'rate'  => 0,
				'total' => 5,
				'votes' => 0,
			) );

			$votes  = '<span class="rating-votes">' . $rating_meta['votes'] . '</span>';
			$rating = '<span class="rating-val">' . $rating_meta['rate'] . '</span>';

			$star_rating = $this->get_stars( $rating_meta['rate'], $rating_meta['total'], $post_id );

			$format = apply_filters(
				'cherry_meta_rating_format',
				__( '%1$s (%2$s votes. Average %3$s of %4$s)', 'cherry-rank' ),
				$star_rating, $votes, $rating, $rating_meta['total'], $post_id
			);

			return sprintf(
				$format, $star_rating, $votes, $rating, $rating_meta['total']
			);

		}

		/**
		 * Get stars markup for star rating
		 *
		 * @since  1.0.0
		 * @param  float   $current current rating value.
		 * @param  integer $total   total rating steps count.
		 * @return string
		 */
		public function get_stars( $current = 0, $total = 5, $post_id = false ) {

			$star  = '<span class="star-rating_item%2$s%3$s" data-rate="%1$s">%1$s</span>';
			$stars = '';

			$current = floatval( $current );

			for ( $i = $total; $i >= 1; $i-- ) {

				$active  = '';
				$is_half = '';

				if ( ( $i + 0.2 ) >= $current && $current >= $i ) {
					$active = ' active';
				}

				if ( ( $i - 0.2 ) >= $current && ( $i - 0.8 ) < $current ) {
					$active  = ' active';
					$is_half = ' is-half';
				}

				if ( $i > $current && ( $i - 0.2 ) < $current ) {
					$active  = ' active';
				}

				$stars .= sprintf( $star, $i, $active, $is_half );
			}

			$disabled = '';

			if ( isset( $_SESSION['cherry-rates'] ) && in_array( $post_id, $_SESSION['cherry-rates'] ) ) {
				$disabled = ' rate-disabled';
			}

			return sprintf(
				'<div class="star-rating%3$s" data-post="%2$s">%1$s</div>',
				$stars, $post_id, $disabled
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

			$rate    = ( ! empty( $_REQUEST['rate'] ) ) ? absint( $_REQUEST['rate'] ) : 0;
			$post_id = ( ! empty( $_REQUEST['post'] ) ) ? absint( $_REQUEST['post'] ) : false;

			if ( ! $post_id ) {
				die();
			}

			if ( isset( $_SESSION['cherry-rates'] ) && in_array( $post_id, $_SESSION['cherry-rates'] ) ) {
				die();
			}

			if ( ! isset( $_SESSION['cherry-rates'] ) ) {
				$_SESSION['cherry-rates'] = array();
			}

			array_push( $_SESSION['cherry-rates'], $post_id );

			$current_rate = get_post_meta( $post_id, $this->meta_key, true );

			if ( ! $current_rate ) {
				$current_rate = array();
			}

			$current_rate = wp_parse_args( $current_rate, array(
				'rate'  => 0,
				'total' => 5,
				'votes' => 0,
			) );

			$votes      = intval( $current_rate['votes'] );
			$curr_count = floatval( $current_rate['rate'] );
			$total      = intval( $current_rate['total'] );

			$new_count = ( ceil( $curr_count * $votes ) + $rate ) / ( $votes + 1 );

			$new_rate = array(
				'rate'  => round( $new_count , 2 ),
				'total' => 5,
				'votes' => $votes + 1,
			);

			update_post_meta( $post_id, $this->meta_key, $new_rate );

			echo $this->get_rating_html( $post_id );

			do_action( 'cherry_rating_ajax_handle', $post_id );

			die();

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

	Cherry_Callback_Rating::get_instance();

}

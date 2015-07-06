/**
 * Process likes/dislikes plugin
 */
(function( $ ) {
	$.fn.CherryLikes = function( options ) {

		var settings = {
			action: 'like',
			done_class: 'action-done'
		}

		if ( options ) {
			$.extend( settings, options );
		}

		function maybe_fix_alt( post, action ) {

			var $alt_item = $( '#' + action + '-' + post + ' a' );

			if ( ! $alt_item.length ) {
				return !1;
			}

			if ( !$alt_item.hasClass(settings.done_class) ) {
				return !1;
			}

			var val = parseInt( $alt_item.text() );

			if ( val == 0 ) {
				return !1;
			}

			val = val - 1;

			$alt_item.removeClass(settings.done_class).text( val );

		}

		this.bind( 'click', function(event) {

			event.preventDefault();

			var $item      = $(this),
				post       = $item.data( 'post' ),
				done       = $item.hasClass( settings.done_class ),
				alt_action = 'dislike';

			if ( settings.action == 'dislike' ) {
				alt_action = 'like';
			}

			if ( $item.hasClass( 'processing' ) ) {
				return !1;
			}

			$item.addClass('processing');

			$.ajax({
				url: cherry_rank.ajaxurl,
				type: "post",
				dataType: "html",
				data: {
					action: 'cherry_handle_' + settings.action,
					post: post,
					done: done,
					nonce: cherry_rank.nonce
				}
			}).done(function(response) {
				$item.removeClass('processing').html(response);
				if ( done == false ) {
					$item.addClass(settings.done_class);
					maybe_fix_alt( post, alt_action );
				} else {
					$item.removeClass(settings.done_class);
				}
			});
		});

		return this;

	};
})(jQuery);

jQuery(document).ready(function($) {

	/**
	 * Process rating
	 */
	$(document).on('click', '.star-rating_item', function(event) {

		event.preventDefault();

		var $item = $(this),
			$container = $item.parent(),
			rate = $item.data( 'rate' ),
			post = $container.data( 'post' );

		if ( $container.hasClass( 'processing' ) || $container.hasClass( 'rate-disabled' ) ) {
			return !1;
		}

		$container.addClass('processing');

		$.ajax({
			url: cherry_rank.ajaxurl,
			type: "post",
			dataType: "html",
			data: {
				action: 'cherry_handle_rating',
				rate: rate,
				post: post,
				nonce: cherry_rank.nonce
			}
		}).done(function(response) {
			$container.removeClass('processing');
			$container.parent().html(response);
		});
	});

	/**
	 * Process like/dislike
	 */
	$('.meta-rank-like-this').CherryLikes({action:'like'});
	$('.meta-rank-dislike-this').CherryLikes({action:'dislike'});

})
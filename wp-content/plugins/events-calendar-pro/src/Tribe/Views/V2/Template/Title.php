<?php
/**
 * Handles the manipulation of the template title to correctly render it in the context of a PRO Views v2 request.
 *
 * @since   4.7.9
 *
 * @package Tribe\Events\Pro\Views\V2\Template
 */

namespace Tribe\Events\Pro\Views\V2\Template;

use Tribe\Events\Pro\Views\V2\Views\Map_View;
use Tribe\Events\Pro\Views\V2\Views\Photo_View;
use Tribe\Events\Pro\Views\V2\Views\Summary_View;
use Tribe\Events\Views\V2\Manager;
use Tribe\Events\Views\V2\Template\Title as TEC_Title;
use Tribe__Context as Context;

/**
 * Class Title
 *
 * @since   4.7.9
 *
 * @package Tribe\Events\Pro\Views\V2\Template
 */
class Title extends TEC_Title {

	/**
	 * Builds the PRO View title based on context.
	 *
	 * @since 4.7.9
	 * @since 5.1.4 - Add filter for plural events label.
	 *

	 * @param string      $title     The input title.
	 * @param  \WP_Term   $cat       The category term to use to build the title.
	 * @param boolean     $depth     Whether to display the taxonomy hierarchy as part of the title.
	 * @param null|string $separator The separator sequence to separate the title components.
	 *
	 * @return string The View title, or an empty string if the rendering View is not a PRO one.
	 */
	public function build_title( $title = '', $depth = true, $separator = ' &#8250; ' ) {
		$context = $this->context ?: tribe_context();
		$posts   = [];
		$title   = '';

		/**
		 * Filter the plural Events label for PRO Views Title.
		 *
		 * @since 5.1.4
		 *
		 * @param string  $events_label_plural The plural events label as it's been generated thus far.
		 * @param Context $context             The context used to build the title, it could be the global one, or one externally
		 *                                     set.
		 */
		$this->events_label_plural = apply_filters( 'tribe_events_pro_filter_views_v2_wp_title_plural_events_label', $this->events_label_plural, $context );

		if ( 'all' === $context->get( 'event_display_mode' ) ) {
			$title = sprintf(
				__( 'All %1$s for %2$s', 'tribe-events-calendar-pro' ),
				tribe_get_event_label_plural_lowercase(),
				get_the_title( $context->get( 'post_id' ) )
			);
		} elseif ( 'week' === $context->get( 'event_display' ) ) {
			/**
			 * Filters the date format that should be used to render PRO views title.
			 *
			 * @since 4.7.9
			 *
			 * @param string $date_format The date format, as read from the options.
			 */
			$date_format = apply_filters(
				'tribe_events_pro_page_title_date_format',
				tribe_get_date_format( true )
			);

			$title = sprintf(
				__( '%1$s for week of %2$s', 'tribe-events-calendar-pro' ),
				$this->events_label_plural,
				date_i18n( $date_format, strtotime( tribe_get_first_week_day( $context->get( 'event_date' ) ) ) )
			);
		} else {
			$posts = $this->get_posts();

			// Resolve our view slug.
			$view_slug = $context->get( 'event_display' );
			if ( $view_slug === null || $view_slug === 'default' ) {
				$manager   = tribe( Manager::class );
				$view_slug = $manager->get_default_view();
			}
			$range = TEC_Title::build_post_range_title( $context, $context->get( 'event_date', 'now' ), $posts );
			switch ( $view_slug ) {
				case Photo_View::get_view_slug():
					$title = sprintf(
						__( 'Display of %1$s from %2$s', 'tribe-events-calendar-pro' ),
						$this->events_label_plural,
						$range
					);
					break;
				case Summary_View::get_view_slug():
					$title = sprintf(
						__( 'Summary of %1$s from %2$s', 'tribe-events-calendar-pro' ),
						$this->events_label_plural,
						$range
					);
					break;
				case Map_View::get_view_slug():
					$title = sprintf(
						__( 'Map of %1$s from %2$s', 'tribe-events-calendar-pro' ),
						$this->events_label_plural,
						$range
					);
					break;
			}
		}

		/**
		 * Filters the view title, specific to PRO Views V2.
		 *
		 * @since 4.7.9
		 *
		 * @param string  $title   The "Events" page title as it's been generated thus far.
		 * @param bool    $depth   Whether to include the linked title or not.
		 * @param Context $context The context used to build the title, it could be the global one, or one externally
		 *                         set.
		 * @param array   $posts   An array of posts fetched by the View.
		 */
		return apply_filters( 'tribe_events_pro_views_v2_view_title', $title, $depth, $context, $posts );
	}
}

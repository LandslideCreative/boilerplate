<?php
/**
 * View: Week View - Event Tooltip Featured Image
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/v2/week/grid-body/events-day/event/tooltip/featured-image.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/1aiy
 *
 * @version 5.0.0
 *
 * @var WP_Post $event The event post object with properties added by the `tribe_get_event` function.
 *
 * @see tribe_get_event() For the format of the event object.
 *
 */

if ( ! $event->thumbnail->exists ) {
	return;
}

?>
<div class="tribe-events-pro-week-grid__event-tooltip-featured-image-wrapper">
	<a
		href="<?php echo esc_url( $event->permalink ); ?>"
		title="<?php echo esc_attr( get_the_title( $event ) ); ?>"
		rel="bookmark"
		class="tribe-events-pro-week-grid__event-tooltip-featured-image-link"
	>
		<img
			src="<?php echo esc_url( $event->thumbnail->full->url ); ?>"
			class="tribe-events-pro-week-grid__event-tooltip-featured-image"
			role="presentation"
			alt=""
			<?php if ( ! empty( $event->thumbnail->srcset ) ) : ?>
				srcset="<?php echo esc_attr( $event->thumbnail->srcset ); ?>"
			<?php endif; ?>
		/>
	</a>
</div>

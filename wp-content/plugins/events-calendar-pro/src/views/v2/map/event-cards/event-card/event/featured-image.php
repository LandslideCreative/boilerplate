<?php
/**
 * View: Map View - Single Event Featured Image
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/v2/map/event-cards/event-card/event/featured-image.php
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
 */

if ( ! $event->thumbnail->exists ) {
	return;
}
?>
<div class="tribe-events-pro-map__event-featured-image-wrapper tribe-common-g-col tribe-common-a11y-hidden">
	<img
		src="<?php echo esc_url( $event->thumbnail->full->url ); ?>"
		class="tribe-events-pro-map__event-featured-image"
		role="presentation"
		alt=""
		<?php if ( ! empty( $event->thumbnail->full->width ) ) : ?>
			width="<?php echo esc_attr( $event->thumbnail->full->width ); ?>"
		<?php endif; ?>
		<?php if ( ! empty( $event->thumbnail->full->height ) ) : ?>
			height="<?php echo esc_attr( $event->thumbnail->full->height ); ?>"
		<?php endif; ?>
		<?php if ( ! empty( $event->thumbnail->srcset ) ) : ?>
			srcset="<?php echo esc_attr( $event->thumbnail->srcset ); ?>"
		<?php endif; ?>
	/>
</div>

<?php
/**
 * View: Summary Date Group - Single Event Title Featured Icon
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/v2/summary/date-group/event/title/featured.php
 *
 * See more documentation about our views templating system.
 *
 * Note this view uses classes from the list view event datetime to leverage those styles.
 *
 * @link http://evnt.is/1aiy
 *
 * @since 5.7.0
 * @since 7.6.1 Added $icon_description parameter and updated the template to use it for the accessible label.
 *
 * @version 7.6.1
 *
 * @var WP_Post $event The event post object with properties added by the `tribe_get_event` function.
 * @var string $icon_description The description of the icon. Used for the accessible label. (optional)
 *
 * @see tribe_get_event() For the format of the event object.
 */

if ( empty( $event->featured ) ) {
	return;
}

if ( empty( $icon_description ) ) {
	$icon_description = __( 'Featured', 'tribe-events-calendar-pro' );
}
?>
<em class="tribe-events-pro-summary__event-title-featured-icon">
	<?php $this->template( 'components/icons/featured', [ 'classes' => [ 'tribe-events-pro-summary__event-title-featured-icon-svg' ] ] ); ?>
</em>
<span class="tribe-events-pro-summary__event-title-featured-text tribe-common-a11y-visual-hide">
	<?php echo esc_html( $icon_description ); ?>
</span>

<?php
/**
 * Block: Related Events
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/blocks/related-events.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1ajx
 *
 * @version 4.6.1
 *
 */
$events = $this->get( 'events', null );

if ( ! is_array( $events ) || empty( $events ) ) {
	return;
}

$default_classes = [ 'tribe-related-events', 'tribe-clearfix' ];

// Add the custom classes from the block attributes.
$classes = isset( $attributes['className'] ) ? array_merge( $default_classes, [ $attributes['className'] ] ) : $default_classes;
?>
<?php $this->template( 'blocks/related-events/title' ); ?>

<ul <?php tec_classes( $classes ); ?>>
	<?php foreach ( $events as $event ) : ?>
		<?php $this->template( 'blocks/related-events/event', array( 'event' => $event ) ); ?>
	<?php endforeach; ?>
</ul>

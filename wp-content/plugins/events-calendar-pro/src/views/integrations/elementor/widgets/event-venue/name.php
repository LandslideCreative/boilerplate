<?php
/**
 * View: Elementor Single Event Venue widget.
 *
 * You can override this template in your own theme by creating a file at
 * [your-theme]/tribe/events-pro/integrations/elementor/widgets/venue/single-venue.php
 *
 * @since 6.4.0
 *
 * @var bool   $show     Whether to show the venue name.
 * @var bool   $link     Whether to link the venue name.
 * @var string $venue_id The venue ID.
 * @var array  $settings The widget settings.
 * @var int    $event_id The event ID.
 * @var TEC\Events\Integrations\Elementor\Widgets\Event_Venue $widget The widget instance.
 */

if ( empty( $show_name ) ) {
	return;
}
?>
<<?php echo tag_escape( $name_tag ); ?> <?php tec_classes( $widget->get_name_base_class() ); ?>>
	<?php if ( ! empty( $link_venue_name ) ) : ?>
		<a <?php tec_classes( $widget->get_name_base_class() . '-link' ); ?> href="<?php echo esc_url( get_permalink( $venue['id'] ) ); ?>">
	<?php endif; ?>
	<?php echo wp_kses_post( $venue['name'] ); ?>
	<?php if ( ! empty( $link_venue_name ) ) : ?>
		</a>
	<?php endif; ?>
<?php echo '</' . tag_escape( $name_tag ) . '>'; ?>

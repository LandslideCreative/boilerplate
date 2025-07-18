<?php
/**
 * View: Map View
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/v2/map.php
 *
 * See more documentation about our views templating system.
 *
 * @link     https://evnt.is/1aiy
 *
 * @since    6.1.2 Changing our nonce verification structures.
 *
 * @version 6.2.0
 * @since 6.2.0 Moved header into the components/header.php template.
 *
 * @var  string  $rest_url             The REST URL.
 * @var  string  $rest_method          The HTTP method, either `POST` or `GET`, the View will use to make requests.
 * @var  int     $should_manage_url    int containing if it should manage the URL.
 * @var  array   $events               An array of the events, in sequence.
 * @var  string  $today_url            URL pointing to the today link for this view.
 * @var  string  $prev_url             URL pointing to the prev page link for this view.
 * @var  string  $next_url             URL pointing to the next page link for this view.
 * @var  array   $providers            Array with all the possible map providers available to the view.
 * @var  bool    $disable_event_search Boolean on whether to disable the event search.
 * @var string[] $container_classes    Classes used for the container of the view.
 * @var array    $container_data       An additional set of container `data` attributes.
 * @var string   $breakpoint_pointer   String we use as pointer to the current view we are setting up with breakpoints.
 */

?>
<div
	<?php tec_classes( $container_classes ); ?>
	data-js="tribe-events-view"
	data-view-rest-url="<?php echo esc_url( $rest_url ); ?>"
	data-view-rest-method="<?php echo esc_attr( $rest_method ); ?>"
	data-view-manage-url="<?php echo esc_attr( $should_manage_url ); ?>"
	<?php foreach ( $container_data as $key => $value ) : ?>
		data-view-<?php echo esc_attr( $key ) ?>="<?php echo esc_attr( $value ) ?>"
	<?php endforeach; ?>
	<?php if ( ! empty( $breakpoint_pointer ) ) : ?>
		data-view-breakpoint-pointer="<?php echo esc_attr( $breakpoint_pointer ); ?>"
	<?php endif; ?>
>
	<section class="tribe-common-l-container tribe-events-l-container">
		<?php $this->template( 'components/loader', [ 'text' => __( 'Loading...', 'tribe-events-calendar-pro' ) ] ); ?>

		<?php $this->template( 'components/json-ld-data' ); ?>

		<?php $this->template( 'components/data' ); ?>

		<?php $this->template( 'components/before' ); ?>

		<?php $this->template( 'components/header' ); ?>

		<?php $this->template( 'components/filter-bar' ); ?>

		<?php if ( ! empty( $events ) ) : ?>
			<div class="tribe-common-g-row tribe-events-pro-map">
				<?php $this->template( 'map/map' ); ?>
				<?php $this->template( 'map/event-cards' ); ?>
			</div>
		<?php endif; ?>

		<?php $this->template( 'components/ical-link' ); ?>

		<?php $this->template( 'components/after' ); ?>
	</section>
</div>

<?php $this->template( 'components/breakpoints' ); ?>

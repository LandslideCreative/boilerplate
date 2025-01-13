<?php
/**
 * Single Event Template
 * A single event. This displays the event title, description, meta, and
 * optionally, the Google map for the event.
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/single-event.php
 *
 * @package TribeEventsCalendar
 * @version 4.6.19
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$start_date = tribe_get_start_date(NULL, false, 'Ymd G:i:s');
$end_date = tribe_get_end_date(NULL, false, 'Ymd G:i:s'); ?>

<section class="page-section white-bg single-event">
	<div class="grid-container">
		<div class="grid-x grid-padding-x align-center">
			<div class="medium-10 large-8 cell">

				<h1 class="single-event-title"><?php the_title(); ?></h1>

				<?php if ( has_post_thumbnail()) : ?>
					<div class="single-event-image">
						<?php the_post_thumbnail('large'); ?>
					</div>
				<?php endif; ?>

				<p class="single-event-date"><?php echo ls_get_list_dates($start_date, $end_date); ?></p>

				<?php if( !tribe_event_is_all_day() ) { ?>
					<p class="single-event-time"><?php echo ls_get_times($start_date, $end_date); ?></p>
				<?php } ?>

				<?php if ( tribe_get_cost() ) : ?>
					<p class="single-event-cost"><?php echo tribe_get_cost( null, true ) ?></p>
				<?php endif; ?>

				<?php if( tribe_get_venue()!='' ) { ?>
					<div class="single-event-location">
						<p class="single-event-venue"><?php echo tribe_get_venue() ?></h3>
						<?php if ( tribe_address_exists() ) : ?>
							<p class="single-event-address">
								<?php echo tribe_get_full_address(); ?>
							</p>
						<?php endif; ?>
					</div>
				<?php } ?>

				<?php if( tribe_embed_google_map() ) { ?>
					<div class="single-event-map">
						<?php tribe_get_template_part( 'modules/meta/map' ); ?>
					</div>
				<?php } ?>

				<?php $organizer_ids = tribe_get_organizer_ids();
				if( $organizer_ids ) {
					foreach( $organizer_ids as $organizer ) { ?>
						<p class="single-event-contact"><strong>Contact:</strong> <a href="mailto:<?php echo tribe_get_organizer_email($organizer); ?>"><?php echo tribe_get_organizer($organizer); ?></a></p>
					<?php }
				} ?>

				<div class="single-event-description">
					<?php the_content(); ?>
				</div>

			</div>
		</div>
	</div>
</section>
<?php // Event Filter

$filters = array();
$filters['tribe_events_cat'] = get_query_var('tribe_events_cat', ''); ?>

<div class="grid-container intro-section filter-container event-filter-container">
	<div class="grid-x grid-padding-x">
		<div class="cell">
			<form class="filter-form" id="event-filter" method="GET" action="<?php echo get_post_type_archive_link('tribe_events'); ?>#event-list">

				<?php /* Category */ ?>
				<label class="event-filter-topic-label visually-hidden" for="event-category">Category</label>
				<div class="select-container">
					<select name="tribe_events_cat" id="event-category">
						<option value="">
							All Categories
						</option>						
						<?php $terms = get_terms( array(
							'taxonomy'	=> 'tribe_events_cat',
							'parent' => 0
						));
						foreach( $terms as $term ) { ?>
							<option value="<?php echo $term->slug; ?>" <?php if($term->slug==$filters['tribe_events_cat']) { echo 'selected'; } ?>>
								<?php echo $term->name; ?>
							</option>
						<?php }	?>
					</select>
				</div>

				<input type="submit" class="button hollow" value="Filter Events">

				<?php if( array_filter($filters) ) { ?>
					<a href="<?php echo get_post_type_archive_link('tribe_events'); ?>#event-list" class="clear-filters">Clear Filters</a>
				<?php } ?>

			</form>
		</div>
	</div>
</div>
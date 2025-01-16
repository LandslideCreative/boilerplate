<?php // Event Filter

// Get current filter(s)
$filters = array();
$filters['event-category'] = get_query_var('tribe_events_cat', ''); ?>

<div class="grid-container intro-section filter-container event-filter-container">
	<div class="grid-x grid-padding-x">
		<div class="cell">
			<p class="filter-label">Filter Events</p>
			<form class="filter-form" id="event-filter">
				<div class="select-container">
					<select name="event-category">
						<option value="<?php echo get_post_type_archive_link('tribe_events'); ?>#event-list">
							All Categories
						</option>
						
						<?php $terms = get_terms( array(
							'taxonomy'	=> 'tribe_events_cat',
							'parent' => 0
						));
						foreach( $terms as $term ) { ?>
							<option value="<?php echo get_term_link($term); ?>#event-list" <?php if($term->slug==$filters['event-category']) { echo 'selected'; } ?>>
								<?php echo $term->name; ?>
							</option>
						<?php }	?>
					</select>
				</div>

				<input type="submit" class="button hollow" value="Go">
			</form>
		</div>
	</div>
</div>
<?php // Event Filter

// Get current filter
$current_slug = '';
if( is_tax('tribe_events_cat') ) {
	$current_term = get_queried_object(); 
	$current_slug = $current_term->slug;
} ?>

<div class="grid-container intro-section event-filter-container">
	<div class="grid-x grid-padding-x">
		<div class="cell">
			<p class="event-filter-label">Filter Events</p>
			<form id="event-filter">
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
							<option value="<?php echo get_term_link($term); ?>#event-list" <?php if($term->slug==$current_slug) { echo 'selected'; } ?>>
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
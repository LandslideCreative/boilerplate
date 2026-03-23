<?php // Sermon Filter

$filters = array();
$filters['topic'] = get_query_var('topic', '');
$filters['speaker'] = get_query_var('speaker', ''); ?>

<div class="grid-container intro-section filter-container sermon-filter-container">
	<div class="grid-x <?php ls_grid_class( 'sermon-filter' ); ?>">
		<div class="cell <?php ls_cell_class( 'sermon-filter' ); ?>">
			<form class="filter-form" id="sermon-filter" method="GET" action="<?php echo get_post_type_archive_link('sermon'); ?>#sermon-list">

				<?php /* Topic */ ?>
				<label class="sermon-filter-topic-label visually-hidden" for="topic">Topic</label>
				<div class="select-container">
					<select name="topic" id="topic">
						<option value="">
							All Topics
						</option>
						<?php 
						$terms = get_terms( array(
							'taxonomy'	=> 'topic',
							'parent' => 0
						));
						foreach( $terms as $term ) { ?>
							<option value="<?php echo $term->slug; ?>" <?php if($term->slug==$filters['topic']) { echo 'selected'; } ?>>
								<?php echo $term->name; ?>
							</option>
						<?php }	?>
					</select>
				</div>

				<?php /* Speaker */ ?>
				<label class="sermon-filter-speaker-label visually-hidden" for="speaker">Speaker</label>
				<div class="select-container">
					<select name="speaker" id="speaker">
						<option value="">
							All Speakers
						</option>
						<?php 
						$terms = get_terms( array(
							'taxonomy'	=> 'speaker',
							'parent' => 0
						));
						foreach( $terms as $term ) { ?>
							<option value="<?php echo $term->slug; ?>" <?php if($term->slug==$filters['speaker']) { echo 'selected'; } ?>>
								<?php echo $term->name; ?>
							</option>
						<?php }	?>
					</select>
				</div>

				<?php /* Book of the Bible */ ?>
				<label class="sermon-filter-bible-label visually-hidden" for="bible">Book of the Bible</label>
				<div class="select-container">
					<select name="bible" id="bible">
						<option value="">
							All Books of the Bible
						</option>
						<?php 
						$terms = get_terms( array(
							'taxonomy'	=> 'bible',
							'parent' => 0
						));
						foreach( $terms as $term ) { ?>
							<option value="<?php echo $term->slug; ?>" <?php if($term->slug==$filters['bible']) { echo 'selected'; } ?>>
								<?php echo $term->name; ?>
							</option>
						<?php }	?>
					</select>
				</div>

				<input type="submit" class="button hollow" value="Filter Sermons">

				<?php if( array_filter($filters) ) { ?>
					<a href="<?php echo get_post_type_archive_link('sermon'); ?>#sermon-list" class="clear-filters">Clear Filters</a>
				<?php } ?>
				
			</form>
		</div>
	</div>
</div>
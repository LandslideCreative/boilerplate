<?php // Sermon Filter

$filters = array();

$filters['topic'] = get_query_var('topic', '');
$filters['speaker'] = get_query_var('speaker', '');
$filters['bible'] = get_query_var('bible', ''); ?>

<div class="grid-container intro-section filter-container sermon-filter-container">
	<div class="grid-x grid-padding-x">
		<div class="cell">
			<p class="filter-label sermon-filter-label">Filter Sermons</p>
			<form id="filter sermon-filter" method="GET">
				<div class="select-container">
					<select name="topic" aria-label="Topic">
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

				<div class="select-container">
					<select name="speaker" aria-label="Speaker">
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

				<div class="select-container">
					<select name="bible" aria-label="Book of the Bible">
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

				<input type="submit" class="button hollow" value="Go">
			</form>
		</div>
	</div>
</div>
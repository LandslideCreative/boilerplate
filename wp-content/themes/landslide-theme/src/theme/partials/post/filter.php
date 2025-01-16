<?php // Post Filter

// Get current filter(s)
$filters = array();
$filters['category'] = get_query_var('category_name', ''); ?>

<div class="grid-container intro-section filter-container post-filter-container">
	<div class="grid-x grid-padding-x">
		<div class="cell">
			<p class="filter-label">Filter Posts</p>
			<form class="filter-form" id="post-filter" method="GET" action="<?php echo get_post_type_archive_link('post'); ?>">
				<div class="select-container">
					<select name="category_name">
						<option value="">
							All Categories
						</option>
						<?php 
						$terms = get_terms( array(
							'taxonomy'	=> 'category',
							'parent' => 0
						));
						foreach( $terms as $term ) { ?>
							<option value="<?php echo $term->slug; ?>" <?php if($term->slug==$filters['category']) { echo 'selected'; } ?>>
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
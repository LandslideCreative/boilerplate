<?php // Post Filter

// Get current filter
$current_slug = '';
if( isset($_GET['category_name']) && $_GET['category_name']!='' ) {
	$current_slug = $_GET['category_name'];
} ?>

<div class="grid-container intro-section post-filter-container">
	<div class="grid-x grid-padding-x">
		<div class="cell">
			<p class="post-filter-label">Filter Posts</p>
			<form id="post-filter" method="GET">
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
							<option value="<?php echo $term->slug; ?>" <?php if($term->slug==$current_slug) { echo 'selected'; } ?>>
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
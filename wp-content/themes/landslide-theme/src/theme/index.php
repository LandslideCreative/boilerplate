<?php 
if( isset($_GET['category_name']) && $_GET['category_name']!='' ) {
	$current_slug = $_GET['category_name'];
} else {
	$current_slug = '';
}

get_header(); ?>

<main role="main" id="main-content">
	<div class="page-section white-bg post-list">

		<div class="grid-container intro-section">
			<div class="grid-x grid-padding-x align-center">
				<div class="cell medium-10 large-8 cell">
					<p class="post-filter-label">Filter Posts</p>
					<form id="post-filter" method="GET" >
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

		<div class="grid-container">
			<div class="grid-x grid-padding-x align-center">
				<div class="medium-10 large-8 cell">
					<?php get_template_part('loop'); ?>
				</div>

				<?php
				// Pagination
				$paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
				$args = array(
					'current_page' => $paged,
					'max_pages' => $wp_query->max_num_pages
				);

				if( $args['max_pages'] > 1 ) { ?>
					<div class="medium-10 large-8 cell">
						<?php get_template_part('partials/pagination/list', '', $args); ?>
					</div>
				<?php } ?>

			</div>
		</div>
	</div>
</main>

<?php get_footer(); ?>

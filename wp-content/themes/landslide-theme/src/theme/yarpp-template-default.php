<?php
/*
YARPP Template: Default Template
Description: The default related posts template
Author: Landslide Creative
*/
?>

<?php if ( have_posts() ) : ?>

	<section class="page-section white-bg related-posts">

		<div class="grid-container intro-section">
			<div class="grid-x grid-padding-x">
				<div class="cell">
					<h2>Related Posts</h2>
				</div>
			</div>
		</div>

		<div class="grid-container">
			<div class="grid-x grid-padding-x align-center">
				<?php while ( have_posts() ) :
					the_post(); ?>
					<div class="medium-10 large-8 cell">
						<?php get_template_part('partials/posts/list', 'item'); ?>
					</div>
				<?php endwhile; ?>
			</div>
		</div>

	</section>

<?php endif; ?>

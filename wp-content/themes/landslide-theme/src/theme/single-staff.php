<?php get_header(); ?>

<main role="main" id="main-content">
	<?php if (have_posts()): while (have_posts()) : the_post(); ?>

		<section class="page-section white-bg single-staff">
			<div class="grid-container">
				<div class="grid-x grid-padding-x align-center">
					<div class="large-10 cell">

						<h1 class="single-staff-title"><?php the_title(); ?></h1>

						<?php if ( get_field('position') ) { ?>
							<h3 class="single-staff-image"><?php the_field('position'); ?></h3>
						<?php } ?>

						<?php if ( has_post_thumbnail()) : ?>
							<div class="single-staff-image">
								<?php the_post_thumbnail('square'); ?>
							</div>
						<?php endif; ?>

						<?php the_content(); ?>
						
					</div>
				</div>
			</div>
		</section>

	<?php endwhile; endif; ?>
</main>

<?php get_footer(); ?>

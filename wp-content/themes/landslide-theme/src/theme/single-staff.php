<?php // Single Staff
get_header(); ?>

<main role="main" id="main-content">
	<?php if (have_posts()): while (have_posts()) : the_post(); ?>

		<section class="page-section white-bg single-staff">
			<div class="grid-container">
				<div class="grid-x grid-padding-x">
					<div class="cell">

						<?php /* Name */ ?>
						<h5 class="label"><a href="<?php echo get_post_type_archive_link('staff'); ?>">Staff</a></h5>
						<h1 class="single-staff-title"><?php the_title(); ?></h1>

						<?php /* Position */ ?>
						<?php if ( get_field('position') ) { ?>
							<h3 class="single-staff-position"><?php the_field('position'); ?></h3>
						<?php } ?>

						<?php /* Image */ ?>
						<?php if ( has_post_thumbnail()) : ?>
							<div class="single-staff-image">
								<?php the_post_thumbnail('square'); ?>
							</div>
						<?php endif; ?>

						<?php /* Bio */ ?>
						<?php the_content(); ?>

						<a href="<?php echo get_post_type_archive_link('staff'); ?>">Back to All Staff</a>
						
					</div>
				</div>
			</div>
		</section>

	<?php endwhile; endif; ?>
</main>

<?php get_footer();
<?php /* Template Name: Sample Template */ get_header(); ?>

<main role="main" id="main-content">
	<?php if (have_posts()): while (have_posts()) : the_post(); ?>

		<?php get_template_part('partials/page', 'header'); ?>

		<?php if($post->post_content != '') { ?>
			<div class="page-section white-bg">
				<div class="grid-container">
					<div class="grid-x grid-padding-x align-center">
						<div class="large-10 cell">
							<?php the_content(); ?>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>

		<?php get_template_part('partials/page', 'builder'); ?>

	<?php endwhile; endif; ?>
</main>

<?php get_footer(); ?>

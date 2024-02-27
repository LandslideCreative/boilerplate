<?php get_header(); ?>

<main role="main" id="main-content">
	<?php if (have_posts()): while (have_posts()) : the_post(); ?>

		<?php get_template_part('partials/header/page'); ?>

		<?php get_template_part('partials/page', 'builder'); ?>

	<?php endwhile; endif; ?>
</main>

<?php get_footer(); ?>

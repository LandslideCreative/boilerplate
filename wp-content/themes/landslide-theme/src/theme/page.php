<?php // Page Template

get_header(); 

if (have_posts()): while (have_posts()) : the_post(); ?>

	<main role="main" id="main-content">

		<?php get_template_part('partials/header/page'); ?>

		<?php get_template_part('partials/page', 'builder'); ?>

	</main>

<?php endwhile; endif;

get_footer();
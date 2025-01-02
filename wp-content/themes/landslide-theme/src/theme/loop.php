<?php // Main Loop

if (have_posts()): while (have_posts()) : the_post();
	get_template_part('partials/post/item');
endwhile; endif;

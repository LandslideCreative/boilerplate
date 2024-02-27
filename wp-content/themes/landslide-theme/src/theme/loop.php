<?php // Post list item

if (have_posts()): while (have_posts()) : the_post();
	get_template_part('partials/posts/list', 'item');
endwhile;
else:
	get_template_part('partials/error', 'missing');
endif;

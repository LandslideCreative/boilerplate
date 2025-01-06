<?php // Sermon Archive

get_header();

$current_page = get_page_by_path('sermons');

$post = $current_page;
setup_postdata($post); 
if( $post ) { ?>

		<main role="main" id="main-content">

			<?php get_template_part('partials/header/page'); ?>

			<?php get_template_part('partials/page', 'builder'); ?>

		</main>
	
<?php } 

get_footer(); ?>
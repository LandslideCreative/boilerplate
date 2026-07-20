<?php // 404 Page

$page_slug = get_page_by_path('not-found');

get_header();

if( $page_slug ) {
	$post = $page_slug;
	setup_postdata($post); 
		if( $post ) { ?>
			<main role="main" id="main-content">

				<?php get_template_part('partials/header/page'); ?>

				<?php get_template_part('partials/page', 'builder'); ?>

			</main>
		<?php }
	wp_reset_postdata();

} else { ?>

	<main role="main" id="main-content">

		<?php get_template_part('partials/header/page'); ?>
		
		<div class="page-section white-bg">
			<div class="grid-container">
				<div class="grid-x grid-padding-x">
					<div class="large-10 cell">
						<h3>Sorry, there is no page at this URL. <a href="<?php echo home_url(); ?>">Click Here</a> to return to the homepage.</h3>
					</div>
				</div>
			</div>
		</div>

	</main>

<?php }

get_footer();
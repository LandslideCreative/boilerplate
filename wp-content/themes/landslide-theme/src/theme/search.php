<?php get_header(); ?>

<main role="main" id="main-content">
	<div class="page-section white-bg">
		<div class="grid-container">
			<div class="grid-x grid-x-padding align-center">
				<div class="medium-10 large-8 cell">
					<h1><?php echo sprintf( __( '%s Search Results for ', 'html5blank' ), $wp_query->found_posts ); echo get_search_query(); ?></h1>
					<?php get_template_part('loop'); ?>
				</div>
				<?php if( $wp_query->max_num_pages > 1 ) { ?>
					<div class="medium-10 large-8 cell">
						<?php get_template_part('pagination'); ?>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>
</main>

<?php get_footer(); ?>

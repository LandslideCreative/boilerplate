<?php // Author Archive
$author = get_queried_object();

get_header(); ?>

<main role="main" id="main-content">
	<div class="page-header">
		<div class="grid-container">
			<div class="grid-x grid-padding-x align-center vertical-center">
				<div class="cell auto">
					<h1>Posts by <?php echo $author->name; ?></h1>
				</div>
			</div>
		</div>
	</div>

	<?php if ( $wp_query->have_posts() ) { ?>
		<div class="page-section white-bg post-list" id="post-list">		
		
			<?php /* Post List */ ?>
			<div class="grid-container post-list-container">
				<div class="grid-x grid-padding-x">
					<div class="cell">
						<?php while ( $wp_query->have_posts() ) { 
							$wp_query->the_post();
							get_template_part('partials/post/item');
						} ?>
					</div>
				</div>
			</div>

			<?php /* Pagination */ ?>
			<?php $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
			$args = array(
				'current_page' => $paged,
				'max_pages' => $wp_query->max_num_pages
			);

			if( $args['max_pages'] > 1 ) { ?>
				<div class="grid-container post-list-pagination">
					<div class="grid-x grid-padding-x">
						<div class="cell">
							<?php get_template_part('partials/pagination/list', '', $args); ?>
						</div>
					</div>
				</div>
			<?php } ?>

		</div>
	<?php } ?>
</main>

<?php get_footer();
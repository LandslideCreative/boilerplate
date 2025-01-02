<?php // Author Archive

get_header(); ?>

<main role="main" id="main-content">

	<?php // Author header ?>
	<div class="page-section white-bg author-header">
		<div class="grid-container">
			<div class="grid-x grid-padding-x">
				<div class="cell">

					<h1>Posts by <?php echo get_the_author() ; ?></h2>
					
					<?php if( get_the_author_meta('description') ) {
						// Author Gravatar
						echo get_avatar(get_the_author_meta('user_email')); ?>

						<h2>About <?php echo get_the_author() ; ?></h2>

						<?php // Author Description
						echo wpautop( get_the_author_meta('description') );
					} ?>

				</div>
			</div>
		</div>
	</div>

	<?php if( have_posts() ) { ?>
		<div class="page-section white-bg post-list" id="post-list">

			<?php // Post List ?>
			<div class="grid-container post-list-container">
				<div class="grid-x grid-padding-x">
					<div class="cell">
						<?php get_template_part('loop'); ?>
					</div>
				</div>
			</div>


			<?php // Pagination
			$paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
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

<?php get_footer(); ?>

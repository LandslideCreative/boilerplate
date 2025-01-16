<?php // Search Results

get_header(); ?>

<main role="main" id="main-content">
	<div class="page-section white-bg search-results" id="search-results">

		<?php /* Header */ ?>
		<div class="grid-container intro-section">
			<div class="grid-x grid-padding-x">
				<div class="cell">
					<h1>Search Results for <em><?php echo get_search_query(); ?></em></h1>
				</div>
			</div>
		</div>

		<?php if ( $wp_query->have_posts() ) { ?>

			<?php /* Search Results */ ?>
			<div class="grid-container search-results-container">
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
				<div class="grid-container search-results-pagination">
					<div class="grid-x grid-padding-x">
						<div class="cell">
							<?php get_template_part('partials/pagination/list', '', $args); ?>
						</div>
					</div>
				</div>
			<?php } ?>

		<?php } else { ?>

			<div class="grid-container search-results-container">
				<div class="grid-x grid-padding-x">
					<div class="cell">
						<h2>No search results found.</h2>
					</div>
				</div>
			</div>

		<?php } ?>
		
	</div>
</main>

<?php get_footer();
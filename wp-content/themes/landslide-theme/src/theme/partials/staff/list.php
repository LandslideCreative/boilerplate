<?php $args = array(
	'post_type' => 'staff'
); 

$staff_query = new WP_Query( $args );

if ( $staff_query->have_posts() ) { ?>

	<div class="page-section white-bg staff-list">
		<div class="grid-container">
			<div class="grid-x grid-padding-x">
				<div class="cell">

					<?php while ( $staff_query->have_posts() ) { 
						$staff_query->the_post();
						get_template_part('partials/staff/list', 'item');
					} 
					wp_reset_postdata(); ?>
					
				</div>
			</div>
		</div>
	</div>

<?php } ?>

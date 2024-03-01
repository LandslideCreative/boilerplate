<?php 
if( have_rows('page_builder') ) {

	while( have_rows('page_builder') ) : the_row();

		// Global Sections
		if( get_row_layout() == 'global_section' ) {
			// Save current post
			$current_post = get_the_ID();

			// Select which global section
			$global_section = get_sub_field('global_section');
			if( $global_section ) {

				// Setup global section
				$post = $global_section;
				setup_postdata( $post );

				// Display global section
				LSPB()->enter_nested_section();
				get_template_part('partials/page', 'builder');
				LSPB()->exit_nested_section();
			}
			
			// Reset to current post
			$post = get_post( $current_post );
			setup_postdata( $post );
		
		// Page Specific Content
		} else if( get_row_layout() == 'page_specific_content' ) {
			
			// Partials for specific templates or pages
			if( is_page_template( 'templates/template-staff.php' ) ) {
				get_template_part('partials/staff/list');
			}

		// Page Section Partials
		} else {

			if( locate_template( 'partials/page-builder/'.get_row_layout().'.php' ) ) {

				get_template_part( 'partials/page-builder/'.get_row_layout() );
			
			} else { ?>
				
				<section class="page-section" <?php LSPB()->display_section_id(); ?>>
					<div class="grid-container">
						<div class="grid-x grid-padding-x">
							<div class="cell">
								<h2>This section type does not exist.</h2>
							</div>
						</div>
					</div>
				</section>
				
			<?php }

		}

	endwhile;
	
}
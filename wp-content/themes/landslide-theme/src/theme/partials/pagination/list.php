<?php

$current_page = $args['current_page'];
$max_pages = $args['max_pages'];

if( $max_pages>1 ) { ?>
	<div class="pagination page-list">

	    <?php $big = 999999999;
		echo paginate_links(array(
	        'base' => str_replace($big, '%#%', get_pagenum_link($big)),
	        'format' => '?paged=%#%',
	        'current' => $current_page,
	        'total' => $max_pages,
	        'next_text' => '<span>Next</span>',
	        'prev_text' => '<span>Back</span>',
	        'mid_size' => 2
	    )); ?>

	</div>
<?php } 

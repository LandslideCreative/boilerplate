<?php

$args = wp_parse_args(
    $args,
    array(
        'current_page' => 1,
        'max_pages' => 1,
        'anchor' => ''
    )
);

$current_page = $args['current_page'];
$max_pages = $args['max_pages'];
$anchor = $args['anchor'];

if( $max_pages>1 ) { ?>
	<div class="pagination page-select">
		<?php if( $current_page!=1 ) { ?>
			<div class="pagination-back">
				<a href="<?php echo get_pagenum_link($current_page-1).$anchor; ?>"><span class="visually-hidden">Back</span></a>
			</div>
		<?php } else { ?>
			<div class="pagination-back disabled"></div>
		<?php } ?>

		<div class="pagination-skip">
			<div class="pagination-select">
				<a href="#" class="pagination-select-current" aria-expanded="false"><?php echo $current_page; ?></a>
				<ul>
					<?php for($i = 1; $i<=$max_pages; $i++) { ?>
						<li><a href="<?php echo get_pagenum_link($i).$anchor; ?>"><?php echo $i; ?></a></li>
					<?php } ?>
				</ul>
			</div>
			<div class="pagination-total">
				of <?php echo $max_pages; ?> pages
			</div>
		</div>


		<?php if( $current_page!=$max_pages ) { ?>
			<div class="pagination-next">
				<a href="<?php echo get_pagenum_link($current_page+1).$anchor; ?>"><span class="visually-hidden">Next</span></a>
			</div>
		<?php } else { ?>
			<div class="pagination-next disabled"></div>
		<?php } ?>
	</div>
<?php }
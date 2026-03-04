<?php 
$show_alert = get_field('alert_toggle', 'options');
$start_date = get_field('alert_start_date', 'options');
$end_date = get_field('alert_end_date', 'options');

if( $show_alert && ($start_date || $end_date) ) {
	$show_alert = false;
	$now = new DateTime('NOW', wp_timezone());

	if($start_date && $end_date) {
	    if( $start_date<=$now->format('Ymd') && $end_date>=$now->format('Ymd') ) {
	        $show_alert = true;
	    }
	} else if( $start_date && $start_date<=$now->format('Ymd') ) {
	    $show_alert = true;
	} else if( $end_date && $end_date>=$now->format('Ymd') ) {
	    $show_alert = true;
	}
}

if( $show_alert ) { ?>
	<div class="alert-bar">
		<?php the_field('alert_copy', 'options'); ?>
	</div>
<?php } ?>
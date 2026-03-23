<?php
/*------------------------------------*\
    Display Functions
\*------------------------------------*/

// Get single featured image size URL
function thumbnail_image_single( $size='medium' ) {
	if ( has_post_thumbnail() ) {
		$thumb_id = get_post_thumbnail_id();
		$image = wp_get_attachment_image_src( $thumb_id, $size, FALSE );
		$image = $image[0];
	} else {
		return FALSE;
	}

	return $image;
}

// Output featured image tag
function thumbnail_image_tag( $sizes='100vw', $thumbnail='x-large' ) {
	if ( has_post_thumbnail() ) {
		$thumb_id = get_post_thumbnail_id();
		$image_url = wp_get_attachment_image_src( $thumb_id, $thumbnail, FALSE );
		$image_url = $image_url[0];
		echo '<img src="'.$image_url.'" srcset="'.wp_get_attachment_image_srcset($thumb_id, $thumbnail).'" sizes="'.$sizes.'">';
	} else {
		return FALSE;
	}

	return TRUE;
}

// Get featured image alt tag
function thumbnail_alt() {
	if ( has_post_thumbnail() ) {
		$thumb_id = get_post_thumbnail_id();
		$alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', TRUE);
	} else {
		return FALSE;
	}

	return $alt;
}

// Get full array of featured image URLs
function thumbnail_image_array() {
	if ( has_post_thumbnail() ) {
		$thumb_id = get_post_thumbnail_id();
		$image['alt'] = get_post_meta($thumb_id, '_wp_attachment_image_alt', TRUE);
		$image['full'] = wp_get_attachment_image_src( $thumb_id, 'full', FALSE );
		$image['full'] = $image['full'][0];
		$all_sizes = get_intermediate_image_sizes();
		foreach( $all_sizes as $size ) {
			$image[$size] = wp_get_attachment_image_src( $thumb_id, $size, FALSE );
			$image[$size] = $image[$size][0];
		}
	} else {
		return FALSE;
	}

	return $image;
}

// Get single ACF image size URL
function acf_image_single( $variable_name, $size='medium', $sub=FALSE, $options='' ) {
	if( $sub ) {
		$variable_image = get_sub_field($variable_name);
	} else {
		$variable_image = get_field($variable_name, $options);
	}

	if( $variable_image ) {
		if( $size =='full' ) {
			$image = $variable_image['url'];
		} else {
			$image = $variable_image['sizes'][$size];
		}
	} else {
		return FALSE;
	}

	return $image;
}

// Output ACF image tag
function acf_image_tag( $variable_name, $sizes='100vw', $thumbnail='x-large', $sub=FALSE, $options='' ) {
	if( $sub ) {
		$variable_image = get_sub_field($variable_name);
	} else {
		$variable_image = get_field($variable_name, $options);
	}

	if( $variable_image ) {
		$thumb_id = $variable_image['ID'];
		$image_url = wp_get_attachment_image_src( $thumb_id, $thumbnail, FALSE );
		$image_url = $image_url[0];
		echo '<img src="'.$image_url.'" srcset="'.wp_get_attachment_image_srcset($thumb_id, $thumbnail).'" sizes="'.$sizes.'">';
	} else {
		return FALSE;
	}

	return TRUE;
}

// Get full array of ACF image URLs
function acf_image_array( $variable_name, $sub=FALSE, $options='' ) {
	if( $sub ) {
		$variable_image = get_sub_field($variable_name);
	} else {
		$variable_image = get_field($variable_name, $options);
	}

	if( $variable_image ) {
		$image['alt'] = $variable_image['alt'];
		$image['full'] = $variable_image['url'];
		$all_sizes = get_intermediate_image_sizes();
		foreach( $all_sizes as $size ) {
			$image[$size] = $variable_image['sizes'][$size];
		}
	} else {
		return FALSE;
	}

	return $image;
}

// Ouput link from ACF link
function link_from_link( $variable_name, $sub=FALSE, $options='', $class='' ) {

	if( $sub ) {
		$link = get_sub_field($variable_name);
	} else {
		$link = get_field($variable_name, $options);
	}

	if( $link ) { 
		echo '<a href="'.$link['url'].'" class="'.$class.'" target="'.$link['target'].'">'.$link['title'].'</a>';
	} else {
		return FALSE;
	}

	return TRUE;
}

// Output button from ACF link
function button_from_link( $variable_name, $sub=FALSE, $options='', $class=''  ) {

	if( $sub ) {
		$link = get_sub_field($variable_name);
	} else {
		$link = get_field($variable_name, $options);
	}

	if( $link ) {
		echo '<a class="button'.$class.'" href="'.$link['url'].'" target="'.$link['target'].'">'.$link['title'].'</a>';
	} else {
		return FALSE;
	}

	return TRUE;
}

// Display date(s)
function ls_get_list_dates( $start, $end=NULL, $date_array=array() ) {
	$weekday = 'l';
	$month = 'F';
	$day = 'j';
	$year = 'Y';

	if( $date_array ) {
		if( array_key_exists('weekday', $date_array) ) {
			$weekday = $date_array['weekday'];
		}
		if( array_key_exists('month', $date_array) ) {
			$month = $date_array['month'];
		}
		if( array_key_exists('day', $date_array) ) {
			$day = $date_array['day'];
		}
		if( array_key_exists('year', $date_array) ) {
			$year = $date_array['year'];
		}
	}

	$date_string = '';

	$start_date = DateTime::createFromFormat('Ymd G:i:s', $start);
	if( $end ) {
		$end_date = DateTime::createFromFormat('Ymd G:i:s', $end);
	}

	if( !$end || $start_date->format('Ymd')==$end_date->format('Ymd') ) {
		if( $weekday ) {
			$date_string .= '<span class="date-weekday">'.$start_date->format($weekday).'</span>';
		}
		if( $weekday && $month ) {
			$date_string .= '<span class="date-weekday-comma">,</span> ';
		}
		if( $month ) {
			$date_string .= '<span class="date-month">'.$start_date->format($month).'</span> ';
		}
		if( $day ) {
			$date_string .= '<span class="date-day">'.$start_date->format($day).'</span>';
		}
		if( $day && $year ) {
			$date_string .= '<span class="date-day-comma">,</span> ';
		}
		if( $year ) {
			$date_string .= '<span class="date-year">'.$start_date->format($year).'</span>';
		}
	} else {
		if( $month ) {
			$date_string .= '<span class="date-month">'.$start_date->format($month).'</span> ';
		}
		if( $day ) {
			$date_string .= '<span class="date-day">'.$start_date->format($day).'</span>';
		}
		if( $year && $start_date->format('Y') != $end_date->format('Y') ) {
			if( $day ) {
				$date_string .= '<span class="date-day-comma">,</span> ';
			}
			$date_string .= '<span class="date-year">'.$start_date->format($year).'</span>';
		}
		$date_string .= ' - ';
		if( $start_date->format('n') != $end_date->format('n') ) {
			if( $month ) {
				$date_string .= '<span class="date-month">'.$end_date->format($month).'</span> ';
			}
		}
		if( $day ) {
			$date_string .= '<span class="date-day">'.$end_date->format($day).'</span>';
		}
		if( $day && $year ) {
			$date_string .= '<span class="date-day-comma">,</span> ';
		}
		if( $year ) {
			$date_string .= '<span class="date-year">'.$end_date->format($year).'</span>';
		}
	}

	return $date_string;
}

// Display time(s)
function ls_get_times( $start, $end=NULL ) {
	$date_string = '';

	$start_date = DateTime::createFromFormat('Ymd G:i:s', $start);
	if( $start==$end || !$end ) {
		$date_string .= $start_date->format('g:i a');
	} else {
		$end_date = DateTime::createFromFormat('Ymd G:i:s', $end);
		$date_string .= $start_date->format('g:i');
		if( $start_date->format('a') != $end_date->format('a') ) {
			$date_string .= $start_date->format(' a');
		}
		$date_string .= ' - ';		
		$date_string .= $end_date->format('g:i a');
	}

	return $date_string;
}

/* Sticky navigation
* 
* To turn on sticky navigation, change $sticky to true and enable it in these Foundation files:
*
* - /assets/app.scss (line 57)
* - /assets/js/lib/foundation-explicit-pieces.js (lines 30 & 77)
*
*/
function ls_sticky_nav( $position='' ) {
	$sticky = false;

	if( $sticky ) {
		if( $position=='container' ) {
			echo 'data-sticky-container';
		} else if( $position=='element' ) {
			echo 'data-sticky data-top-anchor="sticky-navigation:top" data-margin-top="0" data-sticky-on="small"';
		}
	}

	return $sticky;
}
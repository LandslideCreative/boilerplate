<?php
/*------------------------------------*\
    Display Functions
\*------------------------------------*/

// Get post thumbnail URL
function thumbnail_image_single( $size = 'medium' ) {
	return get_the_post_thumbnail_url( null, $size );
}

// Get the post thumbnail alt text
function thumbnail_alt() {
	$thumb_id = get_post_thumbnail_id();

	if ( !$thumb_id ) {
		return '';
	}

	return get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
}

// Output responsive post thumbnail tag
function thumbnail_image_tag( $sizes='100vw', $thumbnail='x-large', $alt=null ) {
	$thumb_id = get_post_thumbnail_id();

	if ( !$thumb_id ) {
		return false;
	}

	$attributes = array( 'sizes' => $sizes );

	if ( $alt !== null ) {
		$attributes['alt'] = $alt;
	}	

	$image = wp_get_attachment_image( $thumb_id, $thumbnail, false, $attributes );

	if ( !$image ) {
		return false;
	}

	echo $image;

	return true;
}

// Get full array of post thumbnail sizes
function thumbnail_image_array() {
	$thumb_id = get_post_thumbnail_id();

	if ( !$thumb_id ) {
		return false;
	}

	$image = array(
		'alt' => get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ),
		'full' => wp_get_attachment_image_url( $thumb_id, 'full' )
	);

	foreach ( get_intermediate_image_sizes() as $size ) {
		$image[$size] = wp_get_attachment_image_url( $thumb_id, $size );
	}

	return $image;
}

// Get ACF image URL
function acf_image_single( $variable_name, $size='medium', $sub=false, $options='' ) {
	if ( $sub ) {
		$image_id = get_sub_field( $variable_name, false );
	} else {
		$image_id = get_field( $variable_name, $options, false );
	}

	if ( !$image_id ) {
		return false;
	}

	return wp_get_attachment_image_url( $image_id, $size );
}

// Get ACF image alt text
function acf_alt( $variable_name, $sub=false, $options='' ) {
	if ( $sub ) {
		$image_id = get_sub_field( $variable_name, false );
	} else {
		$image_id = get_field( $variable_name, $options, false );
	}

	if ( !$image_id ) {
		return '';
	}

	return get_post_meta( $image_id, '_wp_attachment_image_alt', true );
}

// Output responsive ACF image tag
function acf_image_tag( $variable_name, $sizes='100vw', $thumbnail='x-large', $sub=false, $options='', $alt=null ) {
	if ( $sub ) {
		$image_id = get_sub_field( $variable_name, false );
	} else {
		$image_id = get_field( $variable_name, $options, false );
	}

	if ( !$image_id ) {
		return false;
	}

	$attributes = array( 'sizes' => $sizes );

	if ( $alt !== null ) {
		$attributes['alt'] = $alt;
	}

	$image = wp_get_attachment_image( $image_id, $thumbnail, false, $attributes );

	if ( !$image ) {
		return false;
	}

	echo $image;

	return true;
}

// Get full array of ACF image sizes
function acf_image_array( $variable_name, $sub=FALSE, $options='' ) {
	if ( $sub ) {
		$image_id = get_sub_field( $variable_name, false );
	} else {
		$image_id = get_field( $variable_name, $options, false );
	}

	if ( !$image_id ) {
		return false;
	}

	$image = array(
		'alt' => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
		'full' => wp_get_attachment_image_url( $image_id, 'full' )
	);

	foreach ( get_intermediate_image_sizes() as $size ) {
		$image[$size] = wp_get_attachment_image_url( $image_id, $size );
	}

	return $image;
}

// Ouput link from ACF link
// Output link from ACF link.
function link_from_link( $variable_name, $sub = false, $options = '', $class = '' ) {
	if ( $sub ) {
		$link = get_sub_field( $variable_name );
	} else {
		$link = get_field( $variable_name, $options );
	}

	if ( !is_array( $link ) || empty( $link['url'] ) || !isset( $link['title'] ) ) {
		return false;
	}

	$url = esc_url($link['url']);

	$attributes = '';

	if ( $class !== '' ) {
		$attributes .= ' class="'.esc_attr($class).'"';
	}

	if ( !empty( $link['target'] ) ) {
		$attributes .= ' target="'.esc_attr($link['target']).'"';

		if ( $link['target'] === '_blank' ) {
			$attributes .= ' rel="noopener"';
		}
	}

	echo '<a href="'.$url.'"'.$attributes.'>'.esc_html($link['title']).'</a>';

	return true;
}

// Output button from ACF link
function button_from_link( $variable_name, $sub=false, $options='', $class=''  ) {
	return link_from_link( $variable_name, $sub, $options, trim( 'button ' . $class ) );
}

// Display date(s)
function ls_get_list_dates( $start, $end = null, $date_array = array() ) {
	$formats = array_merge( array( 'weekday' => 'l', 'month' => 'F', 'day' => 'j', 'year' => 'Y' ), $date_array );

	$start_date = DateTimeImmutable::createFromFormat( 'Ymd G:i:s', $start );

	if ( !$start_date ) {
		return '';
	}

	$end_date = null;

	if ( $end ) {
		$end_date = DateTimeImmutable::createFromFormat( 'Ymd G:i:s', $end );

		if ( !$end_date ) {
			return '';
		}
	}

	$dates = array( $start_date );
	$date_formats = array( $formats );

	if ( $end_date && $start_date->format( 'Ymd' ) !== $end_date->format( 'Ymd' ) ) {
		$formats['weekday'] = '';
		$start_formats = $formats;
		$end_formats = $formats;

		if ( $start_date->format( 'Y' ) === $end_date->format( 'Y' ) && ( $formats['month'] || $formats['day'] ) ) {
			$start_formats['year'] = '';
		}

		if ( $start_date->format( 'Ym' ) === $end_date->format( 'Ym' ) && $formats['day'] ) {
			$end_formats['month'] = '';
		}

		$dates[] = $end_date;
		$date_formats = array( $start_formats, $end_formats );
	}

	$output = array();

	foreach ( $dates as $index => $date ) {
		$parts = $date_formats[$index];
		$html = '';
		$previous_part = '';

		foreach ( array( 'weekday', 'month', 'day', 'year' ) as $part ) {
			if ( !$parts[$part] ) {
				continue;
			}

			if ( $previous_part === 'weekday' || ( $previous_part === 'day' && $part === 'year' ) ) {
				$html .= '<span class="date-'.$previous_part.'-comma">,</span> ';
			} elseif ( $html !== '' ) {
				$html .= ' ';
			}

			$html .= '<span class="date-'.$part.'">'.esc_html( $date->format( $parts[$part] ) ).'</span>';
			$previous_part = $part;
		}

		$output[] = $html;
	}

	$separator = ' <span aria-hidden="true">-</span><span class="show-for-sr"> to </span> ';

	return implode( $separator, array_unique( $output ) );
}

// Display time(s)
function ls_get_times( $start, $end = null, $uppercase = false ) {
	$meridiem = 'a';

	if ( $uppercase ) {
		$meridiem = 'A';
	}

	$start_date = DateTimeImmutable::createFromFormat( 'Ymd G:i:s', $start );

	if ( !$start_date ) {
		return '';
	}

	$end_date = null;

	if ( $end ) {
		$end_date = DateTimeImmutable::createFromFormat( 'Ymd G:i:s', $end );

		if ( !$end_date ) {
			return '';
		}
	}

	if ( !$end_date || $start_date == $end_date ) {
		return $start_date->format( 'g:i '.$meridiem );
	}

	$date_string = $start_date->format( 'g:i' );

	if ( $start_date->format( 'a' ) !== $end_date->format( 'a' ) ) {
		$date_string .= $start_date->format( ' '.$meridiem );
	}

	$date_string .= ' <span aria-hidden="true">-</span><span class="screen-reader-text"> to </span> ';
	$date_string .= $end_date->format( 'g:i '.$meridiem );

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
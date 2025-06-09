<?php 

// Display event dates in relationship field
function ls_events_calendar_relationship_result( $text, $post, $field, $post_id ) {
    $args['post__in'] = array($post->ID);

    $events = tribe_get_events($args, true);

    if ( $events->have_posts() ) {
        $start_date = tribe_get_start_date($events->posts[0]->ID, false, 'm/d/y');
        $text .= ' ['.$start_date.']';
    }

    return $text;
}
add_filter('acf/fields/relationship/result/name=events', 'ls_events_calendar_relationship_result', 10, 4);

// Filter past events from relationship field
function ls_events_calendar_filter_relationship_result( $options, $field, $post_id ) {
    $timezone = wp_timezone_string();
    $now = new DateTime("now", new DateTimeZone($timezone) );

    $options['meta_query'] = array(
        array(
            'key' => '_EventEndDate',
            'compare' => '>=',
            'value' =>  $now->format('Y-m-d H:i:s'),
            'type' => 'DATETIME'
        )
    );

    $options['meta_key'] = '_EventStartDate';
    $options['meta_type'] = 'DATETIME';
    $options['orderby'] = 'meta_value';

    return $options;
}
add_filter('acf/fields/relationship/query/name=events', 'ls_events_calendar_filter_relationship_result', 10, 3);

// Return redirect/404 for past events
function ls_expire_past_events() {
    if( function_exists('tribe_is_event') ) {
        global $wp_query;

        if( (tribe_is_event() && is_single()) && !is_user_logged_in() ) {
            $timezone = wp_timezone_string();
            $now = new DateTime("now", new DateTimeZone($timezone) );

            $expiration_date = tribe_get_end_date(NULL, true, 'U');
            $expiration_date = $expiration_date+86400+18000;

            if( $now->format('U') > $expiration_date ) {

                if( tribe_is_recurring_event() ) {
                    $event_id = TEC\Events\Custom_Tables\V1\Models\Occurrence::normalize_id ( get_the_ID() );

                    if($event_id) {
                        $recurring_occurrence = tribe_events()->where( 'ID', $event_id )->where( 'start_date', $now->format('Y-m-d H:i:s') )->first()->ID;

                        if( $recurring_occurrence ) {
                            wp_redirect(tribe_get_event_link($recurring_occurrence));
                        } else {
                            $wp_query->set_404();
                            status_header( 404 );
                            include( get_query_template( '404' ) );
                            exit();
                        }
                    }
                } else {
                    $wp_query->set_404();
                    status_header( 404 );
                    include( get_query_template( '404' ) );
                    exit();
                }
            }
        }
    }
}
add_action( 'wp', 'ls_expire_past_events' );

// Remove past events from SearchWP
function ls_remove_past_events_from_searchwp( $ids ) {
    if (function_exists('tribe_get_events')) {
        $past_events = tribe_get_events( [
           'end_date'     => 'now',
           'posts_per_page' => -1,
        ] );

        foreach( $past_events as $past_event ) {
            $past_events_ids[] = $past_event->ID;
        }

        $ids = array_merge( $ids, $past_events_ids );
    }

    return array_unique( $ids );
}
add_filter( 'searchwp\post__not_in', 'ls_remove_past_events_from_searchwp', 20, 2 );
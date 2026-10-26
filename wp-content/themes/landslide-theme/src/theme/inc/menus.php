<?php
/*------------------------------------*\
    Navigation Menus
\*------------------------------------*/

// Display navigation wrapped in a <ul>
function display_navigation($nav_slug) {
    if( has_nav_menu( $nav_slug ) ) {
        $menu_parameters = array(
            'theme_location'  => $nav_slug,
            'menu'            => '',
            'container'       => 'div',
            'container_class' => 'menu-{menu slug}-container',
            'container_id'    => '',
            'menu_class'      => 'menu',
            'menu_id'         => '',
            'echo'            => true,
            'fallback_cb'     => 'wp_page_menu',
            'before'          => '',
            'after'           => '',
            'link_before'     => '',
            'link_after'      => '',
            'items_wrap'      => '<ul>%3$s</ul>',
            'depth'           => 0,
            'walker'          => ''
        );

        wp_nav_menu( $menu_parameters );
    }
}

// Display navigation <li>s
function display_simple_navigation($nav_slug) {
    if( has_nav_menu( $nav_slug ) ) {
        $menu_parameters = array(
            'theme_location'  => $nav_slug,
            'container'       => false,
            'echo'            => true,
            'items_wrap'      => '%3$s',
            'depth'           => 0
        );

        wp_nav_menu( $menu_parameters );
    }
}

// Register navigation menus
function ls_boilerplate_register_menu()
{
    register_nav_menus(array( 
        'top-nav' => __('Top Navigation', 'html5blank'),
        'main-nav' => __('Main Navigation', 'html5blank'),
        'footer-nav' => __('Footer Navigation', 'html5blank'),
        'policy-nav' => __('Policies Navigation', 'html5blank')
    ));
}
add_action('init', 'ls_boilerplate_register_menu');

// Remove the <div> surrounding the dynamic navigation to cleanup markup
function my_wp_nav_menu_args($args = '')
{
    $args['container'] = false;
    return $args;
}
add_filter('wp_nav_menu_args', 'my_wp_nav_menu_args');

// Output submenu parent items as buttons.
function ls_submenu_toggle_buttons( $item_output, $item, $depth, $args ) {
    if ( !in_array( $args->theme_location, array( 'main-nav', 'top-nav' ), true ) || !in_array( 'menu-item-has-children', $item->classes, true ) ) {
        return $item_output;
    }

    return preg_replace( '/<a\b[^>]*>(.*?)<\/a>/s', '<button type="button" class="submenu-toggle" aria-expanded="false">$1</button>', $item_output, 1 );
}
add_filter( 'walker_nav_menu_start_el', 'ls_submenu_toggle_buttons', 10, 4 );

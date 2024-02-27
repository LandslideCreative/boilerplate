<?php

// CSS & JS Build Version auto-incremented by gulp-bump
define( 'LS_BUILD_VERSION', '1.0.0' );

// Set $content_width global variable
if( !isset($content_width) ) { $content_width = 1200; }

/*------------------------------------*\
    Theme Support
\*------------------------------------*/

if( function_exists('add_theme_support') ) {

    // Add post thumbnail support
    add_theme_support('post-thumbnails');

    // Add image sizes (also uses default sizes from Settings->Media at Thumbnail: 150x150, Medium: 800x800 & Large: 1200x1200)
    add_image_size('x-small', 360);
    add_image_size('small', 640);
    add_image_size('x-large', 1920);
    add_image_size('square', 600, 600, true);
    add_image_size('small-square', 300, 300, true);

    // Enables post and comment RSS feed links to head
    add_theme_support('automatic-feed-links');

    // Enable HTML5 support
    add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'style', 'script' ) );

    // Localisation Support
    load_theme_textdomain('landslide', get_template_directory() . '/languages');

}

// Remove unused default thumbnail sizes
function ls_remove_thumbnail_sizes( $sizes ) {
  $targets = ['medium_large', '1536x1536', '2048x2048'];

  foreach($sizes as $size_index=>$size) {
    if(in_array($size, $targets)) {
      unset($sizes[$size_index]);
    }
  }

  return $sizes;
}
add_filter( 'intermediate_image_sizes', 'ls_remove_thumbnail_sizes', 10, 1);

/*------------------------------------*\
    Header Scripts & Styles
\*------------------------------------*/

// Add scripts
function ls_enqueue_scripts() {
    if ($GLOBALS['pagenow'] != 'wp-login.php' && !is_admin()) {

        // Remove WP jQuery
        wp_deregister_script( 'jquery' );

        // Add theme scripts
        wp_register_script('jquery', get_template_directory_uri() . '/assets/js/app.js', array(), LS_BUILD_VERSION, false);
        wp_enqueue_script('jquery');
        
    }
}
add_action('init', 'ls_enqueue_scripts');

// Add conditional scripts
/* function ls_enqueue_conditional_scripts()
{
    if ( is_page('page-name') ) {
        wp_register_script('script-name', '', array('theme-scripts'), '1.0.0');
        wp_enqueue_script('script-name');
    }
} 
add_action('wp_print_scripts', 'ls_boilerplate_conditional_scripts'); */

// Add stylesheets
function ls_enqueue_styles() {
    // Add font CSS
    // wp_register_style('fonts', 'FONT_CSS_URL', array(), '1.0.0');
    // wp_enqueue_style('fonts');

    // Add theme CSS
    wp_register_style('theme-styles', get_template_directory_uri() . '/assets/css/app.css', array(), LS_BUILD_VERSION);
    wp_enqueue_style('theme-styles');
}
add_action('wp_enqueue_scripts', 'ls_enqueue_styles');

// Remove Gutenberg CSS from the frontend
function ls_remove_gutenberg_css() {
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'wc-blocks-style' );
} 
add_action( 'wp_enqueue_scripts', 'ls_remove_gutenberg_css', 100 );

// Remove YARPP styles
add_filter( 'yarpp_enqueue_related_style', '__return_false' );
add_filter( 'yarpp_enqueue_thumbnails_style', '__return_false' );

// Remove 'text/css' from stylesheet
function ls_style_type_remove($tag) {
    return preg_replace('~\s+type=["\'][^"\']++["\']~', '', $tag);
}
add_filter('style_loader_tag', 'ls_style_type_remove');

/*------------------------------------*\
    Frontend Functions
\*------------------------------------*/

// Add page slug to body class
function ls_add_slug_to_body_class($classes) {
    global $post;
    if (is_home()) {
        $key = array_search('blog', $classes);
        if ($key > -1) {
            unset($classes[$key]);
        }
    } elseif (is_page()) {
        $classes[] = sanitize_html_class($post->post_name);
    } elseif (is_singular()) {
        $classes[] = sanitize_html_class($post->post_name);
    }

    return $classes;
}
add_filter('body_class', 'ls_add_slug_to_body_class');

// Custom excerpt callback
function ls_excerpt($length_callback = '', $more_callback = '') {
    global $post;
    if (function_exists($length_callback)) {
        add_filter('excerpt_length', $length_callback);
    }
    if (function_exists($more_callback)) {
        add_filter('excerpt_more', $more_callback);
    }
    $output = get_the_excerpt();
    $output = apply_filters('wptexturize', $output);
    $output = apply_filters('convert_chars', $output);
    $output = '<p>' . $output . '</p>';
    echo $output;
}

// Custom excerpt length - call using ls_excerpt('ls_excerpt_default');
function ls_excerpt_default($length) {
    return 20;
}

// Custom more link on post archives
function ls_more_link($more) {
    global $post;
    return ' <a class="text-button read-more" href="' . get_permalink($post->ID) . '">more...</a>';
}
add_filter('excerpt_more', 'ls_more_link');

// Remove invalid rel attribute values in the categorylist
function ls_remove_category_rel($thelist) {
    return str_replace('rel="category tag"', 'rel="tag"', $thelist);
}
add_filter('the_category', 'ls_remove_category_rel');

// Remove the width and height attributes from inserted images
function ls_remove_size_attributes( $html ) {
   $html = preg_replace( '/(width|height)="\d*"\s/', "", $html );
   return $html;
}
add_filter('post_thumbnail_html', 'ls_remove_size_attributes', 10 );
add_filter('image_send_to_editor', 'ls_remove_size_attributes', 10 );

/*------------------------------------*\
    Actions + Filters
\*------------------------------------*/

// Remove Actions
remove_action('wp_head', 'feed_links_extra', 3); // Display the links to the extra feeds such as category feeds
remove_action('wp_head', 'feed_links', 2); // Display the links to the general feeds: Post and Comment Feed
remove_action('wp_head', 'rsd_link'); // Display the link to the Really Simple Discovery service endpoint, EditURI link
remove_action('wp_head', 'wlwmanifest_link'); // Display the link to the Windows Live Writer manifest file.
remove_action('wp_head', 'wp_generator'); // Display the XHTML generator that is generated on the wp_head hook, WP version
remove_action('wp_head', 'rel_canonical'); // Output rel=canonical for singular queries.
remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0); // Injects rel=shortlink into the head if a shortlink is defined for the current page.

// Add Filters
add_filter('widget_text', 'do_shortcode'); // Allow shortcodes in Dynamic Sidebar
add_filter('widget_text', 'shortcode_unautop'); // Remove <p> tags in Dynamic Sidebar

add_filter('the_excerpt', 'do_shortcode'); // Allow Shortcodes to be executed in excerpt (manual only)
add_filter('the_excerpt', 'shortcode_unautop'); // Remove auto <p> tags in excerpt (manual only)

// Remove Filters
remove_filter('the_excerpt', 'wpautop'); // Remove <p> tags from Excerpt

/*------------------------------------*\
    Custom Functions
\*------------------------------------*/

function get_image_directory() 
{
    $image_directory = get_template_directory_uri()."/assets/img";

    return $image_directory;
}

/*------------------------------------*\
    ACF Functions
\*------------------------------------*/

require get_template_directory() . '/inc/acf.php';

/*------------------------------------*\
    Display Functions
\*------------------------------------*/

require get_template_directory() . '/inc/display.php';

/*------------------------------------*\
    Navigation Menus
\*------------------------------------*/

require get_template_directory() . '/inc/menus.php';


/*------------------------------------*\
    Text Editor
\*------------------------------------*/

require get_template_directory() . '/inc/text-editor.php';

<?php 
/*------------------------------------*\
    Text Editor Functions
\*------------------------------------*/

// Change formats hover style
function fix_formats_hover() {
  echo '<style>
    .mce-menu .mce-menu-item.mce-selected, .wp-admin .mce-menu .mce-menu-item:focus, .wp-admin .mce-menu .mce-menu-item:hover {
        background-color: #ebebeb !important;
        color: inherit !important;
    }

    .mce-menu .mce-menu-item.mce-selected .mce-ico {
        color: #333 !important;
    }

    .mce-menu .mce-menu-item.mce-active.mce-menu-item-normal, .mce-menu .mce-menu-item.mce-active.mce-menu-item-preview {
        background: #dcdcdc !important;
        color: inherit !important;
    }

    .wp-list-table.fixed.tags {
        table-layout: auto;
    }
  </style>';
}
add_action('admin_head', 'fix_formats_hover');

// Remove default format select
function remove_default_format_select( $buttons ) {

    $remove = array( 'formatselect' );

    return array_diff( $buttons, $remove );
 }
add_filter( 'mce_buttons', 'remove_default_format_select' );

// Add custom format select
function custom_format_button( $buttons ) {
    array_unshift( $buttons, 'styleselect' );
    return $buttons;
}
add_filter( 'mce_buttons', 'custom_format_button' );

// Create custom formats
function add_custom_formats( $init_array ) {  
    $style_formats = array(
            array(
                'title' => 'Body',
                'items' => array(
                    array(
                        'title' => 'Paragraph',
                        'block' => 'p'
                    ),
                    array(
                        'title' => 'Large Paragraph',
                        'block' => 'p',
                        'classes' => 'large'
                    ),
                )
            ),
            array(
                'title' => 'Headers',
                'items' => array(
                    array(
                        'title' => 'Heading 1',
                        'block' => 'h1'
                    ),
                    array(
                        'title' => 'Heading 2',
                        'block' => 'h2'
                    ),
                    array(
                        'title' => 'Heading 3',
                        'block' => 'h3'
                    ),
                    array(
                        'title' => 'Heading 4',
                        'block' => 'h4'
                    ),
                    array(
                        'title' => 'Heading 5',
                        'block' => 'h5'
                    )
                )
            ),
            array(
                'title' => 'Buttons',
                'items' => array(
                    array(
                        'title' => 'Button',
                        'inline' => 'a',
                        'classes' => 'button'
                    ),
                    array(
                        'title' => 'Button - Outline',
                        'inline' => 'a',
                        'classes' => 'button hollow'
                    ),
                    array(
                        'title' => 'Text Button',
                        'inline' => 'a',
                        'classes' => 'text-button'
                    )
                )
            ),
            array(
                'title' => 'Lists',
                'items' => array(
                    array(
                        'title' => 'Check Marks',
                        'selector' => 'ul',
                        'classes' => 'checkmarks',
                        'wrapper' => true
                    )
                )
            ),
            array(
                'title' => 'Containers',
                'items' => array(
                    array(
                        'title' => 'Remove Bottom Margin',
                        'block' => 'div',
                        'classes' => 'no-bottom-margin',
                        'wrapper' => true
                    ),
                    array(
                        'title' => 'Match Height',
                        'block' => 'div',
                        'classes' => 'match-height',
                        'attributes' => array (
                            'data-mh' => 'match-height' 
                        ),
                        'wrapper' => true
                    )
                )
            )
        );
    $init_array['style_formats'] = json_encode( $style_formats );  
    $init_array['preview_styles'] .= ' background-color color border padding-left padding-right border-radius';

    return $init_array;  

} 
add_filter( 'tiny_mce_before_init', 'add_custom_formats' );

// Add font to admin
/* function add_admin_font() {
    wp_enqueue_style( 'admin-font', 'FONT_CSS_URL' );
}
add_action( 'admin_enqueue_scripts', 'add_admin_font' ); */

// Add styles to text editor
function add_editor_styles() {
    // add_editor_style( 'FONT_CSS_URL' );
    add_editor_style( 'assets/admin-css/text-editor.css' );
}
add_action( 'admin_init', 'add_editor_styles' );

// Remove editor from admin for pages
/* function ls_remove_editor() {
    $hide_editor = true;

    if (isset($_GET['post'])) {
         $id = $_GET['post'];
         $hide_editor = true;

         $pages_with_editor = array(
            'sample'
         );

         $templates_with_editor = array(
            'templates/template-sample.php'
         );

         $slug = get_post_field( 'post_name', $id );
         $template = get_post_meta($id, '_wp_page_template', true);

         if(in_array($slug, $pages_with_editor)) {
            $hide_editor = false;
         } else if(in_array($template, $templates_with_editor)) {
            $hide_editor = false;
         }
    }

    if($hide_editor) { ?>
        <style>
            .post-type-page .postarea.wp-editor-expand {
                display: none;
            }
        </style>
    <?php }
}
add_action('admin_head', 'ls_remove_editor'); */
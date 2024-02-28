<?php 

// Create LS Page Builder class
if( ! class_exists('LSPageBuilder') ) {

	class LSPageBuilder {

		private static $instance;

		public $nested_section_counter;
		public $section_counter;
		public $section_header;

		public static function get_instance() {
			if ( null == self::$instance ) {
	            self::$instance = new self;
	        }

	        return self::$instance;
		}

		private function __construct() {
			$this->nested_section_counter = array();
			$this->section_counter = 1;
			$this->section_header = '';
		}

		public function enter_nested_section() {
			array_push( $this->nested_section_counter, $this->section_counter);
			$this->section_counter = 1;

			return true;
		}

		public function exit_nested_section() {
			$this->section_counter = array_pop($this->nested_section_counter);	
			$this->increment_section_counter();		

			return true;
		}

		public function increment_section_counter() {
			$this->section_counter++;

			return $this->section_counter;
		}

		public function get_section_id() {
			$section_id = 'id="page-section-';

			if( !empty($this->nested_section_counter) ) {
				foreach( $this->nested_section_counter as $counter ) {
					$section_id .= $counter.'-';
				}
			}

			$section_id .= $this->section_counter.'"';

			$this->increment_section_counter();

			return $section_id;
		}

		public function display_section_id() {

			echo $this->get_section_id();			

			return true;
		}

		public function set_section_header($section_header) {
			$this->section_header = $section_header;

			return true;
		}

		public function clear_section_header() {
			$this->section_header = false;
		}
		

		public function display_section_header() {
			if( $this->section_header ) {
				echo $this->section_header;
				$this->clear_section_header();
			}

			return true;
		}

		public function page_specific_content() {
			if( is_page_template( 'templates/template-staff.php' ) ) {
				get_template_part('partials/staff/list');
			}

			return true;
		}

	}

	function LSPB() {
		return LSPageBuilder::get_instance();
	}

	LSPB();
}

// Save page builder field to plugin
function ls_page_builder_acf_json_save_path( $path ) {
    
    $path = LS_PLUGIN_PATH . '/assets/acf-json';
    
    return $path;
    
}
add_filter('acf/settings/save_json/name=Page Builder', 'ls_page_builder_acf_json_save_path');

// Load page builder field from plugin
function ls_page_builder_acf_json_load_path( $paths ) {

    $paths[] = LS_PLUGIN_PATH . '/assets/acf-json';

    return $paths;    
}
add_filter( 'acf/settings/load_json', 'ls_page_builder_acf_json_load_path' );

// Add LS Page Builder styles and scripts
function ls_add_page_builder_scripts() {

	// LS Page Builder Styles
	wp_register_style('ls-page-builder', LS_PLUGIN_URL . '/assets/css/ls-page-builder.css', array(), '1.0.0');
    wp_enqueue_style('ls-page-builder');

    // LS Page Builder Scripts
	wp_register_script('ls-page-builder', LS_PLUGIN_URL . '/assets/js/ls-page-builder.js', array(), LS_BUILD_VERSION, true);
    wp_enqueue_script('ls-page-builder');
}
add_action('acf/input/admin_enqueue_scripts', 'ls_add_page_builder_scripts');

// Remove ACF WYSIWYG inline styles
function ls_remove_acf_wysiwyg_styles() { ?>
    <script type="text/javascript">
        (function($) {
            acf.add_action('wysiwyg_tinymce_init', function( ed, id, mceInit, $field ){
                $(".acf-field .acf-editor-wrap iframe").removeAttr("style");
            });
            acf.add_action('sortstop', function( ed, id, mceInit, $field ){
                $(".acf-field .acf-editor-wrap iframe").removeAttr("style");
            });
        })(jQuery); 
    </script>
<?php }
add_action('acf/input/admin_footer', 'ls_remove_acf_wysiwyg_styles');

// Create Global Section post type
function ls_create_global_section_post_type()
{
    register_post_type('ls-global-section',
        array(
        'labels' => array(
            'name' => __('Global Sections', 'html5blank'),
            'singular_name' => __('Global Section', 'html5blank'),
            'add_new' => __('Add Global Section', 'html5blank'),
            'add_new_item' => __('Add New Global Section', 'html5blank'),
            'edit_item' => __('Edit Global Section', 'html5blank'),
            'new_item' => __('New Global Section', 'html5blank'),
            'view_item' => __('View Global Section', 'html5blank'),
            'view_items' => __('View Global Sections', 'html5blank'),
            'search_items' => __('Search Global Sections', 'html5blank'),
            'not_found' => __('No Global Sections found', 'html5blank'),
            'not_found_in_trash' => __('No Global Sections found in Trash', 'html5blank'),
        ),
        'public' => true,
        'hierarchical' => false,
        'publicly_queryable' => false,
        'menu_icon' => 'dashicons-businessman',
        'show_in_menu' => false,
        'supports' => array(
            'title'
        ),
        'taxonomies' => array(),
        'can_export' => false
    ));
}
add_action('init', 'ls_create_global_section_post_type');

// Move Global Sections to Theme Settings menu
function ls_move_global_section_menu() {
    add_submenu_page('theme-settings', 'Global Sections', 'Global Sections', 'edit_pages' , 'edit.php?post_type=ls-global-section');
}
add_action('admin_menu', 'ls_move_global_section_menu', 99);
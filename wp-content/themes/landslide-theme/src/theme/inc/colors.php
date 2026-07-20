<?php // Page Builder Colors

if( ! class_exists('LS_Colors') ) {

	class LS_Colors {
		private static $instance;

		private static $colors;
		private static $default_colors;

		public static function get_instance() {
            if ( null == self::$instance ) {
                self::$instance = new self;
            }

            return self::$instance;
        }

        private function __construct() {
            self::$colors = array(
                'white' => '#fffff'
            );
            self::$default_colors = array(
                'white'
            );
        }

        public function set_colors( $colors ) {
        	self::$colors = array_map('strtolower', $colors);
        }

        public function set_default_colors( $colors ) {
        	self::$default_colors = array_map('strtolower', $colors);
        }

        public function get_default_colors() {
        	return self::$default_colors;
        }

        public function get_hex( $color ) {
        	return self::$colors[$color];
        }

        public function get_color( $color ) {
        	return array_search($color, self::$colors);
        }

        public function get_palette( $colors ) {
        	$color_string = '';

        	$color_counter = 1;
        	foreach( $colors as $color ) {
        		if( $color_counter>1 ) {
        			$color_string .= ',';
        		}

        		$color_string .= self::$colors[$color];
        		$color_counter++;
        	}

        	return $color_string;
        }

	}

	function LS_Colors() {
        return LS_Colors::get_instance();
    }

    LS_Colors();
    do_action('ls_define_colors');

}
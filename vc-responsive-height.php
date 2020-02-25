<?php

/**
 * Plugin Name: VC Responsive Height
 * Description: Adds responsive height ability to sections, rows and inner rows.
 * Version:     1.0.0
 * Author:      Reboot
 * Author URI:  https://reboot.com.tr
 * Text Domain: vc-responsive-height
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit('No direct script access allowed');
}

if (!class_exists('VC_RESPONSIVE_HEIGHT')) {

    define('VC_RESPONSIVE_HEIGHT_VERSION', '1.0.0');

    define('VC_RESPONSIVE_HEIGHT_PATH', plugin_dir_path(__FILE__));
    define('VC_RESPONSIVE_HEIGHT_URL', plugin_dir_url(__FILE__));

    define('VC_RESPONSIVE_HEIGHT_ASSETS_VERSION', VC_RESPONSIVE_HEIGHT_VERSION);

    define('VC_RESPONSIVE_HEIGHT_TEXT_DOMAIN', 'vc-responsive-height');

    class VC_RESPONSIVE_HEIGHT
    {
        public static $counter = 0;
        public static $css = [];

        function __construct()
        {
            add_action('plugins_loaded', [$this, 'init']);
            add_action('wp_footer', [$this, 'print_css'], 999);
        }

        function init()
        {
            add_action('vc_after_init', array($this, 'add_params'), 10, 0);
            add_action('vc_after_init', array($this, 'shortcode_atts_hooks'), 20, 0);
            // add_filter('do_shortcode_tag', array($this, 'do_shortcode_tag'), 99, 4);
        }

        public function add_params()
        {
            $tags = $this->get_tags();

            if(empty($tags)) {
                return;
            }

            $screen_sizes = $this->get_screen_sizes();

            if(empty($screen_sizes)) {
                return;
            }

            // params
            $params = array();

            foreach ($screen_sizes as $screen_size) {
                $params[] = array(
                    'type' => 'textfield',
                    'heading' => $screen_size['title'],
                    'description' => __('CSS units are allowed like 500px, 25%, 100vh, 100vh, calc(100vh - 50px), etc.', VC_RESPONSIVE_HEIGHT_TEXT_DOMAIN),
                    'std' => '',
                    'param_name' => 'responsive_height_' . $screen_size['key'],
                    'edit_field_class' => 'vc_col-sm-6',
                    'group' => sprintf(__('Responsive Height', VC_RESPONSIVE_HEIGHT_TEXT_DOMAIN), REBOOT_AGENCY),
                );
            }

            // add
            foreach ($tags as $tag) {
                vc_add_params($tag, $params);
            }

        }

        public function shortcode_atts_hooks()
        {
            $tags = $this->get_tags();

            if(empty($tags)) {
                return;
            }

            foreach ($tags as $tag) {
                add_filter("shortcode_atts_{$tag}", array($this, 'filter'), 999, 4);
            }
        }

        public function filter($out, $pairs, $atts, $shortcode)
        {
            $tags = $this->get_tags();

            if (!in_array($shortcode, $tags)) {
                return $out;
            }

            if(!isset($out['el_class'])) {
                $out['el_class'] = '';
            }

            self::$counter++;

            $out['el_class'] .= ' ' . sprintf(' vc-responsive-height--%s', self::$counter);
            $out['el_class'] = trim($out['el_class']);

            $out['vc_responsive_height_counter'] = self::$counter;

            $this->build_css(self::$counter, $atts);

            return $out;
        }

        function build_css($counter, $atts){
            self::$css[ $counter ] = [];

            $screen_sizes = $this->get_screen_sizes();

            if(empty($screen_sizes)) {
                return;
            }

            foreach ($screen_sizes as $screen_size) {

                $size = $atts['responsive_height_' . $screen_size['key']];

                if(empty($size)) {
                    continue;
                }

                $query = sprintf('@media (min-width: %spx)', $screen_size['min']);
                self::$css[ $counter ][] = sprintf('%s { .vc-responsive-height--%s { min-height: %s !important; } }', $query, $counter, $size);
            }

            self::$css[ $counter ] = implode("\n", self::$css[ $counter ]);
        }

        function get_tags(){
            return apply_filters('vc_responsive_height_tags', [
                'vc_section',
                'vc_row',
                'vc_row_inner',

                'vc_column',
                'vc_column_inner',
                'vc_column_text',
            ]);
        }

        function get_screen_sizes(){
            $sizes = apply_filters(
                'vc_responsive_height_screen_sizes',
                array(
                    array(
                        'title' => esc_html__( 'Mobile Portrait (0+)', VC_RESPONSIVE_HEIGHT_TEXT_DOMAIN ),
                        'key' => 'mobile_portrait',
                        'min' => 0,
                    ),
                    array(
                        'title' => esc_html__( 'Mobile Landscape (480px+)', VC_RESPONSIVE_HEIGHT_TEXT_DOMAIN ),
                        'key' => 'mobile',
                        'min' => 480,
                    ),
                    array(
                        'title' => esc_html__( 'Tablet Portrait (768px+)', VC_RESPONSIVE_HEIGHT_TEXT_DOMAIN ),
                        'key' => 'tablet_portrait',
                        'min' => 768,
                    ),
                    array(
                        'title' => esc_html__( 'Tablet Landscape (1024px+)', VC_RESPONSIVE_HEIGHT_TEXT_DOMAIN ),
                        'key' => 'tablet',
                        'min' => 1024,
                    ),
                    array(
                        'title' => esc_html__( 'Desktop (1200px+)', VC_RESPONSIVE_HEIGHT_TEXT_DOMAIN ),
                        'key' => 'desktop',
                        'min' => 1200,
                    ),
                    array(
                        'title' => esc_html__( 'Large Screen (1400px+)', VC_RESPONSIVE_HEIGHT_TEXT_DOMAIN ),
                        'key' => 'large_screen',
                        'min' => 1400,
                    ),
                )
            );

            return $sizes;
        }

        public function do_shortcode_tag($output, $tag, $attr, $m)
        {

            $tags = $this->get_tags();

            if (empty($tags) || !in_array($tag, $tags)) {
                return $output;
            }

            $screen_sizes = $this->get_screen_sizes();
            if (empty($screen_sizes)) {
                return $output;
            }

            if(!empty(self::$css[ $attr['vc_responsive_height_counter'] ])) {
                $output .= sprintf('<style>%s</style>', self::$css[ $attr['vc_responsive_height_counter'] ]);
            }

            return $output;
        }

        public function print_css(){
            printf('<style>%s</style>', implode("\n", self::$css));
        }
    }

    new VC_RESPONSIVE_HEIGHT;

}
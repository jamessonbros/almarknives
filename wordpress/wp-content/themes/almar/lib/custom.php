<?php
/**
 * Custom functions
 */


// Custom font
add_action('init', 'almar_register_styles');
function almar_register_styles() {
  wp_register_style('fontkit', get_stylesheet_directory_uri().'/assets/css/MyFontsWebfontsKit.css');
}
add_action('wp_enqueue_scripts', 'almar_load_styles');
function almar_load_styles() {
  wp_enqueue_style('fontkit');
}
<?php
/**
 * Custom functions
 */


// Woocommerce support
add_theme_support('woocommerce');


// Favicon
add_action('wp_head', 'almar_load_favicon', 9999);
function almar_load_favicon() {
?>
  
  <link rel="icon" href="/favicon.ico">
  <link rel="shortcut icon" href="/favicon.ico">

<?php
}


// Custom font
add_action('init', 'almar_register_styles');
function almar_register_styles() {
  wp_register_style('fontkit', get_stylesheet_directory_uri().'/assets/css/MyFontsWebfontsKit.css');
}
add_action('wp_enqueue_scripts', 'almar_load_styles');
function almar_load_styles() {
  wp_enqueue_style('fontkit');
}
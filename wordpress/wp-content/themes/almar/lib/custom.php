<?php
/**
 * Custom functions
 */


// Woocommerce support
add_theme_support('woocommerce');


// Woocommerce base template
// add_filter('roots_wrap_base', 'almar_wrap_woocommerce_base');
function almar_wrap_woocommerce_base($templates) {
  if (is_woocommerce()) {
    array_unshift($templates, 'base-shop.php');
  }

  return $templates;
}


// Cleanup woocommerce actions
// remove_all_actions('woocommerce_before_single_product');
// remove_all_actions('woocommerce_before_single_product_summary');
// remove_all_actions('woocommerce_single_product_summary');
// remove_all_actions('woocommerce_after_single_product_summary');
// remove_all_actions('woocommerce_after_single_product');
// add in main product image



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


// Jumbotrons
add_action('almar_jumbotron', 'almar_do_jumbotron', 10);
function almar_do_jumbotron() {
  // front page jumbotron
  if (is_front_page()) {
    get_template_part('templates/jumbotron-front-page');
    return;
  }

  // other jumbotrons
  global $post;
  $slug = $post->post_name;
  get_template_part('templates/jumbotron-'.$slug);
}

// Product single jumbotron
// add_action('almar_product_single_jumbotron', 'almar_do_product_single_jumbotron');
function almar_do_product_single_jumbotron() {
  if (!is_singular('product')) return;
  if (!has_post_thumbnail()) return;
  get_template_part('templates/jumbotron-product-single');
}


// Get output for cart nav link in header
function almar_header_nav_cart_link() {
  global $woocommerce;
  $cart_url = $woocommerce->cart->get_cart_url();
  $cart_count = $woocommerce->cart->cart_contents_count;
  $cart_total = $woocommerce->cart->get_cart_total();
  ob_start();
  ?>
  <a class="nav-cart-link" href="<?php echo $cart_url ?>" title="View shopping cart">
    <span class="glyphicon glyphicon-shopping-cart"></span> 
    <span class="cart-count">(<?php echo $cart_count ?>)</span> 
    <span class="cart-total"><?php echo $cart_total ?></span>
  </a>
  <?php 
  return ob_get_clean();    
}

// AJAX for cart link in header 
add_filter('add_to_cart_fragments', 'almar_woocommerce_header_add_to_cart_fragment');
function almar_woocommerce_header_add_to_cart_fragment($fragments) {
  $output = almar_header_nav_cart_link();
  $fragments['a.nav-cart-link'] = $output;
  return $fragments;
}


// Uservoice widget code
add_action('wp_footer', 'almar_uservoice_widget', 1000);
function almar_uservoice_widget() {
  get_template_part('templates/uservoice-widget');
}
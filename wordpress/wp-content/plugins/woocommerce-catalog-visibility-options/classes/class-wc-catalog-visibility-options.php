<?php
class WC_CVO_Visibility_Options {
	public function __construct() {
		add_action('woocommerce_init', array(&$this, 'on_woocommerce_init'));
	}

	public function on_woocommerce_init() {
		global $wc_cvo;

		if ($wc_cvo->setting('wc_cvo_prices') != 'enabled' || $wc_cvo->setting('wc_cvo_atc') != 'enabled') {

			if (($wc_cvo->setting('wc_cvo_prices') == 'secured' && !catalog_visibility_user_has_access()) || $wc_cvo->setting('wc_cvo_prices') == 'disabled') {
				add_filter('woocommerce_grouped_price_html', array(&$this, 'on_price_html'), 100, 2);
				add_filter('woocommerce_variable_price_html', array(&$this, 'on_price_html'), 100, 2);
				add_filter('woocommerce_sale_price_html', array(&$this, 'on_price_html'), 100, 2);
				add_filter('woocommerce_price_html', array(&$this, 'on_price_html'), 100, 2);
				add_filter('woocommerce_empty_price_html', array(&$this, 'on_price_html'), 100, 2);

				add_filter('woocommerce_variable_sale_price_html', array(&$this, 'on_price_html'), 100, 2);
				add_filter('woocommerce_variable_free_sale_price_html', array(&$this, 'on_price_html'), 100, 2);
				add_filter('woocommerce_variable_free_price_html', array(&$this, 'on_price_html'), 100, 2);
				add_filter('woocommerce_variable_empty_price_html', array(&$this, 'on_price_html'), 100, 2);

				add_filter('woocommerce_free_sale_price_html', array(&$this, 'on_price_html'), 100, 2);
				add_filter('woocommerce_free_price_html', array(&$this, 'on_price_html'), 100, 2);

				if (wc_is_21x()) {
					//2.1x
					add_filter('woocommerce_cart_item_price', array($this, 'on_cart_item_price_html'), 100, 2);
				} else {
					//2.0x
					add_filter('woocommerce_cart_item_price_html', array($this, 'on_cart_item_price_html'), 100, 2);
				}
			}

			//Configure replacement HTML and content.  
			//Note:  If prices are disabled, and purchases are enabled, the alternate add-to-cart button content will still be used. 
			//       Add to cart only makes sense when prices are visibile. 
			if (
				(($wc_cvo->setting('wc_cvo_atc') == 'secured' && !catalog_visibility_user_has_access()) || $wc_cvo->setting('wc_cvo_atc') == 'disabled') ||
				(($wc_cvo->setting('wc_cvo_prices') == 'secured' && !catalog_visibility_user_has_access()) || $wc_cvo->setting('wc_cvo_prices') == 'disabled')
			) {

				//Bulk variations compatibility
				add_filter('woocommerce_bv_render_form', '__return_false');

				//Wishlist compatibility
				add_action('woocommerce_wishlist_user_can_purcahse', array($this, 'on_wishlist_user_can_purcahse'), 100, 2);


				add_action('woocommerce_before_shop_loop_item', array(&$this, 'before_shop_loop_item'), 0);

				add_action('woocommerce_before_add_to_cart_button', array(&$this, 'on_before_add_to_cart_button'), 1);
				add_action('woocommerce_after_add_to_cart_button', array(&$this, 'on_after_add_to_cart_button'), 999);



				remove_shortcode('woocommerce_cart');
				remove_shortcode('woocommerce_checkout');
				remove_shortcode('woocommerce_order_tracking');

				add_shortcode('woocommerce_cart', array(&$this, 'get_woocommerce_cart'));
				add_shortcode('woocommerce_checkout', array(&$this, 'get_woocommerce_checkout'));
				add_shortcode('woocommerce_order_tracking', array(&$this, 'get_woocommerce_order_tracking'));
			}
		}
	}

	/*
	 * Replacement Shortcodes
	 */
	public function get_woocommerce_cart($atts) {
		global $woocommerce;
		return $woocommerce->shortcode_wrapper(array(&$this, 'alternate_single_product_content'), $atts);
	}

	public function get_woocommerce_checkout($atts) {
		global $woocommerce;
		return $woocommerce->shortcode_wrapper(array(&$this, 'alternate_single_product_content'), $atts);
	}

	public function get_woocommerce_order_tracking($atts) {
		global $woocommerce;
		return $woocommerce->shortcode_wrapper(array(&$this, 'alternate_single_product_content'), $atts);
	}

	public function alternate_single_product_content($atts) {
		global $wc_cvo;

		$html = '';

		if (($wc_cvo->setting('wc_cvo_prices') == 'secured' && !catalog_visibility_user_has_access()) || $wc_cvo->setting('wc_cvo_prices') == 'disabled') {
			$html = apply_filters('catalog_visibility_alternate_content', apply_filters('the_content', $wc_cvo->setting('wc_cvo_s_price_text')));
		} elseif (($wc_cvo->setting('wc_cvo_atc') == 'secured' && !catalog_visibility_user_has_access()) || $wc_cvo->setting('wc_cvo_atc') == 'disabled') {
			$html = apply_filters('catalog_visibility_alternate_content', apply_filters('the_content', $wc_cvo->setting('wc_cvo_s_price_text')));
		}

		echo $html;
	}

	/*
	 * Replacement HTML
	 */
	public function on_price_html($html, $product) {
		global $wc_cvo;
		if (!WC_Catalog_Restrictions_Filters::instance()->user_can_view_price($product)) {
			if (($wc_cvo->setting('wc_cvo_prices') == 'secured' && !catalog_visibility_user_has_access()) || $wc_cvo->setting('wc_cvo_prices') == 'disabled') {
				return apply_filters('catalog_visibility_alternate_price_html', do_shortcode(wptexturize($wc_cvo->setting('wc_cvo_c_price_text'))));
			}
		}

		return $html;
	}

	public function on_cart_item_price_html($price, $cart_item) {
		global $wc_cvo;
		$product = $cart_item['data'];

		if (!WC_Catalog_Restrictions_Filters::instance()->user_can_view_price($product)) {
			if (($wc_cvo->setting('wc_cvo_prices') == 'secured' && !catalog_visibility_user_has_access()) || $wc_cvo->setting('wc_cvo_prices') == 'disabled') {
				return apply_filters('catalog_visibility_alternate_price_html', do_shortcode(wptexturize($wc_cvo->setting('wc_cvo_c_price_text'))));
			}
		}

		return $price;
	}

	public function before_shop_loop_item() {
		global $wc_cvo, $product;
		if ($wc_cvo->setting('wc_cvo_atc') != 'enabled' && !WC_Catalog_Restrictions_Filters::instance()->user_can_purchase($product)) {
			remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
			add_action('woocommerce_after_shop_loop_item', array(&$this, 'on_after_shop_loop_item'));
		}
	}

	public function on_after_shop_loop_item() {
		global $post, $product, $wc_cvo;

		if (WC_Catalog_Restrictions_Filters::instance()->user_can_purchase($product)) {
			$label = wptexturize($wc_cvo->setting('wc_cvo_atc_text'));
			if (empty($label)) {
				return;
			}

			$link = get_permalink($post->ID);

			echo apply_filters('catalog_visibility_alternate_add_to_cart_link', sprintf('<a href="%s" data-product_id="%s" class="button product_type_%s">%s</a>', $link, $product->id, $product->product_type, $label));
		}

		add_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
		remove_action('woocommerce_after_shop_loop_item', array(&$this, 'on_after_shop_loop_item'));
	}

	public function on_before_add_to_cart_button() {
		global $product;
		if (!WC_Catalog_Restrictions_Filters::instance()->user_can_purchase($product)) {
			$this->buffer_on = ob_start();
		}
	}

	public function on_after_add_to_cart_button() {
		global $wc_cvo, $post, $product;
		if (!WC_Catalog_Restrictions_Filters::instance()->user_can_purchase($product)) {

			if ($this->buffer_on) {
				ob_end_clean();
			}

			// Variable product price handling
			if ($product->is_type('variable')) {
				?>
				<div class="single_variation_wrap" style="display:none;">
					<?php if (($wc_cvo->setting('wc_cvo_prices') == 'secured' && WC_Catalog_Restrictions_Filters::instance()->user_can_view_price($product)) || $wc_cvo->setting('wc_cvo_prices') == 'enabled') : ?>
						<div class="single_variation"></div>
					<?php endif; ?>
					<div class="variations_button">
						<input type="hidden" name="variation_id" value="" />
					</div>
				</div>
				<div><input type="hidden" name="product_id" value="<?php echo esc_attr($post->ID); ?>" /></div>
				<?php
			}

			$html = apply_filters('catalog_visibility_alternate_add_to_cart_button', do_shortcode(wpautop(wptexturize($wc_cvo->setting('wc_cvo_s_price_text')))));

			echo $html;
		}
	}

	public function on_wishlist_user_can_purcahse($result, $product) {
		return $result & WC_Catalog_Restrictions_Filters::instance()->user_can_purchase($product);
	}

}
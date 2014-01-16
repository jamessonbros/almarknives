<?php

/**
 * woocommerce_product_addons class
 * */
if (!class_exists('WC_Catalog_Restrictions')) {

	class WC_Catalog_Restrictions {
		public $template_url;
		public $version = '1.0.2';

		public function __construct() {
			define('WOOCOMMERCE_CATALOG_RESTRICTIONS_VERSION', $this->version);

			$this->template_url = apply_filters('woocommerce_catalog_restrictions_template_url', 'woocommerce-catalog-visibility-options/');

			require 'woocommerce-catalog-restrictions-functions.php';
			require 'shortcodes/class-wc-catalog-restrictions-location-picker-shortcode.php';
			require 'widgets/class-wc-catalog-restrictions-location-picker-widget.php';

			WC_Catalog_Restrictions_Location_Picker_ShortCode::instance();

			WC_Catalog_Restrictions_Location_Picker_Widget::register();

			if (is_admin()) {

				require 'classes/class-wc-catalog-restrictions-product-admin.php';
				require 'classes/class-wc-catalog-restrictions-category-admin.php';


				WC_Catalog_Restrictions_Product_Admin::instance();
				WC_Catalog_Restrictions_Category_Admin::instance();
			} else {

				add_action('woocommerce_init', array($this, 'on_init'));

				require 'classes/class-wc-catalog-restrictions-query.php';
				require 'classes/class-wc-catalog-restrictions-filters.php';

				WC_Catalog_Restrictions_Query::instance();
				WC_Catalog_Restrictions_Filters::instance();
				//load after woocommerce

				if ($this->get_setting('_wc_restrictions_locations_enabled', 'no') == 'yes') {
					add_filter('template_redirect', array(&$this, 'template_redirect'), 999);
				}
			}

			if (is_admin() && !defined('DOING_AJAX')) {
				require 'woocommerce-catalog-restrictions-installer.php';
				$this->install();

				add_action('admin_init', array($this, 'on_admin_init'));
			}

			//Setup Hooks to clear transients when a post is saved, a category is saved, a user changes their location, a user is updated, a user logs on / out. 
			add_action('save_post', array(&$this, 'clear_transients'));
			add_action('created_term', array(&$this, 'clear_transients'));
			add_action('edit_term', array(&$this, 'clear_transients'));
			add_action('edit_user_profile_update', array(&$this, 'clear_transients'));

			add_action('wp_login', array(&$this, 'clear_session_transients'));
			add_action('wp_logout', array(&$this, 'clear_session_transients'));
			add_action('wc_restrictions_location_updated', array(&$this, 'clear_session_transients'));
		}

		public function install() {
			register_activation_hook(__FILE__, 'activate_woocommerce_catalog_restrictions');
			register_deactivation_hook(__FILE__, 'deactivate_woocommerce_catalog_restrictions');
			if (get_option('woocommerce_catalog_restrictions_db_version') != $this->version) {
				add_action('init', 'install_woocommerce_catalog_restrictions', 1);
			}
		}

		public function clear_transients() {
			global $wpdb;
			$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_twccr%'");
			$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_twccr%'");

			wp_cache_flush();
		}

		public function clear_session_transients() {
			global $wpdb, $woocommerce;
			
			$session_id = $woocommerce->session->get_customer_id();
			$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_twccr_" . $session_id . "%'");
			$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_twccr_" . $session_id . "%'");
			
			wp_cache_flush();
		}

		public function on_init() {
			global $woocommerce, $wpdb;

			if ($_POST && !empty($_POST)) {
				if (isset($_POST['save-location'])) {
					$location = $_POST['location'];

					$woocommerce->session->wc_location = $location;

					do_action('wc_restrictions_location_updated');

					if (isset($woocommerce->session->wc_catalog_restrictions_return_url)) {
						$url = $woocommerce->session->wc_catalog_restrictions_return_url;
						unset($woocommerce->session->wc_catalog_restrictions_return_url);
						wp_safe_redirect($url);
					} else {
						wp_safe_redirect(get_site_url());
					}

					die();
				} else {
					
				}
			}
		}

		public function on_admin_init() {
			global $wpdb;
			if (is_admin() && isset($_GET['wc_catalog_visibility_db_cleanup']) && current_user_can('administrator')) {

				$wc_term_meta_table = $wpdb->prefix . 'woocommerce_termmeta';
				$wpdb->query("DELETE FROM $wc_term_meta_table WHERE (meta_key = '_wc_restrictions' OR meta_key = '_wc_restrictions_allowed') AND (meta_value = '');");
				$wpdb->query("DELETE FROM $wc_term_meta_table WHERE (meta_key = '_wc_restrictions_location' OR meta_key = '_wc_restrictions_locations') AND (meta_value = '');");
			}
		}

		/*
		 * Template and Location Picker Function 
		 */
		public function get_location_for_current_user() {
			global $woocommerce;
			$location = isset($woocommerce->session->wc_location) ? $woocommerce->session->wc_location : '';
			return apply_filters('woocommerce_catalog_restrictions_get_user_location', $location);
		}

		public function template_redirect() {
			global $woocommerce;

			$location = $this->get_location_for_current_user();

			if (empty($location) && $this->get_setting('_wc_restrictions_locations_required', 'no') == 'yes') {
				$location_page_id = woocommerce_get_page_id('choose_location');
				if ($location_page_id && !is_page($location_page_id)) {
					$woocommerce->session->wc_catalog_restrictions_return_url = add_query_arg(array('locationset' => '1'));
					$location_url = get_permalink($location_page_id);
					if ($location_url) {
						wp_safe_redirect($location_url);
						exit;
					}
				}
			}

			if (is_page(woocommerce_get_page_id('choose_location'))) {
				
			}
		}

		/*
		 * Utility functions
		 */
		public function plugin_url() {
			return plugin_dir_url(__FILE__);
		}

		public function plugin_path() {
			return untrailingslashit(plugin_dir_path(__FILE__));
		}

		public function get_setting($key, $default = null) {
			return get_option($key, $default);
		}

	}

}

$GLOBALS['wc_catalog_restrictions'] = new WC_Catalog_Restrictions();
?>
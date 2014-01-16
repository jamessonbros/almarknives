<?php

class WC_Catalog_Restrictions_Query {
	private static $instance;

	public static function instance() {
		if (!self::$instance) {
			self::$instance = new WC_Catalog_Restrictions_Query();
		}
		return self::$instance;
	}

	public function __construct() {
		add_filter('posts_where', array(&$this, 'posts_where'), 10, 2);
	}

	public function is_bound_to_query($query) {
		$post_types = $query->get('post_type');
		return (empty($post_types) || $post_types == 'product' || $post_types == 'any' || (is_array($post_types) && (in_array('product', $post_types) || in_array('any', $post_types) ) ));
	}

	public function posts_where($where, &$query) {
		global $wc_catalog_restrictions, $wpdb, $woocommerce;

		if ($this->is_bound_to_query($query)) {
			/*
			 * These queries work by first finding all products that do not have any rules applied.  It looks at both the 
			 * category and the product meta to determine if there are any rules present. 
			 * 
			 * Second the query finds all products that have a restriction that is not set to public or where the meta_value for the assigned roles / locations
			 * is in the values we pass in. 
			 * 
			 * Third the query finds all proudcts which do not have specific product rules, because product rules override category rules, and filters where the
			 * taxonomy meta is in the list of values we pass in. 
			 * 
			 * Finally we modify the where statement of the main query to include only the product ID's we found. 
			 */

			$filtered = false;

			if ($wc_catalog_restrictions->get_setting('_wc_restrictions_locations_enabled', 'no') == 'yes') {

				$filtered = $this->get_cached_exclusions('l');
				if ($filtered === false) {

					$allowed_by_role = $this->query_allowed_product_ids_by_role();
					$allowed_by_location = $this->query_allowed_product_ids_by_location($allowed_by_role);

					if ($allowed_by_location) {
						$exclusion_sql = " SELECT ID FROM $wpdb->posts WHERE post_type='product' AND $wpdb->posts.ID NOT IN ('" . implode("','", $allowed_by_location) . "')";
						$filtered = $wpdb->get_col($exclusion_sql);
					}

					if ($filtered !== false) {
						$session_id = $woocommerce->session->get_customer_id();
						set_transient('twccr_' . $session_id . '_l', $filtered, 60 * 60 * 24);
					}
				}
			} else {
				$filtered = $this->get_cached_exclusions('r');
				if ($filtered === false) {
					$allowed_by_role = $this->query_allowed_product_ids_by_role();

					if ($allowed_by_role) {
						$exclusion_sql = " SELECT ID FROM $wpdb->posts WHERE post_type='product' AND $wpdb->posts.ID NOT IN ('" . implode("','", $allowed_by_role) . "')";
						$filtered = $wpdb->get_col($exclusion_sql);
					}

					if ($filtered !== false) {
						$session_id = $woocommerce->session->get_customer_id();
						set_transient('twccr_' . $session_id . '_r', $filtered, 60 * 60 * 24);
					}
				}
			}

			if ($filtered) {
				$where .= " AND $wpdb->posts.ID NOT IN ('" . implode("','", $filtered) . "')";
			}
		}
		return $where;
	}

	public function get_cached_exclusions($type, $session_id = false) {
		global $woocommerce;

		if ($session_id === false) {
			$session_id = $woocommerce->session->get_customer_id();
		}

		return get_transient('twccr_' . $session_id . '_' . $type);
	}

	public function get_roles_for_current_user() {
		$roles = array('guest');

		if (is_user_logged_in()) {
			$user = new WP_User(get_current_user_id());
			if (!empty($user->roles) && is_array($user->roles)) {
				foreach ($user->roles as $role) {
					$roles[] = $role;
				}
			}
		}

		return $roles;
	}

	public function get_locations_for_current_user() {
		global $wc_catalog_restrictions;
		$t_loc = $wc_catalog_restrictions->get_location_for_current_user();

		if (!is_array($t_loc)) {
			$t_loc = (array) $t_loc;
		}

		$locations = array();
		foreach ($t_loc as &$location) {
			$location = esc_sql($location);
			if (strstr($location, ':')) {
				$parts = explode(':', $location);
				foreach ($parts as $part) {
					$locations[] = $part;
				}
			} else {
				$locations[] = $location;
			}
		}

		$locations = apply_filters('woocommerce_catalog_restrictions_get_users_location_query', $locations);
		foreach ($locations as $location) {
			$location = empty($location) ? '-1' : esc_sql($location);
		}

		return $locations;
	}

	public function query_allowed_product_ids_by_role($pre_filtered_products = false) {
		global $wpdb;
		$user_roles = $this->get_roles_for_current_user();
		$role_sql = "
        SELECT DISTINCT $wpdb->posts.ID FROM $wpdb->posts INNER JOIN (
            SELECT DISTINCT post_id FROM $wpdb->postmeta 
                    WHERE post_id NOT IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wc_restrictions')
                    AND post_id NOT IN(SELECT DISTINCT tr.object_id FROM {$wpdb->prefix}woocommerce_termmeta 
                        INNER JOIN $wpdb->term_taxonomy tt on {$wpdb->prefix}woocommerce_termmeta.woocommerce_term_id = tt.term_id
                        INNER JOIN $wpdb->term_relationships tr on tt.term_taxonomy_id = tr.term_taxonomy_id
                        WHERE tt.taxonomy = 'product_cat' AND meta_key='_wc_restrictions')
                UNION ALL
            SELECT  post_id FROM $wpdb->postmeta 
                    WHERE (meta_key = '_wc_restrictions' AND meta_value = 'public') OR (meta_key = '_wc_restrictions_allowed' AND meta_value IN ('" . implode("','", $user_roles) . "'))
                UNION ALL 
            SELECT  tr.object_id FROM {$wpdb->prefix}woocommerce_termmeta 
                    INNER JOIN $wpdb->term_taxonomy tt on {$wpdb->prefix}woocommerce_termmeta.woocommerce_term_id = tt.term_id
                    INNER JOIN $wpdb->term_relationships tr on tt.term_taxonomy_id = tr.term_taxonomy_id
                    WHERE tt.taxonomy = 'product_cat' 
                    AND ( (meta_key='_wc_restrictions' AND meta_value='public') OR (meta_key = '_wc_restrictions_allowed' AND meta_value IN ('" . implode("','", $user_roles) . "')) )
                    AND tr.object_id NOT IN (SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key = '_wc_restrictions')
            ) as rfilter on $wpdb->posts.ID = rfilter.post_id WHERE post_type = 'product'";


		if ($pre_filtered_products) {
			$role_sql .= " AND ID IN (" . implode(',', $pre_filtered_products) . ")";
		}

		$allowed = $wpdb->get_col($role_sql);

		return !empty($allowed) ? $allowed : array(-1);
	}

	public function query_allowed_product_ids_by_location($pre_filtered_products = false) {
		global $wpdb;
		$user_locations = $this->get_locations_for_current_user();

		$location_sql =
			"SELECT DISTINCT $wpdb->posts.ID FROM $wpdb->posts INNER JOIN (
            SELECT post_id FROM $wpdb->postmeta 
                    WHERE post_id NOT IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wc_restrictions_location')
                    AND post_id NOT IN(SELECT DISTINCT tr.object_id FROM {$wpdb->prefix}woocommerce_termmeta 
                        INNER JOIN $wpdb->term_taxonomy tt on {$wpdb->prefix}woocommerce_termmeta.woocommerce_term_id = tt.term_id
                        INNER JOIN $wpdb->term_relationships tr on tt.term_taxonomy_id = tr.term_taxonomy_id
                        WHERE tt.taxonomy = 'product_cat' AND meta_key='_wc_restrictions_location')
                UNION ALL
            SELECT post_id FROM $wpdb->postmeta 
                    WHERE (meta_key = '_wc_restrictions_location' AND meta_value = 'public') OR (meta_key = '_wc_restrictions_locations' AND meta_value IN ('" . implode("','", $user_locations) . "'))
                UNION ALL 
            SELECT tr.object_id FROM {$wpdb->prefix}woocommerce_termmeta 
                    INNER JOIN $wpdb->term_taxonomy tt on {$wpdb->prefix}woocommerce_termmeta.woocommerce_term_id = tt.term_id
                    INNER JOIN $wpdb->term_relationships tr on tt.term_taxonomy_id = tr.term_taxonomy_id
                    WHERE tt.taxonomy = 'product_cat' 
                    AND ( (meta_key='_wc_restrictions_location' AND meta_value='public') OR (meta_key = '_wc_restrictions_locations' AND meta_value IN ('" . implode("','", $user_locations) . "')) )
                    AND tr.object_id NOT IN (SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key = '_wc_restrictions_location')
        ) as rfilter on $wpdb->posts.ID = rfilter.post_id WHERE post_type = 'product'";


		if ($pre_filtered_products) {
			$location_sql .= " AND ID IN (" . implode(',', $pre_filtered_products) . ")";
		}

		$allowed = $wpdb->get_col($location_sql);

		return !empty($allowed) ? $allowed : array(-1);
	}

}

?>
<?php
/**
Plugin Name: WooCommerce UserVoice Integration
Plugin URI: http://www.woothemes.com/extension/uservoice/
Description: UserVoice support desk integration for WooCommerce
Version: 1.1.2
Author: WooThemes
Author URI: http://www.woothemes.com
Requires at least: 3.0
Tested up to: 3.6

	Copyright: © 2009-2011 WooThemes.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '39cb81b85fd9d4b39b4abd934613cd44', '18721' );

if (is_woocommerce_active()) {

	global $uv_settings , $uv_api;

	$uv_settings = get_option( 'woocommerce_uservoice_settings' );

	/**
	 * Instantiate UV API functions
	 */
	require_once('uservoice.api.class.php');
	$uv_api = new UserVoice_API();

	/**
	 * Add shortcode for displaying ticket overview page
	 */
	add_shortcode( 'uservoice_tickets', array( $uv_api , 'ticket_overview_page' ) );

	/**
	 * Handle UV auth (saves browser cookie)
	 */
	if( isset( $_GET['uv_auth']) && $_GET['uv_auth'] == 'true' && !function_exists( 'uservoice_auth' ) ) {
		function uservoice_auth() {
			global $uv_api;
			$uv_api->auth( true );
		}
		add_action( 'init' , 'uservoice_auth' );
	}

	/**
	 * Add settings link
	 **/
	if ( ! function_exists( 'add_uservoice_settings_link' ) ) {
		function add_uservoice_settings_link( $links ) {
			$settings_link = '<a href="admin.php?page=woocommerce&tab=integration&section=uservoice">Settings</a>';
	  		array_unshift( $links, $settings_link );
	  		return $links;
		}
		$plugin = plugin_basename( __FILE__ );
		add_filter( 'plugin_action_links_' . $plugin, 'add_uservoice_settings_link' );
	}

	/**
	 * Add integration to WooCommerce
	 **/
	if(!function_exists('add_uservoice_integration')) {
		function add_uservoice_integration( $integrations ) {
			require_once('uservoice.admin.class.php');
			$integrations[] = 'UserVoice_Admin';
			return $integrations;
		}
		add_filter( 'woocommerce_integrations', 'add_uservoice_integration' );
	}

	/**
	 * Trigger SSO login to UserVoice
	 */
	if(!function_exists('uservoice_sso_login')) {
		function uservoice_sso_login() {
			global $uv_settings;

			if($uv_settings['uv_sso_enable'] == 'yes' && strlen($uv_settings['uv_sso_key']) > 0 && strlen($uv_settings['uv_sso_acc']) > 0 && strlen($uv_settings['uv_url']) > 0) {
				if(isset($_GET['uservoice_login']) && $_GET['uservoice_login'] == 1) {

					$current_user = wp_get_current_user();

					if ( is_user_logged_in() ) {

						$do_login = true;
						if( isset( $uv_settings['uv_sso_paying_customers'] ) && $uv_settings['uv_sso_paying_customers'] == 'yes' ) {
							$order_data = uservoice_get_order_data($current_user->ID);
							if( $order_data['total_orders'] == 0 || $order_data['total_spent'] == 0 || $order_data['given_orders'] ) {
								$do_login = false;
							}
						}

						if($do_login) {
							$account_key = $uv_settings['uv_sso_acc'];
							$sso_key = $uv_settings['uv_sso_key'];
							$salted = $sso_key . $account_key;
							$hash = hash('sha1',$salted,true);
							$salted_hash = substr($hash,0,16);

							//Expire SSO cookie in 30 days
							$expire = date('Y-m-d H:i:s' , time() + 60 * 60 * 24 * 30);

							//User ID
							$user_id = $current_user->ID;

							//User display name - set to username if no real name set
							if($current_user->display_name) {
								$user_name = $current_user->display_name;
							} else if($current_user->user_firstname || $current_user->user_lastname) {
								$user_name = $current_user->user_firstname . " " . $current_user->user_lastname;
							} else {
								$user_name = $current_user->user_login;
							}

							//User email address - set to false if does not exist
							$user_email = false;
							if($current_user->user_email) {
								$user_email = $current_user->user_email;
							}

							//Set admin access
							$admin_access = 'deny';
							$current_user_role = $current_user->roles[0];
							if(isset($uv_settings['uv_sso_admin']) && is_array($uv_settings['uv_sso_admin']) && count($uv_settings['uv_sso_admin']) > 0) {
								$admin_roles = $uv_settings['uv_sso_admin'];
								if(in_array($current_user_role, $admin_roles)) {
									$admin_access = 'accept';
								}
							} else {
								if($current_user_role == 'administrator') {
									$admin_access = 'accept';
								}
							}

							//Set up data
							$user_data = array(
							 	"guid" => $user_id,
						  		"expires" => $expire,
							 	"display_name" => $user_name,
							  	"email" => $user_email,
							  	"owner" => 'deny',
							  	"admin" => $admin_access,
							  	"updates" => true,
							  	"comment_updates" => true
							);

							$data = json_encode($user_data);

							if($data) {
								$encrypted_data = uservoice_sso_encryption($data, $salted_hash);

								if(strlen($encrypted_data) > 0) {
									$sso_url = $uv_settings['uv_url'] . "?sso=" . $encrypted_data;
									$redirect = $sso_url;
								}
							}
						}
					} else {
						//If not logged in then go to login page and redirect back through this function after login
						$redirect = wp_login_url( '/?uservoice_login=1' );
					}

					//If no redirect URL is set then go to site home page
					if(!$redirect) {
						$redirect = get_site_url();
					}

					wp_redirect($redirect);
					exit;
				}
			}
		}
		add_action('init', 'uservoice_sso_login');
	}

	/**
	 * Encrypt user data for SSO login (encryption logic provided by UserVoice)
	 */
	if(!function_exists('uservoice_sso_encryption')) {
		function uservoice_sso_encryption($data, $salted_hash) {
			$iv = "OpenSSL for Ruby";

			// double XOR first block
			for ($i = 0; $i < 16; $i++) {
				$data[$i] = $data[$i] ^ $iv[$i];
			}

			$pad = 16 - (strlen($data) % 16);
			$data = $data . str_repeat(chr($pad), $pad);

			$cipher = mcrypt_module_open(MCRYPT_RIJNDAEL_128,'','cbc','');
			mcrypt_generic_init($cipher, $salted_hash, $iv);
			$encrypted_data = mcrypt_generic($cipher,$data);
			mcrypt_generic_deinit($cipher);

			$encrypted_data = urlencode(base64_encode($encrypted_data));

			return $encrypted_data;
		}
	}

	/**
	 * Display feedback tab
	 */
	if(!function_exists('uservoice_feedback_tab')) {
		function uservoice_feedback_tab() {
			if(!is_admin()) {
				global $uv_settings;
				if(isset($uv_settings['uv_feedback_tab']) && strlen($uv_settings['uv_feedback_tab']) > 0 ) {

					$show_tab = true;
					if($uv_settings['uv_sso_paying_customers'] && $uv_settings['uv_sso_paying_customers'] == 'yes') {
						$order_data = uservoice_get_order_data($current_user->ID);
						if( $order_data['total_orders'] == 0 || $order_data['total_spent'] == 0 || $order_data['given_orders'] ) {
							$show_tab = false;
						}
					}

					if($show_tab) {
						$unique_id = woocommerce_clean($uv_settings['uv_feedback_tab']);

						if(strlen($unique_id) <= 22 && ctype_alnum($unique_id)) {

							$js_include = "<script type=\"text/javascript\">
							  var uvOptions = {};
							  (function() {
							    var uv = document.createElement('script'); uv.type = 'text/javascript'; uv.async = true;
							    uv.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'widget.uservoice.com/".$unique_id.".js';
							    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(uv, s);
							  })();
							</script>";

							echo $js_include;
						}
					}
				}
			}
		}
		add_action('wp_footer', 'uservoice_feedback_tab');
	}

	/**
	 * Display WooCommerce gadget in UserVoice Inspector
	 */
	if(!function_exists('uservoice_woocommerce_gadget')) {
		function uservoice_woocommerce_gadget() {
			global $uv_settings;
			if($uv_settings['uv_wc_gadget'] == 'yes') {
				if(isset($_GET['uv_wc_user_gadget']) && $_GET['uv_wc_user_gadget'] == 1 && isset($_GET['email']) && strlen($_GET['email']) > 0) {

					//Set content type to HTML (required for UV gadgets)
					header('Content-Type: text/html; charset=iso-8859-1');

					//Match user by email address
					$user = get_user_by('email', $_GET['email']);

					if($user) {

						//Get complete order data
						$order_data = uservoice_get_order_data($user->ID);

						//Get total orders placed
						$total_orders = 0;
						if($order_data['total_orders']) {
							$total_orders = $order_data['total_orders'];
						}

						//Format total spent
						$currency = get_option( 'woocommerce_currency' );
						$total_spent = woocommerce_price($order_data['total_spent']);

						//Format sign up date
						$member_since = date("j F Y", strtotime($user->data->user_registered));

						?>
						<!DOCTYPE html>
						<html>
							<head>
						    	<title>User Info</title>
						    	<link href="https://cdn.uservoice.com/packages/gadget.css" media="all" rel="stylesheet" type="text/css" />
							</head>
						 	<body>
						    	<div>
						    		<?php if(isset($uv_settings['uv_wc_gadget_username']) && $uv_settings['uv_wc_gadget_username'] == 'yes') { ?>
						    			<b>WP Username:</b> <?php echo $user->user_login; ?><br/>
						    		<?php } ?>
						    		<?php if(isset($uv_settings['uv_wc_gadget_userid']) && $uv_settings['uv_wc_gadget_userid'] == 'yes') { ?>
						    			<b>WP User ID:</b> <?php echo $user->ID; ?><br/>
						    		<?php } ?>
						    		<?php if(isset($uv_settings['uv_wc_gadget_membersince']) && $uv_settings['uv_wc_gadget_membersince'] == 'yes') { ?>
						    			<b>Member since:</b> <?php echo $member_since; ?><br/>
						    		<?php } ?>
						    		<?php if(isset($uv_settings['uv_wc_gadget_totalorders']) && $uv_settings['uv_wc_gadget_totalorders'] == 'yes') { ?>
						    			<b>Orders placed:</b> <?php echo $total_orders; ?><br/>
						    		<?php } ?>
						    		<?php if(isset($uv_settings['uv_wc_gadget_totalspent']) && $uv_settings['uv_wc_gadget_totalspent'] == 'yes') { ?>
						    			<b>Total spent:</b> <?php echo $total_spent; ?><br/>
						    		<?php } ?>
						    	</div>

						    	<?php if(isset($uv_settings['uv_wc_gadget_products']) && $uv_settings['uv_wc_gadget_products'] == 'yes') { ?>
						    	<div>
						    		<br/>
						    		<b>Products purchased:</b><br/>
						    		<ul>
						    			<?php
						    			if($order_data['products'] && is_array($order_data['products']) && count($order_data['products']) > 0) {
							    			foreach($order_data['products'] as $id => $name) {
							    				echo '<li>'.$name.'</li>';
							    			}
							    		} else {
							    			echo '<li><em>None</em></li>';
							    		}
						    			?>
									</ul>
						    	</div>
						    	<?php } ?>

								<script src="https://cdn.uservoice.com/packages/gadget.js" type="text/javascript"></script>
							</body>
						</html>
						<?php
					} else {
						//Return this HTML to hide the gadget if no user data is available
						?>
						<!DOCTYPE html>
						<html>
							<head>
						    	<title>User Info (no data available)</title>
							</head>
						 	<body>
						      	<script type="text/javascript">
						        	window.gadgetNoData = true;
						      	</script>
							</body>
						</html>

						<?php
					}
					exit;
				}
			}
		}
		add_action('init', 'uservoice_woocommerce_gadget');
	}

	/**
	 * Get list of user's purchased products
	 */
	if(!function_exists('uservoice_get_order_data')) {
		function uservoice_get_order_data($userid) {
			if(isset($userid)) {

				//Get all orders made by user
				$args = array(
					'post_type' => 'shop_order',
					'meta_key' => '_customer_user',
					'meta_value' => intval($userid),
				);
				$query = new WP_Query( $args );

				//Loop through orders
				$total_orders = 0;
				$total_spent = 0;
				$given_orders = false;
				while ( $query->have_posts() ) { $query->the_post();

					//Get total order count
					$total_orders++;

					//Get total order amount
					$order_amount = get_post_meta(get_the_ID(), '_order_total', true);
					$total_spent += $order_amount;

					//Get given status (only applicable when using GIve Products extension)
					$given_order = get_post_meta( get_the_ID() , '_given_order', true );
					if( $given_order && $given_order == 1 ) {
						$given_orders = true;
					}

					//Get array of order items
					$items = (array) maybe_unserialize( get_post_meta(get_the_ID(), '_order_items', true) );
					foreach($items as $item) {
						$order_data['products'][$item['id']] = $item['name'];
					}
				}

				$order_data['total_orders'] = intval($total_orders);
				$order_data['total_spent'] = $total_spent;
				$order_data['given_orders'] = $given_orders;

				if(is_array($order_data) && count($order_data) > 0) {
					return $order_data;
				}
			}
			return false;
		}
	}

}
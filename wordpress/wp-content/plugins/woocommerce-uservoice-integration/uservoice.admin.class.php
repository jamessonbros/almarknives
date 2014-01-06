<?php
/**
 * UserVoice admin functions
 *
 * Handles UserVoice settings
 *
 * @version 1.0.0
 * @category Plugins
 * @package WordPress
 * @subpackage WooFramework
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - var $plugin_id
 * - var $settings
 * - var $form_fields
 * - var $id
 * - var $method_title
 * - var $method_description
 
 * - Constructor()
 * - get_user_roles_select_box()
 * - init_form_fields()
 */

if (!class_exists('UserVoice_Admin')) {

	class UserVoice_Admin extends WC_Integration {

		var $plugin_id = 'woocommerce_';
		var $settings = array();
		var $form_fields = array();
		var $id;
		var $method_title;
		var $method_description;

		public function __construct() {
	        $this->id					= 'uservoice';
	        $this->method_title     	= __( 'UserVoice', 'woocommerce' );
	        $this->method_description	= __( 'UserVoice is a support desk and knowledge base service. Sign up here: <a href="http://www.uservoice.com/" target="_blank">http://www.uservoice.com</a>.', 'woocommerce' );
			
			// Load the form fields.
			$this->init_form_fields();
			
			// Load the settings.
			$this->init_settings();
			
			// Actions
			add_action( 'woocommerce_update_options_integration_uservoice', array( &$this, 'process_admin_options') );
	    }

	    /**
	     * Get list of user roles for select box
	     * @return array User role list
	     */
	    private function get_user_roles_select_box() {
	    	$roles = new WP_Roles();
	    	foreach( $roles as $role)  {
	    		if( is_array( $role ) ) {
		    		foreach( $role as $slug => $details ) {
		    			if( is_array( $details ) ) {
		    				//Only show roles that are able to edit posts
		    				if( isset( $details['capabilities']['edit_posts'] ) && $details['capabilities']['edit_posts'] == 1 ) {
			    				$role_list[$slug] = str_replace( '|User role' , '' , $details['name'] );
			    			}
			    		}
		    		}
		    	}
	    	}

	    	if( is_array( $role_list ) ) {
		    	return $role_list;
		    }

		    return false;
	    }

	    /**
	     * Initialise form settings fields
	     * @return void
	     */
	    function init_form_fields() {

	    	$user_roles = $this->get_user_roles_select_box();
	    	
	    	$this->form_fields = array(
	    		'uv_sso_enable' => array(  
					'title' 			=> __('Enable UserVoice SSO', 'woocommerce'),
					'label' 			=> __('Allow users to sign in to UserVoice using their WordPress account (a process known as single sign-on or SSO) - will only work if you specify your UserVoice URL, SSO key and account name below.<br/>In order for this to work you must set your SSO remote sign-in URL in your UserVoice settings to <u>'.get_site_url().'/?uservoice_login=1</u> and the remote sign-out URL to: <u>'.wp_logout_url().'</u>', 'woocommerce'),
					'type' 				=> 'checkbox',
					'checkboxgroup'		=> 'sso',
					'default' 			=> get_option('woocommerce_uv_sso_enable') ? get_option('woocommerce_uv_sso_enable') : 'no'  // Backwards compat
				),
				'uv_sso_paying_customers' => array(  
					'title' 			=> __('Restrict SSO & feedback tab to paying customers only', 'woocommerce'),
					'label' 			=> __('Enabling this will make sure that only paying customers (i.e. any customers who have completed an order) will be able to log in to UserVoice via SSO as well as have the feedback tab available to them (enable feedback tab below).', 'woocommerce'),
					'type' 				=> 'checkbox',
					'checkboxgroup'		=> 'sso',
					'default' 			=> get_option('woocommerce_uv_sso_enable') ? get_option('woocommerce_uv_sso_enable') : 'no'  // Backwards compat
				),
				'uv_url' => array(  
					'title' 			=> __('UserVoice URL', 'woocommerce'),
					'description' 		=> __('The URL for your UserVoice setup - e.g. http://support.woothemes.com. Required if you have SSO enabled.', 'woocommerce'),
					'type' 				=> 'text',
			    	'default' 			=> 'http://' // Backwards compat
				),
	    		'uv_sso_key' => array(  
					'title' 			=> __('UserVoice SSO key', 'woocommerce'),
					'description' 		=> __('SSO key for your account. Required if you have SSO enabled.', 'woocommerce'),
					'type' 				=> 'text',
			    	'default' 			=> '' // Backwards compat
				),
	    		'uv_sso_acc' => array(  
					'title' 			=> __('UserVoice account name', 'woocommerce'),
					'description' 		=> __('UserVoice account name. You will find this in your default UserVoice URL - e.g. account_name.uservoice.com. Required if you have SSO enabled.', 'woocommerce'),
					'type' 				=> 'text',
			    	'default' 			=> '' // Backwards compat
				),
				'uv_sso_admin' => array(  
					'title' 			=> __( 'User permission levels for UserVoice admins', 'woocommerce' ),
					'description' 		=> '<br/>' . __( 'Choose the user roles on your WordPress site that will be logged in to UserVoice as administrators - this means that they will be able to respond to support tickets as well as edit the UserVoice account details.', 'woocommerce' ),
					'default' 			=> 'administrator',
					'type' 				=> 'multiselect',
					'class' 			=> 'chosen_select',
					'css' 				=> 'width: 450px;',
					'options' 			=> $user_roles
				),
	    		'uv_feedback_tab' => array(  
					'title' 			=> __('UserVoice feedback tab unique ID', 'woocommerce'),
					'description' 		=> __('This is the unique ID that is part of the feedback tab code provided by UserVoice. You will find the full code under Settings &gt; Channels in your UserVoice admin console and inside the code is a line that reads: \'widget.uservoice.com/UNIQUE_ID_HERE.js\' - copy the unique ID from that section and paste it in here (ID will be no more than 22 alpha-numeric characters). Leave this field blank to not show the feedback tab.', 'woocommerce'),
					'type' 				=> 'text',
			    	'default' 			=> '' // Backwards compat
				),
				'uv_wc_gadget' => array(  
					'title' 			=> __('Enable WooCommerce gadget in UserVoice Inspector', 'woocommerce'),
					'label' 			=> __('Enabling this will display a gadget in the User Inspector (sidebar next to each ticket in UserVoice) that will provide you with information about the user who logged the ticket. The gadget identifies users by their email address, so will only work if their email address on UserVoice is the same as their email address on your store - users signed in via SSO will always have the correct email address assigned to them.<br/>To activate the gadget on UserVoice once you have enabled it here, go to your Admin Console and navigate to Settings &gt; Integrations then click on \'Custom Gadget...\' at the bottom of the page. Give the gadget any name you like and use this as the URL: <u>'.get_site_url().'/?uv_wc_user_gadget=1</u><br/><br/><b>Select items to display in gadget:</b>', 'woocommerce'),
					'type' 				=> 'checkbox',
					'checkboxgroup'		=> 'gadget',
					'default' 			=> get_option('woocommerce_uv_sso_gadget') ? get_option('woocommerce_uv_sso_gadget') : 'no'  // Backwards compat
				),
				'uv_wc_gadget_username' => array(  
					'title' 			=> __('', 'woocommerce'),
					'label' 			=> __('User\'s WordPress username', 'woocommerce'),
					'type' 				=> 'checkbox',
					'checkboxgroup'		=> 'gadget_options',
					'default' 			=> get_option('uv_wc_gadget_username') ? get_option('uv_wc_gadget_username') : 'yes'  // Backwards compat
				),
				'uv_wc_gadget_userid' => array(  
					'title' 			=> __('', 'woocommerce'),
					'label' 			=> __('User\'s WordPress ID', 'woocommerce'),
					'type' 				=> 'checkbox',
					'checkboxgroup'		=> 'gadget_options',
					'default' 			=> get_option('uv_wc_gadget_userid') ? get_option('uv_wc_gadget_userid') : 'yes'  // Backwards compat
				),
				'uv_wc_gadget_membersince' => array(  
					'title' 			=> __('', 'woocommerce'),
					'label' 			=> __('Date on which the user became a member of your site', 'woocommerce'),
					'type' 				=> 'checkbox',
					'checkboxgroup'		=> 'gadget_options',
					'default' 			=> get_option('uv_wc_gadget_membersince') ? get_option('uv_wc_gadget_membersince') : 'yes'  // Backwards compat
				),
				'uv_wc_gadget_totalorders' => array(  
					'title' 			=> __('', 'woocommerce'),
					'label' 			=> __('Total amount of orders placed by the user', 'woocommerce'),
					'type' 				=> 'checkbox',
					'checkboxgroup'		=> 'gadget_options',
					'default' 			=> get_option('uv_wc_gadget_totalorders') ? get_option('uv_wc_gadget_totalorders') : 'yes'  // Backwards compat
				),
				'uv_wc_gadget_totalspent' => array(  
					'title' 			=> __('', 'woocommerce'),
					'label' 			=> __('Total amount the user has spent at your store', 'woocommerce'),
					'type' 				=> 'checkbox',
					'checkboxgroup'		=> 'gadget_options',
					'default' 			=> get_option('uv_wc_gadget_totalspent') ? get_option('uv_wc_gadget_totalspent') : 'yes'  // Backwards compat
				),
				'uv_wc_gadget_products' => array(  
					'title' 			=> __('', 'woocommerce'),
					'label' 			=> __('Products purchased by the user', 'woocommerce'),
					'type' 				=> 'checkbox',
					'checkboxgroup'		=> 'gadget_options',
					'default' 			=> get_option('uv_wc_gadget_products') ? get_option('uv_wc_gadget_products') : 'yes'  // Backwards compat
				),
	    		'uv_api_key' => array(  
					'title' 			=> __('API details for ticket overview page:', 'woocommerce'),
					'description' 		=> __('API key', 'woocommerce'),
					'type' 				=> 'text',
			    	'default' 			=> '' // Backwards compat
				),
	    		'uv_api_secret' => array(  
					'title' 			=> __('', 'woocommerce'),
					'description' 		=> __('API secret', 'woocommerce'),
					'type' 				=> 'text',
			    	'default' 			=> '' // Backwards compat
				),
	    		'uv_api_request_url' => array(  
					'title' 			=> __('', 'woocommerce'),
					'description' 		=> __('Request Token URL', 'woocommerce'),
					'type' 				=> 'text',
			    	'default' 			=> '' // Backwards compat
				),
	    		'uv_api_access_url' => array(  
					'title' 			=> __('', 'woocommerce'),
					'description' 		=> __('Access Token URL', 'woocommerce'),
					'type' 				=> 'text',
			    	'default' 			=> '' // Backwards compat
				),
	    		'uv_api_accname' => array(  
					'title' 			=> __('', 'woocommerce'),
					'description' 		=> __('UserVoice account name. You will find this in your default UserVoice URL - e.g. account_name.uservoice.com.', 'woocommerce'),
					'type' 				=> 'text',
			    	'default' 			=> '' // Backwards compat
				),
	    		'uv_api_forum_link' => array(  
					'title' 			=> __('', 'woocommerce'),
					'description' 		=> __('Your UserVoice URL.', 'woocommerce'),
					'type' 				=> 'text',
			    	'default' 			=> '' // Backwards compat
				),
	    		'uv_api_reply_msg' => array(  
					'title' 			=> __('', 'woocommerce'),
					'description' 		=> __('Message to instruct users on how to reply to tickets. Basic HTML allowed.', 'woocommerce'),
					'type' 				=> 'textarea',
					'css' 				=> 'width: 450px;',
			    	'default' 			=> 'To respond to this ticket you must reply to the one of the emails you received from our support system that is related to it.' // Backwards compat
				),
			);
			
	    } // End init_form_fields()	    
	}
}

?>
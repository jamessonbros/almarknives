<?php
/*
Plugin Name: ShortCodes UI
Plugin URI: http://en.bainternet.info
Description: Admin UI for creating ShortCodes in WordPress removing the need for you to write any code.
Version: 1.9.8
Author: Bainternet
Author URI: http://en.bainternet.info
*/
if ( !class_exists('BA_ShortCode_Maker')){
	class BA_ShortCode_Maker {
		
		/**
	 	* Holds the shortcodes tags
	 	*
	 	* @var array()
	 	* @access public
	 	*/
		public $sc_tags;
		
		/**
	 	* Holds the css,js per shortcode
	 	*
	 	* @var array()
	 	* @access public
	 	*/
		public $sc_media;
		
		/**
	 	* Holds the css,js per shortcode per location
	 	*
	 	* @var array()
	 	* @access public
	 	*/
		public $sc_external;

		/**
		 * $cpt_name 
		 *
		 * Holds the name of the custom post type
		 * @since 1.9.2
		 * @var string
		 */
		public $cpt_name = 'ba_sh';
		/**
         * $dir 
         * 
         * olds plugin directory
         * @since 1.9.2
         * @var string
         */
		public $dir = '';
		/**
		 * $url 
		 * 
		 * holds assets url
		 * @since 1.9.2
		 * @var string
		 */
        public $url = '';

		//constarctor
	    public function __construct() {
	    	$this->dir = plugin_dir_path(__FILE__);
	    	$this->url = plugins_url('', __FILE__);
	    	$this->sc_media = array();
	    	$this->sc_external = array();
	    	$this->sc_tags = array();
	    	$isadmin = is_admin();
	    	
	    	$this->hooks();
			
			//export import functions
			/* @todo implement a single shortcode export from row actions*/
			//add_filter('post_row_actions',array($this,'Export_shortcodes_Row_action'), 10, 2);
	    }

	    /**
	     * hooks 
	     *
	     * function to manage plugin hooks
	     * @since 1.9.2
	     * @return void
	     */
	    public function hooks(){
	    	//ajax hook up
	    	$this->ajax_hooks();

	    	if (is_admin())
	    		$this->admin_hooks();
	    	else
	    		$this->client_hooks();

	    	//register shortcodes
			add_action('init',array($this,'load_shortcodes'),30);
	    	//autoP fix
	    	add_filter('after_theme_setup',array($this,'autop_fix'));
	    	//widget class
	    	include_once($this->dir.'classes/widget.class.php');
	    }

	    /**
	     * is_edit_page 
	     *
	     * checks if the current page is an edit page
	     * @since 1.9.2
	     * @return boolean returns true on edit or new post page
	     */
	    public function is_edit_page(){
	    	global $pagenow;
	    	if(is_admin() && ($pagenow=='post-new.php' || $pagenow=='post.php') )
	    		return true;
	    	return false;
	    }

	    /**
	     * ajax_hooks 
	     *
	     * function to load and hook ajax class
	     * @since 1.9.2
	     * @return void
	     */
	    public function ajax_hooks(){
	    	if (!class_exists('shortcode_UI_ajax'))
	    		include_once($this->dir.'classes/shortcodes.ui.ajax.class.php');
	    	$ajax = new shortcode_UI_ajax();
	    }

	   
	    /**
	     * admin_hooks 
	     *
	     * function to load admin side hooks
	     * 	     
	     * @since 1.9.2
	     * @return void
	     */
	    public function admin_hooks(){

	    	//register shortcode type
	    	add_action( 'init', array($this,'register_customs'),20 );
	    	
	    	
	    	//admin menu export page
	    	add_action('admin_menu', array($this,'sc_ui_import_export_menupage'));
			add_action('admin_print_scripts-ba_sh_page_sc_ui_ie',array($this,'sc_ui_import_export_scripts'));
			add_action('admin_print_styles-ba_sh_page_sc_ui_ie',array($this,'sc_ui_import_export_styles'));
			
	    	//help tabs
			global $wp_version;
			if ( $wp_version >= 3.3 ) {
				add_action('load-post.php',array(&$this, 'shui_add_help_tab'));
				add_action('load-post-new.php',array(&$this, 'shui_add_help_tab'));
				add_action('load-edit.php',array(&$this, 'shui_add_help_tab'));
			}

			//change custom post type title
			add_filter('enter_title_here',array($this,'custom_enter_title'));
			//update messages
			add_filter('post_updated_messages',array($this, 'sh_updated_messages'));
			//manage columns
	    	add_filter('manage_edit-ba_sh_columns', array($this,'add_new_sc_columns'));
			add_action('manage_ba_sh_posts_custom_column', array($this, 'manage_sc_columns'), 10, 2);

			//tinymce button
			global $typenow; 
			if($this->is_edit_page()){
				add_filter( 'mce_buttons', array($this,'Add_custom_buttons' ));
				add_filter( 'tiny_mce_before_init', array($this,'Insert_custom_buttons' ));
				//add_filter('admin_footer',array($this,'insert_shortcode_button'));
				add_action('admin_print_scripts',array($this,'register_scripts'));
				add_action('admin_print_styles',array($this,'register_styles'));
			}
			add_action('load-widgets.php',array($this,'register_styles'));
			add_action('load-widgets.php',array($this,'register_scripts'));
			//metaboxes
			add_action('init',array($this,'load_meta_box'));
	    }

	    /**
	     * client_hooks 
	     *
	     * function to load client side hooks
	     * 
	     * @since 1.9.2
	     * @return void
	     */
	    public function client_hooks(){
			//setup scripts and styles // the_posts gets triggered before wp_head
			add_filter('the_posts', array($this, 'conditionally_add_scripts_and_styles'));
			//add scripts and style
			add_action('wp_footer',array($this,'print_footer_Scripts'));
			add_action('wp_footer',array($this,'external_print_footer_Scripts'));
			add_action('wp_head',array($this,'external_print_head_Scripts'));
			//use built in wp_enqueue functions
			add_action('wp_enqueue_scripts', array($this,'external_script_enqueue'));
			add_action('wp_print_styles', array($this,'external_style_enqueue'));
	    }

		//add help tabs with tut videos	    
		public function shui_add_help_tab () {
		    $screen = get_current_screen();
		    if ( $screen->id != $this->cpt_name && $screen->id != 'edit-ba_sh' && $screen->id != 'add') {
		    	return;
		    }
		    include_once($this->dir.'config/shortcode.ui.help.tabs.php');
		}
	    
	    /*
	     ****************************
	     * 		  SimpleBox	*
	     ****************************
	     */
	    
	    //register and enqeue scripts
	    public function register_scripts(){
	    	
	    	wp_enqueue_script('SimpleBox',$this->url.'/js/SimpleBox/SimpleBox.js',array('jquery'),"",true );
	    	wp_enqueue_script('insert_shui',$this->url.'/js/insert_shortcode.js',array('jquery'),"",true );
	    	$config = array( 'get_shortcode_rander_nonce' => wp_create_nonce('get_shortcode_rander') );
	    	wp_localize_script( 'insert_shui', 'conf', $config );

	    }
	    
	    //register and enqeue styles
	    public function register_styles(){
	    	
	    	wp_enqueue_style( 'SimpleBox',$this->url.'/js/SimpleBox/SimpleBox.css');
	    }
	    
		/* 
		 ****************************
		 * tinymce button functions *
		 ****************************
		 */
	    	    
		//add buttons
		public function Add_custom_buttons( $mce_buttons ){
			$mce_buttons[] = '|';
			$mce_buttons[] = 'ShortCodeUI';
			return $mce_buttons;
		}
		
		public function insert_shortcode_button(){
			?>
			<style>
				.sc-desc{background: none repeat scroll 0 0 #F1fc5c;border-radius: 8px 8px 8px 8px;color: #777777;display: block;float: right;margin: 3px 0 10px 5px;max-width: 240px;padding: 15px;}
				.sc_att{width: 650px;}
			 	.sc_container{border:1px solid #ddd;border-bottom:0;background:#f9f9f9;margin-top: 5px;}
				#sc_f_table label{font-size:12px;font-weight:700;width:200px;display:block;float:left;}
				#sc_f_table input {padding:30px 10px;border-bottom:1px solid #ddd;border-top:1px solid #fff;}
				#sc_f_table small{display:block;float:right;width:200px;color:#999;}
				#sc_f_table input[type="text"], #sc_f_table select{width:280px;font-size:12px;padding:4px;	color:#333;line-height:1em;background:#f3f3f3;}
				#sc_f_table input:focus, .#sc_f_table textarea:focus{background:#fff;}
				#sc_f_table textarea{width:280px;height:175px;font-size:12px;padding:4px;color:#333;line-height:1.5em;background:#f3f3f3;}
				#sc_f_table h3 {cursor:pointer;font-size:1em;text-transform: uppercase;margin:0;font-weight:bold;color:#232323;float:left;width:80%;padding:14px 4px;}
				#sc_f_table th, #sc_f_table td{border:1px solid #bbb;padding:10px;text-align:center;}
				#sc_f_table th, .#sc_f_table td.feature{border-color:#888;}
				@import "<?php plugins_url('css/jquery-ui.css',__FILE__);?> ";
			</style>';
			<?php
		}
		
		
		//set button
		public function Insert_custom_buttons( $initArray ){
			$image = plugins_url('images/tinymce_button.png',__FILE__);
			$initArray['setup'] = <<<JS
[function(ed) {
    ed.addButton('ShortCodeUI', {
        title : 'ShortCodeUI',
        image : '$image',
        onclick : function() {
        	//launch shortcode ui panel
        	shui_editor = 'visual';
        	SimpleBox(null,'admin-ajax.php?action=sh_ui_panel','ShortCodes UI');
        }
    });
}][0]
JS;
			return $initArray;
		}
		
		
		/* 
		 ********************************
		 * End tinymce button functions *
		 ********************************
		 */
		
		/* 
		 ****************************
		 * manage columns functions *
		 ****************************
		 */
		
		//add columns function 
		public function add_new_sc_columns($columns){
			$new_columns['cb'] = '<input type="checkbox" />';
			$new_columns['title'] = _x('ShortCode Name', 'column name');
			$new_columns['sc_tag'] = __('Shortcode Tag');
			$new_columns['image'] = __('Preview');
			$new_columns['cats'] = __('Categories');
			$new_columns['sc_Author'] = __('ShortCode Author');
			return $new_columns;
		}

		//render columns function 
		public function manage_sc_columns($column_name, $id) {
			global $wpdb;
			$prefix = '_basc';
			switch ($column_name) {
			case 'id':
				echo $id;
			        break;
			case 'image':
				// Get number preview image
				$image = get_post_meta($id,$prefix.'sh_preview_image',true);
				if (false != $image && !empty($image)){
					echo '<img src="'.$image.'" width="80px" height="80px"/>';
				}else {
					echo '<img src="http://i.imgur.com/W8R4m.jpg" width="80px" height="80px"/>';
				}
				break;
			case 'sc_tag':
				//get tag
				$tag = get_post_meta($id,$prefix.'sh_tag',true); 
				if (false != $tag && !empty($tag))
					echo '<p>['.$tag.']</p>';
				break;
			case 'cats':
				$cats = wp_get_object_terms($id, 'bs_sh_cats');
				$re ='';
				foreach ((array)$cats as $c){
					$re .=  $c->name .', ';
				}
				if (strlen($re) > 2)
					echo substr($re,0,-2);
				break;
			case 'sc_Author':
				$author = get_post_meta($id,$prefix.'_Author_Name',true);
				$author_url = get_post_meta($id,$prefix.'_Author_url',true);
				$support_url =  get_post_meta($id,$prefix.'_Support_url',true);
				if (!empty($author_url) && !empty($author)){
					echo '<a href="'.$author_url.'" target="_blank"><strong>'.$author.'</strong></a>';
				}elseif(!empty($author)){
					echo '<strong>'.$author.'</strong>';
				}
				if (!empty($support_url)){
					echo '<br/><a href="'.$support_url.'" target="_blank">Shortcode Support</a>';
				}
				break;
			default:
					break;
				} // end switch
		}
		
		/* 
		 ********************************
		 * End manage columns functions *
		 ********************************
		 */
		
		
	    //setup scripts and styles
		public function conditionally_add_scripts_and_styles($posts){
			if (empty($posts)) return $posts;
		 
			if (!isset($this->sc_tags) && !is_array($this->sc_tags))
				return $posts;
			
			$tags = array_keys($this->sc_tags);
			
			foreach ($tags as $index => $t){
				foreach ($posts as $post) {
					if (stripos($post->post_content, '['.$t) !== false) {
						$this->sc_external[$t]['found'] = true; 
						break;
					}
				}
			}
			return $posts;
		}

		
	    //fix translation
	    public function custom_enter_title( $input ) {
	    	$screen = get_current_screen();
	    	if ( $this->cpt_name == $screen->post_type ) {
	    		return __('Enter Shortcode Name','ba_shcode');
	    	}
		    return $input;
		}
		
		//register shortcodes
		public function load_shortcodes(){
			global $post,$wpdb;

			$tmp_post = $post;
			
			$prefix = '_basc';
			
			$myshortcodes = $wpdb->get_results(
				"SELECT id 
				FROM $wpdb->posts 
				WHERE 1=1 
				AND $wpdb->posts.post_type = 'ba_sh' 
				AND ($wpdb->posts.post_status = 'publish') 
				ORDER BY $wpdb->posts.post_date DESC","ARRAY_A"
			);
			
			foreach( $myshortcodes as $arr){
				$p = $arr['id'];
				$sc_meta = get_post_custom($p);
				$sc_tag = $sc_type = '';
				if (isset($sc_meta[$prefix.'sh_type']))
					$sc_type = $sc_meta[$prefix.'sh_type'][0];
				if (isset($sc_meta[$prefix.'sh_tag'])){
					$sc_tag = $sc_meta[$prefix.'sh_tag'][0];
					//avoid duplicate shortcode tags
					$sc_tag = $this->avoid_duplicate_shortcode_tags($sc_tag,$p);
					
					$this->sc_tags[$sc_tag]= array( 'id' => $p,'head'=>false,'body'=>false);
					$this->sc_tags[$sc_tag]['id'] = $p; 
					//add_shortcode handler
					switch($sc_type) {
					    case 'simple':
					      	//add_shortcode($sc_tag, create_function('',"return '".$p->post_content ."';"));
					      	add_shortcode($sc_tag, array($this,'simple_shortcode_handler'));
					      	break;
					    case 'content':
					      	add_shortcode($sc_tag, array($this,'content_shortcode_handler'));
					      	break;
					    case 'advanced':
					      	add_shortcode($sc_tag, array($this,'advanced_shortcode_handler'));
					      	break;
					    default:
					    case 'snippet':
					      	add_shortcode($sc_tag, array($this,'snippet_shortcode_handler'));
					      break;
					}
					
					if (isset($sc_meta[$prefix.'sh_external'][0])){
						$externals = unserialize($sc_meta[$prefix.'sh_external'][0]);
						
						if (is_array($externals) && count($externals > 0)){
							foreach ($externals as $ex){
								$this->sc_external[$sc_tag][$ex['_bascsh_location']][] = array(
									'type' => $ex['_bascsh_ex_f_type'],
									'url'  => $ex['_basc_url'], 
									'how'  => (isset($ex['_basc_enque']))? $ex['_basc_enque']: 'tag' 
								);
							}
						}
					}
				}
			}
		}
		
		//register post type and custom taxonomy on init
		public function register_customs(){
			$this->register_cpt_ba_sh();
			$this->register_ct_bs_sh_cats();
			include_once($this->dir.'/classes/admin-class/adminp.php');
			include_once($this->dir.'/classes/plugin.info.class.php');
			include_once($this->dir.'/config/shortcode.ui.admin.pages.php');
		}
		
		//register custom taxonomy for shortcodes categories
		public function register_ct_bs_sh_cats(){
			$labels = array(
			    'name' => _x( 'Categories', 'taxonomy general name' ),
			    'singular_name' => _x( 'Category', 'taxonomy singular name' ),
			    'search_items' =>  __( 'Search shortcode categories' ),
			    'popular_items' => __( 'Popular shortcode categories' ),
			    'all_items' => __( 'All Shortcode categories' ),
			    'parent_item' => null,
			    'parent_item_colon' => null,
			    'edit_item' => __( 'Edit shortcode category' ), 
			    'update_item' => __( 'Update shortcode category' ),
			    'add_new_item' => __( 'Add shortcode category' ),
			    'new_item_name' => __( 'New shortcode category name' ),
			); 
			
			$args = array(
			    	'hierarchical' => true,
			    	'labels' => $labels,
			    	'show_ui' => true,
			    	'query_var' => true,
			    	'rewrite' => array( 'slug' => 'bs_sh_cats' ),
			);
			
			if (!$this->can_user_manage('ct')){
				$args['show_ui'] = false;	
		    }
			register_taxonomy('bs_sh_cats',	array($this->cpt_name),$args);
		}
		
		//register shortcode post type
		public function register_cpt_ba_sh() {

			$labels = array( 
				'name' => _x( 'Short Codes', 'ba_sh' ),
				'singular_name' => _x( 'Shortcode', 'ba_sh' ),
				'add_new' => _x( 'Add New', 'ba_sh' ),
				'add_new_item' => _x( 'Add New Shortcode', 'ba_sh' ),
				'edit_item' => _x( 'Edit Shortcode', 'ba_sh' ),
				'new_item' => _x( 'New Shortcode', 'ba_sh' ),
				'view_item' => _x( 'View Shortcode', 'ba_sh' ),
				'search_items' => _x( 'Search Short Codes', 'ba_sh' ),
				'not_found' => _x( 'No short codes found', 'ba_sh' ),
				'not_found_in_trash' => _x( 'No short codes found in Trash', 'ba_sh' ),
				'parent_item_colon' => _x( 'Parent Shortcode:', 'ba_sh' ),
				'menu_name' => _x( 'Short Codes', 'ba_sh' ),
			);

			$args = array( 
				'labels' => $labels,
				'hierarchical' => false,
				'supports' => array( 'title', 'editor', 'custom-fields' ),
				'public' => false,
				'show_ui' =>  true,
				'show_in_menu' => true,
				'menu_icon' => 'http://i.imgur.com/cdru8.png',
				'show_in_nav_menus' => false,
				'publicly_queryable' => false,
				'exclude_from_search' => true,
				'has_archive' => false,
				'query_var' => true,
				'can_export' => true,
				'rewrite' => false,
				'capability_type' => 'post'
			);
		   if (!$this->can_user_manage('cpt')){
				$args['show_ui'] = false;	
		   }
			register_post_type( $this->cpt_name, $args );
		}
		
		//shortcodes update messages
		public function sh_updated_messages( $messages ) {
			global $post, $post_ID;
		  	$messages[$this->cpt_name] = array(
			    0 => '', // Unused. Messages start at index 1.
			    1 => sprintf( __('Shortcode updated. <a href="%s">View Shortcode</a>'), esc_url( get_permalink($post_ID) ) ),
			    2 => __('Custom field updated.'),
			    3 => __('Custom field deleted.'),
			    4 => __('Shortcode updated.'),
			    /* translators: %s: date and time of the revision */
			    5 => isset($_GET['revision']) ? sprintf( __('Shortcode restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			    6 => sprintf( __('Shortcode published. <a href="%s">View Shortcode</a>'), esc_url( get_permalink($post_ID) ) ),
			    7 => __('Shortcode saved.'),
			    8 => sprintf( __('Shortcode submitted. <a target="_blank" href="%s">Preview Shortcode</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			    9 => sprintf( __('Shortcode scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Shortcode</a>'),
			      // translators: Publish box date format, see http://php.net/date
			      date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			    10 => sprintf( __('Shortcode draft updated. <a target="_blank" href="%s">Preview Shortcode</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		 	);
		
			return $messages;
		}
				
		//can user manage shortcode and taxonomies
		public function can_user_manage($type){
			//	how can manage?
			global $current_user;
			get_currentuserinfo();
			$user_id = intval( $current_user->ID );

			if( ! $user_id ) {
				return FALSE;
			}
			$user = new WP_User( $user_id ); // $user->roles
			$pl_options = get_option('shui_settings',null);
			if ($pl_options == null){
				return true;
			}
			
			$orderedRoles = array(
				'norole' => 0,
				'subscriber' => 1,
				'contributor' => 2,
				'author' => 3,
				'editor' => 4,
				'administrator' => 5
			);
			$cu = $user->roles[0];
			$set = $orderedRoles[strtolower($pl_options[$type])];
			if ($orderedRoles[$user->roles[0]] >= $orderedRoles[strtolower($pl_options[$type])]){
				return true;
			}
			return false;			
		}
		

		/**
		 * load_meta_box 
		 *
		 * adds shortcode UI metaboxes
		 * 
		 * @return void
		 */
		public function load_meta_box(){
			require_once($this->dir.'/classes/meta-box-class/my-meta-box-class.php');
			$prefix = '_basc';
			extract(array('type' => $this->cpt_name,'prefix' => '_basc'));
			include_once($this->dir.'config/shortcode.settings.meta.php');
			include_once($this->dir.'config/shortcode.advanced.meta.php');
			include_once($this->dir.'config/shortcode.external.files.meta.php');
			include_once($this->dir.'config/shortcode.author.meta.php');
		}
		

		//simple shortcode handler
		public function simple_shortcode_handler($attr,$content = null,$tag=''){
			$sc_id = $this->sc_tags[$tag]['id'];
			$sc_meta = get_post_custom($sc_id);
			
			$sc_content = $sc_template = '';
			
			if (isset($sc_meta['_bascsh_template'][0]) && $sc_meta['_bascsh_template'][0] != '' )
				$sc_template = $sc_meta['_bascsh_template'][0];
				
			$sc_content = $this->get_sc_content($sc_id);
			
			if ($sc_content == '' && $sc_template == '') return '';
			
			if(isset($sc_meta['_bascsh_attr'][0]) ){
				$shortcode_attributes = unserialize($sc_meta['_bascsh_attr'][0]);
				if (is_array($shortcode_attributes) && count($shortcode_attributes > 0)){
					$args = array();
					foreach ($shortcode_attributes as $at){
						if (isset($attr[$at['_basc_name']])){
							$sc_content  = str_replace('{'.$at['_basc_name'].'}',$attr[$at['_basc_name']],$sc_content);
							$sc_template  = str_replace('{'.$at['_basc_name'].'}',$attr[$at['_basc_name']],$sc_template);
						}else{
							$sc_content  = str_replace('{'.$at['_basc_name'].'}',$at['_basc_std'],$sc_content);
							$sc_template = str_replace('{'.$at['_basc_name'].'}',$at['_basc_std'],$sc_template);
						}
						$args[$at['_basc_name']] = $at['_basc_std'];
					}
					extract(shortcode_atts($args, $attr));
				}
			}
					
			$content = str_replace('{SC_CONTENT}',$sc_content,$sc_template);
			
			//check for JavaScript and Css code
			if (!isset($this->sc_media[$tag])){
				if (isset($sc_meta['_bascsh_js'][0]))
					$this->sc_media[$tag]['js'] = $sc_meta['_bascsh_js'][0];
				if (isset($sc_meta['_bascsh_style'][0]))
					$this->sc_media[$tag]['css'] = $sc_meta['_bascsh_style'][0];
			}
			
			return apply_filters($tag,do_shortcode($content));
		}
		
		//snippet shortcode handler
		public function snippet_shortcode_handler($attr,$content = null,$tag=''){
			$sc_id = $this->sc_tags[$tag]['id'];
			$con = ''; 
			$con = $this->get_sc_content($sc_id);
			return apply_filters($tag,do_shortcode($con));
		}
		
		//content shortcode handler
		public function content_shortcode_handler($attr,$content = null,$tag=''){
			$sc_id = $this->sc_tags[$tag]['id'];
			$sc_meta = get_post_custom($sc_id);
			
			$sc_content = $sc_template = '';
			
			if (isset($sc_meta['_bascsh_template'][0]) && $sc_meta['_bascsh_template'][0] != '' ){
				//shortcode template
				$sc_template = $sc_meta['_bascsh_template'][0];
			}

			//shortcode editor content
			$sc_content = $this->get_sc_content($sc_id);
			
			if ($sc_content == '' && $sc_template == '' && $content == null) return '';
			

			
			if(isset($sc_meta['_bascsh_attr'][0]) ){
				$shortcode_attributes = unserialize($sc_meta['_bascsh_attr'][0]);
				if (is_array($shortcode_attributes) && count($shortcode_attributes > 0)){
					$args = array();
					foreach ($shortcode_attributes as $at){
						if (isset($attr[$at['_basc_name']])){
							$content = str_replace('{'.$at['_basc_name'].'}',$attr[$at['_basc_name']],$content);
							$sc_content  = str_replace('{'.$at['_basc_name'].'}',$attr[$at['_basc_name']],$sc_content);
							$sc_template  = str_replace('{'.$at['_basc_name'].'}',$attr[$at['_basc_name']],$sc_template);
						}else{
							$content = str_replace('{'.$at['_basc_name'].'}',$at['_basc_std'],$content);
							$sc_content  = str_replace('{'.$at['_basc_name'].'}',$at['_basc_std'],$sc_content);
							$sc_template = str_replace('{'.$at['_basc_name'].'}',$at['_basc_std'],$sc_template);
						}
						$args[$at['_basc_name']] = $at['_basc_std'];
					}
					extract(shortcode_atts($args, $attr));
				}
			}
			
			
			$contenti = str_replace('{SC_CONTENT}',$sc_content,$sc_template);
			if (!empty($content) && $content != '' && false !== strpos($contenti, '{CONTENT}')){
				$content = str_replace('{CONTENT}',$content,$contenti);
			}else{
				$content = $contenti;
			}	
				
			
			
			
			//check for JavaScript and Css code
			if (!isset($this->sc_media[$tag])){
				if (isset($sc_meta['_bascsh_js'][0]))
					$this->sc_media[$tag]['js'] = $sc_meta['_bascsh_js'][0];
				if (isset($sc_meta['_bascsh_style'][0]))
					$this->sc_media[$tag]['css'] = $sc_meta['_bascsh_style'][0];
			}
			
			return apply_filters($tag,do_shortcode($content));
		}
		
		//advanced shortcode handler
		public function advanced_shortcode_handler($attr,$content = null,$tag=''){
			$sc_id = $this->sc_tags[$tag]['id'];
			$sc_meta = get_post_custom($sc_id);
			if (!isset($sc_meta['_bascsh_php'][0])) return '';
			
			$code = trim($sc_meta['_bascsh_php'][0]);
			if ($code == '') return '';
			
				
			$sc_content = $this->get_sc_content($sc_id);
						
			if(isset($sc_meta['_bascsh_attr'][0]) ){
				$shortcode_attributes = unserialize($sc_meta['_bascsh_attr'][0]);
				if (is_array($shortcode_attributes) && count($shortcode_attributes > 0)){
					$args = array();
					foreach ($shortcode_attributes as $at){
						
						$args[$at['_basc_name']] = $at['_basc_std'];
					}
					extract(shortcode_atts($args, $attr));
				}
			}
			
			$return_val = '';
			
			//echo php
			if (isset($sc_meta['_bascphp_type'][0]) && $sc_meta['_bascphp_type'][0] == 'echo'){
			
				try{
					ob_start();
					eval($code);
					$return_val = ob_get_clean();
				}catch(Exception $e){
					
				}
			}else{//return php
				try{
					$return_val = eval($code);
				}catch(Exception $e){
					
				}
			}
			
			
			//check for JavaScript and Css code
			if (!isset($this->sc_media[$tag])){
				if (isset($sc_meta['_bascsh_js'][0]))
					$this->sc_media[$tag]['js'] = $sc_meta['_bascsh_js'][0];
				if (isset($sc_meta['_bascsh_style'][0]))
					$this->sc_media[$tag]['css'] = $sc_meta['_bascsh_style'][0];
			}
			
			return apply_filters($tag,do_shortcode($return_val));
		}
		
		//helper function to get shortcode content (the one from the editor)
		public function get_sc_content($pid){
			global $wpdb;
			$sc_content = $wpdb->get_var( $wpdb->prepare( 
			"
				SELECT post_content 
				FROM $wpdb->posts 
				WHERE post_type = 'ba_sh' 
				AND post_status = 'publish'
				AND  ID = %s
				LIMIT 1
			", 
			$pid
			) );
			
			$suppress_filters = get_post_meta($pid,'_basc_apply_content_filters',true);
			if(!empty($suppress_filters))
				return apply_filters( 'shortcodes_ui_raw_content', $sc_content);

			$pl_options = get_option('shui_settings',null);
			if ($pl_options == null || !isset($pl_options['autop'])){
				
			}else{
				switch ($pl_options['autop']) {
					case 'remove':
						remove_filter( 'the_content', 'wpautop' );
						break;
					case 'prospond':
						remove_filter( 'the_content', 'wpautop' );
						add_filter( 'the_content', 'wpautop' , 12);
						break;
					default:
						break;
				}
			}
			do_action('scui_external_hooks_remove');
			$c =  apply_filters('the_content',$sc_content);
			do_action('scui_external_hooks_return');
			return $c;
		}
		
		//helper debug function pre_var_dump
		public function pre_var_dump($var){
			echo '<pre>';
			var_dump($var);
			echo '</pre>';
		} 
		
		//print JavaScript
		public function print_footer_Scripts(){
			$js = $css =  '';
			foreach($this->sc_media as $sc){
				if (isset($sc['js']))
					$js .= "\n" . $sc['js']; 
				if (isset($sc['css']))
					$css .= "\n" . $sc['css'];
			}
			if ($css != '')
				echo '<style>'.$css.'</style>';
			if ($js != '')
				echo '<script>'.$js.'</script>';
		}
		
		//print head scripts and styles
		public function external_print_head_Scripts(){
			if (isset($this->sc_external)){
				if (is_array($this->sc_external) && count($this->sc_external) > 0){
					foreach ($this->sc_external as $key => $arr){
						if (isset($arr['found']) && $arr['found'] && isset($arr['head']) ){
							foreach ((array)$arr['head'] as $ex){
									if ('script' == $ex['type']){
										if ('tag' == $ex['how'])
											echo '<script type="text/javascript" src="'.$ex['url'].'"></script>';
									}else{
										if ('tag' == $ex['how'])
											echo '<link href="'.$ex['url'].'" media="all" type="text/css" rel="stylesheet">';
									}
							}
						}
					}
				}
			}
		}
		
		//print footer scripts and styles
		public function external_print_footer_Scripts(){
			if (isset($this->sc_external)){
				if (is_array($this->sc_external) && count($this->sc_external) > 0){
					foreach ($this->sc_external as $key => $arr){
						if (isset($arr['found']) && $arr['found'] && isset($arr['body']) ){
							foreach ((array)$arr['body'] as $ex){
								if ('script' == $ex['type']){
									if ('tag' == $ex['how'])
										echo '<script type="text/javascript" src="'.$ex['url'].'"></script>';
								}else{
									if ('tag' == $ex['how'])
										echo '<link href="'.$ex['url'].'" media="all" type="text/css" rel="stylesheet">';
								}
							}	
						}
					}
				}
			}
		}
		
		//external_script_enqueue
		public function external_script_enqueue(){
			if (isset($this->sc_external)){
				if (is_array($this->sc_external) && count($this->sc_external) > 0){
					foreach ($this->sc_external as $key => $arr){
						if (isset($arr['found']) && $arr['found'] && isset($arr['body']) ){
							foreach ((array)$arr['body'] as $ex){
								if ('script' == $ex['type']){
									if ('enqueue' == $ex['how']){
										wp_enqueue_script($key,$ex['url'],'','',true);
									}
								}
							}	
						}elseif (isset($arr['found']) && $arr['found'] && isset($arr['head']) ){
							foreach ((array)$arr['head'] as $ex){
								if ('script' == $ex['type']){
									if ('enqueue' == $ex['how']){
										wp_enqueue_script($key,$ex['url'],'','',false);
									}
								}
							}	
						}
					}
				}
			}
		}
		
		//external_style_enqueue
		public function external_style_enqueue(){
			if (isset($this->sc_external)){
				if (is_array($this->sc_external) && count($this->sc_external) > 0){
					foreach ($this->sc_external as $key => $arr){
						if (isset($arr['found']) && $arr['found'] && isset($arr['body']) ){
							foreach ((array)$arr['body'] as $ex){
								if ('link' == $ex['type']){
									if ('enqueue' == $ex['how']){
										wp_enqueue_style( $key, $ex['url']);
									}
								}
							}	
						}elseif (isset($arr['found']) && $arr['found'] && isset($arr['head']) ){
							foreach ((array)$arr['head'] as $ex){
								if ('link' == $ex['type']){
									if ('enqueue' == $ex['how']){
										wp_enqueue_style( $key, $ex['url']);
									}
								}
							}	
						}
					}
				}
			}
		}
		
		/*
		 * Export Import Functions
		 */
		
		//export action Row
		public function Export_shortcodes_Row_action($actions, $post){
    		if ($post->post_type =="ba_sh"){
        		$actions['Export'] = '<a href="#" sc_id="'.$post->ID.'">Export ShortCode</a>';
    		}
    		return $actions;
		}
		
		public function sc_ui_import_export_menupage() {

	 	   add_submenu_page( 'edit.php?post_type=ba_sh', 'ShortCodes UI Import Export', 'Import/Export', 'manage_options', 'sc_ui_ie', array($this,'sc_ui_import_export_page'));
		}
	
		//import export panel
		public function sc_ui_import_export_page(){
        	global $wpdb;
            include_once($this->dir.'config/shortcode.ui.import.export.page.php');
        }
		
		//load import/export js code
		public function sc_ui_import_export_scripts(){
			wp_enqueue_script('jquery'); 
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-tabs');
			wp_enqueue_script('sc_ui_im_ex', $this->url.'/js/im_ex.js', array('jquery'),'', true);
		}
		
		//load import/export css code
		public function sc_ui_import_export_styles(){

			wp_enqueue_style( 'jquery-ui', $this->url.'/css/jquery-ui.css');
		}
		
		
		//avoid duplicates and bad tag names
		public function avoid_duplicate_shortcode_tags($sc_tag,$sc_id){
			$tmp_tag = $sc_tag;
			$shortcode_exists = true;
			$counter_tag = 0;
			$sc_tag = str_replace(' ','_',$sc_tag);
			$sc_tag = str_replace('  ','_',$sc_tag);
			$sc_tag = str_replace('.','_',$sc_tag);
			while ($shortcode_exists){
				if (!array_key_exists($sc_tag,array_keys($this->sc_tags))){
					$shortcode_exists = false;
				}else{
					$sc_tag = $sc_tag.'_'.$counter_tag;
					$counter_tag = $counter_tag + 1; 
				}
			}
			if ($tmp_tag != $sc_tag){
				update_post_meta($sc_id,'_bascsh_tag',$sc_tag);
			}
			return $sc_tag;
		}

		//autoP Fix
		public function autop_fix(){
			$pl_options = get_option('shui_settings',null);
			if ($pl_options == null || !isset($pl_options['autop'])){
				return;
			}
			switch ($pl_options['autop']) {
				case 'remove':
					remove_filter( 'the_content', 'wpautop' );
					break;
				case 'prospond':
					remove_filter( 'the_content', 'wpautop' );
					add_filter( 'the_content', 'wpautop' , 12);
					break;
				default:
					break;
			}

		}

		
		/**
		 * jsonDie 
		 *
		 * echos a json encoded array and dies
		 * @param  array $arr 
		 * @return void
		 */
		function jsonDie($arr){
			echo json_encode($arr);
			die();
		}
		
		
	}//end class
}//end if
global $shortcodes_ui;
$shortcodes_ui = new BA_ShortCode_Maker();
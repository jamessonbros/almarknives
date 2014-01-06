<?php
/**
 *  Plugin info class is a simple call to get plugin info using the api.wordpress.org json format api 
 * 
 * @author Ohad Raz <admin@bainternet.info> 
 * 
 * @version 0.2
 * @copyright Ohad Raz.
 * @license  GPL V2. 
 * 
 */
?>
<?php
/* Disallow direct access to the plugin file */
if (basename($_SERVER['PHP_SELF']) == basename (__FILE__)) {
	die('Sorry, but you cannot access this page directly.');
}

if (!class_exists('plugin_repo_info')){
	
	class plugin_repo_info{
		
		/**
		 * plugin name , plugin slug
		 * @var string
		 */
		public $plugin_name;

		/**
		 * plugin_info object
		 * @var object
		 */
		public $plugin_info;

		/**
		 * plugin found
		 * @var boolean
		 */
		public $plugin_found;
		/**
		 * $refresh_time
		 * @var int
		 */
		public $refresh_time;


		/**
		 * class constructor
		 * 
		 * @author Ohad Raz <admin@bainternet.info>
		 * @since 0.1
		 * @access public
		 * 
		 * @param string $plugin_name 
		 * @param int $refresh_time time in days
		 * 
		 * @return Void
		 * 
		 */
		public function __construct($plugin_name,$refresh_time = null){
			$this->plugin_name = $plugin_name;
			$this->plugin_info = null;
			$this->plugin_found = false;
			$this->refresh_time = (null === $refresh_time) ? 60*60*24*2 : $refresh_time * 60*60*24;
			$this->get_plugin_stats();
		}

		/**
		 * get_plugin_stats
		 * 
		 * @author Ohad Raz <admin@bainternet.info>
		 * @since 0.1
		 * @access public
		 * 
		 * @return plugin_info object
		 */
		public function get_plugin_stats(){
			$plugin_Name = $this->plugin_name;
			$value = get_transient( 'PI_'.$plugin_Name );
			if ( false === $value ) {
				$resp = wp_remote_get( 'http://api.wordpress.org/plugins/info/1.0/'.$plugin_Name.'.json');
                if ( !is_wp_error($resp) && 200 == $resp['response']['code']) {
					$response = json_decode($resp['body']);
					set_transient( 'PI_'.$plugin_Name, $response, $this->refresh_time);
					$this->plugin_found = true;
					$this->plugin_info = $response;
					return;
				}else{
					return null;
				}
			}else{
				$this->plugin_found = true;
				$this->plugin_info = $value;
			}
		}

		/**
		 * get_info 
		 * 
		 * @author Ohad Raz <admin@bainternet.info>
		 * @since 0.1
		 * @access public
		 * @param  $string $key 
		 * 
		 * @return (mixed)string|array|null returns null when key not found
		 */
		public function get_info($key){
			return isset($this->plugin_info->$key) ? $this->plugin_info->$key : null;
		}

		/**
		 * get_downloads description
		 * 
		 * @author Ohad Raz <admin@bainternet.info>
		 * @since 0.1
		 * @access public
		 * 
		 * @return string downloads number
		 */
		public function get_downloads(){
			return $this->get_info('downloaded');
		}

		/**
		 * get_votes_number
		 * 
		 * @author Ohad Raz <admin@bainternet.info>
		 * @since 0.1
		 * @access public
		 * 
		 * @return string number of rating votes
		 */
		public function get_votes_number(){
			return $this->get_info('num_ratings');
		}


		/**
		 * get_votes_rating 
		 * 
		 * @author Ohad Raz <admin@bainternet.info>
		 * @since 0.1
		 * @access public
		 * 
		 * @return string votes rating
		 */
		public function get_votes_rating(){
			return $this->get_info('rating');
		}
		
		/**
		 * get_votes_download_link 
		 * 
		 * @author Ohad Raz <admin@bainternet.info>
		 * @since 0.1
		 * @access public
		 * 
		 * @return string plugin download url
		 */
		public function get_votes_download_link(){
			return $this->get_info('download_link');
		}

		/**
		 * get_rating_stars 
		 *  
		 * @author Ohad Raz <admin@bainternet.info>
		 * @since 0.1
		 * @access public
		 * 
		 * @return stirng  html star rating 
		 */
		public function get_rating_stars(){
			$rating = $this->get_votes_rating();
			$rating = $rating /10;
			$rating = $rating /2;
			$r =  round($rating);
			$ret_val = '<style>
			.rating {float:left; width: 275px;}
			.rating:not(:checked) > input {position:absolute;top:-9999px;clip:rect(0,0,0,0);}
			.rating:not(:checked) > label {float:right;width:1em;padding:0 .1em;overflow:hidden;white-space:nowrap;cursor:pointer;font-size:200%;line-height:1.2;color:#ddd;text-shadow:1px 1px #bbb, 2px 2px #666, .1em .1em .2em rgba(0,0,0,.5);}
			.rating:not(:checked) > label:before {content: "★ ";}
			.rating > input:checked ~ label {color: gold;text-shadow:1px 1px #c60, 2px 2px #940, .1em .1em .2em rgba(0,0,0,.5);}
			.rating > label:active {position:relative;top:2px;left:2px;}
			</style>
			<div class="rating">
	    		<span>Current Rating:</span>
	    		<input disabled="disabled" type="radio" id="star5" value="5"  /><label for="star5" title="">5 stars</label>
	    		<input disabled="disabled" type="radio" id="star4"  value="4" /><label for="star4" title="">4 stars</label>
	    		<input disabled="disabled" type="radio" id="star3"  checked="checked" value="3" /><label for="star3" title="">3 stars</label>
	    		<input  disabled="disabled" type="radio" id="star2"  value="2" /><label for="star2" title="">2 stars</label>
	    		<input disabled="disabled"  type="radio" id="star1"  value="1" /><label for="star1">1 star</label>
			</div> ('. $this->get_votes_number().')<div style="clear: both"></div>';
			return str_replace('value="'.$r.'"','checked="checked" value="'.$r.'"',$ret_val);
		}
	}//end class
}//end if
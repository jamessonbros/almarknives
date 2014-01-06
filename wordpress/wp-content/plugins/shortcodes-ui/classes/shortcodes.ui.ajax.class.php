<?php /* shortcode_UI_ajax class*/ ?>
<?php
defined('ABSPATH') OR exit('No direct script access allowed');
/**
* shortcode_UI_ajax
* All plugin ajax related function and hooks
* @since 1.9.2
* @author Ohad Raz <admin@bainternet.info>
*/
class shortcode_UI_ajax
{
	public $sc_tags = null;
	public $cpt_name = 'ba_sh';
	function __construct($args = array()){
		$this->hooks();
	}

	function hooks(){
		//render in editor
		add_action('wp_ajax_ba_sb_rander', array($this,'ba_sb_rander'));

		//ajax export/Import function
		add_action('wp_ajax_ba_sb_get_ex_code', array($this,'sc_ui_ajax_export'));
		add_action('wp_ajax_ba_sb_Import_sc', array($this,'sc_ui_ajax_import'));

		//ajax tinymce functions
		add_action('wp_ajax_sh_ui_panel', array($this,'load_tinymce_panel'));
		add_action('wp_ajax_ba_sb_shortcodes', array($this,'get_shortcode_list'));
		add_action('wp_ajax_ba_sb_shortcode', array($this,'get_shortcode_fields'));
	}
	public function registered_shortcodes(){
		if (null === $this->sc_tags){
			global $shortcodes_ui;
			$this->sc_tags = $shortcodes_ui->sc_tags;
		}
		return $this->sc_tags;
	}
	//get Shortcode to Export function
	public function get_shortcode_export($post_id){
		//shortcode post row
		$po = get_post($post_id,'ARRAY_A');
		$p = array();
		$fs = array('post_content','post_title','post_status','post_excerpt','comment_status','post_password','post_type');
		foreach($fs as $key){
			$p[$key] = $po[$key]; 	
		}
		//shortcode meta
		$meta = get_post_custom($post_id);
		unset($meta['_edit_last']);
		unset($meta['_edit_lock']);
		
		//shortcode tax
		$tax = array();
		$taxs = wp_get_object_terms($post_id, 'bs_sh_cats');
		foreach ((array)$taxs as $t){
			$tax[] = $t->slug;
		}
		$tag = $meta['_bascsh_tag'][0];
		return array('p' => $p, 'meta' => $meta, 'tax' => $tax, 'tag' => $tag);
	}
	
	//ajax Export function
	public function sc_ui_ajax_export(){
		check_ajax_referer( 'sc_ui_Get_Export_code', 'seq' );
		if (!isset($_POST['sc_ids'])){
			$re['errors'] = __('No ShortCode were found!');
			$this->jsonDie($re);
		}
		
		foreach ((array)$_POST['sc_ids'] as $p){
			$re['code'][] = $this->get_shortcode_export($p);
		}
		$re['code']= "<!*!* START export Code !*!*>\n".base64_encode(serialize($re['code']))."\n<!*!* END export Code !*!*>";
		//update nonce
		$re['nonce'] = wp_create_nonce("sc_ui_Get_Export_code");
		
		$this->jsonDie($re);
	}

	/**
	 * Rednder snippent in to editor
	 * 
	 * @since 1.6.4
	 * @access public
	 * @author Ohad Raz
	 * 
	 */ 
	public function ba_sb_rander(){
		check_ajax_referer( 'get_shortcode_rander', 'seq' );
		if (isset($_GET['sc_to_rander'])){
			$re['code'] = do_shortcode($_GET['sc_to_rander']);
			$this->jsonDie($re);
		}else{
			$re['error'] = true;
			$this->jsonDie($re);
		}
	}

	//import shortcode to database function 
	function import_shortcode($sc){
		if (in_array($sc['tag'],array_keys($this->registered_shortcodes()))){
        	return array('sc_title' => $sc['p']['post_title'], 'status' => __('ShortCode already Exists with this tag'), 'tag' => $sc['tag']);
		}else{
        	//insert shortcode post row
			$sc_id = wp_insert_post($sc['p']);
            if (!is_wp_error($sc_id) && $sc_id > 0){
            //insert meta
           		 foreach ($sc['meta'] as $k => $v){
            		if ($k == "_bascsh_attr" || $k == "_bascsh_external"){
                		update_post_meta($sc_id,$k,unserialize($v[0]));
                	}else{
                		update_post_meta($sc_id,$k,$v[0]);
                	}
            	}
                //set imported flag
                update_post_meta($sc_id,'_bascimported',1);
                //taxonomy
                wp_set_object_terms($sc_id,(array)$sc['tax'],'bs_sh_cats');
                return array('sc_title' => $sc['p']['post_title'], 'status' => __('Imported Successfully'), 'tag' => $sc['tag']);
			}else{
                return array('sc_title' => $sc['p']['post_title'], 'status' =>__('Error in Importting Shortcode'), 'tag' => $sc['tag']);
			}
        } 
                    
	}
	
	//ajax Import function
	public function sc_ui_ajax_import(){
		check_ajax_referer( 'sc_ui_Import_sc', 'seq2' );
		if (!isset($_POST['import_code'])){
			$re['errors'] = __('No Import Code Was Found!');
			$this->jsonDie($re);
		}
		
		//prepare import code
		$import_code = $_POST['import_code'];
		$import_code = str_replace("<!*!* START export Code !*!*>\n","",$import_code);
		$import_code = str_replace("\n<!*!* END export Code !*!*>","",$import_code);
	   	$import_code = base64_decode($import_code);
		$import_code = unserialize($import_code);
		
		if (is_array($import_code)){
		     foreach  ($import_code as $shortcode){
		     	$re['status'][] = $this->import_shortcode($shortcode);
		     	$re['dump'] = $shortcode['tax'];
		     }
		      
			
		}
		$re['nonce'] = wp_create_nonce("sc_ui_Import_sc");
		$this->jsonDie($re);
	}

	//load tinymce panel
	public function load_tinymce_panel(){
		?>
		<div class="sc_container">
			<div class="sc_selector">
				<span class="sc_category" style="width: 50%;">Shortcode Categories: <select id="sc_cat" name="sc_cat"><?php 
					$cats = get_categories(array('taxonomy' => 'bs_sh_cats','type' => 'ba_sh'));
					echo '<option vlaue="0">'.__('Select Category').'</option>';
					echo '<option vlaue="0">'.__('Select All ShortCodes').'</option>';
					foreach ($cats as $category) {
  						$option = '<option value="'.$category->term_id.'">';
						$option .= $category->name;
						$option .= '</option>';
						echo $option;
						}
					?>
					</select>
				</span>
				<span class="sc_names" style="width: 50%;"> Select Shortcode:
					<select class="sc_name" name="sc_name" id="sc_name">
						<option>Please Select A Category First</option>
					</select>
				</span>
			</div>
			<div class="sc_status" style="display: none;"><img src="http://i.imgur.com/l4pWs.gif" alt="loading..."/></div>
			<div class="sc_ui"></div>
			<div class="sc_atts"></div>
			<div class="sc_insert"></div>
		</div>
		<script>
			//declare walker object
			var walker = new Array();
	    	
	    	jQuery(document).ready(function() {		
	    		//select shortcode category	
				jQuery("#sc_cat").change(function() {
					//before ajax
					if (jQuery("sc_cat").val() != -1) {
						jQuery(".sc_status").show('fast');
						jQuery(".sc_ui").html('');
						jQuery.ajaxSetup({ cache: false });
						jQuery.getJSON(ajaxurl,
						{  	cat: jQuery("#sc_cat").val(),
							rnd: microtime(false), //hack to avoid request cache
						    action: "ba_sb_shortcodes",
						    seq: "<?php echo wp_create_nonce("list_sh_by_cat");?>"
						},
						function(data) {
							if (data.errors){
								alert('Error in getting shortcode list! :(');						
							}else{
								jQuery("#sc_name >option").remove();
								var myCombo= jQuery('#sc_name');

								jQuery.each(data.items, function(i,item){
									myCombo.append(jQuery('<option> </option>').val(item.id).html(item.title));
								});
							}
						});
						jQuery(".sc_status").hide('3500');
						jQuery.ajaxSetup({ cache: true });
						
					}
		    	});

					    	
		    	//select shortcode
				jQuery("#sc_name").change(function() {
					jQuery(".sc_status").show('fast');
					jQuery(".sc_ui").html('');
					jQuery.ajaxSetup({ cache: false });
					
					jQuery.getJSON(ajaxurl,
					{  	sc_id: jQuery("#sc_name").val(),
						rnd: microtime(false), //hack to avoid request cache
					    action: "ba_sb_shortcode",
					    seq: "<?php echo wp_create_nonce("get_shortcode_fields");?>"
					},
					function(data) {
						if (data){
							console.log(data);
							walker = data;
							jQuery(".sc_ui").append('<h2>'+ jQuery('#sc_name>option:selected').text() +' Shortcode</h2>');
							if (data.errors){
								alert('Error in getting shortcode! ):(');						
							}else{
								if(data.preview){
									jQuery(".sc_ui").append('<div>Preview <br/><img src="' + data.preview + '"/></div>');
								}
								if (data.fields){
									jQuery(".sc_ui").append('<h3>ShortCode Attributes</h3>');								
									jQuery(".sc_ui").append(jQuery('<table> </table>').attr('id','sc_f_table').attr('width' ,'100%'));
									if (data.headers){
										jQuery("#sc_f_table").append(data.headers);	
									}
									jQuery.each(data.fields, function(i,item){
										jQuery(".sc_ui").append(item.html +'<hr/>');
									});
								}
								if (data.content){
									if (shui_editor == "visual" &&  typeof tinyMCE != 'undefined'){
										selected_content = tinyMCE.activeEditor.selection.getContent();	
									}
									jQuery(".sc_ui").append('<h3>ShortCode Content</h3>');
									jQuery(".sc_ui").append('<div><textarea class="sc_content" style="width: 398px; height: 70px;">'+selected_content+'</textarea><br/>Enter The Content that needs to be inside the shortcode tags here</div>');
								}
								if (data.submit){
									jQuery(".sc_ui").append('<div>'+ data.submit + '</div>');
								}
							}
						}
					});
					jQuery(".sc_status").hide('3500');
					jQuery.ajaxSetup({ cache: true });
					
		    	});
	    	});

		</script>
		<style>
			.sc_selector{margin-top: 10px;}
			.sc_ui{overflow: auto; width: 630px; padding: 0 3px;}
			.sc_ui table{Border: 2px solid;}
			.sc_ui tr{Border: 2px solid;}
			.sc_ui td{Border: 2px solid;}
			.sc-label{font-weight: bloder; font-size: 14px,text-align: center;}
			
		</style>
		<?php 
		die();
	}

	//get shortcode list for panel
	public function get_shortcode_list(){
		check_ajax_referer( 'list_sh_by_cat', 'seq' );				
		global $wpdb;
		$args = array( 'posts_per_page' => -1, 'post_type' => $this->cpt_name, 'fields' =>'ids' );
		if (isset($_GET['cat']) && $_GET['cat'] != -1){
			if ($_GET['cat'] != 0){
				$args['tax_query'] =array(
					array(
						'taxonomy' => 'bs_sh_cats',
						'field' => 'id',
						'terms' => $_GET['cat']
					)
				);
			}
		}
		
		$myshortcodes = get_posts( $args );
		$re = array();
		if (count($myshortcodes) > 0){
			$myshortcodes = implode(',', $myshortcodes);
			$ids_with_titles = $wpdb->get_results( 
				"
					SELECT ID, post_title 
					FROM $wpdb->posts
					WHERE ID IN  ({$myshortcodes})
				"
			); 
			$prefix = '_basc';
			$re['items'][] = array('id' => 0,'title' => __('Select A ShortCode')); 
			foreach( $ids_with_titles as $p){
				$item = array('id' => $p->ID,'title' => $p->post_title);
				$re['items'][] = $item;
			}
		}else{
			$re['errors'] = __('No ShortCodes were found! try looking for something else');
		}
		$this->jsonDie($re);
	}

	//get shortcode fields for panel
	public function get_shortcode_fields(){
		check_ajax_referer( 'get_shortcode_fields', 'seq' );
		$sc_id = intval($_REQUEST['sc_id']);
		$sc_meta = get_post_custom($sc_id);
		$prefix = '_basc';
		$re = array();
		
		$sc_type = $sc_meta[$prefix.'sh_type'][0];
		if(isset($sc_meta['_bascsh_attr'][0]) ){
			$shortcode_attributes = unserialize($sc_meta['_bascsh_attr'][0]);
			if (is_array($shortcode_attributes) && count($shortcode_attributes > 0)){
				$fields = array();
				
				foreach ($shortcode_attributes as $at){
					$field = array();
					$field['name'] = $at[$prefix.'_name'];
					$field['std'] = $at[$prefix.'_std'];
					$field['options'] = explode("\n", $at[$prefix.'_options']);
					$field['description'] = $at[$prefix.'_desc'];
					$html ='';
					$html = '<div class="sc_att"><div class="sc-label" style="float: left;width: 150px;">
						<p id="'.$field['name'].'-lable">'.$field['name'].'</p></div>';
					
					if (!is_array($field['options']) || count($field['options']) == 1 ){
						$html .= '<div class="sc-field" style="float: left;width: 200px;"><input name="'.$field['name'].'" id="'.$field['name'].'" value="'.$field['std'].'"></div>';
					}else{
						$html .='<div class="sc-field" style="float: left;width: 200px;"><select name="'.$field['name'].'" id="'.$field['name'].'">';
						foreach ($field['options'] as $op){
							$op = trim($op);
							$selected = ($op == $field['std'])? ' selected="selected"' : ''; 
							$html .= '<option value="'.$op.'"'.$selected.'>'.$op.'</option>';
						}
						$html .= '</select></div>';
					}
					$html .= '<div class="sc-desc" style="float:left;">'.$field['description'].'</div></div><div style="clear: both;"></div>';
					$field['html'] = $html;
					$re['fields'][] = $field; 
				}
			}
		}
		//preview image
		if (isset($sc_meta[$prefix.'sh_preview_image'][0]))
			$re['preview'] = $sc_meta[$prefix.'sh_preview_image'][0];
		//content field
		if (in_array($sc_type,array('content','advanced')))
			$re['content'] = true;
		elseif ('snippet' == $sc_type) {
			$re['snip_insert'] = true;
		}
			
		
		$re['submit'] = '<br/><br/><input type="submit" value="Insert Shortcode" id="insert_sc" class="button-primary insert_shortcode">';
		if (isset($re['snip_insert']) && $re['snip_insert']){
			$re['submit'] .= '<input type="submit" value="Render in to editor" id="render_sc" class="button-primary render_shortcode">';
		}
		
		//shortcode Tag
		$re['tag'] = $sc_meta[$prefix.'sh_tag'];

		$this->jsonDie($re);
	}

	/**
	 * jsonDie 
	 *
	 * echos a json encoded array and dies
	 * @param  array $arr 
	 * @return void
	 */
	public function jsonDie($arr){
		echo json_encode($arr);
		die();
	}
}//end class
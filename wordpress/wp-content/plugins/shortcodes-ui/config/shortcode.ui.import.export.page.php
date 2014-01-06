<?php /* Import Export Page */ ?>
<?php
defined('ABSPATH') OR exit('No direct script access allowed');
?>
<div class="wrap">
	<div id="icon-plugins" class="icon32"></div><h2><a href="http://en.bainternet.info" target="_blank">BaInternet</a> ShortCodes UI <?php _e('Import/Export'); ?></h2>
		<div id="tabs">
			<ul>
				<li><a href="#Export">Export ShortCodes</a></li>
				<li><a href="#Import">Import Shortcodes</a></li>
				<li><a href="#stn">Export As Standalone Plugin</a></li>
			</ul>
			<div id="Export">
				<h4><?php _e('Export'); ?></h4>
				<p><?php _e('Select ShortCodes To Export'); ?> <small>(<?php _e('Hold CRTL to select multiple shortcode'); ?>)</small></p>
				<?php 
					$args = array( 'posts_per_page' => -1, 'post_type' => $this->cpt_name, 'fields' =>'ids' );
					$myshortcodes = get_posts( $args );
					if (count($myshortcodes) > 0){
						$myshortcodes = implode(',', $myshortcodes);
						$ids_with_titles = $wpdb->get_results( 
						"
						SELECT ID, post_title 
						FROM $wpdb->posts
						WHERE ID IN  ({$myshortcodes})
						"
						);
						if (count($ids_with_titles) > 0){
							echo '<select id="sc_to_ex" name="sc_to_ex[]" multiple="multiple" style="height: 18em;">';
							foreach( $ids_with_titles as $p){
								echo '<option value="'.$p->ID.'">'.$p->post_title.'</option>';
							}
							echo '</select>';
							echo '<p><input class="button-primary" type="button" name="export" value="'.__('Export ShortCodes').'" id="su_ui_export" />';
							echo '<input type="hidden" id="sc_ui_Get_Export_code" name="sc_ui_Get_Export_code" value="'.wp_create_nonce("sc_ui_Get_Export_code").'" />';
							echo '<div class="sc_ex_status" style="display: none;"><img src="http://i.imgur.com/l4pWs.gif" alt="loading..."/></div>';
							echo '<div class="export_code" style="display: none"><label for="export_code">'.__('Export Code').'</label><br/>
								<textarea id="export_code" style="width: 760px; height: 160px;"></textarea><br/>
								<p>'.__('Copy this code to and paste it at this page in the WordPress Install you want to use this shortcodes in at the buttom box under Import Code').'</p>
								</div>';
						}else{
							echo '<p>No ShortCodes are avialble!</p>';
						}
					 }else{
						echo '<p>No ShortCodes are avialble!</p>';
					 }
				 ?>
			 </div>
			 <div id="Import">
				<h4><?php _e('Import'); ?></h4>
				<p><?php _e('To Import ShortCodes paste the Export output in to the Import Code box bellow and click Import.'); ?></p>
				<div style="float: right;"><input class="button-primary" type="button" name="import_demo" value="<?php _E('Install Demo ShortCodes');?>" id="su_ui_import_demo" /></div>
				<div class="import_code"><label for="import_code"><?php _E('Import Code');?></label><br/>
					<textarea id="import_code" style="width: 760px; height: 160px;"></textarea><br/>
					<input type="hidden" id="sc_ui_Import_sc" name="sc_ui_Import_sc" value="<?php echo wp_create_nonce("sc_ui_Import_sc");?>" />
					<input class="button-primary" type="button" name="import" value="<?php _E('Import ShortCodes');?>" id="su_ui_import" />
					<div class="sc_im_status" style="display: none;"><img src="http://i.imgur.com/l4pWs.gif" alt="loading..."/></div>
					<div class="im-results" style="display: none;"></div>
				</div>
			 </div>
			 <div id="stn">
				<h4><?php _e('Export Shortcode as Standalone Plugin'); ?></h4>
				<div>
					<p><span style="color: red;font-size: 28px;"><strong><?php _e('Comming soon!')?></strong></span></p>
					<p><?php echo __('You can Use this option to export a shortcode as a plugin and and install it in any site you want, sell it or share it at the WordPress Plugin repository, Anything YOU WANT.')?></p>
				</div>
		</div>  
	</div>
</div>
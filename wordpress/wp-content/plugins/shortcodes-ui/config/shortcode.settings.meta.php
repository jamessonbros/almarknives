<?php /* Settings metabox */ ?>
<?php
defined('ABSPATH') OR exit('No direct script access allowed');
$config = array(
	'id' => 'sc_meta_box',					// meta box id, unique per meta box
	'title' => 'ShortCode Settings',					// meta box title
	'pages' => array($type),			// post types, accept custom post types as well, default is array('post'); optional
	'context' => 'normal',						// where the meta box appear: normal (default), advanced, side; optional
	'priority' => 'high',						// order of meta box: high (default), low; optional
	'fields' => array()							// list of meta fields (can be added by field arrays)
);
$my_meta =  new AT_Meta_Box($config);
$my_meta->addSelect($prefix.'sh_type',
	array(
		'snippet' => 'Simple Snippet',
		'simple'=>'Simple One Tag ShortCode',
		'content'=>'Simple ShortCode with content',
		'advanced'=>'Advanced ShortCode'),
	array('desc'=> '<ul>
		<li><strong>Simple Snippet</strong> - will return the value palced in the Editor above.</br> eg: <strong>[shortcode_tag]</strong></li>
		<li><strong>Simple One Tag ShortCode</strong> - will return the value palced in the Editor above or usign the template below, this type can have attributes. </br> eg: <strong>[shortcode_tag attribute1="value1" attribute2="value2"]</strong></li>
		<li><strong>Simple ShortCode with content</strong> - Must have {CONTENT} token in the Editor Above or in template below, will return the value palced in the Editor above</br> eg: <strong>[shortcode_tag]content[/shortcode_tag]</strong></li>
		<li><strong>Advanced ShortCode</strong> - Used for creating advanced ShortCodes (php functions,JavaScript functions ...)</li><ul>','name'=> 'ShortCode Type', 'std'=> array('snippet')));
$my_meta->addText($prefix.'sh_tag',array('name'=> 'ShortCode Tag','desc'=>'This tag will be used to call this shortcode'));
$my_meta->addTextarea($prefix.'sh_template',array('name'=> 'ShortCode Template','desc' => 'Used for shortcodes with HTML tags, CSS and JavaScript Code.<br/>Use this to avoid WordPress from Striping tags.<br/> if you want to include the editor content(the one from above) then place {SC_CONTENT} token in your template,<br/>If this shortcode uses content in his tags then place {CONTENT} token in your template,<br/> You can Also include any attribute you have added to this shortcode using a token eg: {attribute name}'));
$my_meta->addCheckbox($prefix.'_apply_content_filters',array('name'=> 'Suppress content filters','desc' => 'If checked then the content from the editor will be retrived as raw without any filters (avoid other plugins adding stuff to the content)', 'std' => false));
$dsec ='<br/>
<span class="sc_p_img"></span>
<script>
if (jQuery(\'input[name="_bascsh_preview_image"]\').val() != \'\'){
	jQuery(".sc_p_img").append(\'Preview:<br/> \');
	jQuery(".sc_p_img").append(\'<img id="theImg" src="\' + jQuery(\'input[name="_bascsh_preview_image"]\').val() + \'" />\');
}
</script>';
$my_meta->addText($prefix.'sh_preview_image',array('name'=> 'Preview Image','desc' =>$dsec));
$my_meta->Finish();
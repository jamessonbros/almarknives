<?php /* advanced metabox */ ?>
<?php
defined('ABSPATH') OR exit('No direct script access allowed');
$config = array(
	'id' => 'sc_advanced_meta_box',					// meta box id, unique per meta box
	'title' => 'ShortCode Advanced Settings',					// meta box title
	'pages' => array($this->cpt_name),			// post types, accept custom post types as well, default is array('post'); optional
	'context' => 'normal',						// where the meta box appear: normal (default), advanced, side; optional
	'priority' => 'high',						// order of meta box: high (default), low; optional
	'fields' => array()							// list of meta fields (can be added by field arrays)
);
$my_meta_2 =  new AT_Meta_Box($config);
$repeater_fields[] = $my_meta_2->addText($prefix.'_name',array('name'=> 'Attribute Name','group' =>'start' ),true);
$repeater_fields[] = $my_meta_2->addText($prefix.'_std',array('name'=> 'Attribute Default Value','group' =>'end'),true);
$repeater_fields[] = $my_meta_2->addTextarea($prefix.'_options',array('name'=> 'Attribute Value Options','desc' => 'Insert One option in each line' ),true);
$repeater_fields[] = $my_meta_2->addTextarea($prefix.'_desc',array('name'=> 'Attribute Description','desc' => 'Enter a short description of this attribute field' ),true);

$my_meta_2->addRepeaterBlock($prefix.'sh_attr',array('name' => 'ShortCode Attributes','fields' => $repeater_fields,'inline'=>true));
$theme = get_option('shui_settings');
if (isset($theme['code_editor_theme'])){
	switch ($theme['code_editor_theme']) {
		case 0:
			$theme = "default";
			break;
		case 1:
			$theme = "light";
			break;
		case 2:
			$theme = "dark";
			break;
		default:
			$theme = "default";
			break;
	}
}
$my_meta_2->addCode($prefix.'sh_style',array('theme' => $theme, 'syntax'=> 'css','name'=> 'CSS Style','desc' => 'If your Shortcode have stylesheet classes defined, you can add style sheet definitions here. <br/>This will be output in the page footer. Also leave out opening and ending &lt;style&gt;&lt;/style&gt; tags'));
$my_meta_2->addCode($prefix.'sh_js',array('theme' => $theme,'syntax'=> 'javascript', 'name'=> 'JavaScipt','desc' => 'Must Be a valid JavaScript Code in order for it to Work.<br/>This will be output in the page footer. Also leave out opening and ending &lt;script&gt;&lt;/script&gt; tags'));
$my_meta_2->addCode($prefix.'sh_php',array('theme' => $theme,'syntax'=> 'php','name'=> 'PHP Code','desc' => 'Must Be a valid PHP Code in order for it to Work, Also leave out opening and ending &lt;?php ?&gt; tags'));
$my_meta_2->addRadio($prefix.'php_type',array('echo'=>'my code uses PHP echo','return'=>'my code uses PHP return'),array('name'=> 'What does this code do?', 'std'=> array('echo')));


$my_meta_2->Finish();
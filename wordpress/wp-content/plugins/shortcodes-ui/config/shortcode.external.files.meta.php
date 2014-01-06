<?php /*external metabox*/ ?>
<?php
defined('ABSPATH') OR exit('No direct script access allowed');
$config = array(
	'id' => 'sc_external_meta_box',					// meta box id, unique per meta box
	'title' => 'External Files Section',					// meta box title
	'pages' => array($this->cpt_name),			// post types, accept custom post types as well, default is array('post'); optional
	'context' => 'normal',						// where the meta box appear: normal (default), advanced, side; optional
	'priority' => 'high',						// order of meta box: high (default), low; optional
	'fields' => array()							// list of meta fields (can be added by field arrays)
);
$my_meta_3 =  new AT_Meta_Box($config);
$repeater_fields2[] = $my_meta_3->addText($prefix.'_url',array('name'=> 'External File URL'),true);
$repeater_fields2[] = $my_meta_3->addSelect($prefix.'sh_ex_f_type',array('script'=>'JavaScript','link'=>'CSS Stylesheet'),array('desc'=> 'Select External File Type','name'=> 'External File Type', 'std'=> array('script')),true);
$repeater_fields2[] = $my_meta_3->addSelect($prefix.'sh_location',array('head'=>'Before &lt;/HEAD&gt; Tag','body'=>'Before &lt;/BODY&gt; Tag'),array('desc'=> 'Use this to add external JS and CSS Files','name'=> 'Where to Include', 'std'=> array('body')),true);
$repeater_fields2[] = $my_meta_3->addSelect($prefix.'_enque',array('enqueue'=>'Use wp_enqueue','tag'=>'include as html tag'),array('desc'=> 'Use wp_enqueue will use the built-in wp_enqueue_script() and wp_enqueue_style() functions to include the external JS and CSS Files<br/> include as a tag will simple insert a link and script tags.','name'=> 'How to Include', 'std'=> array('enqueue')),true);
//wp_enqueue_script
$my_meta_3->addRepeaterBlock($prefix.'sh_external',array('name' => 'External Files','fields' => $repeater_fields2,'inline'=>true));

$my_meta_3->Finish();
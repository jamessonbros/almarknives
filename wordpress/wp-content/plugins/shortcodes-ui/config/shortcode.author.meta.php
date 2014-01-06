<?php /* author metabox */ ?>
<?php
defined('ABSPATH') OR exit('No direct script access allowed');
$config = array(
	'id' => 'sc_Author_meta_box',					// meta box id, unique per meta box
	'title' => 'ShortCode Author',					// meta box title
	'pages' => array($this->cpt_name),			// post types, accept custom post types as well, default is array('post'); optional
	'context' => 'side',						// where the meta box appear: normal (default), advanced, side; optional
	'priority' => 'high',						// order of meta box: high (default), low; optional
	'fields' => array()							// list of meta fields (can be added by field arrays)
);
$my_meta_4 =  new AT_Meta_Box($config);
$my_meta_4->addText($prefix.'_Author_Name',array('name'=> 'Author Name'));
$my_meta_4->addText($prefix.'_Author_url',array('name'=> 'Author Url'));
$my_meta_4->addText($prefix.'_Support_url',array('name'=> 'ShortCode Support Url'));
$my_meta_4->addHidden($prefix.'imported',array('std'=>"0"));
$my_meta_4->Finish();
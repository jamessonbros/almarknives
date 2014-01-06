<?php /* Settings admin page */ ?>
<?php
defined('ABSPATH') OR exit('No direct script access allowed');


$options_panel = new shui_SubPage('shui', array('page_title' => __('Settings','shui'),'option_group' => 'shui_settings'));
$options_panel->OpenTabs_container('');
$options_panel->TabsListing(array(
	'links' => array(
		'Settings' =>  __('Settings'),
		'options1' =>  __('Help')
		)
	));
//options page
$options_panel->OpenTab('Settings');
$options_panel->addSubtitle(__('Settings:','shui'));
$options_panel->addRoles(array(
	'id'    => 'cpt',
	'label' => __('Who can create new shortcodes?','shui'),
	'desc'  => __('Only users in the selected role or above will see the Shortcodes UI, be able to create new shortcodes and import/export shortcodes.','shui')
));
$options_panel->addRoles(array(
	'id'    => 'ct',
	'label' => __('Who can create manage shortcodes categories?','shui'),
	'desc'  => __('Only users in the selected role or above will see the Shortcodes UI categories and be able to create new categories.','shui')
));

$options_panel->addDropdown(array(
	'id'      => 'autop',
	'label'   => __('Fix AutoP filter'),
	'options' => array(
		__('Leave as Is')                    => 'no',
		__('Prospond Till After Shortcodes') => 'prospond',
		__('Remove AutoP filter')            => 'remove'),
	'desc'	  => __('WordPress filters the content and adds P tags, this is call autoP filter, which can cause problems with your shortcode.')
));
$options_panel->addDropdown(array(
	'id'    => 'code_editor_theme',
	'label' => __('Code Editor Theme'),
	'options' => array(
		__('Default') => 0,
		__('Light')   => 1,
		__('Dark')    => 2),
	'desc'  => __('Select a theme to use in code editor for shortcodes UI code fields ( CSS, Javascript, PHP ).')
));	
$options_panel->CloseDiv_Container();
//help
$options_panel->OpenTab('options1');
$options_panel->addSubtitle(__('Help:','sis'));
$options_panel->addParagraph('Any feedback or suggestions are welcome at <a href="http://en.bainternet.info/2011/shortcodes-ui">plugin homepage</a>');
$options_panel->addParagraph('<a href="http://wordpress.org/tags/shortcodes-ui?forum_id=10">Support forum</a> for help and bug submission');
$options_panel->addParagraph('Also check out <a href="http://en.bainternet.info/category/plugins">my other plugins</a>');
$options_panel->addParagraph('And if you like my work <span style="color: #FC000D;">make a donation</span>  <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=EXMZGSLS4JAR8"><img src="http://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif"></a>');

$p = new plugin_repo_info('shortcodes-ui');
if ($p->plugin_found){
    $options_panel->addParagraph('Few Plugin stats:');
	$options_panel->addParagraph('Downloaded: ' .$p->get_downloads().' Times.');
    $options_panel->addParagraph($p->get_rating_stars());
}
$options_panel->addParagraph('Help out and give the plugin a <a href="http://wordpress.org/extend/plugins/shortcodes-ui">good rating<a/>' );
$options_panel->CloseDiv_Container();
$options_panel->CloseDiv_Container();
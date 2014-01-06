<?php /* Help Tabs */ ?>
<?php
defined('ABSPATH') OR exit('No direct script access allowed');
$tabs = array(
    array( 
        'title'   => __('Simple Snippet'),
        'v' => 'MKIxhq8elrU'
    ),
    array(
        'title' => __('Simple One Tag'),
        'v'     => 'y-SpsT1dIJ0',
    ),
    array(
        'title' => __('Simple ShortCode with Content'),
        'v'     => 'YxGlfiP-3UA',
    ),
    array(
        'title' => __('Advanced shortcodes'),
        'v'     => '_CMxuF9L_yw',
    ),
    array(
        'title' => __('ShortCodes UI Overview'),
        'v'     => 'GTnnRTTY3m4',
    ),
    array(
        'title' => __('New Features V1.7'),
        'v'     => 'MmOeS-ZKeJc',
    )
);
foreach ($tabs as $tab){
    $screen->add_help_tab(
        array(
            'title' => $tab['title'],
            'id' => str_replace(" ", "_", strtolower($tab['title'])),
            'content' => '<h3>'.$tab['title'].'</h3><iframe width="640" height="480" src="http://www.youtube.com/embed/'.$tab['v'].'?rel=0&showinfo=0&controls=0&autohide=1" frameborder="0" allowfullscreen></iframe>',
        )
    );
}
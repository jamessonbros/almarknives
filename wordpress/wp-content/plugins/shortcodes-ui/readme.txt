=== ShortCodes UI ===
Contributors: bainternet 
Donate link:http://en.bainternet.info/donations
Tags: shortcode, shortcodes ui, shortcode maker, shortcode generator, shortcodes, snippet, snippet manager, snippet ui, editor templates
Requires at least: 2.9.2
Tested up to: 3.6.1
Stable tag: 1.9.8

This Plugin adds an admin UI for creating shortcodes without the need to code, edit code, or even know code.
 

== Description ==

This Plugin adds an admin UI for creating shortcodes without the need to code, edit code, or even know code.
[youtube http://www.youtube.com/watch?v=n3-dy-PGJrs]

**Features**

*	Based on the native custom post type (no extra tables in the database).
*	Ajaxed Import/Export functionality (so you can move your shortcodes from one site to another and shared them around).
*	Built in Tinymce (visual editor) button with an Ajaxed popup panel.
*	Built in Quicktag (HTML editor) button with an Ajaxed popup panel.
*	Works with all posts types (post,page,custom).
*	Each shortcode Can be used as a Template Tag.
*	Each shortcode has its own filter hook.
*	syntax highlighted code editor (NEW).
*	Render the shortcode into editor (NEW).
* 	Built in tutorial Videos (NEW).
*	Shortcode UI widget, You can now embed wny shortcode inside a shortcodes UI widget (NEW).
*	Export as standalone plugin (SOON).

any feedback or suggestions are welcome.

check out my [other plugins][1]


[1]: http://en.bainternet.info/category/plugins


== Installation ==
Simple steps:  

1.  Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation.
1.  Then activate the Plugin from Plugins page.
1.  Done!

== Frequently Asked Questions ==
=What are the requirements?=

PHP 5.2 and up.

=I have Found a Bug, Now what?=

Simply use the <a href=\"http://wordpress.org/tags/shortcodes-ui/?forum_id=10\">Support Forum</a> and thanks a head for doing that.

=How to Use?=
[youtube http://www.youtube.com/watch?v=n3-dy-PGJrs]

== Screenshots ==

1. shortcode ui Shortcodes listing.

2. Shortcode ui.

3. shortcode ui Tinymce button.

4. shortcode ui Quicktag button.

5. shortcode ui panel.

6. shortcode ui panel insert to post button.

7. shortcode ui Widget.
== Changelog ==
1.9.8
Fixed Bug on installs when prefix is not wp_ (which you should always change).

1.9.7
Fixed bug caused by nextgen gallery.

1.9.6
Added Shortcode UI widget.

1.9.5
Added Multilangual with WPML support ish.

1.9.4
Fixed Advanced shortcode not working. 
WordPress 3.6 compatiblility.

1.9.3
Fixed Shortcodes not showing after 1.9.2

1.9.2 Major file restrcture.
moved js to external file.
moved ajax functionalty to external file.
moved help tabs to external file.
moved meta boxes to external files.
moved import export panel to external file.
moved all classes to classes folder.
added Suppress content filters option.
added filter tag `shortcodes_ui_raw_content`.
updated meta box class to latest version 3.0.5 .
code cleanup.


1.9.1 Fixed render into post issue

1.9 removed remote calls for image or css as per guidlines.

1.8.9 Changed cpt hook to solve conflicting with other plugins.

1.8.8 Added action hooks to remove and return filters 
`scui_external_hooks_remove`
`scui_external_hooks_return`

1.8.7 Fixed extra P tags on snippets.

1.8.6 Fixed snippets and simple content not displying correctly.

1.8.5 Fixed JS error when the editor is not present.

1.8.4 Typo

1.8.3 Changed import to use post instead of get for larger imports

1.8.2 Fixed WP_DEBUG errors.

1.8.1 fixed Typo.

1.8 Fixed paypal redirect bug on options page, added plugin info class.

1.7.1 Fixed simplebox js to fixed top 100px for editors other then main content editor.

1.7 Added syntax highlighted code editor to shortcode UI code (css, Javascript, php) editors.

Added a new option to render the shortcode into editor instead of just inserting the shortcode tag.

added an AutoP fix options.

added a new feature tut video.

updated metabox class.


1.6.3 Edbuttons bug fixed (js now only included on edit pages).

fixed html editor button ajax function.

1.6.2 notice in metabox class fixed.

notice in plugin class fixed.

sortcodes UI panel ajax bug fixed.

1.6.1 restored missing Shourcodes ui metaboxes.

1.6 cleaned Code, Fixed dashboard widgets bug.

1.5 Added three more tutorial videos.

1.4 Added two tutorial videos to WordPress 3.3 help tabs, fixed attributes and external files JS.

1.3 Fixed Quicktag button to work with the new quicktags api.

1.2 Big Update, ReCoded External File inclution and added a feature to use WordPress Built-in 'wp_enquire_script()' and 'wp_enquire_style()'

Added options panel to let you control how can create shortcodes by role.

Fixed Import,Export panel to include scripts correctly.

1.1 added shortcode filter support and fixed minor bugs

1.0 initial release.
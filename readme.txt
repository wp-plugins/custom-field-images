=== Custom Field Images ===
Contributors: scribu
Tags: custom-fields, images, thumbs
Requires at least: 2.5
Tested up to: 2.5
Stable tag: trunk

Display images at the top of your post content, excerpt and feed using custom fields.

== Description ==

Like it's name suggests, Custom Field Images displays images using post metadata. It adds a simple box under the post editing screen for easy handling.

Credits go to [Justin Tadlock](http://justintadlock.com/archives/2007/10/27/wordpress-custom-fields-adding-images-to-posts), where I got the ideea from.

== Installation ==

Custom Field Images can be installed in 3 easy steps:

1. Unzip "Custom Field Images" archive and put the folder into your "plugins" folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins admin menu.
1. Inside the Wordpress admin, go to Settings > Custom Field Images, adjust the options according to your needs, and save them.s
	
== Frequently Asked Questions ==

= How can I show custom field images only on certain page types? =

You can add `<?php custom_field_image() ?>` inside The Loop in the theme files where you want them to appear. In addition, you can use [conditional tags](http://codex.wordpress.org/Conditional_Tags). Be sure to check the settings so as not to have duplicate images.

For example, if you want them to appear only when viewing posts by category, add the above function to category.php in your theme directory and uncheck "Display in: content and/or excerpt" from the settings page.

= Why aren't my images aligning properly? =

This is due to the fact that your theme doesn't have the 'wp_head' hook included. You can simply copy the CSS from align.css into your theme's stylesheet.
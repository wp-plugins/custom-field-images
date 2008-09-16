=== Custom Field Images ===
Contributors: scribu
Donate link: http://scribu.net/projects
Tags: custom fields, images, thumbs
Requires at least: 2.5
Tested up to: 2.6+
Stable tag: 1.5

Display images at the top of your post content, excerpt and feed using custom fields.

== Description ==

Custom Field Images gives you increased flexibility in displaying images on specific page types, post excerpts and even feeds.

**Features**

* Simple box under the post editing screen for easy handling
* One click import and export of existing images
* Customizable defaults so that the only thing you need to add is the image URL.

Credits go to [Justin Tadlock](http://justintadlock.com/archives/2007/10/27/wordpress-custom-fields-adding-images-to-posts), where I got the original ideea from.

== Installation ==

The plugin can be installed in 2 easy steps:

1. Unzip "Custom Field Images" archive and put the folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins menu.

== Frequently Asked Questions ==

= How can I show custom field images only on certain page types? =

You can add `<?php custom_field_image() ?>` inside The Loop in the theme files where you want them to appear. In addition, you can use [conditional tags](http://codex.wordpress.org/Conditional_Tags). Be sure to check the settings so as not to have duplicate images.

For example, if you want them to appear only when viewing posts by category, add the above function to category.php in your theme directory and uncheck "Display in: content and/or excerpt" from the settings page.

= Why aren't my images aligning properly? =

This is due to your theme's CSS. Check if you have something like this in style.css:

`img.alignleft { float:left; margin: 0 1em .5em 0; }
img.alignright { float:right; margin: 0 0 .5em 1em; }
img.aligncenter { display:block; margin:0 auto .5em auto; }`

= How can I delete a custom field image from a post? =

Just clear the URL field and save the post.

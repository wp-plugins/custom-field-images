=== Custom Field Images ===
Contributors: scribu
Donate link: http://scribu.net/wordpress
Tags: custom fields, images, thumbs
Requires at least: 2.8
Tested up to: 2.8.5
Stable tag: trunk

Easily associate any image to a post and display it in post excerpts, feeds etc.

== Description ==

Custom Field Images provides a flexible way to associate an image to a post and display it anywhere on your site.

**Easily insert image data**

The plugin adds a custom meta box which is designed specifically for image data, making it easier to use than the generic custom fields box. There is also a button for filling the box automatically with an image uploaded through WordPress.

**Manage all images in one go**

The plugin has button for importing / exporting / deleting any available images, all at once. Also, you can set default settings for all images, so you don't have to edit every image if you change your site's layout.

**Show recent posts as images**

Instead of displaying recent posts as plain links, you can display them as a list of images, using the built-in widget.

== Installation ==

You can use the WordPress plugin installer, or do it manually:

1. Unzip "Custom Field Images" archive and put the folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins menu.

= Usage =

You can add `<?php custom_field_image() ?>` inside The Loop in the theme files where you want them to appear. Be sure to check the settings so as not to have duplicate images.

For example, if you want them to appear only when viewing posts by category, add the above function to category.php in your theme directory and uncheck "Display in: content and/or excerpt" from the settings page.

You can also add `[cfi]` where you want the image to appear in the post content (not excerpt). By default, it is inserted at the beginning.

There is also an additional template tag, called `get_custom_field_image()` which you can use to display the images however you want. See `template-tags.php` in the plugin directory for more details.

== Frequently Asked Questions ==

= "Parse error: syntax error, unexpected T_CLASS..." Help! =

Make sure your new host is running PHP 5. Add this line to wp-config.php:

`var_dump(PHP_VERSION);`

= Why are the images displayed two times? =

Probably because you used the template tag and forgot to uncheck the content, excerpt or feed checkboxes from the Settings page.

= Why aren't my images aligning properly? =

This is due to your theme's CSS. Check if you have something like this in style.css:

`img.alignleft {float:left; margin: 0 1em .5em 0}
img.alignright {float:right; margin: 0 0 .5em 1em}
img.aligncenter {display:block; margin: 0 auto .5em auto}`

= What if I don't want to use the widget? =

You can add `<?php cfi_loop($query) ?>` directly to your theme files. $query is an optional parameter, which acts as a [query string](http://codex.wordpress.org/Template_Tags/query_posts).

= How can I delete a custom field image from a post? =

Just clear the URL field and save the post.

== Screenshots ==

1. The custom box for inserting image data
2. The quick insert button
3. The settings page

== Changelog ==

= 2.2.2 =
* fixed image link on single posts

= 2.2 = 
* added border:0; to all styles for feeds
* if displaying the post, show only the image, without the link
* if size not available, use default
* grab the first image in the gallery tab and not the first image uploaded
* added Russian translation
* [more info](http://scribu.net/wordpress/custom-field-images/cfi-2-2.html)

= 2.1.1 =
* fixed warning
* set default size to thumbnail

= 2.1 =
* added first attachment option
* better values for get_custom_field_image()
* bugfix for IE
* [more info](http://scribu.net/wordpress/custom-field-images/cfi-2-1.html)

= 2.0 =
* add image by ID and size
* better template tags
* default URL
* fixed l10n
* [more info](http://scribu.net/wordpress/custom-field-images/cfi-2-0.html)

= 1.9 =
* l10n
* better admin page
* fixed (Insert CFI) button
* [more info](http://scribu.net/wordpress/custom-field-images/cfi-1-9.html)

= 1.8 =
* CFI Loop widget
* [more info](http://scribu.net/wordpress/custom-field-images/cfi-1-8.html)

= 1.7 =
* (Insert CFI) button
* [more info](http://scribu.net/wordpress/custom-field-images/cfi-1-7.html)

= 1.6 =
* [cfi] shortcode
* duplicate alt. text as title
* [more info](http://scribu.net/wordpress/custom-field-images/cfi-1-6.html)

= 1.5 =
* import / export functionality
* option to automatically link to associated post

= 1.4 =
* extra attributes for image link

= 1.3 =
* one field per image instead of 4

= 1.2 =
* general improvements

= 1.1 =
* page support

= 1.0 =
* initial release


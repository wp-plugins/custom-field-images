=== Custom Field Images ===
Contributors: scribu
Donate link: http://scribu.net/wordpress
Tags: custom fields, images, thumbs
Requires at least: 2.5
Tested up to: 2.8
Stable tag: trunk

Easily manage and display images in post excerpts, feeds etc. using custom fields.

== Description ==

Custom Field Images gives you increased flexibility in displaying images on specific page types, post excerpts and even feeds.

**Easily insert image data**

The custom meta box is designed specifically for image data, making it easier to use than the generic Custom Fields box. There is also a button for filling the box after an image is uploaded through WordPress.

**Manage all images in one go**

The plugin has a management page from which you can import / export / delete available images, all at once. Also, you can set defaults for all images, so you don't have to edit every image if you change your site's layout.

**Show recent posts as images**

Instead of displaying recent posts as a list of plain links, you can display them as a list of images, using the built-in widget.

== Installation ==

The plugin can be installed in 2 easy steps:

1. Unzip "Custom Field Images" archive and put the folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins menu.

= Usage =

You can add `<?php custom_field_image() ?>` inside The Loop in the theme files where you want them to appear. Be sure to check the settings so as not to have duplicate images.

For example, if you want them to appear only when viewing posts by category, add the above function to category.php in your theme directory and uncheck "Display in: content and/or excerpt" from the settings page.

You can also add `[cfi]` where you want the image to appear in the post content (not excerpt). By default, it is inserted at the beginning.

== Frequently Asked Questions ==

= Why are the images displayed two times? =
Probably because you used the template tag and forgot to uncheck the content, excerpt or feed checkboxes from the Settings page.

= Why aren't my images aligning properly? =

This is due to your theme's CSS. Check if you have something like this in style.css:

`img.alignleft {float:left; margin: 0 1em .5em 0}
img.alignright {float:right; margin: 0 0 .5em 1em}
img.aligncenter {display:block; margin:0 auto .5em auto}`

= What if I don't want to use the widget? =
You can add `<?php cfi_loop($query) ?>` directly to your theme files. $query is an optional parameter, which acts as a [query string](http://codex.wordpress.org/Template_Tags/query_posts).

= How can I delete a custom field image from a post? =

Just clear the URL field and save the post.

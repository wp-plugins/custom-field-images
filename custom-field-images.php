<?php
/*
Plugin Name: Custom Field Images
Version: 1.4.2.2
Description: (<a href="options-general.php?page=custom-field-images"><strong>Settings</strong></a>) Easily display images anywhere using custom fields.
Author: scribu
Author URI: http://scribu.net/
Plugin URI: http://scribu.net/projects/custom-field-images.html
*/

/*
Copyright (C) 2008 scribu.net (scribu AT gmail DOT com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class cfImg {

	// Styles for the feed images. Feel free to customize
	var $styles = array(
		'left' => 'float:left; margin: 0 1em .5em 0;',
		'center' => 'display:block; margin:0 auto .5em auto;',
		'right' => 'float:right; margin: 0 0 .5em 1em;'
	);

	// Data fields for each image
	var $data = array(
		'cfi-url' => '',
		'cfi-align' => '',
		'cfi-alt' => '',
		'cfi-link' => ''
	);

	// wp_postmeta -> meta_key
	var $field = '_cfi_image';

	// Various display options
	var $options = array(
		'default_align' => 'right',
		'extra_attr' => '',

		'content' => TRUE,
		'feed' => TRUE,
		'excerpt' => TRUE
	);

	function __construct() {
		$this->options = get_option('cfi_options');

		add_filter('the_content', array(&$this, 'to_content'));
		add_filter('the_excerpt', array(&$this, 'to_excerpt'));
	}

	function to_content($content) {
		$is_feed = is_feed();
		if ( ($is_feed && $this->options['feed']) || (!$is_feed && $this->options['content']) )
			return $this->generate() . $content;

		return $content;
	}

	function to_excerpt($excerpt) {
		$is_feed = is_feed();
		if ( ($is_feed && $this->options['feed']) || (!$is_feed && $this->options['excerpt']) )
			return $this->generate() . $excerpt;

		return $excerpt;
	}

	function load() {
		global $post;

		$this->data = unserialize(get_post_meta($post->ID, $this->field, TRUE));
	}

	function generate() {
		$this->load();

		$url = $this->data['cfi-url'];

		if (!$url)
			return;

		// Begin img tag
		$image.= '<img src="'. $url .'" ';

		// Set alignment
		$align = $this->data['cfi-align'] ? $this->data['cfi-align'] : 'right';

		if (is_feed())
			$image .= 'style="' . $this->styles[$align] .'" ';
		else
			$image .= 'class="cfi align'. $align .'" ';

		// Set alt text
		$alt = $this->data['cfi-alt'];

		$image .= 'alt="';

		if ($alt)
			$image .= $alt .'" ';
		else
			$image .= get_the_title() .'" ';

		// End img tag
		$image .= '/>';

		return $this->add_link($image);
	}

	function add_link($image) {
		// Sets the link for the image

		$link = $this->data['cfi-link'];

		if (!$link)
			return $image;

		$output = '<a href="'. $link . '"';
		$output .= ' ' . stripslashes($this->options['extra_attr']);
		$output .= '>' . $image . '</a>'."\n";

		return $output;
	}
}

// Init
if ( is_admin() )
	require_once('inc/admin.php');
else
	$cfImg = new cfImg();

// Activate
register_activation_hook(__FILE__, create_function('', '$admin = new cfImgAdmin(); $admin->activate();') );

// Functions
function custom_field_image() {
	global $cfImg;
	echo $cfImg->generate();
}
?>

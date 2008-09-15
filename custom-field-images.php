<?php
/*
Plugin Name: Custom Field Images
Version: 1.5b:3
Description: (<a href="edit.php?page=custom-field-images">Manage</a> | <a href="options-general.php?page=custom-field-images">Settings</a>) Easily display images anywhere using custom fields.
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

	// Styles for images in feeds
	var $styles = array(
		'left' => 'float:left; margin: 0 1em .5em 0;',
		'center' => 'display:block; margin:0 auto .5em auto;',
		'right' => 'float:right; margin: 0 0 .5em 1em;'
	);

	// $post->ID
	var $id;

	// wp_postmeta -> meta_key
	var $key = '_cfi_image';

	// Data fields for each image
	var $data = array(
		'url' => '',
		'align' => '',
		'alt' => '',
		'link' => ''
	);

	// Display options
	var $options = array(
		'default_align' => 'right',
		'default_link' => TRUE,
		'extra_attr' => '',

		'content' => TRUE,
		'feed' => TRUE,
		'excerpt' => TRUE
	);

	function __construct() {
		$this->options = get_option('cfi_options');

		add_filter('the_excerpt', array(&$this, 'filter'));
		add_filter('the_content', array(&$this, 'filter'));
	}

	function filter($content) {
		$type = substr(current_filter(), 4);
		$is_feed = is_feed();

		if ( ($is_feed && $this->options['feed']) || (!$is_feed && $this->options[$type]) )
			return $this->generate() . $content;

		return $content;
	}

	function load($post_id = '') {
		global $post;

		$this->id = $post_id ? $post_id : $post->ID;

		$this->data = get_post_meta($this->id, $this->key, TRUE);
	}

	function generate($post_id = '') {
		$this->load($post_id);

		$url = $this->data['url'];

		if ( !$url )
			return;

		// Begin img tag
		$image.= '<img src="'. $url .'" ';

		// Set alignment
		$align = $this->data['align'] ? $this->data['align'] : $this->options['default_align'];

		if ( is_feed() )
			$image .= 'style="' . $this->styles[$align] .'" ';
		else
			$image .= 'class="cfi align'. $align .'" ';

		// Set alt text
		$alt = $this->data['alt'] ? $this->data['alt'] : get_the_title();

		$image .= 'alt="' . $alt . '" ';

		// End img tag
		$image .= '/>';

		return $this->add_link($image);
	}

	function add_link($image) {
		// Sets the link for the image

		$link = $this->data['link'];

		if ( !$link )
			if ( !$this->options['default_link'] )
				return $image;
			else
				$link = get_permalink($this->id);

		$output = '<a href="'. $link . '"';
		$output .= ' ' . stripslashes($this->options['extra_attr']);
		$output .= '>' . $image . '</a>';

		return $output;
	}
}

// Init
if ( is_admin() ) {
	require_once('inc/admin.php');

	$cfImgAdmin = new cfImgAdmin();

	register_activation_hook(__FILE__, array(&$cfImgAdmin, 'activate'));
} else
	$cfImg = new cfImg();

// Template tag
function custom_field_image() {
	global $cfImg;
	echo $cfImg->generate();
}


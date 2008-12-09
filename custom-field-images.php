<?php
/*
Plugin Name: Custom Field Images
Version: 1.7a
Description: Easily manage and display images anywhere using custom fields.
Author: scribu
Author URI: http://scribu.net/
Plugin URI: http://scribu.net/projects/custom-field-images.html

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

class displayCFI {

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
		'add_title' => TRUE,
		'default_link' => TRUE,
		'extra_attr' => '',

		'content' => TRUE,
		'feed' => TRUE,
		'excerpt' => TRUE
	);

	// PHP4 compatibility
	function displayCFI() {
		$this->__construct();
	}

	function __construct() {
		$this->options = get_option('cfi_options');

		add_filter('the_excerpt', array(&$this, 'filter'));
		add_filter('the_content', array(&$this, 'filter'));
	}

	function filter($content) {
		$type = substr(current_filter(), 4);
		$is_feed = is_feed();

		if ( ($is_feed && $this->options['feed']) || (!$is_feed && $this->options[$type]) )
			if ( $type != 'excerpt' && (FALSE !== strpos($content, '[cfi]')) )
				return str_replace('[cfi]', $this->generate(), $content);
			else
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
			$image .= sprintf( 'style="%s" ', $this->styles[$align] );
		else
			$image .= sprintf( 'class="cfi align%s" ', $align );

		// Set alt text
		$alt = $this->data['alt'] ? $this->data['alt'] : get_the_title();

		$image .= sprintf( 'alt="%s" ', $alt );

		// Set title
		if ( $this->options['add_title'] )
			$image .= sprintf( 'title="%s" ', $alt );

		// End img tag
		$image .= '/>';

		return $this->add_link($image);
	}

	function add_link($image) {
		$link = $this->data['link'];

		if ( !$link )
			if ( !$this->options['default_link'] || is_single() || is_page() )
				return $image;
			else
				$link = get_permalink($this->id);

		return sprintf( '<a href="%s" %s>' . $image . '</a>', $link, stripslashes($this->options['extra_attr']) );
	}
}

// Init
if ( is_admin() ) {
	require_once('inc/admin.php');
	new adminCFI(__FILE__);
}
	
$CFIobj = new displayCFI();

// Template tag
function custom_field_image() {
	global $CFIobj;
	echo $CFIobj->generate();
}


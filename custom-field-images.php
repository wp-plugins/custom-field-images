<?php
/*
Plugin Name: Custom Field Images
Version: 1.8b
Description: Easily manage and display images anywhere using custom fields.
Author: scribu
Author URI: http://scribu.net/
Plugin URI: http://scribu.net/projects/custom-field-images

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
	public $key = '_cfi_image';

	// Data fields for each image
	var $data = array(
		'url' => '',
		'align' => '',
		'alt' => '',
		'link' => ''
	);

	// Options object
	var $options;

	public function __construct() {
		global $CFI_options;

		$this->options = $CFI_options;

		add_filter('the_excerpt', array(&$this, 'filter'));
		add_filter('the_content', array(&$this, 'filter'));
	}

	public function filter($content) {
		$type = substr(current_filter(), 4);
		$is_feed = is_feed();

		if ( ($is_feed && $this->options->get('feed')) || (!$is_feed && $this->options->get($type)) )
			if ( $type != 'excerpt' && (FALSE !== strpos($content, '[cfi]')) )
				return str_replace('[cfi]', $this->get(), $content);
			else
				return $this->get() . $content;

		return $content;
	}

	public function get($post_id = '') {
		return $this->add_link($this->generate($post_id));
	}

	public function generate($post_id = '') {
		$this->load($post_id);

		$url = $this->data['url'];

		if ( !$url )
			return;

		// Begin img tag
		$image .= '<img src="'. $url .'" ';

		// Set alignment
		$align = $this->data['align'] ? $this->data['align'] : $this->options->get('default_align');

		if ( is_feed() )
			$image .= sprintf( 'style="%s" ', $this->styles[$align] );
		else
			$image .= sprintf( 'class="cfi align%s" ', $align );

		// Set alt text
		$alt = $this->data['alt'] ? $this->data['alt'] : get_the_title();

		$image .= sprintf( 'alt="%s" ', $alt );

		// Set title
		if ( $this->options->get('add_title') )
			$image .= sprintf( 'title="%s" ', $alt );

		// End img tag
		$image .= '/>';

		return $image;
	}

	public function add_link($image, $link = '') {
		if ( empty($link) )
			$link = $this->data['link'];

		if ( !$link )
			if ( !$this->options->get('default_link') )
				return $image;
			else
				$link = get_permalink($this->id);

		return sprintf( "<a href='$link' %s>$image</a>", stripslashes($this->options->get('extra_attr')) );
	}

	protected function load($post_id = '') {
		global $post;

		$this->id = $post_id ? $post_id : $post->ID;

		$this->data = get_post_meta($this->id, $this->key, TRUE);
	}
}

// Init
global $CFI_options, $CFI_display;

// Create options instance
if ( !class_exists('scbOptions') )
	require_once('inc/scbOptions.php');
$CFI_options = new scbOptions('cfi_options');

// Create display instance
$CFI_display = new displayCFI();

// Create widget instance
require_once('widget.php');
$CFI_widget = new widgetCFI(__FILE__);

// Load admin classes
if ( is_admin() ) {
	require_once(dirname(__FILE__).'/admin.php');
	cfi_admin_init(__FILE__);
}


// Template tag
function custom_field_image() {
	global $CFI_display;
	echo $CFI_display->get();
}


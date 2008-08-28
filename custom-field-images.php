<?php
/*
Plugin Name: Custom Field Images
Version: 1.2.8b
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

/******************************/
/*** BEGIN Editable options ***/
/******************************/

	var $new_window = TRUE;		// Set to TRUE if you want links to open in a new window

	var $styles = array(
		'left' => 'float:left; margin: 0 1em .5em 0;',
		'center' => 'display:block; margin:0 auto .5em auto;',
		'right' => 'float:right; margin: 0 0 .5em 1em;'
	);

/******************************/
/**** END Editable options ****/
/******************************/

	var $data = array(
		'cfi-url' => '',
		'cfi-align' => '',
		'cfi-alt' => '',
		'cfi-link' => ''
	);

	var $show_in = array(
		'content' => TRUE,
		'feed' => TRUE,
		'excerpt' => TRUE,
	);

	function __construct() {
		$this->show_in = get_option('cfi-show-in');
		
		if ($this->show_in['content']) {
			add_filter('the_content', array(&$this, 'display'));
		}

		if ($this->show_in['excerpt']) {
			add_filter('the_excerpt', array(&$this, 'display'));
		}
		
		if ($this->show_in['feed'])
			//add_filter('the_content_rss', array(&$this, 'display'));
			add_filter('the_content', array(&$this, 'display'));	// hack
	}

	function display($content) {
		// Checks if we should display the image in feeds or not

		$is_feed = is_feed();
		if ( ($is_feed && $this->show_in['feed']) || (!$is_feed && $this->show_in['content']) )
			return $this->generate() . $content;
		else
			return $content;
	}

	function load() {
		// Loads cfi data for current post

		global $post;

		$custom_fields = get_post_custom($post->ID);

		foreach ($this->data as $key => $value)
			$this->data[$key] = stripslashes($custom_fields[$key][0]);

		if ($this->data['cfi-align'] == '')
			$this->data['cfi-align'] = 'right';
	}

	function generate() {
		// Creates the image tag

		$this->load();

		$url = $this->data['cfi-url'];

		if (!$url)
			return;

		// Begin img tag
		$image.= '<img src="'. $url .'" ';

		// Set alignment
		$align = $this->data['cfi-align'];
		if (is_feed())
			$image .= 'style="' . $this->styles[$align] .'" ';
		else
			$image .= 'class="align'. $align .'" ';

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

		if ($this->new_window)
			$output .= ' target="_blank"';

		$output .= '>' . $image . '</a>'."\n";

		return $output;
	}
}

// Init
if ( is_admin() )
	require_once('inc/admin.php');
else
	$cfImg = new cfImg();

// Functions
function custom_field_image() {
	global $cfImg;
	echo $cfImg->generate();
}
?>

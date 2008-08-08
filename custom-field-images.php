<?php
/*
Plugin Name: Custom Field Images
Version: 1.2.5
Description: Easily display images anywhere using custom fields.
Author: scribu
Author URI: http://scribu.net/
Plugin URI: hhttp://scribu.net/projects/custom-field-images.html
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

/****************************/
/***** Editable options *****/
/****************************/

	var $styles = array(
		'left' => 'float:left; margin: 0 1em .5em 0;',
		'center' => 'display:block; margin:0 auto .5em auto;',
		'right' => 'float:right; margin: 0 0 .5em 1em;'
	);

/****************************/
/* Do not modify anything below */
/****************************/

	var $data = array(
		'cfi-url' => '',
		'cfi-align' => '',
		'cfi-alt' => '',
		'cfi-link' => ''
	);

	var $show_in = array();

	function __construct() {
		$this->show_in = get_option('cfi_show_in');
		
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

	function load() {
		global $post;

		$custom_fields = get_post_custom($post->ID);

		foreach ($this->data as $key => $value)
			$this->data[$key] = stripslashes($custom_fields[$key][0]);

		if ($this->data['cfi-align'] == '')
			$this->data['cfi-align'] = 'right';
	}

	function generate() {
		$this->load();

		$url = $this->data['cfi-url'];
		if ($url) {
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

			// Set link
			$link = $this->data['cfi-link'];
			if ($link)
			$image = '<a href="'. $link . '">' . $image . '</a>'."\n";
			return $image;
		}
	}

	function display($content) {
		$is_feed = is_feed();
		if ( ($is_feed && $this->show_in['feed']) || (!$is_feed && $this->show_in['content']) )
			return $this->generate() . $content;
		else
			return $content;
	}
}

// Init
if ( is_admin() )
	include ('cfImgAdmin.class.php');
else
	$cfImg = new cfImg();

// Functions
function custom_field_image() {
	global $cfImg;
	echo $cfImg->generate();
}
?>

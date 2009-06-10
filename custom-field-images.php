<?php
/*
Plugin Name: Custom Field Images
Description: Easily manage and display images anywhere using custom fields.
Version: 1.8.4a
Author: scribu
Author URI: http://scribu.net/
Plugin URI: http://scribu.net/wordpress/custom-field-images

Copyright (C) 2009 scribu.net (scribu AT gmail DOT com)

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

require_once dirname(__FILE__) . '/inc/scb-check.php';
if ( !scb_check(__FILE__) ) return;

class displayCFI 
{
	// Styles for images in feeds
	public $styles = array(
		'left' => 'float:left; margin: 0 1em .5em 0;',
		'center' => 'display:block; margin:0 auto .5em auto;',
		'right' => 'float:right; margin: 0 0 .5em 1em;'
	);

	// Data fields for current image
	public $data = array(
		'url' => '',
		'align' => '',
		'alt' => '',
		'link' => ''
	);

	// wp_postmeta.meta_key
	public $key = '_cfi_image';

	// $post->ID
	protected $id;

	// Options object holder
	protected $options;

	public function __construct($options)
	{
		$this->options = $options;

		add_filter('the_excerpt', array(&$this, 'filter'));
		add_filter('the_content', array(&$this, 'filter'));
	}

	public function filter($content)
	{
		$type = substr(current_filter(), 4);
		$is_feed = is_feed();

		if ( ($is_feed && $this->options->get('feed')) || (!$is_feed && $this->options->get($type)) )
			if ( $type != 'excerpt' && (FALSE !== strpos($content, '[cfi]')) )
				return str_replace('[cfi]', $this->generate(), $content);
			else
				return $this->generate() . $content;

		return $content;
	}

	public function generate($post_id = '', $data = '' )
	{
		$this->load($post_id);

		if ( is_array($data) )
			$this->data = @array_merge($this->data, $data);

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

		return $this->add_link($image);
	}

	public function loop($query)
	{
		$query = wp_parse_args($query, array(
			'meta_key' => $CFI_display->key,
			'post_type' => 'post',
			'post_status' => 'publish'
		));

		$side_query = new WP_query($query);

		ob_start();

		// Do the loop
		echo "<ul id='cfi-loop'>";
		while ( $side_query->have_posts() ) : $side_query->the_post();
			echo "<li>";
			echo $this->generate($post->ID, array(
				'alt' => $post->post_title,
				'align' => '',
				'link' => get_permalink($post->ID)
			));
			echo "</li>\n";
		endwhile;
		echo "</ul>\n";

		return ob_get_clean();
	}

	protected function add_link($image)
	{
		$link = $this->data['link'];

		if ( empty($link) )
			if ( $this->options->get('default_link') )
				$link = get_permalink($this->id);
			else
				return $image;

		return sprintf( "<a href='$link' %s>$image</a>", stripslashes($this->options->get('extra_attr')) );
	}

	protected function load($post_id = '')
	{
		global $post;

		$this->id = $post_id ? $post_id : $post->ID;

		$this->data = get_post_meta($this->id, $this->key, TRUE);
	}
}


// Init
_cfi_init();
function _cfi_init()
{
	// Load translations
	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain('custom-field-images', "wp-content/plugins/$plugin_dir/lang", "$plugin_dir/lang");

	$options = new scbOptions('cfi_options', __FILE__, array(
			'default_align' => 'right',
			'add_title' => TRUE,
			'default_link' => TRUE,
			'extra_attr' => '',
			'insert_button' => TRUE,

			'content' => TRUE,
			'feed' => TRUE,
			'excerpt' => TRUE
	));

	$GLOBALS['CFI_display'] = new displayCFI($options);

	// Load widget class
	require_once(dirname(__FILE__) . '/widget.php');
	scbWidget::init('widgetCFI', __FILE__, 'cfi-loop');

	// Load admin classes
	if ( is_admin() )
	{
		require_once(dirname(__FILE__) . '/admin.php');
		cfi_admin_init(__FILE__, $options);
	}

	// Load template tags
	require_once(dirname(__FILE__) . '/template-tags.php');
}


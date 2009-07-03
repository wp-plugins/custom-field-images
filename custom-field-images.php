<?php
/*
Plugin Name: Custom Field Images
Description: Easily associate any image to a post and display it in post excerpts, feeds etc.
Version: 2.0rc2
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


// Init
_cfi_init();
function _cfi_init()
{
	// Load translations
	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain('custom-field-images', "wp-content/plugins/$plugin_dir/lang", "$plugin_dir/lang");

	// Load scbFramework
	require_once dirname(__FILE__) . '/inc/scb/load.php';

	$options = new scbOptions('cfi_options', __FILE__, array(
		'default_url' => '',
		'default_align' => '',
		'default_link' => TRUE,
		'extra_attr' => '',
		'insert_button' => TRUE,

		'content' => TRUE,
		'feed' => TRUE,
		'excerpt' => TRUE
	));

	displayCFI::init($options);

	// Load template tags
	require_once dirname(__FILE__) . '/template-tags.php';

	// Load widget class
	if ( class_exists('WP_Widget') )
	{
		require_once dirname(__FILE__) . '/widget.php';
		scbWidget::init('widgetCFI', __FILE__, 'cfi-loop');
	}

	// Load admin classes
	if ( is_admin() )
	{
		require_once dirname(__FILE__) . '/admin.php';
		cfi_admin_init(__FILE__, $options);
	}
}

abstract class displayCFI
{
	// wp_postmeta.meta_key
	const key = '_cfi_image';

	const token = '[cfi]';

	// Styles for images in feeds
	static $styles = array(
		'left' => 'float:left; margin: 0 1em .5em 0;',
		'center' => 'display:block; margin:0 auto .5em auto;',
		'right' => 'float:right; margin: 0 0 .5em 1em;'
	);

	// Data fields for current image
	static $data = array(
		'id' => '',
		'size' => '',
		'url' => '',
		'align' => '',
		'alt' => '',
		'link' => ''
	);

	// $post->ID
	static $id;

	// Options object holder
	static $options;

	static function init($options)
	{
		self::$options = $options;

		add_filter('the_excerpt', array(__CLASS__, 'filter'), 20);
		add_filter('the_content', array(__CLASS__, 'filter'), 20);
	}

	static function filter($content)
	{
		$type = substr(current_filter(), 4);
		$is_feed = is_feed();

		$cond = ($is_feed && self::$options->feed) || (!$is_feed && self::$options->$type);

		if ( !$cond )
			return $content;

		$img = self::generate();

		if ( $type != 'excerpt' && FALSE !== strpos($content, self::token) )
			$content = str_replace(self::token, $img, $content);
		else
			$content = $img . $content;

		// For partial feeds
//		if ( is_feed() )
//			echo $img;

		return $content;
	}

	static function generate($post_id = '', $defaults = '')
	{
		self::load($post_id);

		if ( ! empty($defaults) )
			self::$data = wp_parse_args($defaults, self::$data);

		if ( self::$data['id'] )
		{
			if ( self::$data['size'] )
				$data = image_downsize(self::$data['id'], self::$data['size']);
 			else
 				$data = image_downsize(self::$data['id']);

			$url = $data[0];
		}

		if ( empty($url) )
			$url = self::$data['url'];

		if ( empty($url) )
			$url = self::$options->default_url;

		if ( empty($url) )
			return;

		// Begin img tag
		$image .= '<img src="'. $url .'" ';

		// Set alignment
		$align = self::$data['align'] ? self::$data['align'] : self::$options->default_align;

		if ( is_feed() )
			$image .= sprintf( 'style="%s" ', self::$styles[$align] );
		else
			$image .= sprintf( 'class="cfi align%s" ', $align );

		// Set alt text & title
		$alt = self::$data['alt'] ? self::$data['alt'] : get_the_title();

		$image .= sprintf( 'alt="%s" title="%s"', $alt, $alt);

		// End img tag
		$image .= '/>';

		return self::add_link($image);
	}

	static function loop($query)
	{
		$query = wp_parse_args($query, array(
			'meta_key' => displayCFI::key,
			'post_type' => 'post',
			'post_status' => 'publish'
		));

		$side_query = new WP_query($query);

		ob_start();

		// Do the loop
		echo "<ul class='cfi-loop'>";
		while ( $side_query->have_posts() ) : $side_query->the_post();
			echo "<li>";
			echo self::generate($post->ID, array(
				'size' => 'thumbnail',
				'alt' => $post->post_title,
				'align' => '',
				'link' => get_permalink($post->ID)
			));
			echo "</li>\n";
		endwhile;
		echo "</ul>\n";

		return ob_get_clean();
	}

	protected static function add_link($image)
	{
		$link = self::$data['link'];

		if ( empty($link) )
			if ( self::$options->get('default_link') )
				$link = get_permalink(self::$id);
			else
				return $image . "\n";

		return sprintf( "<a href='$link' %s>$image</a>\n", stripslashes(self::$options->get('extra_attr')) );
	}

	static function load($post_id = '')
	{
		global $post;

		$post_id = intval($post_id);

		self::$id = $post_id ? $post_id : $post->ID;

		self::$data = get_post_meta(self::$id, self::key, TRUE);
	}
}


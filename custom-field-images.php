<?php
/*
Plugin Name: Custom Field Images
Description: Easily associate any image to a post and display it in post excerpts, feeds etc.
Version: 2.0a
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

abstract class displayCFI
{
	// Styles for images in feeds
	static $styles = array(
		'left' => 'float:left; margin: 0 1em .5em 0;',
		'center' => 'display:block; margin:0 auto .5em auto;',
		'right' => 'float:right; margin: 0 0 .5em 1em;'
	);

	// Data fields for current image
	static $data = array(
		'url' => '',
		'align' => '',
		'alt' => '',
		'link' => ''
	);

	// wp_postmeta.meta_key
	static $key = '_cfi_image';

	static $token = '[cfi]';

	// $post->ID
	static $id;

	// Options object holder
	static $options;

	static function init($options)
	{
		self::$options = $options;

		add_filter('the_excerpt', array(__CLASS__, 'filter'));
		add_filter('the_content', array(__CLASS__, 'filter'));
	}

	static function filter($content)
	{
		$type = substr(current_filter(), 4);
		$is_feed = is_feed();

		if ( 
			($is_feed && self::$options->get('feed')) 
		|| (!$is_feed && self::$options->get($type))
		)
			if ( $type != 'excerpt' && FALSE !== strpos($content, '[cfi]') )
				return str_replace(self::$token, self::generate(), $content);
			else
				return self::generate() . $content;

		return $content;
	}

	static function generate($post_id = '', $data = '')
	{
		self::load($post_id);

		if ( is_array($data) )
			self::$data = @array_merge(self::$data, $data);

		$url = self::$data['url'];

		if ( !$url )
			return;

		// Begin img tag
		$image .= '<img src="'. $url .'" ';

		// Set alignment
		$align = self::$data['align'] ? self::$data['align'] : self::$options->get('default_align');

		if ( is_feed() )
			$image .= sprintf( 'style="%s" ', self::$styles[$align] );
		else
			$image .= sprintf( 'class="cfi align%s" ', $align );

		// Set alt text
		$alt = self::$data['alt'] ? self::$data['alt'] : get_the_title();

		$image .= sprintf( 'alt="%s" ', $alt );

		// Set title
		if ( self::$options->get('add_title') )
			$image .= sprintf( 'title="%s" ', $alt );

		// End img tag
		$image .= '/>';

		return self::add_link($image);
	}

	static function loop($query)
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
			echo self::generate($post->ID, array(
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

		self::$data = get_post_meta(self::$id, self::$key, TRUE);
	}
}


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
			'default_align' => 'right',
			'add_title' => TRUE,
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


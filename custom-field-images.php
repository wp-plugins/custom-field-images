<?php
/*
Plugin Name: Custom Field Images
Description: Easily associate any image to a post and display it in post excerpts, feeds etc.
Version: 2.1
Author: scribu
Author URI: http://scribu.net/
Plugin URI: http://scribu.net/wordpress/custom-field-images
Text Domain: custom-field-images
Domain Path: /lang

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
	require_once dirname(__FILE__) . '/scb/load.php';

	$options = new scbOptions('cfi_options', __FILE__, array(
		'default_url' => '',
		'default_align' => '',
		'default_link' => true,
		'first_attachment' => false,
		'extra_attr' => '',

		'content' => true,
		'feed' => true,
		'excerpt' => true
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
	static $data_keys = array(
		'id',
		'size',
		'url',
		'align',
		'alt',
		'link'
	);
	static $data = array();

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
		echo "<ul class='cfi-loop'>\n";
		while ( $side_query->have_posts() ) : $side_query->the_post();
			echo "<li>";
			echo self::generate('', array(
				'size' => 'thumbnail',
				'alt' => $post->post_title,
				'align' => '',
				'link' => get_permalink(get_the_ID())
			));
			echo "</li>\n";
		endwhile;
		echo "</ul>\n";

		return ob_get_clean();
	}

	static function generate($post_id = '', $defaults = '')
	{
		self::load($post_id, $defaults);

		@extract(self::$data);

		if ( empty($url) )
			return;

		// Begin img tag
		$image .= '<img src="'. $url .'" ';

		if ( is_feed() )
			$image .= sprintf( 'style="%s" ', self::$styles[$align] );
		else
			$image .= sprintf( 'class="cfi align%s" ', $align );

		$image .= sprintf( 'alt="%s" title="%s"', $alt, $alt );

		// End img tag
		$image .= '/>';

		if ( ! $link = self::$data['link'] )
			return $image . "\n";

		return @sprintf( "<a href='$link' %s>$image</a>\n", stripslashes(self::$options->extra_attr) );
	}

	static function load($post_id = '', $defaults = '', $raw = false)
	{
		if ( ! $post_id = intval($post_id) )
			$post_id = get_the_ID();

		self::$data = get_post_meta($post_id, self::key, TRUE);

		if ( ! empty($defaults) )
			self::$data = wp_parse_args($defaults, self::$data);

		if ( $raw )
			return self::$data;

		// id
		if ( ! self::$data['id'] && ! self::$data['url'] && self::$options->first_attachment )
			self::$data['id'] = self::get_first_attachment_id($post_id);

		// url
		if ( ! $url = self::get_url_by_id(self::$data['id'], self::$data['size']) )
			if ( ! $url = self::$data['url'] )
				if ( ! $url = self::$options->default_url )
					return;

		// align
		if ( ! $align = self::$data['align'] )
			$align = self::$options->default_align;

		// alt
		if ( ! $alt = self::$data['alt'] )
			$alt = get_the_title();

		// link
		if ( ! $link = self::$data['link'] )
			if ( self::$options->default_link )
				$link = get_permalink($post_id);

		foreach ( self::$data_keys as $key )
			if ( isset($$key) )
				self::$data[$key] = $$key;
	}

	static function get_first_attachment_id($post_id)
	{
		$atachment = get_children(array(
			'post_parent' => $post_id,
			'post_type' => 'attachment',
			'numberposts' => 1
		));

		return @key($atachment);
	}

	static function get_url_by_id($id, $size)
	{
		if ( ! $id )
			return false;

		if ( $size )
			$data = image_downsize($id, $size);
		else
			$data = image_downsize($id);

		return @$data[0];
	}
}


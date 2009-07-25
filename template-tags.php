<?php

/*
Display an image. Use within The Loop or set the $post_id parameter.
$defaults - one or more options which you wish to override
*/
function custom_field_image($post_id = '', $defaults = '')
{
	echo get_custom_field_image($post_id, $defaults);
}

/*
Get a custom field image in various formats:
'html' - returns the generated image html
'array' - returns an array with the following fields:
	'id'
	'size'
	'url'
	'align'
	'alt'
	'link'
'object' - returns an object with the same properties
*/
function get_custom_field_image($post_id = '', $defaults = '', $format = 'html')
{
	if ( 'html' == $format )
		return displayCFI::generate($post_id, $defaults);

	displayCFI::load($post_id);

	$data = displayCFI::$data;

	if ( 'object' == $format )
		return (object) $data;

	return $data;
}

/*
Creates a loop with custom field images
*/
function cfi_loop($query)
{
	echo displayCFI::loop($query);
}


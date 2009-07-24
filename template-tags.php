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
'html' - the default way, just returns the formatted image
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

	displayCFI::load_with_defaults($post_id);

	$data = displayCFI::$data;

#	print_r($data);

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


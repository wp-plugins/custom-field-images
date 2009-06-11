<?php

/*
Display an image. Use within The Loop or set the $post_id parameter.
*/
function custom_field_image($post_id = '')
{
	echo get_custom_field_image($post_id);
}

/*
Get a custom field image in various formats:
'html' - the default way, just returns the formatted image
'array' - returns an array with the following fields:
	'url'
	'align'
	'alt'
	'link'
'object' - returns an object with the same properties
*/
function get_custom_field_image($post_id = '', $format = 'html')
{
	global $CFI_display;

	if ( 'html' == $format )
		return $CFI_display->generate($post_id);

	$CFI_display->load($post_id);

	$data = $CFI_display->data;

	if ( 'object' == $format )
		return (object) $data;

	return $data;
}

/*
Creates a loop with custom field images
*/
function cfi_loop($query)
{
	global $CFI_display;

	echo $CFI_display->loop($query);
}


<?php

function custom_field_image($post_id = '')
{
	echo get_custom_field_image($post_id);
}

function get_custom_field_image($post_id = '', $format = 'html')
{
	global $CFI_display;

	if ( 'html' == $format )
		return $CFI_display->generate($post_id);
	else
	{
		$CFI_display->load($post_id);

		$data = $CFI_display->data;

		if ( 'object' == $format )
			return (object) $data;

		return $data;
	}
}

function cfi_loop($query)
{
	global $CFI_display;

	echo $CFI_display->loop($query);
}


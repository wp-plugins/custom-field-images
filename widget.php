<?php

// Simple: Select categories
// Advanced: Insert query


class widgetCFI extends scbWidget
{
	function widgetCFI()
	{
		$widget_ops = array(
			'description' => __('Display posts as a thumb list', 'custom-field-images')
		);

		$this->WP_Widget('cfi-loop', __('CFI Loop', 'custom-field-images'), $widget_ops);
	}

	function content($instance)
	{
		global $CFI_display;

		echo $CFI_display->loop($instance['query']);
	}

	function update($new_instance, $old_instance)
	{
		if ( ! isset($new_instance['title']) ) // user clicked cancel
				return false;

		$instance = $old_instance;
		$instance['title'] = esc_html($new_instance['title']);
		$instance['query'] = esc_html($new_instance['query']);

		return $instance;
	}

	function form($instance) 
	{
		$rows = array(
			array(
				'title' => __('Title', 'custom-field-images') . ':',
				'type' => 'text',
				'name' => 'title',
			),

			array(
				'title' => '<a target="_blank" href="http://codex.wordpress.org/Template_Tags/query_posts#Parameters">' 
							. __('Query string', 'custom-field-images') . '</a>:',
				'type' => 'text',
				'name' => 'query',
				'desc' => __('Example: <em>category_name=Events</em>', 'custom-field-images'),
			)
		);

		foreach ( $rows as $row )
			echo $this->input($row, $instance);
	}
}


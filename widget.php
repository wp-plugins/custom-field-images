<?php

// Simple: Select categories
// Advanced: Insert query


class widgetCFI extends scbWidget
{
	function widgetCFI()
	{
		$widget_ops = array(
			'title' => 'Recent Posts',
			'query' => '',
			'description' => 'Test'
		);

		$this->WP_Widget('cfi-loop', __('CFI Loop', CFI_TEXTDOMAIN), $widget_ops);
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
		$instance['title'] = wp_specialchars($new_instance['title']);
		$instance['query'] = wp_specialchars($new_instance['query']);

		return $instance;
	}

	function form($instance) 
	{
		$rows = array(
			array(
				'title' => __('Title:', CFI_TEXTDOMAIN),
				'type' => 'text',
				'name' => 'title',
			),

			array(
				'title' => '<a target="_blank" href="http://codex.wordpress.org/Template_Tags/query_posts#Parameters">' . __('Query string', CFI_TEXTDOMAIN) . '</a>',
				'type' => 'text',
				'name' => 'query',
				'desc' => __('Example: <em>category_name=Events</em>', CFI_TEXTDOMAIN),
			)
		);

		foreach ( $rows as $row )
			echo $this->input($row, $instance);
	}
}

add_action('widgets_init', create_function('', "register_widget(widgetCFI);"));


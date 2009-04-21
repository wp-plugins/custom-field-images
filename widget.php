<?php

// Simple: Select categories
// Advanced: Insert query

class widgetCFI extends scbWidget {
	function __construct() {
		$widget_ops = array(
			'title' => 'Recent Posts',
			'query' => ''
		);
		$this->WP_Widget('cfi-loop', 'CFI Loop', $widget_ops);
	}

	function widget($instance) {
		global $CFI_display;

		echo $CFI_display->loop($instance['query']);
	}

	function update($new_instance, $old_instance) {
		if ( ! isset($new_instance['title']) ) // user clicked cancel
				return false;

		$instance = $old_instance;
		$instance['title'] = wp_specialchars($new_instance['title']);
		$instance['query'] = wp_specialchars($new_instance['query']);

		return $instance;
	}

	function form($instance) {
		$rows = array(
			array(
				'title' => 'Title:',
				'type' => 'text',
				'names' => 'title',
			),
			array(
				'title' => 'Query string (See <a target="_blank" href="http://codex.wordpress.org/Template_Tags/query_posts#Parameters">available parameters</a>)',
				'type' => 'text',
				'names' => 'query',
				'desc' => 'Example: <em>category_name=Events</em>'
			)
		);

		foreach ( $rows as $row )
			echo $this->input($row, $instance);
	}
}

add_action('widgets_init', create_function('', "register_widget('widgetCFI');"));


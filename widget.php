<?php

// Simple: Select categories
// Advanced: Insert query

if ( !class_exists('scbWidget_06') )
	require_once(dirname(__FILE__) . '/inc/scbWidget.php');

class widgetCFI extends scbWidget_06 {

	protected function setup() {
		$this->name = 'CFI Loop';

		$this->defaults = array(
			'title' => 'Recent Posts',
			'query' => ''
		);
	}

	protected function content($instance) {
		global $CFI_display;

		echo $CFI_display->loop($instance['query']);
	}

	protected function control_update($new_instance, $old_instance) {
		if ( !isset($new_instance['title']) ) // user clicked cancel
				return false;

		$instance = $old_instance;
		$instance['title'] = wp_specialchars( $new_instance['title'] );
		$instance['query'] = wp_specialchars( $new_instance['query'] );

		return $instance;
	}

	protected function control_form($instance) {
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

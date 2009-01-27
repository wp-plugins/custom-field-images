<?php

// Simple: Select categories
// Advanced: Insert query

if ( !class_exists('scbWidget_05') )
	require_once('inc/scbWidget.php');

class widgetCFI extends scbWidget_05 {

	protected function setup() {
		$this->name = 'CFI Loop';
		$this->slug = 'cfi_widget';

		$this->defaults = array(
			'title' => 'Recent Posts',
			'query' => ''
		);
	}

	protected function content() {
		extract($this->options->get());
		ob_start();

		echo "<ul id='sidebar_posts'>";
		$side_query = new WP_query($query);
		while ( $side_query->have_posts() ) : $side_query->the_post();
			echo "<li>";
			custom_field_image(); 
			echo "</li>\n";
		endwhile;
		echo "</ul>\n";

		return ob_get_clean();
	}

	protected function control() {
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

		$options = $this->options->get();

		foreach ( $rows as $row )
			echo $this->input($row, $options);
	}
}

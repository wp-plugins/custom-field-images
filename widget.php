<?php

// Simple: Select categories
// Advanced: Insert query

if ( !class_exists('scbWidget_05') )
	require_once(dirname(__FILE__) . '/inc/scbWidget.php');

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
		global $CFI_display;

		extract($this->options->get());
		ob_start();

		echo "<ul id='cfi-loop'>";

		$query= wp_parse_args($query, array(
			'meta_key' => $CFI_display->key,
			'post_type' => 'post',
			'post_status' => 'publish'
		));

		$side_query = new WP_query($query);
		while ( $side_query->have_posts() ) : $side_query->the_post();
			echo "<li>";
			echo $CFI_display->add_link($CFI_display->generate($post->ID), get_permalink($post->ID));
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

<?php

// Version 1.0

class scribuOptions {
	var $key = '';
	var $data = NULL;

	// PHP4 compatibility
	function scribuOptions($key, $data = '') {
		$this->__construct($key, $data = NULL);
	}

	function __construct($key, $data = NULL) {
		$this->key = $key;

		if ( $data )
			$this->data = $data;
		else
			$this->data = get_option($this->key);
	}

	function delete() {
		delete_option($this->key);
	}

	function get($field = '') {
		if ( empty($field) === true )
			return $this->data;

		return @$this->data[$field];
	}

	function update($data, $override = true) {
		if ( is_array($this->data) && is_array($data) && !$override)
			$newdata = array_merge($this->data, $data);
		else
			$newdata = $data;

		if ( $this->data !== $newdata ) {
			$this->data = $newdata;

			   add_option($this->key, $this->data) or
			update_option($this->key, $this->data);
		}
	}
}


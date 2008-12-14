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

	function form_row($title, $desc, $type, $names, $values, $label = true) {

		$f1 = is_array($names);
		$f2 = is_array($values);

		if ( $f1 || $f2 ) {
			if ( $f1 && $f2 )
				$a = array_combine($names, $values);
			elseif ( $f1 && !$f2 )
				$a = array_fill_keys($names, $values);
			elseif ( !$f1 && $f2)
				$a = array_fill_keys($values, $names);

			if ( $f1 ) {
				$i1 = 'name';
				$i2 = 'val';
			}

			if ( $f2 ) {	
				$i1 = 'val';
				$i2 = 'name';
			}
	
			$l1 = 'name';

		} else {
			$a = array($names => $values);

			$i1 = 'name';
			$i2 = 'val';

			$l1 = 'desc';
		}

		foreach ( $a as $name => $val ) {
			if ( in_array($type, array('checkbox', 'radio')) )
				$extra = ($this->get($$i1) == $$i2) ? "checked='checked' " : '';

			$inputs[] = sprintf('<input name="%1$s" id="%1$s" value="%2$s" type="%3$s" %4$s/> ', $$i1, $$i2, $type, $extra );
			if ( $label )
				$inputs[] = sprintf("<label for='%1\$s'>%2\$s</label> ", $$i1, $$l1);
		}

		return "\n<tr>\n\t<th scope='row' valign='top'>$title</th>\n\t<td>\n\t\t". implode($inputs, "\n") ."</td>\n\n</tr>";
	}
}

// < PHP 5.2
if ( !function_exists('array_fill_keys') ) :
function array_fill_keys($keys, $value) {
	$r = array();

	foreach($keys as $key)
		$r[$key] = $value;

	return $r;
}
endif;

// < PHP 5.0
if ( !function_exists('array_combine') ) :
function array_combine($keys, $values) {
	if ( (count($keys) != count($values)) || empty($keys) || empty($values) )
		return false;

	$r = array();	

	for ( $i = 0; $i<count($keys); $i++ )
		$r[$keys[$i]] = $values[$i];

	return $r;
}
endif;


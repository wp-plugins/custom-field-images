<?php

class adminCFI {

	// PHP4 compatibility
	function adminCFI($file) {
		$this->__construct($file);
	}

	function __construct($file) {
		global $CFIoptions;

		$this->options = $CFIoptions;

		new boxCFI();
		new manageCFI();

		if ( $this->options->get('insert_button') )
			new insertCFI();

		register_activation_hook($file, array(&$this, 'install'));
	}

	function install() {
		$this->options->update(array(
			'default_align' => 'right',
			'add_title' => TRUE,
			'default_link' => TRUE,
			'extra_attr' => '',
			'insert_button' => TRUE,

			'content' => TRUE,
			'feed' => TRUE,
			'excerpt' => TRUE
		), false);
	}
}

class boxCFI extends displayCFI {

	// PHP4 compatibility
	function boxCFI() {
		$this->__construct();
	}

	function __construct() {
		add_action('admin_menu', array(&$this, 'box_init'));
		add_action('save_post', array(&$this, 'save'), 1, 2);
	}

	function box_init() {
		add_meta_box('cfi-box', 'Custom Field Image', array(&$this, 'box'), 'post', 'normal');
		add_meta_box('cfi-box', 'Custom Field Image', array(&$this, 'box'), 'page', 'normal');
	}

	function box() {
		$this->load();
?>
		<table style="width: 100%;">
		    <tr>
		        <td style="width: 10%; text-align: right"><strong>Image URL</strong></td>
		        <td><input tabindex="3" name="cfi-url" id="cfi-url" type="text" style="width: 100%" value="<?php echo $this->data['url']; ?>" /></td>
		    </tr>
		    <tr>
		        <td style="width: 10%; text-align: right">Alt. Text</td>
		        <td><input tabindex="3" name="cfi-alt" id="cfi-alt" type="text" style="width: 100%" value="<?php echo $this->data['alt']; ?>" /></td>
		    </tr>
		    <tr>
		        <td style="width: 10%; text-align: right">Link to</td>
		        <td><input tabindex="3" name="cfi-link" id="cfi-link" type="text" style="width: 100%" value="<?php echo $this->data['link']; ?>" /></td>
		    </tr>
		    <tr>
		        <td style="width: 10%; text-align: right">Align</td>
		        <td id="cfi-align"><?php
		        	foreach ( $this->styles as $align => $style ) {
						echo '<input tabindex="3" name="cfi-align" type="radio" value="' . $align . '" ';
						if ( $this->data['align'] == $align )
							echo 'checked="checked" ';
						echo '/>'. $align ."\n";
					}
				?></td>
		    </tr>
		</table>
<?php
	}

	function save($post_id, $post) {
		if ( $post->post_type == 'revision' )
			return;

		if ( $_POST['cfi-url'] == '' ) {
			delete_post_meta($post_id, $this->key);
			return;
		}

		foreach ( $this->data as $name => $value )
			$this->data[$name] = $_POST['cfi-'.$name];

		   add_post_meta($post_id, $this->key, $this->data, TRUE) or
		update_post_meta($post_id, $this->key, $this->data);
	}
}


class insertCFI {

	// PHP4 compatibility
	function insertCFI() {
		$this->__construct();
	}

	function __construct() {
		add_action('admin_head', array(&$this, 'insert'));
	}

	function insert() {
		$urls = array('post-new.php', 'page-new.php', 'post.php', 'page.php');

		$f = false;
		foreach ( $urls as $url )
			if ( FALSE !== strpos($_SERVER['SCRIPT_NAME'], '/wp-admin/'.$url) ) {
				$f = true;
				break;
			}
		if ( !$f ) return;

		$src = $this->get_plugin_url() . '/insert.js';
		echo "<script type='text/javascript' src='{$src}'></script>\n";
	}

	function get_plugin_url() {
		if ( function_exists('plugins_url') )
			return plugins_url(plugin_basename(dirname(__FILE__)));
		else
			// Pre-2.6 compatibility
			return get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__));
	}
}


class manageCFI extends displayCFI {
	var $nonce = 'cfi-admin-key';

	// PHP4 compatibility
	function manageCFI() {
		$this->__construct();
	}

	function __construct() {
		global $CFIoptions;

		$this->options = $CFIoptions;

		add_action('admin_menu', array(&$this, 'page_init'));
	}

	function process_posts($action) {
		$action = strtolower($action);

		switch ($action) {
			case 'import':
			case 'export':
				$r = call_user_func(array(&$this, 'impex'), $action);
				break;
			default:
				$r = call_user_func(array(&$this, $action));
		}

		if ( $r !== NULL)
			printf('<div class="updated fade"><p>%sed <strong>%d</strong> image(s).</p></div>', ucfirst(rtrim($action, 'e')), $r);
		else
			echo '<div class="error"><p>An error has occured.</p></div>';
	}

// Import/Export methods

	function impex($action) {
		$operators = array(
			'import' => '!=',
			'export' => '='
		);

		$posts = $this->get_posts($operators[$action]);

		foreach ( $posts as $post )
			$count += call_user_func(array(&$this, $action . '_single'), $post);

		return (int) $count;
	}

	function import_single($post) {
		if ( 0 == preg_match('#^\s*(<a[^\<]+>)?\s*(<img[^\<]+>)\s*(?:</a>)?#i', $post->content, $matches) )
			return 0;

		$img = $this->get_attributes($matches[2]);

		$element['url'] = $img['src'];
		$element['alt'] = $img['alt'];

		// Set align
		$img_clases = explode(' ', $img['class']);

		// Search for known classes
		foreach ( $img_clases as $class ) {
			if ( !in_array(substr($class, 5), array_keys($this->styles)) )
				continue;

			$align = substr($class, 5);
			break;
		}

		$element['align'] = $align;

		// Set link
		$element['link'] = '';

		if ( $matches[1] ) {
			$link = $this->get_attributes($matches[1]);
			$element['link'] = $link['href'];
		}

		add_post_meta($post->ID, $this->key, $element, TRUE);

		// Delete image from post
		$new_content = str_replace($matches[0], '', $post->content);

		$this->update_post($new_content, $post->ID);

		return 1;
	}

	function get_attributes($string) {
		preg_match_all('#(\w+)="\s*((?:[^"]+\s*)+)\s*"#i', $string, $matches, PREG_SET_ORDER);

		foreach( $matches as $att )
			$attributes[$att[1]] = $att[2];

		return $attributes;
	}

	function export_single($post) {
		$new_content = $this->generate($post->ID) . $post->content;

		if ( $new_content == $post->content )
			return 0;

		$this->update_post($new_content, $post->ID);

		delete_post_meta($post->ID, $this->key);

		return 1;
	}

	function get_posts($operator) {
		global $wpdb;

		$query = $wpdb->prepare("
			SELECT DISTINCT ID, post_content AS content
			FROM $wpdb->posts NATURAL JOIN $wpdb->postmeta
			WHERE post_type IN ('post', 'page')
			AND meta_key $operator '%s'
		", $this->key);

		return $wpdb->get_results($query);
	}

	function update_post($content, $id) {
		global $wpdb;

		$query = $wpdb->prepare("
			UPDATE $wpdb->posts
			SET post_content = %s
			WHERE ID = %d
		", $content, $id);

		return $wpdb->query($query);
	}

// Delete methods

	function delete() {
		global $wpdb;

		$query = $wpdb->prepare("
			DELETE FROM $wpdb->postmeta
			WHERE meta_key = '%s'
		", $this->key);

		return $wpdb->query($query);
	}

// Options and management page methods

	function page_init() {
		if ( current_user_can('manage_options') ) {
			add_options_page('CFI Settings', 'CFI Settings', 8, 'custom-field-images', array(&$this, 'options_page'));
			add_management_page('CFI Management', 'CFI Management', 8, 'custom-field-images', array(&$this, 'management_page'));
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
				$extra = ($this->options->get($$i1) == $$i2) ? "checked='checked' " : '';

			$inputs[] = sprintf('<input name="%1$s" id="%1$s" value="%2$s" type="%3$s" %4$s/> ', $$i1, $$i2, $type, $extra );
			if ( $label )
				$inputs[] = sprintf("<label for='%1\$s'>%2\$s</label> ", $$i1, $$l1);
		}

		return "\n<tr>\n\t<th scope='row' valign='top'>$title</th>\n\t<td>\n\t\t". implode($inputs, "\n") ."</td>\n\n</tr>";
	}

	function handle_options() {
		if ( 'Save Changes' == $_POST['action'] ) {
			check_admin_referer($this->nonce);

			foreach ( $this->options->get() as $name => $value )
				$new_options[$name] = $_POST[$name];

			$this->options->update($new_options);

			echo '<div class="updated fade"><p>Options <strong>saved</strong>.</p></div>';
		}
	}

	function options_page() {
		$this->handle_options();
		extract($this->options->get());
?>
<div class="wrap">

<h2>Custom Field Images Options</h2>

<form method="post" action="">
	<table class="form-table">
<?php
		echo $this->form_row(
			'Display in',
			'',
			'checkbox',
			array('content', 'excerpt',	'feed'),
			'true'
		);

		echo $this->form_row(
			'Default alignment',
			'',
			'radio',
			'default_align',
			array('left', 'center', 'right')
		);

		echo $this->form_row(
			'Extra link attributes',
			'Example: <em>target="_blank" rel="nofollow"</em>',
			'text',
			'extra_attr',
			htmlentities(stripslashes($extra_attr))
		);

		echo $this->form_row(
			'Link image to post',
			'If the <em>Link to</em> field is blank, the image will have a link to the post or page it is associated with.',
			'checkbox',
			'default_link',
			'true'
		);

		echo $this->form_row(
			'Duplicate Alt. Text as Title',
			'If the <em>Alt. Text</em> field is not empty, it will also be added as the image title.',
			'checkbox',
			'add_title',
			'true'
		);
		
		echo $this->form_row(
			'Insert CFI button',
			'Add button in the Insert Image form',
			'checkbox',
			'insert_button',
			'true'
		);
?>
	</table>

	<?php wp_nonce_field($this->nonce); ?>

	<p class="submit">
		<input name="action" type="submit" class="button-primary" value="Save Changes" />
	</p>
</form>
</div>
<?php
	}

	function management_page() {
		if ( isset($_POST['action']) ) {
			check_admin_referer($this->nonce);
			$this->process_posts($_POST['action']);
		}
?>
<div class="wrap">

<p>Here you can manage all custom field images at once. Please make a <strong>backup</strong> of your database before you proceed.</p>

<h2>Import images</h2>

<p>This will scan for images at beginning of posts, insert them into custom field keys and then remove them from the posts.</p>

<form method="post" action="">
	<?php wp_nonce_field($this->nonce); ?>

	<p class="submit">
		<input name="action" type="submit" onClick="return confirm('Are you sure you want to do this?\nIt cannot be undone.')" value="Import" />
	</p>
</form>
<br />

<h2>Export images</h2>

<p>This will insert all custom field images at the beginning of their respective posts and then delete the custom field keys.</p>

<form method="post" action="">
	<?php wp_nonce_field($this->nonce); ?>

	<p class="submit">
		<input name="action" type="submit" onClick="return confirm('Are you sure you want to do this?\nIt cannot be undone.')" value="Export" />
	</p>
</form>
<br />

<h2>Delete images</h2>

<p>This will delete all custom field images.</p>

<form method="post" action="">
	<?php wp_nonce_field($this->nonce); ?>

	<p class="submit">
		<input name="action" type="submit" onClick="return confirm('Are you sure you want to do this?\nIt cannot be undone.')" value="Delete" />
	</p>
</form>

</div>
<?php
	}
}


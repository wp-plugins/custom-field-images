<?php
class cfImgAdmin extends cfImg {
	var $version = '1.5';
	var $vercomp;
	var $nonce = 'cfi-admin-key';

	function __construct() {
		$this->vercomp = version_compare(get_option('cfi_version'), $this->version);

		if ( $this->vercomp < 0 )
			add_action('admin_notices', array(&$this, 'warning'));

		add_action('admin_menu', array(&$this, 'page_init'));
		add_action('admin_menu', array(&$this, 'box_init'));
		add_action('save_post', array(&$this, 'save'), 1, 2);
	}

	function warning() {
		$manage_url = 'edit.php?page=custom-field-images';

		if ( strstr($_SERVER['REQUEST_URI'], $manage_url) )
			return;

		echo '<div class="updated fade"><p><strong>Custom Field Images</strong>: Data upgrade required. Please visit the <a href="' . $manage_url . '">management page</a>.</p></div>';
	}

// Upgrade options on activation

	function activate() {
		if ( $this->vercomp >= 0 )
			return;

		$ver = 	get_option('cfi_version');

		switch ($ver) {
			case '1.4':
			case '1.3':
				$old_options = get_option('cfi_options');
				break;
			case '':
				$old_options = get_option('cfi-show-in');
				delete_option('cfi-show-in');
		}

		foreach ( $old_options as $name => $value )
			$this->options[$name] = $value;

		   add_option('cfi_options', $this->options) or
		update_option('cfi_options', $this->options);
	}

// Management page methods

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
			echo '<div class="updated fade"><p>' . ucfirst(rtrim($action, 'e')) . 'ed <strong>' . $r . '</strong> image(s).</p></div>';
		else
			echo '<div class="error"><p>An error has occured.</p></div>';
	}

// Upgrade methods

	function upgrade() {
		global $wpdb;

		$ver = get_option('cfi_version');

		if ( !$ver )
			return $this->upgrade_1_2();

		update_option('cfi_version', $this->version);

		// Get old data
		$query = $wpdb->prepare("
			SELECT post_id, meta_value
			FROM $wpdb->postmeta
			WHERE meta_key = '%s'
		", $this->key);

		$old_data = $wpdb->get_results($query, 'ARRAY_A');

		if ( !$old_data )
			return 0;

		// Convert old data
		foreach ($old_data as $row) {
			$id = $row['post_id'];

			$old_element = unserialize(unserialize($row['meta_value']));

			foreach($this->data as $field => $value)
				$new_element[$field] = $old_element["cfi-$field"];

			$count += update_post_meta($id, $this->key, $new_element);
		}

		return (int) $count;
	}

	function upgrade_1_2() {
		global $wpdb;

		add_option('cfi_version', $this->version, '', 'no');

		// Set data fields
		foreach ( $this->data as $name => $value )
			$fields[] = "'cfi-$name'";

		$fields = implode(',', $fields);

		// Get old data
		$query = $wpdb->prepare("
			SELECT post_id, meta_key, meta_value
			FROM $wpdb->postmeta
			WHERE meta_key IN(%s)
		", $fields);

		$old_data = $wpdb->get_results($query, 'ARRAY_A');

		if ( !$old_data )
			return 0;

		// Convert old data
		foreach ($old_data as $row) {
			$id = $row['post_id'];
			$new_field = substr($row['meta_key'], 4);

			$new_data[$id][$new_field] = $row['meta_value'];
		}

		// Add new data (don't overwrite newer data)
		foreach ($new_data as $id => $element)
			$count += add_post_meta($id, $this->key, $element, TRUE);

		// Delete old data
		$query = $wpdb->prepare("
			DELETE FROM $wpdb->postmeta
			WHERE meta_key IN(%s)
		", $fields);

		$wpdb->query($query);

		return (int) $count;
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

		if ( $new_content == $post->content )
			return 0;

		$this->update_post($new_content, $post->ID);

		return 1;
	}

	function export_single($post) {
		$new_content = $this->generate($post->ID) . $post->content;

		if ( $new_content == $post->content )
			return 0;

		$this->update_post($new_content, $post->ID);

		delete_post_meta($post->ID, $this->key);

		return 1;
	}

	function get_attributes($string) {
		preg_match_all('#(\w+)="\s*((?:[^"]+\s*)+)\s*"#i', $string, $matches, PREG_SET_ORDER);

		foreach( $matches as $att )
			$attributes[$att[1]] = $att[2];

		return $attributes;
	}

	function get_posts($operator) {
		global $wpdb;

		$query = $wpdb->prepare("
			SELECT DISTINCT ID, post_content AS content
			FROM $wpdb->posts NATURAL JOIN $wpdb->postmeta
			WHERE post_status IN('publish', 'draft')
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

// Box methods

	function box_init() {
		add_meta_box('cfi-box', 'Custom Field Image', array(&$this, 'box'), 'post', 'normal');
		add_meta_box('cfi-box', 'Custom Field Image', array(&$this, 'box'), 'page', 'normal');
	}

	function box() {
		$this->load();

		?>

		<div style="text-align:right">
		<p><strong>Image URL</strong>
			<input name="url" id="url" type="text" style="width: 46em" value="<?php echo $this->data['url']; ?>" />
		</p>
		<p>Alt. Text
			<input name="alt" id="alt" type="text" style="width: 46em" value="<?php echo $this->data['alt']; ?>" />
		</p>
		<p>Link to
			<input name="link" id="link" type="text" style="width: 46em" value="<?php echo $this->data['link']; ?>" />
		</p>
		<p style="text-align:left; margin-left:4.5em;">Align
			<?php foreach ( $this->styles as $align => $style ) {
				echo '<input name="align" id="align" type="radio" value="' . $align . '" ';
				if ( $this->data['align'] == $align)
					echo 'checked="checked" ';
				echo '/>'. $align ."\n";
			} ?>
		</p>
		</div>
<?php	}

	function save($post_id, $post) {
		if ( $post->post_type == 'revision' )
			return;

		if ( $_POST['url'] == '' ) {
			delete_post_meta($post_id, $this->key);
			return;
		}

		foreach ( $this->data as $name => $value )
			$this->data[$name] = $_POST[$name];

		   add_post_meta($post_id, $this->key, $this->data, TRUE) or
		update_post_meta($post_id, $this->key, $this->data);
	}

// Options and management page methods

	function page_init() {
		if ( current_user_can('manage_options') )
			add_options_page('Custom Field Images', 'Custom Field Images', 8, 'custom-field-images', array(&$this, 'options_page'));
			add_management_page('Custom Field Images', 'Custom Field Images', 8, 'custom-field-images', array(&$this, 'management_page'));
	}

	function options_page() {
		$this->options = get_option('cfi_options');

		// Update options
		if ( 'Save' == $_POST['action'] ) {
			check_admin_referer($this->nonce);

			foreach ( $this->options as $name => $value )
				$new_options[$name] = $_POST[$name];

			if ( $this->options != $new_options ) {
				$this->options = $new_options;
				update_option('cfi_options', $this->options);
			}

			echo '<div class="updated fade"><p>Options <strong>saved</strong>.</p></div>';
		}
?>
<div class="wrap">

<h2>Custom Field Images Options</h2>

<form method="post" action="">
	<table class="form-table">
		<tr>
			<th scope="row" valign="top">Display in</th>
			<td>
			<?php foreach ( array('content', 'excerpt', 'feed') as $name ) { ?>
				<input type="checkbox" name="<?php echo $name; ?>" value="TRUE" <?php if ( $this->options[$name] == TRUE) echo 'checked="checked"'; ?> />
			 	<label>post <?php echo $name; ?></label>
				<br class="clear" />
			<?php } ?>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top">Default alignment</th>
			<td>
			<?php foreach ( $this->styles as $align => $style ) { ?>
				<input type="radio" name="default_align" value="<?php echo $align; ?>" <?php if ( $this->options['default_align'] == $align) echo 'checked="checked" ';?> />
				<label><?php echo $align; ?></label>
			<?php } ?>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top">Link image to the current post</th>
			<td>
				<input type="checkbox" name="default_link" value="TRUE" <?php if ( $this->options['default_link']) echo 'checked="checked" ';?> />
				<label>If an image has no link specified, it will be linked to the post or page it is associated with.</label>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top">Extra attributes</th>
			<td>
				<input type="text" name="extra_attr" value="<?php echo htmlentities(stripslashes($this->options['extra_attr'])); ?>" style="width: 250px" />
				<label>Example: <em>target="_blank" rel="nofollow"</em></label>
				<p>This is for adding extra attributes to the links added to images.</p>
			</td>
		</tr>
	</table>

	<?php wp_nonce_field($this->nonce); ?>

	<p class="submit">
		<input name="action" type="submit" value="Save" />
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

<?php if ( $this->vercomp < 0 ) { ?>
<h2>Upgrade custom field keys</h2>

<p>This operation is required only once, in order to use older data.</p>

<form method="post" action="">
	<?php wp_nonce_field($this->nonce); ?>

	<p class="submit">
	<input name="action" type="submit" value="Upgrade" />
	</p>
</form>
<br />
<?php } ?>

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


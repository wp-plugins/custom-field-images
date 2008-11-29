<?php
class cfImgAdmin extends cfImg {
	var $nonce = 'cfi-admin-key';

	function __construct() {
		add_action('admin_menu', array(&$this, 'page_init'));
		add_action('admin_menu', array(&$this, 'box_init'));
		add_action('save_post', array(&$this, 'save'), 1, 2);
	}

// Upgrade options

	function activate() {
		if ( $old_options = get_option('cfi_options') )
			$this->options = array_merge($this->options, $old_options);

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
		<table style="width: 100%;">
		    <tr>
		        <td style="width: 10%; text-align: right"><strong>Image URL</strong></td>
		        <td><input tabindex="3" name="url" type="text" style="width: 100%" value="<?php echo $this->data['url']; ?>" /></td>
		    </tr>
		    <tr>
		        <td style="width: 10%; text-align: right">Alt. Text</td>
		        <td><input tabindex="3" name="alt" type="text" style="width: 100%" value="<?php echo $this->data['alt']; ?>" /></td>
		    </tr>
		    <tr>
		        <td style="width: 10%; text-align: right">Link to</td>
		        <td><input tabindex="3" name="link" type="text" style="width: 100%" value="<?php echo $this->data['link']; ?>" /></td>
		    </tr>
		    <tr>
		        <td style="width: 10%; text-align: right">Align</td>
		        <td><?php
		        	foreach ( $this->styles as $align => $style ) {
						echo '<input tabindex="3" name="align" type="radio" value="' . $align . '" ';
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
		if ( current_user_can('manage_options') ) {
			add_options_page('CFI Settings', 'CFI Settings', 8, 'custom-field-images', array(&$this, 'options_page'));
			add_management_page('CFI Management', 'CFI Management', 8, 'custom-field-images', array(&$this, 'management_page'));
		}
	}

	function update_options() {
		$this->options = get_option('cfi_options');

		// Update options
		if ( 'Save Changes' == $_POST['action'] ) {
			check_admin_referer($this->nonce);

			foreach ( $this->options as $name => $value )
				$new_options[$name] = $_POST[$name];

			if ( $this->options != $new_options ) {
				$this->options = $new_options;
				update_option('cfi_options', $this->options);
			}

			echo '<div class="updated fade"><p>Options <strong>saved</strong>.</p></div>';
		}
	}

	function options_page() {
		$this->update_options();
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
			<th scope="row" valign="top">Duplicate Alt. Text as Title</th>
			<td>
				<input type="checkbox" name="add_title" value="TRUE" <?php if ( $this->options['add_title']) echo 'checked="checked" ';?> />
				<label>If the <em>Alt. Text</em> field is not empty, it will also be added as the image title.</label>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top">Link image to post</th>
			<td>
				<input type="checkbox" name="default_link" value="TRUE" <?php if ( $this->options['default_link']) echo 'checked="checked" ';?> />
				<label>If the <em>Link to</em> field is blank, the image will have a link to the post or page it is associated with.</label>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top">Extra link attributes</th>
			<td>
				<input type="text" name="extra_attr" value="<?php echo htmlentities(stripslashes($this->options['extra_attr'])); ?>" style="width: 250px" />
				<label>Example: <em>target="_blank" rel="nofollow"</em></label>
				<p>This is for adding extra attributes to the links added to images.</p>
			</td>
		</tr>
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


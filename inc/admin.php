<?php
class cfImgAdmin extends cfImg {
	function __construct() {
		if ( !get_option('cfi_version') )
			add_action('admin_notices', array(&$this, 'warning'));

		add_action('admin_menu', array(&$this, 'page_init'));
		add_action('admin_menu', array(&$this, 'box_init'));
		add_action('save_post', array(&$this, 'save'), 1, 2);
	}

	var $nonce = 'cfi-admin-key';

	function warning() {
		echo '<div class="updated fade"><p><strong>Custom Field Images</strong>: Please visit the <a href="options-general.php?page=custom-field-images">Settings page</a>.</p></div>';
	}

	function activate() {
		$ver = 	get_option('cfi_version');

		if ($ver === '1.4')
			return;

		if ($ver === '1.3') {
			$show_in = get_option('cfi_options');
			update_option('cfi_version', '1.4');
		} else {
			$show_in = get_option('cfi-show-in');
			delete_option('cfi-show-in');
		}

		foreach ($show_in as $name => $value)
			$this->options[$name] = $value;

		   add_option('cfi_options', $this->options) or
		update_option('cfi_options', $this->options);
	}

	function upgrade() {
		global $wpdb;

		add_option('cfi_version', '1.4', '', 'no');

		// Set data fields
		foreach ($this->data as $name => $value)
			$fields[] = "'$name'";
		$fields = implode(',', $fields);

		// Get old data
		$query = "
			SELECT post_id, meta_key, meta_value
			FROM $wpdb->postmeta
			WHERE meta_key IN($fields)
		";

		$old_data = $wpdb->get_results($query, 'ARRAY_A');

		if (!$old_data)
			return 'none';

		// Convert old data
		foreach ($old_data as $row)
			$new_data[$row['post_id']][$row['meta_key']] = $row['meta_value'];

		// Add new data (don't overwrite newer data)
		foreach ($new_data as $id => $element)
			add_post_meta($id, $this->field, serialize($element), TRUE);

		// Delete old data
		$wpdb->query("
			DELETE FROM $wpdb->postmeta
			WHERE meta_key IN($fields)
		");

		return TRUE;
	}

	function box_init() {
		add_meta_box('cfi-box', 'Custom Field Image', array(&$this, 'box'), 'post', 'normal');
		add_meta_box('cfi-box', 'Custom Field Image', array(&$this, 'box'), 'page', 'normal');
	}

	function box() {
		$this->load();

		?>

		<div style="text-align:right">
		<p><strong>Image URL</strong>
			<input name="cfi-url" id="cfi-url" type="text" style="width: 46em" value="<?php echo $this->data['cfi-url']; ?>" />
		</p>
		<p>Alt. Text
			<input name="cfi-alt" id="cfi-alt" type="text" style="width: 46em" value="<?php echo $this->data['cfi-alt']; ?>" />
		</p>
		<p>Link to
			<input name="cfi-link" id="cfi-link" type="text" style="width: 46em" value="<?php echo $this->data['cfi-link']; ?>" />
		</p>
		<p style="text-align:left; margin-left:4.5em;">Align
			<?php foreach ($this->styles as $align => $style) {
				echo '<input name="cfi-align" id="cfi-align" type="radio" value="' . $align . '" ';
				if ($this->data['cfi-align'] == $align)
					echo 'checked="checked" ';
				echo '/>'. $align ."\n";
			} ?>
		</p>
		</div>
<?php	}

	function save($post_id, $post) {
		if ($post->post_type == 'revision')
			return;

		if ($_POST['cfi-url'] == '') {
			delete_post_meta($post_id, $this->field);
			return;
		}

		foreach ($this->data as $name => $value)
			$this->data[$name] = $_POST[$name];

		   add_post_meta($post_id, $this->field, serialize($this->data), TRUE) or
		update_post_meta($post_id, $this->field, serialize($this->data));
	}

	// Options Page
	function page_init() {
		if ( current_user_can('manage_options') )
			add_options_page('Custom Field Images', 'Custom Field Images', 8, 'custom-field-images', array(&$this, 'page'));
	}

	function page() {
		$this->options = get_option('cfi_options');

		// Update options
		if (isset($_POST['submit']) && 'update' == $_POST['action']) {
			check_admin_referer($this->nonce);
			foreach ( $this->options as $name => $value )
				$new_options[$name] = $_POST[$name];

			if ( $this->options != $new_options ) {
				$this->options = $new_options;
				update_option('cfi_options', $this->options);
			}

			echo '<div class="updated"><p>Options <strong>saved</strong>.</p></div>';
		}

		// Upgrade data
		if (isset($_POST['submit']) && 'upgrade' == $_POST['action']) {
			$result = $this->upgrade();

			if ($result === TRUE)
				echo '<div class="updated"><p>All data <strong>upgraded</strong>.</p></div>';
			elseif ($result === 'none')
				echo '<div class="updated"><p>No data to upgrade.</p></div>';
			else
				echo '<div class="error"><p>An error has occured.</p></div>';
		}

		// Delete data
		if (isset($_POST['submit']) && 'delete' == $_POST['action']) {
			global $wpdb;

			$wpdb->query("
				DELETE FROM $wpdb->postmeta
				WHERE meta_key = '$this->field'
			");

			echo '<div class="updated"><p>All data <strong>deleted</strong>.</p></div>';
		}
?>
<div class="wrap">

<?php if ( !get_option('cfi_version') ) { ?>
<h2>Upgrade custom field keys</h2>

<p>This operation is required only once, in order to use older data. Please make a backup of your database first.</p>

<form name="cfi-upgrade" method="post" action="">
	<?php wp_nonce_field($this->nonce); ?>
	<input name="action" type="hidden" value="upgrade" />

	<p class="submit">
	<input name="submit" type="submit" value="Upgrade" />
	</p>
</form>

<br class="clear" />
<?php } ?>

<h2>Custom Field Images Options</h2>

<form name="cfi-display" method="post" action="">
	<table class="form-table">
		<tr>
			<th scope="row" valign="top">Display in</th>
			<td>
			<?php foreach (array('content', 'excerpt', 'feed') as $name) { ?>
				<input type="checkbox" name="<?php echo $name; ?>" value="TRUE" <?php if ($this->options[$name] == TRUE) echo 'checked="checked"'; ?> />
			 	<label>post <?php echo $name; ?></label>
				<br class="clear" />
			<?php } ?>
			</td>
		 </tr>
		<tr>
			<th scope="row" valign="top">Default alignment</th>
			<td>
			<?php foreach ($this->styles as $align => $style) { ?>
				<input type="radio" name="default_align" value="<?php echo $align; ?>" <?php if ($this->options['default_align'] == $align)	echo 'checked="checked" ';?> />
				<label><?php echo $align; ?></label>
			<?php } ?>
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
	<input name="action" type="hidden" value="update" />

	<p class="submit">
		<input name="submit" type="submit" value="Save Options" />
	</p>
</form>

<br class="clear" />

<h2>Delete all data</h2>

<p>This will delete all custom keys asociated with Custom Field Images.</p>

<form name="cfi-delete" method="post" action="">
	<?php wp_nonce_field($this->nonce); ?>
	<input name="action" type="hidden" value="delete" />

	<p class="submit">
		<input name="submit" type="submit" onClick="return confirm('Are you sure you want to do this?\nIt cannot be undone.')" value="Delete" />
	</p>
</form>

</div>
<?php
	}
}

$cfImgAdmin = new cfImgAdmin();
?>

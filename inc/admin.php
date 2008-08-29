<?php
class cfImgAdmin extends cfImg {
	function __construct() {
		add_option('cfi_options', $this->show_in);

		if ( !get_option('cfi_version') )
			add_action('admin_notices', array(&$this, 'warning'));

		add_action('admin_menu', array(&$this, 'box_init'));
		add_action('save_post', array(&$this, 'save'));

		add_action('admin_menu', array(&$this, 'page_init'));
	}

	function warning() {
		echo '<div class="updated fade"><p><strong>Custom Field Images</strong>: Please visit the <a href="options-general.php?page=custom-field-images">Settings page</a>.</p></div>';
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

	function save($post_id) {
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
		$this->show_in = get_option('cfi_options');

		// Update display options
		if ( $_POST['submit-display'] ) {
			foreach ($this->show_in as $name => $value)
				$this->show_in[$name] = $_POST[$name];

			update_option('cfi_options', $this->show_in);
			echo '<div class="updated"><p>Options <strong>saved</strong>.</p></div>';
		}

		// Upgrade cf keys
		if ( $_POST['submit-upgrade'] ) {
			$result = $this->upgrade();

			if ($result === TRUE)
				echo '<div class="updated"><p>All data <strong>upgraded</strong>.</p></div>';
			elseif ($result === 'none')
				echo '<div class="updated"><p>No data to upgrade.</p></div>';
			else
				echo '<div class="error"><p>An error has occured.</p></div>';
		}

		// Delete cf keys
		if ( $_POST['submit-delete'] ) {
			global $wpdb;

			$wpdb->query("
				DELETE FROM $wpdb->postmeta
				WHERE meta_key = $this->field
			");

			echo '<div class="updated"><p>All data <strong>deleted</strong>.</p></div>';
		}
?>
<div class="wrap">

<?php if ( !get_option('cfi_version') ) { ?>
<h2>Upgrade custom field keys</h2>

<p>This operation is required only once, in order to use older data. Please make a backup of your database first.</p>

<form name="cfi-upgrade" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<p class="submit">
	<input type="submit" name="submit-upgrade" value="Upgrade" />
	</p>
</form>

<br class="clear" />
<?php } ?>

<h2>Custom Field Images Options</h2>

<form name="cfi-display" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<table class="form-table">
		<tr>
			<th scope="row" valign="top">Display in</th>
			<td>
			<?php foreach ($this->show_in as $name => $value) { ?>
				<input type="checkbox" <?php if ($value == TRUE) echo 'checked="checked"'; ?> name="<?php echo $name; ?>" value="TRUE" />
			 	<label>post <?php echo $name; ?></label>
				<br class="clear" />
			<?php } ?>
			</td>
		 </tr>
	</table>

	<p class="submit">
		<input name="submit-display" value="Save Options" type="submit" />
	</p>
</form>

<br class="clear" />

<h2>Delete all data</h2>

<p>This will delete all custom keys asociated with Custom Field Images.</p>

<form name="cfi-delete" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<p class="submit">
		<input name="submit-delete" type="submit" onClick="return confirm('Are you sure you want to do this?\nIt cannot be undone.')" value="Delete" />
	</p>
</form>

</div>
<?php
	}

	function upgrade() {
		global $wpdb;

		delete_option('cfi-show-in');
		add_option('cfi_version', '1.3', '', 'no');

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
}

$cfImgAdmin = new cfImgAdmin();
?>

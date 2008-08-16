<?php
class cfImgAdmin extends cfImg {
	function __construct() {
		add_option('cfi-show-in', $this->show_in);

		add_action('edit_form_advanced', array(&$this, 'postbox'));
		add_action('edit_page_form', array(&$this, 'postbox'));
		add_action('save_post', array(&$this, 'save'));
		add_action('admin_menu', array(&$this, 'page_init'));
	}

	function postbox() {
		$this->load();
		
		?>
	<div id="cfi-div" class="postbox <?= postbox_classes('cfi-div', 'post'); ?>">
		<h3>Custom Field Image</h3>
		<div class="inside" style="text-align:right">
			<p><strong>Image URL</strong>
				<input name="cfi-url" id="cfi-url" type="text" style="width: 46em" value="<?= $this->data['cfi-url']; ?>" />
			</p>
			<p>Alt. Text
				<input name="cfi-alt" id="cfi-alt" type="text" style="width: 46em" value="<?= $this->data['cfi-alt']; ?>" />
			</p>
			<p>Link to
				<input name="cfi-link" id="cfi-link" type="text" style="width: 46em" value="<?= $this->data['cfi-link']; ?>" />
			</p>
			<p style="text-align:left; margin-left:4.8em;">Align
				<?php foreach ($this->styles as $align => $style) {
					echo '<input name="cfi-align" id="cfi-align" type="radio" value="' . $align . '" ';
					if ($this->data['cfi-align'] == $align)
						echo 'checked="checked" ';
					echo '/>'. $align ."\n";
				} ?>
			</p>
		</div>
	</div>
<?php
	}

	function save($post_id) {
		$this->load();

		foreach ($this->data as $name => $value)
			if ( $_POST[$name] == '') {
				// Delete value
				delete_post_meta($post_id, $name);
			}
			elseif ($_POST[$name] != $value ) {
				// Set new value
				$value = $_POST[$name];
				$updated = update_post_meta($post_id, $name, $value);
				if (!$updated)
					add_post_meta($post_id, $name, $value);
			}
	}

	// Options Page
	function page_init() {
		if ( current_user_can('manage_options') ) {
			add_options_page('Custom Field Images', 'Custom Field Images', 8, 'custom-field-images', array(&$this, 'page'));
			add_filter( 'plugin_action_links', array(&$this, 'filter_plugin_actions'), 10, 2 );
		}
	}

	function page() {
		$this->show_in = get_option('cfi-show-in');

		// Update display options
		if ( $_POST['submit-display'] ) {
			foreach ($this->show_in as $name => $value)
				$this->show_in[$name] = $_POST[$name];

			update_option('cfi-show-in', $this->show_in);
			echo '<div class="updated"><p>Display options saved.</p></div>';
		}

		unset($this->show_in);
		$this->show_in = get_option('cfi-show-in');

		// Rename cf keys
		if ( $_POST['submit-key-rename'] ) {
			global $wpdb;

			foreach ($this->data as $field => $value) {
				$key = $_POST[$field];
				if ($key) {
					$query = "UPDATE $wpdb->postmeta SET meta_key = '$field' WHERE meta_key = '$key'";
					$wpdb->query($query);
				}
			}
			echo '<div class="updated"><p>Key renamed.</p></div>';
		}

		// Delete cf keys
		if ( $_POST['submit-delete'] ) {
			global $wpdb;

			$query = "DELETE FROM $wpdb->postmeta WHERE meta_key IN(";
			foreach ($this->data as $name => $value)
				$query.= " '$name',";
			$query = rtrim($query, ',');
			$query.= " )";
			$wpdb->query($query);

			echo '<div class="updated"><p>All data deleted.</p></div>';
		}
?>
<div class="wrap">
<h2>Custom Field Images Options</h2>

<form id="cfi-display" name="cfi-display" method="post" action="<?= str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<table class="form-table">
	 <tr>
	<th scope="row" valign="top">Display in</th>
	<td><?php foreach ($this->show_in as $name => $value) { ?>
		<input type="checkbox" <?php if ($value == TRUE) echo 'checked="checked"'; ?> name="<?= $name; ?>" value="TRUE" />
		 	<label>post <?= $name; ?></label>
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

<h2>Rename custom field keys</h2>
<form name="rename-key" method="post" action="<?= str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<table class="form-table">
	<tr>
	<th scope="row" valign="top">Rename key</th>
	<td>
	<?php foreach ($this->data as $field => $value) { ?>
		<input type="text" name="<?= $field; ?>" size="25" />
		to
		<input type="text" value="<?= $field; ?>" size="25" disabled="disabled" />
		<br />
	<?php } ?>
		If you already use custom field images, you can rename the custom field keys so that they can be used by this plugin.
		<br />Example: <em>Thumb URL</em> to <em>cfi-url</em>.
		<br />Please <strong>backup your database</strong> first!
	</td>
	</tr>
	</table>

	<p class="submit">
	<input name="submit-key-rename" value="Rename" type="submit" />
	</p>
</form>

<br class="clear" />

<h2>Delete all data</h2>
<p>This will delete all custom keys asociated with Custom Field Images.</p>
<form name="cfiDelete" method="post" action="<?= str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<p class="submit">
		<input name="submit-delete" type="submit" onClick="return confirm('Are you sure you want to do this?\nIt cannot be undone.')" value="Delete" />
	</p>
</form>

</div>
<?php
	}

	function filter_plugin_actions($links, $file) {
		static $this_plugin;
		if ( ! $this_plugin )
			$this_plugin = plugin_basename(dirname(__FILE__)) . '/custom-field-images.php';

		if ( $file == $this_plugin ) {
			$settings_link = '<a href="options-general.php?page=custom-field-images"><strong>Settings</strong></a>';
			$links[] = $settings_link;
		}
		return $links;
	}
}

$cfImgAdmin = new cfImgAdmin();
?>

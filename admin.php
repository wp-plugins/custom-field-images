<?php

// Adds the CFI metabox
class boxCFI extends displayCFI 
{
	function __construct()
	{
		add_action('admin_menu', array($this, 'box_init'));
		add_action('save_post', array($this, 'save'), 1, 2);
	}

	function box_init() 
	{
		add_meta_box('cfi-box', 'Custom Field Image', array($this, 'box'), 'post', 'normal');
		add_meta_box('cfi-box', 'Custom Field Image', array($this, 'box'), 'page', 'normal');
	}

	function box() 
	{
?>
<style type="text/css">
		#cfi-box table, #cfi-box .text {width:100%}
		#cfi-box th {width:7%; text-align:right; font-weight: normal}
</style>
<?php $rows = array(
			array(
				'title' => '<strong>' . __('Image URL', 'custom-field-images') . '</strong>',
				'type' => 'text',
				'name' => 'cfi-url',
				'extra' => 'class="text"'
			),

			array(
				'title' => __('Alt. Text', 'custom-field-images'),
				'type' => 'text',
				'name' => 'cfi-alt',
				'extra' => 'class="text"'
			),

			array(
				'title' => __('Link to', 'custom-field-images'),
				'type' => 'text',
				'name' => 'cfi-link',
				'extra' => 'class="text"'
			),

			array(
				'title' => __('Align', 'custom-field-images'),
				'type' => 'radio',
				'name' => 'cfi-align',
				'value' => array('left', 'center', 'right')
			)
		);

		$this->load();

		if ( $this->data ) 
		{
			// Prepend 'cfi-' to data keys
			foreach ( $this->data as $key => $value )
				$options['cfi-'.$key] = $value;
		}

		echo scbForms::table($rows, $options);
	}

	function save($post_id, $post) 
	{
		if ( DOING_AJAX === true || empty($_POST) || $post->post_type == 'revision' )
			return;

		// Delete data on empty url
		if ( empty($_POST['cfi-url']) ) {
			delete_post_meta($post_id, $this->key);
			return;
		}

		foreach ( $this->data as $name => $value )
			$this->data[$name] = $_POST['cfi-'.$name];

		   add_post_meta($post_id, $this->key, $this->data, TRUE) or
		update_post_meta($post_id, $this->key, $this->data);
	}
}

// Loads (Insert CFI) button script
class insertCFI 
{
	function __construct() 
	{
		add_action('admin_print_scripts', array($this, 'insert'));
	}

	function insert() 
	{
		global $pagenow;

		if ( !in_array($pagenow, array('post.php', 'post-new.php', 'page.php', 'page-new.php')) )
			return false;

		$src = $this->get_plugin_url() . '/inc';
		
		wp_register_script('livequery', $src . '/livequery.js');
		wp_enqueue_script('cfi-insert', $src . '/insert.js', array('jquery', 'livequery'));
	}

	private function get_plugin_url() 
	{
		// WP < 2.6
		if ( !function_exists('plugins_url') )
			return get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__));

		return plugins_url(plugin_basename(dirname(__FILE__)));
	}
}

// Adds the CFI Settings page
class settingsCFI extends scbBoxesPage
{
	function setup()
	{
		$this->display = $GLOBALS['CFI_display'];
	
		$this->args = array(
			'page_title' => __('Custom Field Images', 'custom-field-images'),
			'action_link' => __('Settings', 'custom-field-images'),
		);

		$this->boxes = array(
			array('settings', __('Settings', 'custom-field-images'), 'normal'),
			array('manage', __('Management', 'custom-field-images'), 'normal'),
		);
	}

	function page_head()
	{
		wp_enqueue_style('cft_css', $this->plugin_url . '/inc/admin.css', array(), '1.2');
	}

	function settings_box()
	{
		$rows = array(
			array(
				'title' => __('Display in', 'custom-field-images'),
				'type' => 'checkbox',
				'name' => array('content', 'excerpt', 'feed'),
				'desc' => array(
					__('content', 'custom-field-images'),
					__('excerpt', 'custom-field-images'),
					__('feed', 'custom-field-images'),
				)
			),

			array(
				'title' => __('Default alignment', 'custom-field-images'),
				'type' => 'radio',
				'name' => 'default_align',
				'value' => array('left', 'center', 'right'),
				'desc' => array(
					__('left', 'custom-field-images'),
					__('center', 'custom-field-images'),
					__('right', 'custom-field-images'),
				)
			),

			array(
				'title' => __('Extra link attributes', 'custom-field-images'),
				'desc' => __('Example', 'custom-field-images') . ': <em>target="_blank" rel="nofollow"</em>',
				'type' => 'text',
				'name' => 'extra_attr'
			),

			array(
				'title' => __('Link image to post', 'custom-field-images'),
				'desc' => __('If the <em>Link to</em> field is blank, the image will have a link to the post or page it is associated with.', 'custom-field-images'),
				'type' => 'checkbox',
				'name' => 'default_link',
			),

			array(
				'title' => __('Duplicate Alt. Text as Title', 'custom-field-images'),
				'desc' => __('The <em>Alt. Text</em> will also be added as the image title.', 'custom-field-images'),
				'type' => 'checkbox',
				'name' => 'add_title',
			),

			array(
				'title' => __('Insert CFI button', 'custom-field-images'),
				'desc' => __('Add button in the Insert Image form', 'custom-field-images'),
				'type' => 'checkbox',
				'name' => 'insert_button',
			)
		);

		echo $this->form_table($rows, $this->formdata, $this->submit_button('action', __('Save Changes', 'custom-field-images')));
	}

	function settings_handler()
	{
		if ( $_POST['action'] != __('Save Changes', 'custom-field-images') )
			return;

		foreach ( $this->options->get() as $name => $value )
			$new_data[$name] = $_POST[$name];

		$this->formdata = $new_data;
		$this->options->update($this->formdata);

		$this->admin_msg(__('Settings <strong>saved</strong>.', 'custom-field-images'));
	}


// Manage


	function one_button_form($action, $value)
	{
		return $this->form(array(
			array(
				'type' => 'hidden',
				'name' => 'action',
				'value' => $action,
				'desc' => false
			),

			array(
				'type' => 'submit',
				'name' => 'action_button',
				'value' => $value,
				'extra' => 'class="button" onClick="return confirm(\'Are you sure?\')"',
				'desc' => false
			)
		));
	}

	function manage_box()
	{
		echo "<p>" . __("Here you can manage all custom field images at once. Please make a <strong>backup</strong> of your database before you proceed.", 'custom-field-images') . "</p>\n";

		$sections = array(
			array(
				'header' => __("Import images", 'custom-field-images'),
				'description' => __("This will extract the first image at the beginning of posts, insert it into custom fields and then remove it from the post content.", 'custom-field-images'),
				'value' => __('Import', 'custom-field-images'),
				'action' => 'import'
			),

			array(
				'header' => __("Export images", 'custom-field-images'),
				'description' => __("This will insert each image in the post content and then delete the custom field.", 'custom-field-images'),
				'value' => __('Export', 'custom-field-images'),
				'action' => 'export'
			),

			array(
				'header' => __("Delete images", 'custom-field-images'),
				'description' => __("This will delete all custom field images.", 'custom-field-images'),
				'value' => __('Delete', 'custom-field-images'),
				'action' => 'delete'
			),
		);

		foreach ( $sections as $section )
		{
			extract($section);
			$output .= $this->row_wrap($header, "<p>$description</p>\n" . $this->one_button_form($action, $value));
		}

		echo $this->table_wrap($output);
	}

	function manage_handler() 
	{
		if ( !isset($_POST['action']) )
			return;

		$action = trim($_POST['action']);

		switch ($action)
		{
			case 'import':
			case 'export':
				$r = $this->impex($action); break;
			case 'delete':
				$r = $this->delete(); break;
			default: return;
		}

		if ( $r !== NULL )
		{
			$actions = array(
				'import' => __('Imported', 'custom-field-images'),
				'export' => __('Exported', 'custom-field-images'),
				'delete' => __('Deleted', 'custom-field-images'),
			);

			$this->admin_msg($actions[$action] 
				. " <strong>$r</strong> " 
				. _n('image', 'images', $r, 'custom-field-images') 
				. '.'
			);
		}
		else
			$this->admin_msg(__('An error has occured.', 'custom-field-images'), 'error');
	}

// Import/Export methods

	private function impex($action) 
	{
		$operators = array(
			'import' => '!=',
			'export' => '='
		);

		$posts = $this->get_posts($operators[$action]);

		foreach ( $posts as $post )
			$count += call_user_func(array($this, $action . '_single'), $post);

		return (int) $count;
	}

	private function import_single($post) 
	{
		if ( 0 == preg_match('#^\s*(<a[^\<]+>)?\s*(<img[^\<]+>)\s*(?:</a>)?#i', $post->content, $matches) )
			return 0;

		$img = $this->get_attributes($matches[2]);

		$element['url'] = $img['src'];
		$element['alt'] = $img['alt'];

		// Set align
		$img_clases = explode(' ', $img['class']);

		// Search for known classes
		foreach ( $img_clases as $class ) 
		{
			if ( !in_array(substr($class, 5), array_keys($this->display->styles)) )
				continue;

			$align = substr($class, 5);
			break;
		}

		$element['align'] = $align;

		// Set link
		$element['link'] = '';

		if ( $matches[1] ) 
		{
			$link = $this->get_attributes($matches[1]);
			$element['link'] = $link['href'];
		}

		add_post_meta($post->ID, $this->key, $element, TRUE);

		// Delete image from post
		$new_content = str_replace($matches[0], '', $post->content);

		$post->content = $new_content;

		wp_update_post($post);

		return 1;
	}

	private function get_attributes($string) 
	{
		preg_match_all('#(\w+)="\s*((?:[^"]+\s*)+)\s*"#i', $string, $matches, PREG_SET_ORDER);

		foreach( $matches as $att )
			$attributes[$att[1]] = $att[2];

		return $attributes;
	}

	private function export_single($post) 
	{
		$new_content = $this->display->generate($post->ID) . $post->content;

		if ( $new_content == $post->content )
			return 0;

		$post->content = $new_content;

		wp_update_post($post);

		delete_post_meta($post->ID, $this->key);

		return 1;
	}

	private function get_posts($operator) 
	{
		global $wpdb;

		return $wpdb->get_results($wpdb->prepare("
			SELECT DISTINCT ID, post_content AS content
			FROM $wpdb->posts NATURAL JOIN $wpdb->postmeta
			WHERE post_type IN ('post', 'page')
			AND meta_key $operator '%s'
		", $this->display->key));
	}

// Delete methods

	function delete()
	{
		global $wpdb;

		return $wpdb->query($wpdb->prepare("
			DELETE FROM $wpdb->postmeta
			WHERE meta_key = '%s'
		", $this->display->key));
	}
}

function cfi_admin_init($file, $options)
{
	new boxCFI();
	new settingsCFI($file, $options);

	if ( $options->insert_button )
		new insertCFI();
}


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
				'title' => '<strong>' . __('Image URL', CFI_TEXTDOMAIN) . '</strong>',
				'type' => 'text',
				'names' => 'cfi-url',
				'extra' => 'class="text"'
			),

			array(
				'title' => __('Alt. Text', CFI_TEXTDOMAIN),
				'type' => 'text',
				'names' => 'cfi-alt',
				'extra' => 'class="text"'
			),

			array(
				'title' => __('Link to', CFI_TEXTDOMAIN),
				'type' => 'text',
				'names' => 'cfi-link',
				'extra' => 'class="text"'
			),

			array(
				'title' => __('Align', CFI_TEXTDOMAIN),
				'type' => 'radio',
				'names' => 'cfi-align',
				'values' => array('left', 'center', 'right')
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
class settingsCFI extends scbOptionsPage 
{
	function __construct($file, $options)
	{
		$this->args = array(
			'page_title' => __('Custom Field Images Settings', CFI_TEXTDOMAIN),
			'menu_title' => __('CFI Settings', CFI_TEXTDOMAIN),
			'page_slug' => 'cfi-settings',
		);
		
		parent::__construct($file, $options);
	}

	function page_content() 
	{
		$rows = array(
			array(
				'title' => __('Display in', CFI_TEXTDOMAIN),
				'type' => 'checkbox',
				'names' => array('content', 'excerpt', 'feed')
			),

			array(
				'title' => __('Default alignment', CFI_TEXTDOMAIN),
				'type' => 'radio',
				'names' => 'default_align',
				'values' => array('left', 'center', 'right')
			),

			array(
				'title' => __('Extra link attributes', CFI_TEXTDOMAIN),
				'desc' => 'Example: <em>target="_blank" rel="nofollow"</em>',
				'type' => 'text',
				'names' => 'extra_attr'
			),

			array(
				'title' => __('Link image to post', CFI_TEXTDOMAIN),
				'desc' => 'If the <em>Link to</em> field is blank, the image will have a link to the post or page it is associated with.',
				'type' => 'checkbox',
				'names' => 'default_link',
			),

			array(
				'title' => __('Duplicate Alt. Text as Title', CFI_TEXTDOMAIN),
				'desc' => 'If the <em>Alt. Text</em> field is not empty, it will also be added as the image title.',
				'type' => 'checkbox',
				'names' => 'add_title',
			),

			array(
				'title' => __('Insert CFI button', CFI_TEXTDOMAIN),
				'desc' => 'Add button in the Insert Image form',
				'type' => 'checkbox',
				'names' => 'insert_button',
			)
		);
		echo $this->form_table($rows);
	}
}

// Adds the CFI Management page
class manageCFI extends scbOptionsPage 
{
	private $display;

	function __construct($file, $options)
	{
		$this->display = $GLOBALS['CFI_display'];

		$this->args = array(
			'page_title' => __('Manage Custom Field Images', CFI_TEXTDOMAIN),
			'menu_title' => __('CFI Management', CFI_TEXTDOMAIN),
			'page_slug' => 'cfi-management',
			'action_link' => 'Manage'
		);

		parent::__construct($file, $options);
	}

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

	function html_wrap($tag, $content)
	{
		return "<$tag>$content</$tag>\n";
	}

	function page_content()
	{
		echo $this->html_wrap('p', __("Here you can manage all custom field images at once. Please make a <strong>backup</strong> of your database before you proceed.", CFI_TEXTDOMAIN));

		$sections = array(
			array(
				'header' => __("Import images", CFI_TEXTDOMAIN),
				'description' => __("This will scan for images at beginning of posts, insert them into custom field keys and then remove them from the posts.", CFI_TEXTDOMAIN),
				'value' => __('Import', CFI_TEXTDOMAIN),
				'action' => 'import'
			),

			array(
				'header' => __("Export images", CFI_TEXTDOMAIN),
				'description' => __("This will insert all custom field images at the beginning of their respective posts and then delete the custom field keys.", CFI_TEXTDOMAIN),
				'value' => __('Export', CFI_TEXTDOMAIN),
				'action' => 'export'
			),

			array(
				'header' => __("Delete images", CFI_TEXTDOMAIN),
				'description' => __("This will delete all custom field images.", CFI_TEXTDOMAIN),
				'value' => __('Delete', CFI_TEXTDOMAIN),
				'action' => 'delete'
			),
		);

		foreach ( $sections as $section )
		{
			extract($section);
			echo $this->html_wrap('h2', $header);
			echo $this->html_wrap('p', $description);
			echo $this->one_button_form($action, $value);
		}
	}

	function form_handler() 
	{
		if ( !isset($_POST['action']) )
			return false;

		check_admin_referer($this->nonce);

		$action = trim($_POST['action']);

		switch ($action)
		{
			case 'import':
			case 'export':
				$r = $this->impex($action);
				break;
			case 'delete':
				$r = $this->delete();
		}

		if ( $r !== NULL )
		{
			$actions = array(
				'import' => __('Imported', CFI_TEXTDOMAIN),
				'export' => __('Exported', CFI_TEXTDOMAIN),
				'delete' => __('Deleted', CFI_TEXTDOMAIN),
			);

			$this->admin_msg($actions[$action] . " <strong>$r</strong> " . _n('image', 'images', $r, CFI_TEXTDOMAIN) . '.');
		}
		else
			$this->admin_msg(__('An error has occured.', CFI_TEXTDOMAIN), 'error');
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

		$this->update_post($new_content, $post->ID);

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

		$this->update_post($new_content, $post->ID);

		delete_post_meta($post->ID, $this->key);

		return 1;
	}

	private function get_posts($operator) 
	{
		global $wpdb;

		$query = $wpdb->prepare("
			SELECT DISTINCT ID, post_content AS content
			FROM $wpdb->posts NATURAL JOIN $wpdb->postmeta
			WHERE post_type IN ('post', 'page')
			AND meta_key $operator '%s'
		", $this->display->key);

		return $wpdb->get_results($query);
	}

	private function update_post($content, $id)
	{
		global $wpdb;

		$query = $wpdb->prepare("
			UPDATE $wpdb->posts
			SET post_content = '%s'
			WHERE ID = %d
		", $content, $id);

		return $wpdb->query($query);
	}

// Delete methods

	function delete()
	{
		global $wpdb;

		$query = $wpdb->prepare("
			DELETE FROM $wpdb->postmeta
			WHERE meta_key = '%s'
		", $this->display->key);

		return $wpdb->query($query);
	}
}

function cfi_admin_init($file, $options)
{
	// Load translations
	$plugin_dir = basename(dirname($file));
	load_plugin_textdomain(CFI_TEXTDOMAIN, "wp-content/plugins/$plugin_dir/lang", "$plugin_dir/lang");

	new boxCFI(CFI_TEXTDOMAIN);
	new settingsCFI($file, $options, CFI_TEXTDOMAIN);
	new manageCFI($file, $options, CFI_TEXTDOMAIN);

	if ( $options->insert_button )
		new insertCFI();
}


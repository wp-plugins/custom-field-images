<?php
if ( !class_exists('scbOptionsPage') )
	require_once('inc/scbOptionsPage.php');

class boxCFI extends displayCFI {
	public function __construct() {
		add_action('admin_menu', array($this, 'box_init'));
		add_action('save_post', array($this, 'save'), 1, 2);
	}

	public function box_init() {
		add_meta_box('cfi-box', 'Custom Field Image', array($this, 'box'), 'post', 'normal');
		add_meta_box('cfi-box', 'Custom Field Image', array($this, 'box'), 'page', 'normal');
	}

	public function box() {
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

	public function save($post_id, $post) {
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
	public function __construct() {
		add_action('admin_head', array($this, 'insert'));
	}

	public function insert() {
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

	public function get_plugin_url() {
		if ( function_exists('plugins_url') )
			return plugins_url(plugin_basename(dirname(__FILE__)));
		else
			// Pre-2.6 compatibility
			return get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__));
	}
}

class optionsCFI extends scbOptionsPage {
	public function __construct(scbOptions $options) {
		$this->options = $options;

		$this->args = array(
			'page_title' => 'Custom Field Images Settings',
			'short_title' => 'CFI Settings',
			'page_slug' => 'cfi-settings'
		);

		$this->nonce = 'options-cfi';
		$this->init();
	}

	public function page_content() {
		echo $this->page_header();
		$rows = array(
			array(
				'title' => 'Display in',
				'type' => 'checkbox',
				'names' => array('content', 'excerpt',	'feed'),
				'values' => 'true'
			),

			array(
				'title' => 'Default alignment',
				'type' => 'radio',
				'names' => 'default_align',
				'values' => array('left', 'center', 'right')
			),

			array(
				'title' => 'Extra link attributes',
				'desc' => 'Example: <em>target="_blank" rel="nofollow"</em>',
				'type' => 'text',
				'names' => 'extra_attr'
			),

			array(
				'title' => 'Link image to post',
				'desc' => 'If the <em>Link to</em> field is blank, the image will have a link to the post or page it is associated with.',
				'type' => 'checkbox',
				'names' => 'default_link',
				'values' => 'true'
			),

			array(
				'title' => 'Duplicate Alt. Text as Title',
				'desc' => 'If the <em>Alt. Text</em> field is not empty, it will also be added as the image title.',
				'type' => 'checkbox',
				'names' => 'add_title',
				'values' => 'true'
			),
		
			array(
				'title' => 'Insert CFI button',
				'desc' => 'Add button in the Insert Image form',
				'type' => 'checkbox',
				'names' => 'insert_button',
				'values' => 'true'
			)
		);

		echo $this->form_table($rows);
		echo $this->page_footer();
	}
}

class manageCFI extends scbOptionsPage {
	var $display;

	function __construct(displayCFI $display) {
		$this->display = $display;

		$this->args = array(
			'page_title' => 'Manage Custom Field Images',
			'short_title' => 'CFI Management',
			'page_slug' => 'cfi-management'
		);

		$this->init();
	}

	public function page_init() {
		if ( !current_user_can('manage_options') )
			return false;

		extract($this->args);
		add_management_page($short_title, $short_title, 8, $page_slug, array(&$this, 'page_content'));
	}

	public function page_content() {
		echo $this->page_header();

		echo "<p>Here you can manage all custom field images at once. Please make a <strong>backup</strong> of your database before you proceed.</p>\n";

		$warning = 'onClick="return confirm(\'Are you sure?\')" ';

		echo "<h2>Import images</h2>\n";
		echo "<p>This will scan for images at beginning of posts, insert them into custom field keys and then remove them from the posts.</p>\n";
		echo $this->form_wrap(str_replace('<input ', '<input ' . $warning ,$this->submit_button('Import')));

		echo "<h2>Export images</h2>\n";
		echo "<p>This will insert all custom field images at the beginning of their respective posts and then delete the custom field keys.</p>\n";
		echo $this->form_wrap(str_replace('<input ', '<input ' . $warning ,$this->submit_button('Export')));

		echo "<h2>Delete images</h2>\n";
		echo "<p>This will delete all custom field images.</p>\n";
		echo $this->form_wrap(str_replace('<input ', '<input ' . $warning ,$this->submit_button('Delete')));

		echo $this->page_footer();
	}

	protected function form_handler() {
		if ( !isset($_POST['action']) )
			return false;

		check_admin_referer($this->nonce);

		$action = strtolower($_POST['action']);

		switch ($action) {
			case 'import':
			case 'export':
				$r = call_user_func(array($this, 'impex'), $action);
				break;
			case 'delete':
				$r = call_user_func(array($this, 'delete'));
		}

		if ( $r !== NULL )
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
			$count += call_user_func(array($this, $action . '_single'), $post);

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
			if ( !in_array(substr($class, 5), array_keys($this->display->styles)) )
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
		$new_content = $this->display->generate($post->ID) . $post->content;

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
}

class adminCFI {
	function __construct($file) {
		global $CFIoptions, $CFIdisplay;

		new boxCFI();
		new optionsCFI($CFIoptions);
		new manageCFI($CFIdisplay);

		if ( $CFIoptions->get('insert_button') )
			new insertCFI();

		register_activation_hook($file, array($this, 'install'));
	}

	function install() {
		global $CFIoptions;

		$CFIoptions->update(array(
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


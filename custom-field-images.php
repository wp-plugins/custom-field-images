<?php
/*
Plugin Name: Custom Field Images
Version: 1.2.1
Description: Easily display images anywhere using custom fields.
Author: scribu
Author URI: http://scribu.net/
Plugin URI: http://scribu.net/downloads/custom-field-images.html
*/

/*
Copyright (C) 2008 scribu.net (scribu AT gmail DOT com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class cfImg {

/****************************/
/***** Editable options *****/
/****************************/

	var $attach_stylesheet = TRUE;

	var $styles = array(
		'left' => 'float:left; margin: 0 1em .5em 0;',
		'center' => 'display:block; margin:0 auto .5em auto;',
		'right' => 'float:right; margin: 0 0 .5em 1em;'
	);

/****************************/
/***** Do not modify anything below *****/
/****************************/

	var $data = array(
		'cfi-url' => '',
		'cfi-align' => '',
		'cfi-alt' => '',
		'cfi-link' => ''
	);

	var $show_in = array();
	
	function cfImg(){
		$this->show_in = get_option('cfi_show_in');
		
		if($this->show_in['content']){
			add_filter('the_content', array(&$this, 'display'));
		}

		if($this->show_in['excerpt']){
			add_filter('the_excerpt', array(&$this, 'display'));
		}
		
		if($this->show_in['feed'])
			//add_filter('the_content_rss', array(&$this, 'display'));
			add_filter('the_content', array(&$this, 'display'));	# hack
		
		if($cfi_attach_stylesheet)
			add_action('wp_head', array(&$this, 'stylesheet'));
	}

	function load(){
		global $post;

		$custom_fields = get_post_custom($post->ID);

		foreach($this->data as $key => $value)
			$this->data[$key] = stripslashes($custom_fields[$key][0]);

		if( $this->data['cfi-align'] == '')
			$this->data['cfi-align'] = 'right';
	}

	function generate(){
		$this->load();

		$url = $this->data['cfi-url'];
		if($url){
		# Begin img tag
			$image.= '<img src="'. $url .'" ';

			# Set alignment
			$align = $this->data['cfi-align'];
			if(is_feed())
				$image.= 'style="' . $this->styles[$align] .'" ';
			else
				$image.= 'class="align'. $align .'" ';

			# Set alt text
			$alt = $this->data['cfi-alt'];
			$image.= 'alt="';
			if($alt)
				$image.= $alt .'" ';
			else
				$image.= get_the_title() .'" ';

			# End img tag
			$image.= '/>';

			# Set link
			$link = $this->data['cfi-link'];
			if($link)
			$image = '<a href="'. $link . '">' . $image . '</a>'."\n";
			return $image;
		}
	}

	function display($content){
		$is_feed = is_feed();
		if( ($is_feed && $this->show_in['feed']) || (!$is_feed && $this->show_in['content']) )
			return $this->generate() . $content;
		else
			return $content;
	}

	function stylesheet(){
		$siteurl = get_option("siteurl");
		$siteurl = rtrim($siteurl, '/') . '/';
		$plugin_path = $siteurl . "wp-content/plugins/" . dirname(plugin_basename(__FILE__));

		echo '<link rel="stylesheet" href="' . $plugin_path . '/align.css" type="text/css" media="screen" />'."\n";
	}
}

class cfImgAdmin extends cfImg {
	function cfImgAdmin(){
		add_action('edit_form_advanced', array(&$this, 'postbox'));
		add_action('edit_page_form', array(&$this, 'postbox'));
		add_action('save_post', array(&$this, 'save'));
		add_action('admin_menu', array(&$this, 'page_init'));
	}

	function postbox(){
		$this->load();
		
		?>
	<div id="cfi-div" class="postbox <?= postbox_classes('cfi-div', 'post'); ?>">
		<h3>Custom Field Image:</h3>
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
				<?php foreach($this->styles as $align => $style){
					echo '<input name="cfi-align" id="cfi-align" type="radio" value="' . $align . '" ';
					if($this->data['cfi-align'] == $align)
						echo 'checked="checked" ';
					echo '/>'. $align ."\n";
				} ?>
			</p>
		</div>
	</div>
<?php
	}

	function save($post_id){
		$this->load();

		foreach($this->data as $name => $value)
			if ( $_POST[$name] == ''){
				# Delete value
				delete_post_meta($post_id, $name);
			}
			elseif($_POST[$name] != $value ){
				# Set new value
				$value = $_POST[$name];
				$updated = update_post_meta($post_id, $name, $value);
				if(!$updated)
					add_post_meta($post_id, $name, $value);
			}
	}

	# Options Page
	function page_init() {
		$page = add_options_page('Custom Field Images', 'Custom Field Images', 8, 'custom-field-images', array(&$this, 'page'));
		add_action("admin_print_scripts-$page", array(&$this, 'page_head'));
	}

	function page_head() {
		wp_enqueue_script('nimic_js_functions', '/wp-content/plugins/custom-field-images/functions.js');
	}

	function page() {
		$this->show_in = get_option('cfi_show_in');

		# Update display options
		if ( $_POST['submit-display'] ){
			foreach($this->show_in as $name => $value)
				$this->show_in[$name] = $_POST[$name];

			update_option('cfi_show_in', $this->show_in);
			echo '<div class="updated"><p>Display options saved.</p></div>';
		}

		unset($this->show_in);
		$this->show_in = get_option('cfi_show_in');

		# Rename cf keys
		if ( $_POST['submit-key-rename'] ){
			global $wpdb;

			foreach($this->data as $field => $value){
				$key = $_POST[$field];
				if($key){
					$query = "UPDATE $wpdb->postmeta SET meta_key = '$field' WHERE meta_key = '$key'";
					$wpdb->query($query);
				}
			}
			echo '<div class="updated"><p>Key renamed.</p></div>';
		}

		# Delete cf keys
		if ( $_POST['submit-delete'] ){
			global $wpdb;

			$query = "DELETE FROM $wpdb->postmeta WHERE meta_key IN(";
			foreach($this->data as $name => $value)
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
	<td><?php foreach($this->show_in as $name => $value){ ?>
		<input type="checkbox" <?php if($value == TRUE) echo 'checked="checked"'; ?> name="<?= $name; ?>" value="TRUE" />
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
		<?php foreach($this->data as $field => $value){ ?>
		<input type="text" name="<?= $field; ?>" size="25" />
  	 	to
		<input type="text" value="<?= $field; ?>" size="25" disabled="disabled" />
		<br />
		<?php } ?>
		If you already use custom field images, you can rename the custom field keys so that they can be used by this plugin.
		<br />Example: <em>Thumb URL</em> to <em>cfi-url</em>.
		<br />Please <strong>make a backup</strong> first!
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
		<input name="submit-delete" type="submit" onClick="return confirm_delete()" value="Delete" />
	</p>
</form>

</div>
<?php
	}
}

# Init
global $cfImg;
function cfi_init(){
	if ( is_admin() )
		$cfImgAdmin = new cfImgAdmin();
	else{
		global $cfImg;
		$cfImg = new cfImg();
	}
}

function custom_field_image(){
	global $cfImg;
	echo $cfImg->generate();
}

function cfi_activate(){
	$show_in = array(
		'content' => TRUE,
		'feed' => TRUE,
		'excerpt' => TRUE,
	);

	add_option('cfi_show_in', $show_in);
	
/*	#Update from version 1.2

	global $wpdb;

	foreach($this->styles as $style){
		$query = "UPDATE $wpdb->postmeta SET meta_value = '" . $style ."' WHERE meta_key = 'cfi-align' AND meta_value = '". substr($style, 5) ."'";
		$wpdb->query($query);
	}
*/
}

register_activation_hook(__FILE__, 'cfi_activate');
add_action('plugins_loaded', 'cfi_init');
?>
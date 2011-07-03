<?php
/*
Plugin Name: Post Links
Description: Add links to your posts!
Version: 1.0
Author: Liam Parker
Author URI: http://liamparker.com/
*/

add_action("admin_init", "plSetupAdmin");
add_action('save_post', 'plSavePluginAdmin');

//Define Initial Variables
define("pl_plugin_id", 'pl', true);
define("pl_plugin_name", 'Post Links', true);
define("pl_plugin_name_id", 'postLinks', true);
define("pl_form_verification_id", pl_plugin_id."-verification", true);
define("pl_form_row_id", pl_plugin_id."-id", true);

//Setup Admin
function plSetupAdmin(){
	if(function_exists('get_post_types')) {
		$postTypes = get_post_types( array(), 'objects' );
		foreach ($postTypes as $postType) {
			if ($postType->show_ui) {
				add_meta_box(pl_plugin_name_id, pl_plugin_name, pl_plugin_name_id, $postType->name, "normal", "high");
			}
		}
	} 
}

//Get Post Links
function getPostLinks(){
	global $post;
	$links = get_post_meta($post->ID, pl_plugin_name_id, true);
	if (is_array($links)){ 
		foreach($links['linkTitle'] as $id => $linkTitle ) {
			$link = $links['link'][$id]; 
			$result .= "<a href='$link' class='button'>$linkTitle</a>";
		}
	}
	return $result;
}

//Make Input Row
function plMakeRow($id, $title="", $link=""){
	$pl_form_row_id = pl_form_row_id."[$id]";
	$row = "<div class='box'><input type='hidden' name='$pl_form_row_id' value='$id'/><label>Link Title: </label><input type='text' name='linkTitle[$id]' value='$title' /><label> Link: </label><input type='text' name='link[$id]' value='$link' /> <input id='remove' class='button' type='button' name='remove' value='Remove Row'/></div>";
	return $row;
}

//Setup Meta Box
function postLinks(){

?>

<style>
	#wpwrap #<?php echo pl_plugin_name_id; ?> .box {margin: 5px 0;overflow: hidden;}
	#wpwrap #<?php echo pl_plugin_name_id; ?> p {margin: 10px 0;}
</style>

<script>
	var $j = jQuery.noConflict();
	$j(document).ready(function() {

		function newPostLinksRow(){
			$j('#newPostLinksRow').before("<?php echo plMakeRow(rand(1000, 2000), "", ""); ?>");
		}

		$j('#postLinks #add').click(function() {
			newPostLinksRow();
		});

		$j('#postLinks #remove').live('click', function() {
			$j(this).parent().remove();
		});

		$j('#postLinks #clear').click(function() {
			$j("#postLinks .box").remove();
			newPostLinksRow();
		})
})

</script>

<?php
	global $post;
	$custom = get_post_custom($post->ID);
	$links = $custom[pl_plugin_name_id][0];
	$links = unserialize($links);
	if (is_array($links)){ 
		foreach($links['id'] as $id ) {
			$link = $links['link'][$id]; 
			$linkTitle = $links['linkTitle'][$id]; 
			if($link or $linkTitle){
				echo plMakeRow($id, $linkTitle, $link);
			}
		}
	}
	echo plMakeRow("1000"); 
?>
	<div id="newPostLinksRow"></div> 
	<?php wp_nonce_field( plugin_basename(__FILE__), pl_form_verification_id ); ?>
	<p>
		<input id="add" class="button" type="button" name="add" value="Add New Row"/>
		<input id="clear" class="button" type="button" name="clear" value="Clear Rows"/>
	</p>
<?php
}

//Admin Save
function plSavePluginAdmin(){
	if (wp_verify_nonce( $_POST[pl_form_verification_id], plugin_basename(__FILE__)) && !defined('DOING_AUTOSAVE')) {
		global $post;
		$linksArray = array();
		$counter = 0;
		if($_POST[pl_form_row_id]){
			foreach($_POST[pl_form_row_id] as $id) {
				$linkTitle = $_POST['linkTitle'][$id]; 
				$link = $_POST['link'][$id]; 
				if($linkTitle){
					$linksArray['linkTitle'][$counter] = $linkTitle; 
				}
				if($link){
					$linksArray['link'][$counter] = $link; 
				}
				if($link && $linkTitle){
					$linksArray['id'][$counter] = $counter; 
				}
				$counter++;
			} 
		}
		if (count($linksArray)>0){
			update_post_meta($post->ID, pl_plugin_name_id, $linksArray);
		}else{
			delete_post_meta($post->ID, pl_plugin_name_id);
		} 
	}
}
?>
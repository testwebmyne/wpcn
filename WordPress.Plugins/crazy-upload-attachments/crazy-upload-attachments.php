<?php
/*
Plugin Name: Crazy Upload Attachments
Plugin URI: http://goto8848.net/projects/wordpress-plugin-crazy-upload-attachments/
Description: Use the plugin, you can upload any kind of attachments.
Version: 0.91
Author: Crazy Loong
Author URI: http://goto8848.net/
*/

/*  Copyright 2006  Crazy Loong  (email : crazyloong@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

$cua_type = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'gif' => 'image/gif',
		'png' => 'image/png',
		'bmp' => 'image/bmp',
		'tif|tiff' => 'image/tiff',
		'ico' => 'image/x-icon',
		'asf|asx|wax|wmv|wmx' => 'video/asf',
		'avi' => 'video/avi',
		'mov|qt' => 'video/quicktime',
		'mpeg|mpg|mpe' => 'video/mpeg',
		'txt|c|cc|h' => 'text/plain',
		'rtx' => 'text/richtext',
		'css' => 'text/css',
		'htm|html' => 'text/html',
		'mp3|mp4' => 'audio/mpeg',
		'ra|ram' => 'audio/x-realaudio',
		'wav' => 'audio/wav',
		'ogg' => 'audio/ogg',
		'mid|midi' => 'audio/midi',
		'wma' => 'audio/wma',
		'rtf' => 'application/rtf',
		'js' => 'application/javascript',
		'pdf' => 'application/pdf',
		'doc' => 'application/msword',
		'pot|pps|ppt' => 'application/vnd.ms-powerpoint',
		'wri' => 'application/vnd.ms-write',
		'xla|xls|xlt|xlw' => 'application/vnd.ms-excel',
		'mdb' => 'application/vnd.ms-access',
		'mpp' => 'application/vnd.ms-project',
		'swf' => 'application/x-shockwave-flash',
		'class' => 'application/java',
		'tar' => 'application/x-tar',
		'zip' => 'application/zip',
		'gz|gzip' => 'application/x-gzip',
		'exe' => 'application/x-msdownload'
);


if (FALSE == get_option('cua_type')) {
	add_option('cua_type', $cua_type);
} else {
	$cua_type = get_option('cua_type');
}

function crazy_upload_manage_page() {
	global $wpdb, $cua_type;
?>
<div class=wrap>
<?php
	if (isset($_POST['type_update'])) {
		$new_cua_type = array();
		foreach ($_POST['cua_type'] as $cua_type_key => $cua_type_value) {
			if (!empty($cua_type_value[1]) && !empty($cua_type_value[2])) {
				$new_cua_type[$cua_type_value[1]] = $cua_type_value[2];
			}
		}
		$cua_type = $new_cua_type;
		update_option('cua_type', $cua_type);
?>
<p>类型已更新</p>
<?php
	} elseif (isset($_POST['type_add']) && !empty($_POST['type_add']) && !empty($_POST['type_add'])) {
		$cua_type[$_POST['type_suffix']] = $_POST['type_mime'];
		update_option('cua_type', $cua_type);
?>
<p>类型已添加</p>
<?php
	}
?>
<h2>上传文件类型管理</h2>
<p>管理现有类型。</p>
<form method="post">
	<fieldset name="upload">
		<legend></legend>
		<table>
			<tr><td>文件后缀</td><td>MIME 类型</td></tr>
<?php
	foreach ($cua_type as $key => $value) {
		
		//$sql = "SELECT COUNT(*) FROM $cp_table_name";
		//$protects_num = $wpdb->get_var($sql);
?>
			<tr>
				<td><input type="text" name="cua_type[<?php echo $key; ?>][1]" value="<?php echo $key; ?>" size="30" /></td>
				<td><input type="text" name="cua_type[<?php echo $key; ?>][2]" value="<?php echo $value; ?>" size="30" /></td>
			</tr>
<?php
	}
?>
		</table>
	</fieldset>
	<p class="submit">
		<input type="submit" name="type_update" value="Update &raquo;" />
	</p>
</form>
<h2>添加新类型</h2>
<p>添加新的上传文件类型。</p>
<form method="post">
	<fieldset name="upload">
		<legend></legend>
		<table>
			<tr><td>文件后缀</td><td>MIME 类型</td></tr>
			<tr>
				<td><input type="text" name="type_suffix" size="30" /></td>
				<td><input type="text" name="type_mime" size="30" /></td>
			</tr>
		</table>
	</fieldset>
	<p class="submit">
		<input type="submit" name="type_add" value="Add &raquo;" />
	</p>
</form>
<?php
}


function crazy_upload_add_adminmenu() {
	add_submenu_page( 'plugins.php', 'Upload', 'Upload Sytle Management' , 8, __FILE__, 'crazy_upload_manage_page');
}

add_action('admin_menu', 'crazy_upload_add_adminmenu');

function custom_upload_file_type($type) {
	global $cua_type;
	$type = $cua_type;
	return $type;
}

add_filter('upload_mimes', 'custom_upload_file_type');
?>
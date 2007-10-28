<?php
/*
Plugin Name: Say-It-Now
Plugin URI: http://blog.edward.in/projects/say-it-now
Description: 从预先设定的诗句、名言列表中随机显示一段，可以选择显示在标题栏或者在其他的地方显示。预先已经设定了一些在数据库中，可以自己编辑诗句列表。所有的设定都可以在后台的控制面板里设定。
Version: 1.41
Author: Edward Gao
Author URI: http://blog.edward.in/
*/

/*  Thanks to Chad Butler! 感谢Chad Butler的Verse-O-Matic插件！  */

/*  Copyright 2007  Edward Gao (email : edwardright@gmail.com)

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



// 请勿修改以下内容
define("WP_SIN_VERSES", $table_prefix."sin");
define("WP_SIN_SETTINGS", $table_prefix."sin_settings");
define("WP_SIN_VERSION", "1.41");

// 安装程序开始
function sin_install() 
{
   global $wpdb;

   require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

   $table_name = WP_SIN_VERSES;
   if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      
	$sql = "CREATE TABLE `".WP_SIN_VERSES."` (
		`sinID` INT(11) NOT NULL AUTO_INCREMENT ,
		`author` VARCHAR( 20 ) NOT NULL ,
		`book` VARCHAR( 30 ) NOT NULL ,
		`chapter` VARCHAR( 10 ) NOT NULL ,
		`verse` VARCHAR( 10 ) NOT NULL ,
		`verseText` TEXT NOT NULL ,
		`visible` ENUM( 'yes', 'no' ) NOT NULL ,
		`visible_title` ENUM( 'yes', 'no' ) NOT NULL ,
  		`date` DATE DEFAULT NULL , 
  		PRIMARY KEY  (`sinID`),
  		KEY `date` (`date`)	)";	
      dbDelta($sql);
	  
	  $sql = "CREATE TABLE `".WP_SIN_SETTINGS."` (
		`displayMethod` VARCHAR( 20 ) NOT NULL ,
		`staticID` VARCHAR( 10 ) NULL ) ";	
      dbDelta($sql);

      $insert  = "INSERT INTO `".WP_SIN_VERSES."` (author, book, chapter, verse, verseText, visible, visible_title) values "
	     . "('','','','','test', 'yes', 'no'), ";
      $results = $wpdb->query( $insert );
	  
	  $insert = "INSERT INTO `".WP_SIN_SETTINGS."` (displayMethod) values ('random')";
	  $results = $wpdb->query( $insert );
   }
} // 安装程序结束




// 调用程序开始

// 1.在网页中调用
function sin_body()
{
	global $wpdb;

	// 定义显示方式
	$sql = "select * from ".WP_SIN_SETTINGS;
	$settingsArr = $wpdb->get_row($sql, ARRAY_N);
	$displayMethod = $settingsArr[0];

	// 显示方式
	switch ($displayMethod) {
	
	case "static": //静态显示模式
		$sql = "select * from ".WP_SIN_VERSES." where visible='yes' and sinID='{$settingsArr[1]}'";
		$verseArr = $wpdb->get_row($sql, ARRAY_N);
		break;
		
	case "random": //默认为随机模式
		$sql = "select * from ".WP_SIN_VERSES." where visible='yes' order by rand() limit 1";
		$verseArr = $wpdb->get_row($sql, ARRAY_N);
		break;
	
	}
	

	$verseArrCount = count($verseArr);
	$verseAuth = $verseArr[1]; //$verseAuth => 说这话的人
	$verseBook = $verseArr[2]; //$verseBook => 出自哪部书
	$verseChap = $verseArr[3]; //$verseChap => 出自哪一章
	$verseVrse = $verseArr[4]; //$verseVrse => 出自那一节
	$verseText = $verseArr[5]; //$verseText => 诗词的内容

	// 内容出处的输出设置
	if ($verseBook == null) {
		$verseRef = "";
	} else if ($verseChap != null && $verseVrse != null) {
		$verseRef = "《" . $verseBook . "》第" . $verseChap . "章 第" . $verseVrse. "节";
	} else if ($verse->chapter != null && $verse->verse == null) {
		$verseRef = "《" . $verseBook . "》第" . $verseChap . "章";
	} else if ($verse->chapter == null) {
		$verseRef = "《" . $verseBook . "》";
	}
	
	// 输出设置
	$i=0;	
	echo "\n\n<!-- BEGIN Say-It-Now Plugin -->\n";
	echo "<!--     version 1.41    -->\n";
	echo $verseText . "<br />";
	echo "<div align=\"right\">" . $verseAuth . " ——" . $verseRef . "</div>\n";
	echo "\n<!-- /END Say-It-Now Plugin -->\n\n";

}

// 2.在标题栏调用
function sin_title() {
	global $wpdb;

	//Set user defined variables
	$sql = "select * from ".WP_SIN_SETTINGS;
	$settingsArr = $wpdb->get_row($sql, ARRAY_N);
	$displayMethod = $settingsArr[0];

	// 显示方式
	
	switch ($displayMethod) {
	
	case "static": //静态显示模式
		$sql = "select * from ".WP_SIN_VERSES." where visible='yes' and sinID='{$settingsArr[1]}'";
		$verseArr = $wpdb->get_row($sql, ARRAY_N);
		break;
		
	case "random": //默认为随机模式
		$sql = "select * from ".WP_SIN_VERSES." where visible='yes' order by rand() limit 1";
		$verseArr = $wpdb->get_row($sql, ARRAY_N);
		break;
	
	} //选择结束
	
	$verseArrCount = count($verseArr);
	$verseAuth = $verseArr[1]; //$verseAuth => 说这话的人
	$verseBook = $verseArr[2]; //$verseBook => 出自哪部书
	$verseChap = $verseArr[3]; //$verseChap => 出自哪一章
	$verseVrse = $verseArr[4]; //$verseVrse => 出自那一节
	$verseText = $verseArr[5]; //$verseText => 诗词的内容
	$visible_title = $verseArr[7];
	
	// 输出方式，默认只输出诗词内容。
	$verseOutput = "$verseText";

	// 只在Blog首页显示名言
	if (!is_home() || $visible_title == 'no' || $verseOutput == null) return;
	$i=0;	
	echo "\n\n<!-- BEGIN Say-It-Now Plugin -->\n";
	echo "<!--     version 1.41    -->\n";
	echo "<script type=\"text/javascript\">\n document.title = document.title + ' - ' + \"$verseOutput\";\n</script>\n";
	echo "\n<!-- /END Say-It-Now Plugin -->\n\n";
}

// 3.侧栏调用
function widget_sin_init() {

	if ( !function_exists('register_sidebar_widget') )
	    return;

	function widget_sin($args) {
		extract($args);
		
		echo($before_module . $before_title . $title . $after_title . "<ul>");
			sin_body();
		echo("</ul>" . $after_module);
	}
	
	register_sidebar_widget('Say-It-Now', 'widget_sin');
}

// 将管理页面添加到后台导航
function sin_admin_menu()
{
	add_submenu_page('edit.php', 'Say-It-Now', 'Say-It-Now', '8', basename(__FILE__), 'sin_admin' );
}

// 加载插件项
add_action('admin_menu', 'sin_admin_menu');
add_action('activate_say-it-now.php','sin_install');
add_action('wp_head', 'sin_title');
add_action('plugins_loaded', 'widget_sin_init');

// 后台管理
function sin_admin()
{
		global $wpdb;
		
		require_once('admin.php');
		$parent_file = 'edit.php';
		
		// clear all globals. 
		$edit = $create = $save = $delete = false;
		
		// Request necessary variables, etc...
		$action           = !empty($_REQUEST['action'])              ? $_REQUEST['action'] : '';
		$display          = !empty($_REQUEST['display'])             ? $_REQUEST['display'] : '';
		$sinID            = !empty($_REQUEST['sinID'])               ? $_REQUEST['sinID'] : '';
		$author           = !empty($_REQUEST['sin_author'])          ? $_REQUEST['sin_author'] : '';
		$book             = !empty($_REQUEST['sin_book'])            ? $_REQUEST['sin_book'] : '';
		$chapter          = !empty($_REQUEST['sin_chapter'])         ? $_REQUEST['sin_chapter'] : '';
		$verse            = !empty($_REQUEST['sin_verse'])           ? $_REQUEST['sin_verse'] : '';
		$verseText        = !empty($_REQUEST['sin_verseText'])       ? $_REQUEST['sin_verseText'] : '';
		$visible          = !empty($_REQUEST['sin_visible'])         ? $_REQUEST['sin_visible'] : '';
		$visible_title    = !empty($_REQUEST['sin_visible_title'])   ? $_REQUEST['sin_visible_title'] : '';
		$date             = !empty($_REQUEST['sin_date'])            ? $_REQUEST['sin_date'] : '';
		$sinDisplay       = !empty($_REQUEST['sin_display'])         ? $_REQUEST['sin_display'] : '';
		$staticID         = !empty($_REQUEST['sin_staticID'])        ? $_REQUEST['sin_staticID'] : '';
		
		if (ini_get('magic_quotes_gpc')) {
			if($author)         {$author          = stripslashes($author);}
			if($book)           {$book            = stripslashes($book);}
			if($chapter)        {$chapter         = stripslashes($chapter);}
			if($verse)          {$verse           = stripslashes($verse);}
			if($verseText)      {$verseText       = stripslashes($verseText);}
			if($visible)        {$visible         = stripslashes($visible);}
			if($visible_title)  {$visible_title   = stripslashes($visible_title);}	
		}
		
		
		require_once('admin-header.php');
		
		
		// 基本行为：
		//  * add              添加条目
		//  * update           修改条目
		//  * delete           删除条目
		//  * update_settings  更新设置
		//  * reset            更新数据
		
		switch ($action) {
		
		
		case "add":	
		
			$sql = "insert into ".WP_SIN_VERSES." set "
				."author = '".mysql_escape_string($author)."', "
				."book = '".mysql_escape_string($book)."', "
				."chapter = '".mysql_escape_string($chapter)."', "
				."verse = '".mysql_escape_string($verse)."', "
				."verseText = '".mysql_escape_string($verseText)."', "
				."visible = '".mysql_escape_string($visible)."', "
				."visible_title = '".mysql_escape_string($visible_title)."'";
				 
			$wpdb->get_results($sql);
			
			$sql = "select sinID from ".WP_SIN_VERSES."
				where verseText='" . mysql_escape_string($verseText)."' 
				and book='".mysql_escape_string($book)."' 
				and visible='".mysql_escape_string($visible)."' 
				and visible_title='".mysql_escape_string($visible_title)."' 
				limit 1";
				
			$result = $wpdb->get_results($sql);
			
			if (empty($result) || empty($result[0]->sinID)) {?>
				<div class="error"><p><strong><?php _e('很抱歉，您本次添加失败了:('); ?></strong></p></div>
				<?php
			} else {?>
				<div id="message" class="updated fade"><p><?php _e('已成功添加:)'); ?></p></div>
				<?php
			}
			break;
		  
		
		case "update":	
			
			if (empty($sinID)) {?>
				<div class="error"><p><strong><?php _e('更新失败，请您正确填写ID信息。'); ?></strong></p></div>
				<?php		
			} else {
				$sql = "update ".WP_SIN_VERSES." set 
					author = '".mysql_escape_string($author)."', 
					book = '".mysql_escape_string($book)."', 
					chapter = '".mysql_escape_string($chapter)."', 
					verse = '".mysql_escape_string($verse)."', 
					verseText = '".mysql_escape_string($verseText)."',
					visible = '".mysql_escape_string($visible)."', 
					visible_title = '".mysql_escape_string($visible_title)."', 
					date = '".mysql_escape_string($date)."' 
					where sinID = '".mysql_escape_string($sinID)."'";
				
				$wpdb->get_results($sql);
				
				$sql = "select sinID from ".WP_SIN_VERSES."
					where verseText='" . mysql_escape_string($verseText)."' 
					and book='".mysql_escape_string($book)."' 
					and visible='".mysql_escape_string($visible)."' 
					and visible_title='".mysql_escape_string($visible_title)."'
					limit 1";
					
				$result = $wpdb->get_results($sql);
				
				if (empty($result) || empty($result[0]->sinID)) {
					?>
					<div class="error"><p><strong><?php _e('无法编辑本内容，请重试:('); ?></strong></p></div>
					<?php
				} else {
					?>
					<div id="message" class="updated fade"><p><?php _e('已成功更新:)'); ?></p></div>
					<?php
				}		
			}
			break;
		  
		
		case "delete":
		
			if (empty($sinID)) {
				?>
				<div class="error"><p><strong><?php _e('删除失败，请您选择正确的ID :('); ?></strong></p></div>
				<?php			
			} else {
				$sql = "delete from ".WP_SIN_VERSES." where sinID = '".mysql_escape_string($sinID)."'";
				$wpdb->get_results($sql);
				
				$sql = "select sinID from ".WP_SIN_VERSES." where sinID = '".mysql_escape_string($sinID)."'";
				$result = $wpdb->get_results($sql);
				
				if (empty($result) || empty($result[0]->sinID)) {
					?>
					<div id="message" class="updated fade"><p><strong><?php _e('删除成功:)'); ?></strong></p></div>
					<?php
				} else {
					?>
					<div class="error"><p><strong><?php _e('删除失败:('); ?></strong></p></div>
					<?php
				}		
			}
			break;
		
		  
		case("update_settings"):
			
			$sql = "update ".WP_SIN_SETTINGS." set 
				displayMethod = '".mysql_escape_string($sinDisplay)."',
				staticID = '".mysql_escape_string($staticID)."'";
			$wpdb->get_results($sql);?>
			
			<div id="message" class="updated fade"><p><?php _e('您已成功地更新了设置:)'); ?></p></div>
			<?php	
			break;
			
		
		case("reset_daily"):
		
			$sql = "update ".WP_SIN_VERSES." set date=null";
			$wpdb->query($sql);?>
			
			<div id="message" class="updated fade"><p><?php _e('您已成功地更新了数据:)'); ?></p></div>
			<?php
		
		}
		
		
		
		
		// 函数定义		
		
		// 诗词添加框
		function sin_edit_form($mode='add', $sinID=false)
		{
			global $wpdb;
			$data = false;
			
			if ($sinID !== false) {
				$data = $wpdb->get_results("select * from ".WP_SIN_VERSES." where sinID='".mysql_escape_string($sinID)."' limit 1");
				if (empty($data)) { ?>
					<div class="error"><p><?php _e('没有找到该ID下的条目，请'); ?><a href="edit.php?page=sin-admin.php"><?php _e('返回'); ?></a><?php _e('重新查找。'); ?></p></div>
					<?php return;
				}
				$data = $data[0];
			}
			
			if ($mode=="update") {
				$buttonText = "编辑 &raquo;";
			} else {
				$buttonText = "添加 &raquo;";
			}
			?>
			<form name="quoteform" id="quoteform" method="post" action="<?php echo $_SERVER['PHP_SELF']?>?page=say-it-now.php">
				<input type="hidden" name="action" value="<?php echo $mode?>">
				<input type="hidden" name="sinID" value="<?php echo $sinID?>">
			
				<div id="item_manager">
		
					<fieldset class="small">
						<?php _e('诗句'); ?>
						<textarea name="sin_verseText" class="input" cols=70 rows=2><?php if ( !empty($data) ) echo htmlspecialchars($data->verseText); ?></textarea>
						<?php _e('如果选择显示在标题栏里，最好少于38个字。'); ?>
					</fieldset>
					
					<fieldset class="small">
						<?php _e('书目'); ?>
						<input type="text" name="sin_book" class="input" size=10 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->book); ?>" />
						<?php _e('可以留空。'); ?>
					</fieldset>
					
					<fieldset class="small">
						<?php _e('章'); ?>
						&nbsp;&nbsp;&nbsp;<input type="text" name="sin_chapter" class="input" size=10 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->chapter); ?>" />
						<?php _e('可以留空。'); ?>
					</fieldset>
					
					<fieldset class="small">
						<?php _e('节'); ?>
						&nbsp;&nbsp;&nbsp;<input type="text" name="sin_verse" class="input" size=10 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->verse); ?>" />
						<?php _e('可以留空。'); ?>
					</fieldset>
					
					<fieldset class="small">
						<?php _e('作者'); ?>
						<input type="text" name="sin_author" class="input" size=10 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->author); ?>" />
						<?php _e('可以留空。'); ?>
					</fieldset>

					<fieldset class="small">
						<?php _e('是否可见'); ?>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="sin_visible" class="input" value="yes" 
						<?php if ( empty($data) || $data->visible=='yes' ) echo "checked" ?>/> <?php _e('是'); ?>
						<input type="radio" name="sin_visible" class="input" value="no" 
						<?php if ( !empty($data) && $data->visible=='no' ) echo "checked" ?>/> <?php _e('否'); ?>
					</fieldset>
					
					<fieldset class="small">
						<?php _e('是否显示在标题栏'); ?>
						<input type="radio" name="sin_visible_title" class="input" value="yes" 
						<?php if ( empty($data) || $data->visible_title=='yes' ) echo "checked" ?>/> <?php _e('是'); ?>
						<input type="radio" name="sin_visible_title" class="input" value="no" 
						<?php if ( !empty($data) && $data->visible_title=='no' ) echo "checked" ?>/> <?php _e('否'); ?>
					</fieldset>
					
					<p class="submit"><input type="submit" name="save" value="<?php echo $buttonText;?>" style="font-weight: bold;" tabindex="4" class="button" /></p>
		
				</div>
			</form>
			<?php
		}
		
		
		// 诗词列表
		function sin_display_list()
		{
			global $wpdb;
			$verses = $wpdb->get_results("SELECT * FROM " . WP_SIN_VERSES . " order by sinID");
			if (!empty($verses)) { ?>
				<h3><?php _e('诗句：('); ?><a href="#add")><?php _e('添加'); ?> &raquo;</a>)</h3>
				<table width="100%" cellpadding="3" cellspacing="3">
					<tr>
						<th scope="col"><?php _e('ID'); ?></th>
						<th scope="col"><?php _e('出自'); ?></th>
						<th scope="col"><?php _e('作者'); ?></th>
						<th scope="col"><?php _e('诗句'); ?></th>
						<th scope="col"><?php _e('是否显示'); ?></th>
						<th scope="col"><?php _e('显示在标题栏'); ?></th>
						<th scope="col"><?php _e('编辑'); ?></th>
						<th scope="col"><?php _e('删除'); ?></th>
					</tr>
				<?php
				$class = '';
				foreach ($verses as $verse) {
					$class = ($class == 'alternate') ? '' : 'alternate';
					$today = date('Y-m-d');
					$class = $verse->date == $today ? 'today' : $class;
					?>
					<tr class="<?php echo $class; ?>" valign="top">
						<th scope="row"><?php echo $verse->sinID; ?></th>
						<td nowrap>
							<?php 
								if ($verse->book == null) {
									echo "";
								} else if ($verse->chapter != null && $verse->verse != null) {
									echo "《".$verse->book."》"."<br />第".$verse->chapter."章 第".$verse->verse."节";
								} else if ($verse->chapter != null && $verse->verse == null) {
									echo "《".$verse->book."》"."<br />第".$verse->chapter."章"; 
								} else if ($verse->chapter == null) {
									echo "《".$verse->book."》";
								} ?>
						</td>						
						<td nowrap><?php echo $verse->author; ?></td>
						<td><?php echo $verse->verseText; ?></td>
						<td><?php echo $verse->visible=='yes' ? '是' : '否'; ?></td>
						<td><?php echo $verse->visible_title=='yes' ? '是' : '否'; ?></td>
						<td><a href="edit.php?page=say-it-now.php&action=edit&amp;sinID=<?php echo $verse->sinID;?>" class='edit'><?php _e('编辑'); ?></a></td>
						<td><a href="edit.php?page=say-it-now.php&action=delete&amp;sinID=<?php echo $verse->sinID."&amp;sin_book=".$verse->book."&amp;sin_chapter=".$verse->chapter."&amp;sin_verse=".$verse->verse;?>" class="delete" onclick="return confirm('<?php _e('确定要删除吗？'); ?>')"><?php _e('删除'); ?></a></td>
					</tr>
					<?php
				}
				?>
				</table>
				<?php
			} else {
				?>
				<p><?php _e("您还没有填写诗句。") ?></p>
				<?php	
			}
		}
		
		// 用户管理界面
		
		if ($action == 'edit') {?>
			<div class="wrap">
				<h2><?php _e('编辑诗句'); ?></h2>
				<?php
				if (empty($sinID)) {?>
					<div class="error"><p><?php _e('没有找到该ID下的条目，请'); ?><a href="edit.php?page=sin-admin.php"><?php _e('返回'); ?></a><?php _e('重新查找。'); ?></p></div>
				<?php } else {
					sin_edit_form('update', $sinID);
				} ?>
			</div>
				
		<?php } else {
		
			$sql = "select * from ".WP_SIN_SETTINGS;
			$chkdArr = $wpdb->get_row($sql, ARRAY_N);
			$displayMethod  = $chkdArr[0];
			$static = $chkdArr[1];
			?>
			<div class="wrap">
				<h2><?php _e('Say-It-Now 管理'); ?></h2>
				<h3><?php _e('设置');?></h3>
				<form name="settings" id="settings" method="post" action="<?php echo $_SERVER['PHP_SELF']?>?page=say-it-now.php">
				
				<table width="100%" border="0" cellpadding="3" cellspacing="3">
				  <tr> 
					<td width="131">Say-It-Now <br /> &nbsp;&nbsp;&nbsp;1.41 </td>
					<td width="189" align="right" nowrap><?php _e('显示方式'); ?></td>
					<td width="478" align="left">&nbsp;&nbsp;<select name="sin_display">
						<option value="random" <?php if ($displayMethod=="random") {echo "selected";}?>><?php _e('随机显示'); ?></option>
						<option value="static" <?php if ($displayMethod=="static") {echo "selected";}?>><?php _e('固定显示'); ?></option>
					  </select></td>
					<td width="119" rowspan="2" align="center"><input type="hidden" name="action" value="update_settings"> 
				    <input type="submit" name="EditSettings" value="<?php _e('确定'); ?> &raquo;" style="font-weight: bold;" tabindex="4" class="button" />					</td>
				  </tr>
				  <tr>
				    <td width="131" height="27">&nbsp;&nbsp;&nbsp;<a href="http://bbs.edward.in/">获取帮助</a></td> 
					<td align="right" nowrap><?php _e('固定显示ID'); ?></td>
					<td align="left">&nbsp;&nbsp;<input name="sin_staticID" type="text" size="3" maxlength="10" value="<?php echo $static ?>">
					<?php _e('（固定显示该ID的内容，必须在显示方式中选择固定显示方式）'); ?></td>
				  </tr>
				</table>
				</form>
				<?php sin_display_list();?>
				<input type="submit" name="reset_daily" class="button" value="<?php _e('更新数据'); ?> &raquo;" onclick="javascript:document.location.href='edit.php?page=say-it-now.php&action=reset_daily'" />
			</div>
			<div class="wrap">
				<h2><?php _e('添加'); ?></h2>
				<?php sin_edit_form(); ?>
			</div>
		<?php }?>
		
<?php } ?>
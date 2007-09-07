<?php
/*
Plugin Name: 1 Bit Audio Player
Plugin URI: http://1bit.markwheeler.net
Description: A very simple and lightweight Flash audio player for previewing tracks in a WordPress blog.
Version: 1.2.1
Author: Mark Wheeler
Author URI: http://www.markwheeler.net
*/

// add javascript to head
function oneBitJsHead() {
	if(get_option('oneBitSWFObject') == 1)
		echo '<script type="text/javascript" src="'.get_settings('siteurl').'/wp-content/plugins/1bit/swfobject.js"></script>'."\n";
    echo '<script type="text/javascript" src="'.get_settings('siteurl').'/wp-content/plugins/1bit/1bit.js"></script>'."\n";
    echo '<script type="text/javascript">'."\n";
    echo "oneBit = new OneBit('".get_settings('siteurl')."/wp-content/plugins/1bit/1bit.swf');\n";
	echo "oneBit.ready(function() {\n";
	echo "oneBit.apply('".get_settings('oneBitSelector')."', '#".get_settings('oneBitForeColor')."', '";
	if (get_settings('oneBitBackColor') != 'transparent')
		echo "#";
	echo get_settings('oneBitBackColor')."', '".get_settings('oneBitSize')."');\n";
	echo "});\n";
	echo "</script>\n";
}

function oneBitOptions() {
    if (function_exists('add_options_page')) {
		add_options_page('1 Bit Audio Player Options', '1 Bit Audio Player', 8, basename(__FILE__), 'oneBitOptionsPage');
    }
 }
 
function oneBitOptionsPage() {
	if (isset($_POST['info_update'])) { ?>
		<div class="updated">
		<p>
		<strong>
		<?php
		if($_POST['oneBitSize'] < 4 && !$_POST['oneBitAutoSize']) {
			_e('Please set the player size to a valid whole number equal to or greater than 4.', 'English');
		} else {
			if(!$_POST['oneBitSelector']) {
				_e('Please set a selector string (if in doubt just enter \'a\').', 'English');
			} else {
				if($_POST['oneBitAutoSize'])
					update_option('oneBitSize', '');
				else
				    update_option('oneBitSize', $_POST['oneBitSize']);
				update_option('oneBitForeColor', str_pad(strtoupper($_POST['oneBitForeColor']), 6, '0'));
				if($_POST['oneBitTransparent'])
				    update_option('oneBitBackColor', 'transparent');
				else
					update_option('oneBitBackColor', str_pad(strtoupper($_POST['oneBitBackColor']), 6, '0'));
				update_option('oneBitSelector', $_POST['oneBitSelector']);
				update_option('oneBitSWFObject', $_POST['oneBitSWFObject']);
				_e('Options saved.', 'English');
			}
		}
		?>
		</strong>
		</p>
		</div>
	<?php } ?>
	<div class=wrap>
	<form method="post">
		<h2>1 Bit音频播放器设置</h2>
		<div class="submit">
		<input type="submit" name="info_update" value="<?php _e('Update Options', 'English'); ?> &raquo;" />
		</div>
		<p>
		<?php _e('1 Bit音频播放器将在MP3链接后面自动插入。请到<a href="http://1bit.markwheeler.net">1 Bit website</a> 查看技术文档和更新。', 'English'); ?>
		</p>
		<fieldset name="foreColor">
		<h3><?php _e('图标颜色', 'English'); ?></h3>
		<p>
		<label for="oneBitForeColor"><?php _e('请输入颜色十六进制的值: #', 'English') ?></label>
		<input type="text" name="oneBitForeColor" id="oneBitForeColor" maxlength="6" size="10" value="<?php echo get_option('oneBitForeColor'); ?>" />
		</p>
		</fieldset>
		<fieldset name="backColor">
		<h3><?php _e('背景颜色', 'English'); ?></h3>
		<p>
		<label><input name="oneBitTransparent" type="radio" value="0" class="tog" <?php if(get_option('oneBitBackColor') != 'transparent') echo 'checked == "1" '; ?>/><?php _e("使用纯色背景（推荐）。 ", 'English') ?></label>
		<label for="oneBitBackColor"><?php _e('请输入颜色十六进制的值:: #', 'English') ?></label>
		<input type="text" name="oneBitBackColor" id="oneBitBackColor" maxlength="6" size="10" value="<?php if(get_option('oneBitBackColor') != 'transparent') echo get_option('oneBitBackColor'); ?>" />
		</p>
		<p>
		<label><input name="oneBitTransparent" type="radio" value="1" class="tog" <?php if(get_option('oneBitBackColor') == 'transparent') echo 'checked == "1" '; ?>/><?php _e('使用透明背景（可能导致在Firefox中出现<a href="http://www.google.com/search?q=wmode+firefox">\'focus\' bugs</a>）', 'English') ?></label>
		</p>
		</fieldset>
		<fieldset name="size">
		<h3><?php _e('播放器尺寸', 'English'); ?></h3>
		<p>
		<label><input name="oneBitAutoSize" type="radio" value="1" class="tog" <?php if(!get_option('oneBitSize')) echo 'checked == "1" '; ?>/><?php _e('设置为1 Bit的默认尺寸', 'English') ?></label>
		</p>
		<p>
		<label><input name="oneBitAutoSize" type="radio" value="0" class="tog" <?php if(get_option('oneBitSize')) echo 'checked == "1" '; ?>/><?php _e("手动设置尺寸。 ", 'English') ?></label>
		<label for="oneBitSize"><?php _e('请输入尺寸像素的值（播放器始终是方形的）: ', 'English') ?></label>
		<input type="text" name="oneBitSize" id="oneBitSize" maxlength="3" size="3" value="<?php echo get_option('oneBitSize'); ?>" />
		</p>
		</fieldset>
		<fieldset name="选择符">
		<h3><?php _e('Selector', 'English'); ?></h3>
		<p>
		<label for="oneBitSelector"><?php _e('应用到1 Bit的CSS选择符: ', 'English') ?></label>
		<input type="text" name="oneBitSelector" id="oneBitSelector" maxlength="50" value="<?php echo get_option('oneBitSelector'); ?>" />
		</p>
		<p>
		<?php _e('查看<a href="http://en.wikipedia.org/wiki/Cascading_Style_Sheets#Selectors_.26_Pseudo_Classes">Wikipedia</a>得到一些基础的参考。默认的选择符是简单的 \'a\'.', 'English'); ?>
		</p>
		</fieldset>
		<fieldset name="javascript">
		<h3><?php _e('包含SWFObject组件', 'English'); ?></h3>
		<p>
		<?php _e("1 Bit音频播放器插件需要 <a href=\"http://blog.deconcept.com/swfobject/\">SWFObject</a> 以嵌入Flash。如果您已经在站点上使用了SWFObject组件您可以让1 Bit不要包含它 （避免页面上重复出现脚本）.", 'English'); ?>
		</p>
		<p>
		<label><input name="oneBitSWFObject" type="radio" value="1" class="tog" <?php if(get_option('oneBitSWFObject') == 1) echo 'checked == "1" '; ?>/><?php _e('请添加SWFObject组件到我的站点', 'English') ?></label>
		</p>
		<p>
		<label><input name="oneBitSWFObject" type="radio" value="0" class="tog" <?php if(get_option('oneBitSWFObject') == 0) echo 'checked == "0" '; ?>/><?php _e("我已经使用了SWFObject组件，不需重复添加", 'English') ?></label>
		</p>
		</fieldset>
		<div class="submit">
		<input type="submit" name="info_update" value="<?php _e('更新选项', 'English'); ?> &raquo;" />
		</div>
	</form>
	</div>
	<?php
}

//set initial defaults
add_option('oneBitForeColor', '000000');
add_option('oneBitBackColor', 'FFFFFF');
add_option('oneBitSize', 10);
add_option('oneBitSelector', 'a');
add_option('oneBitSWFObject', 1);

add_action('wp_head', 'oneBitJsHead');
add_action('admin_menu', 'oneBitOptions');
?>
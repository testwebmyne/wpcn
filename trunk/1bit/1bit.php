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
		<h2>1 Bit Audio Player Options</h2>
		<div class="submit">
		<input type="submit" name="info_update" value="<?php _e('Update Options', 'English'); ?> &raquo;" />
		</div>
		<p>
		<?php _e('The 1 Bit Audio Player will be inserted automatically after any links to MP3 files. See the <a href="http://1bit.markwheeler.net">1 Bit website</a> for documentation and updates.', 'English'); ?>
		</p>
		<fieldset name="foreColor">
		<h3><?php _e('Icon color', 'English'); ?></h3>
		<p>
		<label for="oneBitForeColor"><?php _e('Hex value for icon color: #', 'English') ?></label>
		<input type="text" name="oneBitForeColor" id="oneBitForeColor" maxlength="6" size="10" value="<?php echo get_option('oneBitForeColor'); ?>" />
		</p>
		</fieldset>
		<fieldset name="backColor">
		<h3><?php _e('Background color', 'English'); ?></h3>
		<p>
		<label><input name="oneBitTransparent" type="radio" value="0" class="tog" <?php if(get_option('oneBitBackColor') != 'transparent') echo 'checked == "1" '; ?>/><?php _e("Use a solid background color (recommended). ", 'English') ?></label>
		<label for="oneBitBackColor"><?php _e('Hex value for background: #', 'English') ?></label>
		<input type="text" name="oneBitBackColor" id="oneBitBackColor" maxlength="6" size="10" value="<?php if(get_option('oneBitBackColor') != 'transparent') echo get_option('oneBitBackColor'); ?>" />
		</p>
		<p>
		<label><input name="oneBitTransparent" type="radio" value="1" class="tog" <?php if(get_option('oneBitBackColor') == 'transparent') echo 'checked == "1" '; ?>/><?php _e('Make the background transparent (can cause <a href="http://www.google.com/search?q=wmode+firefox">\'focus\' bugs</a> in Firefox)', 'English') ?></label>
		</p>
		</fieldset>
		<fieldset name="size">
		<h3><?php _e('Player size', 'English'); ?></h3>
		<p>
		<label><input name="oneBitAutoSize" type="radio" value="1" class="tog" <?php if(!get_option('oneBitSize')) echo 'checked == "1" '; ?>/><?php _e('Set 1 Bit\'s size automatically', 'English') ?></label>
		</p>
		<p>
		<label><input name="oneBitAutoSize" type="radio" value="0" class="tog" <?php if(get_option('oneBitSize')) echo 'checked == "1" '; ?>/><?php _e("Set a manual size. ", 'English') ?></label>
		<label for="oneBitSize"><?php _e('Size in pixels (the player is always square): ', 'English') ?></label>
		<input type="text" name="oneBitSize" id="oneBitSize" maxlength="3" size="3" value="<?php echo get_option('oneBitSize'); ?>" />
		</p>
		</fieldset>
		<fieldset name="selector">
		<h3><?php _e('Selector', 'English'); ?></h3>
		<p>
		<label for="oneBitSelector"><?php _e('CSS selector to apply 1 Bit to: ', 'English') ?></label>
		<input type="text" name="oneBitSelector" id="oneBitSelector" maxlength="50" value="<?php echo get_option('oneBitSelector'); ?>" />
		</p>
		<p>
		<?php _e('See <a href="http://en.wikipedia.org/wiki/Cascading_Style_Sheets#Selectors_.26_Pseudo_Classes">Wikipedia</a> for some basic reference. The default selector is simply \'a\'.', 'English'); ?>
		</p>
		</fieldset>
		<fieldset name="javascript">
		<h3><?php _e('Include SWFObject', 'English'); ?></h3>
		<p>
		<?php _e("The 1 Bit Audio Player plugin requires <a href=\"http://blog.deconcept.com/swfobject/\">SWFObject</a> in order to embed Flash. If you're already using SWFObject on your site then you can tell 1 Bit to skip it's inclusion (and avoid having the script on your pages twice).", 'English'); ?>
		</p>
		<p>
		<label><input name="oneBitSWFObject" type="radio" value="1" class="tog" <?php if(get_option('oneBitSWFObject') == 1) echo 'checked == "1" '; ?>/><?php _e('Please add SWFObject to my site', 'English') ?></label>
		</p>
		<p>
		<label><input name="oneBitSWFObject" type="radio" value="0" class="tog" <?php if(get_option('oneBitSWFObject') == 0) echo 'checked == "0" '; ?>/><?php _e("I already use SWFObject, don't add it again", 'English') ?></label>
		</p>
		</fieldset>
		<div class="submit">
		<input type="submit" name="info_update" value="<?php _e('Update Options', 'English'); ?> &raquo;" />
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
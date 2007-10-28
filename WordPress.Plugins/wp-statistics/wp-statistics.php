<?php
/*
Plugin Name: WP 2.3 statistics
Version:     4.1
Plugin URI:  http://www.sediyer.com/internet/statistics-wp-23-plug-in-version-41-update/
Description: Add the statistics into your blog, such as total posts, pages, tags.
Author:      Albert Lee
Author URI:  http://www.sediyer.com/

License
=============================================================================
Copyright (C) 2007 Albert Lee (email: lb13810398408@gmail.com)

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
/*
*/
load_plugin_textdomain('wp23_statistics',PLUGINDIR . '/' . dirname(plugin_basename (__FILE__)) );
register_activation_hook(__FILE__, 'wp_stat_activate');

$wp_stat_sample =
'posts:%post%
pages:%page%
category:%category%
tag:%tag%
links:%link%
<span title="Visitors/Admin:%comment_guest%/%comment_author%">comments:%comment%</span>
TBPB:%tbpb%
total words:%word%
<span title="already online<' . '?php echo floor((time()-strtotime("2006-4-1"))/86400); ?' . '>days">Set up time:2006.4.1</span>
last update:<br />%last%';

function wp_stat_activate() {
	global $wp_stat_sample;
	if ( get_option('wp-statistics') === false )
		update_option('wp-statistics', $wp_stat_sample);

	if ( get_option('wp-statistics-style') === false )
		update_option('wp-statistics-style', 1);
}

if ( !function_exists('mb_strlen1') ):
function mb_strlen1($str) {
	$num = 0;
	for ( $i = 0; $i < strlen($str); $i++ ) {
		$ord = ord($str{$i});
		if ( ($ord & 0xC0) != 0x80 ) $num++;
	}
	return $num;
}
endif;

class Ystat {
	var $cache;     //Cache
	var $cachelen;  //Cache length
	var $now;
	var $stat_code; //Statistics code
	var $style;     //Statistics code style

	function Ystat() {
		$this->cache = wp_cache_get('ystat', 'plugins');
		if ( $this->cache == false )
			$this->cache = array();
		$this->cachelen = count($this->cache);

		$this->stat_code = get_option('wp-statistics');
		$this->style = get_option('wp-statistics-style');
	}

	function show() {
		$code = $this->stat_code;

		if ( $this->style == 1 ) {
			$code = '<div id="statistics">' . nl2br($code) . '</div>';
		} elseif ( $this->style == 2 ) {
			$tmp = "<ul>\n";
			foreach ( explode("\n", $code) as $line ) {
				$line = trim($line);
				if ( !empty($line) )
					$tmp .= "<li>$line</li>\n";
			}
			$tmp .= '</ul>';
			$code = $tmp;
		}

		$code = preg_replace_callback('/%([^%]*)%/u', array(&$this, 'replace_stat'), $code);

		eval(' ?>' . $code . '<?php ');
	}

	function replace_stat($matches) {
		$stat = &$matches[1];
		return $this->get($stat);
	}

	function get($str) {
		global $wpdb;

		if ( isset($this->cache[$str]) )
			return $this->cache[$str];

		switch ($str) {
			//Posts
			case 'post':
				$result = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'post' AND (post_status = 'publish' OR post_status = 'private')");
				break;
			//Pages
			case 'page':
				$result = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'page' AND post_status = 'publish'");
				break;
			//Categories
			case 'category':
				$result = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE taxonomy = 'category'");
				break;
			//Tags
			case 'tag':
				$result = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->term_taxonomy WHERE taxonomy = 'post_tag'");
				break;
			//Links
			case 'link':
				$result = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->links WHERE link_visible = 'Y'");
				break;
			//Comments
			case 'comment':
				$result = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = '1' AND comment_type != 'trackback' AND comment_type != 'pingback'");
				break;
			//Comments by guest
			case 'comment_guest':
				$result = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = '1' AND comment_type != 'trackback' AND comment_type != 'pingback' AND user_id = 0");
				break;
			//Comments by author
			case 'comment_author':
				$result = $this->get('comment') - $this->get('comment_guest');
				break;
			//Trackback&Pingback
			case 'tbpb':
				$result = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = '1' AND (comment_type = 'trackback' OR comment_type = 'pingback')");
				break;
			//Word counts
			case 'word':
				$words = $wpdb->get_results("SELECT post_content FROM $wpdb->posts WHERE (post_type = 'post' OR post_type = 'page') AND (post_status = 'publish' OR post_status = 'private')");
				$result = 0;
				$mb_strlen = function_exists('mb_strlen') ? 'mb_strlen' : 'mb_strlen1';
				foreach ($words as $word)
					$result += $mb_strlen(preg_replace('/\s/', '', html_entity_decode( strip_tags($word->post_content) ) ), 'UTF-8');
				break;
			//Last Update
			case 'last':
				$result = $wpdb->get_results("SELECT MAX(post_modified) AS MAX_m FROM $wpdb->posts WHERE (post_type = 'post' OR post_type = 'page') AND (post_status = 'publish' OR post_status = 'private')");
				$result = date('Y-n-j g:ia', strtotime($result[0]->MAX_m));
				break;
			default:
				break;
		}

		$this->cache[$str] = $result;
		return $result;
	}

	function close() {
		if ($this->cachelen <> count($this->cache))
			wp_cache_set('ystat', $this->cache, 'plugins');
	}
}

function ShowStatistics() {
	$y = new Ystat();
	$y->show();
	$y->close();
}

add_action('admin_menu', 'wp_stat_add_option_page');

function wp_stat_add_option_page() {
	if ( function_exists('add_options_page') ) {
		 add_options_page("WP 2.3 statistics Settings", 'wp-statistics', 8, __FILE__, 'wp_stat_option_page');
	}
}

function wp_stat_option_page() {
	global $wpdb;
	if ( isset($_POST['Submit']) ) {
		update_option('wp-statistics', stripslashes((string) $_POST['stat']) );
		update_option('wp-statistics-style', (int) $_POST['style']);
?>
<div id="message" class="updated fade"><p><?php _e("WP 2.3 statistics has already updated",'wp23_statistics'); ?></p></div>
<?php
	}

	wp_cache_delete('ystat', 'plugins');

	$stat = get_option('wp-statistics');
	$style = get_option('wp-statistics-style');
	$wp_stat = new Ystat();
?>
<div class='wrap'>
<h2 id="edit-settings"><?php _e("WP 2.3 statistics Setup",'wp23_statistics'); ?></h2>

<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<table width="100%" border="0" cellspacing="0" cellpadding="6">

	<tr>
	<td width="50%" valign="top">
		<h3><?php _e("Statistics code",'wp23_statistics'); ?></h3>
		<p><?php _e("HTML code with PHP code Embedded. Use the code like %tag%.",'wp23_statistics'); ?></p>
	</td>

	<td width="50%" valign="top">
		<h3><?php _e("Output preview",'wp23_statistics'); ?></h3>
		<p><?php _e("It can be refresh as soon as you submit a new statistics code.",'wp23_statistics'); ?></p>
	</td>
	</tr>

	<tr>
	<td width="50%" valign="top">
		<textarea name="stat" cols="52" rows="16"><?php echo htmlentities($stat, ENT_QUOTES, 'UTF-8') ?></textarea>
	</td>

	<td width="50%" valign="top">
		<div><?php $wp_stat->show(); ?></div>
	</td>
	</tr>

	<tr><td valign="top">
	<h3><?php _e("How to deal with a line",'wp23_statistics'); ?></h3>
	<div style="margin-left:80px">
		<label><input type="radio" name="style" value="0"<?php checked(0, $style) ?> /> 
		<?php _e("none",'wp23_statistics'); ?></label><br />
		<label><input type="radio" name="style" value="1"<?php checked(1, $style) ?> /> 
		<?php _e("add &quot;&lt;br /&gt;&quot; at the end of a line",'wp23_statistics'); ?></label>
		<br />
		<label><input type="radio" name="style" value="2"<?php checked(2, $style) ?> /> 
		<?php _e("add &quot;&lt;li&gt;&quot; and &quot;&lt;/li&gt;&quot; into a line",'wp23_statistics'); ?></label>
		</div>
	</td></tr>

	</table>

	<p class="submit"><input type="submit" name="Submit" value="<?php _e('Update Options') ?> &raquo;" /></p>
</form>
</div>

<?php
global $wp_stat_sample;

$wp_stat->stat_code = $wp_stat_sample;
?>

<div class='wrap'>
<h2><?php _e("Help",'wp23_statistics'); ?></h2>

<p><?php _e("WP 2.3 statistics plugin is now already can be used in wordpress2.3.",'wp23_statistics'); ?></p>
<p><?php _e("This plugin support",'wp23_statistics'); ?> <a href="http://automattic.com/code/widgets/">WordPress Widgets</a> and <a href="http://nybblelabs.org.uk/projects/sidebar-modules/">Sidebar Modules</a>. <?php _e("You can put the statistics into your blog page easily.",'wp23_statistics'); ?></p>
<table width="100%" border="0" cellspacing="0" cellpadding="6">
	<tr>
	<td width="50%" valign="top">
		<h3><?php _e("Example of statistics code",'wp23_statistics'); ?></h3>
	  </td>

	<td width="50%" valign="top">
		<h3><?php _e("Output preview",'wp23_statistics'); ?></h3>
	  </td>
	</tr>

	<tr>
	<td width="50%" valign="top">
		<textarea name="stat" cols="52" rows="16" readonly="readonly"><?php echo htmlentities($wp_stat_sample, ENT_QUOTES, 'UTF-8'); ?></textarea>	</td>

	<td width="50%" valign="top">
		<div><?php $wp_stat->show(); ?></div>
	</td>
	</tr>

	</table>

</div>
<?php
}

function widget_Ystat_init() {

	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;

	function statistics_sidebar_module($args) {
		extract($args);

		if ( !is_home() ) return;

		echo $before_widget . $before_title . $title . $after_title;
			ShowStatistics();
		echo $after_widget;
	}

	register_sidebar_widget('Statistics module', 'statistics_sidebar_module', 'sb-stat');
}

add_action('plugins_loaded', 'widget_Ystat_init');
?>
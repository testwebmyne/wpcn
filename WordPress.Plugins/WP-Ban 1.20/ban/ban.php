<?php
/*
Plugin Name: WP-Ban
Plugin URI: http://lesterchan.net/portfolio/programming.php
Description: Ban users by IP, IP Range, host name and referer url from visiting your WordPress's blog. It will display a custom ban message when the banned IP, IP range, host name or referer url trys to visit you blog. You can also exclude certain IPs from being banned. There will be statistics recordered on how many times they attemp to visit your blog. It allows wildcard matching too.
Version: 1.20
Author: Lester 'GaMerZ' Chan
Author URI: http://lesterchan.net
*/


/*  
	Copyright 2007  Lester Chan  (email : gamerz84@hotmail.com)

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


### Create Text Domain For Translation
add_action('init', 'ban_textdomain');
function ban_textdomain() {
	load_plugin_textdomain('wp-ban', 'wp-content/plugins/ban');
}


### Function: Ban Menu
add_action('admin_menu', 'ban_menu');
function ban_menu() {
	if (function_exists('add_management_page')) {
		add_management_page(__('Ban', 'wp-ban'), __('Ban', 'wp-ban'), 'manage_options', 'ban/ban-options.php');
	}
}


### Function: Get IP Address
if(!function_exists('get_IP')) {
	function get_IP() {
		if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$ip_address = $_SERVER["REMOTE_ADDR"];
		} else {
			$ip_address = $_SERVER["HTTP_X_FORWARDED_FOR"];
		}
		if(strpos($ip_address, ',') !== false) {
			$ip_address = explode(',', $ip_address);
			$ip_address = $ip_address[0];
		}
		return $ip_address;
	}
}


### Function: Print Out Banned Message
function print_banned_message() {
	// Credits To Joe (Ttech) - http://blog.fileville.net/
	$banned_stats = get_option('banned_stats');
	$banned_stats['count'] = (intval($banned_stats['count'])+1);
	$banned_stats['users'][get_IP()] = intval($banned_stats['users'][get_IP()]+1);
	update_option('banned_stats', $banned_stats);
	$banned_message = stripslashes(get_option('banned_message'));
	$banned_message = str_replace("%SITE_NAME%", get_option('blogname'), $banned_message);
	$banned_message = str_replace("%SITE_URL%",  get_option('siteurl'), $banned_message);
	$banned_message = str_replace("%USER_ATTEMPTS_COUNT%",  $banned_stats['users'][get_IP()], $banned_message);
	$banned_message = str_replace("%USER_IP%", get_IP(), $banned_message);
	$banned_message = str_replace("%USER_HOSTNAME%",  @gethostbyaddr(get_IP()), $banned_message);
	$banned_message = str_replace("%TOTAL_ATTEMPTS_COUNT%",  $banned_stats['count'], $banned_message);				
	echo $banned_message;
	exit(); 
}


### Function: Process Banning
function process_ban($banarray, $against)  {
	if(!empty($banarray) && !empty($against)) {
		foreach($banarray as $cban) {
			$regexp = str_replace ('.', '\\.', $cban);
			$regexp = str_replace ('*', '.+', $regexp);
			if(ereg("^$regexp$", $against)) {
				print_banned_message();
			}
		}
	}
	return;
}


### Function: Process Banned IP Range
function process_ban_ip_range($banned_ips_range) {
	if(!empty($banned_ips_range)) {
		foreach($banned_ips_range as $banned_ip_range) {
			$range = explode('-', $banned_ip_range);
			$range_start = trim($range[0]);
			$range_end = trim($range[1]);
			if(check_ip_within_range(get_IP(), $range_start, $range_end)) {
				print_banned_message();
				break;
			}
		}
	}
}


### Function: Banned
add_action('init', 'banned');
function banned() {
	$banned_ips = get_option('banned_ips');
	$banned_ips_range = get_option('banned_ips_range');
	$banned_hosts = get_option('banned_hosts');
	$banned_referers = get_option('banned_referers');
	$banned_exclude_ips = get_option('banned_exclude_ips');
	$is_excluded = false;
	if(!empty($banned_exclude_ips)) {
		foreach($banned_exclude_ips as $banned_exclude_ip) {
			if(get_IP() == $banned_exclude_ip) {
				$is_excluded = true;
				break;
			}
		}
	}
	if(!$is_excluded) {
		process_ban($banned_ips, get_IP());
		process_ban_ip_range($banned_ips_range);
		process_ban($banned_hosts, @gethostbyaddr(get_IP()));
		process_ban($banned_referers, $_SERVER['HTTP_REFERER']);
	}
}


### Function: Check Whether Or Not The IP Address Belongs To Admin
function is_admin_ip($check) {
	$admin_ip = get_IP();
	$regexp = str_replace ('.', '\\.', $check);
	$regexp = str_replace ('*', '.+', $regexp);
	if(ereg("^$regexp$", $admin_ip)) {
		return true;
	}
	return false;
}


### Function: Check Whether IP Within A Given IP Range
function check_ip_within_range($ip, $range_start, $range_end) {
	$range_start = ip2long($range_start);
	$range_end = ip2long($range_end);
	$ip = ip2long($ip);
	if($ip >= $range_start && $ip <= $range_end) {
		return true;
	}
	return false;
}


### Function: Check Whether Or Not The Hostname Belongs To Admin
function is_admin_hostname($check) {
	$admin_hostname = @gethostbyaddr(get_IP());
	$regexp = str_replace ('.', '\\.', $check);
	$regexp = str_replace ('*', '.+', $regexp);
	if(ereg("^$regexp$", $admin_hostname)) {
		return true;
	}
	return false;
}


### Function: Check Whether Or Not The Referer Belongs To This Site
function is_admin_referer($check) {
	$regexp = str_replace ('.', '\\.', $check);
	$regexp = str_replace ('*', '.+', $regexp);
	$url_patterns = array(get_option('siteurl'), get_option('home'), get_option('siteurl').'/', get_option('home').'/', get_option('siteurl').'/ ', get_option('home').'/ ', $_SERVER['HTTP_REFERER']);
	foreach($url_patterns as $url) {
		if(ereg("^$regexp$", $url)) {
			return true;
		}
	}
	return false;
}


### Function: Create Ban Options
add_action('activate_ban/ban.php', 'ban_init');
function ban_init() {
	global $wpdb;
	$banned_ips = array();
	$banned_ips_range = array();
	$banned_hosts = array();
	$banned_referers = array();
	$banned_exclude_ips = array();
	$banned_stats = array('users' => array(), 'count' => 0);
	add_option('banned_ips', $banned_ips, 'Banned IPs');
	add_option('banned_hosts', $banned_hosts, 'Banned Hosts');
	add_option('banned_stats', $banned_stats, 'WP-Ban Stats');
	add_option('banned_message', '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n".
	'<html xmlns="http://www.w3.org/1999/xhtml">'."\n".
	'<head>'."\n".
	'<meta http-equiv="Content-Type" content="text/html; charset='.get_option('blog_charset').'" />'."\n".
	'<title>%SITE_NAME% - %SITE_URL%</title>'."\n".
	'</head>'."\n".
	'<body>'."\n".
	'<p style="text-align: center; font-weight: bold;">'.__('You Are Banned.', 'wp-ban').'</p>'."\n".
	'</body>'."\n".
	'</html>', 'Banned Message');
	// Database Upgrade For WP-Ban 1.11
	add_option('banned_referers', $banned_referers, 'Banned Referers');
	add_option('banned_exclude_ips', $banned_exclude_ips, 'Banned Exclude IP');
	add_option('banned_ips_range', $banned_ips_range, 'Banned IP Range');
}
?>
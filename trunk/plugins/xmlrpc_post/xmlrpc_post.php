<?php
/*
Plugin Name: XMLRPC Post
Plugin URI: http://goto8848.net/xmlrpc-post/
Description: When you post in a blog, the post will post in other blog.
Version: 1.0
Author: Crazy Loong
Author URI: http://goto8848.net/
*/

/*  Copyright 2007  Crazy Loong  (email : crazyloong@gmail.com)

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
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
include_once(ABSPATH . WPINC . '/class-IXR.php');

$xmlrpc_blog_servers = array(
	array('xmlrpc_path' => 'http://localhost/wordpress/xmlrpc.php', 'user_login' => 'admin', 'user_pass' => '123456', 'publish' => 0),
	array('xmlrpc_path' => 'http://localhost/wordpress/xmlrpc.php', 'user_login' => 'admin', 'user_pass' => '123456', 'publish' => 0)
	);

function post_in_other($post_ID) {
	global $xmlrpc_blog_servers;
	
	$post_meta_broadcast = get_post_meta($post_ID, 'broadcast', true);
	
	if ( empty($post_meta_broadcast) == false ) {
		
		foreach ( $xmlrpc_blog_servers as $xmlrpc_blog_server ) :
		
		$post_data = wp_get_single_post($post_ID, ARRAY_A);
		
		$categories = implode(',', wp_get_post_categories($post_ID));
		
		$content  = '<title>'.stripslashes($post_data['post_title']).'</title>';
		$content .= '<category>'.$categories.'</category>';
		$content .= stripslashes($post_data['post_content']);
		
		$client = new IXR_Client($xmlrpc_blog_server['xmlrpc_path']);
		$client->query('blogger.newPost', 1, 1, $xmlrpc_blog_server['user_login'], $xmlrpc_blog_server['user_pass'], $content, $xmlrpc_blog_server['publish']);
		
		endforeach;
		
		delete_post_meta($post_ID, 'broadcast');
	}
	
	return $post_ID;
}

add_action('publish_post', 'post_in_other', 5);

?>
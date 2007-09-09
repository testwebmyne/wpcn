<?php
/*
Plugin Name: Ban-Commentors
Plugin URI: http://www.paopao.name/?p=36
Description: Bans commentors by username, email, URI, or ip. Those users logined will be ignored.
Author: paopao
Version: 0.2
Author URI: http://www.paopao.name/

// Copyright (c) 2007 paopao. All rights reserved.
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// This is an plugin for WordPress
// http://wordpress.org/
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// **********************************************************************
*/


//check Commentors
function paopao_blacklist_check($author, $user_email, $url, $comment, $user_ip, $user_agent) {
	// Retrieve settings
	$banned_users = $banned_emails = $banned_uris = $banned_ips = array();
	$banned_users = explode(",", get_option("banned_users"));
	$banned_emails = explode(",", get_option("banned_emails"));
	$banned_uris = explode(",", get_option("banned_uris"));
	$banned_ips = explode(",", get_option("banned_ips"));
	$banned_flag = 0;
	$banned_reason = array();

	// Is the commentor's username banned?
	if ( !empty($author) && is_array($banned_users) && in_array($author, $banned_users) ) {
		$banned_flag = 1;
		$banned_reason[0] = 'username';
		$banned_reason[1] = $author;
	}

	// Is the commentor's email banned?
	if( !$banned_flag && !empty($user_email) && is_array($banned_emails) ) {
		foreach ($banned_emails as $id => $email) {
			$email = trim($email);
			$crnt = trim($user_email);
		
			$be = substr($email, 0, strpos($email, "@"));
			$bc = substr($crnt, 0, strpos($crnt, "@"));
			$ae = substr($email, strpos($email, "@")+1, strrpos($email, ".")-strlen($be)-1);
			$ac	= substr($crnt, strpos($crnt, "@")+1, strrpos($crnt, ".")-strlen($bc)-1);
			$ee = substr($email, strrpos($email, ".")+1);
			$ec = substr($crnt, strrpos($crnt, ".")+1);
			$match = 0;
		
			// Before the @
			if ( $be == "*" ) {
				$match++;
			}
			elseif ( $be == $bc ) {
				$match++;
			}
		
			// After the @, but before the .
			if ( $ae == "*" ) {
				$match++;
			}
			elseif ( $ae == $ac ) {
				$match++;
			}
		
			// After the . (aka extension)
			if ( $ee == "*" ) {
				$match++;
			}
			elseif ( $ee == $ec ) {
				$match++;
			}
		
			if ( $match == 3 ) {
				$banned_flag = 1;
				$banned_reason[0] = 'email';
				$banned_reason[1] = $email;
			}
		}
	}

	// Is the commentor's URI banned?
	if ( !$banned_flag && !empty($url) && is_array($banned_uris) && count($banned_uris) ) {
		foreach ($banned_uris as $id => $uri) {
			$uri = trim($uri);
			$url = trim($url);
			if ( stristr($url,$uri) ) {
				$banned_flag = 1;
				$banned_reason[0] = 'url';
				$banned_reason[1] = $uri;
				break;
			}
		}
	}

	// Is the commentor's ip banned?
	if ( !$banned_flag && is_array($banned_ips) ) {
		foreach ($banned_ips as $id => $ip) {
			$ip = trim($ip);
			$ip_split = explode(".", $ip);
			$crnt = $user_ip;
			$crnt_split	= explode(".", $crnt);
		
			$match=0;
			foreach ($ip_split as $num => $partial) {
				if ( $partial == "*" ) {
					$match++;
				}
				elseif ( $partial == $crnt_split[$num] ) {
					$match++;
				}
			}
		
			if ( $match == 4 ) {
				$banned_flag = 1;
				$banned_reason[0] = 'ip';
				$banned_reason[1] = $ip;
			}
		}
	}

	if ( $banned_flag )
		wp_die( __('You cannot use this '.$banned_reason[0].'( '.$banned_reason[1].' ) to post comments, it was banned!!!') );

	return $banned_flag;
}

//Is the commentor logined
function paopao_pre_user_id ( $user_id ){
	if( !$user_id )
		add_action('wp_blacklist_check', 'paopao_blacklist_check', 10, 6);
	return $user_id;
}

// options are added in case of plugin activation
add_action('activate_'.basename(__FILE__), 'paopao_ban_commentors_activate');
function paopao_ban_commentors_activate() {
	add_option('banned_users','');
    add_option('banned_emails','');
    add_option('banned_uris','');
    add_option('banned_ips','');
}

// options are deleted in case of plugin deactivation
add_action('deactivate_'.basename(__FILE__), 'paopao_ban_commentors_deactivate');
function paopao_ban_commentors_deactivate() {
	delete_option('banned_users','');
    delete_option('banned_emails','');
    delete_option('banned_uris','');
    delete_option('banned_ips','');
}

add_filter('pre_user_id', 'paopao_pre_user_id');
add_action('admin_menu', 'ban_commentors');

function ban_commentors() {
    global $wpdb;
    if (function_exists('add_submenu_page'))
        add_submenu_page('plugins.php', __('Ban-Commentors Options'), __('Ban-Commentors Options'), 1, __FILE__, 'bc_subpanel');
}

function bc_subpanel() {
    if ($_POST['stage'] == 'process') {
        update_option('banned_users', $_POST['banned_users_option']);
        update_option('banned_emails', $_POST['banned_emails_option']);
        update_option('banned_uris', $_POST['banned_uris_option']);
		update_option('banned_ips', $_POST['banned_ips_option']);
    }
?>
    <div class="wrap">
        <h2 id="write-post">Ban-Commentors Options&hellip;</h2>
        <p>Populate the form fields below to permanently ban comment entries on your blog by username, email address, URI or ip. Notice those users logined will be ignored.  If you notice any bugs or have suggestions please contact the developers at <a href="http://www.paopao.name/?p=36" title="paopao's blog">Ban-Commentors on paopao.name</a>.</p>
        <p>To utilize the wildcard feature, enter an IP address range to ban like this; <i>192.168.0.*</i> or <i>10.*.*.*</i>. The same works for email addresses, like this; <i>*@spam.com</i> or <i>spambot@*.*</i>, for example.</p>
        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo basename(__FILE__); ?>">
            <input type="hidden" name="stage" value="process" />
            <fieldset class="options">
                <legend>Ban-Commentors Preference</legend>
                <table>
                    <tr>
                        <td valign="top">Banned Commentors [separated by a comma(,)]</td>
                        <td>
							<textarea name="banned_users_option" rows="10" cols="70"><?php
							echo get_option("banned_users");
							?></textarea>
						</td>
                    </tr>
                    <tr>
                        <td valign="top">Banned Emails [separated by a comma(,)]</td>
                        <td>
							<textarea name="banned_emails_option" rows="10" cols="70"><?php
							echo get_option("banned_emails");
							?></textarea>
						</td>
                    </tr>
                    <tr>
                        <td valign="top">Banned URIs [separated by a comma(,)]</td>
                        <td>
							<textarea name="banned_uris_option" rows="10" cols="70"><?php
							echo get_option("banned_uris");
							?></textarea>
						</td>
                    </tr>
                    <tr>
                        <td valign="top">Banned IPs [separated by a comma(,)]</td>
                        <td>
							<textarea name="banned_ips_option" rows="10" cols="70"><?php
							echo get_option("banned_ips");
							?></textarea>
						</td>
                    </tr>
                </table>
            </fieldset>
            <p class="submit"><input type="submit" value="Update Preferences &raquo;" name="Submit" /></p>
        </form>
    </div>
<?php            
}
?>
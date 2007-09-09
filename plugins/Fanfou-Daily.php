<?php
/*
Plugin Name: Fanfou-Daily
Plugin URI: http://www.paopao.name/
Description: post your Fanfou messages to your blog daily
Author: paopao
Version: 0.1
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


//daily post
function paopao_fanfou_daily() {
	// Retrieve settings
	$postcatid = array();
	$fanfouid = get_option("FD_fanfouid");
	$timeline = strtotime(date('Y-m-d', time() + 28800 ).' 00:00') + ( get_option('FD_timeline') * 3600 )  - 86400;
	$filteron = get_option("FD_filteron") == 'yes' ? 1 : 0;
	$posttitle = get_option("FD_posttitle");
	$postcatid[] = get_option("FD_postcatid");
	if ( $posttitle == '' ) {
		$posttitle = 'Fanfou Daily '.date('Y-m-d',time() + 28800);
	} else {
		$posttitle .= ' '.date('Y-m-d',time() + 28800);
	}

	require_once dirname(__FILE__) .'/Fanfou-Daily/Fanfou_Daily.php';

	$fanfou_daily = new Fanfou_Daily($fanfouid, $timeline, $filteron);
	if ( ($postcontent = $fanfou_daily->get_daily()) != '' ) {
		$postarr = array();
		$postarr['post_title'] = $posttitle;
		$postarr['post_content'] = $postcontent;
		$postarr['post_category'] = $postcatid;
		$postarr['post_author'] = 1;
		$postarr['post_status'] = 'publish';
		wp_insert_post($postarr);
	} else {
		return false;
	}
}

// options are added in case of plugin activation
add_action('activate_'.basename(__FILE__), 'paopao_fanfou_daily_activate');
function paopao_fanfou_daily_activate() {
    add_option('FD_fanfouid','');
    add_option('FD_timeline',date('G', time() + 32400));
    add_option('FD_filteron','yes');
    add_option('FD_posttitle','');
	add_option('FD_postcat','Fanfou');
	add_option('FD_postcatid','1');
}

// options are deleted in case of plugin deactivation
add_action('deactivate_'.basename(__FILE__), 'paopao_fanfou_daily_deactivate');
function paopao_fanfou_daily_deactivate() {
	delete_option("FD_fanfouid");
	delete_option("FD_timeline");
	delete_option("FD_filteron");
	delete_option("FD_posttitle");
	delete_option("FD_postcat");
	delete_option("FD_postcatid");
	if ( $timestamp = wp_next_scheduled('paopao_fanfou_daily_hook') ) {
		wp_unschedule_event( $timestamp, 'paopao_fanfou_daily_hook' );
	}
}

add_action('paopao_fanfou_daily_hook', 'paopao_fanfou_daily');
add_action('admin_menu', 'Fanfou_Daily_menu');

function Fanfou_Daily_menu() {
    global $wpdb;
    if (function_exists('add_submenu_page'))
		add_submenu_page('plugins.php', __('Fanfou-Daily Options'), __('Fanfou-Daily Options'), 1, __FILE__, 'Fanfou_Daily_subpanel');
}

function Fanfou_Daily_subpanel() {
	global $wpdb;

    if ($_POST['FD_stage'] == 'process') {
        update_option('FD_fanfouid', $_POST['FD_fanfouid_option']);
        update_option('FD_timeline', $_POST['FD_timeline_option']);
        update_option('FD_filteron', $_POST['FD_filteron_option']);
		update_option('FD_posttitle', $_POST['FD_posttitle_option']);
		update_option('FD_postcat', $_POST['FD_postcat_option']);
		if ($cats = $wpdb->get_results("SELECT cat_ID, cat_name FROM $wpdb->categories", ARRAY_A)) {
			$cat_flag = 0;
			foreach ($cats as $cat) {
				if ( $cat['cat_name'] == $_POST['FD_postcat_option'] ) {
					update_option('FD_postcatid', $cat['cat_ID']);
					$cat_flag = 1;
					break;
				}
			}
			if ( !$cat_flag ) {
				update_option('FD_postcatid', wp_create_category($_POST['FD_postcat_option']));
			}
		}
		$newtimestamp = strtotime(date('Y-m-d', time() + 28800 ).' 00:00') + ( $_POST['FD_timeline_option'] * 3600 ) - 28800;
		if ( !($timestamp = wp_next_scheduled('paopao_fanfou_daily_hook')) ) {
			wp_schedule_event( $newtimestamp, 'daily', 'paopao_fanfou_daily_hook' );
		} else {
			wp_unschedule_event( $timestamp, 'paopao_fanfou_daily_hook' );
			wp_schedule_event( $newtimestamp, 'daily', 'paopao_fanfou_daily_hook' );
		}
    }
?>
    <div class="wrap">
        <h2 id="write-post">Fanfou-Daily Options&hellip;</h2>
        <p>Populate the form fields below to post your Fanfou messages to your blog daily.  If you notice any bugs or have suggestions please contact the developers at <a href="http://www.paopao.name/" title="paopao's blog">Fanfou-Daily on paopao.name</a>.</p>
        <p>Make sure that your fanfou id below is validated.</p>
        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo basename(__FILE__); ?>">
            <input type="hidden" name="FD_stage" value="process" />
            <fieldset class="options">
                <legend>Fanfou-Daily Preference</legend>
                <table>
                    <tr>
                        <td valign="top">Your Fanfou id:</td>
                        <td>
							<input type="text" name="FD_fanfouid_option" value="<?php echo get_option("FD_fanfouid"); ?>" />
						</td>
                    </tr>
                    <tr>
                        <td valign="top">Post time:</td>
                        <td>
							<select name="FD_timeline_option" size="1">
							<?php
								$temp_timeline = get_option("FD_timeline");
								for ( $i = 1; $i < 25; $i++ ) {
									if ( $i == $temp_timeline ) {
										echo '<option value="'.$i.'" selected>'.$i.'</option>';
									} else {
										echo '<option value="'.$i.'">'.$i.'</option>';
									}
								}
							?>
							</select>
						</td>
                    </tr>
                    <tr>
                        <td valign="top">Filter messages start with "@" :</td>
                        <td>
							<?php
								if ( get_option("FD_filteron") == 'yes' ) {
									echo '<input name="FD_filteron_option" type="checkbox" value="yes" checked>';
								} else {
									echo '<input name="FD_filteron_option" type="checkbox" value="yes">';
								}
							?>
						</td>
                    </tr>
                    <tr>
                        <td valign="top">Post title(default is "Fanfou Daily"):</td>
                        <td>
							<input type="text" name="FD_posttitle_option" value="<?php echo get_option("FD_posttitle"); ?>" />
						</td>
                    </tr>
                    <tr>
                        <td valign="top">Post categories(default is "Fanfou"):</td>
                        <td>
							<input type="text" name="FD_postcat_option" value="<?php echo get_option("FD_postcat"); ?>" />
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
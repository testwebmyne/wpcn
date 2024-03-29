<?php
/*
Plugin Name: Counterize II
Plugin URI: http://www.navision-blog.de/counterize
Description: Simple counter-plugin with no external libs - saves IP, timestamp, visited URl, referring URl and browserinformation in database, and can display total hits, unique hits and other statistics in WordPress webpages. Admin-interface available with detailed information...
Version: 2.10
Author: Steffen Forkmann
Author URI: http://navision-blog.de
*/

/*
New in 2.10
  - Italian version (thanks to Emanuele)
  - Russian version (thanks to Ivan http://shadow-blub.livejournal.com/)
  - Small installation bugfixes (thanks to Thorsten http://www.siteartwork.de/)
New in 2.08
  - %-Bug fixed (% in graphs on new line)
  - max-Width in Settings (width-fix for firefox)
  - users online
  - browser icons
  - operating systems
New in 2.06
  - Minor compatibility bugfixes for Wordpress 2.2
New in 2.05
  - small Bugfixes
  - All TableNames replaced with function calls
  - LocalizationFramework used
  - DayOfWeek-Bug fixed
  - display only EXTERNAL referers (Thanks to eric)
  - extremly speed up sql queries
  - stats moved back to Manage-Page (only for admins visible)
  - colors in all stats
  - separate statistics page for blog entries
  - Microsoft URL Control-bot excluded
New in 2.04
Features:
        - custom whois-server
        - moved all files to own folder
        - separate settings/admin page
        - stats moved to doashboard-subpage
        - ref-analyzer (http://nopaste.easy-coding.de/?id=146) for keywords
        - keyword-stats (alpha)
BugFixes:
  - mysql-version independent table structure
        - don't show stats, when db is empty (DivByZero)

New in 2.03
        - Installprocess for mysql version 4
        - Filter and MapView for most visited ips

New in 2.02
        - BugFix - UserAgent

New in 2.01
        - New Author: Steffen Forkmann
        - New Table Structure (saves a lot of space and mostly time)
        - Redesigning some functions

New in 0.53
        - By mistake (during test) commented out the exclude-function. Once again re-enabled.
        - Added the feature "top referers" in the admin-interface
        - Added the feature to manually select amount to show in the bar-graphs, instead of default 15
        - Minor stuff, changed text, thicker bars in graphs, now "unique hits last 7 days" also shown in admin-interface,

New in 0.52
        - More bots
        - New function called counterize_getuniquelatest7days() (Curtis)
        - Exclusion of most common images and RSS feeds (SHRIKEE)
        - Don't use the now() insert call when inserting entries. Use gmdate() instead
*/


if(function_exists('load_plugin_textdomain'))
        load_plugin_textdomain('counterize','wp-content/plugins/counterize');

include("browsniff.php");

# Counterize II-Tables
function counterize_agentsTable()
{
        return $GLOBALS['table_prefix'] . "Counterize_UserAgents";
}

function counterize_logTable()
{
        return $GLOBALS['table_prefix'] . "Counterize";
}

function counterize_pageTable()
{
        return $GLOBALS['table_prefix'] . "Counterize_Pages";
}

function counterize_refererTable()
{
        return $GLOBALS['table_prefix'] . "Counterize_Referers";
}

function counterize_keywordTable()
{
        return $GLOBALS['table_prefix'] . "Counterize_Keywords";
}

function counterize_update_all_userAgents()
{
    $wpdb =& $GLOBALS['wpdb'];
    $agents = $wpdb->get_results("Select * from `".counterize_agentsTable()."`");
    foreach ($agents as $agent)
    {
                list(
          $browser_name, $browser_code, $browser_ver, $os_name, $os_code, $os_ver,
                      $pda_name, $pda_code, $pda_ver ) = counterize_detect_browser($agent->name);

                $wpdb->query("update ".counterize_agentsTable()." set "
        . " browserName = '$browser_name', "
        . " browserCode = '$browser_code', "
        . " browserVersion = '$browser_ver', "
        . " osName = '$os_name', "
        . " osCode = '$os_code', "
        . " osVersion = '$os_ver' "
        . "where agentID=".$agent->agentID);
    }
}

# get the major release number of the installed mysql-version
function mysqlMajorRelease()
{
  $version = explode(".",mysql_get_client_info());
  return $version[0];
}

# Returns how many entries there are in the DB.
function counterize_getamount($only_this_month = false)
{
        $sql = 'SELECT COUNT(1) FROM ' . counterize_logTable();
        if($only_this_month)
          $sql .= " where month(timestamp) = month(now()) and year(timestamp) = year(now()) ";
        $wpdb =& $GLOBALS['wpdb'];
        return $wpdb->get_var($sql);
}

function counterize_getkeywordamount()
{
        $sql = "SELECT sum(count) FROM " . counterize_keywordTable() . " where keywordID <> 1";
        $wpdb =& $GLOBALS['wpdb'];
        return $wpdb->get_var($sql);
}

# Return how many unique entries there are in the DB.
function counterize_getuniqueamount()
{
        $sql = 'SELECT count(DISTINCT IP) FROM ' . counterize_logTable();
        $wpdb =& $GLOBALS['wpdb'];
        return $wpdb->get_var($sql);
}

# Returns amount of entries in the DB matching the visiting IP-address.
function counterize_getfromcurrentip()
{
        $sql = 'SELECT COUNT(1) FROM '.counterize_logTable().' WHERE IP = "' . $_SERVER['REMOTE_ADDR'] . '"';
        $wpdb =& $GLOBALS['wpdb'];
        return $wpdb->get_var($sql);
}

// deletes a entry from the database
function counterize_killEntry($entryID)
{
  $entries = counterize_getentries(1,$entryID);

  foreach($entries as $entry)
  {
        $sql = "DELETE FROM ".counterize_logTable()." WHERE ID=$entry->id";
        $wpdb =& $GLOBALS['wpdb'];
        $num = $wpdb->query($sql);

        $sql = "UPDATE ".counterize_pageTable()." set count = count - 1 WHERE pageID=$entry->pageID";
        $wpdb =& $GLOBALS['wpdb'];
        $num = $wpdb->query($sql);

        $sql = "UPDATE ".counterize_refererTable()." set count = count - 1 WHERE refererID=$entry->refererID";
        $wpdb =& $GLOBALS['wpdb'];
        $num = $wpdb->query($sql);

        $sql = "UPDATE ".counterize_agentsTable()." set count = count - 1 WHERE agentID=$entry->agentID";
        $wpdb =& $GLOBALS['wpdb'];
        $num = $wpdb->query($sql);

        $sql = "UPDATE ".counterize_keywordTable()." set count = count - 1 WHERE keywordID=$entry->keywordID";
        $wpdb =& $GLOBALS['wpdb'];
        $num = $wpdb->query($sql);
        }
}

// flushes the db - be careful
function counterize_flush()
{
        $sql = 'DELETE FROM '.counterize_logTable();
        $wpdb =& $GLOBALS['wpdb'];
        $num = $wpdb->query($sql);

        $sql = 'DELETE FROM '.counterize_pageTable();
        $wpdb =& $GLOBALS['wpdb'];
        $num = $wpdb->query($sql);

        $sql = 'DELETE FROM '.counterize_agentsTable();
        $wpdb =& $GLOBALS['wpdb'];
        $num = $wpdb->query($sql);

        $sql = 'DELETE FROM '.counterize_refererTable();
        $wpdb =& $GLOBALS['wpdb'];
        $num = $wpdb->query($sql);
}

# Returns amount of hits today.
function counterize_gethitstoday()
{
        $today = date("Y-m-d");
        $sql = "SELECT COUNT(1) FROM ".counterize_logTable()." WHERE timestamp >= '$today'";
        $wpdb =& $GLOBALS['wpdb'];
        return $wpdb->get_var($sql);
}

# Returns amount of hits during the last 7 days.
function counterize_getlatest7days()
{
        $sevendaysago = date("Y-m-d", strtotime("-1 week"));
        $sql = "SELECT COUNT(1) FROM ".counterize_logTable()." WHERE timestamp >= '$sevendaysago'";
        $wpdb =& $GLOBALS['wpdb'];
        return $wpdb->get_var($sql);
}

# From Curtis(http://www.graymattersonline.net/)
# Returns amount of unique IP's in the last 7 days
function counterize_getuniquelatest7days()
{
        $sevendaysago = date("Y-m-d", strtotime("-1 week"));
        $sql = "SELECT count(DISTINCT IP) FROM ".counterize_logTable()." WHERE timestamp >= '$sevendaysago'";
        $wpdb =& $GLOBALS['wpdb'];
        return $wpdb->get_var($sql);
}

function counterize_get_online_users()
{
        $sql = "SELECT count(DISTINCT IP) FROM ".counterize_logTable()." WHERE timestamp > DATE_SUB(now(), INTERVAL 5 MINUTE)";
        $wpdb =& $GLOBALS['wpdb'];
        return $wpdb->get_var($sql);
}

# Returns amount of unique referer-URl's
function counterize_getuniquereferers()
{
        $sql = 'SELECT count(DISTINCT referer) FROM '.counterize_logTable();
        $wpdb =& $GLOBALS['wpdb'];
        return $wpdb->get_var($sql);
}

# Returns amount of unique browser-strings in DB.
function counterize_getuniquebrowsers()
{
        $sql = 'SELECT count(1) FROM '. counterize_agentsTable();
        $wpdb =& $GLOBALS['wpdb'];
        return $wpdb->get_var($sql);
}

# Returns amount on current article
function counterize_getHitsOnCurrentArticle()
{
        if ($_SERVER['REQUEST_URI'])
                $requesturl = $_SERVER['REQUEST_URI'];
        $sql = "SELECT Count FROM ". counterize_pageTable() ." WHERE url = '$requesturl'";
        $wpdb =& $GLOBALS['wpdb'];
        return $wpdb->get_var($sql);
}

# Returns amount of unique URl's
function counterize_getuniqueurl()
{
        $sql = 'SELECT count(1) FROM ' .counterize_refererTable();
        $wpdb =& $GLOBALS['wpdb'];
        return $wpdb->get_var($sql);
}

function counterize_return_first_hit($dateformat = "j/n-Y")
{
        $sql = "SELECT timestamp from ".counterize_logTable()." ORDER BY id ASC LIMIT 1";
        $wpdb =& $GLOBALS['wpdb'];
        $t = $wpdb->get_var($sql);
        return date($dateformat, strtotime($t));
}

# show the most visited pages
function counterize_most_visited_pages($number = 10, $width = 300)
{
  $sql = "SELECT count as amount , url as url, url as label FROM ".counterize_pageTable()." ORDER BY amount DESC LIMIT $number";
  $wpdb =& $GLOBALS['wpdb'];
  $rows = $wpdb->get_results($sql);

  counterize_renderstats_vertical($rows, __('Page','counterize'), $width, false);
}


# New in 0.53
function counterize_most_visited_referrers($number = 10, $width = 300)
{
  $sql = "SELECT count as amount, name as label, name as url FROM %sCounterize_Referers WHERE "
    . " name <> 'unknown' and "
    . " name NOT LIKE '" . get_option("home") . "%%' "
    . " and name NOT LIKE '" . get_option("siteurl") . "%%' "
    . " ORDER BY count DESC LIMIT $number";
  $sql = sprintf($sql, $GLOBALS['table_prefix']);
  $wpdb =& $GLOBALS['wpdb'];
  $rows = $wpdb->get_results($sql);

  counterize_renderstats_vertical($rows, __('Referer','counterize'), $width);
}


function counterize_most_visited_ips($number = 10, $width = 300)
{
  $sql = "SELECT COUNT(IP) AS amount, IP as label, concat('http://www.geoiptool.com/en/?IP=',IP) as url
  FROM ".counterize_logTable()." GROUP BY IP ORDER BY amount DESC LIMIT $number";
  $wpdb =& $GLOBALS['wpdb'];
  $rows = $wpdb->get_results($sql);

  counterize_renderstats_vertical($rows, 'IP', $width);
}

function counterize_most_searched_keywords($number = 10, $width = 300)
{
  $sql = "SELECT count as amount, keyword as label FROM " . counterize_keywordTable() ." where keywordID <> 1 GROUP BY keyword ORDER BY count DESC LIMIT $number";
  $wpdb =& $GLOBALS['wpdb'];
  $rows = $wpdb->get_results($sql);

  counterize_renderstats_vertical($rows, __('Keyword','counterize'), $width);
}

function counterize_most_used_browsers_without_version($number = 10, $width = 300)
{
  $sql = "SELECT sum(count) as amount, browserName as label, browserCode FROM ".counterize_agentsTable()
  ." group by label "
  ." ORDER BY amount DESC LIMIT $number";
  $wpdb =& $GLOBALS['wpdb'];
  $rows = $wpdb->get_results($sql);

  reset($rows);
  while (list($i, $r) = each($rows)) {
    $row =& $rows[$i];
    if($row->label == " " || $row->label == "")
      $row->label = __("unknown","counterize");
    else
      $row->label = counterize_get_image_url ($row->browserCode, $row->label) . " ". $row->label;
  }

  counterize_renderstats_vertical($rows, __('UserAgent','counterize'), $width, true, "100%", false);
}

function counterize_most_used_browsers($number = 10, $width = 300)
{
  $sql = "SELECT sum(count) as amount, concat(concat(browserName,' '),browserVersion) as label, browserCode FROM ".counterize_agentsTable()
  ." group by label "
  ." ORDER BY amount DESC LIMIT $number";
  $wpdb =& $GLOBALS['wpdb'];
  $rows = $wpdb->get_results($sql);

  reset($rows);
  while (list($i, $r) = each($rows)) {
    $row =& $rows[$i];
    if($row->label == " " || $row->label == "")
      $row->label = __("unknown","counterize");
    else
      $row->label = counterize_get_image_url ($row->browserCode, $row->label) . " ". $row->label;
  }

  counterize_renderstats_vertical($rows, __('UserAgent','counterize'), $width, true, "100%", false);
}

function counterize_most_used_os($number = 10, $width = 300)
{
  $sql = "SELECT sum(count) as amount, concat(concat(osName,' '),osVersion) as label, osCode FROM ".counterize_agentsTable()
  ." group by label "
  ." ORDER BY amount DESC LIMIT $number";
  $wpdb =& $GLOBALS['wpdb'];
  $rows = $wpdb->get_results($sql);

  reset($rows);
  while (list($i, $r) = each($rows)) {
    $row =& $rows[$i];
    if($row->label == " " || $row->label == "")
      $row->label = __("unknown","counterize");
    else
      $row->label = counterize_get_image_url ($row->osCode, $row->label) . " ". $row->label;
  }

  counterize_renderstats_vertical($rows, __('Operating System','counterize'), $width, true, "100%", false);
}

function counterize_getdailystats($only_this_month = false)
{
  $wpdb =& $GLOBALS['wpdb'];

  $sql = "SELECT
    dayofmonth(timestamp) as label,
    COUNT(1) as amount
    FROM " . counterize_logTable();

  if($only_this_month)
    $sql .= " where month(timestamp) = month(now()) and year(timestamp) = year(now()) ";
  $sql .= " group by label";
  return $wpdb->get_results($sql);
}

function counterize_getmonthlystats()
{
  $wpdb =& $GLOBALS['wpdb'];

  $sql = "SELECT
    concat(concat(substring(monthname(timestamp),1,3),' '),substring(year(timestamp),3,2)) as label,
    COUNT(1) as amount,
    month(timestamp) as m,
    year(timestamp) as y
    FROM " . counterize_logTable();

  $sql .= " group by label order by y,m";
  return $wpdb->get_results($sql);
}

function counterize_getweeklystats()
{
  $wpdb =& $GLOBALS['wpdb'];

  $sql = "SELECT
    dayname(timestamp) as label,
    COUNT(1) as amount,
    dayofweek(timestamp) as day
    FROM " . counterize_logTable();

  $sql .= " group by label order by day";
  return $wpdb->get_results($sql);
}

function counterize_getdays()
{
        $sql = "SELECT timestamp from ".counterize_logTable()." ORDER BY id ASC LIMIT 1";
        $wpdb =& $GLOBALS['wpdb'];
        $t = $wpdb->get_var($sql);
        $ts = time() - date($dateformat, strtotime($t));
        return date("j", $ts);
}

# This is function is still not done - needs more work...
function counterize_gethourlystats($hour = "undef", $type = both)
{
  $wpdb =& $GLOBALS['wpdb'];

  $sql = "SELECT
    hour(timestamp) as label,
    COUNT(1) as amount
    FROM " . counterize_logTable();

  $sql .= " group by label";
  return $wpdb->get_results($sql);
}

function counterize_ref_analyzer($referer)
{
        $domain = explode('/', $referer);

        $array = array(
                        array('google','q'),
                        array('alltheweb','query'),
                        array('altavista','q'),
                        array('aol','query'),
                        array('excite','search'),
                        array('hotbot','query'),
                        array('lycos','query'),
                        array('yahoo','p'),
                        array('live','q'),
                        array('t-online','q'),
                        array('msn','q'),
                        array('netscape','search')
        );


        for($i=0; $i<count($array); $i++)
        {
                if(eregi($array[$i][0], $referer))
                {
                        $parse = parse_url($referer);
                        parse_str($parse['query'], $output);
                        $keyword = $output[$array[$i][1]];
                        break;
                }
        }

  return array('domain' => str_replace('www.', '', $domain[2]), 'keyword' => strtolower($keyword));
}

# Returns amount of unique hits today
function counterize_getuniquehitstoday()
{
  $today = date("Y-m-d");
  $sql = "SELECT count(DISTINCT ip) FROM ".counterize_logTable()." WHERE timestamp >= '$today'";
  $wpdb =& $GLOBALS['wpdb'];
  return $wpdb->get_var($sql);
}

# Returns amount of hits today, from the visiting IP
function counterize_gethitstodayfromcurrentip()
{
  $today = date("Y-m-d");
  $sql = "SELECT COUNT(1) FROM ".counterize_logTable()." WHERE timestamp >= '$today' AND IP = '" . $_SERVER['REMOTE_ADDR'] . "'";
  $wpdb =& $GLOBALS['wpdb'];
  return $wpdb->get_var($sql);
}

# Fetch information matching ID in DB.
function counterize_getentries($amount = 50, $entryID = null)
{
    $wpdb =& $GLOBALS['wpdb'];
    $sql =  'SELECT id, ip, timestamp, p.url as url, r.name as referer, ua.name as useragent, ';
    $sql .= 'm.refererID, m.agentID, m.pageID, k.keyword, k.keywordID ';
    $sql .= "FROM ".
      counterize_logTable(). " m, " .
      counterize_pageTable(). " p, " .
      counterize_agentsTable(). " ua, " .
      counterize_refererTable(). " r, " .
      counterize_keywordTable(). " k ";
    $sql .= "WHERE m.pageID = p.pageID and m.agentID = ua.agentID and m.refererID = r.refererID ";
    $sql .= " and k.keywordID = r.keywordID and ";

    if($_GET["ipfilter"])
      $sql .= " m.ip = '" . $_GET["ipfilter"] ."' and ";
    if($_GET["urifilter"])
      $sql .= " p.url = '" . $_GET["urifilter"] ."' and ";
    if($_GET["refererfilter"])
      $sql .= " r.name = '" . $_GET["refererfilter"] ."' and ";
    if($_GET["agentfilter"])
      $sql .= " ua.name = '" . $_GET["agentfilter"] ."' and ";

    if(isset($entryID))
      $sql .= " m.id = $entryID and ";

    $sql .= ' 1 = 1 ORDER BY m.timestamp DESC';

    if ($amount == "")
        $sql .= " LIMIT 50";
    else if ($amount != 0)
        $sql .= " LIMIT $amount";

    return $wpdb->get_results($sql);
}

# Append information to DB
function counterize_add()
{
        # Set to unknown, if we're unable to extract information below.
        $referer = $remoteaddr = $useragent = $requesturl = 'unknown';

         if ($_SERVER['REMOTE_ADDR'])
                $remoteaddr = $_SERVER['REMOTE_ADDR'];
         if ($_SERVER['HTTP_USER_AGENT'])
                $useragent = $_SERVER['HTTP_USER_AGENT'];
         if ($_SERVER['REQUEST_URI'])
                $requesturl = $_SERVER['REQUEST_URI'];
         if ($_SERVER['HTTP_REFERER'])
                $referer = $_SERVER['HTTP_REFERER'];

        # Check to see if we really want to insert the entry...
        $checkval = 0;

        ###################################
        # Bots detected and excluded
        #
        # To add an entry to the array, simply create line looking like this:
        #        $botarray[] = "<text in user-agent string>";
        $botarray[] = "bot";
        $botarray[] = "Yahoo! Slurp";
        $botarray[] = "slurpy";
        $botarray[] = "agent 007";
        $botarray[] = "ichiro";
        $botarray[] = "ia_archiver";
        $botarray[] = "zyborg";
        $botarray[] = "linkwalker";
        $botarray[] = "crawl";
        $botarray[] = "python";
        $botarray[] = "perl";
        $botarray[] = "w3c_validator";
        $botarray[] = "Microsoft URL Control";
        $botarray[] = "almaden";
        $botarray[] = "topicspy";
        $botarray[] = "poodle predictor";
        $botarray[] = "link checker pro";
        $botarray[] = "xenu link sleuth";
        $botarray[] = "iconsurf";
        $botarray[] = "zoe indexer";
        $botarray[] = "grub-client";
        $botarray[] = "spider";
        $botarray[] = "pompos";
        $botarray[] = "Mediapartners";
        $botarray[] = "virus_detector";
        $botarray[] = "Nuhk";
        $botarray[] = "findlinks";
        $botarray[] = "larbin";
        $botarray[] = "Sphere Scout";
        $botarray[] = "Ask Jeeves";
        $botarray[] = "Yahoo-Blogs";
        $botarray[] = "unknown";
        ##############################

        # Run through bot-array and see if there's anything we don't like...
        foreach ($botarray as $entry)
                if (stristr($useragent, $entry))
                        $checkval = 1;

        # From SHRIKEE, don't count RSS and other stuff...
        # Exclude files from being counted
        if ($checkval == 0)
        {
                // Exclude RSS feeds (Both with and without permalinks)
                // Stating just feed would make it impossible to name a page or post 'feed'
                if (stristr($requesturl, "feed/"))
                        $checkval = 1;
                if (stristr($requesturl, "feed="))
                        $checkval = 1;
                // Exclude files which annoying browsers like safari and opera request on each page
                elseif (stristr($requesturl, "robots.txt"))
                        $checkval = 1;
                elseif (stristr($requesturl, "favicon.ico"))
                        $checkval = 1;

                // Exclude any admin or core files
                elseif (stristr($requesturl, "wp-includes/"))
                        $checkval = 1;
                elseif (stristr($requesturl, "wp-admin/"))
                        $checkval = 1;
                elseif (stristr($requesturl, "wp-content/"))
                        $checkval = 1;
        }

        // more extensions?
        elseif (stristr($requesturl, ".jpg"))
                $checkval = 1;
        elseif (stristr($requesturl, ".bmp"))
                $checkval = 1;
        elseif (stristr($requesturl, ".png"))
                $checkval = 1;
        elseif (stristr($requesturl, ".gif"))
                $checkval = 1;


        # If not found anything unwanted yet, check to see if it's on the excludelist...
        if ($checkval == 0)
        {
                $tmp = get_option('counterize_excluded');
                $excludelist = preg_replace('/\s\s+/', ' ', $tmp);

                $tmp_array = explode(" ", $excludelist);
                $count = count($tmp_array);

                if ($excludelist != "" && $excludelist != " ")
                {
                        for ($i=0; $i<$count; $i++)
                        {
                                if (strpos($remoteaddr, $tmp_array[$i]) === FALSE)
                                {
                                        # Coming up...
                                }
                                else
                                {
                                        # IP found on exclude-list - we don't want it!
                                        $checkval = 1;
                                }
                        }
                }
        }

        # DISABLED: This functionality is replaced with the admin functionality
        #           to enable/disable counting of certain users...
        #
        # let's check it is a logged in user.
        # If he's logged in, we don't count him.
        if ($checkval == 0)
        {
                $excluded_users = explode(",",get_option('counterize_excluded_users'));
                global $user_ID;
                get_currentuserinfo();
                $tmp = count($excluded_users);
                if($user_ID != "" && $user_ID != " " && in_array($user_ID,$excluded_users))
                        $checkval = 1;
        }

        # If checkval is still 0, then yes - we want to insert it...
        if ($checkval == 0)
        {
                # Replace %20's(spaces) in strings with a white-space
                # Man, someone should create a better checking-module... *sigh*
                $requesturl = str_replace("%20", " ", $requesturl);
                $referer = str_replace("%20", " ", $referer);
                $timestamp = gmdate("Y-m-d H:i:s",time() + ( get_option('gmt_offset') * 60 * 60 ));

                $agentID = counterize_getUserAgentID($useragent);
                $pageID = counterize_getPageID($requesturl);
                $refererID = counterize_getRefererID($referer);
                $keywordID = counterize_getKeywordID($referer);

                $wpdb =& $GLOBALS['wpdb'];
                $sql = "INSERT INTO ".counterize_logTable()." (IP, timestamp, pageID, refererID, agentID) VALUES (";
                $sql .= "'" . $remoteaddr . "',";
                $sql .= "'" . $timestamp . "', '";
                $sql .= $pageID . "', '";
    $sql .= $refererID . "', '";
                $sql .= $agentID . "')";

                $results = $wpdb->query($sql);

                counterize_AddUserAgentVisit($agentID);
                counterize_AddPageVisit($pageID);
                counterize_AddRefererVisit($refererID);
                counterize_AddKeywordVisit($keywordID);
        }
}

# gives the useragentID back
function counterize_getUserAgentID($useragent)
{
  $wpdb =& $GLOBALS['wpdb'];
        $sql = "SELECT count(agentID) from ".counterize_agentsTable()." where name = '$useragent'";

        if(!$wpdb->get_var($sql))
        {
                # create new agent
                list(
      $browser_name, $browser_code, $browser_ver, $os_name, $os_code, $os_ver,
      $pda_name, $pda_code, $pda_ver ) = counterize_detect_browser($useragent);

                $sql = "INSERT INTO ".counterize_agentsTable()." (name,count,browserName,browserCode,browserVersion,osName,osCode,osVersion) VALUES (";
                $sql .= "'$useragent',0,'$browser_name','$browser_name','$browser_ver','$os_name','$os_code','$os_ver')";
    $wpdb->query($sql);
        }

        $sql = "SELECT agentID from ".counterize_agentsTable()." where name = '$useragent'";
        return $wpdb->get_var($sql);
}

function counterize_AddUserAgentVisit($agentID)
{
  $wpdb =& $GLOBALS['wpdb'];
        $sql = "update ".counterize_agentsTable()." set count = count + 1 where agentID = $agentID";
        $wpdb->query($sql);
}

function counterize_AddKeywordVisit($keywordID)
{
  $wpdb =& $GLOBALS['wpdb'];
        $sql = "update " . counterize_keywordTable() ." set count = count + 1 where keywordID = $keywordID";
        $wpdb->query($sql);
}

# gives the pageID back
function counterize_getPageID($url)
{
  $wpdb =& $GLOBALS['wpdb'];
        $sql = "SELECT count(pageID) from " . counterize_pageTable() ." where url = '$url'";
        if(!$wpdb->get_var($sql))
        {
                # create new page
                $sql = "INSERT INTO " . counterize_pageTable() ." (url,count) VALUES ('$url',0)";
    $wpdb->query($sql);
        }

        $sql = "SELECT pageID from " . counterize_pageTable() ." where url = '$url'";
        return $wpdb->get_var($sql);
}

function counterize_AddPageVisit($pageID)
{
  $wpdb =& $GLOBALS['wpdb'];
        $sql = "update " . counterize_pageTable() ." set count = count + 1 where pageID = $pageID";
        $wpdb->query($sql);
}

# gives the keywordID back
function counterize_getKeywordID($referer)
{
  $wpdb =& $GLOBALS['wpdb'];
  $ref = counterize_ref_analyzer($referer);
        $sql = "SELECT count(keywordID) from ".counterize_keywordTable()." where keyword = '".$ref['keyword']."'";
        $count = $wpdb->get_var($sql);

        if($count == 0)
        {
                # create new keyword
                $sql = "INSERT INTO ".counterize_keywordTable()." (keyword,count) VALUES ('" . $ref['keyword']. "',0)";
    $wpdb->query($sql);
        }

        $sql = "SELECT keywordID from ".counterize_keywordTable()." where keyword = '".$ref['keyword']."'";
        return $wpdb->get_var($sql);
}

# gives the refererID back
function counterize_getRefererID($referer)
{
  $wpdb =& $GLOBALS['wpdb'];
        $sql = "SELECT count(refererID) from ".counterize_refererTable()." where name = '$referer'";

        if(!$wpdb->get_var($sql))
        {
          $keywordID = counterize_getKeywordID($referer);
                # create new referer
                $sql = "INSERT INTO ".counterize_refererTable()." (name,count,keywordID) VALUES (";
                $sql .= "'$referer',0,$keywordID)";
    $wpdb->query($sql);
        }

        $sql = "SELECT refererID from ".counterize_refererTable()." where name = '$referer'";
        return $wpdb->get_var($sql);
}

function counterize_AddRefererVisit($refererID)
{
  $wpdb =& $GLOBALS['wpdb'];
        $sql = "update " . counterize_refererTable() ." set count = count + 1 where refererID = $refererID";
        $wpdb->query($sql);
}

function counterize_pagefooter()
{
  ?>
  <div class="wrap">
    <?php _e('<strong>Need help?</strong> Go to <a href="http://www.navision-blog.de/counterize">the Counterize II Homepage</a> and search the Blog of the <a href="http://www.navision-blog.de">.NET / Navision - Group Halle</a> - others may have had the same question or problem as you...','counterize'); ?>
  </div>
  <?php
}

function counterize_copyright()
{
  ?><br /><br />
  <p align=center><small>
    <?php
      _e('Statistics recorded with <a href="http://www.navision-blog.de/counterize" title="Counterize II - Statistics-plugin for Wordpress by Steffen Forkmann">Counterize II</a>','counterize');
      echo ' - Version ' . get_option('counterize_MajorVersion') . "." . get_option('counterize_MinorVersion');
    ?>
  </small></p>
  <?php
}

function counterize_renderstats_vertical($rows, $header, $max_width = "500",
  $nofollow = true, $maxwidth = "100%", $shorten = true)
{
    $max_label = get_option('counterize_maxWidth');
    foreach($rows as $row)
        {
            $items++;
            $complete_amount += $row->amount;
            if($row->amount > $max)
              $max = $row->amount;
        }

  ?>
  <table width="<?php echo $maxwidth; ?>">
  <tr class="alternate">
        <td width="25%"><small><strong><?php _e($header,'counterize'); ?></strong></small></td>
        <td width="10%"><small><strong><?php _e('Amount','counterize'); ?></strong></small></td>
        <td width="65%"><small><strong><?php _e('Graph / %','counterize'); ?></strong></small></td>
  </tr>

  <?php
    foreach($rows as $row)
        {
                  $percent = round($row->amount / $complete_amount * 100,2);

            if($row->amount)
                    $width = round($row->amount * $max_width / $max);
        else
          $width = 0;

        $group = round($width / $max_width * 100);
        ?>
        <tr<?php if($counter%2) { print " class=\"alternate\""; }?>>
                <td width="25%" align="left">
                        <small>
                        <?php
                          if(strlen($row->label) > $max_label && $shorten == true)
              $label = substr($row->label,0,$max_label) . '...';
            else
              $label = $row->label;
            if($row->url)
            {
              echo "<a href=\"" . $row->url . "\"";
              if($nofollow)
                echo " rel=\"nofollow\"";
              echo ">" . $label . "</a>";
            }
            else
              echo $label;
          ?>
                        </small>
                </td>

                <td width="10%">
                        <small> <?php echo $row->amount; ?> </small>
                </td>

                <td width="65%" align="left">
        <IMG SRC="<?php echo get_option("siteurl") . "/wp-content/plugins/";
                        if ($group < 40)
                                echo "counterize/counterize_red.png";
                        else if ($group < 80)
                                echo "counterize/counterize_yellow.png";
                        else
                                echo "counterize/counterize_green.png";
                        ?>" height="8" alt="<?php echo $header . ' - ' . htmlspecialchars($row->label) . ' - ' . $row->amount . ' - ' . $percent . ' %' ?>" align="bottom" width="<?php echo $width ?>"/>
                        &nbsp; <small><strong> <?php echo $percent; ?> % </strong></small>
                </td>
        </tr>
          <?php $counter++;
        }
  ?>
  </table>

  <?php
}


function counterize_renderstats($rows, $max_height = 80, $maxwidth = "100%")
{

  ?>
  <table width="<?php echo $maxwidth; ?>">
        <tr>
        <?php

          foreach($rows as $row)
          {
            $items++;
            $complete_amount += $row->amount;
            if($row->amount > $max)
              $max = $row->amount;
          }

                foreach($rows as $row)
                {
                  $percent = round($row->amount / $complete_amount * 100,2);

            if($row->amount)
                    $height = round($row->amount * $max_height / $max);
        else
          $height = 0;

        $group = round($height / $max_height * 100);

                        echo "<td width=\"3%\"";
                        if($i%2)
          print "class=\"alternate\"";

                        echo " align=\"center\" valign=\"bottom\"><small>";
                        echo $row->amount;
                        ?> <br />
                        <IMG SRC="<?php echo get_option("siteurl") . "/wp-content/plugins/";
                        if ($group < 40)
                                echo "counterize/counterize_red.png";
                        else if ($group < 80)
                                echo "counterize/counterize_yellow.png";
                        else
                                echo "counterize/counterize_green.png";
                        ?>" width="8" alt="Statistics" align="bottom" height="<?php echo $height ?>"/>
                        <?php
                        echo "<br />$percent<br />%</small></td>";
                        $i++;
                }
        ?>
        </tr>
        <tr>
        <?php
          $i = 0;
                foreach($rows as $row)
                {
                        echo "<td width=\"3%\"";
                        if($i % 2)
          echo "class=\"alternate\"";
                        echo " align=\"center\"><small><strong>$row->label</strong></small></td>";
                        $i++;
                }
        ?>
  </tr>
  </table>
  <?php
}

# Do the installation stuff, if the plugin is marked to be activated...
include("counterize_install.php");
$install = (basename($_SERVER['SCRIPT_NAME']) == 'plugins.php' && isset($_GET['activate']));
if ($install)
{
  counterize_install();
}

function counterize_show_history()
{
  $howmany = __("Latest entries",'counterize');

  $amount = get_option('counterize_amount');
  if ($amount == "" || $amount == " ")
        $amount = 50;

  $howmany = $howmany . " (" .$amount .")";
  $entries = counterize_getentries($amount);

  ?>
    <div class="wrap">
        <h2>
        <?php echo $howmany; ?>
        </h2>


        <a href="edit.php?page=counterize/counterize.php"><?php _e("Reset Filters",'counterize'); ?></a></br></br>
                <table width="100%" cellpadding="3" cellspacing="3">
                <tr class="alternate">
                <td scope="col" width="6%"><strong><?php _e("ID",'counterize'); ?></strong></td>
                <td scope="col" width="13%"><strong><?php _e("IP",'counterize'); ?></strong></td>
                <td scope="col" width="14%"><strong><?php _e("Timestamp",'counterize'); ?></strong></td>
                <td scope="col" width="30%"><strong><?php _e("URl",'counterize'); ?></strong></td>
                <td scope="col" width="20%"><strong><?php _e("Referer",'counterize'); ?></strong></td>
                <td scope="col" width="14%"><strong><?php _e("UserAgent",'counterize'); ?></strong></td>
                <td scope="col" width="25%"><strong><?php _e("Keywords",'counterize'); ?></strong></td>
                <td scope="col" width="3%"><strong><?php _e("Kill",'counterize'); ?></strong></td>
                </tr>

                <?php
                foreach($entries as $entry)
      {
      ?>
        <tr <?php if($i%2) { print "class=\"alternate\""; } ?>>
                                <td scope="col" width="6%"><small><?php echo $entry->id; ?></small> </td>
                                <td scope="col" width="10%"><small><?php echo "<a href=\"" . get_option("counterize_whois") . $entry->ip . "\">" . $entry->ip . "</a>"; ?>
            (<a href="edit.php?page=counterize/counterize.php&ipfilter=<?php echo $entry->ip; ?>">F</a>)
            (<a target="_blank" href="http://www.geoiptool.com/en/?IP=<?php echo $entry->ip; ?>">V</a>)</small> </td>
                                <td scope="col" width="14%"><small><?php echo $entry->timestamp; ?> </small></td>
                                <td scope="col" width="25%"><small><?php echo "<a href=\"" . $entry->url . "\">" . wordwrap($entry->url, 30, "\n", 1); ?> </a>
          (<a href="edit.php?page=counterize/counterize.php&urifilter=<?php echo $entry->url; ?>">F</a>)</small></td>
                                <td scope="col" width="20%"><small>
                                <?php
                                if ($entry->referer != "unknown")
          {
                                        echo "<a href=\"" . $entry->referer . "\">" . wordwrap($entry->referer, 30, "\n", 1) . "</a>";
                                        ?> (<a href="edit.php?page=counterize/counterize.php&refererfilter=<?php echo $entry->referer; ?>">F</a>) <?php
                                }
                                else
                                        echo wordwrap($entry->referer, 30, "\n", 1);
                                ?>
                                </small></td>
                                <td scope="col" width="25%"><small><?php echo counterize_browser_string($entry->useragent , true, '<br>'); ?></small> </td>
          <td><small><?php echo $entry->keyword; ?></small></td>
                                <td scope="col" width="5%">
                                <center>
                                <a href="javascript:conf('edit.php?page=counterize/counterize.php&amp;kill=<?php echo $entry->id; ?>');">
                                        <font color="red" size="+1">X</font>
                                </a>
                                </center>
                                </td>
                <?php
                  $i++;
      }
      ?>
        </tr></table>
  </div>

  <?php

}

function counterize_updateText($text="Configuration updated", $color="red")
{
  echo "<div id=\"message\" class=\"updated fade\"><p><font color=\"$color\">";
  _e($text,'counterize');
  echo "</font></p></div>";
}

function counterize_showStats($admin = false)
{
  if(!counterize_getamount())
  {
    _e("There's no data in the database - You can't see stats until you have data.",'counterize');
    return;
  }
  ?>
  <div class="wrap">
        <h2><?php _e('Hit Counter',"counterize");?></h2>
  <table width="100%" cellpadding="3" cellspacing="3">
        <tr>
                <td scope="col" width="15%" align="center">
                        <?php _e("Total hits: ",'counterize'); ?>
                </td>
                <td scope="col" width="15%" align="center">
                        <?php _e("Hits from unique IPs: ",'counterize'); ?>
                </td>
                <td scope="col" width="15%" align="center">
                        <?php _e("Total hits, today: ",'counterize'); ?>
                </td>
                <td scope="col" width="20%" align="center">
                        <?php _e("Hits from unique IPs, today: ",'counterize'); ?>
                </td>
                <td scope="col" width="15%" align="center">
                        <?php _e("Hits, the last 7 days: ",'counterize'); ?>
        </td>
                <td scope="col" width="20%" align="center">
                        <?php _e("Unique hits, the last 7 days: ",'counterize'); ?>
                </td>
        </tr>
        <tr>
          <td align="center"><strong><?php echo counterize_getamount(); ?></strong></td>
          <td align="center"><strong><?php echo counterize_getuniqueamount(); ?></strong></td>
          <td align="center"><strong><?php echo counterize_gethitstoday(); ?></strong></td>
          <td align="center"><strong><?php echo counterize_getuniquehitstoday(); ?></strong></td>
          <td align="center"><strong><?php echo counterize_getlatest7days(); ?></strong></td>
          <td align="center"><strong><?php echo counterize_getuniquelatest7days(); ?></strong></td>
        </tr>
  </table>
  </div>

  <?php
  # Amount to pass as option to the graphs...
  $amount2 = get_option('counterize_amount2');
  if ($amount2 == "" || $amount2 == " ")
        $amount2 = 5;
  $width = 250;
  ?>

  <div class="wrap">
        <h2><?php _e('Visits based on day of month','counterize');?></h2>
        <?php
      if($admin)
        counterize_renderstats(counterize_getdailystats());
      else
        counterize_renderstats_vertical(counterize_getdailystats(),__("Day",'counterize'),$width);
    ?>
  </div>

  <div class="wrap">
        <h2><?php _e('Visits based on day of month (only this month)','counterize');?></h2>
        <?php
          if($admin)
        counterize_renderstats(counterize_getdailystats(true));
      else
        counterize_renderstats_vertical(counterize_getdailystats(true),__("Day",'counterize'),$width);
    ?>
  </div>


  <div class="wrap">
        <h2><?php _e('Visits based on day of week','counterize');?></h2>
        <?php
          if($admin)
        counterize_renderstats(counterize_getweeklystats());
      else
        counterize_renderstats_vertical(counterize_getweeklystats(),__("Day",'counterize'),$width);
    ?>
  </div>

  <div class="wrap">
        <h2><?php _e('Visits based on month','counterize');?></h2>
        <?php
      if($admin)
        counterize_renderstats(counterize_getmonthlystats());
      else
        counterize_renderstats_vertical(counterize_getmonthlystats(),__("Month",'counterize'),$width);
    ?>
  </div>

  <div class="wrap">
        <h2><?php _e('Visits based on hour of day','counterize');?></h2>
        <?php
          if($admin)
        counterize_renderstats(counterize_gethourlystats());
      else
        counterize_renderstats_vertical(counterize_gethourlystats(),__("Hour",'counterize'),$width);
    ?>
  </div>

  <div class="wrap">
        <h2><?php echo __("Most visited pages ",'counterize') . "(" . $amount2 .")"; ?></h2>
        <center> <?php counterize_most_visited_pages($amount2,$width); ?> </center>
  </div>

  <?php
  if($admin)
  {
  ?>
    <div class="wrap">
        <h2><?php echo __("Most visited IPs ",'counterize') . "(" . $amount2 . ")"; ?></h2>
        <center> <?php counterize_most_visited_IPs($amount2,$width); ?> </center>
    </div>
  <?php
  }
  ?>
  <div class="wrap">
        <h2><?php echo __("Most seen referers ",'counterize') . "(" . $amount2 . ")"; ?></h2>
        <center> <?php counterize_most_visited_referrers($amount2,$width); ?> </center>
  </div>

  <div class="wrap">
        <h2><?php echo __("Most used browsers ",'counterize') . "(" . $amount2 . ")";?></h2>
        <center> <?php counterize_most_used_browsers_without_version($amount2,$width); ?> </center>
  </div>

  <div class="wrap">
        <h2><?php echo __("Most used browsers versions ",'counterize') . "(" . $amount2 . ")";?></h2>
        <center> <?php counterize_most_used_browsers($amount2,$width); ?> </center>
  </div>

  <div class="wrap">
        <h2><?php echo __("Most used operating systems ",'counterize') . "(" . $amount2 . ")";?></h2>
        <center> <?php counterize_most_used_os($amount2,$width); ?> </center>
  </div>

  <div class="wrap">
  <h2><?php echo __("Most searched keywords ",'counterize') . "(" . $amount2 . ")";?></h2>
        <center> <?php counterize_most_searched_Keywords($amount2,$width); ?> </center>
  </div>
 <?php
}

function counterize_manage_page()
{
  ?><script language="javascript" type="text/javascript">
        function conf(url)
        {
                if (confirm('<?php _e('Are you sure that you want to delete this entry?','counterize'); ?>'))
                {
                        self.location.href = url;
                }
        }
  </script>
  <?php

  # For the zap-an-entry-option
  if (isset($_GET['kill']))
  {
    counterize_killEntry($_GET['kill']);
    counterize_updateText(__("Deleting entry ","counterize") . $_GET['kill']);
  }

  counterize_showStats(true);
  counterize_show_history();
  counterize_pagefooter();
}

function counterize_filter($data)
{
  $pattern = '/\<\!\-\-\s*counterize_stats\s*\-\-\>/';
        while(preg_match($pattern, $data, $matches))
  {
                ob_start();

                counterize_showStats();
                counterize_copyright();

                $content = ob_get_contents();
                ob_end_clean();
                $replace_pattern = $pattern;
                $data = preg_replace($replace_pattern, $content, $data);
        }
        return $data;
}

include("counterize_admin.php");
include("counterize_dashboard.php");

function counterize_add_pages()
{
  # Set it up... - add to Dashboard and options-page.
  add_action('admin_footer', 'counterize_dashboard');
  add_submenu_page('edit.php',__('Counterize II'), __('Counterize II'), 8, __FILE__, 'counterize_manage_page');
  add_options_page('Counterize II Options', 'Counterize II', 8, basename(__FILE__), 'counterize_options_page');
}

add_action('admin_menu', 'counterize_add_pages');

# Create API hook instead of placing code in the header.php-file
add_action('wp_head', 'counterize_add', 1);
add_filter('the_content', 'counterize_filter');

?>

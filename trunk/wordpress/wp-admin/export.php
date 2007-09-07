<?php
require_once ('admin.php');
$title = __('Export');
$parent_file = 'edit.php';

if ( isset( $_GET['download'] ) )
	export_wp();

require_once ('admin-header.php');
?>

<div class="wrap">
<h2><?php _e('Export'); ?></h2>
<div class="narrow">
<p><?php _e('When you click the button below WordPress will create an XML file for you to save to your computer.'); ?></p>
<p><?php _e('This format, which we call WordPress eXtended RSS or WXR, will contain your posts, comments, custom fields, and categories.'); ?></p>
<p><?php _e('Once you&#8217;ve saved the download file, you can use the Import function on another WordPress blog to import this blog.'); ?></p>
<form action="" method="get">
<h3><?php _e('Optional options'); ?></h3>

<table>
<tr>
<th><?php _e('Restrict Author:'); ?></th>
<td>
<select name="author">
<option value="all" selected="selected"><?php _e('All'); ?></option>
<?php
$authors = $wpdb->get_col( "SELECT post_author FROM $wpdb->posts GROUP BY post_author" );
foreach ( $authors as $id ) {
	$o = get_userdata( $id );
	echo "<option value='$o->ID'>$o->display_name</option>";
}
?>
</select>
</td>
</tr>
</table>
<p class="submit"><input type="submit" name="submit" value="<?php _e('Download Export File'); ?> &raquo;" />
<input type="hidden" name="download" value="true" />
</p>
</form>
</div>
</div>

<?php

function export_wp() {
global $wpdb, $post_ids, $post;

do_action('export_wp');

$filename = 'wordpress.' . date('Y-m-d') . '.xml';

header('Content-Description: File Transfer');
header("Content-Disposition: attachment; filename=$filename");
header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);

$where = '';
if ( isset( $_GET['author'] ) && $_GET['author'] != 'all' ) {
	$author_id = (int) $_GET['author'];
	$where = " WHERE post_author = '$author_id' ";
}

// grab a snapshot of post IDs, just in case it changes during the export
$post_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts $where ORDER BY post_date_gmt ASC");

$categories = (array) get_categories('get=all');

function wxr_missing_parents($categories) {
	if ( !is_array($categories) || empty($categories) )
		return array();

	foreach ( $categories as $category )
		$parents[$category->term_id] = $category->parent;

	$parents = array_unique(array_diff($parents, array_keys($parents)));

	if ( $zero = array_search('0', $parents) )
		unset($parents[$zero]);

	return $parents;
}

while ( $parents = wxr_missing_parents($categories) ) {
	$found_parents = get_categories("include=" . join(', ', $parents));
	if ( is_array($found_parents) && count($found_parents) )
		$categories = array_merge($categories, $found_parents);
	else
		break;
}

// Put them in order to be inserted with no child going before its parent
$pass = 0;
$passes = 1000 + count($categories);
while ( ( $cat = array_shift($categories) ) && ++$pass < $passes ) {
	if ( $cat->parent == 0 || isset($cats[$cat->parent]) ) {
		$cats[$cat->term_id] = $cat;
	} else {
		$categories[] = $cat;
	}
}
unset($categories);

function wxr_cdata($str) {
	if ( seems_utf8($str) == false )
		$str = utf8_encode($str);

	// $str = ent2ncr(wp_specialchars($str));

	$str = "<![CDATA[$str" . ( ( substr($str, -1) == ']' ) ? ' ' : '') . "]]>";

	return $str;
}

function wxr_cat_name($c) {
	if ( empty($c->name) )
		return;

	echo '<wp:cat_name>' . wxr_cdata($c->name) . '</wp:cat_name>';
}

function wxr_category_description($c) {
	if ( empty($c->description) )
		return;

	echo '<wp:category_description>' . wxr_cdata($c->description) . '</wp:category_description>';
}

print '<?xml version="1.0" encoding="' . get_bloginfo('charset') . '"?' . ">\n";

?>

<!--
	This is a WordPress eXtended RSS file generated by WordPress as an export of
	your blog. It contains information about your blog's posts, comments, and
	categories. You may use this file to transfer that content from one site to
	another. This file is not intended to serve as a complete backup of your
	blog.

	To import this information into a WordPress blog follow these steps:

	1.	Log into that blog as an administrator.
	2.	Go to Manage > Import in the blog's admin.
	3.	Choose "WordPress" from the list of importers.
	4.	Upload this file using the form provided on that page.
	5.	You will first be asked to map the authors in this export file to users
		on the blog. For each author, you may choose to map an existing user on
		the blog or to create a new user.
	6.	WordPress will then import each of the posts, comments, and categories
		contained in this file onto your blog.
-->

<!-- generator="wordpress/<?php bloginfo_rss('version') ?>" created="<?php echo date('Y-m-d H:i'); ?>"-->
<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:wp="http://wordpress.org/export/1.0/"
>

<channel>
	<title><?php bloginfo_rss('name'); ?></title>
	<link><?php bloginfo_rss('url') ?></link>
	<description><?php bloginfo_rss("description") ?></description>
	<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></pubDate>
	<generator>http://wordpress.org/?v=<?php bloginfo_rss('version'); ?></generator>
	<language><?php echo get_option('rss_language'); ?></language>
<?php if ( $cats ) : foreach ( $cats as $c ) : ?>
	<wp:category><wp:category_nicename><?php echo $c->slug; ?></wp:category_nicename><wp:category_parent><?php echo $c->parent ? $cats[$c->parent]->name : ''; ?></wp:category_parent><?php wxr_cat_name($c); ?><?php wxr_category_description($c); ?></wp:category>
<?php endforeach; endif; ?>
	<?php do_action('rss2_head'); ?>
	<?php if ($post_ids) {
		// fetch 20 posts at a time rather than loading the entire table into memory
		while ( $next_posts = array_splice($post_ids, 0, 20) ) {
			$where = "WHERE ID IN (".join(',', $next_posts).")";
			$posts = $wpdb->get_results("SELECT * FROM $wpdb->posts $where ORDER BY post_date_gmt ASC");
				foreach ($posts as $post) {
			start_wp(); ?>
<item>
<title><?php the_title_rss() ?></title>
<link><?php the_permalink_rss() ?></link>
<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
<dc:creator><?php the_author() ?></dc:creator>
<?php the_category_rss() ?>

<guid isPermaLink="false"><?php the_guid(); ?></guid>
<description></description>
<content:encoded><![CDATA[<?php echo $post->post_content ?>]]></content:encoded>
<wp:post_id><?php echo $post->ID; ?></wp:post_id>
<wp:post_date><?php echo $post->post_date; ?></wp:post_date>
<wp:post_date_gmt><?php echo $post->post_date_gmt; ?></wp:post_date_gmt>
<wp:comment_status><?php echo $post->comment_status; ?></wp:comment_status>
<wp:ping_status><?php echo $post->ping_status; ?></wp:ping_status>
<wp:post_name><?php echo $post->post_name; ?></wp:post_name>
<wp:status><?php echo $post->post_status; ?></wp:status>
<wp:post_parent><?php echo $post->post_parent; ?></wp:post_parent>
<wp:menu_order><?php echo $post->menu_order; ?></wp:menu_order>
<wp:post_type><?php echo $post->post_type; ?></wp:post_type>
<?php
$postmeta = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE post_id = $post->ID");
if ( $postmeta ) {
?>
<?php foreach( $postmeta as $meta ) { ?>
<wp:postmeta>
<wp:meta_key><?php echo $meta->meta_key; ?></wp:meta_key>
<wp:meta_value><?Php echo $meta->meta_value; ?></wp:meta_value>
</wp:postmeta>
<?php } ?>
<?php } ?>
<?php
$comments = $wpdb->get_results("SELECT * FROM $wpdb->comments WHERE comment_post_ID = $post->ID");
if ( $comments ) { foreach ( $comments as $c ) { ?>
<wp:comment>
<wp:comment_id><?php echo $c->comment_ID; ?></wp:comment_id>
<wp:comment_author><?php echo wxr_cdata($c->comment_author); ?></wp:comment_author>
<wp:comment_author_email><?php echo $c->comment_author_email; ?></wp:comment_author_email>
<wp:comment_author_url><?php echo $c->comment_author_url; ?></wp:comment_author_url>
<wp:comment_author_IP><?php echo $c->comment_author_IP; ?></wp:comment_author_IP>
<wp:comment_date><?php echo $c->comment_date; ?></wp:comment_date>
<wp:comment_date_gmt><?php echo $c->comment_date_gmt; ?></wp:comment_date_gmt>
<wp:comment_content><?php echo $c->comment_content; ?></wp:comment_content>
<wp:comment_approved><?php echo $c->comment_approved; ?></wp:comment_approved>
<wp:comment_type><?php echo $c->comment_type; ?></wp:comment_type>
<wp:comment_parent><?php echo $c->comment_parent; ?></wp:comment_parent>
</wp:comment>
<?php } } ?>
	</item>
<?php } } } ?>
</channel>
</rss>
<?php
	die();
}

include ('admin-footer.php');
?>

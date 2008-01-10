<?php
/*
Plugin Name: Collapsible Categories Tree
Plugin URI: http://www.voidpage.com/blog/2007/10/wp-collapsible-cats-plugin.html
Description: 一个可折叠式分类树插件
Version: 0.3 Beta
Author: Wady
Author URI: http://www.voidpage.com
*/

function collapsible_list_cats($args = '') {
	global $wpdb;

	if ( isset($r['hierarchical']) )
		$r['hierarchical'] = true;
	if ( isset($r['style']) )
		$r['style'] = 'list';
	if ( isset($r['child_of']) )
		$r['child_of'] = 0;
		
	$defaults = array(
		'show_option_all' => '', 'orderby' => 'name', 
		'order' => 'ASC', 'show_count' => 0,
		'sum' => 0, 'shrink' => '1',
		'hide_empty' => 1, 'use_desc_for_title' => 1, 
		'child_of' => 0, 'feed' => '', 
		'feed_image' => '', 'exclude' => '', 
		'hierarchical' => true, 'title_li' => __('Categories')
	);

	$r = wp_parse_args( $args, $defaults );

	extract( $r );

	$categories = get_categories($r);
	
	//初始化一些参数
	$sum = 0; //父分类的子分类总日志数
	$branch = 0;   //子分类循环标记
	$branch_num = 0;  //子分类循环次数
	$current_branch = 0; //子分类循环序号
	$cate_num = 0; //总分类数
	$cate_count =0; //分类计数
	$parent_num = 0; //父级分类数
	$parent = 0; //父级分类计数
	$current_parent = 0; //循环中有子分类的父级分类的序号
	$first_parent = true; //判断是否是第一个父级分类
	
	foreach ( $categories as $cate ){ //获取父级分类数、总分类数、子分类循环次数
		if ($cate->parent == 0){
			$parent_num++;
			$branch = 0;
		}else{
			if ($branch == 0){
				$branch = 1;
				$branch_num++;
			}
		}
		$cate_num++;
	}
	
	$branch = 0;  //清空计数
	
	$catTree = "<div id=\"cateTree\">\n";
	
	if (!empty($r['title_li'])){
		$catTree .= "\t<div class=\"tree_title\">".$r['title_li']."</div>\n";
	}
	
	if (!empty($r['show_option_all'])){
		$catTree .= "\t<div class=\"top_text\"><img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-collapsible-cats/images/empty.gif\" class=\"empty_img\" alt=\"".$r['show_option_all']."\" /> <a href=\"".get_bloginfo('url')."\">".$r['show_option_all']."</a></div>\n";
	}
	
	if ($r['shrink'] == 1){
		$classSTR = "";
		$displayTYPE = "none";
	}else{
		$classSTR = "_s";
		$displayTYPE = "block";
	}
	
	foreach ( $categories as $cate ){
		
		if ($cate->parent == 0){
			//如果是父分类
			if ($branch == 1){ //如果之前有子分类
				$catTree .= "\t</div>\n";
				$branch = 0;//标记子分类循环结束
			}
			
			$parent_id['parent'] = $cate->term_id; //查询是否有子分类
			$parent_cats = get_terms('category', $parent_id);
			
			$parent++; //父级分类位置加 1
			
			if (!empty($parent_cats)){
				//如果是有子分类的父级分类
				$current_parent++; //有子分类的父级分类序号加 1
				$div_id = " id=\"parent_".$current_parent."\""; //给有子分类的父分类的 DIV 添加 ID
				$button = "<a href=\"javascript:Show_Child(".$current_parent.")\" onfocus=\"blur()\" class=\"branch_link\" ><img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-collapsible-cats/images/empty.gif\" class=\"empty_img\" alt=\"点击展开/收缩子分类\" /></a> ";
				
				if (empty($first_parent)){  //如果不是第一个父级分类
					$div_class= " class=\"parent".$classSTR."\"";
				}else{
					$div_class = " class=\"parent_first".$classSTR."\"";
					$first_parent = false;
				}
				
				if ($parent == $parent_num){ //如果是最后一个父级分类
					$div_class = " class=\"parent_last".$classSTR."\"";
				}
				
				if ($r['show_count'] == 1 && $r['sum'] == 1){
					$query = "SELECT count FROM $wpdb->term_taxonomy WHERE parent = '$cate->term_id'";
					$counts = $wpdb->get_col($query);

					foreach ( $counts as $count ){
						$sum = $sum + $count;
					}
				}
				
			}else{
				//如果是没有子分类的父级分类
				if (empty($first_parent)){  //如果不是第一个父级分类
					$div_class= " class=\"no_parent\"";
				}else{
					$div_class = " class=\"no_parent_first\"";
					$first_parent = false;
				}
				
				if ($parent == $parent_num){ //如果是最后一个父级分类
					$div_class = " class=\"no_parent_last\"";
				}
				
				$button = "<img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-collapsible-cats/images/empty.gif\" class=\"empty_img\" alt=\"\" /> ";
			}
			
		}else{
			//如果是子分类
			$div_id = "";
			$div_class = " class=\"branch_item\"";
			$button = "<img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-collapsible-cats/images/empty.gif\" class=\"empty_img\" alt=\"\" /> ";
			
			if ($branch == 0){ //如果为子分类循环第一条
				$current_branch++; //子分类循环次数加 1
				
				if ( ($current_branch == $branch_num)&&($parent == $parent_num) ){
					$catTree .= "\t<div id=\"branch_".$current_branch."\" class=\"branch_last\" style=\"display:".$displayTYPE.";\">\n\t";
				}else{
					$catTree .= "\t<div id=\"branch_".$current_branch."\" class=\"branch\" style=\"display:".$displayTYPE.";\">\n\t";
				}
				$branch = 1; //标记已开始子分类循环
			}else{
				$catTree .= "\t"; //为了工整添加的制表符，此行词句均为废物可以无视
			}
		}
		
		$cate_count++;
		if ($cate_count == $cate_num){
			$div_class = " class=\"last_cate\"";
		}
		
		$catTree .= "\t<div".$div_id.$div_class.">".$button."<a href=\"". get_category_link( $cate->term_id ) ."\" title=\"".sprintf(__( 'View all posts filed under %s' ), $cat_name)."\">".$cate->name."</a>";
		
		if ( (!empty($r['feed'])) && (!empty($r['feed_image'])) ){
			$catTree .= " <a href=\"". get_category_rss_link( false, $cate->term_id, $cate->slug ) ."\" title=\"".$r['feed']."\"><img src=\"".$r['feed_image']."\" alt=\"".$r['feed']."\" class=\"feed_img\" /></a>";
		}else if (!empty($r['feed_image'])){
			$catTree .= " <a href=\"". get_category_rss_link( false, $cate->term_id, $cate->slug ) ."\" title=\"".sprintf(__( 'Feed for all posts filed under %s' ), $cat_name )."\"><img src=\"".$r['feed_image']."\" alt=\"".sprintf(__( 'Feed for all posts filed under %s' ), $cate->name )."\" class=\"feed_img\" /></a>";
		}else if (!empty($r['feed'])){
			$catTree .= " [<a href=\"". get_category_rss_link( false, $cate->term_id, $cate->name ) ."\" title=\"".$r['feed']."\">".$r['feed']."</a>]";
		}
		
		if ($r['show_count'] == 1){
			if ($r['sum'] == 1 && $sum > 0){
				$sum = $sum + $cate->count;
				$catTree .= " [".$sum."]";
			}else{
				$catTree .= " [".$cate->count."]";
			}
		}
		
		$sum = 0;
		
		$catTree .= "</div>\n";
	}
	
	$catTree .= "</div>\n";
	
	echo $catTree;
}

function widget_collapsible_cate($args, $number = 1) {
	extract($args);
	$options = get_option('widget_collapsible_cate');

	$shrink = $options[$number]['shrink'] ? '0' : '1';
	$c = $options[$number]['count'] ? '1' : '0';
	$sumC = $options[$number]['sumCount'] ? '1' : '0';

	$title = empty($options[$number]['title']) ? __('Categories') : $options[$number]['title'];

	echo $before_widget;
	echo $before_title . $title . $after_title;

	$cat_args = "orderby=name&show_count={$c}&sum={$sumC}&shrink={$shrink}";
	
	collapsible_list_cats($cat_args . '&title_li=');

	echo $after_widget;
}

function widget_collapsible_cate_control( $number ) {
	$options = $newoptions = get_option('widget_collapsible_cate');

	if ( !is_array( $options ) ) {
		$options = $newoptions = get_option( 'widget_collapsible_cate' );
	}

	if ( $_POST['collapsible-cate-submit-' . $number] ) {
		$newoptions[$number]['shrink'] = isset($_POST['collapsible-cate-shrink-' . $number]);
		$newoptions[$number]['count'] = isset($_POST['collapsible-cate-count-' . $number]);
		$newoptions[$number]['sumCount'] = isset($_POST['collapsible-cate-sumCount-' . $number]);
		$newoptions[$number]['title'] = strip_tags(stripslashes($_POST['collapsible-cate-title-' . $number]));
	}

	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_collapsible_cate', $options);
	}

	$title = attribute_escape( $options[$number]['title'] );
?>
			<p><label for="collapsible-cate-title-<?php echo $number; ?>">
				<?php _e( 'Title:' ); ?> <input style="width:300px" id="collapsible-cate-title-<?php echo $number; ?>" name="collapsible-cate-title-<?php echo $number; ?>" type="text" value="<?php echo $title; ?>" />
			</label></p>
			
			<p><label for="collapsible-cate-count-<?php echo $number; ?>">
				<input type="checkbox" class="checkbox" id="collapsible-cate-shrink-<?php echo $number; ?>" name="collapsible-cate-shrink-<?php echo $number; ?>"<?php echo $options[$number]['shrink'] ? ' checked="checked"' : ''; ?> /> 展开子分类
			</label></p>

			<p><label for="collapsible-cate-count-<?php echo $number; ?>">
				<input type="checkbox" class="checkbox" id="collapsible-cate-count-<?php echo $number; ?>" name="collapsible-cate-count-<?php echo $number; ?>"<?php echo $options[$number]['count'] ? ' checked="checked"' : ''; ?> /> <?php _e( 'Show post counts' ); ?>
			</label></p>

			<p><label for="collapsible-cate-count-<?php echo $number; ?>">
				<input type="checkbox" class="checkbox" id="collapsible-cate-sumCount-<?php echo $number; ?>" name="collapsible-cate-sumCount-<?php echo $number; ?>"<?php echo $options[$number]['sumCount'] ? ' checked="checked"' : ''; ?> /> 将子分类的日志数加到父分类日志数中
			</label></p>

			<input type="hidden" id="collapsible-cate-submit-<?php echo $number; ?>" name="collapsible-cate-submit-<?php echo $number; ?>" value="1" />
<?php
}

function widget_collapsible_cate_setup() {
	$options = $newoptions = get_option( 'widget_collapsible_cate' );

	if ( isset( $_POST['collapsible-cate-number-submit'] ) ) {
		$number = (int) $_POST['collapsible-cate-number'];

		if ( $number > 9 ) {
			$number = 9;
		} elseif ( $number < 1 ) {
			$number = 1;
		}

		$newoptions['number'] = $number;
	}

	if ( $newoptions != $options ) {
		$options = $newoptions;
		update_option( 'widget_collapsible_cate', $options );
		widget_collapsible_cate_register( $options['number'] );
	}
}

function widget_collapsible_cate_page() {
	$options = get_option( 'widget_collapsible_cate' );
?>
	<div class="wrap">
		<form method="post">
			<h2>树形分类 <?php _e( 'Widgets' ); ?></h2>
			<p style="line-height: 30px;"><?php _e( 'How many categories widgets would you like?' ); ?>
				<select id="collapsible-cate-number" name="collapsible-cate-number" value="<?php echo attribute_escape( $options['number'] ); ?>">
					<?php
						for ( $i = 1; $i < 10; $i++ ) {
							echo '<option value="' . $i . '"' . ( $i == $options['number'] ? ' selected="selected"' : '' ) . '>' . $i . "</option>\n";
						}
					?>
				</select>
				<span class="submit">
					<input type="submit" value="<?php echo attribute_escape( __( 'Save' ) ); ?>" id="collapsible-cate-number-submit" name="collapsible-cate-number-submit" />
				</span>
			</p>
		</form>
	</div>
<?php
}

function widget_collapsible_cate_upgrade() {
	$options = get_option( 'widget_collapsible_cate' );

	$newoptions = array( 'number' => 1, 1 => $options );

	update_option( 'widget_collapsible_cate', $newoptions );

	$sidebars_widgets = get_option( 'sidebars_widgets' );
	if ( is_array( $sidebars_widgets ) ) {
		foreach ( $sidebars_widgets as $sidebar => $widgets ) {
			if ( is_array( $widgets ) ) {
				foreach ( $widgets as $widget )
					$new_widgets[$sidebar][] = ( $widget == 'collapsible-cate' ) ? 'collapsible-cate-1' : $widget;
			} else {
				$new_widgets[$sidebar] = $widgets;
			}
		}
		if ( $new_widgets != $sidebars_widgets )
			update_option( 'sidebars_widgets', $new_widgets );
	}

	if ( isset( $_POST['collapsible-cate-submit'] ) ) {
		$_POST['collapsible-cate-submit-1'] = $_POST['collapsible-cate-submit'];
		$_POST['collapsible-cate-count-1'] = $_POST['categories-count'];
		$_POST['collapsible-cate-title-1'] = $_POST['collapsible-cate-title'];
		foreach ( $_POST as $k => $v )
			if ( substr($k, -5) == 'order' )
				$_POST[$k] = str_replace('collapsible-cate', 'collapsible-cate-1', $v);
	}

	return $newoptions;
}

function widget_collapsible_cate_register() {
	$options = get_option( 'widget_collapsible_cate' );
	if ( !isset($options['number']) )
		$options = widget_collapsible_cate_upgrade();
	$number = (int) $options['number'];

	if ( $number > 9 ) {
		$number = 9;
	} elseif ( $number < 1 ) {
		$number = 1;
	}

	$dims = array( 'width' => 350, 'height' => 160 );
	$class = array( 'classname' => 'widget_collapsible_cate' );

	for ( $i = 1; $i <= 9; $i++ ) {
		$name = '树形分类' . $i;
		$id = 'collapsible-cate-' . $i;

		$widget_callback = ( $i <= $number ) ? 'widget_collapsible_cate' : '';
		$control_callback = ( $i <= $number ) ? 'widget_collapsible_cate_control' : '';

		wp_register_sidebar_widget( $id, $name, $widget_callback, $class, $i );
		wp_register_widget_control( $id, $name, $control_callback, $dims, $i );
	}

	add_action( 'sidebar_admin_setup', 'widget_collapsible_cate_setup' );
	add_action( 'sidebar_admin_page', 'widget_collapsible_cate_page' );
}

function collapsible_cats_init(){
	widget_collapsible_cate_register();
}

function collapsible_cats_script() {
	echo "<link rel=\"stylesheet\" href=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-collapsible-cats/wp-collapsible-cats.css\" type=\"text/css\" media=\"screen\" />\n";
	echo "<script language=\"text/javascript\" type=\"text/javascript\" src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-collapsible-cats/wp-collapsible-cats.js\"></script>\n";
}

add_action('wp_head', 'collapsible_cats_script');
add_action('widgets_init', 'collapsible_cats_init', 5);
?>
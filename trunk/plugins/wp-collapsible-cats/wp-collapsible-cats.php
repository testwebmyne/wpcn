<?php
/*
Plugin Name: Collapsible Categories Tree
Plugin URI: http://www.voidpage.com/2007/09/wp-collapsible-cats-plugin.html
Description: 一个可折叠式分类树
Version: 0.1 Beta
Author: Wady
Author URI: http://www.voidpage.com
*/

function collapsible_list_cats($args = '') {
	$r = wp_parse_args( $args );

	// Map to new names.
	if ( isset($r['optionall']) && isset($r['all']))
		$r['show_option_all'] = $r['all'];
	if ( isset($r['sort_column']) )
		$r['orderby'] = $r['sort_column'];
	if ( isset($r['sort_order']) )
		$r['order'] = $r['sort_order'];
	if ( isset($r['optiondates']) )
		$r['show_last_update'] = $r['optiondates'];
	if ( isset($r['optioncount']) )
		$r['show_count'] = $r['optioncount'];
	if ( isset($r['list']) )
		$r['style'] = $r['list'] ? 'list' : 'break';
	$r['title_li'] = '';

	return collapsible_list_categories($r);
}

function collapsible_list_categories($args = '') {
	$defaults = array(
		'show_option_all' => '', 'orderby' => 'name', 
		'order' => 'ASC', 'show_last_update' => 0, 
		'style' => 'list', 'show_count' => 0, 
		'hide_empty' => 1, 'use_desc_for_title' => 1, 
		'child_of' => 0, 'feed' => '', 
		'feed_image' => '', 'exclude' => '', 
		'hierarchical' => true, 'title_li' => __('Categories')
	);

	$r = wp_parse_args( $args, $defaults );

	if ( !isset( $r['pad_counts'] ) && $r['show_count'] && $r['hierarchical'] ) {
		$r['pad_counts'] = true;
	}

	if ( isset( $r['show_date'] ) ) {
		$r['include_last_update_time'] = $r['show_date'];
	}

	extract( $r );

	$categories = get_categories($r);
	
	//初始化一些参数；
	$branch = 0;   //子分类循环标记
	$branch_num = 0;  //子分类循环次数
	$current_branch = 0; //子分类循环序号
	$cate_num = 0; //总分类数；
	$parent_num = 0; //父级分类数；
	$parent = 0; //父级分类计数
	$current_parent = 0; //循环中有子分类的父级分类的序号
	$first_parent = true; //判断是否是第一个父级分类
	
	foreach ( $categories as $cate ){ //获取父级分类数、总分类数、子分类循环次数
		if ($cate->parent == 0){
			$parent_num++;
		}else{
			if ($branch == 0){
				$branch_num++;
			}
		}
		$cate_num++;
	}
	
	$catTree = "<div id=\"cateTree\">\n";
	
	foreach ( $categories as $cate ){
		
		if ($cate->parent == 0){ //如果是父分类
			if ($branch == 1){ //如果之前有子分类
				$catTree .= "\t</div>\n";
				$branch = 0;//标记子分类循环结束
			}
			
			$parent_id['parent'] = $cate->term_id; //查询是否有子分类
			$parent_cats = get_terms('category', $parent_id);
			
			$parent++; //父级分类位置加 1
			
			if (!empty($parent_cats)){
				$current_parent++; //有子分类的父级分类序号加 1
				$div_id = " id=\"parent_".$current_parent."\""; //给有子分类的父分类的 DIV 添加 ID
				$button = "<a href=\"javascript:Show_Child(".$current_parent.")\" onfocus=\"blur()\"><img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-collapsible-cats/images/empty.gif\" /></a> ";
				
				if (empty($first_parent)){  //如果不是第一个父级分类
					$div_class= " class=\"parent\"";
				}else{
					$div_class = " class=\"parent_first\"";
					$first_parent = false;
				}
				
				if ($parent == $parent_num){ //如果是最后一个父级分类
					$div_class = " class=\"parent_last\"";
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
				
				$button = "<img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-collapsible-cats/images/empty.gif\" /> ";
			}
		}else{
			$div_id = "";
			$div_class = " class=\"branch_item\"";
			$button = "<img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-collapsible-cats/images/empty.gif\" /> ";
			
			if ($branch == 0){ //如果为子分类循环第一条
				$current_branch++; //子分类循环次数加 1
				$catTree .= "\t<div id=\"branch_".$current_branch."\" class=\"branch\" style=\"display:none;\">\n\t";
				$branch = 1; //标记已开始子分类循环
			}else{
				$catTree .= "\t";
			}
		}
		
		$catTree .= "\t<div".$div_id.$div_class.">".$button."<a href=\"". get_category_link( $cate->term_id ) ."\" title=\"". sprintf(__( 'View all posts filed under %s' ), $cate->name) ."\">".$cate->name."</a>";
		
		if ($r['show_count'] == 1){
			$catTree .= " [".$cate->count."]";
		}
		
		$catTree .= "</div>\n";
	}
	
	$catTree .= "</div>\n";
	
	echo $catTree;
}

function collapsible_cats_script() {
	echo "<link rel='stylesheet' href='".get_bloginfo('wpurl')."/wp-content/plugins/wp-collapsible-cats/wp-collapsible-cats.css' type='text/css' media='screen' />\n";
	echo "<script language='text/javascript' type='text/javascript' src='".get_bloginfo('wpurl')."/wp-content/plugins/wp-collapsible-cats/wp-collapsible-cats.js.'></script>\n";
}

add_action('wp_head', 'collapsible_cats_script');
?>
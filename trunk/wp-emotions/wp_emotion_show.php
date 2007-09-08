<?php
/*
Plugin Name: WordPress Emotions Show
Version: 1.2
Plugin URI: http://fairyfish.net/
Description: Make the specific words to picture when comments. also works on Content.
Author: Denis Deng
Author URI: http://fairyfish.net/
*/

$convert_content_keywords = 1;

function compare($image_x,$image_y){
	if($image_x["len"] == $image_y["len"])
		return 0;
	elseif($image_x["len"] < $image_y["len"])
		return 1;
	else
		return -1;
}

function comments_emotion($comment_text ="") {	
	$emotion_url =  get_settings('siteurl'). "/wp-content/emotions/";
	$emotion_dir = ABSPATH . "wp-content/emotions";	
	$images = array();
	
	if ($handle=opendir($emotion_dir))
	{
		while (false !== ($file = readdir($handle))) 
		{
			if ($file != "." && $file != ".." ) 
			{				
				$img_url = rawurlencode($file);

				$encoding_list="EUC-CN, UTF-8";
				if (mb_detect_encoding($file,$encoding_list)!="UTF-8"){					
					$file=iconv(mb_detect_encoding($file,$encoding_list), "UTF-8", $file);
				}
				
				if(($WINDIR = $_SERVER['WINDIR']) == "") {				
					$WINDIR = $HTTP_SERVER_VARS["WINDIR"];				
				}
				
				if(($OS = $_ENV["OS"]) == "") {				
					$OS = $HTTP_ENV_VARS["OS"];		
				}
				
				if($WINDIR<>"" && preg_match("/win/i", $OS)){
					$img_url = $file; //hack for win server
				}	
				
				$ext_len = (strrpos($file,".")) ? strlen(substr($file,strrpos($file,".")+1)) : 0;
				$img_name = substr($file,0,strlen($file)- $ext_len - 1);
				$img_alt = '<img src="'.$emotion_url.$img_url . '" alt="'. $img_name . '" />';
				$img_url = "fix_bug_prefix".rawurlencode($file);
				$img_len = strlen($img_name);
				
				$image = array(
					'name' => $img_name,
					'url' => $img_url,
					'alt' => $img_alt,
					'len' => $img_len
				);
					
				array_push($images, $image);	
			}
		}
		
		usort($images,"compare");
		
		$images_name = array();
		$images_url = array();
		$images_url_2 = array();
		$images_alt = array();
		
		while (list($key,$image) = each ($images)) {
			array_push($images_name, "/".$image["name"]."/");
			array_push($images_url, $image["url"]);
			array_push($images_url_2, "/".$image["url"]."/");
			array_push($images_alt, $image["alt"]);
		}
		
		$comment_text = preg_replace($images_name,$images_url,$comment_text );
		$comment_text = preg_replace($images_url_2,$images_alt,$comment_text );
	
		closedir($handle);
	}	
	
	return $comment_text;
}

add_filter('comment_text', 'comments_emotion',99);

if($convert_content_keywords){
	add_filter('the_content', 'comments_emotion',99);
}
?>
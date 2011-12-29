<?php
/*
Plugin Name: All in One Video Pack Sidebar Widget
Plugin URI: http://www.kaltura.org/
Description: A sidebar widget that allows you to display the most recent posted videos and comments in your blog.  
Version: 99.99(DEV)
Author: Kaltura
Author URI: http://kaltura.org/
*/
define('KALTURA_ROOT', dirname(__FILE__));

require_once(KALTURA_ROOT.'/settings.php');
require_once(KALTURA_ROOT.'/lib/kaltura_client.php');
require_once(KALTURA_ROOT.'/lib/kaltura_helpers.php');


class AllInOneVideoWidget 
{
	function AllInOneVideoWidget() 
	{
		// load only if the main plugin is loadeded
		if (defined("KALTURA_PLUGIN_FILE")) 
		{
			add_action("widgets_init", array(&$this, "registerWidget"));
			add_action("widgets_init", array(&$this, "registerWidget"));
			add_action("widgets_init", array(&$this, "registerWidget"));
			add_action("wp_ajax_nopriv_kaltura_get_video_comments", array(&$this, "getVideoComments"));
			add_action("wp_ajax_nopriv_kaltura_get_video_posts", array(&$this, "getVideoPosts"));
		}
		else
		{
			$msg = __("Please activate \"All in One Video Pack\" before using the sidebar widget");
			$notice = '<div class="updated fade"><p><strong>'.$msg.'</strong></p></div>';
			add_action('admin_notices', create_function("", 'echo \''.$notice.'\';'));
		}
	}
	
	function registerWidget() 
	{
		$description = "The most recent posted videos and comments in your blog";
		$options = array("classname" => "widget_text", "description" => $description);
		$id = "all-in-one-video-pack-widget";
		$name = "Recent Videos Widget";
		wp_register_sidebar_widget($id, $name, array(&$this, 'displayWidget'), $options);
	}
	
	function displayWidget($args)
	{
		extract($args);

        echo $before_widget;
        echo $before_title;
        echo 'Recent Videos';
        echo $after_title;
        echo '<div id="kaltura-sidebar-menu">' . "\n";
	    echo '<a id="kaltura-posts-button" onclick="Kaltura.switchSidebarTab(this, \'posts\');">'.__("Posted Videos").'</a> | ' . "\n";
	    echo '<a id="kaltura-comments-button" onclick="Kaltura.switchSidebarTab(this, \'comments\');">'.__("Video Comments").'</a>' . "\n";
        echo '</div>' . "\n";
        
        echo '<div id="kaltura-loader"><img src="'.KalturaHelpers::getPluginUrl().'/images/loader.gif" alt="Loading..." /></div>' . "\n";
        echo '<div id="kaltura-sidebar-container"></div>' . "\n";
        echo '<script type="text/javascript">' . "\n";
        echo 'jQuery("#kaltura-posts-button").click()' . "\n";
        echo 'var kaltura_loader = new SWFObject("'.KalturaHelpers::getPluginUrl().'/images/loader.swf", "kaltura-loader-swf", 35, 35, "9", "#000000");' . "\n";
        echo 'kaltura_loader.addParam("wmode", "transparent");' . "\n";
        echo 'kaltura_loader.write("kaltura-loader");' . "\n";
        echo '</script>' . "\n";
        
        echo $after_widget;
	}
	
	function getVideoComments()
	{
		$page_size = 5;
	
		$page = (integer)(@$_GET["page"]);
		if ($page < 1)
			$page = 1;
			
		$widgets = KalturaWPModel::getLastPublishedCommentWidgets($page, $page_size);
		$total_count = KalturaWPModel::getLastPublishedCommentWidgetsCount();
	
		if ($page * $page_size >= $total_count)
			$last_page = true;
			
		if ($page == 1)
			$first_page = true;
			
		echo '<div id="kaltura-video-comments">';
		if ($widgets) 
		{
			echo '<ul id="kaltura-items">';
			foreach($widgets as $widget)
			{
				$post_id = $widget["post_id"];
				$comment_id = $widget["comment_id"];
				$post = &get_post($post_id);
				$comment = &get_comment($comment_id);
				echo '<li>';
				echo '<div class="thumb">';
				echo '<a href="'.get_permalink($post_id).'#comment-'.$comment_id.'">';
				echo '<img src="'.KalturaHelpers::getThumbnailUrl($widget["id"], $widget["entry_id"], 120, 90, null).'" width="120" height="90" />';
				echo '</a>';
				echo '</div>';
				echo 'Reply to <a href="'.get_permalink($post_id).'">'.$post->post_title.'</a><br />';
				echo $comment->comment_author . ", " . mysql2date("M j", $comment->comment_date);
				echo '</li>';
			}
			echo '</ul>';
			
			echo '<ul class="kaltura-sidebar-pager">';
			echo '	<li>';
			if (!$first_page)
				echo '<a onclick="Kaltura.switchSidebarTab(this, \'comments\','.($page - 1).');">Newer</a>';
			else
				echo '&nbsp;';
			echo '	</li>';
			echo '	<li>';
			if (!$last_page)
				echo '<a onclick="Kaltura.switchSidebarTab(this, \'comments\','.($page + 1).');">Older</a>';
			else
				echo '&nbsp;';
			echo '	</li>';
			echo '</ul>';
		}
		else
		{
			echo 'No video comments yet';
		}
		echo '</div>';
	}
	
	function getVideoPosts() 
	{
		$page_size = 5;
	
		$page = (integer)(@$_GET["page"]);
		if ($page < 1)
			$page = 1;
			
		$widgets = KalturaWPModel::getLastPublishedPostWidgets($page, $page_size);
		$total_count = KalturaWPModel::getLastPublishedPostWidgetsCount();
	
		if ($page * $page_size >= $total_count)
			$last_page = true;
			
		if ($page == 1)
			$first_page = true;
		
		echo '<div id="kaltura-video-posts">';
		if ($widgets) 
		{
			echo '<ul id="kaltura-items">';
			foreach($widgets as $widget)
			{
				$post_id = $widget["post_id"];
				$post = &get_post($post_id);
				$user = get_userdata($post->post_author);
				echo '<li>';
				echo '<div class="thumb">';
				echo '<a href="'.get_permalink($post_id).'">';
				echo '<img src="'.KalturaHelpers::getThumbnailUrl($widget["id"], $widget["entry_id"], 120, 90, null).'" width="120" height="90" />';
				echo '</a>';
				echo '</div>';
				echo '<a href="'.get_permalink($post_id).'">'.$post->post_title.'</a><br />';
				echo $user->display_name . ", " . mysql2date("M j", $widget["created_at"]);
				echo '</li>';
			}
			echo '</ul>';
			
			echo '<ul class="kaltura-sidebar-pager">';
			echo '	<li>';
			if (!$first_page)
				echo '<a onclick="Kaltura.switchSidebarTab(this, \'posts\','.($page - 1).');">Newer</a>';
			else
				echo '&nbsp;';
			echo '	</li>';
			echo '	<li>';
			if (!$last_page)
				echo '<a onclick="Kaltura.switchSidebarTab(this, \'posts\','.($page + 1).');">Older</a>';
			else
				echo '&nbsp;';
			echo '	</li>';
			echo '</ul>';
		}
		else
		{
			echo 'No posted videos yet';
		}
		echo '</div>';
	}
}

// initialize the plugin after all plugins are loaded because we depend on our main plugin
add_action("plugins_loaded", create_function("", '$allInOneVideoWidget = new AllInOneVideoWidget();'));

?>
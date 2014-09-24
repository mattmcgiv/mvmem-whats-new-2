<?php

/**
 * Plugin Name: MVMEM Whats New
 * Description: Each user sees new comments and pages since the last time he/she visited.
 * Version: 0.1
 * Author: Matt McGivney
 * Author URI: http://antym.com
 * Stable tag: 0.1
 * Tested up to: 4.0
 * License: GPL2
 */
 
 /*  Copyright 2014 Matt McGivney  (email : matt@antym.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/



global $mvmem_whats_new_db_version;
$mvmem_whats_new_db_version = '0.1';

//Register actions
register_activation_hook( __FILE__, 'mvmem_whats_new_db_install' );
add_action('publish_page','mvmem_whats_new_publish_post');
add_action('wp_head','mvmem_whats_new_mark_post_read');
add_action('wp_head','mvmem_whats_new_mark_comment_read');
add_action('wp_insert_comment', 'mvmem_whats_new_post_comment',99,2);
add_shortcode('mvmem-whats-new', 'mvmem_display_whats_new');

function mvmem_whats_new_db_install() {
	global $wpdb;
	global $mvmem_whats_new_db_version;

	$table_name = $wpdb->prefix . 'mvmem_whats_new_posts';
	$table_name_comments = $wpdb->prefix . 'mvmem_whats_new_comments';
	
	/*
	 * Set charset and collate
	 */
	$charset_collate = '';

	if ( ! empty( $wpdb->charset ) ) {
	  $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
	}

	if ( ! empty( $wpdb->collate ) ) {
	  $charset_collate .= " COLLATE {$wpdb->collate}";
	}

	if( $wpdb->get_var("SHOW TABLES LIKE '" . $table_name . "'") === $table_name ) {
	 // The database table exists, do nothing
	 //var_dump($table_name); die();
	} else {
	 // Table does not exist, so create it
	 	$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		userid bigint(20) NOT NULL,		
		postid bigint(20) NOT NULL,
		UNIQUE KEY id (id)
		) $charset_collate;";
	}
	
	
	
	if( $wpdb->get_var("SHOW TABLES LIKE '" . $table_name_comments . "'") === $table_name_comments ) {
	 // The database table exists, do nothing
	} else {
	 // Table does not exist, so create it
	 	$sql = "CREATE TABLE $table_name_comments (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		userid bigint(20) NOT NULL,		
		commentid bigint(20) NOT NULL,
		UNIQUE KEY id (id)
		) $charset_collate;";
	}

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql);

	add_option( 'mvmem_whats_new_db_version', $mvmem_whats_new_db_version );
}

//When a new post is published, add it to each user's list
function mvmem_whats_new_publish_post() {
	//if there are users
	//Get all users
	global $wpdb;
	$table_name = $wpdb->prefix . 'mvmem_whats_new_posts';
	$mvmem_all_users = mvmem_whats_new_get_users();
	
	//Get postID
	//get the post object
	$mvmem_current_post = get_post();
	
	//TODO: if it is just an update, as opposed to a new post, ignore it

	
	
	$mvmem_current_post_ID = $mvmem_current_post->ID;
	
	foreach ($mvmem_all_users as $user) {
		//get the user ID from the array and save it to a variable
		$array = (array) $user;
		$mvmem_user_ID = $array['ID'];
		//$query="SELECT * FROM $table_name WHERE userid='$mvmem_user_ID' AND postid='$mvmem_current_post_ID'";
		
		
		
		//Prevent update entries with this check		
		if (($mvmem_current_post->post_date) === ($mvmem_current_post->post_modified)) {
			var_dump($mvmem_current_post->post_date);
			var_dump($mvmem_current_post->post_modified);
			//die();
			//call func to write to db
			mvmem_enqueue_whats_new($mvmem_user_ID, $mvmem_current_post_ID);
		}		
	}
	
}

//When a new comment is posted, add it to each user's list
function mvmem_whats_new_post_comment($comment_id, $comment_object) {
	//if there are users
	//Get all users
	$mvmem_all_users = mvmem_whats_new_get_users();
	//Get commentID
	//TODO: if it is just an update, as opposed to a new post, ignore it
	//get the comment object
	$mvmem_current_comment_ID = $comment_id;
	
	foreach ($mvmem_all_users as $user) {
		//get the user ID from the array and save it to a variable
		$array = (array) $user;
		$mvmem_user_ID = $array['ID'];
		
		//call func to write to db
		mvmem_enqueue_whats_new_comment($mvmem_user_ID, $mvmem_current_comment_ID);
		
	}
	
}

function mvmem_whats_new_get_users() {
 	$mvmem_users = get_users(array('fields'=>array('ID')));	
	return $mvmem_users;
}

function mvmem_enqueue_whats_new ($userID, $postID) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'mvmem_whats_new_posts';
	
	//TODO:if the table exists
	//For each user
		//add the userid and postid as a row to the $table_name = $wpdb->prefix . 'mvmem_whats_new_posts';
	
	$wpdb->insert( 
		$table_name, 
		array( 
			'userid' => $userID, 
			'postid' => $postID,
		) 
	);
}

function mvmem_enqueue_whats_new_comment ($userID, $commentID) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'mvmem_whats_new_comments';
	
	//TODO:if the table exists
	//For each user
		//add the userid and commentid as a row to the $table_name = $wpdb->prefix . 'mvmem_whats_new_comments';
	
	$wpdb->insert( 
		$table_name, 
		array( 
			'userid' => $userID, 
			'commentid' => $commentID,
		) 
	);
}

function mvmem_whats_new_mark_post_read() {
	global $wpdb;
	$mvmem_whats_new_current_user = get_current_user_id();
	$mvmem_whats_new_postid = url_to_postid( get_permalink() );
	
	$table_name = $wpdb->prefix . 'mvmem_whats_new_posts';
	
	//TODO:check if table exists before trying to delete from it
	//remove entry for this user and page
	$wpdb->query( 
	$wpdb->prepare( 
		"
         DELETE FROM $table_name
		 WHERE userid = %d
		 AND postid = %d
		",
	        $mvmem_whats_new_current_user, $mvmem_whats_new_postid 
        )
	);
	//var_dump($mvmem_whats_new_current_user . " viewed " . $mvmem_whats_new_postid); die();
	
	
}

function mvmem_whats_new_mark_comment_read() {
	global $wpdb;
	global $post;
	
	$mvmem_whats_new_current_user = get_current_user_id();
	//var_dump($mvmem_whats_new_current_user); die();
	
	$mvmem_whats_new_post_ID=$post->ID;
	//var_dump($mvmem_whats_new_post_ID); die();
	
	//prepare an array with current postID
	$mvmem_args = array(
		'post_id' => $mvmem_whats_new_post_ID,
	);
	
	//get comments for the current post id
	//$mvmem_whats_new_comments_this_page=get_comments($mvmem_args);
	$mvmem_comments_this_page=$wpdb->get_results( 
		$wpdb->prepare( 
			"
	        SELECT comment_ID FROM wp_comments
			WHERE comment_post_ID = %d
			",
			$mvmem_whats_new_post_ID
	    )
	);
	
	//var_dump($mvmem_comments_this_page); die();
	$table_name = $wpdb->prefix . 'mvmem_whats_new_comments';
	
	foreach ($mvmem_comments_this_page as $comment) {
		$array = (array) $comment;
		$mvmem_comment_ID = intval($array['comment_ID']);
		//var_dump($mvmem_comment_ID); die();
		//TODO:check if table exists before trying to delete from it
		//remove entry for this user and page
		$wpdb->query( 
		$wpdb->prepare( 
			"
	         DELETE FROM $table_name
			 WHERE userid = %d
			 AND commentid = %d
			",
		        $mvmem_whats_new_current_user, $mvmem_comment_ID 
	        )
		);
	}	
}



function mvmem_display_whats_new() {
    global $wpdb;
	$mvmem_whats_new_current_user = get_current_user_id();
				

	$mvmem_page_ids=$wpdb->get_results( 
		$wpdb->prepare( 
			"
	        SELECT postid FROM wp_mvmem_whats_new_posts
			WHERE userid = %d
			",
			$mvmem_whats_new_current_user
	    )
	);			
			
	echo '<h3>New Pages</h3>';
	foreach ($mvmem_page_ids as $page_id) {
		$mvmem_page_ID = $page_id->postid;
		
		
		echo '<a href="' . get_permalink($mvmem_page_ID) . '">' . get_the_title($mvmem_page_ID) . '</a>'; 
		
		echo "<br><hr>";

	}
	
	$mvmem_comment_ids=$wpdb->get_results(
		$wpdb->prepare(
			"
			SELECT commentid FROM wp_mvmem_whats_new_comments
			WHERE userid =%d
			",
			$mvmem_whats_new_current_user
		)
	);
	//var_dump($mvmem_comment_ids); die();
	echo '<h3>New Comments</h3>';
	foreach ($mvmem_comment_ids as $comment_id) {
		
		$mvmem_comment_ID = intval($comment_id->commentid);
		//$mvmem_comment_ID = $comment_id->commentid;
		//var_dump($mvmem_comment_ID);die;
		
		//$args=array('ID'=>$mvmem_comment_ID);
		//$var=get_comments('ID='.$mvmem_comment_ID);
		$comment = get_comment( $mvmem_comment_ID );

 
		$mvmem_post_ID=$comment->comment_post_ID;
		//var_dump($mvmem_post_ID); die();
		
		
		echo get_comment_author($mvmem_comment_ID).' - <a href="'.get_permalink($mvmem_post_ID).'">"' . get_comment_text($mvmem_comment_ID) . '"</a><br>On page titled "' . get_the_title($mvmem_post_ID) . '"'; 
		
		echo "<br><hr>";
	}
}



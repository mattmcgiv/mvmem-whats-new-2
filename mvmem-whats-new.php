<?php

/**
 * Plugin Name: MVMEM Whats New
 * Description: Each user sees new comments and posts since the last time he/she visited.
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

function mvmem_whats_new_db_install() {//TODO: add similar comments table
	global $wpdb;
	global $mvmem_whats_new_db_version;

	$table_name = $wpdb->prefix . 'mvmem_whats_new_posts';
	
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
	} else {
	 // Table does not exist, so create it
	 	$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		userid bigint(20) NOT NULL,		
		postid bigint(20) NOT NULL,
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
	$mvmem_all_users = mvmem_whats_new_get_users();
	
	//Get postID
	//TODO: if it is just an update, as opposed to a new post, ignore it
	//get the post object
	$mvmem_current_post = get_post();
	
	$mvmem_current_post_ID = $mvmem_current_post->ID;
	
	foreach ($mvmem_all_users as $user) {
		//get the user ID from the array and save it to a variable
		$array = (array) $user;
		$mvmem_user_ID = $array['ID'];
		
		mvmem_enqueue_whats_new($mvmem_user_ID, $mvmem_current_post_ID);
		//add the post id to the database
		
	}
	//if the table exists
	//For each user
		//add the userid and postid as a row to the $table_name = $wpdb->prefix . 'mvmem_whats_new_posts';
}

function mvmem_whats_new_get_users() {
 	$mvmem_users = get_users(array('fields'=>array('ID')));
	
	return $mvmem_users;
}

function mvmem_enqueue_whats_new ($userID, $postID) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'mvmem_whats_new_posts';
	
	$wpdb->insert( 
		$table_name, 
		array( 
			'userid' => $userID, 
			'postid' => $postID,
		) 
	);
}


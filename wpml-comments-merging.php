<?php
/*
Plugin Name: WPML Merged Comments
Plugin URI: https://rickypoon.ca/
Description: WPML: Show Reviews/Comments in All Languages
Version: 2.0
Author: Ricky Poon
Author URI: https://rickypoon.ca/
License: GPL

This is a fixed version of the no longer maintained WPML Comment Merging plugin:
http://wordpress.org/extend/plugins/wpml-comment-merging/
Thanks to Simon Wheatley for contributing the fix.

Forked from: https://github.com/jgalea/wpml-comments-merging
*/
global $sitepress;
function sort_merged_comments($a, $b) { 
	return $a->comment_ID - $b->comment_ID;
}
function merge_comments($comments, $post_ID) {
	// get all the languages for which this post exists
	$languages = icl_get_languages('skip_missing=1');
	$post = get_post( $post_ID );
	$type = $post->post_type;
	foreach($languages as $l) {
		// in $comments are already the comments from the current language
		if(!$l['active']) {
			$otherID = icl_object_id($post_ID, $type, false, $l['language_code']);
			//$othercomments = get_comments($otherID);
			$othercomments = get_approved_comments($otherID);
			$comments = array_merge($comments, $othercomments);
		}
	}
	if ($languages) {
		// if we merged some comments in we need to reestablish an order
		usort($comments, 'sort_merged_comments');
	}
	return $comments;
}
function merge_comment_count($count, $post_ID) {
	// get all the languages for which this post exists
	$languages = icl_get_languages('skip_missing=1');
	$post = get_post( $post_ID );
	$type = $post->post_type;
	foreach($languages as $l) {
		// in $count is already the count from the current language
		if(!$l['active']) {
			$otherID = icl_object_id($post_ID, $type, false, $l['language_code']);
			if($otherID) {
				// cannot use call_user_func due to php regressions
				if ($type == 'page') {
					$otherpost = get_page($otherID);
				} else {
					$otherpost = get_post($otherID);
				}
				if ($otherpost) {
					// increment comment count using translation post comment count.
					$count = $count + $otherpost->comment_count;
				}
			}
		}
	}
	return $count;
}
remove_filter('comments_clauses', array( $sitepress, 'comments_clauses' ), 10, 2);
add_filter('comments_array', 'merge_comments', 100, 2);
add_filter('get_comments_number', 'merge_comment_count', 100, 2);

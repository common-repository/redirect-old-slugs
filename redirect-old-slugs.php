<?php
/*
Plugin Name: Redirect Old Slugs
Plugin URI: http://txfx.net/code/wordpress/redirect-old-slugs/
Description: Allows you to change your post slugs without breaking the old ones (which will redirect to the new one!)
Version: 0.3
Author: Mark Jaquith
Author URI: http://txfx.net/
*/

/*  Copyright 2005  Mark Jaquith (email: mark.gpl@txfx.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


function txfx_old_slug_redirect () {
	global $wp_query;
	if ( is_404() && '' != $wp_query->query_vars['name'] ) :
		global $wpdb;

		$query = "SELECT post_id FROM $wpdb->postmeta, $wpdb->posts WHERE ID = post_id AND meta_key = 'old_slug' AND meta_value='" . $wp_query->query_vars['name'] . "'";

		// if year, monthnum, or day have been specified, make are query more precise
		// just in case there are multiple identical old_slug values
		if ( '' != $wp_query->query_vars['year'] )
			$query .= " AND YEAR(post_date) = '{$wp_query->query_vars['year']}'";
		if ( '' != $wp_query->query_vars['monthnum'] )
			$query .= " AND MONTH(post_date) = '{$wp_query->query_vars['monthnum']}'";
		if ( '' != $wp_query->query_vars['day'] )
			$query .= " AND DAYOFMONTH(post_date) = '{$wp_query->query_vars['day']}'";

		$id = $wpdb->get_var($query);

		if ( !$id )
			return;

		$link = get_permalink($id);

		if ( !$link )
			return;

		header("HTTP/1.0 301 Moved Permanently");
		header("Status: 301 Moved Permanently");
		header("Location: $link");
		exit;
	endif;
}

function txfx_check_for_changed_slugs ($post_id) {
	if ( !strlen($_POST['txfx-old-slug']) )
		return $post_id;

	$post = &get_post($post_id);

	// we're only concerned with published posts and pages
	if ( $post->post_status != 'publish' && $post->post_status != 'static' )
		return $post_id;

	// only bother if the slug has changed
	if ( $post->post_name == $_POST['txfx-old-slug'] )
		return $post_id;

	$old_slugs = get_post_meta($post_id, 'old_slug');

	// if we haven't added this old slug before, add it now
	if ( !count($old_slugs) || !in_array($_POST['txfx-old-slug'], $old_slugs) )
		add_post_meta($post_id, 'old_slug', $_POST['txfx-old-slug']);

	// if the new slug was used previously, delete it from the list
	if ( in_array($post->post_name, $old_slugs) )
		delete_post_meta($post_id, 'old_slug', $post->post_name);

	return $post_id;
}

function txfx_remember_old_slug () {
	global $post, $post_name;
	$name = (strlen($post_name)) ? $post_name : $post->post_name;
	echo '<input type="hidden" id="txfx-old-slug" name="txfx-old-slug" value="' . $name . '" />';
}

add_action('template_redirect', 'txfx_old_slug_redirect');
add_action('edit_post', 'txfx_check_for_changed_slugs');
add_action('edit_form_advanced', 'txfx_remember_old_slug');
add_action('edit_page_form', 'txfx_remember_old_slug');

?>

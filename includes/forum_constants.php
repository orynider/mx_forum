<?php
/**
 * forum_hack.php
 *                         --------------
 * begin                : June, 2004
 * copyright            : phpMiX (c) 2004
 * contact              : http://www.phpmix.com
 * module               : mx_forum
 * file contents        : Common definitions for the module.
 */

/**
 * This program is free software; you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation; either version 2 of the License, or
 *    (at your option) any later version.
 */
// **********************************************************************
// Read theme definition
// **********************************************************************
if ( file_exists( $module_root_path . "templates/" . $theme['template_name'] . "/images" ) )
{
	// ----------
	$current_template_images = $module_root_path . "templates/" . $theme['template_name'] . "/images" ;
	// ----------
}
else
{
	// ----------
	$current_template_images = $module_root_path . "templates/" . "subSilver" . "/images" ;
	// ----------
}

$images['printer'] = "$current_template_images/printer.gif";

define( 'POST_ADD_TYPE', 20 );
define( 'TOPIC_ADD_TYPE_TABLE', $mx_table_prefix . 'topic_add_type' );

// get type list for adding and editing articles

function phpbb2_get_types()
{
	$item_types_array = array( 'forum_news_announce', 'forum_news_announce', 'forum_news_sticky', 'forum_news_post' );
	$item_types_id_array = array( POST_GLOBAL_ANNOUNCE, POST_ANNOUNCE, POST_STICKY, POST_NORMAL );

	return array( $item_types_array, $item_types_id_array );
}

// Functions for newssuite operation mode
function phpbb2_auth_cat( $cat_id )
{
	global $news_type_select_data, $phpbb_type_select_data, $phpbb2_config;

	$tmp_phpbb = $phpbb_type_select_data[$cat_id]['forum_news'] == 1;
	$tmp_news = true;

	if ( $phpbb2_config['news_mode_operate'] )
	{
		$tmp_news = $news_type_select_data[$cat_id]['forum_news'] == 1;
	}

	return $tmp_phpbb && $tmp_news;
}

function phpbb2_auth_item( $cat_id, $item_type = '-5' )
{
	global $news_type_select_data, $phpbb2_config;

	$item_types_array = phpbb2_get_types();
	$validated_types = array();

	$ii = 0;
	$item_types_list = '(';
	for( $z = 0; $z < ( count( $item_types_array[0] ) ); $z++ )
	{
		if ( $news_type_select_data[$cat_id][$item_types_array[0][$z]] )
		{
			$ii++;
			$validated_types[] = $item_types_array[1][$z];
			$item_types_list .= ( ( $ii == 1 ) ? $item_types_array[1][$z] : ',' . $item_types_array[1][$z] );
		}
	}
	$item_types_list .= ')';

	if ( $item_type == '-5' )
	{
		return $item_types_list;
	}

	if ( in_array( $item_type, $validated_types ) || !$phpbb2_config['news_mode_operate'] )
	{
		return true;
	}
	else
	{
		return false;
	}
}

$mxbb_footer_addup[] = 'mxBB phpBB Module';

?>
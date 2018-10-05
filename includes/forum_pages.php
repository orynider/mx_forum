<?php
/**
 * forum_pages.php
 *                         ---------------
 * begin                : June, 2004
 * copyright            : phpMiX (c) 2004
 * contact              : http://www.phpmix.com
 * module               : mx_forum
 * file contents        : Common definitions for the module.
 * 
 * editor settings      : TabSize = 4
 */

/**
 * This program is free software; you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation; either version 2 of the License, or
 *    (at your option) any later version.
 */

// Set the numbers to the Portal Page IDs where you place each Block.
// If you do not want a phpBB Script to act as a Portal Block, use 0.

// Note: This piece of code snippet is somewhat ugly and needs cleaning up...still it works...

if ( empty( $_SESSION['phpbb_setup'] ) )
{ 
	$news_setup = array();
	
	$sql = "SELECT col.page_id, blk.block_id, sys.parameter_value, fnc.function_file 
    		FROM " . COLUMN_BLOCK_TABLE . " bct,
			" . COLUMN_TABLE . " col,
			" . BLOCK_TABLE . " blk,
			" . BLOCK_SYSTEM_PARAMETER_TABLE . " sys,
			" . FUNCTION_TABLE . " fnc,
			" . PARAMETER_TABLE . " par
    		WHERE col.column_id = bct.column_id
			AND blk.function_id = fnc.function_id
			AND par.function_id = fnc.function_id
      		AND blk.block_id    = bct.block_id
			AND blk.block_id    = sys.block_id
			AND par.parameter_name 	= 'phpbb_type_select'
      		ORDER BY page_id, block_id";
	
	if ( !$phpbb_result = $db->sql_query( $sql ) )
	{
		mx_message_die( GENERAL_ERROR, "Could not query modules information", "", __LINE__, __FILE__, $sql );
	}

	while ( $phpbb_rows = $db->sql_fetchrow( $phpbb_result ) )
	{
			$page_id = $phpbb_rows['page_id'];
			$block_id = $phpbb_rows['block_id']; 

				$phpbb_select_par = $phpbb_rows['parameter_value'];

				switch ( $phpbb_rows['function_file'] )
				{
					case 'index.php':
						$phpbb_mode_par = 'index';
						break;
					case 'viewforum.php':
						$phpbb_mode_par = 'viewforum';
						break;
					case 'viewtopic.php':
						$phpbb_mode_par = 'viewtopic';
						break;
				} 
				
				// Extract 'what posts to view info', the cool Array ;)
				$phpbb_type_select_data = array();
				$phpbb_type_select_temp = $phpbb_select_par;
				$phpbb_type_select_temp = stripslashes( $phpbb_type_select_temp );
				$phpbb_type_select_data = eval( "return " . $phpbb_type_select_temp . ";" );
				$phpbb2_config['news_mode_operate'] = true; 
				if ( is_array($phpbb_type_select_data) )
				{
					$news_setup[$page_id] = $phpbb_type_select_data;
					$news_mode[$page_id] = $phpbb_mode_par;
				}
			
	}
	
	
	
/*
	while ( list( $page_idd, $page_roww ) = each( $_SESSION['mx_pages'] ) )
	{
		$block_countt = count( $page_roww['blocks'] ); 

		for( $j = 0; $j < $block_countt; $j++ )
		{
			$block_idd = $page_roww['blocks'][$j]['block_id']; 

			if ( !empty( $_SESSION['block_' . $block_idd]['phpbb_type_select']['parameter_value'] ) )
			{
				$phpbb_select_par = $_SESSION['block_' . $block_idd]['phpbb_type_select']['parameter_value'];

				switch ( $page_roww['blocks'][$j]['function_file'] )
				{
					case 'index.php':
						$phpbb_mode_par = 'index';
						break;
					case 'viewforum.php':
						$phpbb_mode_par = 'viewforum';
						break;
					case 'viewtopic.php':
						$phpbb_mode_par = 'viewtopic';
						break;
				} 
				
				// Extract 'what posts to view info', the cool Array ;)
				$phpbb_type_select_data = array();
				$phpbb_type_select_temp = $phpbb_select_par;
				$phpbb_type_select_temp = stripslashes( $phpbb_type_select_temp );
				$phpbb_type_select_data = eval( "return " . $phpbb_type_select_temp . ";" );
				$phpbb2_config['news_mode_operate'] = true; 
				// echo('g');
				$news_setup[$page_idd] = $phpbb_type_select_data;
				$news_mode[$page_idd] = $phpbb_mode_par;
			}
			else 
			{
				echo('ff');	
			}
		}
	} 
*/
	while ( list( $page_idd, $news_setup_roww ) = each( $news_setup ) )
	{
		while ( list( $forum_idd, $news_forum_roww ) = each( $news_setup_roww ) )
		{
			if ( $news_forum_roww['forum_news'] == 1 )
			{
				switch ( $news_mode[$page_idd] )
				{
					case 'index':
						$page_to_index[$forum_idd] = $page_idd;
						break;
					case 'viewforum':
						$page_to_viewforum[$forum_idd] = $page_idd;
						break;
					case 'viewtopic':
						$page_to_viewtopic[$forum_idd] = $page_idd;
						break;
				}
			}
		}
	}

	$_SESSION['phpbb_setup']['index'] = $page_to_index;
	$_SESSION['phpbb_setup']['viewforum'] = $page_to_viewforum;
	$_SESSION['phpbb_setup']['viewtopic'] = $page_to_viewtopic;
}

// Start initial var setup

if ( isset( $_GET[POST_FORUM_URL] ) || isset( $_POST[POST_FORUM_URL] ) )
{
	$forum_id = ( isset( $_GET[POST_FORUM_URL] ) ) ? intval( $_GET[POST_FORUM_URL] ) : intval( $_POST[POST_FORUM_URL] );
}
else if ( isset( $_GET['forum'] ) )
{
	$forum_id = intval( $_GET['forum'] );
}
else
{
	$forum_id = '';
}

$sql = '';
$topic_id = $post_id = 0;
if ( isset( $_GET[POST_TOPIC_URL] ) )
{
	$topic_id = intval( $_GET[POST_TOPIC_URL] );
	$sql = "SELECT forum_id
	FROM " . TOPICS_TABLE . " 
	WHERE topic_id = $topic_id";
}
else if ( isset( $_GET['topic'] ) )
{
	$topic_id = intval( $_GET['topic'] );
	$sql = "SELECT forum_id
	FROM " . TOPICS_TABLE . " 
	WHERE topic_id = $topic_id";
}

if ( isset( $_GET[POST_POST_URL] ) )
{
	$post_id = intval( $_GET[POST_POST_URL] );
	$sql = "SELECT forum_id
	FROM " . POSTS_TABLE . "  
	WHERE post_id = $post_id";
}

if ( $sql )
{
	if ( !( $result = $db->sql_query( $sql ) ) )
	{
		mx_message_die( GENERAL_ERROR, "no info - error", '', __LINE__, __FILE__, $sql );
	}
	if ( !( $row = $db->sql_fetchrow( $result ) ) )
	{
		mx_message_die( GENERAL_MESSAGE, 'Topic_post_not_exist' );
	}
	$forum_id = $row['forum_id'];
}

$mx_index = str_replace( 'page_', '', $_SESSION['phpbb_setup']['index'][$forum_id] ) ;
$mx_viewforum = str_replace( 'page_', '', $_SESSION['phpbb_setup']['viewforum'][$forum_id] ) ;
$mx_viewtopic = str_replace( 'page_', '', $_SESSION['phpbb_setup']['viewtopic'][$forum_id] ) ;

if ( ( $mx_index || $mx_viewforum || $mx_viewtopic ) && $_SESSION['phpbb_setup_default']['override_default_pages'] == 'Block_setup' )
{
	$mx_forum_pages = array( 'faq' => 0,
		'groupcp' => 0,
		'index' => $mx_index,
		'login' => 0,
		'memberlist' => 0,
		'modcp' => 0,
		'posting' => 0,
		'privmsg' => 0,
		'profile' => 0,
		'search' => 0,
		'viewforum' => $mx_viewforum,
		'viewonline' => 0,
		'viewtopic' => $mx_viewtopic 
		);
}
else
{
	$mx_forum_pages = array( 'faq' => $_SESSION['phpbb_setup_default']['faq'],
		'groupcp' => $_SESSION['phpbb_setup_default']['groupcp'],
		'index' => $_SESSION['phpbb_setup_default']['index'],
		'login' => $_SESSION['phpbb_setup_default']['login'],
		'memberlist' => $_SESSION['phpbb_setup_default']['memberlist'],
		'modcp' => $_SESSION['phpbb_setup_default']['modcp'],
		'posting' => $_SESSION['phpbb_setup_default']['posting'],
		'privmsg' => $_SESSION['phpbb_setup_default']['privmsg'],
		'profile' => $_SESSION['phpbb_setup_default']['profile'],
		'search' => $_SESSION['phpbb_setup_default']['search'],
		'viewforum' => $_SESSION['phpbb_setup_default']['viewforum'],
		'viewonline' => $_SESSION['phpbb_setup_default']['viewonline'],
		'viewtopic' => $_SESSION['phpbb_setup_default']['viewtopic'] 
		);
		
}
// --------------------------------------------------------------------------------
// That's all Folks!
// --------------------------------------------------------------------------------

?>
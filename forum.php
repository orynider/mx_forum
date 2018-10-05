<?php
/***************************************************************************
 *                                forum.php
 *                            -------------------
 *   begin                : Saturday, Feb 13, 2001
 *   copyright            : (C) 2001 The phpBB Group
 *   email                : support@phpbb.com
 *
 *   $Id: forum.php,v 1.7 2010/10/10 15:01:18 orynider Exp $
 *
 *
 ***************************************************************************/

/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/
 
/**
* @ignore
*/


// MXP: Removed IN_PHPBB def
if( !defined('IN_PORTAL') )
{
	die("Hacking attempt !!!");
}
//-mx_forum module


//
// Initiate user style (template + theme) management
// - populate $theme, $images and initiate $template.
//
$mx_user->init_style();


define("IN_INDEX", true);
// MXP: Removed IN_PHPBB def $phpbb_root_path = './phpbb2/';
// $phpEx = 'php';

// MXP: Removed include common.php define('PHP_EXT', $phpEx);
/*
$mx_user->data = $mx_user->session_pagestart($mx_user_ip, PAGE_SEARCH);
$mx_user->set_lang($mx_user->lang, $mx_user->help, 'common');
$lang = &$mx_user->lang;
//$mx_user->_init_userprefs($mx_user->data);
init_userprefs($mx_user->data);
*/
// End session management
//

// ===================================================
// Get action variable otherwise set it to the main index
// ===================================================
$action = $mx_request_vars->request('phpbb_script', MX_TYPE_NO_TAGS, 'index');

$viewcat = (isset($_GET[POST_CAT_URL])) ? $_GET[POST_CAT_URL] : -1;

if( isset($_GET['mark']) || isset($_POST['mark']) )
{
	$mark_read = ( isset($_POST['mark']) ) ? $_POST['mark'] : $_GET['mark'];
}
else
{
	$mark_read = '';
}


// UPI2DB - BEGIN
$mark_always_read = request_var('always_read', '');
$mark_forum_id = request_var('forum_id', 0);

$viewcat = (!empty($_GET[POST_CAT_URL]) ? intval($_GET[POST_CAT_URL]) : -1);
$viewcat = (($viewcat <= 0) ? -1 : $viewcat);
$viewcatkey = ($viewcat < 0) ? 'Root' : POST_CAT_URL . $viewcat;

$mark_read = request_var('mark', '');

//
// Handle marking posts
//
if( $mark_read == 'forums' )
{
	if( $mx_user->data['session_logged_in'] )
	{
		setcookie($board_config['cookie_name'] . '_f_all', time(), 0, $board_config['cookie_path'], $board_config['cookie_domain'], $board_config['cookie_secure']);
	}

	$template->assign_vars(array(
		"META" => '<meta http-equiv="refresh" content="3;url='  .$mx_forum->append_sid("index.$phpEx") . '">')
	);

	$message = $lang['Forums_marked_read'] . '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . $mx_forum->append_sid("index.$phpEx") . '">', '</a> ');

	mx_message_die(GENERAL_MESSAGE, $message);
}
//
// End handle marking posts
//

$tracking_topics = ( isset($_COOKIE[$board_config['cookie_name'] . '_t']) ) ? unserialize($_COOKIE[$board_config['cookie_name'] . "_t"]) : array();
$tracking_forums = ( isset($_COOKIE[$board_config['cookie_name'] . '_f']) ) ? unserialize($_COOKIE[$board_config['cookie_name'] . "_f"]) : array();

//
// If you don't use these stats on your index you may want to consider
// removing them
//
$total_posts = get_db_stat('postcount');
$total_users = get_db_stat('usercount');
$newest_userdata = get_db_stat('newestuser');
$newest_user = $newest_userdata['username'];
$newest_uid = $newest_userdata['user_id'];

if( $total_posts == 0 )
{
	$l_total_post_s = $lang['Posted_articles_zero_total'];
}
else if( $total_posts == 1 )
{
	$l_total_post_s = $lang['Posted_article_total'];
}
else
{
	$l_total_post_s = $lang['Posted_articles_total'];
}

if( $total_users == 0 )
{
	$l_total_user_s = $lang['Registered_users_zero_total'];
}
else if( $total_users == 1 )
{
	$l_total_user_s = $lang['Registered_user_total'];
}
else
{
	$l_total_user_s = $lang['Registered_users_total'];
}

$order_legend = 'group_name';
// Grab group details for legend display
if ($phpbb_auth->acl_gets('a_group', 'a_groupadd', 'a_groupdel'))
{
	$sql = 'SELECT g.*, g.group_id as group_colour
		FROM ' . GROUPS_TABLE . ' g
		WHERE g.group_id > 0
		ORDER BY ' . $order_legend . ' ASC';
}
else
{
	$sql = 'SELECT g.*, g.group_id as group_colour
		FROM ' . GROUPS_TABLE . ' g
		LEFT JOIN ' . USER_GROUP_TABLE . ' ug
			ON (
				g.group_id = ug.group_id
				AND ug.user_id = ' . $mx_user->data['user_id'] . '
				AND ug.user_pending = 0
			)
		WHERE g.group_id > 0
			AND (g.group_type <> ' . GROUP_HIDDEN . ' OR ug.user_id = ' . $mx_user->data['user_id'] . ')
		ORDER BY g.' . $order_legend . ' ASC';
}
$result = $db->sql_query($sql);

$legend = array();

while ($row = $db->sql_fetchrow($result))
{
	$colour_text = ($row['group_colour']) ? ' style="color:#FFA' . $row['group_colour'] . '4F"' : '';
	$group_name = $row['group_name'];

	if ($row['group_name'] == 'BOTS' || ($mx_user->data['user_id'] != ANONYMOUS && !$phpbb_auth->acl_get('u_viewprofile')))
	{
		$legend[] = '<span' . $colour_text . '>' . $group_name . '</span>';
	}
	else
	{
		$legend[] = '<a' . $colour_text . ' href="' . $mx_forum->append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=group&amp;g=' . $row['group_id']) . '">' . $group_name . '</a>';
	}
}
$db->sql_freeresult($result);

$legend = implode(', ', $legend);


//
// Start page proper
//
$sql = "SELECT c.cat_id, c.cat_title, c.cat_order
	FROM " . CATEGORIES_TABLE . " c 
	ORDER BY c.cat_order";
if( !($result = $db->sql_query($sql)) )
{
	mx_message_die(GENERAL_ERROR, 'Could not query categories list', '', __LINE__, __FILE__, $sql);
}

$category_rows = array();
while ($row = $db->sql_fetchrow($result))
{
	$category_rows[] = $row;
}
$db->sql_freeresult($result);

// Begin Simple Subforums MOD
$subforums_list = array();
// End Simple Subforums MOD
$birthdays = $birthday_list = array();

if( ( $total_categories = count($category_rows) ) )
{
	//
	// Define appropriate SQL
	//
	switch(SQL_LAYER)
	{
		case 'postgresql':
			$sql = "SELECT f.*, p.post_time, p.post_username, u.username, u.user_id 
				FROM " . FORUMS_TABLE . " f, " . POSTS_TABLE . " p, " . USERS_TABLE . " u
				WHERE p.post_id = f.forum_last_post_id 
					AND u.user_id = p.poster_id  
					UNION (
						SELECT f.*, NULL, NULL, NULL, NULL
						FROM " . FORUMS_TABLE . " f
						WHERE NOT EXISTS (
							SELECT p.post_time
							FROM " . POSTS_TABLE . " p
							WHERE p.post_id = f.forum_last_post_id  
						)
					)
					ORDER BY cat_id, forum_order";
		break;

		case 'oracle':
			$sql = "SELECT f.*, p.post_time, p.post_username, u.username, u.user_id 
				FROM " . FORUMS_TABLE . " f, " . POSTS_TABLE . " p, " . USERS_TABLE . " u
				WHERE p.post_id = f.forum_last_post_id(+)
					AND u.user_id = p.poster_id(+)
				ORDER BY f.cat_id, f.forum_order";
		break;

		default:
			$sql = "SELECT f.*, p.post_time, p.post_username, u.username, u.user_id
				FROM (( " . FORUMS_TABLE . " f
				LEFT JOIN " . POSTS_TABLE . " p ON p.post_id = f.forum_last_post_id )
				LEFT JOIN " . USERS_TABLE . " u ON u.user_id = p.poster_id )
				ORDER BY f.cat_id, f.forum_order";
		break;
	}
	if ( !($result = $db->sql_query($sql)) )
	{
		mx_message_die(GENERAL_ERROR, 'Could not query forums information', '', __LINE__, __FILE__, $sql);
	}

	$forum_data = array();
	while( $row = $db->sql_fetchrow($result) )
	{
		$forum_data[] = $row;
	}
	$db->sql_freeresult($result);
	if ( !($total_forums = count($forum_data)) )
	{
		mx_message_die(GENERAL_MESSAGE, $lang['No_forums']);
	}

	//
	// Obtain a list of topic ids which contain
	// posts made since user last visited
	//
	if ($mx_user->data['session_logged_in'])
	{
		// 60 days limit
		if ($mx_user->data['user_lastvisit'] < (time() - 5184000))
		{
			$mx_user->data['user_lastvisit'] = time() - 5184000;
		}

		$sql = "SELECT t.forum_id, t.topic_id, p.post_time 
			FROM " . TOPICS_TABLE . " t, " . POSTS_TABLE . " p 
			WHERE p.post_id = t.topic_last_post_id 
				AND p.post_time > " . $mx_user->data['user_lastvisit'] . " 
				AND t.topic_moved_id = 0"; 
		if ( !($result = $db->sql_query($sql)) )
		{
			mx_message_die(GENERAL_ERROR, 'Could not query new topic information', '', __LINE__, __FILE__, $sql);
		}

		$new_topic_data = array();
		while( $topic_data = $db->sql_fetchrow($result) )
		{
			$new_topic_data[$topic_data['forum_id']][$topic_data['topic_id']] = $topic_data['post_time'];
		}
		$db->sql_freeresult($result);
	}

	//
	// Obtain list of moderators of each forum
	// First users, then groups ... broken into two queries
	//
	$sql = "SELECT aa.forum_id, u.user_id, u.username 
		FROM " . AUTH_ACCESS_TABLE . " aa, " . USER_GROUP_TABLE . " ug, " . GROUPS_TABLE . " g, " . USERS_TABLE . " u
		WHERE aa.auth_mod = " . TRUE . " 
			AND g.group_single_user = 1 
			AND ug.group_id = aa.group_id 
			AND g.group_id = aa.group_id 
			AND u.user_id = ug.user_id 
		GROUP BY u.user_id, u.username, aa.forum_id 
		ORDER BY aa.forum_id, u.user_id";
	if ( !($result = $db->sql_query($sql)) )
	{
		mx_message_die(GENERAL_ERROR, 'Could not query forum moderator information', '', __LINE__, __FILE__, $sql);
	}

	$forum_moderators = array();
	while( $row = $db->sql_fetchrow($result) )
	{
		$forum_moderators[$row['forum_id']][] = '<a href="' . $mx_forum->append_sid("profile.$phpEx?mode=viewprofile&amp;" . POST_USERS_URL . "=" . $row['user_id']) . '">' . $row['username'] . '</a>';
	}
	$db->sql_freeresult($result);

	$sql = "SELECT aa.forum_id, g.group_id, g.group_name 
		FROM " . AUTH_ACCESS_TABLE . " aa, " . USER_GROUP_TABLE . " ug, " . GROUPS_TABLE . " g 
		WHERE aa.auth_mod = " . TRUE . " 
			AND g.group_single_user = 0 
			AND g.group_type <> " . GROUP_HIDDEN . "
			AND ug.group_id = aa.group_id 
			AND g.group_id = aa.group_id 
		GROUP BY g.group_id, g.group_name, aa.forum_id 
		ORDER BY aa.forum_id, g.group_id";
	if ( !($result = $db->sql_query($sql)) )
	{
		mx_message_die(GENERAL_ERROR, 'Could not query forum moderator information', '', __LINE__, __FILE__, $sql);
	}

	while( $row = $db->sql_fetchrow($result) )
	{
		$forum_moderators[$row['forum_id']][] = '<a href="' . $mx_forum->append_sid("groupcp.$phpEx?" . POST_GROUPS_URL . "=" . $row['group_id']) . '">' . $row['group_name'] . '</a>';
	}
	$db->sql_freeresult($result);

	//
	// Find which forums are visible for this user
	//
	
	/*
	include_once($phpbb_root_path . 'includes/auth.' . $phpEx);
	$is_auth_ary = array();
	$is_auth_ary = auth(AUTH_VIEW, AUTH_LIST_ALL, $userdata, $forum_data);
	*/
	
	// Fix, using the cached auth data
	$auth_data_sql = $phpbb_auth->get_auth_forum();
	$is_auth_tmp = explode(',', $auth_data_sql);

	$is_auth_data = array();
	foreach ($is_auth_tmp as $key => $forum_id_tmp)
	{
		$is_auth_data[trim($forum_id_tmp)] = true;
	}
	
	// Load a template from style for our page
	$handle = 'body';	
	$tpl_name = 'index_'.$handle;
	
	//
	// Start output of page
	//
	define('SHOW_ONLINE', true);
	// $page_title = $lang['Index'];
	// MXP: Removed include page_header.php
	//$template->set_filenames(array(
	//	'block_forum' => 'index_block_forum.html')
	//);
	
	//+mxBB_module
	//include($phpbb_root_path . 'includes/page_header.'.$phpEx);
	//-mxBB_module
	$template->set_filenames(array($handle => "{$tpl_name}.{$tplEx}"));
	$template->assign_vars(array(
		//+mxbb_module
		//'TOTAL_POSTS' => sprintf($l_total_post_s, $total_posts),
		//'TOTAL_USERS' => sprintf($l_total_user_s, $total_users),
		//'NEWEST_USER' => sprintf($lang['Newest_user'], '<a href="' . $mx_forum->append_sid("profile.$phpEx?mode=viewprofile&amp;" . POST_USERS_URL . "=$newest_uid") . '">', $newest_user, '</a>'), 
		//-mxbb_module
		
		'FORUM_IMG' => $mx_forum->images['forum'],
		'FORUM_NEW_IMG' => $mx_forum->images['forum_new'],
		'FORUM_LOCKED_IMG' => $mx_forum->images['forum_locked'],

		'L_FORUM' => $lang['Forum'],
		'L_TOPICS' => $lang['Topics'],
		'L_REPLIES' => $lang['Replies'],
		'L_VIEWS' => $lang['Views'],
		'L_POSTS' => $lang['Posts'],
		'L_LASTPOST' => $lang['Last_Post'], 
		'L_NO_NEW_POSTS' => $lang['No_new_posts'],
		'L_NEW_POSTS' => $lang['New_posts'],
		'L_NO_NEW_POSTS_LOCKED' => $lang['No_new_posts_locked'], 
		'L_NEW_POSTS_LOCKED' => $lang['New_posts_locked'], 
		'L_ONLINE_EXPLAIN' => $lang['Online_explain'], 

		'L_MODERATOR' => $lang['Moderators'], 
		'L_FORUM_LOCKED' => $lang['Forum_is_locked'],
		'L_MARK_FORUMS_READ' => $lang['Mark_all_forums'],
		
		//+mxBB_module
		//'U_MARK_READ' => append_sid("index.$phpEx?mark=forums")
		//-mxBB_module
	));

	//
	// Okay, let's build the index
	//
	for($i = 0; $i < $total_categories; $i++)
	{
		$cat_id = $category_rows[$i]['cat_id'];

		//
		// Should we display this category/forum set?
		//
		$display_forums = true;
		for($j = 0; $j < $total_forums; $j++)
		{
			//if ( $is_auth_ary[$forum_data[$j]['forum_id']]['auth_view'] && $forum_data[$j]['cat_id'] == $cat_id )
			if ( $is_auth_data[$forum_data[$j]['forum_id']] && $forum_data[$j]['cat_id'] == $cat_id )
			{
				//+mxBB_module
				$val_forum_id = $forum_data[$j]['forum_id'];
			
				//-mxBB_module
				$display_forums = true;
			}
			else
			{
				//+mxBB_module
				$val_forum_id = $forum_data[$j]['forum_id'];
			
				//-mxBB_module
				$display_forums = false;
			}			
		}

	//+MXP_module	
	$template->assign_vars(array(
		'U_MARK_READ' => $mx_forum->append_sid("index.$phpEx?mark=forums&f=" . $val_forum_id)
		//'U_MARK_READ' => mx_append_sid(PORTAL_URL . 'index.' . $phpEx . '?page=' . $page_id . '&mark=forums')
	));
	//-MXP_module

		//
		// Yes, we should, so first dump out the category
		// title, then, if appropriate the forum list
		//
		if ( $display_forums )
		{
			$template->assign_block_vars('catrow', array(
				'CAT_ID' => $cat_id,
				'CAT_DESC' => $category_rows[$i]['cat_title'],
				'U_VIEWCAT' => $mx_forum->append_sid("index.$phpEx?" . POST_CAT_URL . "=$cat_id")
				//'U_VIEWCAT' => mx_append_sid(PHPBB_URL . "index.$phpEx" . '?page=' . $page_id . '&' . POST_CAT_URL . "=$cat_id")				
			));

			if ( $viewcat == $cat_id || $viewcat == -1 )
			{
				for($j = 0; $j < $total_forums; $j++)
				{
					if ( $forum_data[$j]['cat_id'] == $cat_id )
					{
						$forum_id = $forum_data[$j]['forum_id'];

						//+mxBB_module - forum subselect
						//if ( $is_auth_ary[$forum_id]['auth_view'] && phpbb2_auth_cat($forum_id) )
						if ($is_auth_data[$forum_id])						
						{
							if ( $forum_data[$j]['forum_status'] == FORUM_LOCKED )
							{
								$folder_image = $mx_forum->images['forum_locked']; 
								$folder_alt = $lang['Forum_locked'];
							}
							else
							{
								$unread_topics = false;
								if ( $userdata['session_logged_in'] )
								{
									if ( !empty($new_topic_data[$forum_id]) )
									{
										$forum_last_post_time = 0;

										while( list($check_topic_id, $check_post_time) = @each($new_topic_data[$forum_id]) )
										{
											if ( empty($tracking_topics[$check_topic_id]) )
											{
												$unread_topics = true;
												$forum_last_post_time = max($check_post_time, $forum_last_post_time);

											}
											else
											{
												if ( $tracking_topics[$check_topic_id] < $check_post_time )
												{
													$unread_topics = true;
													$forum_last_post_time = max($check_post_time, $forum_last_post_time);
												}
											}
										}

										if ( !empty($tracking_forums[$forum_id]) )
										{
											if ( $tracking_forums[$forum_id] > $forum_last_post_time )
											{
												$unread_topics = false;
											}
										}

										if ( isset($_COOKIE[$board_config['cookie_name'] . '_f_all']) )
										{
											if ( $_COOKIE[$board_config['cookie_name'] . '_f_all'] > $forum_last_post_time )
											{
												$unread_topics = false;
											}
										}

									}
								}
								$folder_image = ( $unread_topics ) ? $mx_forum->images['forum_new'] : $mx_forum->images['forum']; 
								$folder_alt = ( $unread_topics ) ? $lang['New_posts'] : $lang['No_new_posts']; 
							}

							//+mxBB_module
							// Get number of post in forum - newssuite addon
							if ( $phpbb2_config['news_mode_operate'] )
							{							
								$sql_num = "SELECT count(topic_id) AS total
								FROM " . TOPICS_TABLE . "
								WHERE "; 
								
								$sql_num .= " forum_id = " . $forum_id;
								if ( !($result_num = $db->sql_query($sql_num)) )
								{
									mx_message_die(GENERAL_ERROR, 'Error getting total postss', '', __LINE__, __FILE__, $sql_num);
								}
								if ( $total = $db->sql_fetchrow($result_num) )
								{
									$topics = $total['total'];
								}
								else
								{
									$topics = $forum_data[$j]['forum_topics'];
								}

								$sql_num = "SELECT count(p.post_id) AS ptotal
									FROM " . TOPICS_TABLE . " t, " . POSTS_TABLE . " p
									WHERE "; 
								
								$sql_num .= " t.forum_id = " . $forum_id . ' AND';
								$sql_num .= " p.forum_id = " . $forum_id . ' AND';
								$sql_num .= " t.topic_id = p.topic_id";
								if ( !($result_num = $db->sql_query($sql_num)) )
								{
									mx_message_die(GENERAL_ERROR, 'Error getting total postss', '', __LINE__, __FILE__, $sql_num);
								}
								if ( $total = $db->sql_fetchrow($result_num) )
								{
									$posts = $total['ptotal'];
								}
								else
								{
									$topics = $forum_data[$j]['forum_topics'];
								}
							}
							else
							{	
								$posts = $forum_data[$j]['forum_posts'];
								$topics = $forum_data[$j]['forum_topics'];
							}
							//-mxBB_module
							
							if ( $forum_data[$j]['forum_last_post_id'] )
							{
								$last_post_time = create_date($board_config['default_dateformat'], $forum_data[$j]['post_time'], $board_config['board_timezone']);

								$last_post = $last_post_time . '<br />';

								$last_post .= ( $forum_data[$j]['user_id'] == ANONYMOUS ) ? ( ($forum_data[$j]['post_username'] != '' ) ? $forum_data[$j]['post_username'] . ' ' : $lang['Guest'] . ' ' ) : '<a href="' . $mx_forum->append_sid("profile.$phpEx?mode=viewprofile&amp;" . POST_USERS_URL . '='  . $forum_data[$j]['user_id']) . '">' . $forum_data[$j]['username'] . '</a> ';
								
								$last_post .= '<a href="' . $mx_forum->append_sid("viewtopic.$phpEx?"  . POST_POST_URL . '=' . $forum_data[$j]['forum_last_post_id']) . '#' . $forum_data[$j]['forum_last_post_id'] . '"><img src="' . $mx_forum->images['icon_latest_reply'] . '" border="0" alt="' . $lang['View_latest_post'] . '" title="' . $lang['View_latest_post'] . '" /></a>';
							}
							else
							{
								$last_post = $lang['No_Posts'];
							}

							if ( count($forum_moderators[$forum_id]) > 0 )
							{
								$l_moderators = ( count($forum_moderators[$forum_id]) == 1 ) ? $lang['Moderator'] : $lang['Moderators'];
								$moderator_list = implode(', ', $forum_moderators[$forum_id]);
							}
							else
							{
								$l_moderators = '&nbsp;';
								$moderator_list = '&nbsp;';
							}

							$row_color = ( !($i % 2) ) ? $theme['td_color1'] : $theme['td_color2'];
							$row_class = ( !($i % 2) ) ? $theme['td_class1'] : $theme['td_class2'];

							$template->assign_block_vars('catrow.forumrow',	array(
								'ROW_COLOR' => '#' . $row_color,
								'ROW_CLASS' => $row_class,
								'FORUM_FOLDER_IMG' => $folder_image, 
								'FORUM_NAME' => $forum_data[$j]['forum_name'],
								'FORUM_DESC' => $forum_data[$j]['forum_desc'],
								//+mxBB_module
								'POSTS' => $posts,
								'TOPICS' => $topics,
								//-mxBB_module
								'LAST_POST' => $last_post,
								'MODERATORS' => $moderator_list,

								'L_MODERATOR' => $l_moderators, 
								'L_FORUM_FOLDER_ALT' => $folder_alt, 

								'U_VIEWFORUM' => $mx_forum->append_sid("viewforum.$phpEx?" . POST_FORUM_URL . "=$forum_id"))
							);
						}
					}
				}
			}
		}
	} // for ... categories

}// if ... total_categories
else
{
	mx_message_die(GENERAL_MESSAGE, $lang['No_forums']);
}

//+mxBB_module
// we need to add forum id to return link
$template->assign_vars(array(
	'UU_INDEX' => $phpbb_root_path . 'index.php?f=' . $val_forum_id,
));
//-mxBB_module

//
// Generate the page
//
$mx_forum->common_template_vars();
$template->pparse('body');

include ( $module_root_path . "includes/phpbb_footer." . $phpEx );

//+mx_forum
//include($phpbb_root_path . 'includes/page_tail.'.$phpEx);
return;
//-mx_forum

?>
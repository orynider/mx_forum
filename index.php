<?php
/***************************************************************************
 *                                index.php
 *                            -------------------
 *   begin                : Saturday, Feb 13, 2001
 *   copyright            : (C) 2001 The phpBB Group
 *   email                : support@phpbb.com
 *
 *   $Id: index.php,v 1.6 2005/10/01 14:12:41 jonohlsson Exp $
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


//-mx_forum module
/*
define('IN_PHPBB', true);
$phpbb_root_path = './';
include($phpbb_root_path . 'extension.inc');
include($phpbb_root_path . 'common.'.$phpEx);
*/
//-mx_forum module

//-mx_forum module
/*
$mx_root_path = './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
// mxBB: Removed include common.php
include($mx_root_path . 'common.'.$phpEx);

$module_root_path = "./modules/mx_phpbb/";

//
// Page selector
//
$page_id = $mx_request_vars->request('page', MX_TYPE_INT, 2);
*/

//-mx_forum module


//+mx_forum module

// ===================================================
// Include the constants file
// ===================================================
include($module_root_path . "includes/phpbb_constants.$phpEx");
include($module_root_path . "includes/forum_hack.$phpEx");

//+mx_forum module
/*
//
// Start session, user and style (template + theme) management
// - populate $userdata, $lang, $theme, $images and initiate $template.
//
$mx_user->init($user_ip, PAGE_LOGIN);

//
// Load and instatiate CORE (page) and block classes
//
$mx_page->init( $page_id );

//
// Initiate user style (template + theme) management
// - populate $theme, $images and initiate $template.
//
$mx_user->init_style();

// session id check
if (!$mx_request_vars->is_empty_request('sid'))
{
	$sid = $mx_request_vars->request('sid', MX_TYPE_NO_TAGS);
}
else
{
	$sid = '';
}

*/
//-mx_forum module

/**
* MXP rewrite of global, to instance $this in this function */ /**  * MXP rewrite of global, to instance $this in this function */ 
/**  * @ignore  * /  
global $mx_user; 
global $phpbb_auth;
 * / 
//+mx_forum module
/*
$userdata = session_pagestart($user_ip, PAGE_INDEX);
init_userprefs($userdata);
*/
//-mx_forum modul

define("IN_INDEX", true);
// MXP: Removed IN_PHPBB def$phpbb_root_path = './phpbb2/';
// $phpEx = 'php';

// MXP: Removed include common.phpdefine('PHP_EXT', $phpEx);
/*
$mx_userdata = $mx_user->session_pagestart($mx_user_ip, PAGE_SEARCH);
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

	$message = $lang['Forums_marked_read'] . '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . $mx_forum->append_sid("index.$phpEx?f=".$_GET['f']) . '">', '</a> ');

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
//+mx_forum module
/*
//-mx_forum module
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
//+mx_forum module
*/
//-mx_forum module

// ===================================================
// Is admin?
// ===================================================
switch (PORTAL_BACKEND)
{
	case 'internal':
	case 'phpbb2':
		$is_admin = ( ( $userdata['user_level'] == ADMIN  ) && $userdata['session_logged_in'] ) ? true : 0;
		break;
	case 'phpbb3':
		$is_admin = ( $userdata['user_type'] == USER_FOUNDER ) ? true : 0;
		break;
}

// ===================================================
// if the module is disabled give them a nice message
// ===================================================
if (!($mx_forum->phpbb_config['enable_module'] || $is_admin))
{
	mx_message_die( GENERAL_MESSAGE, $lang['pafiledb_disable'] );
}

// ===================================================
// an array of all expected actions
// ===================================================
$actions = array(
			'index' => 'forum',
			'viewforum' => 'viewforum',
			'viewtopic' => 'viewtopic',
			'faq' => 'faq',
			'groupcp' => 'groupcp',
			'login' => 'login',
			'memberlist' => 'memberlist',
			'mcp' => 'mcp', //'standalone' ???
			'modcp' => 'index', //'standalone' ???
			'posting' => 'posting',
			'privmsg' => 'privmsg',
			'profile' => 'profile',
			'ucp' => 'ucp',
			'search' => 'search',
			'viewonline' => 'viewonline',
			'other' => 'other',

			// Cash
			'cash' => 'other',
			'bin' => 'other',
);

// ===================================================
// Lets Build the page
// ===================================================
define('SHOW_ONLINE', true);
$page_title = $mx_user->lang($action);

if ( $action !== 'index' )
{
	//error_reporting(1); //Errors will coupt thumbnalis
	//@include_once( $mx_forum->module_root_path . $actions[$action] . '.' . $phpEx );
	//$mx_forum->module( $actions[$action] );
	if ((@include $mx_forum->module_root_path . $actions[$action] . ".$phpEx") === false)
	{
		print_r("Not installed, module $actions[$action] to load it: " . __LINE__ . ", file: " . __FILE__ .", last query: ". $sql);
	}	
}
else if(!is_object($mx_block))
{
	die("Hacking attempt !!!");	
}
else
{
	// ===================================================
	// 	Lets Build the main page
	//	Here we include the rest of index.php 
	//	ported from phpBB as forum.php
	// ===================================================		
	
	if ((include $mx_forum->module_root_path . $actions[$action] . ".$phpEx") === false)
	{
		print_r("Not installed, module $actions[$action] to load it: " . __LINE__ . ", file: " . __FILE__ .", last query: ". $sql);
	}
	
}

?>
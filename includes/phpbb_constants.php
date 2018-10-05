<?php
/**
*
* @package Mx-Publisher Module - mx_phpbb
* @version $Id: phpbb_constants.php,v 1.10 2008/06/23 21:12:54 jonohlsson Exp $
* @copyright (c) 2002-2006 [Markus, Jon Ohlsson] Mx-Publisher Project Team
* @license http://opensource.org/licenses/gpl-license.php GNU General Public License v2
*
*/




// ---------------------------------------------------------------------START
// This file defines specific constants for the module
// -------------------------------------------------------------------------

if ( !defined( 'IN_PORTAL' ) )
{
	die( "Hacking attempt" );
}

// Forum/Topic states
!defined('FORUM_CAT') ? define('FORUM_CAT', 0) : false;
!defined('FORUM_POST') ? define('FORUM_POST', 1) : false;
!defined('FORUM_LINK') ? define('FORUM_LINK', 2) : false;
!defined('ITEM_UNLOCKED') ? define('ITEM_UNLOCKED', 0) : false;
!defined('ITEM_LOCKED') ? define('ITEM_LOCKED', 1) : false;
!defined('ITEM_MOVED') ? define('ITEM_MOVED', 2) : false;

// Topic types
!defined('POST_NORMAL') ? define('POST_NORMAL', 0) : false;
!defined('POST_STICKY') ? define('POST_STICKY', 1) : false;
!defined('POST_ANNOUNCE') ? define('POST_ANNOUNCE', 2) : false;
!defined('POST_GLOBAL') ? define('POST_GLOBAL', 3) : false;

define( 'PAGE_FORUM', -502 );
define( 'PHPBB_CONFIG_TABLE', $mx_table_prefix . 'phpbb_plugin_config' );
define( 'PHPBB3_CONFIG_TABLE', $mx_table_prefix . 'phpbb3_plugin_config' );
define( 'POST_ADD_TYPE', 20 );
define( 'TOPIC_ADD_TYPE_TABLE', $mx_table_prefix . 'topic_add_type' );

/* START Include language file */
$default_lang = $language = ($mx_user->user_language_name) ? $mx_user->user_language_name : (($board_config['default_lang']) ? $board_config['default_lang'] : 'english');
/*  */
if ((@include $mx_root_path . "includes/shared/phpbb2/language/lang_" . $language . "/lang_main.$phpEx") === false)
{
	if ((@include $mx_root_path . "includes/shared/phpbb2/language/lang_english/lang_main.$phpEx") === false)
	{
			mx_message_die(CRITICAL_ERROR, 'Language file ' . $mx_root_path . "language/lang_" . $language . "/lang_main.$phpEx" . ' couldn\'t be opened.');
	}
	$default_lang = $language = 'english'; 
}
/*  */
if ((@include $module_root_path . "language/lang_" . $language . "/lang_phpbb.$phpEx") === false)
{
	if ((@include $module_root_path . "language/lang_english/lang_phpbb.$phpEx") === false)
	{
			mx_message_die(CRITICAL_ERROR, 'Language file ' . $mx_root_path . "language/lang_" . $language . "/lang_phpbb.$phpEx" . ' couldn\'t be opened.');
	}
	$default_lang = $language = 'english'; 
}

// -------------------------------------------------------------------------
// Footer Copyrights
// -------------------------------------------------------------------------
if (is_object($mx_page))
{
	// -------------------------------------------------------------------------
	// Extend User Style with module lang and images
	// Usage:  $mx_user->extend(LANG, IMAGES)
	// Switches:
	// - LANG: MX_LANG_MAIN (default), MX_LANG_ADMIN, MX_LANG_ALL, MX_LANG_NONE
	// - IMAGES: MX_IMAGES (default), MX_IMAGES_NONE
	// -------------------------------------------------------------------------
	$mx_user->extend(MX_LANG_MAIN, MX_IMAGES_NONE);
}

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
	$current_template_images = $module_root_path . "templates/" . "_core" . "/images" ;
	// ----------
}

$images['phpbb_folder_announce'] = $images['folder_announce'];
$images['phpbb_folder_sticky'] = $images['folder_sticky'];
$images['phpbb_folder'] = $images['folder'];

/*
* get type list for adding and editing articles
*/
function phpbb3_get_types()
{
	$item_types_array = array( 'forum_news_announce', 'forum_news_announce', 'forum_news_sticky', 'forum_news_post' );
	$item_types_id_array = array( POST_GLOBAL_ANNOUNCE, POST_ANNOUNCE, POST_STICKY, POST_NORMAL );

	return array( $item_types_array, $item_types_id_array );
}
/*
* get type list for adding and editing articles
*/
if (is_object($mx_page))
{
	$mx_page->add_copyright('MXP Module');
}

// ----------
$phpbb_module_version = "0.9x BETA";
$phpbb_module_author = "MX Team";
$phpbb_module_orig_author = "phpBB Group";
?>
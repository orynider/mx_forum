<?php
/**
 * newssuite_constants.php
 *                             -------------------
 *    begin                : April, 2003
 *    copyright            : (C) 2002 MX-System
 *    email                : support@mx-system.com
 *    description		  : define constants
 * 	 Author				  : Haplo (jonohlsson@hotmail.com)
 * 	 credit				  : Roman Malarev (romutis), MarcMoris 
 * 
 *    $Id: phpbb_constants.php,v 1.4 2005/02/18 10:06:52 jonohlsson Exp $
 */
// ---------------------------------------------------------------------START
// This file defines specific constants for the module
// -------------------------------------------------------------------------
define( 'PAGE_FORUM', -502 );
define( 'PHPBB_CONFIG_TABLE', $mx_table_prefix . 'phpbb_plugin_config' );
define( 'TOPIC_ADD_TYPE_TABLE', $mx_table_prefix . 'topic_add_type' );
// **********************************************************************
// Read language definition
// **********************************************************************
if ( !file_exists( $mx_root_path . 'modules/mx_forum/language/lang_' . $board_config['default_lang'] . '/lang_phpbb.' . $phpEx ) )
{
	include( $mx_root_path . 'modules/mx_forum/language/lang_english/lang_phpbb.' . $phpEx );
	$link_language = 'lang_english';
}
else
{
	include( $mx_root_path . 'modules/mx_forum/language/lang_' . $board_config['default_lang'] . '/lang_phpbb.' . $phpEx );
	$link_language = 'lang_' . $board_config['default_lang'];
} 
// ----------
$current_template_images = $mx_root_path . "modules/mx_newssuite/templates/" . $theme['template_name'] . "/images" ;
$current_template_images = $mx_root_path . 'modules/mx_newssuite/templates/subSilver/images' ;
// ----------
$phpbb_module_version = "0.9x BETA";
$phpbb_module_author = "MX Team";
$phpbb_module_orig_author = "phpBB Group";


?>
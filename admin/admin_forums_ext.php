<?php
/**
 * admin_forums_ext.php
 *                             -------------------
 *    begin                : Thursday, Jul 12, 2001
 *    copyright            : (C) 2001 The phpBB Group
 *    email                : support@phpbb.com
 * 
 *    $Id: admin_forums_ext.php,v 1.3 2005/09/15 17:59:19 jonohlsson Exp $
 */

/**
 * This program is free software; you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation; either version 2 of the License, or
 *    (at your option) any later version.
 */

define( 'IN_PORTAL', 1 );

if ( !empty( $setmodules ) )
{
	$file = basename( __FILE__ );
	$module['phpBB plugin']['Management'] = 'modules/mx_forum/admin/' . $file;
	return;
}

$mx_root_path = '../../../';
$module_root_path = "../";
require( $mx_root_path . 'extension.inc' );
require( $mx_root_path . '/admin/pagestart.' . $phpEx );

include_once( $mx_root_path . 'admin/page_header_admin.' . $phpEx );
include_once( $module_root_path . 'includes/phpbb_constants.' . $phpEx );

//
// Mode setting
//
$mode = $mx_request_vars->request('mode', MX_TYPE_NO_TAGS, '');

//
// Main db settings 
// Pull all config data
//
$sql = "SELECT *
	 FROM " . PHPBB_CONFIG_TABLE;
if ( !$result = $db->sql_query( $sql ) )
{
	mx_message_die( CRITICAL_ERROR, "Could not query phpbb plugin base configuration information", "", __LINE__, __FILE__, $sql );
}
else
{
	while ( $row = $db->sql_fetchrow( $result ) )
	{
		$phpbb_config_name = $row['config_name'];
		$phpbb_config_value = $row['config_value'];
		$phpbb_default_config[$phpbb_config_name] = $phpbb_config_value;

		$phpbb_new[$phpbb_config_name] = ( isset( $HTTP_POST_VARS[$phpbb_config_name] ) ) ? $HTTP_POST_VARS[$phpbb_config_name] : $phpbb_default_config[$phpbb_config_name];
		if ( isset( $HTTP_POST_VARS['submit'] ) )
		{
			$sql = "UPDATE " . PHPBB_CONFIG_TABLE . " SET
			   		config_value = '" . str_replace( "\'", "''", $phpbb_new[$phpbb_config_name] ) . "'
					WHERE config_name = '$phpbb_config_name'";
			if ( !$db->sql_query( $sql ) )
			{
				mx_message_die( GENERAL_ERROR, "Failed to update general configuration for $config_name", "", __LINE__, __FILE__, $sql );
			}
		}
	}

	if ( isset( $HTTP_POST_VARS['submit'] ) )
	{
		$message = $lang['phpbb_config_updated'] . "<br /><br />" . sprintf( $lang['Click_return_phpbb_config'], "<a href=\"" . append_sid( "admin_forums_ext.$phpEx" ) . "\">", "</a>" ) . "<br /><br />" . sprintf( $lang['Click_return_admin_index'], "<a href=\"" . append_sid( $mx_root_path . "admin/index.$phpEx?pane=right" ) . "\">", "</a>" );
		mx_message_die( GENERAL_MESSAGE, $message );
	}
}

//
// Populate parameter variables
//
$phpbb_faq = $phpbb_new['faq'];
$phpbb_groupcp = $phpbb_new['groupcp'];
$phpbb_index = $phpbb_new['index'];
$phpbb_login = $phpbb_new['login'];
$phpbb_memberlist = $phpbb_new['memberlist'];
$phpbb_modcp = $phpbb_new['modcp'];
$phpbb_posting = $phpbb_new['posting'];
$phpbb_privmsg = $phpbb_new['privmsg'];
$phpbb_profile = $phpbb_new['profile'];
$phpbb_search = $phpbb_new['search'];
$phpbb_viewforum = $phpbb_new['viewforum'];
$phpbb_viewonline = $phpbb_new['viewonline'];
$phpbb_viewtopic = $phpbb_new['viewtopic'];
$phpbb_override_default_pages = $phpbb_new['override_default_pages'];

//
// Start page proper
//
$template->set_filenames( array( "body" => "admin/forum_admin_body_ext.tpl" ));

$template->assign_vars( array( 
		'S_FORUM_ACTION' => append_sid( "admin_forums_ext.$phpEx" ),
		'L_FORUM_TITLE' => $lang['Forum_admin'],
		'L_FORUM_EXPLAIN' => $lang['Forum_admin_explain'],
		'L_SUBMIT' => $lang['submit'],
		'L_RESET' => $lang['reset'],

		'L_DEFAULT_PAGES_TITLE' => $lang['default_pages_title'],
		'L_DEFAULT_PAGES_TITLE_EXPLAIN' => $lang['default_pages_title_explain'],

		'L_DEFAULT_PAGES_MORE_TITLE' => $lang['default_pages_more_title'],
		'L_DEFAULT_PAGES_MORE_TITLE_EXPLAIN' => $lang['default_pages_more_title_explain'],
				
		'L_PHPBB_OVERRIDE_DEFAULT_PAGES' => $lang['phpbb_override'],
		'L_PHPBB_OVERRIDE_DEFAULT_PAGES_EXPLAIN' => $lang['phpbb_override_explain'],

		'L_PHPBB_OVERRIDE_DEFAULT_PAGES_YES' => $lang['phpbb_override_yes'],
		'L_PHPBB_OVERRIDE_DEFAULT_PAGES_NO' => $lang['phpbb_override_no'],
		
		'OVERRIDE_DEFAULT_PAGES_CHECKBOX_YES' => ( $phpbb_override_default_pages == '1' ) ? ' checked="checked"' : '',
		'OVERRIDE_DEFAULT_PAGES_CHECKBOX_NO' => ( $phpbb_override_default_pages == '0' ) ? ' checked="checked"' : '',

		'L_DEFAULT_PAGES_PROFILECP' => $lang['default_pages_profilecp'],
				
		'L_PHPBB_FAQ' => $lang['phpbb_faq'],
		'PHPBB_FAQ' => $phpbb_faq,

		'L_PHPBB_GROUPCP' => $lang['phpbb_groupcp'],
		'PHPBB_GROUPCP' => $phpbb_groupcp,

		'L_PHPBB_INDEX' => $lang['phpbb_index'] . ', ' . $lang['phpbb_viewforum'] . ', ' . $lang['phpbb_viewtopic'],
		'PHPBB_INDEX' => $phpbb_index,

		'L_PHPBB_LOGIN' => $lang['phpbb_login'],
		'PHPBB_LOGIN' => $phpbb_login,

		'L_PHPBB_MEMBERLIST' => $lang['phpbb_memberlist'],
		'PHPBB_MEMBERLIST' => $phpbb_memberlist,

		'L_PHPBB_MODCP' => $lang['phpbb_modcp'],
		'PHPBB_MODCP' => $phpbb_modcp,

		'L_PHPBB_POSTING' => $lang['phpbb_posting'],
		'PHPBB_POSTING' => $phpbb_posting,

		'L_PHPBB_PRIVMSG' => $lang['phpbb_privmsg'],
		'PHPBB_PRIVMSG' => $phpbb_privmsg,

		'L_PHPBB_PROFILE' => $lang['phpbb_profile'],
		'PHPBB_PROFILE' => $phpbb_profile,

		'L_PHPBB_SEARCH' => $lang['phpbb_search'],
		'PHPBB_SEARCH' => $phpbb_search,

		//'L_PHPBB_VIEWFORUM' => $lang['phpbb_viewforum'],
		//'PHPBB_VIEWFORUM' => $phpbb_viewforum,

		'L_PHPBB_VIEWONLINE' => $lang['phpbb_viewonline'],
		'PHPBB_VIEWONLINE' => $phpbb_viewonline,

		//'L_PHPBB_VIEWTOPIC' => $lang['phpbb_viewtopic'],
		//'PHPBB_VIEWTOPIC' => $phpbb_viewtopic 
	));

$template->pparse( "body" );
include_once( $mx_root_path . 'admin/page_footer_admin.' . $phpEx );

?>

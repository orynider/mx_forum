<?php
/**
 * * ------------------------------------------------------------------------
 * 		subject				: mxBB-Portal - CMS & portal
 * 		begin            	: june, 2002
 * 		copyright          	: (C) 2002-2005 mxBB-Portal
 * 		email             	: jonohlsson@hotmail.com
 * 		project site		: www.mx-system.com
 * 
 * -------------------------------------------------------------------------
 * 
 *     $Id: db_upgrade.php,v 1.7 2005/10/01 14:12:41 jonohlsson Exp $
 */

/**
 * This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 */

define( 'IN_PORTAL', true );
if ( !defined( 'IN_ADMIN' ) )
{
	$mx_root_path = './../../';
	$phpEx = substr(strrchr(__FILE__, '.'), 1);
	include( $mx_root_path . 'common.' . $phpEx ); 
	// Start session management
	$userdata = session_pagestart( $user_ip, PAGE_INDEX );
	mx_init_userprefs( $userdata );

	if ( !$userdata['session_logged_in'] )
	{
		die( "Hacking attempt(1)" );
	}

	if ( $userdata['user_level'] != ADMIN )
	{
		die( "Hacking attempt(2)" );
	} 
	// End session management
}

$mx_module_version = 'mxBB - phpBB Integration Module 1.0.3';
$mx_module_copy = 'Based on <a href="http://www.phpbb.com/" target="_phpbb" >phpBB</a>';

$sql = array();

//
// Precheck
//
if ( $result = $db->sql_query( "SELECT config_name from " . $mx_table_prefix . "phpbb_plugin_config" ) )
{ 
	//
	// Upgrade checks
	//
	$upgrade_101 = 0;
	$upgrade_102 = 0;
	$upgrade_103 = 0;
	$upgrade_104 = 0;
	$upgrade_105 = 0;
	$upgrade_106 = 0;
	$upgrade_107 = 0;

	//
	// validate before 1.01
	//
	$result = $db->sql_query( "SELECT config_value from " . $mx_table_prefix . "phpbb_plugin_config WHERE config_name = 'override_default_pages'" );
	if ( $db->sql_numrows( $result ) == 0 )
	{
		$upgrade_101 = 1;
	}

	if ( !$result = $db->sql_query( "SELECT topic_type_id from " . $mx_table_prefix . "topic_add_type" ) )
	{
		$upgrade_102 = 1;
	}

	$message = "<b>Upgrading!</b><br/><br/>";

	if ( $upgrade_101 == 1 )
	{
		$message .= "<b>Upgrading to v. 1.01...</b><br/><br/>";
		$sql[] = "CREATE TABLE " . $mx_table_prefix . "phpbb_plugin_config (
		  			 	   	    config_name VARCHAR(255) NOT NULL default '', 
								config_value varchar(255) NOT NULL default '',
								PRIMARY KEY  (config_name)
								) TYPE=MyISAM";

		$sql[] = "INSERT INTO " . $mx_table_prefix . "phpbb_plugin_config VALUES ('override_default_pages', 'Block_setup')";
		$sql[] = "INSERT INTO " . $mx_table_prefix . "phpbb_plugin_config VALUES ('faq', '0')";
		$sql[] = "INSERT INTO " . $mx_table_prefix . "phpbb_plugin_config VALUES ('groupcp', '0')";
		$sql[] = "INSERT INTO " . $mx_table_prefix . "phpbb_plugin_config VALUES ('index', '2')";
		$sql[] = "INSERT INTO " . $mx_table_prefix . "phpbb_plugin_config VALUES ('login', '0')";
		$sql[] = "INSERT INTO " . $mx_table_prefix . "phpbb_plugin_config VALUES ('memberlist', '0')";
		$sql[] = "INSERT INTO " . $mx_table_prefix . "phpbb_plugin_config VALUES ('modcp', '0')";
		$sql[] = "INSERT INTO " . $mx_table_prefix . "phpbb_plugin_config VALUES ('posting', '0')";
		$sql[] = "INSERT INTO " . $mx_table_prefix . "phpbb_plugin_config VALUES ('privmsg', '0')";
		$sql[] = "INSERT INTO " . $mx_table_prefix . "phpbb_plugin_config VALUES ('profile', '0')";
		$sql[] = "INSERT INTO " . $mx_table_prefix . "phpbb_plugin_config VALUES ('search', '0')";
		//$sql[] = "INSERT INTO " . $mx_table_prefix . "phpbb_plugin_config VALUES ('viewforum', '0')";
		$sql[] = "INSERT INTO " . $mx_table_prefix . "phpbb_plugin_config VALUES ('viewonline', '0')";
		//$sql[] = "INSERT INTO " . $mx_table_prefix . "phpbb_plugin_config VALUES ('viewtopic', '0')";
	}
	else if ( $upgrade_102 == 1 )
	{
		$message .= "<b>Upgrading to v. 1.02...</b><br/><br/>";
		$sql[] = "CREATE TABLE " . $mx_table_prefix . "topic_add_type (
		  	topic_type_name varchar(64) NOT NULL default '',
		  	topic_type_id tinyint(4) NOT NULL default '0',
		  	topic_type_auth tinyint(1) NOT NULL default '-1',
		  	topic_type_active tinyint(1) NOT NULL default '0',
		  	topic_type_order tinyint(3) NOT NULL default '0',
		  	topic_type_color varchar(6) NOT NULL default '0',
		  	topic_type_image varchar(255) default NULL,
		  	topic_type_image_new varchar(255) default NULL
								) TYPE=MyISAM";

		$sql[] = "INSERT INTO " . $mx_table_prefix . "topic_add_type VALUES ('announce', 0, -1, 0, -1, 'FF0000', '', '')";
		$sql[] = "INSERT INTO " . $mx_table_prefix . "topic_add_type VALUES ('sticky', 0, -1, 0, -1, 'FAD400', '', '')";
		$sql[] = "ALTER TABLE " . TOPICS_TABLE . " ADD topic_type_active TINYINT(1) NOT NULL DEFAULT '0' AFTER topic_type";
	}
	else
	{
		$message .= "<b>Nothing to upgrade...</b><br/><br/>";
	}
	
	$sql[] = "UPDATE " . $mx_table_prefix . "module" . "
				    SET module_version  = '" . $mx_module_version . "',
				      module_copy  = '" . $mx_module_copy . "'
				    WHERE module_id = '" . $mx_module_id . "'";	

	$message .= mx_do_install_upgrade( $sql );
}
else
{ 
	//
	// If not installed
	//
	$message = "<b>Module is not installed...and thus cannot be upgraded ;)</b><br/><br/>";
}

echo "<br /><br />";
echo "<table  width=\"90%\" align=\"center\" cellpadding=\"4\" cellspacing=\"1\" border=\"0\" class=\"forumline\">";
echo "<tr><th class=\"thHead\" align=\"center\">Module Installation/Upgrading/Uninstalling Information - module specific db tables</th></tr>";
echo "<tr><td class=\"row1\"  align=\"left\"><span class=\"gen\">" . $message . "</span></td></tr>";
echo "</table><br />";

?>
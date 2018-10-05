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
 *     $Id: db_uninstall.php,v 1.5 2005/09/15 17:59:19 jonohlsson Exp $
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
	include( $mx_root_path . 'extension.inc' );
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

$sql = array( 
	"DROP TABLE IF EXISTS " . $mx_table_prefix . "phpbb_plugin_config", 
	);

echo "<br /><br />";
echo "<table  width=\"90%\" align=\"center\" cellpadding=\"4\" cellspacing=\"1\" border=\"0\" class=\"forumline\">";
echo "<tr><th class=\"thHead\" align=\"center\">Module Installation/Upgrading/Uninstalling Information - module specific db tables</th></tr>";
echo "<tr><td class=\"row1\"  align=\"left\"><span class=\"gen\">" . mx_do_install_upgrade( $sql ) . "</span></td></tr>";
echo "</table><br />";

?>
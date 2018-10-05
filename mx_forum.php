<?php
/***************************************************************************
 *                                mx_index.php
 *                            -------------------
 *   begin                : Saturday, Feb 13, 2001
 *   copyright            : (C) 2001 The phpBB Group
 *   email                : support@phpbb.com
 *
 *   $Id: mx_forum.php,v 1.2 2005/10/01 14:12:41 jonohlsson Exp $
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
if( !defined('IN_PORTAL') )
{
	die("Hacking attempt !!!");
}
include_once($mx_root_path . "modules/mx_forum/includes/forum_hack.$phpEx");
include_once($mx_root_path . "modules/mx_forum/includes/phpbb_constants.$phpEx");

$phpbb_type_select_data = ( !empty( $mx_block->block_parameters['Source_phpBB_Forums']['parameter_value'] ) ) ? unserialize($mx_block->block_parameters['Source_phpBB_Forums']['parameter_value']) : array();

switch ( $mx_forum->phpbb_script )
{
	case 'index':
		include($mx_root_path . "modules/mx_forum/index.$phpEx");
		break;
	case 'viewforum':
		include($mx_root_path . "modules/mx_forum/viewforum.$phpEx");
		break;
	case 'viewtopic':
		include($mx_root_path . "modules/mx_forum/viewtopic.$phpEx");
		break;
	case 'posting':
		include($mx_root_path . "modules/mx_forum/posting.$phpEx");
		break;		
} 
?>
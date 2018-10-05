<?php
/** ------------------------------------------------------------------------
 *		subject				: mx-portal, CMS & portal
 *		begin            	: june, 2002
 *		copyright          	: (C) 2002-2005 MX-System
 *		email             	: jonohlsson@hotmail.com
 *		project site		: www.mx-system.com
 * 
 *		description			:
 * -------------------------------------------------------------------------
 * 
 *    $Id: phpbb_footer.php,v 1.1 2005/01/15 23:45:37 jonohlsson Exp $
 */

/**
 * This program is free software; you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation; either version 2 of the License, or
 *    (at your option) any later version.
 */

if ( !defined( 'IN_PORTAL' ) )
{
	die( "Hacking attempt" );
}

// Parse and show the overall footer.

$template->set_filenames( array( 'phpbb_footer' => 'phpbb_footer.tpl' ) 
	);

$template->assign_vars( array( 'L_MODULE_VERSION' => $phpbb_module_version,
		'L_MODULE_ORIG_AUTHOR' => $phpbb_module_orig_author,
		'L_MODULE_AUTHOR' => $phpbb_module_author ) 
	);

$template->pparse( 'phpbb_footer' );

?>
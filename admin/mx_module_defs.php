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
 *    $Id: mx_module_defs.php,v 1.3 2005/09/15 17:59:19 jonohlsson Exp $
 */

 /**
 * This program is free software; you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation; either version 2 of the License, or
 *    (at your option) any later version.
 */

 
/********************************************************************************\
| Class: mx_blockcp_parameter
| The mx_blockcp_parameter object provides extra module block parameters, added to the standard core parameters.
| 
| Usage examples:
| 
\********************************************************************************/

//
// The following flags are class specific options
//
//define('MX_ALL_DATA'		, -1);		// Flag - write all data

class mx_module_defs
{
	// ------------------------------
	// Private Methods
	//
	//

	// ===================================================
	// define module specific block parameters
	// ===================================================
	function get_parameters($type_row = '')
	{
		global $lang;
		
		if (empty($type_row))
		{
			$type_row = array();
		}
		
		$type_row['phpbb_type_select'] = !empty($lang['ParType_phpbb_type_select']) ? $lang['ParType_phpbb_type_select'] : "phpBB Source";
		
		return $type_row;
	}

	// ===================================================
	// Submit custom parameter field and data
	// ===================================================
	function submit_module_parameters( $parameter_data, $block_id )
	{
		global $HTTP_POST_VARS, $db, $board_config, $html_entities_match, $html_entities_replace;

		$parameter_value = $HTTP_POST_VARS[$parameter_data['parameter_name']];
		$parameter_opt = '';
					
		switch ( $parameter_data['parameter_type'] )
		{
			case 'phpbb_type_select':
				$parameter_value = addslashes( serialize( $parameter_value ) );
				break;
		}
		
		return array('parameter_value' => $parameter_value, 'parameter_opt' => $parameter_opt);
	}
		
	// ===================================================
	// display parameter field and data in the add/edit page
	// ===================================================
	function display_module_parameters( $parameter_data )
	{
		global $template, $mx_blockcp, $mx_root_path, $theme, $lang;

		switch ( $parameter_data['parameter_type'] )
		{
			case 'phpbb_type_select':
				$this->display_edit_Phpbb_type_select( $block_id, $parameter_data['parameter_id'], $parameter_data );
				break;
		}
	}
	
	function display_edit_Phpbb_type_select( $block_id, $parameter_id, $parameter_data )
	{
		global $template, $board_config, $db, $theme, $lang, $images, $mx_blockcp, $mx_root_path, $phpEx, $mx_table_prefix, $table_prefix;

		$module_root_path = $mx_root_path . $mx_blockcp->module_root_path;
		include_once( $module_root_path . "includes/phpbb_constants.$phpEx" );
		include_once( $module_root_path . "includes/phpbb_defs.$phpEx" );

		if (file_exists($mx_root_path . $mx_blockcp->module_root_path . 'templates/'. $theme['template_name'] . '/admin/mx_module_parameters.tpl'))
		{
			$module_template_file = $mx_root_path . $mx_blockcp->module_root_path . 'templates/'. $theme['template_name'] . '/admin/mx_module_parameters.tpl';
		}
		else 
		{
			$module_template_file = $mx_root_path . $mx_blockcp->module_root_path . 'templates/subSilver' . '/admin/mx_module_parameters.tpl';
		}
		
		$template->set_filenames(array(
			'parameter' => $module_template_file)
		);
			
		// Get number of forums in db
		$sql = "SELECT * 
			FROM " . NEWS_CAT_TABLE . "
			ORDER BY $cat_extract_order";
		if ( !( $result = $db->sql_query( $sql ) ) )
		{
			message_die( GENERAL_ERROR, "Couldn't obtain forums information.", "", __LINE__, __FILE__, $sql );
		}
		
		$forums = $db->sql_fetchrowset( $result );

		// Get array of categories from the database
		
		$sql = "SELECT cat_id, cat_title
						FROM " . CATEGORIES_TABLE . " 
						ORDER BY cat_order";
		if ( !( $result = $db->sql_query( $sql ) ) )
		{
			mx_message_die( GENERAL_ERROR, "Couldn't obtain category list.", "", __LINE__, __FILE__, $sql );
		}
		
		$categories = $db->sql_fetchrowset( $result );
		
		$phpbb_type_select_data = ( !empty( $mx_blockcp->block_parameters['Source_phpBB_Forums']['parameter_value'] ) ) ? unserialize($mx_blockcp->block_parameters['Source_phpBB_Forums']['parameter_value']) : array();

		// 
		// Check that some categories exist
		// 
		if ( $total_categories = count( $categories ) )
		{ 
			// 
			// Check that some forums exist (these were queried earlier)
			// 
			if ( $total_forums = count( $forums ) )
			{
				$template->assign_block_vars( 'switch_forums_phpbb', array( 'COLSPAN' => count( $item_types_array ) + 2 
						) );
		
				for( $i = 0; $i < $total_categories; $i++ )
				{
					$template->assign_block_vars( 'catrow', array( 'CAT_ID' => $categories[$i]['cat_id'],
							'COLSPAN' => count( $item_types_array ) + 1,
							'CAT_NAME' => $categories[$i]['cat_title'] 
							) );
		
					for( $j = 0; $j < $total_forums; $j++ )
					{
						if ( $forums[$j]['cat_id'] == $categories[$i]['cat_id'] || $forums[$j]['cat_id'] == '' )
						{
							$template->assign_block_vars( 'catrow.forumrow_phpbb', array( 'FORUM_ID' => $forums[$j][$catt_id],
									'FORUM_NAME' => $forums[$j][$catt_name],
									'FORUM_DESC' => $forums[$j][$catt_desc],
		
									'CHECKED' => ( $phpbb_type_select_data[$forums[$j]['forum_id']] ? 'CHECKED' : '' ),
								));
						}
					}
				}
			}
		}
		
		$template->assign_vars(array(
			'NAME' =>  $lang[$parameter_data['parameter_name']],
			'SELSCT_NAME' =>  $parameter_data['parameter_name'],
			'PARAMETER_TITLE' => ( !empty($lang[$parameter_data['parameter_name']]) ) ? $lang[$parameter_data['parameter_name']] : $parameter_data['parameter_name'],
			'PARAMETER_TYPE' => ( !empty($lang["ParType_".$parameter_data['parameter_type']]) ) ? $lang["ParType_".$parameter_data['parameter_type']] : '',
			'PARAMETER_TYPE_EXPLAIN' => ( !empty($lang["ParType_".$parameter_data['parameter_type'] . "_info"]) ) ? '<br />' . $lang["ParType_".$parameter_data['parameter_type'] . "_info"]  : '',
			
			'SCRIPT_PATH' => $module_root_path,
			'I_ANNOUNCE' => PHPBB_URL . $images['folder_announce'],
			'I_STICKY' => PHPBB_URL . $images['folder_sticky'],
			'I_NORMAL' => PHPBB_URL . $images['folder'],
	
			'L_ANNOUNCEMENT' => $lang['Post_Announcement'],
			'L_STICKY' => $lang['Post_Sticky'],
			'L_NORMAL' => $lang['Posted'],		
		));
		
		$template->pparse('parameter');
		
	}
}
?>
<?php
/**
*
* @package Mx-Publisher Module - mx_phpbb
* @version $Id: forum_hack.php,v 1.2 2010/10/16 04:07:43 orynider Exp $
* @copyright (c) 2002-2006 [Markus, Jon Ohlsson] Mx-Publisher Project Team
* @license http://opensource.org/licenses/gpl-license.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
	die("Hacking attempt");
}

/**
 * forum_hack.php
 *                         --------------
 * begin                : June, 2004
 * copyright            : phpMiX (c) 2004
 * contact              : http://www.phpmix.com
 * module               : mx_forum
 * file contents        : Common definitions for the module.
 */

/**
 * This program is free software; you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation; either version 2 of the License, or
 *    (at your option) any later version.
 */

@define('MX_FORUM_DEBUG', true);
@define('MX_PHPBB3_BLOCK', true);
// --------------------------------------------------------------------------------
// Class: mx_forum
// --------------------------------------------------------------------------------
class mx_forum
{
	
	// Class Implementation Notes:
	
	// Variables/Methods prefixed with an underscore are meant to be private. So, do
	// not use them outside this class. They may change in future implementations.
	
	// Actually, everything here is subject to change in future as well. =:-o
	
	var $phpbb_config, // Config values array
	$phpbb_block_map, // Block mapping	
	$phpbb_url, // aka(MX): PHPBB_URL
	$portal_url, // aka(MX): PORTAL_URL
	$phpbb_root_path,	
	$images, // Used to replace phpBB $images with our own version
	// ...we will prefix all images with a full phpBB URL.
	$forum_pages,
	$script_name, 
	$phpbb_script, // phpBB mode: index, viewforum, viewtopic...	
	$tpl_ext = 'html';
	// Instance Initialization...
	function init()
	{
		global $db, $images, $board_config, $lang, $block_id, $userdata, $block_config, $template, $tplEx, $_GET, $_POST; 
		
		$PHPBB_CONFIG_TABLE = $this->_get_mx_table_name('phpbb_plugin_config');

		//
		// Pull all config data
		//
		$sql = "SELECT *
			FROM " . $PHPBB_CONFIG_TABLE;
		if (!$result = $db->sql_query($sql))
		{
			if ( defined('IN_PORTAL') )
			{
				mx_message_die( CRITICAL_ERROR, "Could not query phpbb plugin configuration information", "", __LINE__, __FILE__, $sql );
			}	
			print_r("mx_phpbb is not installed, return to phpbb common.php line:" . __LINE__ . ", file:" . __FILE__ .", query: ". $sql );
			return;
		}
		else
		{
			while ( $row = $db->sql_fetchrow( $result ) )
			{
				$config_name = $row['config_name'];
				$config_value = $row['config_value'];
				$this->phpbb_config[$config_name] = $config_value;
			}
		}
		$db->sql_freeresult($result);

		//
		// Is module redirections activated
		//
		if 	(!$this->phpbb_config['enable_module'])
		{
			print_r("mx_phpbb is not installed, return to phpbb common.php line:" . __LINE__ . ", file:" . __FILE__ .", query: ". $sql );
			return;
		}		
		
		// Get Portal/Forum paths...
		$this->init_paths(); 
		
		// Load user defined table of forum pages...
		$this->init_forum_pages(); 
		
		// Find phpBB Mode
		$this->phpbb_script = isset($_GET['phpbb_script']) ? $_GET['phpbb_script'] : 'index';		
		
		// Extra initialization if beign called as a Portal Block...
		if (defined( 'IN_PORTAL'))
		{ 
			global $mx_root_path, $mx_cache, $mx_block, $mx_user, $mx_images;
			// Get phpBB script name from current Portal Page ID...			
			$page_id = ( isset( $_GET['page'] ) ? intval( $_GET['page'] ) : 2 );
			$this->script_page = '';
			$this->tpl_ext = ( isset($tplEx) ? $tplEx : $this->tpl_ext );
			foreach( $this->forum_pages as $script_name => $script_page )
			{
				if ( $script_page == $page_id )
				{
					$this->script_name = $script_name;
					break;
				}
			} 
			
			// Prefix $images with corected PHPBB_URL...		
			$this->images = array();
			$images = $mx_user->images;
			foreach($images as $key => $val)
			{
				if (is_array($val))
				{
					foreach($val as $key2 => $val2)
					{
						//$this->images[$key][$key2] = $this->phpbb_url . $val2;
						$this->images[$key][$key2] = str_replace('/imageset/', $mx_user->imageset_path, $val2);
					}
				}
				else
				{
					//$this->images[$key] = $this->phpbb_url . $val;
					$this->images[$key] = str_replace('/imageset/', $mx_user->imageset_path, $val);
				}
			}
					
			// General MX vars
			// ----------------------------------------------------------
			// Block info addin
			$block_config = $mx_cache->_get_block_config($block_id, 0, 'block_config');
			$title = $this->title = $mx_block->block_info['block_title'];
			$show_title = ($user->data['user_level'] == ADMIN) ? true : $mx_block->block_info['show_title'];
			$description = $mx_block->block_info['block_desc'];
			$block_size = ( isset( $block_size ) && !empty( $block_size ) ? $block_size : '100%' ); 
			$show_stats = $mx_block->block_info['show_stats'];
			$block_style = $mx_block->get_parameters('block_style');			
			// Extract 'what posts to view info', the cool Array ;)
			$phpbb_type_select_data = array();
			//$phpbb_type_select_temp = $block_config[$block_id][phpbb_type_select]['parameter_value'];
			$phpbb_type_select_temp  = $mx_block->get_parameters('phpbb_type_select');			
			$phpbb_type_select_temp = stripslashes($phpbb_type_select_temp);
			$phpbb_type_select_data = eval( "return " . $phpbb_type_select_temp . ";" );
			$edit = $mx_block->block_info['auth_edit'];
			$module_root_path = $this->module_root_path = $mx_root_path . $mx_block->block_info['module_root_path']; 
			$name = $mx_block->block_info['module_name']; 
			
			$this->phpbb_type_select_data = $phpbb_type_select_data;
			
			/*
			$is_auth_ary = array();
			$is_auth_ary = block_auth(AUTH_EDIT, $block_id , $userdata, $block_config[$block_id][auth_edit], $block_config[$block_id][auth_edit_group] );
			*/
			
			// Load a template from style for our page
			$handle = 'phpbb_header';	
			$tpl_name = ''.$handle;
			
			$template->set_filenames(array($handle => "{$tpl_name}.{$this->tpl_ext}"));	
			$template->assign_vars( array( 
					'L_TITLE' => $title,
					'L_DESCRIPTION' => $desc,
					'BLOCK_SIZE' => $block_size 
			) );
			
			$template->pparse($handle);
			// --------------------------------------------------------------		
			$this->user = $mx_user;			
		}
		elseif (defined( 'IN_PHPBB'))
		{		
			global $user;		
			
			$this->user = $user;
		}		
	}
	
	// --------------------------------------------------------------------------------
	// Path Initialization...
	// --------------------------------------------------------------------------------	
	function init_paths()
	{
		global $mx_root_path, $portal_config, $db, $phpEx, $board_config;
		
		if ( defined( PHPBB_URL ) && defined( PORTAL_URL ) )
		{
			$this->phpbb_url = PHPBB_URL;
			$this->portal_url = PORTAL_URL;
		}
		else
		{
			$PORTAL_TABLE = $this->_get_mx_table_name('portal');
			$sql = "SELECT * FROM $PORTAL_TABLE WHERE portal_id = 1";
			if (!($result = $db->sql_query($sql)))
			{
				$this->phpbb_url = '';
				$this->portal_url = '';
			}
			else
			{
				$row = $db->sql_fetchrow($result);
				if (is_file($mx_root_path . 'includes/sessions/phpbb3/constants.php'))
				{
					//
					// Core 3.0.x
					//
					$script_name = preg_replace('/^\/?(.*?)\/?$/', "\\1", trim($row['script_path']));
					$server_name = trim($row['server_name']);
					$server_protocol = ( $row['cookie_secure'] ) ? 'https://' : 'http://';
					$server_port = (($row['server_port']) && ($row['server_port'] <> 80)) ? ':' . trim($row['server_port']) . '/' : '/';

					$server_url = $server_protocol . str_replace("//", "/", $server_name . $server_port . $script_name . '/'); //On some server the slash is not added and this trick will fix it

					@define('PORTAL_URL', $server_url);

					$script_name_phpbb = preg_replace('/^\/?(.*?)\/?$/', "\\1", trim($board_config['script_path'])) . '/';

					$server_url_phpbb = $server_protocol . $server_name . $server_port . $script_name_phpbb;
					@define('PHPBB_URL', $server_url_phpbb);

					$this->phpbb_url = PHPBB_URL;
					$this->portal_url = PORTAL_URL;
				}
				else
				{
					//
					// Core 2.8.x
					//
					$this->phpbb_url = $row['portal_phpbb_url'];
					$this->portal_url = $row['portal_url'];
				}
			}
			$db->sql_freeresult($result);
		}
		$this->phpbb_root_path = str_replace("//", "/", $mx_root_path . $portal_config['portal_backend_path']);
		$this->mx_root_path = str_replace("//", "/", $mx_root_path . $mx_root_path);
	}
	
	// --------------------------------------------------------------------------------
	// Page Initialization...
	// --------------------------------------------------------------------------------	
	function init_forum_pages()
	{
		global $mx_root_path, $phpEx, $page_id, $_POST, $_GET, $db, $mx_table_prefix, $is_inline_review ;

		if ( empty( $_SESSION['phpbb_setup_default'] ) )
		{
			$PHPBB_CONFIG_TABLE = $this->_get_mx_table_name( 'phpbb_plugin_config' ); 
						
			// Pull all config data		
			$sql = "SELECT *
		 		FROM " . $PHPBB_CONFIG_TABLE;
			if ( !$result = $db->sql_query( $sql ) )
			{
				if (defined('IN_PORTAL'))
				{
					mx_message_die(CRITICAL_ERROR, "Could not query phpbb plugin configuration information", "", __LINE__, __FILE__, $sql );
				}
				elseif (defined('IN_PHPBB'))
				{
					message_die(CRITICAL_ERROR, "Could not query phpbb plugin configuration information", "", __LINE__, __FILE__, $sql );
				}				
			}
			else
			{
				while ( $row = $db->sql_fetchrow( $result ) )
				{
					$config_name = $row['config_name'];
					$config_value = $row['config_value'];
					$_SESSION['phpbb_setup_default'][$config_name] = $config_value;
				}
			}
		}
		$mx_table_prefix = $this->_get_mx_table_name('');
		
		define( 'FUNCTION_TABLE', $mx_table_prefix . 'function' );
		define( 'PARAMETER_TABLE', $mx_table_prefix . 'parameter' );

		define( 'COLUMN_TABLE' , $mx_table_prefix . 'column' );
		define( 'COLUMN_BLOCK_TABLE', $mx_table_prefix . 'column_block' );

		define( 'BLOCK_TABLE', $mx_table_prefix . 'block' );
		define( 'BLOCK_SYSTEM_PARAMETER_TABLE', $mx_table_prefix . 'block_system_parameter' );

		include( $mx_root_path . 'modules/mx_forum/includes/forum_pages.' . $phpEx );
		
		//
		// Now load pages defintion
		// This is only called if the default pages mapping should be overridden by the block setup itself
		//
		if ($this->phpbb_config['override_default_pages'])
		{
			if ( !defined( 'IN_PORTAL' ) )
			{
				/* ================================================================================ *
					We are being called by a phpBB Script running as a Portal Block !!!
				* ================================================================================ */
				define( 'IN_PORTAL', 1 );
				$mx_table_prefix = $this->_get_mx_table_name('');
				$phpEx = substr(strrchr(__FILE__, '.'), 1);
				include_once($mx_root_path . "includes/mx_constants.".$phpEx);
				include_once($mx_root_path . "includes/mx_functions.".$phpEx);
				include_once($mx_root_path . "includes/mx_functions_core.".$phpEx);
			}

			//
			// Query to find all mx_forum blocks - the forum_id select array
			//
			$sql = "SELECT col.page_id, blk.block_id, sys.parameter_value, fnc.function_file
		    		FROM " . COLUMN_BLOCK_TABLE . " bct,
					" . COLUMN_TABLE . " col,
					" . BLOCK_TABLE . " blk,
					" . BLOCK_SYSTEM_PARAMETER_TABLE . " sys,
					" . FUNCTION_TABLE . " fnc,
					" . PARAMETER_TABLE . " par
		    		WHERE col.column_id = bct.column_id
					AND blk.function_id = fnc.function_id
					AND par.function_id = fnc.function_id
		      		AND blk.block_id    = bct.block_id
					AND blk.block_id    = sys.block_id
					AND par.parameter_type 	= 'phpbb_type_select'
		      		ORDER BY page_id, block_id";

			if ( !$phpbb_result = $db->sql_query( $sql ) )
			{
				mx_message_die( GENERAL_ERROR, "Could not query modules information", "", __LINE__, __FILE__, $sql );
			}
			
			while ( $phpbb_rows = $db->sql_fetchrow( $phpbb_result ) )
			{
				$phpbb_type_select_data = ( !empty( $phpbb_rows['parameter_value'] ) ) ? unserialize($phpbb_rows['parameter_value']) : array();

				if (is_array($phpbb_type_select_data))
				{
					foreach ($phpbb_type_select_data as $forum_id => $value)
					{
						if ($value == 1)
						{
							$this->phpbb_block_map[$forum_id] = $phpbb_rows['page_id'];
						}
					}
				}
			}
			$db->sql_freeresult($result);

			//
			// Start initial var setup - get current post/forum/cat id etc
			//
			if ( isset( $_GET[POST_FORUM_URL] ) || isset( $_POST[POST_FORUM_URL] ) )
			{
				$forum_id = ( isset( $_GET[POST_FORUM_URL] ) ) ? intval( $_GET[POST_FORUM_URL] ) : intval( $_POST[POST_FORUM_URL] );
			}
			else if ( isset( $_GET['forum'] ) )
			{
				$forum_id = intval( $_GET['forum'] );
			}
			else
			{
				$forum_id = '';
			}

			$sql = '';
			$topic_id = $post_id = 1;
			if ( isset($_GET[POST_TOPIC_URL] ))
			{
				$topic_id = intval($_GET[POST_TOPIC_URL]);
				$sql = "SELECT forum_id
				FROM " . TOPICS_TABLE . "
				WHERE topic_id = $topic_id";
			}
			else if ( isset( $_GET['topic'] ) )
			{
				$topic_id = intval( $_GET['topic'] );
				$sql = "SELECT forum_id
				FROM " . TOPICS_TABLE . "
				WHERE topic_id = $topic_id";
			}

			if ( isset( $_GET[POST_POST_URL] ) )
			{
				$post_id = intval( $_GET[POST_POST_URL] );
				$sql = "SELECT forum_id
				FROM " . POSTS_TABLE . "
				WHERE post_id = $post_id";
			}

			//
			// We have a problem here, when p denotes a pm_id, and not a post_id!!!!!!!!!!!!!!!!!!!
			//
			if ($sql)
			{
				if ( !( $result = $db->sql_query( $sql ) ) )
				{
					mx_message_die( GENERAL_ERROR, "no info - error", '', __LINE__, __FILE__, $sql );
				}
				if ( !( $row = $db->sql_fetchrow( $result ) ) )
				{
					mx_message_die( GENERAL_MESSAGE, 'Topic_post_not_exist' );
				}
				$forum_id = $row['forum_id'];
				$db->sql_freeresult($result);
			}

			//
			// If block-page association is found, override deafult mapping
			//
			$mx_index = $this->phpbb_block_map[$forum_id];

			if  ($mx_index)
			{
				$this->phpbb_config['index'] = $mx_index;
			}
		}

		//
		// Populate the forum_pages array
		//
		$mx_forum_pages = array(
			'index' => $this->phpbb_config['index'],
			'viewforum' => $this->phpbb_config['viewforum'],
			'viewtopic' => $this->phpbb_config['viewtopic'],
			'faq' => $this->phpbb_config['faq'],
			'groupcp' => $this->phpbb_config['groupcp'],
			'login' => $this->phpbb_config['login'],
			'memberlist' => $this->phpbb_config['memberlist'],
			'mcp' => $this->phpbb_config['mcp'], //'standalone' ???
			'modcp' => $this->phpbb_config['modcp'], //'standalone' ???
			'posting' => $this->phpbb_config['posting'],
			'privmsg' => $this->phpbb_config['privmsg'],
			'profile' => $this->phpbb_config['profile'],
			'ucp' => $this->phpbb_config['ucp'],
			'search' => $this->phpbb_config['search'],
			'viewonline' => $this->phpbb_config['viewonline'],
			'other' => $this->phpbb_config['other'],

			// Cash
			'cash' => $this->phpbb_config['other'],
			'bin' => $this->phpbb_config['other'],
		);
		
		if ( isset( $mx_forum_pages ) && is_array( $mx_forum_pages ) && !isset($_GET['mode']))
		{
			$this->forum_pages = $mx_forum_pages;
		}
		elseif ( isset( $mx_forum_pages ) && is_array( $mx_forum_pages ) && isset($_GET['mode']) && ($_GET['mode'] != 'topicreview') )
		{
			$this->forum_pages = $mx_forum_pages;
		}
		else
		{
			//
			// Something is weird, do not redirect phpbb pages :(
			//
			$this->forum_pages = array(
				'faq' => 0,
				'groupcp' => 0,
				'index' => 0,
				'login' => 0,
				'memberlist' => 0,
				'modcp' => 0,
				'posting' => 0,
				'privmsg' => 0,
				'profile' => 0,
				'search' => 0,
				'viewforum' => 0,
				'viewonline' => 0,
				'viewtopic' => 0,

				// Cash
				'cash' => 0,
				'bin' => 0
			);
		}
	}
	
	/**
	 * load module.
	 *
	 * @param unknown_type $module_name send module name to load it
	 */
	function module( $module_name )
	{
		global $module_root_path, $phpEx;

		$this->module_name = $module_name;

		if (include( $this->module_root_path . $module_name . '.' . $phpEx ) === false)
		{
			print_r("not installed, module $module_name to load it: " . __LINE__ . ", file:" . __FILE__ .", query: ". $sql );
		}
	}
	
	// --------------------------------------------------------------------------------
	// Tricky but useful method to retrieve Portal Table names from phpBB scope...
	// --------------------------------------------------------------------------------
	function _get_mx_table_name($table_suffix)
	{
		global $mx_root_path, $phpEx, $mx_table_prefix;
		if (!isset($mx_table_prefix))
		{
			include($mx_root_path . 'config.' . $phpEx);
		}
		return $mx_table_prefix . $table_suffix;
	}
	
	// --------------------------------------------------------------------------------
	// Get a Full URL to a Portal Page or prefix the URL being passed with full phpBB URL.
	// --------------------------------------------------------------------------------
	function full_url($url, $non_html_amp = false, $portal_url = false)
	{
		//
		// If portal_page is set, transform the url into a portal equvivalent
		//
		if ($portal_url)
		{
			$url = $this->get_portal_url($url, $non_html_amp, false);
		}
		else
		{
			//$url = ( !empty($portal_url) ? $portal_url : $this->phpbb_url.$url );
			// $script_name = $this->_validate_redirection($url, $by_http_vars);
			//$url = ( $this->phpbb_url.$url."&page=" . $this->forum_pages[$script_name] );			
			//$url = $this->get_portal_url( $url, $non_html_amp, false );
			$url = $this->phpbb_url . $url;
		}

		return ( $url );
	}
	
	// --------------------------------------------------------------------------------
	// Get an URL to a Portal Page, whenever is possible... =:-o
	// --------------------------------------------------------------------------------
	function get_portal_url( $url, $non_html_amp = false, $by_http_vars = false )
	{
		global $phpEx;

		$script_name = $this->_validate_redirection($url, $by_http_vars);
		
		if ( empty( $script_name ) )
		{
			global $action;
			
			$script_name = $action;
			// return '';
		}
		
		$mx_profilecp_vars = '';
		$pos = 0;
		
		//
		// Redirect phpBB login calls to MXP
		//
		if ($script_name == 'login')
		{
			return $this->portal_url . 'login.' . $phpEx;
		}

		//
		// Support for the ProfileCP Module
		//
		if (file_exists($mx_root_path . 'modules/mx_profilecp/profile.php') && false)
		{
			switch ($script_name)
			{
				case 'memberlist':
					$mx_profilecp_vars = '&mode=buddy&sub=memberlist';
				break;

				case 'privmsg':

					$get_vars = array('folder', 'sid', 'mode', 'start', 'msgdays', POST_POST_URL, POST_USERS_URL);
					$s_call = '';
					$newpm = false;

					for ($i = 0; $i < count($get_vars); $i++)
					{
						$key = $get_vars[$i];
						$val = $_GET[$get_vars[$i]];

						switch ($get_vars[$i])
						{
							case POST_USERS_URL:
								$key = 'b';
							break;

							case 'mode':
								$newpm = ($val == 'newpm');

								if (!$newpm)
								{
									$key = 'privmsg_mode';
								}
							break;

							case 'folder':
								$key = 'sub';
							break;

							default:
								$key = $get_vars[$i];
						}

						if (isset($_GET[$get_vars[$i]]))
						{
							$s_call .= '&' . $key . '=' . $_GET[$get_vars[$i]];
						}
					}

					$mx_profilecp_vars = '&mode=privmsg'.$s_call;
				break;

				case 'profile':

					$s_call = '';
					if ( isset($_GET['mode']) || isset($_POST['mode']) )
					{
						$mode = ( isset($_GET['mode']) ) ? $_GET['mode'] : $_POST['mode'];
						$user_id = ( isset($_GET['u']) ) ? $_GET['u'] : $userdata['user_id'];

						if ( $mode == 'viewprofile' )
						{
							$s_call = "&mode=viewprofile&u=" . $user_id;
						}
						else if ( $mode == 'editprofile' || $mode == 'register' )
						{
							if ( $mode == 'editprofile' )
							{
								$s_call = "&mode=profil";
							}
							else
							{
								$s_call = "&mode=register";
							}
						}
					}

					$mx_profilecp_vars = $s_call;

				break;
			}
		}

		if ( !empty($script_name) && isset( $this->forum_pages[$script_name] ) && $this->forum_pages[$script_name] > 0 )
		{
			if (empty($url))
			{
				$return = $this->portal_url . "index.$phpEx?page=" . $this->forum_pages[$script_name] . '&phpbb_script=' . $script_name . $mx_profilecp_vars;
				return $return;			
			}
			
			if ( (($pos = strpos($url, '?', $pos + 1 )) === false) || ($pos === 0) )
			{
				$return = $this->portal_url . "index.$phpEx?page=" . $this->forum_pages[$script_name] . '&phpbb_script=' . $script_name . $mx_profilecp_vars;
				return $return;
			}
			$return = $this->portal_url . "index.$phpEx?page=" . $this->forum_pages[$script_name] . ( ( $non_html_amp ) ? '&' : '&amp;' ) . substr( $url, $pos + 1 ) . '&phpbb_script=' . $script_name . $mx_profilecp_vars;
			return $return;
		}
		
		$script_name = explode('/', $script_name);
		$offset = (count($script_name) > 1) ? (count($script_name) - 1) : 0;
		$script_name = $script_name[$offset];
		$return = $this->portal_url . "index.$phpEx?page=" . $this->phpbb_config['index'] . ( ( $non_html_amp ) ? '&' : '&amp;' ) . 'phpbb_script=' . $script_name . $mx_profilecp_vars;
		return $return;
	}
	
	// --------------------------------------------------------------------------------
	//
	// --------------------------------------------------------------------------------
	function _validate_redirection($url, $by_http_vars)
	{

		if (($pos = strpos( $url, '.' )) === false)
		{
			//return 'index';
		}
		
		$script_name = substr($url, 0, $pos);
		$script_name = substr($script_name, strrpos($script_name, "/"));
		$script_name = str_replace("/", "", $script_name);		
		
		switch ( $script_name )
		{
			case 'posting':
				$mode = $this->_get_mode( $url, $by_http_vars );
				if ( $mode == 'topicreview' || $mode == 'smilies' )
				{
					return '';
				}
			break;
			case 'search':
				$mode = $this->_get_mode( $url, $by_http_vars );
				if ( $mode == 'searchuser' )
				{
					return $script_name;
				}
			break;
			case 'viewtopic':
				$mode = $this->_get_mode( $url, $by_http_vars );
				if ( $mode == 'printertopic' || $mode == 'smilies' )
				{
					return '';
				}
			break;
			case 'cash':
				$mode = $this->_get_mode( $url, $by_http_vars );
				if ( $mode == 'modedited' )
				{
					return '';
				}
			break;			
		}

		return !empty($script_name) ? $script_name : 'index';
	}
	
	// --------------------------------------------------------------------------------
	// Get mode
	// --------------------------------------------------------------------------------
	function _get_mode($url, $by_http_vars)
	{
		if ( $by_http_vars )
		{
			return $this->_get_http_var( 'mode', '' );
		}
		$query_array = $this->_get_http_query_array( $url );
		return ( isset( $query_array['mode'] ) ? $query_array['mode'] : '' );
	}
	
	// --------------------------------------------------------------------------------
	// Get http vars
	// --------------------------------------------------------------------------------
	function _get_http_var( $key, $default )
	{
		return ( isset( $_GET[$key] ) ? $_GET[$key] : ( isset( $_POST[$key] ) ? $_POST[$key] : $default ) );
	}
	
	// --------------------------------------------------------------------------------
	// Get http vars
	// --------------------------------------------------------------------------------
	function _get_http_query_array($url)
	{
		if ( ( $pos = strpos( $url, '?' ) ) === false )
		{
			return array();
		}

		$url = substr( $url, $pos + 1 );

		if ( ( $pos = strpos( $url, '&amp;' ) ) !== false )
		{
			$url = str_replace( '&amp;', '&', $url );
		}

		$query_array = array();
		$query_string = explode( '&', $url );

		for( $i = 0; $i < count( $query_string ); $i++ )
		{
			$keyval = explode( '=', $query_string[$i] );
			$query_array[$keyval[0]] = $keyval[1];
		}

		return $query_array;
	}
	
	// --------------------------------------------------------------------------------
	// Initialize Common Template Vars...
	// --------------------------------------------------------------------------------
	function common_template_vars( $argarray = false )
	{
		global $template, $lang, $phpEx, $board_config, $userdata;

		static $l_timezone = '';
		if ( empty( $l_timezone ) )
		{ 
			// Arg! ...MX version of includes/page_header.php does not use this variable. :-(
			
			$l_timezone = explode( '.', $board_config['board_timezone'] );
			$l_timezone = ( count( $l_timezone ) > 1 && $l_timezone[count( $l_timezone )-1] != 0 ) ? $lang[sprintf( '%.1f', $board_config['board_timezone'] )] : $lang[number_format( $board_config['board_timezone'] )];
		}
		$common_vars = array(
			'U_PHPBB_ROOT_PATH' => $this->phpbb_url,
			'L_INDEX' => sprintf( $lang['Forum_Index'], $board_config['sitename'] ),
			'U_INDEX' => $this->append_sid("index.$phpEx"),
			'S_TIMEZONE' => ($this->user->data['user_dst'] || ($this->user->data['user_id'] == ANONYMOUS && $board_config['board_dst'])) ? sprintf($this->user->lang['ALL_TIMES'], $this->user->lang['tz'][$tz], $mx_user->lang['tz']['dst']) : sprintf($this->user->lang['ALL_TIMES'], $this->user->lang['tz'][$tz], ''),
			'L_USERNAME' => $lang['Username']
			);
		switch ( $this->script_name )
		{
			case 'index':
				global $s_last_visit; // declared here: includes/page_header.php
				$script_vars = array( 
					'LAST_VISIT_DATE'		=> sprintf($this->user->lang['YOU_LAST_VISIT'], $s_last_visit),
					'CURRENT_TIME'			=> sprintf($this->user->lang['CURRENT_TIME'], $this->user->format_date(time(), false, true)),
					'U_SEARCH'				=> mx3_append_sid("{$this->phpbb_root_path}search.$phpEx"),
					'U_SEARCH_SELF'			=> mx3_append_sid("{$this->phpbb_root_path}search.$phpEx", 'search_id=egosearch'),
					'U_SEARCH_NEW'			=> mx3_append_sid("{$this->phpbb_root_path}search.$phpEx", 'search_id=newposts'),
					'U_SEARCH_UNANSWERED'	=> mx3_append_sid("{$this->phpbb_root_path}search.$phpEx", 'search_id=unanswered'),
					'U_SEARCH_ACTIVE_TOPICS'=> mx3_append_sid("{$this->phpbb_root_path}search.$phpEx", 'search_id=active_topics'),
					'U_DELETE_COOKIES'		=> mx3_append_sid("{$this->phpbb_root_path}ucp.$phpEx", 'mode=delete_cookies'),
					'U_TEAM'				=> mx3_append_sid("{$this->phpbb_root_path}memberlist.$phpEx", 'mode=leaders'),
					'L_AUTO_LOGIN' 			=> $lang['Log_me_in'],
					'L_LOGIN_LOGOUT'		=> $lang['Login'],
					'S_LOGIN_ACTION'		=> mx3_append_sid('login.'.$phpEx)
				);
			break;
				//
				// Show online block
				// At some point we need to remove the online block. We already have a mxp online block, and should have the option to remove this phpbb block.
				//
				$this->show_online();
			break;
			case 'search':
				$script_vars = array( 'L_SEARCH' => $lang['Search'] 
					);
			break;
			case 'viewforum':
				global $online_userlist; // declared here: includes/page_header.php
				$script_vars = array( 'LOGGED_IN_USER_LIST' => $online_userlist 
					);
				$this->show_online();
			break;
			default:
				$script_vars = array();
				break;
		}
		$template->assign_vars( $common_vars + $script_vars + ( ( $argarray === false ) ? array() : $argarray ) );
		$template->assign_block_vars( 'switch_user_logged_' . ( ( $userdata['session_logged_in'] ) ? 'in' : 'out' ), array() );
		if (!$userdata['session_logged_in'])
		{
			$template->assign_block_vars('switch_allow_autologin', array());
			$template->assign_block_vars('switch_user_logged_out.switch_allow_autologin', array());
		}
	}


	// Do we really need this one?
	/**
	* Reset all login keys for the specified user
	* Called on password changes
	*/
	function session_reset_keys($user_id, $user_ip)
	{
		global $db, $userdata, $board_config, $mx_backend;

		$key_sql = ($user_id == $userdata['user_id'] && !empty($userdata['session_key'])) ? "AND key_id != '" . md5($userdata['session_key']) . "'" : '';

		$sql = 'DELETE FROM ' . SESSIONS_KEYS_TABLE . '
			WHERE user_id = ' . (int) $user_id . "
				$key_sql";

		if ( !$db->sql_query($sql) )
		{
			mx_message_die(CRITICAL_ERROR, 'Error removing auto-login keys', '', __LINE__, __FILE__, $sql);
		}

		$where_sql = 'session_user_id = ' . (int) $user_id;
		$where_sql .= ($user_id == $userdata['user_id']) ? " AND session_id <> '" . $userdata['session_id'] . "'" : '';
		$sql = 'DELETE FROM ' . SESSIONS_TABLE . "
			WHERE $where_sql";
		if ( !$db->sql_query($sql) )
		{
			mx_message_die(CRITICAL_ERROR, 'Error removing user session(s)', '', __LINE__, __FILE__, $sql);
		}

		if ( !empty($key_sql) )
		{
			$auto_login_key = dss_rand() . dss_rand();

			$current_time = time();

			$sql = 'UPDATE ' . SESSIONS_KEYS_TABLE . "
				SET last_ip = '$user_ip', key_id = '" . md5($auto_login_key) . "', last_login = $current_time
				WHERE key_id = '" . md5($userdata['session_key']) . "'";

			if ( !$db->sql_query($sql) )
			{
				mx_message_die(CRITICAL_ERROR, 'Error updating session key', '', __LINE__, __FILE__, $sql);
			}

			// And now rebuild the cookie
			$sessiondata['userid'] = $user_id;
			$sessiondata['autologinid'] = $auto_login_key;
			$cookiename = $board_config['cookie_name'];
			$cookiepath = $board_config['cookie_path'];
			$cookiedomain = $board_config['cookie_domain'];
			$cookiesecure = $board_config['cookie_secure'];

			setcookie($cookiename . '_data', serialize($sessiondata), $current_time + 31536000, $cookiepath, $cookiedomain, $cookiesecure);

			$userdata['session_key'] = $auto_login_key;
			unset($sessiondata);
			unset($auto_login_key);
		}
	}

	// --------------------------------------------------------------------------------
	// Hook into some phpBB functions to append phpBB path to URLs...
	// --------------------------------------------------------------------------------	
	function append_sid( $url, $non_html_amp = false )
	{ 
		// Replaces same function in sessions.php
		return mx_append_sid( $this->full_url( $url, $non_html_amp ), $non_html_amp );
	}
	
	// --------------------------------------------------------------------------------
	// Hook for auth
	// --------------------------------------------------------------------------------
	function auth($type, $forum_id, $userdata, $f_access = '')
	{
		global $phpbb_auth;
		
		//
		// Replaces same function in sessions.php
		//
		$return_url = $phpbb_auth->auth($type, $forum_id, $userdata, $f_access = '');
				
		return $return_url;
	}	

	/*
	// --------------------------------------------------------------------------------
	// Hook into some phpBB functions to append phpBB path to URLs...
	// --------------------------------------------------------------------------------
	function phpbb_realpath( $url )
	{
		global $phpbb_root_path;
		//
		// Replaces same function in sessions.php
		//
		return str_replace('./', '', $phpbb_root_path . $url);
	}

	// --------------------------------------------------------------------------------
	// Hook into some phpBB functions to append phpBB path to URLs...
	// --------------------------------------------------------------------------------
	function opendir( $url )
	{
		global $phpbb_root_path;
		//
		// Replaces same function in sessions.php
		//
		return @opendir(str_replace('./', '', $phpbb_root_path . $url));
	}

	// --------------------------------------------------------------------------------
	// Hook into some phpBB functions to append phpBB path to URLs...
	// --------------------------------------------------------------------------------
	function is_file( $url )
	{
		global $phpbb_root_path;
		//
		// Replaces same function in sessions.php
		//
		return is_file(str_replace('./', '', $phpbb_root_path . $url));
	}

	// --------------------------------------------------------------------------------
	// Hook into some phpBB functions to append phpBB path to URLs...
	// --------------------------------------------------------------------------------
	function is_link( $url )
	{
		global $phpbb_root_path;
		//
		// Replaces same function in sessions.php
		//
		return is_link(str_replace('./', '', $phpbb_root_path . $url));
	}
	*/

	// --------------------------------------------------------------------------------
	//
	// --------------------------------------------------------------------------------
	function redirect($url)
	{
		//
		// Replaces same function in function.php
		
		global $db;

		if ( !empty( $db ) )
		{
			$db->sql_close();
		} 
		// redirect() is often called passing an append_sid URL as its first argument.
		// However, this is not the case in some places at viewtopic.php, others?...
		// So, we should detect if we have already processed URL or not.
		if ( substr( $url, 0, 5 ) != 'http:' )
		{
			$url = $this->full_url( $url );
		}
		
		//if ($_GET['phpbb_script'])
		//{
		//	exit;
		//}		

		if ( @preg_match( '/Microsoft|WebSTAR|Xitami/', getenv( 'SERVER_SOFTWARE' ) ) )
		{
			header( 'Refresh: 0; URL=' . $url );
			echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">' . "\n" . '<html><head>' . "\n" . '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">' . "\n" . '<meta http-equiv="refresh" content="0; url=' . $url . '">' . "\n" . '<title>Redirect</title>' . "\n" . '<script language="javascript" type="text/javascript">' . "\n" . '<!--' . "\n" . 'if( document.images ) {' . "\n" . "\t" . 'parent.location.replace("' . $url . '");' . "\n" . '} else {' . "\n" . "\t" . 'parent.location.href = "' . $url . '";' . "\n" . '}' . "\n" . '// -->' . "\n" . '</script>' . "\n" . '</head>' . "\n" . '<body>' . "\n" . '<div align="center">If your browser does not support meta redirection please click ' . '<a href="' . $url . '">HERE</a> to be redirected</div>' . "\n" . '</body></html>';
			exit;
		}
		@header( 'Location: ' . $url );
		exit;
	}

	// --------------------------------------------------------------------------------
	// Replaces same function in function.php
	// --------------------------------------------------------------------------------
	function make_jumpbox( $action, $match_forum_id = 0 )
	{ 
		

		// Replaces same function in function.php
		
		make_jumpbox( $this->phpbb_url . $action, $match_forum_id );
	}
	

	//
	// Redeclared, for Simple Subforums MOD
	//
	function make_jumpbox_ref($action, $match_forum_id, &$forums_list)
	{
		global $template, $userdata, $lang, $db, $nav_links, $phpEx, $SID;

		$sql = "SELECT c.cat_id, c.cat_title, c.cat_order
			FROM " . CATEGORIES_TABLE . " c, " . FORUMS_TABLE . " f
			WHERE f.cat_id = c.cat_id
			GROUP BY c.cat_id, c.cat_title, c.cat_order
			ORDER BY c.cat_order";
		if ( !($result = $db->sql_query($sql)) )
		{
			mx_message_die(GENERAL_ERROR, "Couldn't obtain category list.", "", __LINE__, __FILE__, $sql);
		}

		$category_rows = array();
		while ( $row = $db->sql_fetchrow($result) )
		{
			$category_rows[] = $row;
		}

		if ( $total_categories = count($category_rows) )
		{
			$sql = "SELECT *
				FROM " . FORUMS_TABLE . "
				ORDER BY cat_id, forum_order";
			if ( !($result = $db->sql_query($sql)) )
			{
				mx_message_die(GENERAL_ERROR, 'Could not obtain forums information', '', __LINE__, __FILE__, $sql);
			}

			$boxstring = '<select name="' . POST_FORUM_URL . '" onchange="if(this.options[this.selectedIndex].value != -1){ forms[\'jumpbox\'].submit() }"><option value="-1">' . $lang['Select_forum'] . '</option>';

			$forum_rows = array();
			while ( $row = $db->sql_fetchrow($result) )
			{
				$forum_rows[] = $row;

				// Begin Simple Subforums MOD
				$forums_list[] = $row;
				// End Simple Subforums MOD
			}

			if ( $total_forums = count($forum_rows) )
			{
				for($i = 0; $i < $total_categories; $i++)
				{
					$boxstring_forums = '';
					for($j = 0; $j < $total_forums; $j++)
					{
						if ( !$forum_rows[$j]['forum_parent'] && $forum_rows[$j]['cat_id'] == $category_rows[$i]['cat_id'] && $forum_rows[$j]['auth_view'] <= AUTH_REG )
						{

//						if ( !$forum_rows[$j]['forum_parent'] && $forum_rows[$j]['cat_id'] == $category_rows[$i]['cat_id'] && $is_auth[$forum_rows[$j]['forum_id']]['auth_view'] )
//						{

								// Begin Simple Subforums MOD
								$id = $forum_rows[$j]['forum_id'];
								// End Simple Subforums MOD

							$selected = ( $forum_rows[$j]['forum_id'] == $match_forum_id ) ? 'selected="selected"' : '';
							$boxstring_forums .=  '<option value="' . $forum_rows[$j]['forum_id'] . '"' . $selected . '>' . $forum_rows[$j]['forum_name'] . '</option>';

							//
							// Add an array to $nav_links for the Mozilla navigation bar.
							// 'chapter' and 'forum' can create multiple items, therefore we are using a nested array.
							//
							$nav_links['chapter forum'][$forum_rows[$j]['forum_id']] = array (
								'url' => mx_append_sid("viewforum.$phpEx?" . POST_FORUM_URL . "=" . $forum_rows[$j]['forum_id']),
								'title' => $forum_rows[$j]['forum_name']
							);

							// Begin Simple Subforums MOD
							for( $k = 0; $k < $total_forums; $k++ )
							{
								if ( $forum_rows[$k]['forum_parent'] == $id && $forum_rows[$k]['cat_id'] == $category_rows[$i]['cat_id'] && $forum_rows[$k]['auth_view'] <= AUTH_REG )
								{
//								if ( $forum_rows[$k]['forum_parent'] == $id && $forum_rows[$k]['cat_id'] == $category_rows[$i]['cat_id'] && $is_auth[$forum_rows[$k]['forum_id']]['auth_view'] )
//								{
									$selected = ( $forum_rows[$k]['forum_id'] == $match_forum_id ) ? 'selected="selected"' : '';
									$boxstring_forums .=  '<option value="' . $forum_rows[$k]['forum_id'] . '"' . $selected . '>-- ' . $forum_rows[$k]['forum_name'] . '</option>';

									//
									// Add an array to $nav_links for the Mozilla navigation bar.
									// 'chapter' and 'forum' can create multiple items, therefore we are using a nested array.
									//
									$nav_links['chapter forum'][$forum_rows[$k]['forum_id']] = array (
										'url' => mx_append_sid("viewforum.$phpEx?" . POST_FORUM_URL . "=" . $forum_rows[$k]['forum_id']),
										'title' => $forum_rows[$k]['forum_name']
									);

								}
							}
							// End Simple Subforums MOD

						}
					}

					if ( $boxstring_forums != '' )
					{
						$boxstring .= '<option value="-1">&nbsp;</option>';
						$boxstring .= '<option value="-1">' . $category_rows[$i]['cat_title'] . '</option>';
						$boxstring .= '<option value="-1">----------------</option>';
						$boxstring .= $boxstring_forums;
					}
				}
			}

			$boxstring .= '</select>';
		}
		else
		{
			$boxstring .= '<select name="' . POST_FORUM_URL . '" onchange="if(this.options[this.selectedIndex].value != -1){ forms[\'jumpbox\'].submit() }"></select>';
		}

		// Let the jumpbox work again in sites having additional session id checks.
//		if ( !empty($SID) )
//		{
			$boxstring .= '<input type="hidden" name="sid" value="' . $userdata['session_id'] . '" />';
//		}

		$template->set_filenames(array(
			'jumpbox' => 'jumpbox.html')
		);
		$template->assign_vars(array(
			'L_GO' => $lang['Go'],
			'L_JUMP_TO' => $lang['Jump_to'],
			'L_SELECT_FORUM' => $lang['Select_forum'],

			'S_JUMPBOX_SELECT' => $boxstring,
			'S_JUMPBOX_ACTION' => mx_append_sid($action))
		);
		$template->assign_var_from_handle('JUMPBOX', 'jumpbox');

		return;
	}


	// --------------------------------------------------------------------------------
	// Replaces same function in function.php
	// --------------------------------------------------------------------------------
	function generate_pagination($base_url, $num_items, $per_page, $start_item, $add_prevnext_text = true)
	{
		//
		// Replaces same function in function.php
		//
		return mx_generate_pagination($this->full_url( $base_url ), $num_items, $per_page, $start_item, $add_prevnext_text);
	}
	
	// --------------------------------------------------------------------------------
	// Replaces same function in bbcode.php
	// --------------------------------------------------------------------------------
	function smilies_pass($message)
 	{
		global $mx_bbcode;	
		//
		// Replaces same function in bbcode.php
		//
		
		global $board_config;
		$smilies_path = $board_config['smilies_path'];
		$board_config['smilies_path'] = $this->phpbb_url . $board_config['smilies_path'];
		$message = smilies_pass( $message );
		$board_config['smilies_path'] = $smilies_path;
		return $message;
	}
	
	// --------------------------------------------------------------------------------
	// Replaces same function in functions_post.php
	// --------------------------------------------------------------------------------
	function generate_smilies($mode, $page_id)
	{ 
		//
		// Replaces same function in functions_post.php
		// $mx_bbcode;
		global $board_config, $template, $phpEx;
		$smilies_path = $board_config['smilies_path'];
		$board_config['smilies_path'] = $this->phpbb_url . $board_config['smilies_path'];
		generate_smilies( $mode, $page_id );
		$board_config['smilies_path'] = $smilies_path;
		$template->assign_vars( array( 'U_MORE_SMILIES' => $this->append_sid( "posting.$phpEx?mode=smilies" ) ) 
			);
	}

	//
	// [Remove?]
	//
	function username_search($search_match)
	{
		global $db, $board_config, $template, $lang, $images, $theme, $phpEx, $phpbb_root_path;
		global $starttime, $gen_simple_header;
		global $mx_request_vars, $mx_root_path, $mx_user, $mx_page;

		$gen_simple_header = TRUE;

		$username_list = '';
		if ( !empty($search_match) )
		{
			$username_search = preg_replace('/\*/', '%', phpbb_clean_username($search_match));

			$sql = "SELECT username
				FROM " . USERS_TABLE . "
				WHERE username LIKE '" . str_replace("\'", "''", $username_search) . "' AND user_id <> " . ANONYMOUS . "
				ORDER BY username";
			if ( !($result = $db->sql_query($sql)) )
			{
				mx_message_die(GENERAL_ERROR, 'Could not obtain search results', '', __LINE__, __FILE__, $sql);
			}

			if ( $row = $db->sql_fetchrow($result) )
			{
				do
				{
					$username_list .= '<option value="' . $row['username'] . '">' . $row['username'] . '</option>';
				}
				while ( $row = $db->sql_fetchrow($result) );
			}
			else
			{
				$username_list .= '<option>' . $lang['No_match']. '</option>';
			}
			$db->sql_freeresult($result);
		}

		$page_title = $lang['Search'];

		//include($mx_root_path . 'includes/page_header.'.$phpEx);

		$template->set_filenames(array(
			'search_user_body' => 'search_username.html')
		);

		$template->assign_vars(array(
			'USERNAME' => (!empty($search_match)) ? phpbb_clean_username($search_match) : '',

			'L_CLOSE_WINDOW' => $lang['Close_window'],
			'L_SEARCH_USERNAME' => $lang['Find_username'],
			'L_UPDATE_USERNAME' => $lang['Select_username'],
			'L_SELECT' => $lang['Select'],
			'L_SEARCH' => $lang['Search'],
			'L_SEARCH_EXPLAIN' => $lang['Search_author_explain'],
			'L_CLOSE_WINDOW' => $lang['Close_window'],

			'S_USERNAME_OPTIONS' => $username_list,
			'S_SEARCH_ACTION' => $this->append_sid("search.$phpEx?mode=searchuser"))
		);

		if ( $username_list != '' )
		{
			$template->assign_block_vars('switch_select_name', array());
		}

		$template->pparse('search_user_body');

		//include($mx_root_path . 'includes/page_tail.'.$phpEx);

		return;
	}
	
	function decode_message($custom_bbcode_uid = '', $update_this_message = true)
	{	
		globaL $parse_message;
		
		$parse_message->decode_message($custom_bbcode_uid, $update_this_message);
	}

	// --------------------------------------------------------------------------------
	// Fix required by ported posting.php after submiting changes (new,edit,delete)...
	// --------------------------------------------------------------------------------
	function fix_posting_msg( &$message, &$meta )
	{
		//
		// Fix required by ported posting.php after submiting changes (new,edit,delete)...
		//
		$script_names_ary = array( 'viewtopic', 'viewforum' );
		$this->_force_full_url( $message, $script_names_ary );
		$this->_force_full_url( $meta, $script_names_ary );
	}

	// --------------------------------------------------------------------------------
	// Fix required by ported viewtopic.php for secured links to modcp and posting...
	// --------------------------------------------------------------------------------
	function fix_viewtopic_urls( $viewtopic_string )
	{
		//
		// Fix required by ported viewtopic.php for secured links to modcp and posting...
		//
		$script_names_ary = array( 'modcp', 'posting', 'viewtopic' );
		$this->_force_full_url( $viewtopic_string, $script_names_ary );
		return $viewtopic_string;
	}

	// --------------------------------------------------------------------------------
	// Force full url
	// --------------------------------------------------------------------------------
	function _force_full_url(&$string, &$script_names_ary)
	{
		for( $i = 0; $i < count( $script_names_ary ); $i++ )
		{
			$script_name = $script_names_ary[$i] . '.';
			$string = str_replace( $script_name, $this->phpbb_url . $script_name, $string );
		}
	}

	// --------------------------------------------------------------------------------
	// mxbb_reformat - fixup (truncates) urls, images and words (wrapping) for a narrow column layout
	// --------------------------------------------------------------------------------
	function mxbb_magic($mytext = '')
	{
		global $board_config;

		$mytext = $this->_magic_url( $mytext );
		$mytext = $this->_magic_img( $mytext );
		$mytext = $this->_word_wrap_pass( $mytext );

		return $mytext;
	}

	// --------------------------------------------------------------------------------
	// Replace magic urls of form http://xxx.xxx., www.xxx. and xxx@xxx.xxx.
	// Cuts down displayed size of link if over 50 chars, turns absolute links
	// into relative versions when the server/script path matches the link
	// --------------------------------------------------------------------------------
	function _magic_url($url)
	{
		global $board_config;

		if ( $url )
		{
			$server_protocol = ( $board_config['cookie_secure'] ) ? 'https://' : 'http://';
			$server_port = ( $board_config['server_port'] <> 80 ) ? ':' . trim( $board_config['server_port'] ) . '/' : '/';

			$match = array();
			$replace = array();

			//
			// relative urls for this board
			//
			$match[] = '#(^|[\n ])' . $server_protocol . trim( $board_config['server_name'] ) . $server_port . preg_replace( '/^\/?(.*?)(\/)?$/', '$1', trim( $board_config['script_path'] ) ) . '/([^ \t\n\r <"\']+)#i';
			$replace[] = '<a href="$1" target="_blank">$1</a>';

			//
			// matches a xxxx://aaaaa.bbb.cccc. ...
			//
			$match[] = '#(^|[\n ])([\w]+?://.*?[^ \t\n\r<"]*)#ie';
			$replace[] = "'\$1<a href=\"\$2\" target=\"_blank\">' . ((strlen('\$2') > 25) ? substr(str_replace('http://','','\$2'), 0, 17) . '...' : '\$2') . '</a>'";

			//
			// matches a "www.xxxx.yyyy[/zzzz]" kinda lazy URL thing
			//
			$match[] = '#(^|[\n ])(www\.[\w\-]+\.[\w\-.\~]+(?:/[^ \t\n\r<"]*)?)#ie';
			$replace[] = "'\$1<a href=\"http://\$2\" target=\"_blank\">' . ((strlen('\$2') > 25) ? substr(str_replace(' ', '%20', str_replace('http://','', '\$2')), 0, 17) . '...' : '\$2') . '</a>'";

			//
			// matches an email@domain type address at the start of a line, or after a space.
			//
			$match[] = '#(^|[\n ])([a-z0-9&\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)#ie';
			$replace[] = "'\$1<a href=\"mailto:\$2\">' . ((strlen('\$2') > 25) ? substr('\$2', 0, 15) . ' ... ' . substr('\$2', -5) : '\$2') . '</a>'";

			$url = preg_replace( $match, $replace, $url );

			//
			// Also fix already tagged links
			//
			$url = preg_replace( "/<a href=(.*?)>(.*?)<\/a>/ie", "(strlen(\"\\2\") > 25 && !eregi(\"<\", \"\\2\") ) ? '<a href='.stripslashes(\"\\1\").'>'.substr(str_replace(\"http://\",\"\",\"\\2\"), 0, 17) . '...</a>' : '<a href='.stripslashes(\"\\1\").'>'.\"\\2\".'</a>'", $url );

			return $url;
		}
		return $url;
	}

	// --------------------------------------------------------------------------------
	// Validates the img for block_size and resizes when needed
	// run within a div tag to ensure the table layout is not broken
	// --------------------------------------------------------------------------------
	function _magic_img( $img )
	{
		global $board_config, $block_size;

		$image_size = '300';
		if ( $img )
		{
			//
			// Also fix already tagged links
			// $img = preg_replace("/<img src=(.*?)(|border(.*?)|alt(.*?))>/ie", "'<br /><br /><center><img src='.stripslashes(\"\\1\").' width=\"'.makeImgWidth(trim(stripslashes(\"\\1\"))).'\" ></center><br />'", $img);
			//
			$img = preg_replace( "/<img src=(.*?)>/ie", "(substr_count(\"\\1\", \"smiles\") > 0 ) ? '<img src='.stripslashes(\"\\1\").'>' :

			'<div style=\" overflow: hidden; margin: 0px; padding: 0px; float: left; \">
			<img class=\"noenlarge\" src='.stripslashes(\"\\1\").' border=\"0\"  OnLoad=\"if(this.width > $image_size) { this.width = $image_size }\" onclick = \"full_img( this.src )\" alt=\" Click to enlarge \">
			</div>'", $img );

			return $img;
		}
		return $img;
	}

	// --------------------------------------------------------------------------------
	// Force Word Wrapping (by TerraFrost)
	// --------------------------------------------------------------------------------
	function _word_wrap_pass( $message )
	{
		$tempText = "";
		$finalText = "";
		$curCount = $tempCount = 0;
		$longestAmp = 9;
		$inTag = false;
		$ampText = "";

		for ( $num = 0;$num < strlen( $message );$num++ )
		{
			$curChar = $message{$num};

			if ( $curChar == "<" )
			{
				for ( $snum = 0;$snum < strlen( $ampText );$snum++ )
				$this->_addWrap( $ampText{$snum}, $ampText{$snum+1}, $finalText, $tempText, $curCount, $tempCount );
				$ampText = "";
				$tempText .= "<";
				$inTag = true;
			}
			elseif ( $inTag && $curChar == ">" )
			{
				$tempText .= ">";
				$inTag = false;
			}
			elseif ( $inTag )
			{
				$tempText .= $curChar;
			}
			elseif ( $curChar == "&" )
			{
				for ( $snum = 0;$snum < strlen( $ampText );$snum++ )
				$this->_addWrap( $ampText{$snum}, $ampText{$snum+1}, $finalText, $tempText, $curCount, $tempCount );
				$ampText = "&";
			}
			elseif ( strlen( $ampText ) < $longestAmp && $curChar == ";" && ( strlen( html_entity_decode( "$ampText;" ) ) == 1 || preg_match( '/^&#[0-9][0-9]*$/', $ampText ) ) )
			{
				$this->_addWrap( "$ampText;", $message{$num+1}, $finalText, $tempText, $curCount, $tempCount );
				$ampText = "";
			}
			elseif ( strlen( $ampText ) >= $longestAmp || $curChar == ";" )
			{
				for ( $snum = 0;$snum < strlen( $ampText );$snum++ )
				$this->_addWrap( $ampText{$snum}, $ampText{$snum+1}, $finalText, $tempText, $curCount, $tempCount );
				$this->_addWrap( $curChar, $message{$num+1}, $finalText, $tempText, $curCount, $tempCount );
				$ampText = "";
			}
			elseif ( strlen( $ampText ) != 0 && strlen( $ampText ) < $longestAmp )
			{
				$ampText .= $curChar;
			}
			else
			{
				$this->_addWrap( $curChar, $message{$num+1}, $finalText, $tempText, $curCount, $tempCount );
			}
		}

		return $finalText . $tempText;
	}

	function _addWrap( $curChar, $nextChar, &$finalText, &$tempText, &$curCount, &$tempCount )
	{
		//
		// Settings
		//
		$softHyph = "&shy;";
		// $softHyph = "&emsp;";
		$maxChars = 10;
		$wrapProhibitedChars = "([{!;,:?}])";

		if ( $curChar == " " || $curChar == "\n" )
		{
			$finalText .= $tempText . $curChar;
			$tempText = "";
			$curCount = 0;
			$curChar = "";
		}
		elseif ( $curCount >= $maxChars )
		{
			$finalText .= $tempText . $softHyph;
			$tempText = "";
			$curCount = 1;
		}
		else
		{
			$tempText .= $curChar;
			$curCount++;
		}

		//
		// the following code takes care of (unicode) characters prohibiting non-mandatory breaks directly before them.
		// $curChar isn't a " " or "\n"
		//
		if ( $tempText != "" && $curChar != "" )
		{
			$tempCount++;
		}
		// $curChar is " " or "\n", but $nextChar prohibits wrapping.
		elseif ( ( $curCount == 1 && strstr( $wrapProhibitedChars, $curChar ) !== false ) || ( $curCount == 0 && $nextChar != "" && $nextChar != " " && $nextChar != "\n" && strstr( $wrapProhibitedChars, $nextChar ) !== false ) )
		{
			$tempCount++;
		}
		// $curChar and $nextChar aren't both either " " or "\n"
		elseif ( !( $curCount == 0 && ( $nextChar == " " || $nextChar == "\n" ) ) )
		{
			$tempCount = 0;
		}

		if ( $tempCount >= $maxChars && $tempText == "" )
		{
			$finalText .= "&nbsp;";
			$tempCount = 1;
			$curCount = 2;
		}

		if ( $tempText == "" && $curCount > 0 )
		{
			$finalText .= $curChar;
		}
	}

	//
	// [Remove?]
	//
	function show_online()
	{
		global $db, $phpbb_auth, $template, $theme, $mx_user, $userdata, $lang, $board_config, $phpEx;
		//
		// NOTE: This code is copied from page_header.php, and only called for the index page. If you have an online MOD installed, this is the code to update
		//
		// Get basic (usernames + totals) online
		// situation
		//

		// Get users online list ... if required
		$logged_visible_online = 0;
		$logged_hidden_online = 0;
		$guests_online = 0;		
		$l_online_users = $online_userlist = $l_online_record = '';

		if ($board_config['load_online'] && $board_config['load_online_time'] && $display_online_list)
		{
			$logged_visible_online = $logged_hidden_online = $guests_online = $prev_user_id = 0;
			$prev_session_ip = $reading_sql = '';

			if (!empty($_REQUEST['f']))
			{
				$f = $mx_request_vars->request('f', MX_TYPE_INT, 0);

				$reading_sql = ' AND s.session_page ' . $db->sql_like_expression("{$db->any_char}_f_={$f}x{$db->any_char}");
			}

			// Get number of online guests
			if (!$board_config['load_online_guests'])
			{
				if ($db->sql_layer === 'sqlite')
				{
					$sql = 'SELECT COUNT(session_ip) as num_guests
						FROM (
							SELECT DISTINCT s.session_ip
								FROM ' . SESSIONS_TABLE . ' s
								WHERE s.session_user_id = ' . ANONYMOUS . '
									AND s.session_time >= ' . (time() - ($board_config['load_online_time'] * 60)) .
									$reading_sql .
						')';
				}
				else
				{
					$sql = 'SELECT COUNT(DISTINCT s.session_ip) as num_guests
						FROM ' . SESSIONS_TABLE . ' s
						WHERE s.session_user_id = ' . ANONYMOUS . '
							AND s.session_time >= ' . (time() - ($board_config['load_online_time'] * 60)) .
						$reading_sql;
				}
				$result = $db->sql_query($sql);
				$guests_online = (int) $db->sql_fetchfield('num_guests');
				$db->sql_freeresult($result);
			}

			$sql = 'SELECT u.username, u.username_clean, u.user_id, u.user_type, u.user_allow_viewonline, u.user_colour, s.session_ip, s.session_viewonline
				FROM ' . USERS_TABLE . ' u, ' . SESSIONS_TABLE . ' s
				WHERE s.session_time >= ' . (time() - (intval($board_config['load_online_time']) * 60)) .
					$reading_sql .
					((!$board_config['load_online_guests']) ? ' AND s.session_user_id <> ' . ANONYMOUS : '') . '
					AND u.user_id = s.session_user_id
				ORDER BY u.username_clean ASC, s.session_ip ASC';
			$result = $db->sql_query($sql);

			while ($row = $db->sql_fetchrow($result))
			{
				// User is logged in and therefore not a guest
				if ($row['user_id'] != ANONYMOUS)
				{
					// Skip multiple sessions for one user
					if ($row['user_id'] != $prev_user_id)
					{
						if ($row['user_colour'])
						{
							$user_colour = ' style="color:#' . $row['user_colour'] . '"';
							$row['username'] = '<strong>' . $row['username'] . '</strong>';
						}
						else
						{
							$user_colour = '';
						}

						if ($row['session_viewonline'])
						{
							$user_online_link = $row['username'];
							$logged_visible_online++;
						}
						else
						{
							$user_online_link = '<em>' . $row['username'] . '</em>';
							$logged_hidden_online++;
						}

						if (($row['session_viewonline']) || $auth->acl_get('u_viewonline'))
						{
							if ($row['user_type'] <> USER_IGNORE)
							{
								$user_online_link = '<a href="' . append_sid("{$this->phpbb_root_path}memberlist.$phpEx", 'mode=viewprofile&amp;u=' . $row['user_id']) . '"' . $user_colour . '>' . $user_online_link . '</a>';
							}
							else
							{
								$user_online_link = ($user_colour) ? '<span' . $user_colour . '>' . $user_online_link . '</span>' : $user_online_link;
							}

							$online_userlist .= ($online_userlist != '') ? ', ' . $user_online_link : $user_online_link;
						}
					}

					$prev_user_id = $row['user_id'];
				}
				else
				{
					// Skip multiple sessions for one user
					if ($row['session_ip'] != $prev_session_ip)
					{
						$guests_online++;
					}
				}

				$prev_session_ip = $row['session_ip'];
			}
			$db->sql_freeresult($result);

			if (!$online_userlist)
			{
				$online_userlist = $mx_user->lang['NO_ONLINE_USERS'];
			}

			if (empty($_REQUEST['f']))
			{
				$online_userlist = $mx_user->lang['REGISTERED_USERS'] . ' ' . $online_userlist;
			}
			else
			{
				$l_online = ($guests_online == 1) ? $mx_user->lang['BROWSING_FORUM_GUEST'] : $mx_user->lang['BROWSING_FORUM_GUESTS'];
				$online_userlist = sprintf($l_online, $online_userlist, $guests_online);
			}

			$total_online_users = $logged_visible_online + $logged_hidden_online + $guests_online;

			if ($total_online_users > $board_config['record_online_users'])
			{
				phpBB3::set_config('record_online_users', $total_online_users, true);
				phpBB3::set_config('record_online_date', time(), true);
			}

			// Build online listing
			$vars_online = array(
				'ONLINE'	=> array('total_online_users', 'l_t_user_s'),
				'REG'		=> array('logged_visible_online', 'l_r_user_s'),
				'HIDDEN'	=> array('logged_hidden_online', 'l_h_user_s'),
				'GUEST'		=> array('guests_online', 'l_g_user_s')
			);

			foreach ($vars_online as $l_prefix => $var_ary)
			{
				switch (${$var_ary[0]})
				{
					case 0:
						${$var_ary[1]} = $mx_user->lang[$l_prefix . '_USERS_ZERO_TOTAL'];
					break;

					case 1:
						${$var_ary[1]} = $mx_user->lang[$l_prefix . '_USER_TOTAL'];
					break;

					default:
						${$var_ary[1]} = $mx_user->lang[$l_prefix . '_USERS_TOTAL'];
					break;
				}
			}
			unset($vars_online);

			$l_online_users = sprintf($l_t_user_s, $total_online_users);
			$l_online_users .= sprintf($l_r_user_s, $logged_visible_online);
			$l_online_users .= sprintf($l_h_user_s, $logged_hidden_online);
			$l_online_users .= sprintf($l_g_user_s, $guests_online);

			$l_online_record = sprintf($mx_user->lang['RECORD_ONLINE_USERS'], $board_config['record_online_users'], $user->format_date($board_config['record_online_date']));

			$l_online_time = ($board_config['load_online_time'] == 1) ? 'VIEW_ONLINE_TIME' : 'VIEW_ONLINE_TIMES';
			$l_online_time = sprintf($mx_user->lang[$l_online_time], $board_config['load_online_time']);
		}
		else
		{
			$l_online_time = '';
		}

		$template->assign_vars(array(
			// WHO IS ONLINE
			'TOTAL_USERS_ONLINE'			=> $l_online_users,
			'LOGGED_IN_USER_LIST'			=> $online_userlist,
			'RECORD_USERS'					=> $l_online_record,
		
			'L_WHO_IS_ONLINE' => $lang['Who_is_Online'],
			'L_ONLINE_EXPLAIN'	=> $l_online_time,
			'L_WHOSONLINE_ADMIN' => sprintf($lang['Admin_online_color'], '<span style="color:#' . $theme['fontcolor3'] . '">', '</span>'),
			'L_WHOSONLINE_MOD' => sprintf($lang['Mod_online_color'], '<span style="color:#' . $theme['fontcolor2'] . '">', '</span>'),

			'U_VIEWONLINE'			=> ($phpbb_auth->acl_gets('u_viewprofile', 'a_user', 'a_useradd', 'a_userdel')) ? mx3_append_sid("{$this->phpbb_root_path}viewonline.$phpEx") : '',

			//LOGIN
			'L_USERNAME' => $lang['Username'],
			'L_PASSWORD' => $lang['Password'],
			'L_LOGIN_LOGOUT' => $l_login_logout,
			'L_LOGIN' => $lang['Login'],
			'L_LOG_ME_IN' => $lang['Log_me_in'],
			'L_AUTO_LOGIN' => $lang['Log_me_in'],
			'S_LOGIN_ACTION' => $this->append_sid('login.'.$phpEx),
		));
	}

	//
	// Functions for newssuite operation mode
	//
	function phpbb2_auth_cat( $cat_id )
	{
		global $page_id;

		if (empty($this->phpbb_block_map))
		{
			return true;
		}

		return $this->phpbb_block_map[$cat_id] == $page_id;
	}

	//
	// Main method
	//
	function read_file($phpbb_file, $sub_call = false)
	{
		global $phpbb_root_path, $mx_root_path, $phpEx, $template, $_POST, $_GET, $_COOKIE, $_GET, $db, $userdata, $mode, $theme, $lang, $table_prefix, $mx_table_prefix, $board_config, $portal_config;
		global $html_entities_match, $html_entities_replace, $unhtml_specialchars_match, $unhtml_specialchars_replace;
		global $mx_forum, $mx_user, $phpbb_auth, $mx_cache, $user_ip;
		global $server_url; // used globally by usercp_register.php
		
		$phpbb_root_path = $this->phpbb_root_path;
		$mx_root_path = $this->mx_root_path;

		//
		//Check for cash mod
		//
		if (defined('IN_CASHMOD'))
		{
			global $cash, $cm_groups; // allways used globally by cash mod

			switch ( $phpbb_file )
			{
				case 'memberlist':
					@define('CM_MEMBERLIST', true);
					global $cm_memberlist;
					break;
				case 'posting':
					@define('CM_POSTING', true);
					global $cm_posting;
					break;
				case 'viewtopic':
					@define('CM_VIEWTOPIC', true);
					global $cm_viewtopic;
					break;
				case 'viewprofile':
					@define('CM_VIEWPROFILE', true);
					global $cm_viewprofile;
					break;
				default:
					@define('CM_EVENT', true);
			}

			//Include cash mod language file
			if ((@include $phpbb_root_path . "language/lang_" . $default_lang . "/lang_cash.$phpEx") === false)
			{
				if ((@include $phpbb_root_path . "language/lang_english/lang_cash.$phpEx") === false)
				{
					mx_message_die(CRITICAL_ERROR, 'Language file ' . $module_root_path . "language/lang_" . $default_lang . "/lang_cash.$phpEx" . ' couldn\'t be opened.');
				}
			}

			include_once($phpbb_root_path . 'includes/functions_cash.'.$phpEx);
			$template = new Template_plus($phpbb_root_path . 'templates/'. $theme['template_name']);
		}
		
		switch ( $phpbb_file )
		{
			case 'bbcode':
				$code = '';
				$mx_cache->load_file('bbcode', 'phpbb3');
			break;
			
			default:
				$code =	file_get_contents($phpbb_root_path . $phpbb_file . ".$phpEx");
			break;				
		}		

		$code = file_get_contents($phpbb_root_path . $phpbb_file . ".$phpEx");

		//
		// Remove def of IN_PHPBB
		//
		$code = preg_replace('#^(.?define).*(IN_PHPBB).*(\r\n?|\n)#m','// MXP: Removed IN_PHPBB def' . "\n", $code);

		//
		// Remove php tags
		//
		$code = str_replace('<?php','',$code);
		$code = str_replace('?>','',$code);

		//
		// Rewrite the phpbb_root_path
		//
		$code = preg_replace ("/phpbb_root_path = (.*)\;/", "phpbb_root_path = '" . $phpbb_root_path . "';", $code);

		//
		// Commment out the main includes
		//
		//$code = preg_replace('#^(.?include).*(extension).*(\r\n?|\n)#m','// mxBB: Removed include extension.inc' . "\n", $code);
		$code = preg_replace('#^(.?include).*(extension).*(\r\n?|\n)#m','// MXP: Removed include extension.inc' . "\n", $code);
		$code = str_replace("substr(strrchr(__FILE__, '.'), 1);", "'". $phpEx ."';", $code);
		$code = preg_replace('#^(.?include).*(common).*(\r\n?|\n)#m','// MXP: Removed include common.php' . "\n", $code);
	
		
		//
		// Change some includes already exists in MXP
		//		
		$code = preg_replace('#^(.?include).*(auth).*(\r\n?|\n)#m','require($mx_root_path . \'includes/sessions/phpbb3/auth.\' . $phpEx);' . "\n", $code);
		$code = preg_replace('#^(.?include).*(functions_display).*(\r\n?|\n)#m','require($mx_root_path . \'modules/mx_phpbb3blocks/includes/mx_functions_display.\' . $phpEx);' . "\n", $code);
		//$code = preg_replace('#^(.?include).*(message_parser).*(\r\n?|\n)#m','require($mx_root_path . \'includes/shared/phpbb3/includes/message_parser.\' . $phpEx);' . "\n", $code);	
		
		//
		// Remove some includes already included by mxBB
		//
		$code = preg_replace ("/include(.*)functions_select/", "//Include this from MXP", $code);
		$code = preg_replace ("/include(.*)functions_post/", "//Include this from MXP", $code);
		//$code = preg_replace ("/include(.*)bbcode/", "//Include this from MXP", $code);
		//$code = preg_replace ("/include(.*)message_parser/", "//Include this from MXP: message_parser", $code);	

		//
		// Commment out the page_header and page_tail includes
		//
		$code = preg_replace('#^(.?).*(include).*(page_header).*(\r\n?|\n)#m','// MXP: Removed include page_header.php' . "\n", $code);
		$code = preg_replace('#^(.?).*(include).*(page_tail).*(\r\n?|\n)#m',"// MXP: Removed include page_tail.php" . "\n" . '//return;' . "\n", $code);
		$code = str_replace("include($phpbb_root_path . 'includes/page_header.'.$phpEx);", "// MXP: Removed include page_header.php", $code);
		//$code = str_replace('page_footer(', '//page_footer(', $code);
		$code = str_replace('page_footer();', "\n" . '$mx_forum->common_template_vars();' . "\n" . '$template->pparse(\'body\');', $code);
		//$code = str_replace('$template->pparse(;', "\n" . '$mx_forum->common_template_vars();' . "\n" . '$template->pparse(', $code);


		//
		// Comment out the Start Session area
		//
		$code = preg_replace('#^(.?//).*(Start|Set).*(session).*(\r\n?|\n)#m','/*' . "\n", $code);
		$code = preg_replace('#^(.?//).*(End).*(session).*(\r\n?|\n)#m', '*/' . "\n", $code);
		$code = preg_replace( '#^(.?//).*(Start|set).*(session).*(\r\n?|\n)(.*)(.?//).*(End).*(session).*(\r\n?|\n)#m', '/** Start|set session \5 End session */', $code);

		//
		// Replace common phpBB functions with MXP alternatives
		//
		$code = str_replace('append_sid(', '$mx_forum->append_sid(', $code);
		$code = str_replace('session_reset_keys(', '$mx_forum->session_reset_keys(', $code);
		$code = str_replace('auth(', '$mx_forum->auth(', $code);
			
		$code = str_replace('$auth->acl(', '// MXP: Removed $auth->acl(', $code);
		$code = str_replace('$phpbb_root_path =', '// MXP: Removed $phpbb_root_path =', $code);
		$code = str_replace('$phpEx =', '// MXP: Removed $phpEx =', $code);
		$code = str_replace('$user->session_begin()', '// MXP: Removed $user->session_begin()', $code);
		//$code = str_replace('$user->setup(\'viewforum', '// MXP: Removed $user->setup(\'viewforum', $code);		
		$code = str_replace('\'body\'', '\'phpbb3_body\'', $code);		
		if (strpos($code, '$auth') !== false)
		{
			$code = str_replace('* @ignore', '* MXP rewrite of global, to instance $this in this function */' . "\n /** \n * @ignore \n */ \n" . 'global $phpbb_auth;' . "\n " .'/*', $code);		
			$code = str_replace('$auth', '$phpbb_auth', $code);		
		}		
		
		if (strpos($code, '$user') !== false)
		{
			$code = str_replace('* @ignore', '* MXP rewrite of global, to instance $this in this function */' . "\n /** \n * @ignore \n */ \n " . 'global $mx_user;' . "\n " .'/*', $code);		
			$code = str_replace('$user', '$mx_user', $code);		
		}			
		
		//$code = str_replace('@phpbb_realpath(', '$mx_forum->phpbb_realpath(', $code);
		//$code = str_replace('@opendir(', '$mx_forum->opendir(', $code);
		//$code = str_replace('is_file(', '$mx_forum->is_file(', $code);
		//$code = str_replace('is_link(', '$mx_forum->is_link(', $code);
		
		$code = str_replace('$config', '$board_config', $code);
		$code = str_replace('$cache', '$mx_cache', $code);
		$code = str_replace('$user', '$mx_user', $code);
		$code = str_replace('$auth', '$phpbb_auth', $code);
		$code = str_replace('session_begin()', 'init($user_ip, PAGE_INDEX)', $code);
		$code = str_replace('login_box(\'\', $mx_user->lang[\'LOGIN_VIEWFORUM\']);', 'append_sid(\'login.\'.$phpEx);', $code);		
		
		/*
		*/
		$code = str_replace('display_forums(', 'mx_display_forums(', $code);
		$code = str_replace('generate_forum_rules(', 'mx_generate_forum_rules(', $code);
		$code = str_replace('generate_text_for_display(', 'mx_generate_text_for_display(', $code); 
		$code = str_replace('generate_forum_nav(', 'mx_generate_forum_nav(', $code);
		$code = str_replace('get_forum_parents(', 'mx_get_forum_parents(', $code);
		$code = str_replace('get_moderators(', 'mx_get_moderators(', $code);
		$code = str_replace('gen_forum_auth_level(', 'mx_gen_forum_auth_level(', $code);
		$code = str_replace('topic_status(', 'mx_topic_status(', $code);
		$code = str_replace('display_custom_bbcodes(', 'mx_display_custom_bbcodes(', $code);
		$code = str_replace('display_reasons(', 'mx_display_reasons(', $code);
		$code = str_replace('display_user_activity(', 'mx_display_user_activity(', $code);
		$code = str_replace('watch_topic_forum(', 'mx_watch_topic_forum(', $code);
		$code = str_replace('get_user_rank(', 'mx_get_user_rank(', $code);
		$code = str_replace('get_user_avatar(', 'mx_get_user_avatar(', $code);
		$code = str_replace('get_username_string(', 'mx_get_username_string(', $code);
		//$code = str_replace('append_sid(', 'mx3_append_sid(', $code);
		/**/
		$code = str_replace('redirect(', '$mx_forum->redirect(', $code);
		$code = str_replace('make_jumpbox(', '$mx_forum->make_jumpbox(', $code);
		$code = str_replace('make_jumpbox_ref(', '$mx_forum->make_jumpbox_ref(', $code);
		$code = str_replace('smilies_pass(', '$mx_forum->smilies_pass(', $code);
		$code = str_replace('generate_smilies(\'inline\',', '$mx_forum->generate_smilies(\'inline\',', $code);
		$code = str_replace('generate_pagination(', '$mx_forum->generate_pagination(', $code);
		$code = str_replace('$images[', '$mx_forum->images[', $code);
		$code = str_replace('request_var(', 'phpBB3::request_var(', $code);
		$code = str_replace('login_forum_box(', 'phpBB3::login_forum_box(', $code);
		$code = str_replace('update_forum_tracking_info(', 'phpBB3::update_forum_tracking_info(', $code);
		$code = str_replace('login_box(', 'phpBB3::login_box(', $code);
		$code = str_replace('gen_sort_selects(', 'phpBB3::gen_sort_selects(', $code);
		$code = str_replace('on_page(', 'phpBB3::on_page(', $code);
		$code = str_replace('get_topic_tracking(', 'phpBB3::get_topic_tracking(', $code);			
		$code = str_replace('censor_text(', 'phpBB3::censor_text(', $code);
		$code = str_replace('bump_topic_allowed(', 'phpBB3::bump_topic_allowed(', $code);
		$code = str_replace('bbcode_nl2br(', 'phpBB3::bbcode_nl2br(', $code);
		$code = str_replace('smiley_text(', 'phpBB3::smiley_text(', $code);		
		$code = str_replace('add_form_key(', 'phpBB3::add_form_key(', $code);
		$code = str_replace('message_die(', 'mx_message_die(', $code);
		$code = str_replace(' unique_id(', ' phpBB3::unique_id(', $code);
		$code = str_replace(' decode_message(', ' phpBB3::decode_message(', $code);		

		//
		// Remove some includes already included by MXP
		//
		$code = preg_replace ("/include(.*)functions\./", "//", $code);
		$code = preg_replace ("/include(.*)functions_select./", "//", $code);
		$code = preg_replace ("/include(.*)functions_post./", "//", $code);
		$code = preg_replace ("/include(.*)bbcode./", "//", $code);
		$code = preg_replace ("/include(.*)auth./", "//", $code);
		
		
		//Fix Login path for anonymouse users who go direct to forum
		$code = str_replace('"login.', '$mx_root_path . "login.', $code);

		//
		// If functions are declared, pass the mx_forum class
		// NOTE: This regex will only work if "global" is stated on a new line...
		//
		$code = preg_replace('#^(.?).*($global)(.*?)($)(.*?)(\r\n?|\n)#m', '// MXP rewrite of global, to instance $this in this function' . "\n " . 'global $mx_forum, \\3', $code);
		//$code = str_replace('* @ignore', '* MXP rewrite of global, to instance $this in this function */' . "\n " . 'global $mx_forum;' . "\n " .'/*', $code);		
		
		switch ($phpbb_file)
		{
			case 'viewforum':
			case 'posting':
			case 'modcp':
			case 'search':			
				$code = preg_replace('#^(.?).*(global $)(.*?)($)(.*?)(\r\n?|\n)#m', '// MXP rewrite of $global, to instance $this-> in this function' . "\n " . 'global $mx_forum, \\3', $code);			
			break;

			default:
				$code = preg_replace('#^(.?).*($global)(.*?)($)(.*?)(\r\n?|\n)#m', '// MXP rewrite of $global, to instance $this-> in this function' . "\n " . 'global $mx_forum, \\3', $code);			
			break;
		}		

		//
		// Add the phpbb_root_path to the append_sid function - if not already there
		//
		$code = str_replace('$template->pparse(', "\n" . '$mx_forum->common_template_vars();' . "\n" . '$template->pparse(', $code);
		

		switch ($phpbb_file)
		{
			case 'index':
				//$code = str_replace('$display_forums = true;', '$display_forums = $mx_forum->phpbb2_auth_cat($forum_data[$j][\'forum_id\']);', $code); // For old phpBB versions
				$code = str_replace('$is_auth_ary[$forum_data[$i][\'forum_id\']][\'auth_view\']', '$is_auth_ary[$forum_data[$i][\'forum_id\']][\'auth_view\'] && $mx_forum->phpbb2_auth_cat($forum_data[$i][\'forum_id\'])', $code); // For phpBB 2.0.21 and later
				$code = str_replace('$is_auth_ary[$forum_id][\'auth_view\']', '$is_auth_ary[$forum_id][\'auth_view\'] && $mx_forum->phpbb2_auth_cat($forum_data[$i][\'forum_id\'])', $code); // For phpBB 2.0.21 and later
				$code = str_replace('body', 'block_forum', $code);
				// Hack for XS/Simple SubForum
				$code = str_replace('$images = unserialize($item[\'FORUM_FOLDERS\'])', '$mx_forum->images = unserialize($item[\'FORUM_FOLDERS\'])', $code);
			break;

			case 'viewforum':
				$code = str_replace('<a href=\"modcp', '<a href=\"'.$phpbb_root_path.'modcp', $code);
			break;

			case 'viewtopic':
				//
				// For narrow columns
				//
				//$code = str_replace('$message = str_replace("\n"', '$message = $mx_forum->mxbb_magic( $message );' . "\n" . '$message = str_replace("\n"', $code);

				$code = str_replace('<a href=\"modcp', '<a href=\"'.$phpbb_root_path.'modcp', $code);
				$code = str_replace('<a href=\"viewtopic', '<a href=\"'.$phpbb_root_path.'viewtopic', $code);
				$code = str_replace('<a href=\"bin', '<a href=\"'.$phpbb_root_path.'bin', $code);
				$code = str_replace('$temp_url = "modcp.', '$temp_url = "' . $phpbb_root_path . 'modcp.', $code);
				$code = str_replace('$temp_url = "posting.', '$temp_url = "' . $phpbb_root_path . 'posting.', $code);
				$code = str_replace('$temp_url = "bin.', '$temp_url = "' . $phpbb_root_path . 'bin.', $code);

				//
				// Avatars and ranks
				//
				$code = str_replace('<img src="\' . $board_config[\'avatar_path\']', '<img src="\' . $phpbb_root_path . $board_config[\'avatar_path\']', $code);
				$code = str_replace('<img src="\' . $board_config[\'avatar_gallery_path\']', '<img src="\' . $phpbb_root_path . $board_config[\'avatar_gallery_path\']', $code);
				$code = str_replace('<img src="\' . $ranksrow[$j][\'rank_image\']', '<img src="\' . $phpbb_root_path . $ranksrow[$j][\'rank_image\']', $code);
				break;

			case 'faq':
				$code = str_replace('\'U_FAQ_LINK\' => \'#' . $faq_block[$i][$j]['id'], '\'U_FAQ_LINK\' => \''.$phpbb_root_path.'faq.php#' . $faq_block[$i][$j]['id'], $code);

			break;

			case 'profile':
				//
				// Sub Calls Fix
				//
				$code = str_replace('exit;', 'return;', $code);
			break;

			case 'search':
				//$code = str_replace('username_search(', '$mx_forum->username_search(', $code);
			break;

			case 'modcp':
				$code = str_replace('"modcp.$phpEx?', '$phpbb_root_path."modcp.$phpEx?', $code);
				$code = str_replace('"viewtopic.$phpEx?', '$phpbb_root_path."viewtopic.$phpEx?', $code);
			break;

			case 'posting':
			
			break;

			case 'privmsg':
			break;

			case 'includes/usercp_register':
				//
				// Avatars and ranks
				//
				$code = str_replace('\'./\' . $board_config[\'avatar_gallery_path\']', '$board_config[\'avatar_gallery_path\']', $code);
				$code = str_replace('\'./\' . $board_config[\'avatar_path\']', '$board_config[\'avatar_path\']', $code);

				$code = str_replace('$board_config[\'avatar_gallery_path\']', '\''.$phpbb_root_path.'\' . $board_config[\'avatar_gallery_path\']', $code);
				$code = str_replace('$board_config[\'avatar_path\']', '\''.$phpbb_root_path.'\' . $board_config[\'avatar_path\']', $code);

				//This should fix the url when users try to activate the accont
				$code = str_replace('$server_url . \'?mode=activate&\'', 'PHPBB_URL .\'profile.\'.$phpEx.\'?mode=activate&\'', $code);
			break;

			case 'includes/usercp_avatar':
				//
				// Avatars and ranks
				//
				$code = str_replace( '\'./\' . $board_config[\'avatar_gallery_path\']', '$board_config[\'avatar_gallery_path\']', $code );
				$code = str_replace( '\'./\' . $board_config[\'avatar_path\']', '$board_config[\'avatar_path\']', $code );
 
				$code = str_replace( '$board_config[\'avatar_gallery_path\']', '\'' . $phpbb_root_path . '\' . $board_config[\'avatar_gallery_path\']', $code );
				$code = str_replace( '$board_config[\'avatar_path\']', '\'' . $phpbb_root_path . '\' . $board_config[\'avatar_path\']', $code );

				$code = str_replace( '$error = true;', 'return;', $code);
				$code = str_replace( 'if (!is_uploaded_file($avatar_filename))', 'if (!file_exists($avatar_filename))', $code);
				$code = str_replace( '$move_file($avatar_filename, \'' . $phpbb_root_path .'\' . $board_config[\'avatar_path\'] . "/$new_filename");','$move_file($avatar_filename, \'' . $phpbb_root_path .'\' . $board_config[\'avatar_path\'] . "/$new_filename"); @unlink( $avatar_filename);', $code);
			break;
			
			case 'includes/usercp_viewprofile': 
				// 
				// Avatars and ranks 
				// 
				$code = str_replace('\'./\' . $board_config[\'avatar_gallery_path\']', '$board_config[\'avatar_gallery_path\']', $code); 
				$code = str_replace('\'./\' . $board_config[\'avatar_path\']', '$board_config[\'avatar_path\']', $code); 

				$code = str_replace('$board_config[\'avatar_gallery_path\']', '\''.$phpbb_root_path.'\' . $board_config[\'avatar_gallery_path\']', $code);
				$code = str_replace('$board_config[\'avatar_path\']', '\''.$phpbb_root_path.'\' . $board_config[\'avatar_path\']', $code);
			break;

			case 'includes/usercp_sendpasswd':
				//This should fix the url when users try to recover the password
				$code = str_replace('$server_url . \'?mode=activate&\'', 'PHPBB_URL .\'profile.\'.$phpEx.\'?mode=activate&\'', $code);
			break;

			case 'includes/topic_review':
			break;

			case 'includes/functions_search':
			break;

			case 'includes/functions_cash':
				$code = preg_replace ("/phpbb_root_path = (.*)\;/", "phpbb_root_path = '" . $phpbb_root_path . "';", $code);
				$code = preg_replace ("/$template = (.*)\;/", "$template = '" . $template . "';", $code);
			break;

			case 'cash':
			
				$code = str_replace('exit;', 'return;', $code);
			break;
		}

		/*
		//this funtions are now in funtion container
		$code = str_replace('get_db_stat(', 'phpBB2::get_db_stat(', $code);
		$code = str_replace('create_date(', 'phpBB2::create_date(', $code);
		$code = str_replace('phpbb_clean_username(', 'phpBB2::phpbb_clean_username(', $code);
		$code = str_replace('phpbb_realpath(', 'phpBB2::phpbb_realpath(', $code);
		$code = str_replace('get_userdata(', 'mx_get_userdata(', $code);
		$code = str_replace('dss_rand(', 'mx_dss_rand(', $code);
		*/

		//
		// Now do a recursive study of sub includes ;)
		//
		//$code = preg_replace('#^(.?).*(include)(.*?)(includes\/usercp_)(.*?)(\.).*(\r\n?|\n)#m', "\n " . '$mx_forum->read_file( \'includes/usercp_\\5\', true );' . "\n", $code);
		$code = preg_replace('#^(.?).*(include)(.*?)(includes\/)(.*?)(\.).*(\r\n?|\n)#m', "\n " . '$mx_forum->read_file( \'includes/\\5\', true );' . "\n", $code);
		$code = preg_replace('#^(.?).*(require)(.*?)(includes\/)(.*?)(\.).*(\r\n?|\n)#m', "\n " . '$mx_forum->read_file( \'includes/\\5\', true );' . "\n", $code);

		if ($sub_call)
		{
			// Debug subcalls
			//die(str_replace("\n", '<br>', htmlspecialchars($code)));
			//die( '<pre>'.preg_replace("#\n#esi", "'<br>' . sprintf('%4d ',\$i++)", htmlspecialchars($code)) . '</pre>');
			eval($code);
			//return $code;			
		}
		else
		{
			// Debug main phpBB code
			//error_reporting(0);
			//die(str_replace("\n", '<br>', htmlspecialchars($code)));
			//die( '<pre>'.@preg_replace("#\n#esi", "'\n'", htmlspecialchars($code)) . '</pre>');
			//die( '<pre>'.@preg_replace("#\n#esi", "'<br>'", htmlspecialchars($code)) . '</pre>');			
			//die( '<pre>'.@preg_replace("#\n#esi", "'<br>' . sprintf('%4d ',\$i++)", htmlspecialchars($code)) . '</pre>');
			eval($code);
			//return $code;			
		}
	}

}
// --------------------------------------------------------------------------------
// End Of Class :-)
// --------------------------------------------------------------------------------


// --------------------------------------------------------------------------------
// --------------------------------------------------------------------------------
// --------------------------------------------------------------------------------
// --------------------------------------------------------------------------------
//
// Actual code starts here
//

if ( defined( 'IN_PORTAL' ) )
{
	/* ================================================================================ *
	We are being called by a phpBB Script running as a Portal Block !!!
 * ================================================================================ */

	$mx_forum = new mx_forum();
	$mx_forum->init();
	
	return;
}
else
{
	/* ================================================================================ *
	We are being called by a phpBB Script running Standalone (from phpBB folder) !!!
 * ================================================================================ */ 
    
	$http_protocol = $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
    $request_url = $http_protocol.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];	
	$basename = explode('.', basename($_SERVER['SCRIPT_NAME']));
	$count = count($basename) - 2;
	$action = $basename[$count];
	$phpEx = substr(strrchr(__FILE__, '.'), 1);
	// Check if the Hook MOD has been correctly installed/customized...
	if (!is_file($mx_root_path . 'modules/mx_forum/'.$action.'.'.$phpEx))
	{
		die( 'Error in file: ' . $_SERVER['SCRIPT_NAME'] . '<br />

			Variable $mx_root_path is empty OR does not correctly point to the portal root path !!!<br />

			Please, check if you have correctly defined this variable.' );
	} 
	
	// Check if the phpBB Script has been copied into the modules/mx_forum folder...
	
	// NOTE: This is already done by calling $mx_forum->get_portal_url() below.
	
	// if( !@file_exists($mx_root_path.'modules/mx_forum/'.$_SERVER['SCRIPT_NAME']) )
	// {
	// return;
	// }
	
	// Let's go and try to redirect the phpBB script request to its Portal Page...
	
	$mx_forum = new mx_forum();
	$board_config = &$config;
	$mx_forum->init();

	//if ($mx_forum->phpbb_config['enable_module'])
	if ($mx_forum->phpbb_config['enable_module']  || defined('IN_CMS'))
	{
	    if (isset($_FILES) && !empty($_FILES))
	    {
	        $up_files_keys = array_keys($_FILES);
	        foreach( $up_files_keys as $up_file )
	        {
	            @rename($_FILES[$up_file]['tmp_name'], $_FILES[$up_file]['tmp_name'] . '.mxbb');
	            $_FILES[$up_file]['tmp_name'] .= '.mxbb';
	            $_FILES[$up_file]['tmp_name'] .= '.mxbb';
	        }
	    }
		
		$http_protocol = ( $board_config['cookie_secure'] ) ? 'https://' : 'http://';
		$http_server = isset($board_config['server_name']) ? preg_replace( '#^\/?(.*?)\/?$#', '\1', trim( $board_config['server_name'] ) ) : $_SERVER['HTTP_HOST'];
		$http_port = (($board_config['server_port']) && ($board_config['server_port'] <> 80)) ? ':' . trim( $board_config['server_port'] ) : '';
		$request_url = $http_protocol . $http_server . $http_port . $_SERVER['REQUEST_URI'];
		$phpbb_url = $mx_forum->phpbb_url;
		$phpbb_uri = substr($request_url, strlen($mx_forum->phpbb_url));
		$portal_url = $mx_forum->get_portal_url($phpbb_uri, true, true);
			
		if ( defined( 'MX_FORUM_DEBUG' ) && isset( $_GET['debug'] ) && intval( $_GET['debug'] ) == 1 )
		{
			echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">' . "\n" . '<html><head>' . "\n" . '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">' . "\n" . '<title>debug_info(mx_forum)</title>' . "\n" . '</head>' . "\n" . '<body>' . "\n" . '<b>debug_info(mx_forum):</b><br />' . "\n" . '<br />' . "\n" . 'mx_forum(portal_url)-&gt;"' . $mx_forum->portal_url . '"<br />' . "\n" . 'mx_forum(phpbb_url)-&gt;"' . $mx_forum->phpbb_url . '"<br />' . "\n" . '<br />' . "\n" . 'request_url-&gt;"' . $request_url . '"<br />' . "\n" . 'phpbb_uri-&gt;"' . $phpbb_uri . '"<br />' . "\n" . 'portal_url-&gt;"' . $portal_url . '"<br />' . "\n" . '</body></html>';
			exit;
		}

		//
		// If no page is defined for this phpBB script, we have nothing else to do.
		//
		if (empty($portal_url))
		{
			die("No page is defined for this phpBB script, we have nothing else to do...  $phpbb_url");
			return;
		}

		//
		// Send POST vars via SESSIONS
		//
		session_start();
		$_SESSION['mxbb_post_vars'] = $_POST;
		$_SESSION['mxbb_post_files'] = $_FILES;

		//
		// Actually, redirection takes place here :-)
		//
		$mx_forum->redirect($portal_url);
	}
	else
	{
		$http_protocol = ( $board_config['cookie_secure'] ) ? 'https://' : 'http://';
		$http_server = preg_replace( '#^\/?(.*?)\/?$#', '\1', trim( $board_config['server_name'] ) );
		$http_port = ( $board_config['server_port'] <> 80 ) ? ':' . trim( $board_config['server_port'] ) : '';
		$request_url = $http_protocol . $http_server . $http_port . $_SERVER['REQUEST_URI'];
		$phpbb_uri = substr( $request_url, strlen( $mx_forum->phpbb_url ) );
		$portal_url = $mx_forum->get_portal_url( $phpbb_uri, true, true );


		if ( defined( 'MX_FORUM_DEBUG' ) && isset( $_GET['debug'] ) && intval( $_GET['debug'] ) == 1 )
		{
			echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">' . "\n" . '<html><head>' . "\n" . '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">' . "\n" . '<title>debug_info(mx_forum)</title>' . "\n" . '</head>' . "\n" . '<body>' . "\n" . '<b>debug_info(mx_forum):</b><br />' . "\n" . '<br />' . "\n" . 'mx_forum(portal_url)-&gt;"' . $mx_forum->portal_url . '"<br />' . "\n" . 'mx_forum(phpbb_url)-&gt;"' . $mx_forum->phpbb_url . '"<br />' . "\n" . '<br />' . "\n" . 'request_url-&gt;"' . $request_url . '"<br />' . "\n" . 'phpbb_uri-&gt;"' . $phpbb_uri . '"<br />' . "\n" . 'portal_url-&gt;"' . $portal_url . '"<br />' . "\n" . '</body></html>';
			exit;
		} 
		
		// If no page is defined for this phpBB script, we have nothing else to do.
		
		if ( empty( $portal_url ) )
		{
			return;
		} 
		
		// Actually, redirection takes place here :-)
		
		$mx_forum->redirect( $portal_url );
	}
	exit; // <--- never reached!
	
} // !defined('IN_PORTAL')
// --------------------------------------------------------------------------------
// That's all Folks!
// --------------------------------------------------------------------------------

?>
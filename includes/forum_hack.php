<?php
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

define( 'MX_FORUM_DEBUG', 1 );
// --------------------------------------------------------------------------------
// Class: mx_forum
// --------------------------------------------------------------------------------
class mx_forum
{ 
	
	// Class Implementation Notes:
	
	// Variables/Methods prefixed with an underscore are meant to be private. So, do
	// not use them outside this class. They may change in future implementations.
	
	// Actually, everything here is subject to change in future as well. =:-o
	
	var $phpbb_url, // aka(MX): PHPBB_URL
	$portal_url, // aka(MX): PORTAL_URL
	$images, // Used to replace phpBB $images with our own version
	// ...we will prefix all images with a full phpBB URL.
	$forum_pages,
	$script_name; 
	
	// Instance Initialization...
	
	function init()
	{
		global $images, $board_config, $lang, $block_id, $userdata, $block_config, $template, $module_root_path, $HTTP_POST_VARS, $_POST; 
		
		// Get Portal/Forum paths...
		
		$this->init_paths(); 
		
		// Load user defined table of forum pages...
		
		$this->init_forum_pages(); 
		
		// Extra initialization if beign called as a Portal Block...
		
		if ( defined( 'IN_PORTAL' ) )
		{ 
			
			// Get phpBB script name from current Portal Page ID...
			
			$page_id = ( isset( $_GET['page'] ) ? intval( $_GET['page'] ) : 0 );
			$this->script_page = '';
			foreach( $this->forum_pages as $script_name => $script_page )
			{
				if ( $script_page == $page_id )
				{
					$this->script_name = $script_name;
					break;
				}
			} 
			
			// Prefix $images with PHPBB_URL...
			
			$this->images = array();
			foreach( $images as $key => $val )
			{
				$this->images[$key] = $this->phpbb_url . $val;
			} 
			
			// General MX vars
			// ----------------------------------------------------------
			// Block info addin
			$block_config = read_block_config( $block_id );
			$title = $block_config[$block_id]['block_title'];
			$desc = $block_config[$block_id]['block_desc'];
			$block_size = ( isset( $block_size ) && !empty( $block_size ) ? $block_size : '100%' ); 
			
			// Extract 'what posts to view info', the cool Array ;)
			$phpbb_type_select_data = array();
			$phpbb_type_select_temp = $block_config[$block_id][phpbb_type_select]['parameter_value'];
			$phpbb_type_select_temp = stripslashes( $phpbb_type_select_temp );
			$phpbb_type_select_data = eval( "return " . $phpbb_type_select_temp . ";" );

			$this->phpbb_type_select_data = $phpbb_type_select_data;
			$this->title = $title;

			$is_auth_ary = array();
			$is_auth_ary = block_auth( AUTH_EDIT, $block_id , $userdata, $block_config[$block_id][auth_edit], $block_config[$block_id][auth_edit_group] );

			$template->set_filenames( array( 'phpbb_header' => 'phpbb_header.tpl' ) 
				);

			$template->assign_vars( array( 'L_TITLE' => $title,
					'L_DESCRIPTION' => $desc,
					'BLOCK_SIZE' => $block_size ) 
				);

			$template->pparse( 'phpbb_header' );
			// --------------------------------------------------------------
		}
	}
	function init_paths()
	{
		global $mx_root_path, $db, $phpEx;

		if ( defined( PHPBB_URL ) && defined( PORTAL_URL ) )
		{
			$this->phpbb_url = PHPBB_URL;
			$this->portal_url = PORTAL_URL;
		}
		else
		{
			$PORTAL_TABLE = $this->_get_mx_table_name( 'portal' );
			$sql = "SELECT * FROM $PORTAL_TABLE WHERE portal_id = 1";
			if ( !( $result = $db->sql_query( $sql ) ) )
			{
				$this->phpbb_url = '';
				$this->portal_url = '';
			}
			else
			{
				$row = $db->sql_fetchrow( $result );
				$this->phpbb_url = $row['portal_phpbb_url'];
				$this->portal_url = $row['portal_url'];
			}
		}
	}
	function init_forum_pages()
	{
		global $mx_root_path, $phpEx, $page_id, $HTTP_POST_VARS, $HTTP_GET_VARS, $db, $mx_table_prefix, $is_inline_review ;

		if ( empty( $_SESSION['phpbb_setup_default'] ) )
		{
			$PHPBB_CONFIG_TABLE = $this->_get_mx_table_name( 'phpbb_plugin_config' ); 
						
			// Pull all config data
			
			$sql = "SELECT *
		 		FROM " . $PHPBB_CONFIG_TABLE;
			if ( !$result = $db->sql_query( $sql ) )
			{
				message_die( CRITICAL_ERROR, "Could not query phpbb plugin configuration information", "", __LINE__, __FILE__, $sql );
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
		$mx_table_prefix = $this->_get_mx_table_name( '' );
		
		define( 'FUNCTION_TABLE', $mx_table_prefix . 'function' );
		define( 'PARAMETER_TABLE', $mx_table_prefix . 'parameter' );

		define( 'COLUMN_TABLE' , $mx_table_prefix . 'column' );
		define( 'COLUMN_BLOCK_TABLE', $mx_table_prefix . 'column_block' );

		define( 'BLOCK_TABLE', $mx_table_prefix . 'block' );
		define( 'BLOCK_SYSTEM_PARAMETER_TABLE', $mx_table_prefix . 'block_system_parameter' );

		include( $mx_root_path . 'modules/mx_forum/includes/forum_pages.' . $phpEx );
		
		if ( isset( $mx_forum_pages ) && is_array( $mx_forum_pages ) && $HTTP_GET_VARS['mode'] != 'topicreview' )
		{
			$this->forum_pages = $mx_forum_pages;
		}
		else
		{
			$this->forum_pages = array( 'faq' => 0,
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
				'viewtopic' => 0 
				);
		}
		
	} 
	
	// Tricky but useful method to retrieve Portal Table names from phpBB scope...
	
	function _get_mx_table_name( $table_suffix )
	{
		global $mx_root_path, $phpEx;
		include( $mx_root_path . 'config.' . $phpEx );
		return $mx_table_prefix . $table_suffix;
	} 
	
	// Get a Full URL to a Portal Page or prefix the URL being passed with full phpBB URL.
	
	function full_url( $url, $non_html_amp = false )
	{
		$portal_url = $this->get_portal_url( $url, $non_html_amp, false );
		// return ( !empty($portal_url) ? $portal_url : $this->phpbb_url.$url );
		// $script_name = $this->_validate_redirection($url, $by_http_vars);
		// return ( $this->phpbb_url.$url."&page=" . $this->forum_pages[$script_name] );
		return ( $this->phpbb_url . $url );
	} 
	
	// Get an URL to a Portal Page, whenever is possible... =:-o
	
	function get_portal_url( $url, $non_html_amp = false, $by_http_vars = false )
	{
		global $phpEx;

		$script_name = $this->_validate_redirection( $url, $by_http_vars );
		if ( empty( $script_name ) )
		{
			$script_name = 'index';
			// return '';
		}

		if ( isset( $this->forum_pages[$script_name] ) && $this->forum_pages[$script_name] > 0 )
		{
			if ( ( $pos = strpos( $url, '?', $pos + 1 ) ) === false )
			{
				return $this->portal_url . "index.$phpEx?page=" . $this->forum_pages[$script_name];
			}
			return $this->portal_url . "index.$phpEx?page=" . $this->forum_pages[$script_name] . ( ( $non_html_amp ) ? '&' : '&amp;' ) . substr( $url, $pos + 1 );
		}
		return '';
	}
	function _validate_redirection( $url, $by_http_vars )
	{
		if ( ( $pos = strpos( $url, '.' ) ) === false )
		{
			return '';
		}
		$script_name = substr( $url, 0, $pos );
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
					return '';
				}
				break;
			case 'viewtopic':
				$mode = $this->_get_mode( $url, $by_http_vars );
				if ( $mode == 'printertopic' || $mode == 'smilies' )
				{
					return '';
				}
				break;
		}
		return $script_name;
	}
	function _get_mode( $url, $by_http_vars )
	{
		if ( $by_http_vars )
		{
			return $this->_get_http_var( 'mode', '' );
		}
		$query_array = $this->_get_http_query_array( $url );
		return ( isset( $query_array['mode'] ) ? $query_array['mode'] : '' );
	}
	function _get_http_var( $key, $default )
	{
		return ( isset( $_GET[$key] ) ? $_GET[$key] : ( isset( $_POST[$key] ) ? $_POST[$key] : $default ) );
	}
	function _get_http_query_array( $url )
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
	
	// Initialize Common Template Vars...
	
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
		$common_vars = array( 'U_PHPBB_ROOT_PATH' => $this->phpbb_url,
			'L_INDEX' => sprintf( $lang['Forum_Index'], $board_config['sitename'] ),
			'U_INDEX' => $this->append_sid( "index.$phpEx" ),
			'S_TIMEZONE' => sprintf( $lang['All_times'], $l_timezone ) 
			);
		switch ( $this->script_name )
		{
			case 'index':
				global $s_last_visit; // declared here: includes/page_header.php
				$script_vars = array( 'LAST_VISIT_DATE' => sprintf( $lang['You_last_visit'], $s_last_visit ),
					'CURRENT_TIME' => sprintf( $lang['Current_time'], create_date( $board_config['default_dateformat'], time(), $board_config['board_timezone'] ) ),
					'L_SEARCH_NEW' => $lang['Search_new'],
					'L_SEARCH_UNANSWERED' => $lang['Search_unanswered'],
					'L_SEARCH_SELF' => $lang['Search_your_posts'],
					'U_SEARCH_UNANSWERED' => $this->append_sid( 'search.' . $phpEx . '?search_id=unanswered' ),
					'U_SEARCH_SELF' => $this->append_sid( 'search.' . $phpEx . '?search_id=egosearch' ),
					'U_SEARCH_NEW' => $this->append_sid( 'search.' . $phpEx . '?search_id=newposts' ) 
					);
				break;
			case 'search':
				$script_vars = array( 'L_SEARCH' => $lang['Search'] 
					);
				break;
			case 'viewforum':
				global $online_userlist; // declared here: includes/page_header.php
				$script_vars = array( 'LOGGED_IN_USER_LIST' => $online_userlist 
					);
				break;
			default:
				$script_vars = array();
				break;
		}
		$template->assign_vars( $common_vars + $script_vars + ( ( $argarray === false ) ? array() : $argarray ) );
		$template->assign_block_vars( 'switch_user_logged_' . ( ( $userdata['session_logged_in'] ) ? 'in' : 'out' ), array() );
	} 
	
	// Hook into some phpBB functions to append phpBB path to URLs...
	
	function append_sid( $url, $non_html_amp = false )
	{ 
		// Replaces same function in sessions.php
		
		return append_sid( $this->full_url( $url, $non_html_amp ), $non_html_amp );
	}
	function redirect( $url )
	{ 
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

		if ( @preg_match( '/Microsoft|WebSTAR|Xitami/', getenv( 'SERVER_SOFTWARE' ) ) )
		{
			header( 'Refresh: 0; URL=' . $url );
			echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">' . "\n" . '<html><head>' . "\n" . '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">' . "\n" . '<meta http-equiv="refresh" content="0; url=' . $url . '">' . "\n" . '<title>Redirect</title>' . "\n" . '<script language="javascript" type="text/javascript">' . "\n" . '<!--' . "\n" . 'if( document.images ) {' . "\n" . "\t" . 'parent.location.replace("' . $url . '");' . "\n" . '} else {' . "\n" . "\t" . 'parent.location.href = "' . $url . '";' . "\n" . '}' . "\n" . '// -->' . "\n" . '</script>' . "\n" . '</head>' . "\n" . '<body>' . "\n" . '<div align="center">If your browser does not support meta redirection please click ' . '<a href="' . $url . '">HERE</a> to be redirected</div>' . "\n" . '</body></html>';
			exit;
		}
		@header( 'Location: ' . $url );
		exit;
	}
	function make_jumpbox( $action, $match_forum_id = 0 )
	{ 
		// Replaces same function in function.php
		
		make_jumpbox( $this->phpbb_url . $action, $match_forum_id );
	}
	function generate_pagination( $base_url, $num_items, $per_page, $start_item, $add_prevnext_text = true )
	{ 
		// Replaces same function in function.php
		
		return generate_pagination( $this->full_url( $base_url ), $num_items, $per_page, $start_item, $add_prevnext_text );
	}
	function smilies_pass( $message )
	{ 
		// Replaces same function in bbcode.php
		
		global $board_config;
		$smilies_path = $board_config['smilies_path'];
		$board_config['smilies_path'] = $this->phpbb_url . $board_config['smilies_path'];
		$message = smilies_pass( $message );
		$board_config['smilies_path'] = $smilies_path;
		return $message;
	}
	function generate_smilies( $mode, $page_id )
	{ 
		// Replaces same function in functions_post.php
		
		global $board_config, $template, $phpEx;
		$smilies_path = $board_config['smilies_path'];
		$board_config['smilies_path'] = $this->phpbb_url . $board_config['smilies_path'];
		generate_smilies( $mode, $page_id );
		$board_config['smilies_path'] = $smilies_path;
		$template->assign_vars( array( 'U_MORE_SMILIES' => $this->append_sid( "posting.$phpEx?mode=smilies" ) ) 
			);
	}
	function fix_posting_msg( &$message, &$meta )
	{ 
		// Fix required by ported posting.php after submiting changes (new,edit,delete)...
		
		$script_names_ary = array( 'viewtopic', 'viewforum' );
		$this->_force_full_url( $message, $script_names_ary );
		$this->_force_full_url( $meta, $script_names_ary );
	}
	function fix_viewtopic_urls( $viewtopic_string )
	{ 
		// Fix required by ported viewtopic.php for secured links to modcp and posting...
		
		$script_names_ary = array( 'modcp', 'posting', 'viewtopic' );
		$this->_force_full_url( $viewtopic_string, $script_names_ary );
		return $viewtopic_string;
	}
	function _force_full_url( &$string, &$script_names_ary )
	{
		for( $i = 0; $i < count( $script_names_ary ); $i++ )
		{
			$script_name = $script_names_ary[$i] . '.';
			$string = str_replace( $script_name, $this->phpbb_url . $script_name, $string );
		}
	}
} // class mx_forum
// --------------------------------------------------------------------------------
// End Of Class :-)
// --------------------------------------------------------------------------------
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
	
	// Check if the Hook MOD has been correctly installed/customized...
	
	if ( !@file_exists( $mx_root_path . 'modules/mx_forum/includes/' . @basename( __FILE__ ) ) )
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
	$mx_forum->init();

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
	exit; // <--- never reached!
	
} // !defined('IN_PORTAL')
// --------------------------------------------------------------------------------
// That's all Folks!
// --------------------------------------------------------------------------------

?>
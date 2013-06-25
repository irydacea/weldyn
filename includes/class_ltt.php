<?php
//
//	file: includes/class_ltt.php
//	author: abdev
//	begin: 11/10/2011
//	version: 0.0.4 - 06/15/2012
//	licence: http://opensource.org/licenses/gpl-license.php GNU Public License
//

// ignore
if ( !defined('IN_PHPBB') )
{
	exit;
}

function ltt_check_install()
{
	global $user;

	if ( $user->data['user_type'] == USER_FOUNDER )
	{
		global $config;

		// init var
		$error = false;

		// let's check if the install is good !
		$check_vars = array('url', 'max_chars', 'icons');
		foreach ( $check_vars as $check_var )
		{
			if ( !isset($config['ltt_' . $check_var]) )
			{
				$error = true;
			}
		}
		unset($check_var);

		if ( $error )
		{
			global $phpbb_root_path, $phpEx;

			// load language file
			$user->add_lang('mods/info_acp_ltt');

			$file = "{$phpbb_root_path}db_update.$phpEx";
			if ( !file_exists($file) )
			{
				trigger_error($user->lang['LTT_ISSUE_INSTALL_MISSING'], E_USER_ERROR);
			}

			trigger_error($user->lang('LTT_ISSUE_VAR_MISSING', append_sid($file)), E_USER_ERROR);
		}
	}
}

function ltt_max_chars($topic_title = '')
{
	// no topic title ? so, that forum is empty !
	if ( empty($topic_title) )
	{
		return;
	}

	global $config;

	// censors ...
	$topic_title = censor_text($topic_title);

	// reduce the topic title if needed
	return isset($topic_title[$config['ltt_max_chars']]) ? truncate_string($topic_title, 0, $config['ltt_max_chars'], false, '...') : $topic_title;
}

// borrowed from "aos who visited a topic" mod
function ltt_config(&$display_vars)
{
	for ( $legend = 1; isset($display_vars['vars']['legend' . $legend]); $legend++ )
	{
		$legend;
	}

	$options = array(
		'legend' . ($legend - 1) => 'LTT',
		'ltt_max_chars' => array('lang' => 'LTT_MAX_CHARS', 'validate' => 'int:0', 'type' => 'text:3:4', 'explain' => true),
		'ltt_url' => array('lang' => 'LTT_URL', 'validate' => 'int', 'type' => 'custom', 'function' => 'ltt_url', 'explain' => false),
		'ltt_icons' => array('lang' => 'LTT_ICONS', 'validate' => 'bool', 'type' => 'custom', 'function' => 'ltt_icons', 'explain' => true),

		'legend' . $legend => 'ACP_SUBMIT_CHANGES',
	);

	foreach ( $options as $key => $val )
	{
		$display_vars['vars'][$key] = $val;
	}
	unset($key);
}

function ltt_url($value, $key = '')
{
	return h_radio('config[ltt_url]', array(0 => 'FIRST_POST', 1 => 'LAST_POST', 2 => 'NEWEST_POST'), $value, $key);
}

function ltt_icons($value, $key = '')
{
	return h_radio('config[ltt_icons]', array(1 => 'YES', 0 => 'NO'), $value, $key);
}

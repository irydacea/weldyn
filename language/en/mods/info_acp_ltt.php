<?php
//
//	file: language/en/mods/info_acp_ltt.php
//	author: abdev
//	begin: 12/12/2010
//	version: 0.0.5 - 06/15/2012
//	licence: http://opensource.org/licenses/gpl-license.php GNU Public License
//

// ignore
if ( !defined('IN_PHPBB') )
{
	exit;
}

// init lang ary, if it doesn't !
if ( empty($lang) || !is_array($lang) )
{
	$lang = array();
}

// administration
$lang = array_merge($lang, array(
	'LTT' => 'Latest Topic Title',

	'LTT_MAX_CHARS' => 'Maximum characters per displayed title',
	'LTT_MAX_CHARS_EXPLAIN' => 'The supplementary characters will be replaced by “...”.',
	'LTT_ICONS' => 'Show topic icons',
	'LTT_ICONS_EXPLAIN' => 'Please check beforehand that they are enabled in your forums.',

	'LTT_URL' => 'Link redirection',
	'FIRST_POST' => 'First post',
	'NEWEST_POST' => 'First unread post',
));

// issues
$lang = array_merge($lang, array(
	'LTT_ISSUE_VAR_MISSING' => 'The installation seems to be incomplete. Please run the <a href="%s">installation file</a> !',
	'LTT_ISSUE_INSTALL_MISSING' => 'The installation file seems to be missing. Please check your installation !',
));

// umil
$lang = array_merge($lang, array(
	'INSTALL_LTT' => 'Install Latest Topic Title',
	'INSTALL_LTT_CONFIRM' => 'Are you ready to install Latest Topic Title ?',
	'UPDATE_LTT' => 'Update Latest Topic Title',
	'UPDATE_LTT_CONFIRM' => 'Are you ready to update Latest Topic Title ?',
	'UNINSTALL_LTT' => 'Uninstall Latest Topic Title',
	'UNINSTALL_LTT_CONFIRM' => 'Are you ready to uninstall Latest Topic Title ? All settings for this MOD will be removed !',
));

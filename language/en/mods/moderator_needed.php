<?php
/**
*
* moderator_needed [English]
*
* @package language
* @version $Id: moderator_needed.php,v 1.0.1 2009/09/29 06:50:00 rmcgirr83 Exp $
* @copyright (c) 2009 Richard McGirr 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, array(
    'MODERATOR_NEEDED_REPORTED_POST'	=> '<strong style="color:#FF0000;">%d</strong> Post is reported',
	'MODERATOR_NEEDED_REPORTED_POSTS'	=> '<strong style="color:#FF0000;">%d</strong> Posts are reported',
    'MODERATOR_NEEDED_APPROVE_POST'		=> '<strong style="color:#FF0000;">%d</strong> Post needs approval',
	'MODERATOR_NEEDED_APPROVE_POSTS'	=> '<strong style="color:#FF0000;">%d</strong> Posts need approval',
	'MODERATOR_NEEDED_APPROVE_TOPIC'    => '<strong style="color:#FF0000;">%d</strong> Topic needs approval',
	'MODERATOR_NEEDED_APPROVE_TOPICS'   => '<strong style="color:#FF0000;">%d</strong> Topics need approval',
	'MODERATOR_NEEDED_REPORTED_PM'    	=> '<strong style="color:#FF0000;">%d</strong> PM is reported',
	'MODERATOR_NEEDED_REPORTED_PMS'   	=> '<strong style="color:#FF0000;">%d</strong> PMs are reported',	
));

?>

<?php
/**
*
* prime_signature_cap [English]
*
* @package language
* @version $Id: prime_signature_cap.php,v 0.0.0 2008/02/06 14:30:00 primehalo Exp $
* @copyright (c) 2008 Ken F. Innes IV
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
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge($lang, array(
	'MAX_SIG_LINES'			=> 'Maximum signature lines',
	'MAX_SIG_LINES_EXPLAIN'	=> 'Maximum number of lines in user signatures. Set to 0 for unlimited lines.',
	'TOO_MANY_LINES_SIG'	=> 'Your signature contains %1$d lines. The maximum number of allowed lines is %2$d.',
));

?>
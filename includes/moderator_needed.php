<?php
/**
*
* @package phpBB3
* @version $Id: moderator_needed.php,v 1.0.4 2009/11/23 10:10:05 EST RMcGirr83 Exp $
* @copyright (c) Rich McGirr
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* Include only once.
*/
if (!defined('INCLUDES_MODERATOR_NEEDED_PHP'))
{
	define('INCLUDES_MODERATOR_NEEDED_PHP', true);

	function moderator_needed_count()
	{
		global $auth, $cache, $db, $template, $user, $phpEx, $phpbb_root_path;
		
		if (!function_exists('get_forum_list'))
		{
			include($phpbb_root_path . 'includes/functions_admin.' . $phpEx);
		}
        
		// changed for 3.0.6
		$allow = $allow_pm = false;
		
		// needed language 
		$user->add_lang('mods/moderator_needed');		
		
		// we first need to know what the user is authed for
		// user with auth approve and auth report
		if ($auth->acl_getf_global('m_approve') && $auth->acl_getf_global('m_report'))
		{
			// we wants it all and we wants it now..if we're authed
			$sql_where = ' WHERE (' . $db->sql_in_set('forum_id', get_forum_list('m_approve')) . ' or ' . $db->sql_in_set('forum_id', get_forum_list('m_report')) . ') AND (post_reported = 1 or post_approved = 0)';
			$allow = true;
		}
		// user with auth approve
		elseif ($auth->acl_getf_global('m_approve'))
		{
			// just posts waiting for approval please
			$sql_where = ' WHERE ' . $db->sql_in_set('forum_id', get_forum_list('m_approve')) . ' AND post_approved = 0';
			$allow = true;
		}
		// user with auth report
		elseif ($auth->acl_getf_global('m_report'))
		{
			// just posts that have been reported thanks
			$sql_where = ' WHERE ' . $db->sql_in_set('forum_id', get_forum_list('m_report')) . ' AND post_reported = 1';
			$allow = true;
		}
		// user is a moderator so can act on reported PMS
		if ($auth->acl_getf_global('m_'))
		{
			$allow_pm = true;
		}
		if ($allow)
		{
			// initialize some variables
			$reported_posts_count = $unapproved_posts_count = $unapproved_topics_count = $reported_pms = 0;
			
			// first build an array of topics waiting to be approved
			// but a user still has to have the correct auths
			if ($auth->acl_getf_global('m_approve'))
			{
				$sql = 'SELECT topic_first_post_id
							FROM ' . TOPICS_TABLE . '
						WHERE ' . $db->sql_in_set('forum_id', get_forum_list('m_approve')) . ' AND topic_approved = 0';
				$result = $db->sql_query($sql);

				$unapproved_topics_array = array();
				while ($row = $db->sql_fetchrow($result))
				{
					$unapproved_topics_array[] = (int) $row['topic_first_post_id'];
					// count up the unapproved topics
					$unapproved_topics_count++;
				}
				$db->sql_freeresult($result);

				// we're going to change the sql parameters so as not to include these topics
				if(sizeof($unapproved_topics_array))
				{
					$sql_where .= ' AND ' . $db->sql_in_set('post_id', $unapproved_topics_array, true);
				}
			}
			
			// now all others
			$sql = 'SELECT post_reported, post_approved
						FROM ' . POSTS_TABLE .
					$sql_where . ' OR (forum_id = 0 and post_reported = 1)';
			$result = $db->sql_query($sql);

			while ($row = $db->sql_fetchrow($result))
			{
				// count the reported posts
				if ($row['post_reported'])
				{
					$reported_posts_count++;
				}
				// count the unapproved posts
				if (!$row['post_approved'])
				{
					$unapproved_posts_count++;
				}
			}
			$db->sql_freeresult($result);

			// we gots us some data
			if ($reported_posts_count || $unapproved_posts_count || $unapproved_topics_count)
			{
			    // reported posts
				$l_reported_posts_count = $reported_posts_count ? (($reported_posts_count == 1) ? $user->lang['MODERATOR_NEEDED_REPORTED_POST'] : $user->lang['MODERATOR_NEEDED_REPORTED_POSTS']) : '';
				$total_reported_posts = sprintf($l_reported_posts_count, $reported_posts_count);
				// unapproved topics
				$l_unapproved_topics_count = $unapproved_topics_count ? (($unapproved_topics_count == 1) ? $user->lang['MODERATOR_NEEDED_APPROVE_TOPIC'] : $user->lang['MODERATOR_NEEDED_APPROVE_TOPICS']) : '';
				$total_unapproved_topics = sprintf($l_unapproved_topics_count, $unapproved_topics_count);
				// unapproved posts
				$l_unapproved_posts_count = $unapproved_posts_count ? (($unapproved_posts_count == 1) ? $user->lang['MODERATOR_NEEDED_APPROVE_POST'] : $user->lang['MODERATOR_NEEDED_APPROVE_POSTS']) : '';
				$total_unapproved_posts = sprintf($l_unapproved_posts_count, $unapproved_posts_count);

				// what good is the data if we can't see it?
				// Dump the data to the template engine
				$template->assign_vars(array(
					'TOTAL_MODERATOR_REPORTS'		=> $total_reported_posts,
					'TOTAL_MODERATOR_POSTS'			=> $total_unapproved_posts,
					'TOTAL_MODERATOR_TOPICS'    	=> $total_unapproved_topics,
					
					'U_MODERATOR_REPORTS'			=> append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=reports&amp;mode=reports', true, $user->session_id),
					'U_MODERATOR_APPROVE_POSTS'		=> append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=queue&amp;mode=unapproved_posts', true, $user->session_id),
					'U_MODERATOR_APPROVE_TOPICS'    => append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=queue&amp;mode=unapproved_topics', true, $user->session_id),
				));
			}
        }
		
		// new for 3.0.6 reported PMs
		if ($allow_pm)
		{
			$reported_pms = 0;
			// cache for five minutes
			if (($reported_pms = $cache->get('_reported_pms')) === false)
			{			
				$sql = 'SELECT COUNT(msg_id) AS pms_count 
					FROM ' . PRIVMSGS_TABLE . '
					WHERE message_reported = 1';
				$result = $db->sql_query($sql);
				$reported_pms = (int) $db->sql_fetchfield('pms_count');
				$db->sql_freeresult($result);
				
				// cache this data for 5 minutes, this improves performance
				$cache->put('_reported_pms', $reported_pms, 300);
			}

			if ($reported_pms)
			{
				// reported pms
				$l_reported_pms_count = $reported_pms ? (($reported_pms == 1) ? $user->lang['MODERATOR_NEEDED_REPORTED_PM'] : $user->lang['MODERATOR_NEEDED_REPORTED_PMS']) : '';
				$total_reported_pms = 	sprintf($l_reported_pms_count, $reported_pms);		

				$template->assign_vars(array(
					'TOTAL_MODERATOR_PMS'			=> $total_reported_pms,
					'U_MODERATOR_PMS'				=> append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=pm_reports&amp;mode=pm_reports', true, $user->session_id),
				));					
			}
		}
		
		// user isn't authed for any of this mularky
		if (!$allow || !$allow_pm)
		{
			return;
		}
	}
}
?>
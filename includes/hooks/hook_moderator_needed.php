<?php
/**
* @package phpBB
* @copyright (c) 2011 Rich McGirr
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*/

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
 * This hook displays moderator messages to moderators
 *
 * @param hook_moderator_needed $hook
 * @return void
 */
function hook_moderator_needed(&$hook)
{
	global $auth, $cache, $db, $template, $user, $phpEx, $phpbb_root_path;
	
	if ($auth->acl_getf_global('m_'))
	{
		$allow = false;
		// needed language 
		$user->add_lang('mods/moderator_needed');		

		if ($auth->acl_getf_global('m_approve') || $auth->acl_getf_global('m_report'))
		{
			if (!function_exists('get_forum_list'))
			{
				include($phpbb_root_path . 'includes/functions_admin.' . $phpEx);
			}
			// we need global announcements which don't have any forum id assigned to them
			$global_forum = array(0);
			
			// we first need to know what the user is authed for
			// user with auth approve and auth report
			if ($auth->acl_getf_global('m_approve') && $auth->acl_getf_global('m_report'))
			{
				$forum_list = array_unique(array_merge(get_forum_list('m_approve'), get_forum_list('m_report'), $global_forum));
				// we wants it all and we wants it now..if we're authed
				$sql_where = ' WHERE ' . $db->sql_in_set('forum_id', $forum_list) . ' AND (post_reported = 1 or post_approved = 0)';
				$allow = true;
			}
			// user with auth approve
			elseif ($auth->acl_getf_global('m_approve'))
			{
				$forum_list = array_unique(array_merge(get_forum_list('m_approve'), $global_forum));
				// just posts waiting for approval please
				$sql_where = ' WHERE ' . $db->sql_in_set('forum_id', $forum_list) . ' AND post_approved = 0';
				$allow = true;
			}
			// user with auth report
			elseif ($auth->acl_getf_global('m_report'))
			{
				$forum_list = array_unique(array_merge(get_forum_list('m_report'), $global_forum));
				// just posts that have been reported thanks
				$sql_where = ' WHERE ' . $db->sql_in_set('forum_id', $forum_list) . ' AND post_reported = 1';
				$allow = true;
			}

			if ($allow)
			{
				// initialize some variables
				$reported_posts_count = $unapproved_posts_count = $unapproved_topics_count = 0;
				
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
						$sql_where;
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
		}

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
	else
	{
		return;
	}
}

/**
 * Only register the hook for normal pages, not administration pages.
 */
if (!defined('ADMIN_START'))
{
	$phpbb_hook->register(array('template', 'display'), 'hook_moderator_needed');
}

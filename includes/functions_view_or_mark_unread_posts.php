<?php
/**
*
* functions_view_or_mark_unread_posts.php
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
* checks to see if the user has any unread posts in the forum
* (returns true if there are unread posts and false if there are not)
*/
function check_unread_posts()
{
	global $db, $user, $auth, $exists_unreads;

	// The next block of code skips the check if the user is a guest (since prosilver and subsilver hide the unread link from guests)
	// or if the user is a a bot.  If you use a template that shows the link to unread posts for guests, you may want to get rid of the first part of the if
	// clause so that the text of the link to unread posts will toggle rather than always reading 'View unread posts'.
	if (($user->data['user_id'] == ANONYMOUS) || $user->data['is_bot'])
	{
		return true;
	}

	// if the user is on the index and functions_display has already checked for unreads, we skip unnecessary queries and return true or false
	if ($exists_unreads == 1)
	{
		return true;
	}
	if ($exists_unreads == -1)
	{
		return false;
	}

	// user not on index so we need to check whether there are unreads

	// find forums where last post time is greater than the forum's mark time (or, if there is none, the user's lastmark time)
	$sql = 'SELECT f.forum_id
		FROM ' . FORUMS_TABLE . ' f
		LEFT JOIN ' . FORUMS_TRACK_TABLE . ' ft
		ON (f.forum_id = ft.forum_id AND ft.user_id = ' . $user->data['user_id'] . ')
		WHERE f.forum_last_post_time > ft.mark_time
			OR (ft.mark_time IS NULL AND f.forum_last_post_time > ' . $user->data['user_lastmark'] . ')';
	$result = $db->sql_query($sql);

	// cycle through the results and return true if we hit one the user is authorized to read
	while($row = $db->sql_fetchrow($result))
	{
		if ($auth->acl_get('f_read', $row['forum_id']))
		{
			return true;
		}
	}
	$db->sql_freeresult($result);

	// last step: check to see if any global topics are unread, since the preceding test only checks regular forums
	// (this code is copied from the test for global unreads that appears in display_forums()
	$unread_ga_list = get_unread_topics($user->data['user_id'], 'AND t.forum_id = 0', '', 1);
	if (!empty($unread_ga_list))
	{
		return true;
	}

	// user has no unreads, so return false
	return false;
}


/**
* marks a private message unread when the user clicks the mark pm as unread link
* when viewing the private message.  Takes a single parameter, which is the msg_id of the pm
* being marked as unread
*/
function mark_unread_pm($msg_id)
{
	global $db, $user, $phpbb_root_path, $phpEx;

	// redirect the user to the index if the user is not logged in or if user is a bot
	if (($user->data['user_id'] == ANONYMOUS) || $user->data['is_bot'])
	{
		redirect(append_sid("{$phpbb_root_path}index.$phpEx"));
	}

	$user->setup('ucp');

	// find out what folder we are talking about so we can confine our actions to that folder
	$folder_id = request_var('f', PRIVMSGS_INBOX);

	$sql = 'SELECT msg_id
		FROM ' . PRIVMSGS_TO_TABLE . '
		WHERE msg_id = ' . $msg_id . '
			AND user_id = ' . $user->data['user_id'] . '
			AND pm_deleted = 0
			AND folder_id =' . $folder_id;
	$result = $db->sql_query($sql);

	if ($row = $db->sql_fetchrow($result))
	{
		// there is a pm in the relevant mailbox that matches that msg_id
		// so go ahead and mark it unread
		$sql = 'UPDATE ' . PRIVMSGS_TO_TABLE . '
			SET pm_unread = 1
			WHERE msg_id = ' . $msg_id . '
				AND user_id = ' . $user->data['user_id'] . '
				AND pm_deleted = 0
				AND folder_id =' . $folder_id;
		$db->sql_query($sql);
		include($phpbb_root_path . 'includes/functions_privmsgs.' . $phpEx);
		update_pm_counts();
	}
	else
	{
		// if we get here, there is no pm in this user's inbox that matches that msg_id
		trigger_error('NO_MESSAGE');
	}
	$db->sql_freeresult($result);

	$meta_info = append_sid("{$phpbb_root_path}ucp.$phpEx", 'i=pm&amp;folder=inbox');
	meta_refresh(3, $meta_info);
	$message = $user->lang['PM_MARKED_UNREAD'] . '<br /><br />';
	$message .= '<a href="' . $meta_info . '">' . $user->lang['RETURN_INBOX'] . '</a><br /><br />';
	$message .= sprintf($user->lang['RETURN_INDEX'], '<a href="' . append_sid("{$phpbb_root_path}index.$phpEx") . '">', '</a>');
	trigger_error($message);
}


/**
* marks a post unread when the user clicks the mark post as unread link for the
* post in viewtopic.  Takes two parameters: the post_id of the post
* being marked as unread and the forum from which the user comes.  Note that
* if the post is a global the forum from which the user comes will not be the
* the forum_id for the post (since the forum_id for the post will in that case be 0).

*/
function mark_unread_post($unread_post_id, $return_forum_id)
{
	global $db, $config, $user, $auth, $phpbb_root_path, $phpEx;

	// redirect the user to the index if the user is not logged in or the board is set up
	// to use cookies rather than the database to store read topic info (since this mod is
	// set up to work only with logged in users and not with cookies); also redirect
	// to index if the user is a bot
	if (($user->data['user_id'] == ANONYMOUS) || !$config['load_db_lastread'] || $user->data['is_bot'])
	{
		redirect(append_sid("{$phpbb_root_path}index.$phpEx"));
	}

	$user->setup('viewtopic');
	// fetch the post_time, topic_id and forum_id of the post being marked as unread
	$sql = 'SELECT post_time, topic_id, forum_id
		FROM ' . POSTS_TABLE . '
		WHERE post_id = ' . $unread_post_id;
	$result = $db->sql_query($sql);
	if ($row = $db->sql_fetchrow($result))
	{
		$post_time = $row['post_time'];
		$mark_time = $post_time - 1;
		$topic_id = $row['topic_id'];
		$forum_id = $row['forum_id'];
	}
	else
	{
		// if we get here, post didn't exist so give an error
		trigger_error('NO_TOPIC');
	}
	$db->sql_freeresult($result);

	// trigger an error if (a) topic does not exist or (b) user is not allowed to read it
	if (!$topic_id || (!$auth->acl_get('f_read', $return_forum_id)))
	{
		trigger_error('NO_TOPIC');
	}

	// set mark_time for the user and the relevant topic in the topics_track table
	// to the post_time of the post minus 1 (so that phpbb3 will think the post is unread)
	markread('topic', $forum_id, $topic_id, $mark_time, $user->data['user_id']);

	// now, tinker with the forums_track and topics_track tables in accordance with these rules:
	//
	//	-	set $forum_tracking_info to be the mark_time entry for the user and relevant forum in the forum_tracks table;
	//		if there is no such entry, set $forum_tracking_info to be the user_lastmark entry for the user in the users table
	//
	//	-	if the post_time of the post is smaller (earlier) than $forum_tracking_info, then:
	//
	//		-	set mark_time for the user and the relevant forum in the forums_track table
	//			to the post_time for the post minus 1 (so that phpbb3 will think the forum is unread)
	//
	//		-	but before doing that, add a new topics_track entry
	//			(with mark_time = forum_tracking_info before the new mark_time entry is added to the forums_track table)
	//			for each other topic in the forum that meets all of the following tests
	//
	//			-	does not already have a topics_track entry for the user and forum
	//
	//			-	has a topic_last_post_time less than or equal to the then current forum_tracking_info
	//				(which shows that it has already been read)
	//
	//			-	has a last post time greater than the new mark_time that will be used for the forums_track table
	//
	//			The purpose of adding these new topics_track entries is to make sure that phpbb3
	//			will continue to treat already read topics as already read rather than incorrectly
	//			thinking they are unread because their topic_last_post_time is after the new
	//			mark_time for the relevant forum


	// so the first step: calculate the forum_tracking_info
	$sql = 'SELECT mark_time
		FROM ' . FORUMS_TRACK_TABLE . '
		WHERE forum_id = ' . $forum_id . '
			AND user_id = ' . $user->data['user_id'];
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	$forum_tracking_info = (!empty($row['mark_time'])) ? $row['mark_time'] : $user->data['user_lastmark'];

	// next, check to see if the post being marked unread has a post_time at or before $forum_tracking_info
	if ($post_time <= $forum_tracking_info)
	{
		// ok, post being marked unread has post time at or before $forum_tracking_info, so we will
		// need to create special topics_track entries for all topics that
		// meet the three tests described in the comment that appears before the $sql definition above
		// (since these are the topics that are currently considered 'read' and would otherwise
		// no longer be considered read when we change the forums_track entry to an earlier mark_time
		// later in the script)

		// so, fetch the topic_ids and related info for the topics in this forum that meet the three tests
		$sql = 'SELECT t.topic_id, t.topic_last_post_time, tt.mark_time
			FROM ' . TOPICS_TABLE . ' t
			LEFT JOIN ' . TOPICS_TRACK_TABLE . ' tt ON (t.topic_id = tt.topic_id AND tt.user_id = ' . $user->data['user_id'] . ')
			WHERE tt.mark_time IS NULL
				AND t.forum_id = ' . $forum_id . '
				AND t.topic_last_post_time <= ' . $forum_tracking_info . '
				AND t.topic_last_post_time > ' . $mark_time;
		$result = $db->sql_query($sql);
		$sql_insert_ary = array();

		// for each of the topics meeting the three tests, create a topics_track entry
		while($row = $db->sql_fetchrow($result))
		{
			$sql_insert_ary[] = array(
				'user_id'	=> $user->data['user_id'],
				'topic_id'	=> $row['topic_id'],
				'forum_id'	=> $forum_id,
				'mark_time'	=> $forum_tracking_info,
			);
		}
		$db->sql_multi_insert(TOPICS_TRACK_TABLE, $sql_insert_ary);
		$db->sql_freeresult($result);

		// finally, move the forums_track time back to $mark_time by inserting or updating the relevant row;
		// to do that, find out if there already is an entry for this user_id and forum_id
		$sql = 'SELECT forum_id
			FROM ' . FORUMS_TRACK_TABLE . '
			WHERE forum_id = ' . $forum_id . '
				AND user_id = ' . $user->data['user_id'];
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (isset($row['forum_id']))
		{
			// in this case there is already an entry for this user and forum_id
			// in the forums_track table, so update the entry for the forum_id
			$sql = 'UPDATE ' . FORUMS_TRACK_TABLE . '
				SET mark_time = ' . $mark_time . '
				WHERE forum_id = ' . $forum_id . '
					AND user_id = ' . $user->data['user_id'];
			$db->sql_query($sql);
		}
		else
		{
			// in this case there is no entry for this user and forum_id
			// in the forums_track table, so insert one
			$sql = 'INSERT INTO ' . FORUMS_TRACK_TABLE . ' ' . $db->sql_build_array('INSERT', array(
				'user_id'	=> $user->data['user_id'],
				'forum_id'	=> $forum_id,
				'mark_time'	=> $mark_time,
			));
			$db->sql_query($sql);
		}
	}

	$meta_info = append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $return_forum_id);
	meta_refresh(3, $meta_info);
	$message = $user->lang['POST_MARKED_UNREAD'] . '<br /><br />';
	$message .= sprintf($user->lang['RETURN_FORUM'], '<a href="' . $meta_info . '">', '</a>') . '<br /><br />';
	$message .= sprintf($user->lang['RETURN_INDEX'], '<a href="' . append_sid("{$phpbb_root_path}index.$phpEx") . '">', '</a>');
	trigger_error($message);
}

?>
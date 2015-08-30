<?php

/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */


include_once("classes/class.ilObjectAccess.php");

/**
* Class ilObjForumAccess
*
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id: class.ilObjForumAccess.php 30276 2011-08-14 18:49:49Z akill $
*
* @ingroup ModulesForum
* @extends ilObjectAccess
*/
class ilObjForumAccess extends ilObjectAccess
{

	/**
	 * get commands
	 * 
	 * this method returns an array of all possible commands/permission combinations
	 * 
	 * example:	
	 * $commands = array
	 *	(
	 *		array("permission" => "read", "cmd" => "view", "lang_var" => "show"),
	 *		array("permission" => "write", "cmd" => "edit", "lang_var" => "edit"),
	 *	);
	 */
	function _getCommands()
	{
		$commands = array
		(
			array("permission" => "read", "cmd" => "showThreads", "lang_var" => "show",
				"default" => true),
			array("permission" => "write", "cmd" => "edit", "lang_var" => "edit"),
		);
		
		return $commands;
	}

	/**
	* check whether goto script will succeed
	*/
	function _checkGoto($a_target)
	{
		global $ilAccess;
		
		$t_arr = explode("_", $a_target);

		if ($t_arr[0] != "frm" || ((int) $t_arr[1]) <= 0)
		{
			return false;
		}

		if ($ilAccess->checkAccess("read", "", $t_arr[1]))
		{
			return true;
		}
		return false;
	}

	/**
	* Get thread id for posting
	*/
	function _getThreadForPosting($a_pos_id)
	{
		global $ilDB;
		
		$res = $ilDB->queryf('
			SELECT pos_thr_fk FROM frm_posts WHERE pos_pk = %s',
			array('integer'), array($a_pos_id));
		
		$rec = $res->fetchRow(DB_FETCHMODE_ASSOC);
		
		return $rec["pos_thr_fk"];
	}
	/**
	 * Returns the number of bytes used on the harddisk by the specified forum.
	 *
	 * @param $forum_id id.
	 */
	public static function _lookupDiskUsage($a_obj_id)
	{
		global $ilDB, $lng;
		require_once 'Modules/Forum/classes/class.ilFileDataForum.php';

		$mail_data_dir = ilUtil::getDataDir('filesystem').DIRECTORY_SEPARATOR."forum";

		$result_set = $ilDB->queryf('
			SELECT top_frm_fk, pos_pk FROM frm_posts p
			JOIN frm_data d ON d.top_pk = p.pos_top_fk
			WHERE top_frm_fk = %s',
			array('integer'), array($a_obj_id));

		$size = 0;
		//$count = 0; counts the number of attachments
		while($row = $result_set->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$fileDataForum = new ilFileDataForum($row['top_frm_fk'],$row['pos_pk']);
			$filesOfPost = $fileDataForum->getFilesOfPost();
			foreach ($filesOfPost as $attachment)
			{
				$size += $attachment['size'];
				//$count++;
			}
			unset($fileDataForum);
			unset($filesOfPost);
		}
		return $size;
	}
	
	/**
	* Get number of postings
	*/
	static function getNumberOfPostings($a_obj_id, $a_only_active = false)
	{
		global $ilDB, $ilUser;

		$set = $ilDB->query("SELECT top_pk FROM frm_data ".
			" WHERE top_frm_fk = ".$ilDB->quote($a_obj_id, "integer")
			);
		if ($rec = $ilDB->fetchAssoc($set))
		{
			$act_clause = $a_only_active
				?	" AND (pos_status = ".$ilDB->quote(1, "integer").
					" OR pos_usr_id = ".$ilDB->quote($ilUser->getId(), "integer").") "
				: "";
			
			$frm_id = $rec["top_pk"];
			$res = $ilDB->queryf("SELECT COUNT(*) cnt
				FROM frm_posts JOIN frm_threads ON (frm_posts.pos_thr_fk = frm_threads.thr_pk) 
				WHERE frm_threads.thr_top_fk = %s".
				$act_clause,
				array('integer'), array($frm_id));
			
			$rec = $ilDB->fetchAssoc($res);
			return $rec["cnt"];
		}
		return 0;
	}
	
	/**
	* Get number of read posts
	*/
	static function getNumberOfReadPostings($a_obj_id, $a_only_active = false)
	{
		global $ilDB, $ilUser;
		
		$set = $ilDB->query("SELECT top_pk FROM frm_data ".
			" WHERE top_frm_fk = ".$ilDB->quote($a_obj_id, "integer")
			);
		if ($rec = $ilDB->fetchAssoc($set))
		{
			$act_clause = $a_only_active
				?	" AND (pos_status = ".$ilDB->quote(1, "integer").
					" OR pos_usr_id = ".$ilDB->quote($ilUser->getId(), "integer").") "
				: "";

			$frm_id = $rec["top_pk"];
			$res = $ilDB->queryf("SELECT COUNT(*) cnt
				FROM frm_user_read INNER JOIN frm_posts ON (frm_user_read.post_id = frm_posts.pos_pk) 
				WHERE frm_user_read.usr_id = %s AND frm_posts.pos_top_fk = %s".
				$act_clause,
				array('integer', 'integer'), array($ilUser->getId(), $frm_id));
			
			$rec = $ilDB->fetchAssoc($res);
			return $rec["cnt"];
		}
		return 0;
	}
	
	/**
	 * Count number of new posts
	 * @param int $a_user_id
	 */
	static function getNumberOfNewPostings($a_obj_id, $a_only_active = false)
	{
		global $ilUser, $ilDB, $ilSetting;

		$set = $ilDB->query("SELECT top_pk FROM frm_data ".
			" WHERE top_frm_fk = ".$ilDB->quote($a_obj_id, "integer")
			);
		if ($rec = $ilDB->fetchAssoc($set))
		{
			$act_clause = $a_only_active
				?	" AND (pos_status = ".$ilDB->quote(1, "integer").
					" OR pos_usr_id = ".$ilDB->quote($ilUser->getId(), "integer").") "
				: "";

			$frm_id = $rec["top_pk"];
			
			$new_deadline = date('Y-m-d H:i:s',
				time() - 60 * 60 * 24 * 7 * ($ilSetting->get('frm_store_new')));
		
			$res = $ilDB->queryf('
				SELECT COUNT(pos_pk) cnt
				FROM frm_posts
				LEFT JOIN frm_user_read ON (post_id = pos_pk AND frm_user_read.usr_id = %s)
				LEFT JOIN frm_thread_access ON (pos_thr_fk = frm_thread_access.thread_id AND frm_thread_access.usr_id = %s)
				WHERE pos_top_fk = %s
				AND ((pos_date > frm_thread_access.access_old_ts OR pos_update > frm_thread_access.access_old_ts)
					OR (frm_thread_access.access_old IS NULL AND (pos_date > %s OR pos_update > %s)))
				AND pos_usr_id != %s 
				AND frm_user_read.usr_id IS NULL'.$act_clause,
				array('integer','integer', 'integer', 'timestamp','timestamp','integer'),
				array($ilUser->getId(), $ilUser->getId(), $frm_id, $new_deadline,$new_deadline, $ilUser->getId())
				);

			$rec = $res->fetchRow(DB_FETCHMODE_ASSOC);
					
			return (int) $rec['cnt'];
		}
		
		return 0;
	}

	/**
	 * Count number of new posts
	 * @param int $a_user_id
	 */
	static function getLastPost($a_obj_id, $a_only_active = false)
	{
		global $ilUser, $ilDB, $ilSetting;
		
		$set = $ilDB->query("SELECT top_pk FROM frm_data ".
			" WHERE top_frm_fk = ".$ilDB->quote($a_obj_id, "integer")
			);
		if ($rec = $ilDB->fetchAssoc($set))
		{
			$act_clause = $a_only_active
			?	" AND (pos_status = ".$ilDB->quote(1, "integer").
				" OR pos_usr_id = ".$ilDB->quote($ilUser->getId(), "integer").") "
			: "";

			$frm_id = $rec["top_pk"];
			
			$ilDB->setLimit(1);
			$res = $ilDB->queryf('
				SELECT *
				FROM frm_posts 
				WHERE pos_top_fk = %s'.
				$act_clause.'
				ORDER BY pos_date DESC',
				array('integer'), array($frm_id));
			
			$row = $ilDB->fetchAssoc($res);
			if ($row["pos_pk"] > 0)
			{
				return $row;
			}
		}
		return false;
	}
	
	/**
	* Prepare message for container view
	*/
	function prepareMessageForLists($text)
	{
		// remove quotings
		$text = strip_tags($text);
		$text = preg_replace('/\[(\/)?quote\]/', '', $text);
		include_once("./Services/Utilities/classes/class.ilStr.php");
		if(ilStr::strLen($text) > 40)
		{
			$text = ilStr::subStr($text, 0, 37).'...';
		}

		return $text;
	}
}

?>

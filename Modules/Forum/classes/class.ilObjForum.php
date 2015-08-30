<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

require_once './Modules/Forum/classes/class.ilForum.php';
require_once './classes/class.ilObject.php';
require_once './Modules/Forum/classes/class.ilFileDataForum.php';
require_once './Modules/Forum/classes/class.ilForumProperties.php';

/** @defgroup ModulesForum Modules/Forum
 */

/**
* Class ilObjForum
*
* @author Wolfgang Merkens <wmerkens@databay.de> 
* @version $Id: class.ilObjForum.php 34276 2012-04-18 14:12:18Z mjansen $
*
* @ingroup ModulesForum
*/
class ilObjForum extends ilObject
{
	/**
	* Forum object
	* @var		object Forum
	* @access	private
	*/
	var $Forum;
	
	private $objProperties = null;
	
	/**
	* Constructor
	* @access	public
	* @param	integer	reference_id or object_id
	* @param	boolean	treat the id as reference_id (true) or object_id (false)
	*/
	function ilObjForum($a_id = 0,$a_call_by_reference = true)
	{
		global $ilias;

		/*
		 * this constant is used for the information if a single post is marked as new
		 * All threads/posts created before this date are never marked as new
		 * Default is 8 weeks
		 *
		 */
		$new_deadline = time() - 60 * 60 * 24 * 7 * ($ilias->getSetting('frm_store_new') ? 
													 $ilias->getSetting('frm_store_new') : 
													 8);
		define('NEW_DEADLINE',$new_deadline);
	
		$this->type = "frm";
		$this->ilObject($a_id,$a_call_by_reference);
		
		// TODO: needs to rewrite scripts that are using Forum outside this class
		$this->Forum =& new ilForum();
	}
	
	function read($a_force_db = false)
	{
		parent::read($a_force_db);
	}

	/**
	* Gets the disk usage of the object in bytes.
    *
	* @access	public
	* @return	integer		the disk usage in bytes
	*/
	function getDiskUsage()
	{
	    require_once("./Modules/File/classes/class.ilObjFileAccess.php");
		return ilObjForumAccess::_lookupDiskUsage($this->id);
	}

	function _lookupThreadSubject($a_thread_id)
	{
		global $ilDB;

		$res = $ilDB->queryf('
			SELECT thr_subject FROM frm_threads WHERE thr_pk = %s',
			array('integer'), array($a_thread_id));
		
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return $row->thr_subject;
		}
		return '';
	}
		
	// METHODS FOR UN-READ STATUS
	function getCountUnread($a_usr_id,$a_thread_id = 0)
	{
		return $this->_getCountUnread($this->getId(),$a_usr_id,$a_thread_id);
	}

	function _getCountUnread($a_frm_id, $a_usr_id,$a_thread_id = 0)
	{
		global $ilBench, $ilDB;

		$ilBench->start("Forum",'getCountRead');
		if(!$a_thread_id)
		{
			// Get topic_id
			$res = $ilDB->queryf('
				SELECT top_pk FROM frm_data WHERE top_frm_fk = %s',
				array('integer'), array($a_frm_id));
			
			
			while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
			{
				$topic_id = $row->top_pk;
			}

			// Get number of posts
			$res = $ilDB->queryf('
				SELECT COUNT(pos_pk) num_posts FROM frm_posts 
				WHERE pos_top_fk = %s',
				array('integer'), array($topic_id));
	
			while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
			{
				$num_posts = $row->num_posts;
			}

			$res = $ilDB->queryf('
				SELECT COUNT(post_id) count_read FROM frm_user_read
				WHERE obj_id = %s
				AND usr_id = %s',
				array('integer', 'integer'), array($a_frm_id, $a_usr_id));
			
			while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
			{
				$count_read = $row->count_read;
			}
			$unread = $num_posts - $count_read;

			$ilBench->stop("Forum",'getCountRead');
			return $unread > 0 ? $unread : 0;
		}
		else
		{
			$res = $ilDB->queryf('
				SELECT COUNT(pos_pk) num_posts FROM frm_posts
				WHERE pos_thr_fk = %s',
				array('integer'), array($a_thread_id));
			
			
			while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
			{
				$num_posts = $row->num_posts;
			}

			$res = $ilDB->queryf('
				SELECT COUNT(post_id) count_read FROM frm_user_read 
				WHERE obj_id = %s
				AND usr_id = %s
				AND thread_id = %s',
				array('integer', 'integer', 'integer'), array($a_frm_id, $a_frm_id, $a_thread_id));
				
			while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
			{
				$count_read = $row->count_read;
			}
			$unread = $num_posts - $count_read;

			$ilBench->stop("Forum",'getCountRead');
			return $unread > 0 ? $unread : 0;
		}
		$ilBench->stop("Forum",'getCountRead');
		return false;
	}


	function markThreadRead($a_usr_id,$a_thread_id)
	{
		global $ilDB;
		
		// Get all post ids
		$res = $ilDB->queryf('
			SELECT * FROM frm_posts WHERE pos_thr_fk = %s',
			array('integer'), array($a_thread_id));
		
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->markPostRead($a_usr_id,$a_thread_id,$row->pos_pk);
		}
		return true;
	}

	function markAllThreadsRead($a_usr_id)
	{
		global $ilDB;
		
		$res = $ilDB->queryf('
			SELECT * FROM frm_data, frm_threads 
			WHERE top_frm_fk = %s
			AND top_pk = thr_top_fk',
			array('integer'), array($this->getId()));
		
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->markThreadRead($a_usr_id,$row->thr_pk);
		}

		return true;
	}
		

	function markPostRead($a_usr_id,$a_thread_id,$a_post_id)
	{
		global $ilDB;
		
		// CHECK IF ENTRY EXISTS
		$res = $ilDB->queryf('
			SELECT * FROM frm_user_read 
			WHERE usr_id = %s
			AND obj_id = %s
			AND thread_id = %s
			AND post_id = %s',
			array('integer', 'integer', 'integer', 'integer'),
			array($a_usr_id, $this->getId(), $a_thread_id, $a_post_id));
		
		if($res->numRows())
		{
			return true;
		}

		$res = $ilDB->manipulateF('
			INSERT INTO frm_user_read
			(	usr_id,
				obj_id,
				thread_id,
				post_id
			)
			VALUES (%s,%s,%s,%s)',
			array('integer', 'integer', 'integer', 'integer'),
			array($a_usr_id, $this->getId(), $a_thread_id, $a_post_id));
		
		return true;
	}

	public function markPostUnread($a_user_id, $a_post_id)
	{
		global $ilDB;

		$res = $ilDB->manipulateF('
			DELETE FROM frm_user_read
			WHERE usr_id = %s
			AND post_id = %s',
			array('integer','integer'),
			array($a_user_id, $a_post_id));
	}
	function isRead($a_usr_id,$a_post_id)
	{
		global $ilDB;
		
		$res = $ilDB->queryf('
			SELECT * FROM frm_user_read
			WHERE usr_id = %s
			AND post_id = %s',
			array('integer', 'integer'),
			array($a_usr_id, $a_post_id));
		
		return $ilDB->numRows($res) ? true : false;
	}


	// METHODS FOR NEW STATUS
	function getCountNew($a_usr_id,$a_thread_id = 0)
	{
		global $ilBench, $ilDB;

		$ilBench->start('Forum','getCountNew');
		if($a_thread_id)
		{
			$num = $this->__getCountNew($a_usr_id,$a_thread_id);
			$ilBench->stop('Forum','getCountNew');

			return $num;
		}
		else
		{
			$counter = 0;

			// Get threads
			$res = $ilDB->queryf('
				SELECT DISTINCT(pos_thr_fk) FROM frm_posts,frm_data
				WHERE top_pk = pos_top_fk 
				AND top_frm_fk = %s',
				array('integer'), array($this->getId()));
			
			while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
			{
				$counter += $this->__getCountNew($a_usr_id,$row->pos_thr_fk);
			}
			$ilBench->stop('Forum','getCountNew');
			return $counter;
		}
		return 0;
	}


	function __getCountNew($a_usr_id,$a_thread_id = 0)
	{
		global $ilDB;
		
		$counter = 0;
		
		$timest = $this->__getLastThreadAccess($a_usr_id,$a_thread_id);

		// CHECK FOR NEW
		$res = $ilDB->queryf('
			SELECT pos_pk FROM frm_posts
			WHERE pos_thr_fk = %s
			AND ( pos_date > %s OR pos_update > %s)
			AND pos_usr_id != %s',
			array('integer', 'timestamp', 'timestamp', 'integer'),
			array($a_thread_id, date('Y-m-d H:i:s',$timest), date('Y-m-d H:i:s',$timest), $a_usr_id));
				
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			if(!$this->isRead($a_usr_id,$row->pos_pk))
			{
				++$counter;
			}
		}
		return $counter;
	}

	function isNew($a_usr_id,$a_thread_id,$a_post_id)
	{
		global $ilDB;
		
		if($this->isRead($a_usr_id,$a_post_id))
		{
			return false;
		}
		$timest = $this->__getLastThreadAccess($a_usr_id,$a_thread_id);
		
		$res = $ilDB->queryf('
			SELECT * FROM frm_posts 
			WHERE pos_pk = %s
			AND (pos_date > %s OR pos_update > %s)
			AND pos_usr_id != %s',
			array('integer', 'timestamp', 'timestamp', 'integer'),
			array($a_post_id, date('Y-m-d H:i:s',$timest), date('Y-m-d H:i:s',$timest), $a_usr_id));		
		
		return $res->numRows() ? true : false;
	}

	function updateLastAccess($a_usr_id,$a_thread_id)
	{
		global $ilDB;
	
		$res = $ilDB->queryf('
			SELECT * FROM frm_thread_access 
			WHERE usr_id = %s
			AND obj_id = %s
			AND thread_id = %s',
			array('integer', 'integer', 'integer'),
			array($a_usr_id, $this->getId(), $a_thread_id));
		
		if($res->numRows())
		{
			$res = $ilDB->manipulateF('
				UPDATE frm_thread_access 
				SET access_last = %s
				WHERE usr_id = %s
				AND obj_id = %s
				AND thread_id = %s',
				array('integer', 'integer', 'integer', 'integer'),
				array(time(), $a_usr_id, $this->getId(), $a_thread_id));

		}
		else
		{
			$res = $ilDB->manipulateF('
				INSERT INTO frm_thread_access 
				(	access_last,
					access_old,
				 	usr_id,
				 	obj_id,
				 	thread_id)
				VALUES (%s,%s,%s,%s,%s)',
				array('integer', 'integer', 'integer', 'integer', 'integer'),
				array(time(), '0', $a_usr_id, $this->getId(), $a_thread_id));
				
		}			

		return true;
	}

	// STATIC
	function _updateOldAccess($a_usr_id)
	{
		global $ilDB, $ilias;

		$res = $ilDB->manipulateF('
			UPDATE frm_thread_access 
			SET access_old = access_last
			WHERE usr_id = %s',
			array('integer'), array($a_usr_id));

		// set access_old_ts value
		$set = $ilDB->query("SELECT * FROM frm_thread_access ".
			" WHERE usr_id = ".$ilDB->quote($a_usr_id, "integer")
			);
		while ($rec = $ilDB->fetchAssoc($set))
		{
			$ilDB->manipulate("UPDATE frm_thread_access SET ".
				" access_old_ts = ".$ilDB->quote(date('Y-m-d H:i:s', $rec["access_old"]), "timestamp").
				" WHERE usr_id = ".$ilDB->quote($rec["usr_id"], "integer").
				" AND obj_id = ".$ilDB->quote($rec["obj_id"], "integer").
				" AND thread_id = ".$ilDB->quote($rec["thread_id"], "integer")
				);
		}
					
		// Delete old entries

		$new_deadline = time() - 60 * 60 * 24 * 7 * ($ilias->getSetting('frm_store_new') ?
													 $ilias->getSetting('frm_store_new') : 
													 8);

			$res = $ilDB->manipulateF('
			DELETE FROM frm_thread_access WHERE access_last < %s',
			array('integer'), array($new_deadline));
		
		return true;
	}

	function _deleteUser($a_usr_id)
	{

		global $ilDB;

		$data = array($a_usr_id);
		
		$res = $ilDB->manipulateF('
			DELETE FROM frm_user_read WHERE usr_id = %s',
			array('integer'), $data
		);
		
		$res = $ilDB->manipulateF('
			DELETE FROM frm_thread_access WHERE usr_id = %s',
			array('integer'), $data
		);
		
		return true;
	}


	function _deleteReadEntries($a_post_id)
	{
		global $ilDB;

		$statement = $ilDB->manipulateF('
			DELETE FROM frm_user_read WHERE post_id = %s',
			array('integer'), array($a_post_id));
		
		return true;
	}

	function _deleteAccessEntries($a_thread_id)
	{
		global $ilDB;

		$statement = $ilDB->manipulateF('
			DELETE FROM frm_thread_access WHERE thread_id = %s',
			array('integer'), array($a_thread_id));
			
		return true;
	}
	
	/**
	* update forum data
	*
	* @access	public
	*/
	function update()
	{
		global $ilDB;
		
		if (parent::update())
		{
			
			$statement = $ilDB->manipulateF('
				UPDATE frm_data 
				SET top_name = %s,
					top_description = %s,
					top_update = %s,
					update_user = %s
				WHERE top_frm_fk =%s',
				array('text', 'text', 'timestamp', 'integer', 'integer'),
				array(	$this->getTitle(),
							$this->getDescription(), 
							date("Y-m-d H:i:s"), 
							(int)$_SESSION["AccountId"],
							(int)$this->getId()
			));
			
			return true;
		}

		return false;
	}
	
	/**
	 * Clone Object
	 *
	 * @access public
	 * @param int source_id
	 * @apram int copy id
	 * 
	 */
	public function cloneObject($a_target_id, $a_copy_id = 0)
	{
		global $ilDB,$ilUser;
		
	 	$new_obj = parent::cloneObject($a_target_id, $a_copy_id);
	 	$this->cloneAutoGeneratedRoles($new_obj);

		ilForumProperties::getInstance($this->getId())->copy($new_obj->getId());
		$this->Forum->setMDB2WhereCondition('top_frm_fk = %s ', array('integer'), array($this->getId()));
		
		$topData = $this->Forum->getOneTopic();

		$nextId = $ilDB->nextId('frm_data');

		$statement = $ilDB->insert('frm_data', array(
			'top_pk'		=> array('integer', $nextId),
			'top_frm_fk'	=> array('integer', $new_obj->getId()),
			'top_name'		=> array('text', $topData['top_name']),
			'top_description' => array('text', $topData['top_description']),
			'top_num_posts' => array('integer', $topData['top_num_posts']),
			'top_num_threads' => array('integer', $topData['top_num_threads']),
			'top_last_post' => array('text', $topData['top_last_post']),
			'top_mods'		=> array('integer', !is_numeric($topData['top_mods']) ? 0 : $topData['top_mods']),
			'top_date'		=> array('timestamp', $topData['top_date']),
			'visits'		=> array('integer', $topData['visits']),
			'top_update'	=> array('timestamp', $topData['top_update']),
			'update_user'	=> array('integer', $topData['update_user']),
			'top_usr_id'	=> array('integer', $topData['top_usr_id'])
		));
		
		// read options
		include_once('Services/CopyWizard/classes/class.ilCopyWizardOptions.php');

		$cwo = ilCopyWizardOptions::_getInstance($a_copy_id);
		$options = $cwo->getOptions($this->getRefId());

		$options['threads'] = $this->Forum->_getThreads($this->getId());

		// Generate starting threads
		include_once('Modules/Forum/classes/class.ilFileDataForum.php');
		
		$new_frm = $new_obj->Forum;
		$new_frm->setMDB2WhereCondition('top_frm_fk = %s ', array('integer'), array($new_obj->getId()));
				
		$new_frm->setForumId($new_obj->getId());
		$new_frm->setForumRefId($new_obj->getRefId());
	
		$new_topic = $new_frm->getOneTopic();
		foreach($options['threads'] as $thread_id=>$thread_subject)
		{
			$this->Forum->setMDB2WhereCondition('thr_pk = %s ', array('integer'), array($thread_id));			
			
			$old_thread = $this->Forum->getOneThread();
			
			$old_post_id = $this->Forum->getFirstPostByThread($old_thread['thr_pk']);
			$old_post = $this->Forum->getOnePost($old_post_id);

			// Now create new thread and first post
			$new_post = $new_frm->generateThread($new_topic['top_pk'],
				$old_thread['thr_usr_id'],
				$old_thread['thr_subject'],
				ilForum::_lookupPostMessage($old_post_id),
				$old_post['notify'],
				0,
				$old_thread['thr_usr_alias'],
				$old_thread['thr_date']);
			// Copy attachments
			$old_forum_files = new ilFileDataForum($this->getId(),$old_post_id);
			$old_forum_files->ilClone($new_obj->getId(),$new_post);
		}
		
		return $new_obj;
	}
	
	/**
	 * Clone forum moderator role 
	 *
	 * @access public
	 * @param object forum object
	 * 
	 */
	public function cloneAutoGeneratedRoles($new_obj)
	{
		global $ilLog,$rbacadmin,$rbacreview;

		$moderator = ilObjForum::_lookupModeratorRole($this->getRefId());
		$new_moderator = ilObjForum::_lookupModeratorRole($new_obj->getRefId());
	 	$source_rolf = $rbacreview->getRoleFolderIdOfObject($this->getRefId());
	 	$target_rolf = $rbacreview->getRoleFolderIdOfObject($new_obj->getRefId());
	 	
		if(!$moderator || !$new_moderator || !$source_rolf || !$target_rolf)
		{
			$ilLog->write(__METHOD__.' : Error cloning auto generated role: il_frm_moderator');
		}
	 	$rbacadmin->copyRolePermissions($moderator,$source_rolf,$target_rolf,$new_moderator,true);
		$ilLog->write(__METHOD__.' : Finished copying of role il_frm_moderator.');

		include_once './Modules/Forum/classes/class.ilForumModerators.php';
		$obj_mods = new ilForumModerators($this->getRefId());
		
		$old_mods = array();
		$old_mods = $obj_mods->getCurrentModerators();

		foreach($old_mods as $user_id)
		{
			$rbacadmin->assignUser($new_moderator, $user_id);
		}
	}	

	/**
	* Delete forum and all related data	
	*
	* @access	public
	* @return	boolean	true if all object data were removed; false if only a references were removed
	*/
	function delete()
	{
		global $ilDB;
			
		// always call parent delete function first!!
		if (!parent::delete())
		{
			return false;
		}
		
		// delete attachments
		$tmp_file_obj =& new ilFileDataForum($this->getId());
		$tmp_file_obj->delete();
		unset($tmp_file_obj);

		$this->Forum->setMDB2WhereCondition('top_frm_fk = %s ', array('integer'), array($this->getId()));
		
		$topData = $this->Forum->getOneTopic();	
		
		$threads = $this->Forum->getAllThreads($topData['top_pk']);
		foreach ($threads as $thread)
		{
			$data = array($thread->getId());

			// delete tree
			$statement = $ilDB->manipulateF('
				DELETE FROM frm_posts_tree WHERE thr_fk = %s',
				array('integer'), $data);
								
			// delete posts
			$statement = $ilDB->manipulateF('
				DELETE FROM frm_posts WHERE pos_thr_fk = %s',
				array('integer'), $data);
			
			// delete threads
			$statement = $ilDB->manipulateF('
				DELETE FROM frm_threads WHERE thr_pk = %s',
				array('integer'), $data);
		
		}

		$data = array($this->getId());
		// delete forum
		$statement = $ilDB->manipulateF('
			DELETE FROM frm_data WHERE top_frm_fk = %s',
			array('integer'), $data);

		// delete settings
		$statement = $ilDB->manipulateF('
			DELETE FROM frm_settings WHERE obj_id = %s',
			array('integer'), $data);
		
		// delete read infos
		$statement = $ilDB->manipulateF('
			DELETE FROM frm_user_read WHERE obj_id = %s',
			array('integer'), $data);
		
		// delete thread access entries
		$statement = $ilDB->manipulateF('
			DELETE FROM frm_thread_access WHERE obj_id = %s',
			array('integer'), $data);
		
		return true;
	}

	/**
	* init default roles settings
	* @access	public
	* @return	array	object IDs of created local roles.
	*/
	function initDefaultRoles()
	{
		global $rbacadmin,$rbacreview,$ilDB;

		// Create a local role folder
		$rolf_obj = $this->createRoleFolder();

		// CREATE Moderator role
		$role_obj = $rolf_obj->createRole("il_frm_moderator_".$this->getRefId(),"Moderator of forum obj_no.".$this->getId());
		$roles[] = $role_obj->getId();
		
		// SET PERMISSION TEMPLATE OF NEW LOCAL ADMIN ROLE
		$statement = $ilDB->queryf('
			SELECT obj_id FROM object_data 
			WHERE type = %s 
			AND title = %s',
			array('text', 'text'),
			array('rolt', 'il_frm_moderator'));
		
		$res = $statement->fetchRow(DB_FETCHMODE_OBJECT);
		
		$rbacadmin->copyRoleTemplatePermissions($res->obj_id,ROLE_FOLDER_ID,$rolf_obj->getRefId(),$role_obj->getId());

		// SET OBJECT PERMISSIONS OF COURSE OBJECT
		$ops = $rbacreview->getOperationsOfRole($role_obj->getId(),"frm",$rolf_obj->getRefId());
		$rbacadmin->grantPermission($role_obj->getId(),$ops,$this->getRefId());

		return $roles ? $roles : array();
	}
	
	/**
	 * Lookup moderator role
	 *
	 * @access public
	 * @static
	 * @param int ref_id of forum
	 * 
	 */
	public static function _lookupModeratorRole($a_ref_id)
	{
		global $ilDB;
		
		$mod_title = 'il_frm_moderator_'.$a_ref_id;

		$res = $ilDB->queryf('
			SELECT * FROM object_data WHERE title = %s',
			array('text'), array($mod_title));
		
	 	while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
	 	{
	 		return $row->obj_id;
	 	}
	 	return 0;
	}


	function createSettings()
	{		
		global $ilDB;
		
		// news settings (public notifications yes/no)
		include_once("./Services/News/classes/class.ilNewsItem.php");
		$default_visibility = ilNewsItem::_getDefaultVisibilityForRefId($_GET["ref_id"]);
		if ($default_visibility == "public")
		{
			ilBlockSetting::_write("news", "public_notifications", 1, 0, $this->getId());
		}

		return true;
	}	

	function __getLastThreadAccess($a_usr_id,$a_thread_id)
	{
		global $ilDB;

		$res = $ilDB->queryf('
			SELECT * FROM frm_thread_access 
			WHERE thread_id = %s
			AND usr_id = %s',
			array('integer', 'integer'),
			array($a_thread_id, $a_usr_id));
		
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$last_access = $row->access_old;
		}
		if(!$last_access)
		{
			// Set last access according to administration setting
			$last_access = NEW_DEADLINE;
		}
		return $last_access;
	}

	/**
	* Check whether a user's notification about new posts in a thread is enabled (result > 0) or not (result == 0)
	* @param    integer	user_id	A user's ID
	* @param    integer	thread_id	ID of the thread
	* @return	integer	Result
	* @access	private
	*/
	function isThreadNotificationEnabled($user_id, $thread_id)
	{		
		global $ilDB;
		
		$result = $ilDB->queryf("SELECT COUNT(*) cnt FROM frm_notification WHERE user_id = %s AND thread_id = %s",
		         	array("integer", "integer"), array($user_id, $thread_id));
		
		while($record = $ilDB->fetchAssoc($result))
		{
			return (bool)$record['cnt'];
		}
		
		return false;
	}
	
	public function saveData($a_roles = array())
	{
		global $ilUser, $ilDB;
		
		$nextId = $ilDB->nextId('frm_data');
		
		$top_data = array(
            'top_frm_fk'   		=> $this->getId(),
			'top_name'   		=> $this->getTitle(),
            'top_description' 	=> $this->getDescription(),
            'top_num_posts'     => 0,
            'top_num_threads'   => 0,
            'top_last_post'     => NULL,
			'top_mods'      	=> !is_numeric($a_roles[0]) ? 0 : $a_roles[0],
			'top_usr_id'      	=> $ilUser->getId(),
            'top_date' 			=> ilUtil::now()
        );       
        
        $statement = $ilDB->manipulateF('
        	INSERT INTO frm_data 
        	( 
        	 	top_pk,
        		top_frm_fk, 
        		top_name,
        		top_description,
        		top_num_posts,
        		top_num_threads,
        		top_last_post,
        		top_mods,
        		top_date,
        		top_usr_id
        	)
        	VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)',
        	array('integer', 'integer', 'text', 'text', 'integer', 'integer', 'text', 'integer', 'timestamp', 'integer'),
        	array(
	        	$nextId,
	        	$top_data['top_frm_fk'],
	        	$top_data['top_name'],
	        	$top_data['top_description'],
	        	$top_data['top_num_posts'],
				$top_data['top_num_threads'],
				$top_data['top_last_post'],
				$top_data['top_mods'],
				$top_data['top_date'],
				$top_data['top_usr_id']
		));
	}
} // END class.ilObjForum
?>
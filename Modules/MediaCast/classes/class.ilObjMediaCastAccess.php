<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
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

include_once("classes/class.ilObjectAccess.php");

/**
* Class ilObjMediaCastAccess
*
*
* @author 		Alex Killing <alex.killing@gmx.de>
* @version $Id: class.ilObjMediaCastAccess.php 12772 2006-12-07 09:34:01Z akill $
*
* @ingroup ModulesMediaCast
*/
class ilObjMediaCastAccess extends ilObjectAccess
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
			array("permission" => "read", "cmd" => "listItems", "lang_var" => "show",
				"default" => true),
			array("permission" => "write", "cmd" => "editSettings", "lang_var" => "edit")
		);
		
		return $commands;
	}
	
		/**
	* checks wether a user may invoke a command or not
	* (this method is called by ilAccessHandler::checkAccess)
	*
	* @param	string		$a_cmd		command (not permission!)
	* @param	string		$a_permission	permission
	* @param	int			$a_ref_id	reference id
	* @param	int			$a_obj_id	object id
	* @param	int			$a_user_id	user id (if not provided, current user is taken)
	*
	* @return	boolean		true, if everything is ok
	*/
	function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = "")
	{
		global $ilUser, $lng, $rbacsystem, $ilAccess;

		if ($a_user_id == "")
		{
			$a_user_id = $ilUser->getId();
		}

		switch ($a_cmd)
		{
			case "listItems":

				if(!ilObjMediaCastAccess::_lookupOnline($a_obj_id)
					&& !$rbacsystem->checkAccessOfUser($a_user_id,'write',$a_ref_id))
				{
					$ilAccess->addInfoItem(IL_NO_OBJECT_ACCESS, $lng->txt("offline"));
					return false;
				}
				break;
				
			// for permission query feature
			case "infoScreen":
				if(!ilObjMediaCastAccess::_lookupOnline($a_obj_id))
				{
					$ilAccess->addInfoItem(IL_NO_OBJECT_ACCESS, $lng->txt("offline"));
				}
				else
				{
					$ilAccess->addInfoItem(IL_STATUS_MESSAGE, $lng->txt("online"));
				}
				break;

		}
		switch ($a_permission)
		{
			case "read":
			case "visible":
				if (!ilObjMediaCastAccess::_lookupOnline($a_obj_id) &&
					(!$rbacsystem->checkAccessOfUser($a_user_id, 'write', $a_ref_id)))
				{
					$ilAccess->addInfoItem(IL_NO_OBJECT_ACCESS, $lng->txt("offline"));
					return false;
				}
				break;
		}

		return true;
	}

	/**
	* check whether goto script will succeed
	*/
	function _checkGoto($a_target)
	{
		global $ilAccess;
		
		$t_arr = explode("_", $a_target);

		if ($t_arr[0] != "mcst" || ((int) $t_arr[1]) <= 0)
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
	* Check wether media cast is online
	*
	* @param	int		$a_id	media cast id
	*/
	function _lookupOnline($a_id)
	{
		global $ilDB;

		$q = "SELECT * FROM il_media_cast_data WHERE id = ".$ilDB->quote($a_id);
		$mc_set = $ilDB->query($q);
		$mc_rec = $mc_set->fetchRow(DB_FETCHMODE_ASSOC);

		return $mc_rec["is_online"];
	}

	/**
	* Check wether files should be public
	*
	* @param	int		$a_id	media cast id
	*/
	function _lookupPublicFiles($a_id)
	{
		global $ilDB;

		$q = "SELECT * FROM il_media_cast_data WHERE id = ".$ilDB->quote($a_id);
		$mc_set = $ilDB->query($q);
		$mc_rec = $mc_set->fetchRow(DB_FETCHMODE_ASSOC);

		return $mc_rec["public_files"];
	}

	/**
	 * Returns the number of bytes used on the harddisk by the file object
	 * with the specified object id.
	 * @param int object id of a file object.
	 */
	function _lookupDiskUsage($a_id)
	{
		require_once('Modules/MediaCast/classes/class.ilObjMediaCast.php');
		require_once("./Services/News/classes/class.ilNewsItem.php");
		require_once("./Services/MediaObjects/classes/class.ilObjMediaObject.php");
		$obj = new ilObjMediaCast($a_id, false);
                $obj->read();
                $items = $obj->getItemsArray();
                $size = 0;
		foreach ($items as $item)
		{
			$news_item = new ilNewsItem($item["id"]);
			$news_item->read();
                        $mobId = $news_item->getMobId();
			$size += ilUtil::dirsize(ilObjMediaObject::_getDirectory($mobId));
		}
		return $size;
	}
}

?>

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

/**
* Stores calendar categories
* 
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
*
* @ingroup ServicesCalendar 
*/

class ilCalendarCategory
{
	private static $instances = null;

	const DEFAULT_COLOR = '#04427e';
	
	const TYPE_USR = 1;		// user
	const TYPE_OBJ = 2;		// object
	const TYPE_GLOBAL = 3;	// global
	const TYPE_CH = 4;		// consultation hours
	const TYPE_BOOK = 5;	// booking manager
	
	protected $cat_id;
	protected $color;
	protected $type = self::TYPE_USR;
	protected $obj_id;
	protected $obj_type = null;
	protected $title;
	
	protected $db;
	
	
	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct($a_cat_id = 0)
	{
		global $ilDB;
		
		$this->db = $ilDB;
		$this->cat_id = $a_cat_id;
		
		$this->read();
	}
	
	/**
	 * get instance by obj_id 
	 *
	 * @param int obj_id 
	 * @return object
	 * @static
	 */
	 public static function _getInstanceByObjId($a_obj_id)
	 {
	 	global $ilDB;
	 	
	 	$query = "SELECT cat_id FROM cal_categories ".
	 		"WHERE obj_id = ".$ilDB->quote($a_obj_id ,'integer')." ".
	 		"AND type = ".$ilDB->quote(self::TYPE_OBJ ,'integer');
	 	$res = $ilDB->query($query);
	 	while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
	 	{
	 		return new ilCalendarCategory($row->cat_id);
	 	}
	 	return null;
	 }

	 /**
	  * Get instance by category id
	  * @param int $a_cat_id
	  * @return ilCalendarCategory
	  */
	 public static function getInstanceByCategoryId($a_cat_id)
	 {
		 if(!self::$instances[$a_cat_id])
		 {
			 return self::$instances[$a_cat_id] = new ilCalendarCategory($a_cat_id);
		 }
		 return self::$instances[$a_cat_id];
	 }


	 /**
	  * get all assigned appointment ids
	  * @return 
	  * @param object $a_category_id
	  */
	 public static function lookupAppointments($a_category_id)
	 {
	 	global $ilDB;
	
		$query = "SELECT * FROM cal_cat_assignments ".
			'WHERE cat_id = '.$ilDB->quote($a_category_id,'integer');
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$apps[] = $row->cal_id;
		}
		return $apps ? $apps : array();
	 }
	
	
	/**
	 * get category id
	 *
	 * @access public
	 * @return int category id
	 */
	public function getCategoryID()
	{
		return $this->cat_id;
	}
	
	/**
	 * set title
	 *
	 * @access public
	 * @param string title
	 * @return
	 */
	public function setTitle($a_title)
	{
		$this->title = $a_title;
	}
	
	/**
	 * get title
	 *
	 * @access public
	 * @return string title
	 */
	public function getTitle()
	{
		return $this->title;
	}
	
	
	/**
	 * set color
	 *
	 * @access public
	 * @param string color
	 */
	public function setColor($a_color)
	{
		$this->color = $a_color;
	}
	
	/**
	 * get color
	 *
	 * @access public
	 * @return
	 */
	public function getColor()
	{
		return $this->color;
	}
	
	/**
	 * set type
	 *
	 * @access public
	 * @param int type 
	 */
	public function setType($a_type)
	{
		$this->type = $a_type;
	}
	
	/**
	 * get type
	 *
	 * @access public
	 * @return
	 */
	public function getType()
	{
		return $this->type;
	}
	
	/**
	 * set obj id
	 *
	 * @access public
	 * @param int obj_id
	 */
	public function setObjId($a_obj_id)
	{
		$this->obj_id = $a_obj_id;
	}
	
	/**
	 * get obj_id
	 *
	 * @access public
	 * @return
	 */
	public function getObjId()
	{
		return $this->obj_id;
	}
	
	/**
	 * get type
	 *
	 * @access public
	 */
	public function getObjType()
	{
		return $this->obj_type;
	}
	
	
	/**
	 * add new category
	 *
	 * @access public
	 * @return
	 */
	public function add()
	{
		global $ilDB;

		$next_id = $ilDB->nextId('cal_categories');
		
		$query = "INSERT INTO cal_categories (cat_id,obj_id,color,type,title) ".
			"VALUES ( ".
			$ilDB->quote($next_id,'integer').", ".
			$this->db->quote($this->getObjId() ,'integer').", ".
			$this->db->quote($this->getColor() ,'text').", ".
			$this->db->quote($this->getType() ,'integer').", ".
			$this->db->quote($this->getTitle() ,'text')." ".
			")";
		$res = $ilDB->manipulate($query);

		$this->cat_id = $next_id;
		return $this->cat_id;
	}
	
	/**
	 * update
	 *
	 * @access public
	 * @return
	 */
	public function update()
	{
		global $ilDB;
		
		$query = "UPDATE cal_categories ".
			"SET obj_id = ".$this->db->quote($this->getObjId() ,'integer').", ".
			"color = ".$this->db->quote($this->getColor() ,'text').", ".
			"type = ".$this->db->quote($this->getType() ,'integer').", ".
			"title = ".$this->db->quote($this->getTitle() ,'text')." ".
			"WHERE cat_id = ".$this->db->quote($this->cat_id ,'integer')." ";
		$res = $ilDB->manipulate($query);
		return true;
	}

	/**
	 * delete
	 *
	 * @access public
	 * @return
	 */
	public function delete()
	{
		global $ilDB;
		
		$query = "DELETE FROM cal_categories ".
			"WHERE cat_id = ".$this->db->quote($this->cat_id ,'integer')." ";
		$res = $ilDB->manipulate($query);

		include_once('./Services/Calendar/classes/class.ilCalendarHidden.php');
		ilCalendarHidden::_deleteCategories($this->cat_id);
		
		include_once('./Services/Calendar/classes/class.ilCalendarCategoryAssignments.php');
		foreach(ilCalendarCategoryAssignments::_getAssignedAppointments(array($this->cat_id)) as $app_id)
		{
			include_once('./Services/Calendar/classes/class.ilCalendarEntry.php');
			ilCalendarEntry::_delete($app_id);
		}
		ilCalendarCategoryAssignments::_deleteByCategoryId($this->cat_id);
	}
	
	/**
	 * validate
	 *
	 * @access public
	 * @return bool
	 */
	public function validate()
	{
		return strlen($this->getTitle()) and strlen($this->getColor()) and $this->getType();
	}
	
	/**
	 * read
	 *
	 * @access protected
	 */
	private function read()
	{
		global $ilDB;
		
		if(!$this->cat_id)
		{
			return true;
		}
		
		$query = "SELECT * FROM cal_categories ".
			"WHERE cat_id = ".$this->db->quote($this->getCategoryID() ,'integer')." ";
		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->cat_id = $row->cat_id;
			$this->obj_id = $row->obj_id;
			$this->type = $row->type;
			$this->color = $row->color;
			$this->title = $row->title;
		}
		if($this->getType() == self::TYPE_OBJ)
		{
			$this->title = ilObject::_lookupTitle($this->getObjId());
			$this->obj_type = ilObject::_lookupType($this->getObjId());
		}
	}
}
?>
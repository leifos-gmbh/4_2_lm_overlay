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
* @defgroup ModulesRemoteCourse Modules/RemoteCourse
* 
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
* 
* 
* @ingroup ModulesRemoteCourse
*/

class ilObjRemoteCourse extends ilObject
{
	const ACTIVATION_OFFLINE = 0;
	const ACTIVATION_UNLIMITED = 1;
	const ACTIVATION_LIMITED = 2;
	
	protected $availability_type;
	protected $end;
	protected $start;
	protected $local_information;
	protected $remote_link;
	protected $organization;
	protected $mid;
	
	protected $auth_hash = '';

	/**
	 * Constructor
	 *
	 * @access public
	 * 
	 */
	public function __construct($a_id = 0,$a_call_by_reference = true)
	{
		global $ilDB;
		
		$this->type = "rcrs";
		$this->ilObject($a_id,$a_call_by_reference);
		$this->db = $ilDB;
	}
	
	/**
	 * Lookup online
	 *
	 * @access public
	 * @static
	 *
	 * @param int obj_id
	 */
	public static function _lookupOnline($a_obj_id)
	{
		global $ilDB;
		
		$query = "SELECT * FROM remote_course_settings ".
			"WHERE obj_id = ".$ilDB->quote($a_obj_id ,'integer')." ";
		$res = $ilDB->query($query);
		$row = $res->fetchRow(DB_FETCHMODE_OBJECT);
		switch($row->availability_type)
		{
			case self::ACTIVATION_UNLIMITED:
				return true;
				
			case self::ACTIVATION_OFFLINE:
				return false;
				
			case self::ACTIVATION_LIMITED:
				return time() > $row->r_start && time < $row->r_end;
				
			default:
				return false;
		}
		
		return false;
	}
	
	/**
	 * lookup organization
	 *
	 * @access public
	 * @static
	 *
	 * @param int obj_id
	 */
	public static function _lookupOrganization($a_obj_id)
	{
		global $ilDB;
		
		$query = "SELECT organization FROM remote_course_settings ".
			"WHERE obj_id = ".$ilDB->quote($a_obj_id ,'integer')." ";
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return $row->organization;
		}
		return '';
	}	

	/**
	 * set organization
	 *
	 * @access public
	 * @param string organization
	 * 
	 */
	public function setOrganization($a_organization)
	{
	 	$this->organization = $a_organization;
	}
	
	/**
	 * get organization
	 *
	 * @access public
	 * 
	 */
	public function getOrganization()
	{
	 	return $this->organization;
	}
	
	/**
	 * get local information
	 *
	 * @access public
	 * 
	 */
	public function getLocalInformation()
	{
	 	return $this->local_information;
	}
	
	/**
	 * set local information
	 *
	 * @access public
	 * @param string local information
	 * 
	 */
	public function setLocalInformation($a_info)
	{
	 	$this->local_information = $a_info;
	}
	
	/**
	 * Set Availability type
	 *
	 * @access public
	 * @param int availability type
	 * 
	 */
	public function setAvailabilityType($a_type)
	{
	 	$this->availability_type = $a_type;
	}
	
	/**
	 * get availability type
	 *
	 * @access public
	 * 
	 */
	public function getAvailabilityType()
	{
	 	return $this->availability_type;
	}
	
	/**
	 * set starting time
	 *
	 * @access public
	 * @param int statrting time
	 * 
	 */
	public function setStartingTime($a_time)
	{
	 	$this->start = $a_time;
	}
	
	/**
	 * getStartingTime
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function getStartingTime()
	{
	 	return $this->start;
	}

	/**
	 * set ending time
	 *
	 * @access public
	 * @param int statrting time
	 * 
	 */
	public function setEndingTime($a_time)
	{
	 	$this->end = $a_time;
	}
	
	/**
	 * get ending time
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function getEndingTime()
	{
	 	return $this->end;
	}
	
	/**
	 * set remote link
	 *
	 * @access public
	 * @param string link to original course
	 * 
	 */
	public function setRemoteLink($a_link)
	{
	 	$this->remote_link = $a_link;
	}
	
	/**
	 * get remote link
	 *
	 * @access public
	 * @return string remote link
	 * 
	 */
	public function getRemoteLink()
	{
	 	return $this->remote_link;
	}
	
	/**
	 * get full remote link 
	 * Including ecs generated hash and auth mode
	 *
	 * @access public
	 * 
	 */
	public function getFullRemoteLink()
	{
	 	global $ilUser;
	 	
	 	include_once('./Services/WebServices/ECS/classes/class.ilECSUser.php');
	 	$user = new ilECSUser($ilUser);
	 	$ecs_user_data = $user->toGET();
	 	return $this->getRemoteLink().'&ecs_hash='.$this->auth_hash.$ecs_user_data;
	}
	
	/**
	 * get mid
	 *
	 * @access public
	 * 
	 */
	public function getMID()
	{
	 	return $this->mid;
	}
	
	/**
	 * set mid
	 *
	 * @access public
	 * @param int mid
	 * 
	 */
	public function setMID($a_mid)
	{
	 	$this->mid = $a_mid;
	}
	
	/**
	 * lookup owner mid
	 *
	 * @access public
	 * @static
	 * @param int obj_id
	 */
	public static function _lookupMID($a_obj_id)
	{
		global $ilDB;
		
		$query = "SELECT mid FROM remote_course_settings WHERE ".
			"obj_id = ".$ilDB->quote($a_obj_id ,'integer')." ";
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return $row->mid;
		}
		return 0;
	}
	
	/**
	 * lookup obj ids by mid
	 *
	 * @access public
	 * @param int mid
	 * @return array obj ids
	 * @static
	 */
	public static function _lookupObjIdsByMID($a_mid)
	{
		global $ilDB;
		
		$query = "SELECT * FROM remote_course_settings ".
			"WHERE mid = ".$ilDB->quote($a_mid ,'integer')." ";
			
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$obj_ids[] = $row->obj_id;
		}
		return $obj_ids ? $obj_ids : array();
	}
	
	/**
	 * create authentication resource on ecs server
	 *
	 * @access public
	 * 
	 */
	public function createAuthResource()
	{
	 	global $ilLog;
	 	
	 	include_once('Services/WebServices/ECS/classes/class.ilECSAuth.php');
	 	include_once('Services/WebServices/ECS/classes/class.ilECSConnector.php');
		include_once './Services/WebServices/ECS/classes/class.ilECSImport.php';
		include_once './Services/WebServices/ECS/classes/class.ilECSSetting.php';

		try
		{	 	
			$server_id = ilECSImport::lookupServerId($this->getId());
			
			$connector = new ilECSConnector(ilECSSetting::getInstanceByServerId($server_id));
			$auth = new ilECSAuth();
			$auth->setUrl($this->getRemoteLink());
			$this->auth_hash = $connector->addAuth(@json_encode($auth),$this->getMID());
			return true;
		}
		catch(ilECSConnectorException $exc)
		{
			$ilLog->write(__METHOD__.': Caught error from ECS Auth resource: '.$exc->getMessage());	
			return false;
		}
	}
	
	/**
	 * Create remote course
	 *
	 * @access public
	 * 
	 */
	public function create($a_upload = false)
	{
		global $ilDB;
		
		$obj_id = parent::create($a_upload);
		
		$query = "INSERT INTO remote_course_settings (obj_id,local_information,availability_type,r_start,r_end,remote_link,mid,organization) ".
			"VALUES( ".			
			$this->db->quote($this->getId() ,'integer').", ".
			$ilDB->quote('','text').", ".
			$ilDB->quote(0,'integer').", ".
			$ilDB->quote(0,'integer').", ".
			$ilDB->quote(0,'integer').", ".
			$ilDB->quote('','text').", ".
			$ilDB->quote(0,'integer').", ".
			$ilDB->quote('','text')." ".
			")";
		$res = $ilDB->manipulate($query);
		
		return $obj_id;
	}
	
	

	/**
	 * Update function 
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function update()
	{
		global $ilDB;
		
		if (!parent::update())
		{			
			return false;
		}
		
		$query = "UPDATE remote_course_settings SET ".
			"availability_type = ".(int) $this->db->quote($this->getAvailabilityType() ,'integer').", ".
			"r_start = ".$this->db->quote($this->getStartingTime() ,'integer').", ".
			"r_end = ".$this->db->quote($this->getEndingTime() ,'integer').", ".
			"local_information = ".$this->db->quote($this->getLocalInformation() ,'text').", ".
			"remote_link = ".$this->db->quote($this->getRemoteLink() ,'text').", ".
			"mid = ".$this->db->quote($this->getMID() ,'integer').", ".
			"organization = ".$this->db->quote($this->getOrganization() ,'text')." ".
			"WHERE obj_id = ".$this->db->quote($this->getId() ,'integer')." ";
		$res = $ilDB->manipulate($query);	
		return true;
	}
	
	/**
	 * Delete this remote course
	 *
	 * @access public
	 * 
	 */
	public function delete()
	{
		global $ilDB;
		
		if(!parent::delete())
		{
			return false;
		}
		
		//put here your module specific stuff
		include_once('./Services/WebServices/ECS/classes/class.ilECSImport.php');
		ilECSImport::_deleteByObjId($this->getId());
		
		$query = "DELETE FROM remote_course_settings WHERE obj_id = ".$this->db->quote($this->getId() ,'integer')." ";
		$res = $ilDB->manipulate($query);
		
		return true;
	}
	
	/**
	 * read settings
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function read($a_force_db = false)
	{
		parent::read($a_force_db);

		$query = "SELECT * FROM remote_course_settings ".
			"WHERE obj_id = ".$this->db->quote($this->getId() ,'integer')." ";
		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->setLocalInformation($row->local_information);
			$this->setAvailabilityType($row->availability_type);
			$this->setStartingTime($row->r_start);
			$this->setEndingTime($row->r_end);
			$this->setRemoteLink($row->remote_link);
			$this->setMID($row->mid);
			$this->setOrganization($row->organization);
		}
	}
	
	/**
	 * create remote course from ECSContent object
	 *
	 * @access public
	 * @static
	 * @param int mid
	 *
	 * @param ilECSEContent object with course settings
	 */
	public static function _createFromECSEContent($a_server_id,ilECSEContent $ecs_content, $a_mid)
	{
		global $ilAppEventHandler;

		include_once('./Services/WebServices/ECS/classes/class.ilECSSetting.php');
		include_once './Services/WebServices/ECS/classes/class.ilECSCategoryMapping.php';
		$ecs_settings = ilECSSetting::getInstanceByServerId($a_server_id);
		
		$remote_crs = new ilObjRemoteCourse();
		$remote_crs->setType('rcrs');
		$remote_crs->setOwner(0);
		$new_obj_id = $remote_crs->create();
		$remote_crs->createReference();
		$remote_crs->putInTree(ilECSCategoryMapping::getMatchingCategory($a_server_id,$ecs_content));
		$remote_crs->setPermissions($ecs_settings->getImportId());
		
		$remote_crs->setECSImported($a_server_id,$ecs_content->getEContentId(),$a_mid,$new_obj_id);
		$remote_crs->updateFromECSContent($a_server_id,$ecs_content);
		
		$ilAppEventHandler->raise(
			'Modules/RemoteCourse',
			'create',
			array(
				'rcrs' => $remote_crs,
				'server_id' => $a_server_id
			)
		);
		return $remote_crs;
	}
	
	/**
	 * update remote course settings from ecs content
	 *
	 * @access public
	 * @param ilECSEContent object with course settings
	 * 
	 */
	public function updateFromECSContent($a_server_id,ilECSEContent $ecs_content)
	{
		include_once('./Services/WebServices/ECS/classes/class.ilECSDataMappingSettings.php');
		include_once('./Services/AdvancedMetaData/classes/class.ilAdvancedMDValue.php');
		include_once('./Services/AdvancedMetaData/classes/class.ilAdvancedMDFieldDefinition.php');
		
		$mappings = ilECSDataMappingSettings::getInstanceByServerId($a_server_id);
		
		$this->setTitle($ecs_content->getTitle());
		$this->setDescription($ecs_content->getAbstract());
		$this->setOrganization($ecs_content->getOrganization());
		$this->setAvailabilityType($ecs_content->isOnline() ? self::ACTIVATION_UNLIMITED : self::ACTIVATION_OFFLINE);
		$this->setRemoteLink($ecs_content->getURL());
		$this->setMID($ecs_content->getOwner());
		
		$this->update();
		
		// Study courses
		if($field = $mappings->getMappingByECSName(ilECSDataMappingSetting::MAPPING_IMPORT_RCRS,'study_courses'))
		{
			$value = ilAdvancedMDValue::_getInstance($this->getId(),$field);
			$value->toggleDisabledStatus(true); 
			$value->setValue($ecs_content->getStudyCourses());
			$value->save();
		}

		// Lecturer
		if($field = $mappings->getMappingByECSName(ilECSDataMappingSetting::MAPPING_IMPORT_RCRS,'lecturer'))
		{
			$value = ilAdvancedMDValue::_getInstance($this->getId(),$field);
			$value->toggleDisabledStatus(true); 
			$value->setValue($ecs_content->getLecturers());
			$value->save();
		}
		// CourseType
		if($field = $mappings->getMappingByECSName(ilECSDataMappingSetting::MAPPING_IMPORT_RCRS,'courseType'))
		{
			$value = ilAdvancedMDValue::_getInstance($this->getId(),$field);
			$value->toggleDisabledStatus(true); 
			$value->setValue($ecs_content->getCourseType());
			$value->save();
		}
		// CourseID
		if($field = $mappings->getMappingByECSName(ilECSDataMappingSetting::MAPPING_IMPORT_RCRS,'courseID'))
		{
			$value = ilAdvancedMDValue::_getInstance($this->getId(),$field);
			$value->toggleDisabledStatus(true); 
			$value->setValue($ecs_content->getCourseID());
			$value->save();
		}		
		// Credits
		if($field = $mappings->getMappingByECSName(ilECSDataMappingSetting::MAPPING_IMPORT_RCRS,'credits'))
		{
			$value = ilAdvancedMDValue::_getInstance($this->getId(),$field);
			$value->toggleDisabledStatus(true); 
			$value->setValue($ecs_content->getCredits());
			$value->save();
		}
		
		if($field = $mappings->getMappingByECSName(ilECSDataMappingSetting::MAPPING_IMPORT_RCRS,'semester_hours'))
		{
			$value = ilAdvancedMDValue::_getInstance($this->getId(),$field);
			$value->toggleDisabledStatus(true); 
			$value->setValue($ecs_content->getSemesterHours());
			$value->save();
		}
		// Term
		if($field = $mappings->getMappingByECSName(ilECSDataMappingSetting::MAPPING_IMPORT_RCRS,'term'))
		{
			$value = ilAdvancedMDValue::_getInstance($this->getId(),$field);
			$value->toggleDisabledStatus(true); 
			$value->setValue($ecs_content->getTerm());
			$value->save();
		}
		
		// TIME PLACE OBJECT ########################
		if($field = $mappings->getMappingByECSName(ilECSDataMappingSetting::MAPPING_IMPORT_RCRS,'begin'))
		{
			$value = ilAdvancedMDValue::_getInstance($this->getId(),$field);
			$value->toggleDisabledStatus(true); 
			
			switch(ilAdvancedMDFieldDefinition::_lookupFieldType($field))
			{
				case ilAdvancedMDFieldDefinition::TYPE_DATE:
				case ilAdvancedMDFieldDefinition::TYPE_DATETIME:
					$value->setValue($ecs_content->getTimePlace()->getUTBegin());
					break;
				default:
					$value->setValue($ecs_content->getTimePlace()->getBegin());
					break;
			}
			$value->save();
		}
		if($field = $mappings->getMappingByECSName(ilECSDataMappingSetting::MAPPING_IMPORT_RCRS,'end'))
		{
			$value = ilAdvancedMDValue::_getInstance($this->getId(),$field);
			$value->toggleDisabledStatus(true); 
			switch(ilAdvancedMDFieldDefinition::_lookupFieldType($field))
			{
				case ilAdvancedMDFieldDefinition::TYPE_DATE:
				case ilAdvancedMDFieldDefinition::TYPE_DATETIME:
					$value->setValue($ecs_content->getTimePlace()->getUTEnd());
					break;
				default:
					$value->setValue($ecs_content->getTimePlace()->getEnd());
					break;
			}
			$value->save();
		}
		if($field = $mappings->getMappingByECSName(ilECSDataMappingSetting::MAPPING_IMPORT_RCRS,'room'))
		{
			$value = ilAdvancedMDValue::_getInstance($this->getId(),$field);
			$value->toggleDisabledStatus(true); 
			$value->setValue($ecs_content->getTimePlace()->getRoom());
			$value->save();
		}
		if($field = $mappings->getMappingByECSName(ilECSDataMappingSetting::MAPPING_IMPORT_RCRS,'cycle'))
		{
			$value = ilAdvancedMDValue::_getInstance($this->getId(),$field);
			$value->toggleDisabledStatus(true); 
			$value->setValue($ecs_content->getTimePlace()->getCycle());
			$value->save();
		}
		
		include_once './Services/WebServices/ECS/classes/class.ilECSCategoryMapping.php';
		ilECSCategoryMapping::handleUpdate($a_server_id,$ecs_content,$this->getId());
		
		
		return true;
	}
	
	/**
	 * set status to imported from ecs
	 *
	 * @access public
	 * 
	 */
	public function setECSImported($a_server_id,$a_econtent_id,$a_mid,$a_obj_id)
	{
		include_once('./Services/WebServices/ECS/classes/class.ilECSImport.php');
	 	$import = new ilECSImport($a_server_id,$a_obj_id);
	 	$import->setEContentId($a_econtent_id);
	 	$import->setMID($a_mid);
	 	$import->save();
	}
}
?>
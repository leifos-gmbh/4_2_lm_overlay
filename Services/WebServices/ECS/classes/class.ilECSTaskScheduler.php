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
* 
* @author Stefan Meyer <smeyer.ilias@gmx.de>
* @version $Id$
* 
* 
* @ingroup ServicesWebServicesECS 
*/
class ilECSTaskScheduler
{
	const MAX_TASKS = 30;
	
	private static $instances = array();
	
	private $event_reader = null;

	protected $settings = null;
	protected $log = null;
	protected $db;
	
	private $mids = array();
	private $content = array();
	private $to_create = array();
	private $to_update = array();
	private $to_delete = array();
	
	/**
	 * Singleton constructor
	 *
	 * @access public
	 * 
	 */
	private function __construct(ilECSSetting $setting)
	{
	 	global $ilDB,$ilLog;
	 	
	 	$this->db = $ilDB;
	 	$this->log = $ilLog;
	 	
	 	include_once('./Services/WebServices/ECS/classes/class.ilECSSetting.php');
	 	$this->settings = $setting;
	}

	/**
	 * get singleton instance
	 * Private access use
	 * ilECSTaskScheduler::start() or
	 * ilECSTaskScheduler::startTaskExecution
	 *
	 * @access private
	 * @static
	 *
	 * @return ilECSTaskScheduler
	 *
	 */
	public static function _getInstanceByServerId($a_server_id)
	{
		if(self::$instances[$a_server_id])
		{
			return self::$instances[$a_server_id];
		}
		return self::$instances[$a_server_id] =
			new ilECSTaskScheduler(
				ilECSSetting::getInstanceByServerId($a_server_id)
		);
	}

	/**
	 * Start task scheduler for each server instance
	 */
	public static function start()
	{
		include_once './Services/WebServices/ECS/classes/class.ilECSServerSettings.php';
		$servers = ilECSServerSettings::getInstance();
		foreach($servers->getServers() as $server)
		{
			$sched = new ilECSTaskScheduler($server);
			if($sched->checkNextExecution())
			{
				$sched->initNextExecution();
			}
		}
	}

	/**
	 * Static version iterates over all active instances
	 */
	public static function startExecution()
	{
		include_once './Services/WebServices/ECS/classes/class.ilECSServerSettings.php';
		$servers = ilECSServerSettings::getInstance();
		foreach($server->getServers() as $server)
		{
			$sched = new ilECSTaskScheduler($server);
			$sched->startTaskExecution();
		}

	}

	/**
	 * Get server setting
	 * @return ilECSSetting
	 */
	public function getServer()
	{
		return $this->settings;
	}


	/**
	 * Start Tasks
	 *
	 * @access private
	 *
	 */
	public function startTaskExecution()
	{
		global $ilLog;

		try
		{
			$this->readMIDs();
			$this->readEvents();
			$this->handleEvents();
			
			$this->handleDeprecatedAccounts();
		}
		catch(ilException $exc)
		{
			$this->log->write(__METHOD__.': Caught exception: '.$exc->getMessage());
			return false;
		}
		return true;
	}
	
	/**
	 * Read EContent 
	 *
	 * @access private
	 * 
	 */
	private function readEvents()
	{
	 	try
	 	{
	 		include_once('./Services/WebServices/ECS/classes/class.ilECSEventQueueReader.php');
			$this->event_reader = new ilECSEventQueueReader($this->getServer()->getServerId());
			$this->event_reader->refresh();
	 	}
	 	catch(ilException $exc)
	 	{
	 		throw $exc;
	 	}
	}
	
	/**
	 * Handle events
	 *
	 * @access private
	 * 
	 */
	private function handleEvents()
	{
		include_once './Services/WebServices/ECS/classes/class.ilECSEvent.php';

	 	for($i = 0;$i < self::MAX_TASKS;$i++)
	 	{
	 		if(!$event = $this->event_reader->shift())
	 		{
	 			$this->log->write(__METHOD__.': No more pending events found. DONE');
	 			break;
	 		}
			if($event['op'] == ilECSEvent::DESTROYED)
			{
				$this->handleDelete($event['id']);
	 			$this->log->write(__METHOD__.': Handling delete. DONE');
				continue;
			}
			if($event['op'] == ilECSEvent::NEW_EXPORT)
			{
				$this->handleNewlyCreate($event['id']);
	 			$this->log->write(__METHOD__.': Handling new creation. DONE');
				continue;
			}

	 		// Operation is create or update
	 		// get econtent
	 		try
	 		{
				include_once('./Services/WebServices/ECS/classes/class.ilECSEContentReader.php');
				$reader = new ilECSEContentReader($this->getServer()->getServerId(),$event['id']);
				$reader->read();
				$reader->read(true);
	 		}
	 		catch(Exception $e)
	 		{
	 			$this->log->write(__METHOD__.': Cannot read Econtent. '.$e->getMessage());
	 			continue;
	 		}
	 		if(!$reader->getEContent() instanceof ilECSEContent)
	 		{
	 			$this->handleDelete($event['id']);
	 			$this->log->write(__METHOD__.': Handling delete of deprecated remote courses. DONE');
	 		}
	 		else
	 		{
	 			$this->handleUpdate($reader->getEContent(),$reader->getEContentDetails());
	 			$this->log->write(__METHOD__.': Handling update. DONE');
	 		}
	 	}
	}
	
	private function handleNewlyCreate($a_obj_id)
	{
		global $ilLog;
		
		include_once('./Services/WebServices/ECS/classes/class.ilECSExport.php');
		include_once('./Services/WebServices/ECS/classes/class.ilECSConnectorException.php');
		include_once('./Services/WebServices/ECS/classes/class.ilECSEContentReader.php');
		include_once('./Services/WebServices/ECS/classes/class.ilECSReaderException.php');
		include_once('./Services/WebServices/ECS/classes/class.ilECSContentWriter.php');
		include_once('./Services/WebServices/ECS/classes/class.ilECSContentWriterException.php');
		
		
		$export = new ilECSExport($this->getServer()->getServerId(),$a_obj_id);
		$econtent_id = $export->getEContentId();

		try
		{
			$reader = new ilECSEContentReader($this->getServer()->getServerId(),$econtent_id);
			$reader->read();
			$reader->read(true);
			
			$econtent = $reader->getEContent();
			$details = $reader->getEContentDetails();

			if($econtent instanceof ilECSEContent and $details instanceof ilECSEContentDetails)
			{
				if(!$obj = ilObjectFactory::getInstanceByObjId($a_obj_id,false))
				{
					$ilLog->write(__METHOD__.': Cannot create object instance. Aborting...');
					return false;
				}
				// Delete resource			
				$writer = new ilECSContentWriter($obj,$this->getServer()->getServerId());
				$writer->setExportable(false);
				$writer->setOwnerId($details->getFirstSender());
				$writer->setParticipantIds($details->getReceivers());
				$writer->refresh();
				
				// Create resource
				$writer->setExportable(true);
				$writer->refresh();
				return true;
			}
			return false;
			
		}
		catch(ilECSConnectorException $e1)
		{
			$ilLog->write(__METHOD__.': Cannot handle create event. Message: '.$e1->getMessage());
			return false;
		}
		catch(ilECSReaderException $e2)
		{
			$ilLog->write(__METHOD__.': Cannot handle create event. Message: '.$e2->getMessage());
			return false;
		}
		catch(ilECSContentWriterException $e3)
		{
			$ilLog->write(__METHOD__.': Cannot handle create event. Message: '.$e2->getMessage());
			return false;
		}
		
	}
	
	/**
	 * Handle delete 
	 * @access private
	 * @param array array of event data
	 * 
	 */
	private function handleDelete($econtent_id,$a_mid = 0)
	{
		global $tree;
		
		include_once('./Services/WebServices/ECS/classes/class.ilECSImport.php');
		// if mid is zero delete all obj_ids
		if(!$a_mid)
		{
	 		$obj_ids = ilECSImport::_lookupObjIds($this->settings->getServerId(),$econtent_id);
		}
		else
		{
			$obj_ids = (array) ilECSImport::_lookupObjId($this->settings->getServerId(),$econtent_id,$a_mid);
 		}
		$GLOBALS['ilLog']->write(__METHOD__.': Received obj_ids '.print_r($obj_ids,true));
	 	foreach($obj_ids as $obj_id)
	 	{
	 		$references = ilObject::_getAllReferences($obj_id);
	 		foreach($references as $ref_id)
	 		{
	 			if($tmp_obj = ilObjectFactory::getInstanceByRefId($ref_id,false))
	 			{
		 			$this->log->write(__METHOD__.': Deleting obsolete remote course: '.$tmp_obj->getTitle());
	 				$tmp_obj->delete();
		 			$tree->deleteTree($tree->getNodeData($ref_id));
	 			}
	 			unset($tmp_obj);
	 		}
	 	}
	}
	
	/**
	 * Handle update/creation of remote courses.
	 *
	 * @access private
	 * @param array array of ecscontent
	 * 
	 */
	private function handleUpdate(ilECSEContent $content,  ilECSEContentDetails $details)
	{
		global $ilLog;


		$GLOBALS['ilLog']->write(__METHOD__.': Receivers are '. print_r($details->getReceivers(),true));
		
		include_once('./Services/WebServices/ECS/classes/class.ilECSParticipantSettings.php');
		if(!ilECSParticipantSettings::getInstanceByServerId($this->getServer()->getServerId())->isImportAllowed($content->getOwner()))
		{
			$ilLog->write('Ignoring disabled participant. MID: '.$content->getOwner());
			return false;
		}

		include_once('Services/WebServices/ECS/classes/class.ilECSImport.php');

		// new mids
		#foreach($this->mids as $mid)
		foreach(array_intersect($this->mids,$details->getReceivers()) as $mid)
		{
			// Update existing
			if($obj_id = ilECSImport::_isImported($this->settings->getServerId(),$content->getEContentId(),$mid))
			{
				$ilLog->write(__METHOD__.': Handling update for existing object');
				$remote = ilObjectFactory::getInstanceByObjId($obj_id,false);
				if($remote->getType() != 'rcrs')
				{
					$this->log->write(__METHOD__.': Cannot instantiate remote course. Got object type '.$remote->getType());
					continue;
				}
				$remote->updateFromECSContent($this->getServer()->getServerId(),$content);
			}
			else
			{
				$ilLog->write(__METHOD__.': Handling create for non existing object');
				include_once('./Modules/RemoteCourse/classes/class.ilObjRemoteCourse.php');
				$remote_crs = ilObjRemoteCourse::_createFromECSEContent($this->settings->getServerId(),$content,$mid);
			}

			/*
			// deprecated mids
			foreach(array_diff(ilECSImport::_lookupMIDs($this->settings->getServerId(),$content->getEContentId()),$details->getReceivers()) as $deprecated)
			{
				$this->handleDelete($content->getEContentId(),$deprecated);
			}
			*/
	 	}	
	}
	
	/**
	 * Delete deprecate ECS accounts
	 *
	 * @access private
	 * 
	 */
	private function handleDeprecatedAccounts()
	{
	 	global $ilDB;
	 	
	 	$query = "SELECT usr_id FROM usr_data WHERE auth_mode = 'ecs' ".
	 		"AND time_limit_until < ".time()." ".
	 		"AND time_limit_unlimited = 0 ".
	 		"AND (time_limit_until - time_limit_from) < 7200";
	 	$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			if($user_obj = ilObjectFactory::getInstanceByObjId($row->usr_id,false))
			{
	 			$this->log->write(__METHOD__.': Deleting deprecated ECS user account '.$user_obj->getLogin());
				$user_obj->delete();
			}
			// only one user
			break;
		}
		return true;
	}
	
	/**
	 * Read MID's of this installation 
	 *
	 * @access private
	 * 
	 */
	private function readMIDs()
	{
	 	try
	 	{
	 		$this->mids = array();
	 		
	 		include_once('./Services/WebServices/ECS/classes/class.ilECSCommunityReader.php');
	 		$reader = ilECSCommunityReader::getInstanceByServerId($this->getServer()->getServerId());
	 		foreach($reader->getCommunities() as $com)
	 		{
	 			foreach($com->getParticipants() as $part)
	 			{
	 				if($part->isSelf())
	 				{
	 					$this->mids[] = $part->getMID();
	 					$this->log->write('Fetch MID: '.$part->getMID());
	 				}
	 			}
	 		}
	 	}
	 	catch(ilException $exc)
	 	{
	 		throw $exc;
	 	}
	}
	
	
	/**
	 * Start
	 *
	 * @access public
	 * 
	 */
	public function checkNextExecution()
	{
	 	global $ilLog, $ilDB;

	 	
	 	if(!$this->settings->isEnabled())
	 	{
			return false;
	 	}
		
	 	if(!$this->settings->checkImportId())
	 	{
	 		$this->log->write(__METHOD__.': Import ID is deleted or not of type "category". Aborting');
	 		return false;
	 	}

	 	// check next task excecution time:
	 	// If it's greater than time() directly increase this value with the polling time
		/* synchronized { */
		$query = 'UPDATE settings SET '.
			'value = '.$ilDB->quote(time() + $this->settings->getPollingTime(),'text').' '.
			'WHERE module = '.$ilDB->quote('ecs','text').' '.
			'AND keyword = '.$ilDB->quote('next_execution_'.$this->settings->getServerId(),'text').' '.
			'AND value < '.$ilDB->quote(time(),'text');
		$affected_rows = $ilDB->manipulate($query);
		/* } */


		if(!$affected_rows)
		{
			// Nothing to do
			return false;
		}
	 	return true;
	}


	/**
	 * Call next task scheduler run
	 */
	protected function initNextExecution()
	{
		global $ilLog;

		// Start task execution as backend process
		include_once 'Services/WebServices/SOAP/classes/class.ilSoapClient.php';

		$soap_client = new ilSoapClient();
		$soap_client->setResponseTimeout(1);
		$soap_client->enableWSDL(true);

		$ilLog->write(__METHOD__.': Trying to call Soap client...');
		$new_session_id = duplicate_session($_COOKIE['PHPSESSID']);
		$client_id = $_COOKIE['ilClientId'];

		if($soap_client->init() and 0)
		{
			$ilLog->write(__METHOD__.': Calling soap handleECSTasks method...');
			$res = $soap_client->call('handleECSTasks',array($new_session_id.'::'.$client_id,$this->settings->getServerId()));
		}
		else
		{
			$ilLog->write(__METHOD__.': SOAP call failed. Calling clone method manually. ');
			include_once('./webservice/soap/include/inc.soap_functions.php');
			$res = ilSoapFunctions::handleECSTasks($new_session_id.'::'.$client_id,$this->settings->getServerId());
		}
	}
}
?>
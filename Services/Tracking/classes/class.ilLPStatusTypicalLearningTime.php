<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Tracking/classes/class.ilLPStatus.php';

/**
 * @author Stefan Meyer <meyer@leifos.com>
 *
 * @version $Id: class.ilLPStatusTypicalLearningTime.php 23539 2010-04-17 22:00:54Z akill $
 *
 * @ingroup	ServicesTracking
 *
 */
class ilLPStatusTypicalLearningTime extends ilLPStatus
{

	function ilLPStatusTypicalLearningTime($a_obj_id)
	{
		global $ilDB;

		parent::ilLPStatus($a_obj_id);
		$this->db =& $ilDB;
	}

	function _getInProgress($a_obj_id)
	{
		global $ilDB;

		include_once './Services/MetaData/classes/class.ilMDEducational.php';

		$status_info = ilLPStatusWrapper::_getStatusInfo($a_obj_id);
		$tlt = $status_info['tlt'];

		include_once './Services/Tracking/classes/class.ilChangeEvent.php';
		$all = ilChangeEvent::_lookupReadEvents($a_obj_id);

		foreach($all as $event)
		{
			if($event['spent_seconds'] < $tlt)
			{
				$user_ids[] = $event['usr_id'];
			}
		}
		return $user_ids ? $user_ids : array();
	}

	function _getCompleted($a_obj_id)
	{
		global $ilDB;

		include_once './Services/MetaData/classes/class.ilMDEducational.php';

		$status_info = ilLPStatusWrapper::_getStatusInfo($a_obj_id);
		$tlt = $status_info['tlt'];

		// TODO: move to status info
		include_once './Services/Tracking/classes/class.ilChangeEvent.php';
		$all = ilChangeEvent::_lookupReadEvents($a_obj_id);

		foreach($all as $event)
		{
			if($event['spent_seconds'] >= $tlt)
			{
				$user_ids[] = $event['usr_id'];
			}
		}
		return $user_ids ? $user_ids : array();
	}

	function _getStatusInfo($a_obj_id)
	{
		include_once './Services/MetaData/classes/class.ilMDEducational.php';
		$status_info['tlt'] = ilMDEducational::_getTypicalLearningTimeSeconds($a_obj_id);

		return $status_info;
	}
	
	/**
	 * Determine status
	 *
	 * @param	integer		object id
	 * @param	integer		user id
	 * @param	object		object (optional depends on object type)
	 * @return	integer		status
	 */
	function determineStatus($a_obj_id, $a_user_id, $a_obj = null)
	{
		global $ilObjDataCache, $ilDB;
		
		$status = LP_STATUS_NOT_ATTEMPTED_NUM;
		switch ($ilObjDataCache->lookupType($a_obj_id))
		{
			case 'lm':
				if (ilChangeEvent::hasAccessed($a_obj_id, $a_user_id))
				{
					$status = LP_STATUS_IN_PROGRESS_NUM;
					
					// completed?
					$status_info = ilLPStatusWrapper::_getStatusInfo($a_obj_id);
					$tlt = $status_info['tlt'];

					include_once './Services/Tracking/classes/class.ilChangeEvent.php';
					$re = ilChangeEvent::_lookupReadEvents($a_obj_id, $a_user_id);
					if ($re[0]['spent_seconds'] >= $tlt)
					{
						$status = LP_STATUS_COMPLETED_NUM;
					}
				}
				break;			
		}
		return $status;		
	}		

	/**
	 * Determine percentage
	 *
	 * @param	integer		object id
	 * @param	integer		user id
	 * @param	object		object (optional depends on object type)
	 * @return	integer		percentage
	 */
	function determinePercentage($a_obj_id, $a_user_id, $a_obj = null)
	{
		$tlt = (int) ilMDEducational::_getTypicalLearningTimeSeconds($a_obj_id);
		$re = ilChangeEvent::_lookupReadEvents($a_obj_id, $a_user_id);
		$spent = (int) $re[0]["spent_seconds"];

		if ($tlt > 0)
		{
			$per = min(100, 100 / $tlt * $spent);
		}
		else
		{
			$per = 100;
		}

		return $per;
	}
}	
?>
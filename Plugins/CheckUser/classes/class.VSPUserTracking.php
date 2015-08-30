<?php
class VSPUserTracking
{
	var $ptr_db = null;
	var $ini_data = array();	
	
	var $from_date = '0000-00-00';
	var $till_date = '0000-00-00';
	var $login = '';
	var $merge_time = 600;
	
	function getInstance()
	{
		static $instance;
		
		if (!isset($instance))
		{
	    	$instance =& new VSPUserTracking();
	    }
	    
	    return $instance;	    	    
	}
	
	function VSPUserTracking()
	{
		$this->ini_data = parse_ini_file(dirname(__FILE__) . '/../../../data/endriss/client.ini.php');
		#$this->ini_data = parse_ini_file(dirname(__FILE__)  . '/../../../data/default/client.ini.php');


		$this->ptr_db = mysql_connect($this->ini_data['host'], $this->ini_data['user'], $this->ini_data['pass']);
		mysql_select_db($this->ini_data['name'] ,$this->ptr_db);
		$res = $this->query('SET NAMES utf8;');					
	}
	
	function __destruct()
	{
		mysql_close($this->ptr_db);
	}
	
	function setMergeTime($a_merge_time = 600)
	{
		$this->merge_time = $a_merge_time;
	}
	
	function getMergeTime()
	{
		return $this->merge_time;
	}
	
	function query($sql)
	{
		return mysql_query($sql, $this->ptr_db);
	}
	
	function setLoginName($a_login = '')
	{
		$this->login = $a_login;
	}
	
	function getLoginName()
	{
		return $this->login;
	}
	
	function setFromDate($a_date = '0000-00-00')
	{
		$this->from_date = $a_date;
	}
	
	function getFromDate()
	{
		return $this->from_date;
	}
	
	function setTillDate($a_date = '0000-00-00')
	{
		$this->till_date = $a_date;
	}
	
	function getTillDate()
	{
		return $this->till_date;
	}

	function getGeneralTrackingData()
	{
		$query = "SELECT * 
				  FROM usr_data
				  INNER JOIN ut_online ON ut_online.usr_id = usr_data.usr_id 
				  WHERE 1
				  AND login = '" . $this->getLoginName() . "' 
				  LIMIT 1";
				

		$res = $this->query($query);
		
		$data = array();		
		while ($row = mysql_fetch_object($res))
		{
			
			$data["firstname"] = $row->firstname;
			$data["lastname"] = $row->lastname;
			$data["access_time"] = $row->access_time;
			$data["online_time"] = $row->online_time;
		}
		
		return $data;		
	}
	
	function getRequestedLearningModules()
	{
		$query = "SELECT * 
				  FROM usr_data
				  INNER JOIN read_event ON read_event.usr_id = usr_data.usr_id 
				  INNER JOIN object_data ON object_data.obj_id = read_event.obj_id
				  WHERE 1
				  AND login = '" . $this->getLoginName() . "' 
				  AND object_data.type = 'lm'
				  ORDER BY object_data.title ";
				
			
		$res = $this->query($query);
		
		$data = array();
		$counter = 0;	
		while ($row = mysql_fetch_object($res))
		{
			$data[$counter]["title"] = $row->title;
			$data[$counter]["type"] = $row->type;
			$data[$counter]["access_time"] = $row->last_access;
			$data[$counter]["spent_time"] = $row->spent_seconds;
			$data[$counter]["visits"] = $row->read_count;
			
			++$counter;
		}
		
		return $data;		
	}
	
	function getRequestStatisticsForLearningModules()
	{
		$query = "SELECT * 
				  FROM usr_data
				  INNER JOIN ut_access ON ut_access.user_id = usr_data.usr_id 
				  INNER JOIN object_data ON object_data.obj_id = ut_access.acc_obj_id 
				  WHERE 1
				  AND login = '" . $this->getLoginName() . "'
				  AND ut_access.acc_time >= '" . $this->getFromDate() . " 00:00:00' AND ut_access.acc_time <= '" . $this->getTillDate() . " 23:59:59' 
				  AND acc_obj_type 	 = 'lm'
				  ORDER BY object_data.title, ut_access.acc_time ";
				
			
		$res = $this->query($query);
		
		$data = array();			
		while ($row = mysql_fetch_object($res))
		{
			if (!array_key_exists($row->acc_obj_id, $data))
			{
				$obj_id = $row->acc_obj_id;
				$data[$obj_id] = array();
				$counter = 0;
			}		
			
			$data[$obj_id][$counter]["title"] = $row->title;
			$data[$obj_id][$counter]["type"] = $row->type;
			$data[$obj_id][$counter]["acc_time"] = $row->acc_time;
			$data[$obj_id][$counter]["acc_obj_id"] = $row->acc_obj_id;
			
			++$counter;
		}
		
		return $data;		
	}
	
	function getRequestedTests()
	{
		$query = "SELECT *, MAX(tst_times.started) AS last_access_time, 
							SUM(UNIX_TIMESTAMP(tst_times.finished) - UNIX_TIMESTAMP(tst_times.started)) AS spent_time,
							COUNT(tst_times.times_id) AS requests,
							object_data.title AS title
				  FROM usr_data
				  INNER JOIN tst_active ON tst_active.user_fi = usr_data.usr_id 
				  INNER JOIN tst_tests ON tst_tests.test_id = tst_active.test_fi
				  INNER JOIN object_data ON object_data.obj_id = tst_tests.obj_fi
				  INNER JOIN tst_times ON tst_times.active_fi = tst_active.active_id
				  WHERE 1				  
				  AND login = '" . $this->getLoginName() . "' 
				  GROUP BY tst_tests.test_id";
		
		$res = $this->query($query);

		$data = array();	
		$counter = 0;		
		while ($row = mysql_fetch_object($res))
		{
			$data[$counter]["title"] = $row->title;
			$data[$counter]["tries"] = $row->tries;
			$data[$counter]["type"] = $row->type;			
			$data[$counter]["TIMESTAMP"] = $row->TIMESTAMP;
			$data[$counter]["last_access_time"] = $row->TIMESTAMP;
			$data[$counter]["spent_time"] = $row->spent_time;
			$data[$counter]["requests"] = $row->requests;
			
			++$counter;
		}

		return $data;
	}
	
	function getRequestStatisticsForTests()
	{
		$query = "SELECT *, tst_times.started AS access_time, object_data.title AS title
				  FROM usr_data
				  INNER JOIN tst_active ON tst_active.user_fi = usr_data.usr_id 
				  INNER JOIN tst_tests ON tst_tests.test_id = tst_active.test_fi
				  INNER JOIN object_data ON object_data.obj_id = tst_tests.obj_fi
				  INNER JOIN tst_times ON tst_times.active_fi = tst_active.active_id
				  WHERE 1 
				  AND tst_times.started >= '" . $this->getFromDate() . " 00:00:00' AND tst_times.started <= '" . $this->getTillDate() . " 23:59:59'
				  AND login = '" . $this->getLoginName() . "' 
				  GROUP BY tst_times.times_id
				  ORDER BY object_data.title";
				
			
		$res = $this->query($query);
		
		$data = array();			
		while ($row = mysql_fetch_object($res))
		{
			if (!array_key_exists($row->obj_fi, $data))
			{
				$obj_id = $row->obj_fi;
				$data[$obj_id] = array();
				$counter = 0;
			}		
			
			$data[$obj_id][$counter]["title"] = $row->title;
			$data[$obj_id][$counter]["access_time"] = $row->access_time;
			
			++$counter;
		}
		
		return $data;		
	}
	
	function getAccessTimeAsString($a_access_time)
	{
		return date("d.m.Y", $a_access_time) . " " . date("H:i", $a_access_time) . " Uhr";
	}
	
	function getSpentTimeAsString($a_spent_time)
	{
		if ($a_spent_time > 0)
		{
			$online_time = '';
			if ($a_spent_time > (60 * 60 * 24))
			{
				$online_time = floor($a_spent_time / (60 * 60 * 24)) . (floor($a_spent_time / (60 * 60 * 24)) == 1 ? ' Tag ' : ' Tage ') . floor(($a_spent_time % (60 * 60 * 24)) / (60 * 60)) . (floor(($a_spent_time % (60 * 60 * 24)) / (60 * 60)) == 1 ? ' Stunde ' : ' Stunden ') . floor((($a_spent_time % (60 * 60 * 24)) % (60 * 60)) / 60) . (floor((($a_spent_time % (60 * 60 * 24)) % (60 * 60)) / 60) == 1 ? ' Minute ' : ' Minuten ') . floor(((($a_spent_time % (60 * 60 * 24)) % (60 * 60)) % 60)) . (floor(((($a_spent_time % (60 * 60 * 24)) % (60 * 60)) % 60)) == 1 ? ' Sekunde' : ' Sekunden');
			}
			else if ($a_spent_time > (60 * 60))
			{
				$online_time = floor($a_spent_time / (60 * 60)) . (floor($a_spent_time / (60 * 60)) == 1 ? ' Stunde ' : ' Stunden ') . floor(($a_spent_time % (60 * 60))/60) . (floor(($a_spent_time % (60 * 60))/60) == 1 ? ' Minute ' : ' Minuten ') . floor(($a_spent_time % (60 * 60)) % 60) . (floor(($a_spent_time % (60 * 60)) % 60) == 1 ? ' Sekunde' : ' Sekunden');			
			}
			else if ($a_spent_time >= 60)
			{
				$online_time = floor($a_spent_time / 60) . (floor($a_spent_time / 60) == 1 ? ' Minute ' : ' Minuten ') . $a_spent_time % 60 . ($a_spent_time % 60 == 1 ? ' Sekunde' : ' Sekunden');
			}
			else
			{				
				$online_time = $a_spent_time . ($a_spent_time == 1 ? ' Sekunde' : ' Sekunden');
			}
		}
		else
		{
			$online_time = '0 Sekunden';
		}
		
		return $online_time;			
	}
}
?>
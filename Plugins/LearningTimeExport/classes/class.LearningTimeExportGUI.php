<?php

	class LearningTimeExportGUI
	{
		const EXP_TYPE_BOTH = 1;
		const EXP_TYPE_LMS = 2;
		const EXP_TYPE_TESTS = 3;
		
		private $message = '';
		
		public function run()
		{
			global $lng;
			
			$this->lng = $lng;
			$this->lng->loadLanguageModule('lte');
			
			$this->executeCommand();
		}

		private function executeCommand()
		{
			$cmd = $_GET['cmd'];
			if ($cmd == 'post')
			{
				$cmd = '';
				
				if (is_array($_POST['cmd']))
				{
					foreach ($_POST['cmd'] as $key => $val)
					{
						if ($val != '')
						{
							$cmd = $key;
							break;
						}
					}
					
					if ($cmd == '') $cmd = $_GET['fallbackCmd'];
				}			
			}
					
			if ($cmd == '') $cmd = 'showForm';		
			$this->$cmd();
		}
		
		private function cancel()
		{
			header('Location: ./Plugins/CheckUser/index.php');
			exit;
		}
		
		private function performExport()
		{
			$export_type = (int)$_POST['export_type'];
			
			switch($export_type)
			{
				case self::EXP_TYPE_BOTH:
				case self::EXP_TYPE_LMS:
				case self::EXP_TYPE_TESTS:
					break;
					
				default:
					$this->message = $this->lng->txt('lte_invalid_export_type');
					$this->showForm();
					exit;
			}

			
			$crs_ref_id = (int)$_POST['crs_ref_id'];
			
			include_once('./classes/class.ilObjectFactory.php');
			$object = ilObjectFactory::getInstanceByRefId($crs_ref_id,false);
			
			if(!is_object($object) || $object->getType() != 'crs')
			{
				$this->message = sprintf($this->lng->txt('lte_invalid_crs_ref_id'),$crs_ref_id);
				$this->showForm();
				exit;
			}
			
			$export = $this->export($export_type,$crs_ref_id);
		}
		
		private function export($export_type,$crs_ref_id)
		{
			global $ilObjDataCache;

			$ts = time();
			
			include_once('./classes/class.ilObjectFactory.php');
			include_once('./Modules/ScormAicc/classes/class.ilObjSCORMLearningModule.php');
			include_once('./Modules/Scorm2004/classes/class.ilObjSCORM2004LearningModule.php');
			include_once('./Modules/Course/classes/class.ilCourseParticipants.php');

			$crs_obj_id = $ilObjDataCache->lookupObjId($crs_ref_id);
			$obj_crs = ilCourseParticipants::_getInstanceByObjId($crs_obj_id);
			$crs_members = $obj_crs->getMembers();

			$member_array = array();
			foreach($crs_members as $key=>$value)
			{
				$member_array[$value]= true;
			}

			switch($export_type)
			{
				case self::EXP_TYPE_BOTH:	$types = array('lm','sahs','tst'); break;
					
				case self::EXP_TYPE_LMS:	$types = array('lm','sahs'); break;

				case self::EXP_TYPE_TESTS:	$types = array('tst'); break;
			}


			global $tree;
			$subtree = $tree->getSubTree($tree->getNodeData($crs_ref_id));
			$objects = array( 'lm' => array(), 'sahs' => array(), 'tst' => array() );

			foreach( $subtree as $node )
			{
				if( in_array($node['type'], $types) )
					$objects[$node['type']][] = $node;
			}
			
			$times = array();
			$object_names = array();
			$user_names = array();
			
			foreach((array)$objects['sahs'] as $obj_data)
			{
				$obj_id = $obj_data['obj_id'];
				$ref_id = $obj_data['ref_id'];
				
				$object = ilObjectFactory::getInstanceByObjId($obj_data['obj_id'],false);
				$object_names['sahs'][$obj_id] = $object->getTitle();
				
				if($object->getSubType() == 'scorm2004')
				{
					$object = new ilObjSCORM2004LearningModule($ref_id);
					$tracked_items = $object->getTrackedItems();
					foreach($tracked_items as $item)
					{
						$sco_id = $item['id'];
						// This is necessary because
						// ilObjSCORM2004LearningModule::getTrackingDataAgg()
						// uses $_GET['obj_id'] instead of the passed in variable
						// $a_sco_id
						$_GET['obj_id'] = $sco_id;
						
						$tracking_data = $object->getTrackingDataAgg($sco_id);
						foreach($tracking_data as $data)
						{
							$usr_id = $data['user_id'];
							if(array_key_exists($usr_id, $member_array))
							{
								$times[$usr_id]['sahs'][$obj_id] += $data['session_time'];
							}
						}
					}
				}
				elseif($object->getSubType() == 'scorm')
				{
					$object = new ilObjSCORMLearningModule($ref_id);
					$tracked_items = $object->getTrackedItems();
					foreach($tracked_items as $item)
					{
						$sco_id = $item->getId();
						$tracking_data = $object->getTrackingDataAgg($sco_id);
						foreach($tracking_data as $data)
						{
							$usr_id = $data['user_id'];
							list($hours,$mins,$secs) = explode(':',$data['time']);
							$time = (60*60*(int)$hours) + (60*(int)$mins) + $secs;

							if(array_key_exists($usr_id, $member_array))
							{
								$times[$usr_id]['sahs'][$obj_id] += $time;
							}
						}
					}
				}
			}
			
			foreach((array)$objects['lm'] as $obj_data)
			{
				$obj_id = $obj_data['obj_id'];
				$object = ilObjectFactory::getInstanceByObjId($obj_data['obj_id'],false);
				
				$object_names['lm'][$obj_id] = $object->getTitle();

				global $ilDB;
		
				$query = "SELECT usr_id,spent_seconds FROM read_event ".
					"WHERE obj_id = '".$obj_id."'";
					
				$res = $ilDB->query($query);

				while($row = $ilDB->fetchAssoc($res))
				{
					if(array_key_exists($row['usr_id'], $member_array))
					{
						$times[$row['usr_id']]['lm'][$obj_id] = $row['spent_seconds'];
					}
				}
			}
			
			foreach((array)$objects['tst'] as $obj_data)
			{
				$obj_id = $obj_data['obj_id'];
				$object = ilObjectFactory::getInstanceByObjId($obj_data['obj_id'],false);
				$data = $object->getCompleteEvaluationData(true);
				$participants = $data->getParticipants();
				foreach($participants as $participant)
				{
					$usr_id = $participant->getUserID();
					if(array_key_exists($usr_id, $member_array))
					{
						$object_names['tst'][$obj_id] = $object->getTitle();
						$times[$usr_id]['tst'][$obj_id] = $participant->getTimeOfWork();
					}
				}
			}
			$user_ids = array_keys($times);

			foreach((array)$user_ids as $usr_id)
			{
				if ($user = ilObjectFactory::getInstanceByObjId($usr_id,false))
				{
					$user_names[$usr_id]['lastname'] = $user->getLastname();
					$user_names[$usr_id]['firstname'] = $user->getFirstname();
					$user_names[$usr_id]['login'] = $user->getLogin();
				}
				else
				{
					$user_names[$usr_id]['lastname'] = 'N/A';
					$user_names[$usr_id]['firstname'] = 'N/A';
					$user_names[$usr_id]['login'] = 'N/A';
				}
			}
			
			$crs = ilObjectFactory::getInstanceByRefId($crs_ref_id);
			$crs_title = $crs->getTitle();
			$date = date('d.m.Y',$ts);
			
			include_once('./classes/class.ilFormat.php');
			include_once('./Services/Utilities/classes/class.ilCSVWriter.php');
			$csv = new ilCSVWriter();
			
			$csv->setSeparator(';');
			$csv->setDelimiter('"');
			
			$csv->addColumn($this->lng->txt('lte_learningtimes_of_participants'));
			$csv->addRow();
			$csv->addColumn($this->lng->txt('course'));
			$csv->addColumn($crs_title);
			$csv->addRow();
			$csv->addColumn($this->lng->txt('lte_outputdate'));
			$csv->addColumn($date);
			$csv->addRow();
			
			$csv->addColumn($this->lng->txt('lastname'));
			$csv->addColumn($this->lng->txt('firstname'));
			$csv->addColumn($this->lng->txt('login'));

			foreach((array)$object_names['lm'] as $obj_id => $title) $csv->addColumn($title);
			foreach((array)$object_names['sahs'] as $obj_id => $title) $csv->addColumn($title);
			foreach((array)$object_names['tst'] as $obj_id => $title) $csv->addColumn($title);
			
			$csv->addColumn($this->lng->txt('lte_total'));
			
			$csv->addRow();
			
			$lms_times = array();
			$tests_times = array();
			$users_totals = array();
			foreach((array)$user_names as $usr_id => $user_data)
			{
				$csv->addColumn($user_data['lastname']);
				$csv->addColumn($user_data['firstname']);
				$csv->addColumn($user_data['login']);

				$total = 0;
				foreach((array)$object_names['lm'] as $obj_id => $title)
				{
					$time = $times[$usr_id]['lm'][$obj_id];
					$lms_times[] = $time;
					$total += $time;
					$csv->addColumn($this->formatTime($time));
				}
				foreach((array)$object_names['sahs'] as $obj_id => $title)
				{
					$time = $times[$usr_id]['sahs'][$obj_id];
					$lms_times[] = $time;
					$total += $time;
					$csv->addColumn($this->formatTime($time));
				}
				foreach((array)$object_names['tst'] as $obj_id => $title)
				{
					$time = $times[$usr_id]['tst'][$obj_id];
					$tests_times[] = $time;
					$total += $time;
					$csv->addColumn($this->formatTime($time));
				}
				
				$users_totals[] = $total;
				
				$csv->addColumn($this->formatTime($total));
				$csv->addRow();
			}
			
			if(count($users_totals) > 0)
			{
				$avg_users = 0;
				foreach($users_totals as $total) $avg_users += $total;
				$avg_users = $avg_users / count($users_totals);
			}
			else $avg_users = 0;

			$csv->addColumn($this->lng->txt('lte_avg_all_users'));
			$csv->addColumn($this->formatTime($avg_users));
			
			$csv->addRow();

			if( $export_type == self::EXP_TYPE_TESTS || $export_type == self::EXP_TYPE_BOTH )
			{
				if(count($tests_times) > 0)
				{
					$avg_tests = 0;
					foreach($tests_times as $total) $avg_tests += $total;
					$avg_tests = $avg_tests / count($tests_times);
				}
				else $avg_tests = 0;
	
				$csv->addColumn($this->lng->txt('lte_avg_per_test'));
				$csv->addColumn($this->formatTime($avg_tests));
			}
			
			$csv->addRow();

			if( $export_type == self::EXP_TYPE_LMS || $export_type == self::EXP_TYPE_BOTH )
			{
				if(count($lms_times) > 0)
				{
					$avg_lms = 0;
					foreach($lms_times as $total) $avg_lms += $total;
					$avg_lms = $avg_lms / count($lms_times);
				}
				else $avg_lms = 0;
	
				$csv->addColumn($this->lng->txt('lte_avg_per_lms'));
				$csv->addColumn($this->formatTime($avg_lms));
			}
					
			
			$csvstring = $csv->getCSVString();
			
			$date = date('Ymd',$ts);
			$filename = $date.'_Endriss_Lernzeit_Report_CrsRef-'.$crs_ref_id.'.csv';
			
			ilUtil::deliverData(utf8_decode($csvstring), $filename, 'text/comma-separated-values', 'iso-8859-1');
			
			#header('Content-Type: text/comma-separated-values; charset=utf-8');
			#header('Content-Disposition: attachment; filename='.$filename);			
			#echo utf8_decode($csvstring);			
			#echo $csvstring;
		}
		
		private function formatTime($seconds)
		{
			$hours = floor($seconds / 3600);
			$rest = $seconds % 3600;
	
			$minutes = floor($rest / 60);
			$rest = $rest % 60;
	
			return sprintf("%02d:%02d:%02d",$hours,$minutes,$rest);
		}
		
		private function showForm()
		{
			#global $ilSetting;

			include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");

			#if( $ilSetting->get('enable_tracking') == '1' )
			#{
				$form = new ilPropertyFormGUI();
				$form->setFormAction($_SERVER['PHP_SELF'].'?cmd=post');
				$form->setTitle($this->lng->txt('lte_learning_time_export'));
							
					$text = new ilTextInputGUI($this->lng->txt('lte_crs_ref_id'),'crs_ref_id');
					$text->setValue('');
					$text->setSize(8);
					$text->setMaxLength(8);
	
				$form->addItem($text);
				
					$radio_group = new ilRadioGroupInputGUI($this->lng->txt('lte_export_type'), 'export_type' );
					$radio_group->setValue(self::EXP_TYPE_BOTH);
			
						$radio_opt = new ilRadioOption($this->lng->txt('lte_learning_modules_and_tests'),self::EXP_TYPE_BOTH);
					$radio_group->addOption($radio_opt);
	
						$radio_opt = new ilRadioOption($this->lng->txt('lte_only_learning_modules'),self::EXP_TYPE_LMS);
					$radio_group->addOption($radio_opt);
			
						$radio_opt = new ilRadioOption($this->lng->txt('lte_only_tests'),self::EXP_TYPE_TESTS);
					$radio_group->addOption($radio_opt);
	
				$form->addItem($radio_group);
	
				$form->addCommandButton('performExport',$this->lng->txt('export'));
				
				$main_html = $form->getHTML();
			#}
			#else
			#{
			#	$main_html = '';
			#	$this->message = $this->lng->txt('lte_tracking_not_activated');
			#}
			
			
			
			$tpl = new ilTemplate('tpl.PluginMain.html',true,true,'Plugins/LearningTimeExport');
			$tpl->touchBlock('__global__');

			$tpl->setVariable('TXT_USERTRACKING_LINK',$this->lng->txt('lte_user_tracking_link'));
			$tpl->setVariable('HREF_USERTRACKING_LINK','checkuser.php');

			if($this->message != '') $tpl->setVariable('MESSAGE',$this->message);

			$tpl->setVariable('MAIN_CONTENT',$main_html);
			
			$tpl->parse();
			echo $tpl->get();
		}
		
	}



?>

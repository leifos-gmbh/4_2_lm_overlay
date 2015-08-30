<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "./Modules/Test/classes/inc.AssessmentConstants.php";
include_once "./Modules/Test/classes/class.ilTestServiceGUI.php";

/**
* Scoring class for tests
*
* @author Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version $Id$
*
* @ingroup ModulesTest
* @extends ilTestServiceGUI
*/
class ilTestScoringGUI extends ilTestServiceGUI
{
	
/**
* ilTestScoringGUI constructor
*
* The constructor takes the test object reference as parameter 
*
* @param object $a_object Associated ilObjTest class
* @access public
*/
  function ilTestScoringGUI($a_object)
  {
		parent::ilTestServiceGUI($a_object);
		$this->ctrl->saveParameter($this, "active_id");
		$this->ctrl->saveParameter($this, "userfilter");
		$this->ctrl->saveParameter($this, "pass");
	}
	
	/**
	* execute command
	*/
	function &executeCommand()
	{
		$cmd = $this->ctrl->getCmd();
		$next_class = $this->ctrl->getNextClass($this);

		if (strlen($cmd) == 0)
		{
			$this->ctrl->redirect($this, "manscoring");
		}
		$cmd = $this->getCommand($cmd);
		switch($next_class)
		{
			default:
				$ret =& $this->$cmd();
				break;
		}
		return $ret;
	}

	/**
	* Selects a participant for manual scoring
	*/
	function scoringfilter()
	{
		$this->manscoring();
	}
	
	/**
	* Resets the manual scoring filter
	*/
	function scoringfilterreset()
	{
		$this->manscoring();
	}
	
	/**
	* Save a user as manual scored
	*/
	public function setManScoringDone()
	{
		$manscoring_done = ($_POST["manscoring_done"]) ? 1 : 0;
		$assessmentSetting = new ilSetting("assessment");
		$assessmentSetting->set("manscoring_done_" . $_GET["active_id"], $manscoring_done);
		$this->manscoring();
	}

	/**
	* Shows the test scoring GUI
	*
	* @param integer $active_id The acitve ID of the participant to score
	*/
	function manscoring()
	{
		global $ilAccess;
		if (!$ilAccess->checkAccess("write", "", $this->ref_id)) 
		{
			// allow only write access
			ilUtil::sendInfo($this->lng->txt("cannot_edit_test"), true);
			$this->ctrl->redirectByClass("ilobjtestgui", "infoScreen");
		}

		$active_id = (strlen($_POST["participants"])) ? $_POST["participants"] : ((strlen($_GET["active_id"])) ? $_GET["active_id"] : 0);
		$userfiltervalue = (strlen($_POST["userfilter"])) ? $_POST["userfilter"] : ((strlen($_GET["userfilter"])) ? $_GET["userfilter"] : 0);

		if (strcmp($this->ctrl->getCmd(), "scoringfilterreset") == 0)
		{
			$active_id = 0;
			$userfiltervalue = 0;
		}

		include_once "./Modules/Test/classes/class.ilObjAssessmentFolder.php";
		$scoring = ilObjAssessmentFolder::_getManualScoring();
		if (count($scoring) == 0)
		{
			// allow only if question types are marked for manual scoring
			ilUtil::sendInfo($this->lng->txt("manscoring_not_allowed"));
			return;
		}

		$pass = $this->object->_getResultPass($active_id);
		if (array_key_exists("pass", $_GET))
		{
			if (strlen($_GET["pass"]))
			{
				$maxpass = $this->object->_getMaxPass($active_id);	
				if ($_GET["pass"] <= $maxpass) $pass = $_GET["pass"];
			}
		}
		
		$participantsfilter = ($userfiltervalue) ? $userfiltervalue : 0;
		$participants =& $this->object->getTestParticipantsForManualScoring($participantsfilter);
		if (!array_key_exists($active_id, $participants)) $active_id = 0;

		$this->ctrl->setParameter($this, "active_id", $active_id);
		$this->ctrl->setParameter($this, "userfilter", $userfiltervalue);

		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_as_tst_manual_scoring.html", "Modules/Test");

		if ($active_id > 0)
		{
			$this->tpl->setCurrentBlock("manscoring_done");
			$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this, "scoringfilter"));
			$this->tpl->setVariable("SAVE", $this->lng->txt("save"));
			$this->tpl->setVariable("TEXT_MANSCORING_DONE", $this->lng->txt("set_manscoring_done"));
			$assessmentSetting = new ilSetting("assessment");
			$manscoring_done = $assessmentSetting->get("manscoring_done_" . $active_id);
			if ($manscoring_done)
			{
				$this->tpl->setVariable("CHECKED_MANSCORING_DONE", ' checked="checked"');
			}
			$this->tpl->parseCurrentBlock();
		}

		if (array_key_exists("question", $_POST) || strlen($_GET["anchor"]))
		{
			if (strlen($_GET["anchor"]))
			{
				$question_id = $_GET["anchor"];
			}
			else
			{
				$keys = array_keys($_POST["question"]);
				$question_id = $keys[0];
			}
			$this->tpl->setCurrentBlock("lastchanged");
			$this->tpl->setVariable("LAST_CHANGED", $question_id);
			$this->tpl->parseCurrentBlock();
		}
		$counter = 1;
		foreach ($participants as $participant_active_id => $data)
		{
			$this->tpl->setCurrentBlock("participants");
			$this->tpl->setVariable("ID_PARTICIPANT", $data["active_id"]);
			$suffix = "";
			if ($this->object->getAnonymity())
			{
				$suffix = " " . $counter++;
			}
			if ($active_id > 0)
			{
				if ($active_id == $data["active_id"])
				{
					$this->tpl->setVariable("SELECTED_PARTICIPANT", " selected=\"selected\""); 
				}
			}
			$this->tpl->setVariable("TEXT_PARTICIPANT", $this->object->userLookupFullName($data["usr_id"], FALSE, TRUE, $suffix)); 
			$this->tpl->parseCurrentBlock();
		}
		$userfilter = array(
			"1" => $this->lng->txt("usr_active_only"), 
			"2" => $this->lng->txt("usr_inactive_only"), 
			//"3" => $this->lng->txt("all_users"),
			"4" => $this->lng->txt("manscoring_done"), 
			"5" => $this->lng->txt("manscoring_none"), 
			//"6" => $this->lng->txt("manscoring_pending")
		);
		foreach ($userfilter as $selection => $filtertext)
		{
			$this->tpl->setCurrentBlock("userfilter");
			$this->tpl->setVariable("VALUE_USERFILTER", $selection); 
			$this->tpl->setVariable("TEXT_USERFILTER", $filtertext); 
			if ($userfiltervalue == $selection)
			{
				$this->tpl->setVariable("SELECTED_USERFILTER", " selected=\"selected\""); 
			}
			$this->tpl->parseCurrentBlock();
		}
		
		$this->tpl->setVariable("PLEASE_SELECT", $this->lng->txt("participants"));
		$this->tpl->setVariable("SELECT_USERFILTER", $this->lng->txt("user_status"));
		$this->tpl->setVariable("SELECT_SCOREDFILTER", $this->lng->txt("manscoring"));
		$this->tpl->setVariable("BUTTON_SELECT", $this->lng->txt("to_filter"));
		$this->tpl->setVariable("BUTTON_RESET", $this->lng->txt("reset"));
		$this->tpl->setVariable("FILTER_CLASS", ($active_id) ? "filteractive" : "filterinactive");
		$this->tpl->setVariable("FILTER_CLASS_USERFILTER", ($userfiltervalue) ? "filteractive" : "filterinactive");
		$this->tpl->setVariable("TEXT_SELECT_USER", $this->lng->txt("manscoring_select_user"));
		
		if ($active_id > 0)
		{
			// print pass overview
			if ($this->object->getNrOfTries() != 1)
			{
				$overview = $this->getPassOverview($active_id, "iltestscoringgui", "manscoring");
				$this->tpl->setVariable("PASS_OVERVIEW", $overview);
			}
			// print pass details with scoring
			if (strlen($pass))
			{
				$result_array =& $this->object->getTestResult($active_id, $pass);
				$scoring = $this->getPassListOfAnswersWithScoring($result_array, $active_id, $pass, TRUE);
				$this->tpl->setVariable("SCORING_DATA", $scoring);
			}
		}
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this, "scoringfilter"));
		include_once "./Services/RTE/classes/class.ilRTE.php";
		$rtestring = ilRTE::_getRTEClassname();
		include_once "./Services/RTE/classes/class.$rtestring.php";
		$rte = new $rtestring();
		$rte->addPlugin("latex");
		$rte->addButton("latex"); $rte->addButton("pastelatex");
		include_once "./classes/class.ilObject.php";
		$obj_id = ilObject::_lookupObjectId($_GET["ref_id"]);
		$obj_type = ilObject::_lookupType($_GET["ref_id"], TRUE);
		$rte->addRTESupport($obj_id, "fdb", "assessment");
	}
	
	/**
	* Sets the points of a question manually
	*/
	function setPointsManual()
	{
		if (array_key_exists("question", $_POST))
		{
			$keys = array_keys($_POST["question"]);
			$question_id = $keys[0];
			$points = $_POST["question"][$question_id];
			include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
			$maxpoints = assQuestion::_getMaximumPoints($question_id);
			$result = assQuestion::_setReachedPoints($_GET["active_id"], $question_id, $points, $maxpoints, $_GET["pass"], 1);
			
			// update learning progress
			include_once "./Modules/Test/classes/class.ilObjTestAccess.php";
			include_once("./Services/Tracking/classes/class.ilLPStatusWrapper.php");
			ilLPStatusWrapper::_updateStatus($this->object->getId(),
				ilObjTestAccess::_getParticipantId((int) $_GET["active_id"]));

			if ($result) 
			{
				ilUtil::sendSuccess($this->lng->txt("tst_change_points_done"));
			}
			else
			{
				ilUtil::sendFailure($this->lng->txt("tst_change_points_not_done"));
			}
		}
		$this->manscoring();
	}
	
	function setFeedbackManual()
	{
		if (array_key_exists("feedback", $_POST))
		{
			$feedbacks = array_keys($_POST["feedback"]);
			$question_id = $feedbacks[0];
			include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
			$feedback = ilUtil::stripSlashes($_POST["feedback"][$question_id], FALSE, ilObjAdvancedEditing::_getUsedHTMLTagsAsString("assessment"));
			$result = $this->object->saveManualFeedback($_GET["active_id"], $question_id, $_GET["pass"], $feedback);
			if ($result) 
			{
				ilUtil::sendSuccess($this->lng->txt("tst_set_feedback_done"));
			}
			else
			{
				ilUtil::sendFailure($this->lng->txt("tst_set_feedback_not_done"));
			}
		}
		$this->setPointsManual();
	}
}

?>

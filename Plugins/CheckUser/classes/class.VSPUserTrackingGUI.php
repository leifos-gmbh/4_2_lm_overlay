<?php
class VSPUserTrackingGUI
{
	var $ut = null;
	
	var $message = "";
	var $error = "";
	var $html = "";
	
	function VSPUserTrackingGUI()
	{		
		$this->ut = VSPUserTracking::getInstance();
		
		$this->executeCommand();	
	}
	
	function executeCommand()
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
	
	function setMessage($a_msg = '')
	{
		$this->message = $a_msg;
	}
	
	function getMessage()
	{
		return $this->message;
	}
	
	function setError($a_error_msg = '')
	{
		$this->error = $a_error_msg;
	}
	
	function getError()
	{
		return $this->error;
	}
	
	function getHTML()
	{		
		return $this->html;
	}
	
	function sendForm()
	{
		$errorMsg = '';
		
		$v[] = new ValidateLogin(ilUtil::stripSlashes($_POST['login']), '- ILIAS-Benutzername');
		$v[] = new ValidateDate($_POST['from']['year'] . '-' . $_POST['from']['month'] . '-' . $_POST['from']['day'], '- g&uuml;ltiges Startdatum');
		$v[] = new ValidateDate($_POST['till']['year'] . '-' . $_POST['till']['month'] . '-' . $_POST['till']['day'], '- g&uuml;ltiges Enddatum');		
				
		foreach($v as $validator)
		{
	        if (!$validator->isValid())
	        {
	            while ($error = $validator->getError())
	            {
	                $errorMsg .= $error."<br />\n";
	            }
	        }
		}
		
		if ($errorMsg != '')
		{		
			$this->setError($errorMsg);
			
			$this->showForm();
		}
		else
		{	
			$this->ut->setLoginName(ilUtil::stripSlashes($_POST['login']));
			$this->ut->setFromDate($_POST['from']['year'] . '-' . $_POST['from']['month'] . '-' . $_POST['from']['day']);
			$this->ut->setTillDate($_POST['till']['year'] . '-' . $_POST['till']['month'] . '-' . $_POST['till']['day']);
					
			$this->createExcelExport();
		}
			
		return true;
	}
	
	function createExcelExport()
	{
		$data_found = false;
		
		$gen_data = $this->ut->getGeneralTrackingData();
		if (!empty($gen_data)) $data_found = true;
		$lm_data = $this->ut->getRequestedLearningModules();
		if (!empty($lm_data)) $data_found = true;
		$lm_request_data = $this->ut->getRequestStatisticsForLearningModules();
		if (!empty($lm_request_data)) $data_found = true;
		$tst_data = $this->ut->getRequestedTests();
		if (!empty($tst_data)) $data_found = true;	
		$tst_request_data = $this->ut->getRequestStatisticsForTests();
		if (!empty($tst_request_data)) $data_found = true;
				
		if ($data_found == true)
		{		
			$excelfile = ilUtil::ilTempnam();
			
			$testname = ilUtil::prepareFormOutput($_POST['login'], true). "_" . date("Ymdhis");
			#$adapter = new ilExcelWriterAdapter(ilUtil::prepareFormOutput($_POST['login'], true). "_" . date("Ymdhis"), true);
			$adapter = new ilExcelWriterAdapter($excelfile, false);
	
			$workbook = $adapter->getWorkbook();
			
			$format_bold =& $workbook->addFormat();
			$format_bold->setBold();
			
			$format_datetime =& $workbook->addFormat();
			$format_datetime->setNumFormat("DD/MM/YYYY hh:mm:ss");
			$format_title =& $workbook->addFormat();
			$format_title->setBold();
			$format_title->setColor('black');
			$format_title->setPattern(1);
			$format_title->setFgColor('silver');
			$worksheet =& $workbook->addWorksheet();
			
			$y = 0;
			$x = 0;	
					
					
			$worksheet->write($y, $x, ilExcelUtils::_convert_text(utf8_encode("Benutzername:")), $format_title);
			$worksheet->write($y++, ++$x, ilExcelUtils::_convert_text($row['firstname'] . " " . $gen_data['lastname']));
			$x = 0;
			$worksheet->write($y, $x, ilExcelUtils::_convert_text(utf8_encode("Zuletzt eingeloggt:")), $format_title);
			$worksheet->write($y++, ++$x, ilExcelUtils::_convert_text($this->ut->getAccessTimeAsString($gen_data['access_time'])));
			$x = 0;
			$worksheet->write($y, $x, ilExcelUtils::_convert_text(utf8_encode("Gesamtzeit im System:")), $format_title);
			$worksheet->write($y, ++$x, ilExcelUtils::_convert_text($this->ut->getSpentTimeAsString($gen_data['online_time'])));
			
			$x = 0;
			$y += 2;		
			
			$worksheet->write($y++, $x, ilExcelUtils::_convert_text(utf8_encode("Aufgerufene Lernmaterialien")), $format_title);
			
			$worksheet->write($y, $x++, ilExcelUtils::_convert_text(utf8_encode("Titel")), $format_bold);
			$worksheet->write($y, $x++, ilExcelUtils::_convert_text(utf8_encode("Typ")), $format_bold);
			$worksheet->write($y, $x++, ilExcelUtils::_convert_text(utf8_encode("letzter Aufruf am")), $format_bold);
			$worksheet->write($y, $x++, ilExcelUtils::_convert_text(utf8_encode("Aufrufe gesamt")), $format_bold);
			$worksheet->write($y, $x, ilExcelUtils::_convert_text(utf8_encode("Verweildauer gesamt")), $format_bold);			
						
			foreach ($lm_data as $lm)
			{
				$x = 0;
				$y++;
	
				$worksheet->write($y, $x++, ilExcelUtils::_convert_text($lm['title']));
				$worksheet->write($y, $x++, ilExcelUtils::_convert_text(strtoupper($lm['type'])));
				$worksheet->write($y, $x++, ilExcelUtils::_convert_text($this->ut->getAccessTimeAsString($lm['access_time'])));			
				$worksheet->write($y, $x++, ilExcelUtils::_convert_text($lm['visits']));
				$worksheet->write($y, $x, ilExcelUtils::_convert_text($this->ut->getSpentTimeAsString($lm['spent_time'])));
			}
			
			$x = 0;
			$y += 2;
			
			$worksheet->write($y++, $x, ilExcelUtils::_convert_text(utf8_encode("Aufgerufene Tests")), $format_title);
			
			$worksheet->write($y, $x++, ilExcelUtils::_convert_text(utf8_encode("Titel")), $format_bold);
			$worksheet->write($y, $x++, ilExcelUtils::_convert_text(utf8_encode("Typ")), $format_bold);
			$worksheet->write($y, $x++, ilExcelUtils::_convert_text(utf8_encode("letzter Aufruf am")), $format_bold);
			$worksheet->write($y, $x++, ilExcelUtils::_convert_text(utf8_encode("Aufrufe gesamt")), $format_bold);
			$worksheet->write($y, $x, ilExcelUtils::_convert_text(utf8_encode("Verweildauer gesamt")), $format_bold);			
				
			foreach ($tst_data as $tst)
			{
				$x = 0;
				$y++;
				
				$worksheet->write($y, $x++, ilExcelUtils::_convert_text($tst['title']));
				$worksheet->write($y, $x++, ilExcelUtils::_convert_text(strtoupper($tst['type'])));
				$worksheet->write($y, $x++, ilExcelUtils::_convert_text($this->ut->getAccessTimeAsString(strtotime($tst['last_access_time']))));			
				$worksheet->write($y, $x++, ilExcelUtils::_convert_text($tst['requests']));
				$worksheet->write($y, $x, ilExcelUtils::_convert_text($this->ut->getSpentTimeAsString($tst['spent_time'])));
			}
			
			$x = 0;
			$y += 2;
					
			$lm_output_array = array();
			$starttime = null;
			$last_compare_time = null;			
			foreach ($lm_request_data as $obj_id => $items)
			{			
				$lm_output_array[$obj_id] = array("periods" => array());
					
				$count_items = 0;	
				foreach ($items as $lm)
				{					
					if ($count_items == 0)
					{
						$lm_output_array[$obj_id]["title"] = $lm["title"];
						
						unset($starttime);
						unset($last_compare_time);
					}
					
					if (!isset($last_compare_time) || strtotime($lm["acc_time"]) - $last_compare_time > $this->ut->getMergeTime())
					{						
						$starttime = strtotime($lm["acc_time"]);
						
						$lm_output_array[$obj_id]["periods"][$starttime] = array("starttime" => $starttime,
																			   "endtime" => $starttime);	
					}
					
					$last_compare_time = strtotime($lm["acc_time"]);
					
					$lm_output_array[$obj_id]["periods"][$starttime]["endtime"] = strtotime($lm["acc_time"]);
					
					$count_items++;
				}
			}			
			
			$headline = utf8_encode("Aufrufstatistik für Lernmodule") . " vom " . date("d.m.Y", strtotime($this->ut->getFromDate())) . " bis zum " . date("d.m.Y", strtotime($this->ut->getTillDate()));
			$worksheet->write($y++, $x, ilExcelUtils::_convert_text($headline), $format_title);
			$counter = 0;
			foreach ($lm_output_array as $obj_id => $periods)
			{	
				$x = 0;				
				if ($counter == 0)
				{
					$worksheet->write($y, $x, ilExcelUtils::_convert_text(utf8_encode("Titel")), $format_bold);
					$worksheet->write($y, ++$x, ilExcelUtils::_convert_text(utf8_encode("Erster Aufruf")), $format_bold);
					$worksheet->write($y, ++$x, ilExcelUtils::_convert_text(utf8_encode("Letzter Aufruf")), $format_bold);
					$worksheet->write($y++, ++$x, ilExcelUtils::_convert_text(utf8_encode("Gesamtzeit")), $format_bold);
				}												
					
				$inner_counter = 0;							
				foreach ($periods["periods"] as $period)
				{	
					$x = 0;

					if ($inner_counter == 0) $worksheet->write($y, $x, ilExcelUtils::_convert_text($periods["title"]));
					$worksheet->write($y, ++$x, ilExcelUtils::_convert_text($this->ut->getAccessTimeAsString($period["starttime"])));
					$worksheet->write($y, ++$x, ilExcelUtils::_convert_text($this->ut->getAccessTimeAsString($period["endtime"])));
					$worksheet->write($y, ++$x, ilExcelUtils::_convert_text($this->ut->getSpentTimeAsString($period["endtime"] - $period["starttime"])));
					
					$y++;
					++$inner_counter;
				}
				
				++$y;
				++$counter;
			}
			
			$x = 0;
			$y++;	
			
			$tst_output_array = array();
			$starttime = null;
			$last_compare_time = null;
			foreach ($tst_request_data as $obj_id => $items)
			{
				$tst_output_array[$obj_id] = array("periods" => array());
				
				$count_items = 0;
								
				foreach ($items as $tst)
				{
					if ($count_items == 0)
					{
						$tst_output_array[$obj_id]["title"] = $tst["title"];
						
						unset($starttime);
						unset($last_compare_time);
					}

					if (!isset($last_compare_time) || strtotime($tst["access_time"]) - $last_compare_time > $this->ut->getMergeTime())
					{		
						$starttime = strtotime($tst["access_time"]);
						
						$tst_output_array[$obj_id]["periods"][$starttime] = array("starttime" => $starttime,
																			   "endtime" => $starttime);	
					}
					
					$last_compare_time = strtotime($tst["access_time"]);
					
					$tst_output_array[$obj_id]["periods"][$starttime]["endtime"] = strtotime($tst["access_time"]);					
					$count_items++;
				}
			}			
			
			$headline = utf8_encode("Aufrufstatistik für Tests") . " vom " . date("d.m.Y", strtotime($this->ut->getFromDate())) . " bis zum " . date("d.m.Y", strtotime($this->ut->getTillDate()));
			$worksheet->write($y++, $x, ilExcelUtils::_convert_text($headline), $format_title);
			$counter = 0;
			foreach ($tst_output_array as $obj_id => $periods)
			{	
				$x = 0;				
				if ($counter == 0)
				{
					$worksheet->write($y, $x, ilExcelUtils::_convert_text(utf8_encode("Titel")), $format_bold);
					$worksheet->write($y, ++$x, ilExcelUtils::_convert_text(utf8_encode("Erster Aufruf")), $format_bold);
					$worksheet->write($y, ++$x, ilExcelUtils::_convert_text(utf8_encode("Letzter Aufruf")), $format_bold);
					$worksheet->write($y++, ++$x, ilExcelUtils::_convert_text(utf8_encode("Gesamtzeit")), $format_bold);
				}												
					
				$inner_counter = 0;							
				foreach ($periods["periods"] as $period)
				{	
					$x = 0;

					if ($inner_counter == 0) $worksheet->write($y, $x, ilExcelUtils::_convert_text($periods["title"]));
					$worksheet->write($y, ++$x, ilExcelUtils::_convert_text($this->ut->getAccessTimeAsString($period["starttime"])));
					$worksheet->write($y, ++$x, ilExcelUtils::_convert_text($this->ut->getAccessTimeAsString($period["endtime"])));
					$worksheet->write($y, ++$x, ilExcelUtils::_convert_text($this->ut->getSpentTimeAsString($period["endtime"] - $period["starttime"])));
					
					$y++;
					++$inner_counter;
				}
				
				++$y;
				++$counter;
			}
			
			$workbook->close();
			ilUtil::deliverFile($excelfile, "$testname.xls", "application/vnd.ms-excel");
		}
		else
		{			
			$this->setMessage('Es konnten keine Daten zum eingegebenen Benutzernamen gefunden werden');
		}		
		
		$this->showForm();
		
		return true;
	}
	
	function showForm()
	{
		$this->html .= "
		<?xml version=\"1.0\" encoding=\"UTF-8\"?>
		<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1 plus MathML 2.0 plus SVG 1.1//EN\"
			\"http://www.w3.org/2002/04/xhtml-math-svg/xhtml-math-svg.dtd\">
		<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\en\>
		<head>
		<title>User Tracking</title>	
			<link rel=\"stylesheet\" type=\"text/css\" href=\"./templates/default/delos.css?vers=3-8-0-2007-06-21\" />
			<style type=\"text/css\">
			div.clearer {
				clear:both;
				font-size:1px;
				line-height:1px;
				display:block;
				height:1px;
			} 
			</style>
		</head>
		<body>
		<div id=\"main-container\">";				
		$this->html	.= "
			<div align=\"center\">
			<img src=\"./templates/default/images/ilias_logo_big.png\" border=\"0\" style=\"margin: 20px;\" />
			<div><a href=\"./learningtimeexport.php\"><input onClick=\"document.location.href='./learningtimeexport.php';\" class=\"submit\" type=\"button\" value=\"Lernzeiten Export\"/></a><br /><br /></div>";
	
		if ($this->getError() != '') $this->html .= "<p class=\"warning\" style=\"width: 450px; text-align: left;\">Bitte geben Sie noch folgende Daten ein:<br />".$this->getError()."</p>";
		if ($this->getMessage() != '') $this->html .= "<div class=\"message\" style=\"width: 450px;\">".$this->getMessage()."</div>";
		$this->html .= "	
			<form method=\"post\" action=\"" . $_SERVER['PHP_SELF'] . "?cmd=post&fallbackCmd=sendForm\">
			<input type=\"hidden\" name=\"sendRequest\" value=\"1\" />
			<table class=\"std\" width=\"450\">
			<tr class=\"tblheader\">
				<td class=\"std\" colspan=\"2\">User Tracking</td>
			</tr>
			<tr>
				<td class=\"option\"><label for=\"login\">Benutzername<span class=\"asterisk\">*</span></label></td>
				<td class=\"option_value\"><input type=\"text\" name=\"login\" id=\"login\" value=\"";
		
		if ($this->getError() != '' || $this->getMessage() != '') $this->html .= stripslashes($_POST['login']);
		
		$this->html .= "\" /></td>
			</tr>
			<tr>
				<td class=\"option\"><label for=\"from_day\">Zeitraum der Auswertung<span class=\"asterisk\">*</span><br /><span style=\"font-size: smaller;\">(nur für Aufrufstatistik)</span></label></td>
				<td class=\"option_value\">
					<p><label for=\"from_day\" style=\"float: left; width: 50px;\">von</label>
					<select name=\"from[day]\" id=\"from_day\" size=\"1\">
						<option value=\"\"></option>";
		for ($i = 1; $i <= 31; $i++)
		{
			$this->html .= "<option value=\"" . ($i < 10 ? '0' . $i : $i) . "\"";
			if ($i == (int) $_POST['from']['day'] && ($this->getError() != '' || $this->getMessage() != ''))
			{
			 	$this->html .= " selected=\"selected\"";
			}
			$this->html .= ">" . ($i < 10 ? '0' . $i : $i) . "</option>";							
		}	
		$this->html .= "</select>
					<select name=\"from[month]\" id=\"from_month\" size=\"1\">
						<option value=\"\"></option>";
		for ($i = 1; $i <= 12; $i++)
		{
			$this->html .= "<option value=\"" . ($i < 10 ? '0' . $i : $i) . "\"";;
			if ($i == (int) $_POST['from']['month'] && ($this->getError() != '' || $this->getMessage() != ''))
			{
			 	$this->html .= " selected=\"selected\"";
			}
			$this->html .= ">" . ($i < 10 ? '0' . $i : $i) . "</option>";							
		}			
	
		$this->html .= "</select>
					<select name=\"from[year]\" id=\"from_year\" size=\"1\">
						<option value=\"\"></option>";
		for ($i = 2004; $i <= date('Y'); $i++)
		{
			$this->html .= "<option value=\"" . $i . "\"";
			if ($i == (int) $_POST['from']['year'] && ($this->getError() != '' || $this->getMessage() != ''))
			{
			 	$this->html .= " selected=\"selected\"";
			}
			$this->html .= ">" . $i . "</option>";							
		}			
	
		$this->html .= "</select>
					<div class=\"clearer\">&nbsp;</div></p>
				
					<p><label for=\"till_day\" style=\"float: left; width: 50px;\">bis</label>
					<select name=\"till[day]\" id=\"till_day\" size=\"1\">
						<option value=\"\"></option>";
	for ($i = 1; $i <= 31; $i++)
		{
			$this->html .= "<option value=\"" . ($i < 10 ? '0' . $i : $i) . "\"";
			if ($i == (int) $_POST['till']['day'] && ($this->getError() != '' || $this->getMessage() != ''))
			{
			 	$this->html .= " selected=\"selected\"";
			}
			$this->html .= ">" . ($i < 10 ? '0' . $i : $i) . "</option>";							
		}			
	
		$this->html .= "</select>
					<select name=\"till[month]\" id=\"till_month\" size=\"1\">
						<option value=\"\"></option>";
		for ($i = 1; $i <= 12; $i++)
		{
			$this->html .= "<option value=\"" . ($i < 10 ? '0' . $i : $i) . "\"";
			if ($i == (int) $_POST['till']['month'] && ($this->getError() != '' || $this->getMessage() != ''))
			{
			 	$this->html .= " selected=\"selected\"";
			}
			$this->html .= ">" . ($i < 10 ? '0' . $i : $i) . "</option>";							
		}			
	
		$this->html .= "</select>
					<select name=\"till[year]\" id=\"till_year\" size=\"1\">
						<option value=\"\"></option>";
		for ($i = 2004; $i <= date('Y'); $i++)
		{
			$this->html .= "<option value=\"" . $i . "\"";
			if ($i == (int) $_POST['till']['year'] && ($this->getError() != '' || $this->getMessage() != ''))
			{
			 	$this->html .= " selected=\"selected\"";
			}
			$this->html .= ">" . $i . "</option>";							
		}
		
		$this->html .= "</select>
					<div class=\"clearer\">&nbsp;</div></p>
				</td>			
			</tr>
			<tr class=\"tblfooter\">
				<td class=\"submit\" colspan=\"2\" align=\"right\"><input class=\"submit\" type=\"submit\" name=\"cmd[sendForm]\" value=\"Absenden\"/></td>
			</tr>
			</table>	
			</form>	
			</div>
		</div>
		
		</body>
		</html>";
	}	
}
?>
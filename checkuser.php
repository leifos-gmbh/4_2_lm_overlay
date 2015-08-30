<?php

	#header("Content-Type: text/html; charset=utf-8");

	include_once 'include/inc.header.php';

	global $rbacreview, $ilUser, $lng;
		
	if($rbacreview->isAssigned($ilUser->getId(), SYSTEM_ROLE_ID))
	{
		include_once('./Plugins/CheckUser/classes/class.VSPUserTrackingGUI.php');
		include_once('./Plugins/CheckUser/classes/class.VSPUserTracking.php');
		include_once('./Plugins/CheckUser/classes/class.Validator.php');
		include_once('./Services/Utilities/classes/class.ilUtil.php');	
		include_once('./Services/Excel/classes/class.ilExcelWriterAdapter.php');
		include_once('./Services/Excel/classes/class.ilExcelUtils.php');

		$ut_gui = new VSPUserTrackingGUI;
		echo $ut_gui->getHTML();
	}
	else
	{
		die($lng->txt('permission_denied'));
	}

?>
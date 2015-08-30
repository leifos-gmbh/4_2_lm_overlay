<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Class ilObj<module_name>GUI
*
* @author your name <your email> 
* @version $Id: template.class.ilObjModuleNameGUI.php 27165 2011-01-04 13:48:35Z jluetzen $
* 
* @extends ilObjectGUI
*/

require_once "class.ilObjectGUI.php";

class ilObj<module_name>GUI extends ilObjectGUI
{
	/**
	* Constructor
	* @access public
	*/
	function ilObj<module_name>GUI($a_data,$a_id,$a_call_by_reference,$a_prepare_output = true)
	{
		$this->type = "<type ID>";
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference,$a_prepare_output);
	}
	
	/**
	* save object
	* @access	public
	*/
	function saveObject()
	{
		global $rbacadmin;

		// create and insert forum in objecttree
		$newObj = parent::saveObject();

		// put here object specific stuff
			
		// always send a message
		ilUtil::sendInfo($this->lng->txt("object_added"),true);
		
		ilUtil::redirect($this->getReturnLocation("save",$this->ctrl->getLinkTarget($this,"","",false,false)));
	}
	
	/**
	* get tabs
	* @access	public
	* @param	object	tabs gui object
	*/
	function getTabs(&$tabs_gui)
	{
		// tabs are defined manually here. The autogeneration via objects.xml will be deprecated in future
		// for usage examples see ilObjGroupGUI or ilObjSystemFolderGUI
	}
} // END class.ilObj<module_name>
?>

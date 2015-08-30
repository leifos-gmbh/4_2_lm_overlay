<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
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
* Class ilCourseAvailabilityGUI
*
* @author Stefan Meyer <meyer@leifos.com> 
* @version $Id: class.ilCourseAvailabilityGUI.php 23143 2010-03-09 12:15:33Z smeyer $
* 
* @extends ilObjectGUI
*/

class ilCourseItemAdministrationGUI
{
	var $container_obj;
	var $tpl;
	var $ctrl;
	var $lng;

	/**
	* Constructor
	* @access public
	*/
	function ilObjCourseItemAdministrationGUI(&$container_obj,$a_item_id)
	{
		global $tpl,$ilCtrl,$lng,$ilObjDataCache;

		$this->tpl =& $tpl;
		$this->ctrl =& $ilCtrl;
		$this->lng =& $lng;

		$this->container_obj =& $container_obj;

		$this->item_id = $a_item_id;
		$this->ctrl->saveParameter($this,'item_id');
	}

} // END class.ilObjCourseGrouping
?>

<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2005 ILIAS open source, University of Cologne            |
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

include_once ("Services/Help/classes/class.ilHelp.php");

/**
* Help GUI class.
*
* @author	Alex Killing <alex.killing@gmx.de>
* @version	$Id: class.ilHelpGUI.php 8677 2005-09-18 20:54:49Z akill $
*/
class ilHelpGUI
{
	
	/**
	* constructor
	*/
	function ilHelpGUI()
	{
		global $ilCtrl;
				
		$this->ctrl =& $ilCtrl;
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		$cmd = $this->ctrl->getCmd("showHelp");
		$next_class = $this->ctrl->getNextClass($this);
		
		switch($next_class)
		{
			default:
				return $this->$cmd();
				break;
		}
	}
	
	/**
	* redirect to help page
	*/
	function showHelp()
	{
		global $ilHelp;
		
		if ($target = $ilHelp->getHelpPage())
		{
			ilUtil::redirect($target);
		}
		else
		{
			echo "Could not find help files.";
		}
	}
	

}
?>
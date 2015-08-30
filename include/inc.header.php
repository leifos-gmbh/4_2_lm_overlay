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

/**
* header include for all ilias files. This script will be always included first for every page
* in ILIAS. Inits RBAC-Classes & recent user, log-,language- & tree-object
*
* @author Stefan Meyer <meyer@leifos.com>
* @author Sascha Hofmann <saschahofmann@gmx.de>
* @version $Id: inc.header.php 32338 2011-12-28 13:07:19Z akill $
*
* @package ilias-core
*/

// temporary PHP 5.4 fix. please do not merge this to ILIAS 4.3 or higher
// we should get rid of these calls in newer versions
if (!function_exists("session_unregister"))
{
	function session_unregister($a)
	{
		unset($_SESSION[$a]);
	}
}


$GLOBALS['ilGlobalStartTime'] = microtime();
require_once("Services/Init/classes/class.ilInitialisation.php");
$ilInit = new ilInitialisation();
$GLOBALS['ilInit'] = $ilInit;
$ilInit->initILIAS();

?>

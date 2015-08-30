<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* logout script for ilias
*
* @author Sascha Hofmann <shofmann@databay.de>
* @version $Id: logout.php 21406 2009-09-01 14:48:42Z mkunkel $
*
* @package ilias-core
*/

require_once "include/inc.header.php";

$ilCtrl->initBaseClass("ilStartUpGUI");
$ilCtrl->setCmd("showLogout");
$ilCtrl->setTargetScript("ilias.php");
$ilCtrl->callBaseClass();
$ilBench->save();

exit;
?>
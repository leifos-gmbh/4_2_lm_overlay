<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Factory for importer/exporter implementers
 * 
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilImportExportFactory.php 34789 2012-05-24 09:40:57Z smeyer $
 */
class ilImportExportFactory
{

	public static function getExporterClass($a_type)
	{
		global $objDefinition;

		$comp = $objDefinition->getComponentForType($a_type);
		$c = explode("/", $comp);
		$class = "il".$c[1]."Exporter";

		if(@include_once "./".$comp."/classes/class.".$class.".php")
		{
			return $class;
		}
		throw InvalidArgumentException('Invalid exporter type given');
	}
}
?>

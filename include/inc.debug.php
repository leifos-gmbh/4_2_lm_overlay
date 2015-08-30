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
* debugging functions
* 
* @author Sascha Hofmann <shofmann@databay.de>
* @version $Id: inc.debug.php 30595 2011-09-09 12:55:07Z bheyser $
*
* @package ilias-develop
*/

/**
* shortcut for var_dump
* @access	public
* @param	mixed	any number of parameters
*/
function vd()
{
	$numargs = func_num_args();

	if ($numargs == 0)
	{
		return false;
	}
	
	$arg_list = func_get_args();
	$num = 1;

	
	foreach ($arg_list as $arg)
	{
		echo "<pre>variable ".$num.":<br/>";
		var_dump($arg);
		echo "</pre><br/>";
		$num++;
	}
	
	// BH: php 5.3 seems to not flushing the output consequently so following redirects are still performed
	// and the output of vd() would be lost in nirvana if we not flush the output manualy
	flush(); ob_flush();
}

function pr($var,$name = '')
{
	if($name != '') $name .= ' = ';
	echo '<pre>'.$name.print_r($var,true).'</pre>';

	flush(); ob_flush();
}

?>
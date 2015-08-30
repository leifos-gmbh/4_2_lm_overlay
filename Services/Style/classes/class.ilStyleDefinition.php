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
* parses the template.xml that defines all styles of the current template
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id: class.ilStyleDefinition.php 31126 2011-10-13 16:40:55Z fneumann $
*
* @extends ilSaxParser
*/
require_once("./classes/class.ilSaxParser.php");

class ilStyleDefinition extends ilSaxParser
{
	/**
	 * currently selected skin
	 * @var string
	 */
	static $current_skin;
	
	
	/**
	 * currently selected style
	 * @var string
	 */
	static $current_style;


	/**
	* Constructor
	*
	* parse
	*
	* @access	public
	*/
	function ilStyleDefinition($a_template_id = "")
	{
		global $ilias;

		if ($a_template_id == "")
		{
			// use function to get the current skin
			$a_template_id = self::getCurrentSkin();
		}

		// remember the template id
		$this->template_id = $a_template_id;

		if ($a_template_id == "default")
		{
			parent::ilSaxParser("./templates/".$a_template_id."/template.xml");
		}
		else
		{
			parent::ilSaxParser("./Customizing/global/skin/".$a_template_id."/template.xml");
		}
	}


	// PUBLIC METHODS

	/**
	* get translation type (sys, db or 0)s
	*
	* @param	string	object type
	* @access	public
	*/
	function getStyles()
	{
//echo ":".count($this->styles).":";
		if (is_array($this->styles))
		{
			return $this->styles;
		}
		else
		{
			return array();
		}
	}

	function getTemplateId()
	{
		return $this->template_id;
	}

	
	function getTemplateName()
	{
		return $this->template_name;
	}


	function getStyle($a_id)
	{
		return $this->styles[$a_id];
	}


	function getStyleName($a_id)
	{
		return $this->styles[$a_id]["name"];
	}


	function getImageDirectory($a_id)
	{
		return $this->styles[$a_id]["image_directory"];
	}

	function getSoundDirectory($a_id)
	{
		return $this->styles[$a_id]["sound_directory"];
	}
	
	public static function _getAllTemplates()
	{
		$skins = array();

		$skins[] = array("id" => "default");
		if ($dp = @opendir("./Customizing/global/skin"))
		{
			while (($file = readdir($dp)) != false)
			{
				//is the file a directory?
				if (is_dir("./Customizing/global/skin/".$file) && $file != "." && $file != ".." && $file != "CVS"
					&& $file != ".svn")
				{
					if (is_file("./Customizing/global/skin/".$file."/template.xml"))
					{
						$skins[] = array(
							"id" => $file
						);
					}
				}
			} // while
		}
		else
		{
			return $skins;
		}

		return $skins;
		
	}

	function getAllTemplates()
	{
		return self::_getAllTemplates();
	}
	

	// PRIVATE METHODS

	/**
	* set event handler
	*
	* @param	ressouce	internal xml_parser_handler
	* @access	private
	*/
	function setHandlers($a_xml_parser)
	{
		xml_set_object($a_xml_parser,$this);
		xml_set_element_handler($a_xml_parser, 'handlerBeginTag', 'handlerEndTag');
		xml_set_character_data_handler($a_xml_parser, 'handlerCharacterData');
	}

	/**
	* start tag handler
	*
	* @param	ressouce	internal xml_parser_handler
	* @param	string		element tag name
	* @param	array		element attributes
	* @access	private
	*/
	function handlerBeginTag($a_xml_parser, $a_name, $a_attribs)
	{
		if (!isset($a_attribs["sound_directory"]))
		{
			$a_attribs["sound_directory"] = "";
		}
		
		if (!isset($a_attribs["browsers"]))
		{
			$a_attribs["browsers"] = "";
		}
		
		switch($a_name)
		{
			case "template" :
				$this->template_name = $a_attribs["name"];
				break;

			case "style" :
				$this->styles[$a_attribs["id"]] =
					array(	"id" => $a_attribs["id"],
							"name" => $a_attribs["name"],
							"css_file" => $a_attribs["id"].".css",
							"image_directory" => $a_attribs["image_directory"],
							"sound_directory" => $a_attribs["sound_directory"]
					);
				$browsers =
					explode(",", $a_attribs["browsers"]);
				foreach ($browsers as $val)
				{
					$this->styles[$a_attribs["id"]]["browsers"][] = trim($val);
				}
				break;
		}
	}
	
	
	/**
	* Check wheter a style exists
	*
	* @param	string	$skin		skin id
	* @param	string	$style		style id
	*
	* @return	boolean
	*/
	static function styleExists($skin, $style)
	{
		if ($skin == "default")
		{		
			if (is_file("./templates/".$skin."/template.xml") &&
				is_file("./templates/".$skin."/".$style.".css")
				)
			{
				return true;
			}
		}
		else
		{
			if (is_file("./Customizing/global/skin/".$skin."/template.xml") &&
				is_file("./Customizing/global/skin/".$skin."/".$style.".css")
				)
			{
				return true;
			}
		}
		return false;
	}

	/**
	* Check wheter a skin exists
	*
	* @param	string	$skin		skin id
	*
	* @return	boolean
	*/
	static function skinExists($skin)
	{
		if ($skin == "default")
		{		
			if (is_file("./templates/".$skin."/template.xml"))
			{
				return true;
			}
		}
		else
		{
			if (is_file("./Customizing/global/skin/".$skin."/template.xml"))
			{
				return true;
			}
		}
		return false;
	}

	/**
	* end tag handler
	*
	* @param	ressouce	internal xml_parser_handler
	* @param	string		data
	* @access	private
	*/
	function handlerCharacterData($a_xml_parser,$a_data)
	{
		// DELETE WHITESPACES AND NEWLINES OF CHARACTER DATA
		$a_data = preg_replace("/\n/","",$a_data);
		$a_data = preg_replace("/\t+/","",$a_data);

		if(!empty($a_data))
		{
			switch($this->current_tag)
			{
				default:
					break;
			}
		}
	}

	/**
	* end tag handler
	*
	* @param	ressouce	internal xml_parser_handler
	* @param	string		element tag name
	* @access	private
	*/
	function handlerEndTag($a_xml_parser,$a_name)
	{
	}
		
	
	/**
	 * get the current skin
	 *
	 * use always this function instead of getting the account's skin
	 * the current skin may be changed on the fly by setCurrentSkin()
	 * 
	 * @return	string	skin id
	 */
	public static function getCurrentSkin()
	{
		global $ilias;

		return isset(self::$current_skin) ? self::$current_skin	:
											$ilias->account->skin;
	}
	
	/**
	 * get the current style
	 *
	 * use always this function instead of getting the account's style
	 * the current style may be changed on the fly by setCurrentStyle()

	 * @return	string	style id
	 */
	public static function getCurrentStyle()
	{
		global $ilias;	
		
		return isset(self::$current_style) ? self::$current_style : 	
											$ilias->account->prefs['style'];
	}
	
	/**
	 * set a new current skin
	 * 
	 * @param	string		skin id
	 */
	public static function setCurrentSkin($a_skin)
	{
		global $styleDefinition;
		
		if (is_object($styleDefinition)
		and $styleDefinition->getTemplateId() != $a_skin)
		{
			$styleDefinition = new ilStyleDefinition($a_skin);
			$styleDefinition->startParsing();
		}
		
		self::$current_skin = $a_skin;
	}
	
	
	/**
	 * set a new current style
	 * 
	 * @param	string	style id
	 */
	public static function setCurrentStyle($a_style)
	{
		self::$current_style = $a_style;
	}
}
?>

<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2008 ILIAS open source, University of Cologne            |
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
* This class represents a number property in a property form.
*
* @author Alex Killing <alex.killing@gmx.de> 
* @version $Id$
* @ingroup	ServicesForm
*/
class ilNumberInputGUI extends ilSubEnabledFormPropertyGUI
{
	protected $value;
	protected $maxlength = 200;
	protected $size = 40;
	protected $suffix;
	protected $minvalue = false;
	protected $minvalueShouldBeGreater = false;
	protected $maxvalue = false;
	protected $maxvalueShouldBeLess = false;
	
	/**
	* Constructor
	*
	* @param	string	$a_title	Title
	* @param	string	$a_postvar	Post Variable
	*/
	function __construct($a_title = "", $a_postvar = "")
	{
		parent::__construct($a_title, $a_postvar);
	}

	/**
	* Set suffix.
	*
	* @param	string	$a_value	suffix
	*/
	function setSuffix($a_value)
	{
		$this->suffix = $a_value;
	}

	/**
	* Get suffix.
	*
	* @return	string	suffix
	*/
	function getSuffix()
	{
		return $this->suffix;
	}

	/**
	* Set Value.
	*
	* @param	string	$a_value	Value
	*/
	function setValue($a_value)
	{
		$this->value = str_replace(',', '.', $a_value);
	}

	/**
	* Get Value.
	*
	* @return	string	Value
	*/
	function getValue()
	{
		return $this->value;
	}

	/**
	* Set Max Length.
	*
	* @param	int	$a_maxlength	Max Length
	*/
	function setMaxLength($a_maxlength)
	{
		$this->maxlength = $a_maxlength;
	}

	/**
	* Get Max Length.
	*
	* @return	int	Max Length
	*/
	function getMaxLength()
	{
		return $this->maxlength;
	}

	/**
	* Set minvalueShouldBeGreater
	*
	* @param	boolean	$a_bool	true if the minimum value should be greater than minvalue
	*/
	function setMinvalueShouldBeGreater($a_bool)
	{
		$this->minvalueShouldBeGreater = $a_bool;
	}
	
	/**
	* Get minvalueShouldBeGreater
	*
	* @return	boolean	true if the minimum value should be greater than minvalue
	*/
	function minvalueShouldBeGreater()
	{
		return $this->minvalueShouldBeGreater;
	}

	/**
	* Set maxvalueShouldBeLess
	*
	* @param	boolean	$a_bool	true if the maximum value should be less than maxvalue
	*/
	function setMaxvalueShouldBeLess($a_bool)
	{
		$this->maxvalueShouldBeLess = $a_bool;
	}
	
	/**
	* Get maxvalueShouldBeLess
	*
	* @return	boolean	true if the maximum value should be less than maxvalue
	*/
	function maxvalueShouldBeLess()
	{
		return $this->maxvalueShouldBeLess;
	}
	
	/**
	* Set Size.
	*
	* @param	int	$a_size	Size
	*/
	function setSize($a_size)
	{
		$this->size = $a_size;
	}

	/**
	* Set value by array
	*
	* @param	array	$a_values	value array
	*/
	function setValueByArray($a_values)
	{
		$this->setValue($a_values[$this->getPostVar()]);
	}

	/**
	* Get Size.
	*
	* @return	int	Size
	*/
	function getSize()
	{
		return $this->size;
	}
	
	/**
	* Set Minimum Value.
	*
	* @param	float	$a_minvalue	Minimum Value
	*/
	function setMinValue($a_minvalue)
	{
		$this->minvalue = $a_minvalue;
	}

	/**
	* Get Minimum Value.
	*
	* @return	float	Minimum Value
	*/
	function getMinValue()
	{
		return $this->minvalue;
	}

	/**
	* Set Maximum Value.
	*
	* @param	float	$a_maxvalue	Maximum Value
	*/
	function setMaxValue($a_maxvalue)
	{
		$this->maxvalue = $a_maxvalue;
	}

	/**
	* Get Maximum Value.
	*
	* @return	float	Maximum Value
	*/
	function getMaxValue()
	{
		return $this->maxvalue;
	}

	/**
	* Set Decimal Places.
	*
	* @param	int	$a_decimals	Decimal Places
	*/
	function setDecimals($a_decimals)
	{
		$this->decimals = $a_decimals;
	}

	/**
	* Get Decimal Places.
	*
	* @return	int	Decimal Places
	*/
	function getDecimals()
	{
		return $this->decimals;
	}

	/**
	* Check input, strip slashes etc. set alert, if input is not ok.
	*
	* @return	boolean		Input ok, true/false
	*/	
	function checkInput()
	{
		global $lng;
		
		$_POST[$this->getPostVar()] = ilUtil::stripSlashes($_POST[$this->getPostVar()]);
		if ($this->getRequired() && trim($_POST[$this->getPostVar()]) == "")
		{
			$this->setAlert($lng->txt("msg_input_is_required"));

			return false;
		}

		if (trim($_POST[$this->getPostVar()]) != "" &&
			! is_numeric(str_replace(',', '.', $_POST[$this->getPostVar()])))
		{
			$this->setAlert($lng->txt("form_msg_numeric_value_required"));

			return false;
		}

		if ($this->minvalueShouldBeGreater())
		{
			if (trim($_POST[$this->getPostVar()]) != "" &&
				$this->getMinValue() !== false &&
				$_POST[$this->getPostVar()] <= $this->getMinValue())
			{
				$this->setAlert($lng->txt("form_msg_value_too_low"));

				return false;
			}
		}
		else
		{
			if (trim($_POST[$this->getPostVar()]) != "" &&
				$this->getMinValue() !== false &&
				$_POST[$this->getPostVar()] < $this->getMinValue())
			{
				$this->setAlert($lng->txt("form_msg_value_too_low"));

				return false;
			}
		}

		if ($this->maxvalueShouldBeLess())
		{
			if (trim($_POST[$this->getPostVar()]) != "" &&
				$this->getMaxValue() !== false &&
				$_POST[$this->getPostVar()] >= $this->getMaxValue())
			{
				$this->setAlert($lng->txt("form_msg_value_too_high"));

				return false;
			}
		}
		else
		{
			if (trim($_POST[$this->getPostVar()]) != "" &&
				$this->getMaxValue() !== false &&
				$_POST[$this->getPostVar()] > $this->getMaxValue())
			{
				$this->setAlert($lng->txt("form_msg_value_too_high"));

				return false;
			}
		}
		
		return $this->checkSubItemsInput();
	}

	/**
	* Insert property html
	*
	* @return	int	Size
	*/
	function insert(&$a_tpl)
	{
		$html = $this->render();

		$a_tpl->setCurrentBlock("prop_generic");
		$a_tpl->setVariable("PROP_GENERIC", $html);
		$a_tpl->parseCurrentBlock();
	}

	/**
	* Insert property html
	*/
	function render()
	{
		global $lng;

		$tpl = new ilTemplate("tpl.prop_number.html", true, true, "Services/Form");

		if (strlen($this->getValue()))
		{
			$tpl->setCurrentBlock("prop_number_propval");
			$tpl->setVariable("PROPERTY_VALUE", ilUtil::prepareFormOutput($this->getValue()));
			$tpl->parseCurrentBlock();
		}
		$tpl->setCurrentBlock("prop_number");
		
		$tpl->setVariable("POST_VAR", $this->getPostVar());
		$tpl->setVariable("ID", $this->getFieldId());
		$tpl->setVariable("SIZE", $this->getSize());
		$tpl->setVariable("MAXLENGTH", $this->getMaxLength());
		if (strlen($this->getSuffix())) $tpl->setVariable("INPUT_SUFFIX", $this->getSuffix());
		if ($this->getDisabled())
		{
			$tpl->setVariable("DISABLED",
				" disabled=\"disabled\"");
		}
		
		// constraints
		if ($this->getDecimals() > 0)
		{
			$constraints = $lng->txt("form_format").": ###.".str_repeat("#", $this->getDecimals());
			$delim = ", ";
		}
		if ($this->getMinValue() !== false)
		{
			$constraints.= $delim.$lng->txt("form_min_value").": ".(($this->minvalueShouldBeGreater()) ? "&gt; " : "").$this->getMinValue();
			$delim = ", ";
		}
		if ($this->getMaxValue() !== false)
		{
			$constraints.= $delim.$lng->txt("form_max_value").": ".(($this->maxvalueShouldBeLess()) ? "&lt; " : "").$this->getMaxValue();
			$delim = ", ";
		}
		if ($constraints != "")
		{
			$tpl->setVariable("TXT_NUMBER_CONSTRAINTS", $constraints);
		}
		
		$tpl->parseCurrentBlock();

		return $tpl->get();
	}

	/**
	 * parse post value to make it comparable
	 *
	 * used by combination input gui
	 */
	function getPostValueForComparison()
	{
		$value = ilUtil::stripSlashes($_POST[$this->getPostVar()]);
		if($value != "")
		{
			return (int)$value;
		}
	}
}
?>
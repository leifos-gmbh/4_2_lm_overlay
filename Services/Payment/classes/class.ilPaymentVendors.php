<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
* Class ilPaymentVendors
* 
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id: class.ilPaymentVendors.php 22133 2009-10-16 08:09:11Z nkrzywon $
*
* @extends ilObject
* @package ilias-core
*/

class ilPaymentVendors
{
	var $db = null;

	var $vendors = array();

	/**
	* Constructor
	* @access	public
	*/
	function ilPaymentVendors()
	{
		global $ilDB;

		$this->db = $ilDB;

		$this->__read();
	}

	function getVendors()
	{
		return $this->vendors;
	}

	function isAssigned($a_usr_id)
	{
		return isset($this->vendors[$a_usr_id]);
	}

	function add($a_usr_id)
	{
		if(isset($this->vendors[$a_usr_id]))
		{
			die("class.ilPaymentVendors::add() Vendor already exists");
		}

		$statement = $this->db->manipulateF('
			INSERT INTO payment_vendors
			(	vendor_id,
				cost_center
			) VALUES (%s,%s)',
			array('integer', 'text'),
			array($a_usr_id, 'IL_INST_ID_'.$a_usr_id));
		
		$this->__read();

		return true;
	}
	function update($a_usr_id, $a_cost_center)
	{
		$statement = $this->db->manipulateF('
			UPDATE payment_vendors 
			SET cost_center = %s
			WHERE vendor_id = %s',
			array('text', 'integer'),
			array($a_cost_center, $a_usr_id));	
		
		$this->__read();

		return true;
	}
	function delete($a_usr_id)
	{
		if(!isset($this->vendors[$a_usr_id]))
		{
			die("class.ilPaymentVendors::delete() Vendor does not exist");
		}

		$statement = $this->db->manipulateF('
			DELETE FROM payment_vendors WHERE vendor_id = %s',
			array('integer'),
			array($a_usr_id)); 

		$this->__read();
		
		return true;
	}

	// PRIVATE
	function __read()
	{
		$this->vendors = array();

		$res = $this->db->query('SELECT * FROM payment_vendors');

				
		while($row = $this->db->fetchObject($res))
		{
			$this->vendors[$row->vendor_id]['vendor_id'] = $row->vendor_id;
			$this->vendors[$row->vendor_id]['cost_center'] = $row->cost_center;
		}
		return true;
	}

	// STATIC
	function _isVendor($a_usr_id)
	{
		global $ilDB;

		$res = $ilDB->queryf('
			SELECT * FROM payment_vendors WHERE vendor_id = %s',
			array('integer'), array($a_usr_id));
		
		return $res->numRows() ? true : false;
	}

	function _getCostCenter($a_usr_id)
	{
		global $ilDB;

		$res = $ilDB->queryf('
			SELECT * FROM payment_vendors WHERE vendor_id = %s',
			array('integer'),array($a_usr_id));
	
		while($row = $ilDB->fetchObject($res))
		{
			return $row->cost_center;
		}
		return -1;
	}		

} // END class.ilPaymentVendors
?>

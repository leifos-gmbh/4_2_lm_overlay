<?php
include_once(dirname(__FILE__) . '/class.ValidateLogin.php');
include_once(dirname(__FILE__) . '/class.ValidateDate.php');
class Validator
{    
    var $errorMsg;
	
    function Validator()
    {
        $this->errorMsg = array();
        $this->validate();
    }
  
    function validate()
    {        
    }   
 
    function isValid()
    {
        if (isset ($this->errorMsg))
        {
            return false;
        }
        else
        {
            return true;
        }
    }
    
    function setError($msg)
    {
        $this->errorMsg[] = $msg;
    }
 
    function getError()
    {
        return array_pop($this->errorMsg);
    }
}
?>

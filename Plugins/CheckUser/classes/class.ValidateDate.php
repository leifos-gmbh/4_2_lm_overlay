<?php
class ValidateDate extends Validator
{
   	var $date;
   	var $error;
 
    function ValidateDate($date, $error)
    {
        $this->date = $date;
        $this->setErrorMsg($error);
        
        Validator::Validator();
    }
    
    function setErrorMsg($error = '')
	{
		$this->error = $error;
	}  
    
	function getErrorMsg()
	{
		return $this->error;
	}
 
    function validate()
    {
        if (!preg_match('/^[0-9]{4,4}\-[0-9]{1,2}\-[0-9]{1,2}+$/', $this->date))
        {
            $this->setError($this->getErrorMsg());
        }        
    }
}
?>

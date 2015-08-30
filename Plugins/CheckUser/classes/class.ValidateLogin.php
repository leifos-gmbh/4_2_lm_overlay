<?php
class ValidateLogin extends Validator
{
   	var $login;
   	var $error;
 
    function ValidateLogin($login, $error)
    {
        $this->login = $login;
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
        if (!ereg("^[A-Za-z0-9_\.\+\*\@!\$\%\~\-]+$", $this->login))
        {
            $this->setError($this->getErrorMsg());
        }        
    }
}
?>

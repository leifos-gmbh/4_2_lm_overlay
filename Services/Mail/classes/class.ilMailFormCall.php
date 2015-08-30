<?php
class ilMailFormCall
{
    const REFERER_KEY = 'r';
	const SIGNATURE_KEY = 'sig';

    public static function _getLinkTarget($gui, $cmd, $gui_params = array(), $mail_params = array())
    {       
        $mparams = '';
        foreach($mail_params as $key => $value)
        {
            $mparams .= '&amp;'.$key.'='.$value;
        }

        if(is_object($gui))
        {
            global $ilCtrl;
            $ilCtrlTmp = clone $ilCtrl;
            foreach($gui_params as $key => $value)
            {
                $ilCtrlTmp->setParameter($gui, $key, $value);
            }
            $referer = $ilCtrlTmp->getLinkTarget($gui, $cmd, '', false, false);
        }
        else if(is_string($gui))
        {
            $referer = $gui;
        }

        $referer = '&amp;'.self::REFERER_KEY.'='.rawurlencode(base64_encode($referer));

        return 'ilias.php?baseClass=ilMailGUI'.$mparams.$referer;
    }

    public static function _getRedirectTarget($gui, $cmd, $gui_params = array(), $mail_params = array())
    {
        $mparams = '';
        foreach($mail_params as $key => $value)
        {
            $mparams .= '&'.$key.'='.$value;
        }

        if(is_object($gui))
        {
            global $ilCtrl;
            $ilCtrlTmp = clone $ilCtrl;
            foreach($gui_params as $key => $value)
            {
                $ilCtrlTmp->setParameter($gui, $key, $value);
            }
            $referer = $ilCtrlTmp->getLinkTarget($gui, $cmd, '', false, false);
        }
        else if(is_string($gui))
        {
            $referer = $gui;
        }

        $referer = '&'.self::REFERER_KEY.'='.rawurlencode(base64_encode($referer));

        return 'ilias.php?baseClass=ilMailGUI'.$referer.$mparams;
    }

    public static function _storeReferer($request_params)
    {
        if(isset($request_params[self::REFERER_KEY]))
		{
            $_SESSION[self::REFERER_KEY] = base64_decode(rawurldecode($request_params[self::REFERER_KEY]));
			$_SESSION[self::SIGNATURE_KEY] = base64_decode(rawurldecode($request_params[self::SIGNATURE_KEY]));
		}
        else
		{
            unset($_SESSION[self::REFERER_KEY]);
			unset($_SESSION[self::SIGNATURE_KEY]);
		}
    }
	
	/**
	 * Get preset signature
	 * @return string signature
	 */
	public static function _getSignature()
	{
		$sig = $_SESSION[self::SIGNATURE_KEY];
		
		unset($_SESSION[self::SIGNATURE_KEY]);
		
		return $sig;
	}

    public static function _getRefererRedirectUrl()
    {
        $url = $_SESSION[self::REFERER_KEY];

		if(strlen($url))
		{
			$parts = parse_url($url);
			if(isset($parts['query']) && strlen($parts['query']))
			{
				$url .= '&returned_from_mail=1';
			}
			else
			{
				$url .= '?returned_from_mail=1';
			}
		}

        unset($_SESSION[self::REFERER_KEY]);

        return $url;
    }

    public static function _isRefererStored()
    {
        return isset($_SESSION[self::REFERER_KEY]) && strlen($_SESSION[self::REFERER_KEY]) ? true : false;
    }
}
?>

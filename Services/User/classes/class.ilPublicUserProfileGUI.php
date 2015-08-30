<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * GUI class for public user profile presentation.
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 *
 * @ilCtrl_Calls ilPublicUserProfileGUI: ilObjPortfolioGUI
 *
 * @ingroup ServicesUser
 */
class ilPublicUserProfileGUI
{
	protected $userid; // [int]
	protected $portfolioid; // [int]
	protected $backurl; // [string]
	protected $additional; // [string] used in forum
	protected $embedded; // [bool] used in portfolio
	protected $custom_prefs; // [array] used in portfolio
	
	/**
	* Constructor
	*
	* @param	int		User ID.
	*/
	function __construct($a_user_id = 0)
	{
		global $ilCtrl, $lng;
		
		if($a_user_id)
		{
			$this->setUserId($a_user_id);
		}
		else
		{
			$this->setUserId((int)$_GET["user_id"]);	
		}
		
		$ilCtrl->saveParameter($this, array("user_id","back_url", "user"));
		if ($_GET["back_url"] != "")
		{
			$this->setBackUrl($_GET["back_url"]);
		}		
		
		$lng->loadLanguageModule("user");
	}
	
	/**
	* Set User ID.
	*
	* @param	int	$a_userid	User ID
	*/
	function setUserId($a_userid)
	{
		$this->userid = $a_userid;
	}

	/**
	* Get User ID.
	*
	* @return	int	User ID
	*/
	function getUserId()
	{
		return $this->userid;
	}

	/**
	* Set Additonal Information.
	*
	* @param	array	$a_additional	Additonal Information
	*/
	function setAdditional($a_additional)
	{
		$this->additional = $a_additional;
	}

	/**
	* Get Additonal Information.
	*
	* @return	array	Additonal Information
	*/
	function getAdditional()
	{
		return $this->additional;
	}

	/**
	* Set Back Link URL.
	*
	* @param	string	$a_backurl	Back Link URL
	*/
	function setBackUrl($a_backurl)
	{
		global $ilCtrl;
		
		// we only allow relative links 
		$parts = parse_url($a_backurl);
		if($parts["host"])
		{
			$a_backurl = "#";
		}
		
		$this->backurl = $a_backurl;
		$ilCtrl->setParameter($this, "back_url", rawurlencode($a_backurl));
	}

	/**
	* Get Back Link URL.
	*
	* @return	string	Back Link URL
	*/
	function getBackUrl()
	{
		return $this->backurl;
	}

	/**
	 * Set custom preferences for public profile fields
	 *
	 * @param array $a_prefs 
	 */
	function setCustomPrefs(array $a_prefs)
	{
		$this->custom_prefs = $a_prefs;
	}

	/**
	 * Get user preference for public profile
	 *
	 * Will use original or custom preferences
	 *
	 * @param ilObjUser $a_user
	 * @param string $a_id
	 * @return string
	 */
	protected function getPublicPref(ilObjUser $a_user, $a_id)
	{
		if(!$this->custom_prefs)
		{
			return $a_user->getPref($a_id);
		}
		else
		{
			return $this->custom_prefs[$a_id];
		}
	}
	
	function setEmbedded($a_value, $a_offline = false)
	{
		$this->embedded = (bool)$a_value;
		$this->offline = (bool)$a_offline;
	}
	
	/**
	* Execute Command
	*/
	function executeCommand()
	{
		global $ilCtrl, $tpl;
		
		if(!self::validateUser($this->getUserId()))
		{
			return;
		}
		
		$next_class = $ilCtrl->getNextClass($this);	
		$cmd = $ilCtrl->getCmd();
		
		$tpl->getStandardTemplate();
		
		switch($next_class)
		{	
			case "ilobjportfoliogui":								
				$portfolio_id = $this->getProfilePortfolio();
				if($portfolio_id)
				{
					include_once('Services/PermanentLink/classes/class.ilPermanentLinkGUI.php');
					$plink = new ilPermanentLinkGUI("usr", $this->getUserId());
					$plink = $plink->getHTML();		
					
					include_once "Services/Portfolio/classes/class.ilObjPortfolioGUI.php";
					$gui = new ilObjPortfolioGUI();					
					$gui->initPortfolioObject($portfolio_id);		
					$gui->setAdditional($this->getAdditional());
					$gui->setPermaLink($plink);
					$ilCtrl->forwardCommand($gui);	
					break;
				}							
			
			default:
				$ret = $this->$cmd();
				$tpl->setContent($ret);
				
				// only for direct links
				if ($_GET["baseClass"] == "ilPublicUserProfileGUI")
				{
					$tpl->show();
				}
				break;
		}
	}
	
	/**
	 * View. This one is called e.g. through the goto script
	 */
	function view()
	{
		return $this->getHTML();			
	}

	/**
	 * Show user page
	 */
	function  getHTML()
	{
		global $ilCtrl, $ilSetting;
		
		if($this->embedded)
		{
			return $this->getEmbeddable();
		}
		
		if($this->getProfilePortfolio())
		{			
			$ilCtrl->redirectByClass("ilobjportfoliogui", "preview");
		}
		else
		{
			$this->renderTitle();
			
			// Check from Database if value
			// of public_profile = "y" show user infomation
			$user = new ilObjUser($this->getUserId());
			if ($user->getPref("public_profile") != "y" &&
				($user->getPref("public_profile") != "g" || !$ilSetting->get('enable_global_profiles')) &&
				!$this->custom_prefs)
			{
				return;
			}		
			
			return $this->getEmbeddable();	
		}		
	}
	
	/**
	 * get public profile html code
	 * 
	 * Used in Personal Profile (as preview) and Portfolio (as page block)
	 */
	function getEmbeddable()
	{
		global $ilSetting, $lng, $ilCtrl, $lng, $ilSetting, $ilTabs, $ilUser;

		// get user object
		if (!ilObject::_exists($this->getUserId()))
		{
			return "";
		}
		$user = new ilObjUser($this->getUserId());
		
		$tpl = new ilTemplate("tpl.usr_public_profile.html", true, true,
			"Services/User");
		
		$tpl->setVariable("ROWCOL1", "tblrow1");
		$tpl->setVariable("ROWCOL2", "tblrow2");

		if(!$this->offline && $ilUser->getId() != ANONYMOUS_USER_ID)
		{
			$tpl->setCurrentBlock("mail");
			$tpl->setVariable("TXT_MAIL", $lng->txt("send_mail"));
			require_once 'Services/Mail/classes/class.ilMailFormCall.php';
			$tpl->setVariable('HREF_MAIL', ilMailFormCall::_getLinkTarget(basename($_SERVER['REQUEST_URI']), '', array(), array('type' => 'new', 'rcp_to' => urlencode($user->getLogin()))));
			$tpl->parseCurrentBlock();			
		}
		
		$first_name = "";
		if($this->getPublicPref($user, "public_title") == "y")
		{
			$first_name .= $user->getUTitle()." ";
		}
		$first_name .= $user->getFirstName();

		$tpl->setVariable("TXT_NAME", $lng->txt("name"));
		$tpl->setVariable("FIRSTNAME", $first_name);
		$tpl->setVariable("LASTNAME", $user->getLastName());
		
		if(!$this->offline)
		{
			// vcard
			$tpl->setCurrentBlock("vcard");
			$tpl->setVariable("TXT_VCARD", $lng->txt("vcard"));
			$tpl->setVariable("TXT_DOWNLOAD_VCARD", $lng->txt("vcard_download"));
			$ilCtrl->setParameter($this, "user", $this->getUserId());
			$tpl->setVariable("HREF_VCARD", $ilCtrl->getLinkTarget($this, "deliverVCard"));
			//$tpl->setVariable("IMG_VCARD", ilUtil::getImagePath("vcard.png"));

			/*
			// link to global profile
			if ($user->prefs["public_profile"] == "g" && $ilSetting->get('enable_global_profiles'))
			{
				$tpl->setCurrentBlock("link");
				$tpl->setVariable("TXT_LINK", $lng->txt("usr_link_to_profile"));
				include_once("./classes/class.ilLink.php");
				$tpl->setVariable("HREF_LINK",
					ilLink::_getStaticLink($user->getId(), "usr"));
				$tpl->parseCurrentBlock();
			}			 
			*/
		}
		
		$webspace_dir = ilUtil::getWebspaceDir("user");
		$check_dir = ilUtil::getWebspaceDir();
		$imagefile = $webspace_dir."/usr_images/".$user->getPref("profile_image")."?dummy=".rand(1,999999);
		$check_file = $check_dir."/usr_images/".$user->getPref("profile_image");

		if (!@is_file($check_file))
		{
			$imagefile = $check_file =
				ilObjUser::_getPersonalPicturePath($user->getId(), "small", false, true);
		}

		if ($this->getPublicPref($user, "public_upload")=="y" && $imagefile != "")
		{
			//Getting the flexible path of image form ini file
			//$webspace_dir = ilUtil::getWebspaceDir("output");
			$tpl->setCurrentBlock("image");
			$tpl->setVariable("TXT_IMAGE",$lng->txt("image"));
			$tpl->setVariable("IMAGE_PATH", $imagefile);
			$tpl->setVariable("IMAGE_ALT", $lng->txt("personal_picture"));
			$tpl->parseCurrentBlock();
		}
		
		// address
		if ($this->getPublicPref($user, "public_street") == "y" || 
			$this->getPublicPref($user, "public_zipcode") == "y" ||
			$this->getPublicPref($user, "public_city") == "y" ||
			$this->getPublicPref($user, "public_countr") == "y")
		{
			$tpl->setCurrentBlock("address");
			$tpl->setVariable("TXT_ADDRESS", $lng->txt("address"));
			$val_arr = array ("getStreet" => "street",
			"getZipcode" => "zipcode", "getCity" => "city", "getCountry" => "country", "getSelectedCountry" => "sel_country");
			foreach ($val_arr as $key => $value)
			{
				// if value "y" show information
				if ($this->getPublicPref($user, "public_".$value) == "y")
				{
					if ($user->$key() != "")
					{
						if ($value == "sel_country")
						{
							$lng->loadLanguageModule("meta");
							$tpl->setVariable("COUNTRY",
								$lng->txt("meta_c_".$user->$key()));
						}
						else
						{
							$tpl->setVariable(strtoupper($value), $user->$key());
						}
					}
				}
			}
			$tpl->parseCurrentBlock();
		}

		// institution / department
		if ($this->getPublicPref($user, "public_institution") == "y" ||
			$this->getPublicPref($user, "public_department") == "y")
		{
			$tpl->setCurrentBlock("inst_dep");
			$sep = "";
			if ($this->getPublicPref($user, "public_institution") == "y")
			{
				$h = $lng->txt("institution");
				$v = $user->getInstitution();
				$sep = " / ";
			}
			if ($this->getPublicPref($user, "public_department") == "y")
			{
				$h.= $sep.$lng->txt("department");
				$v.= $sep.$user->getDepartment();
			}
			$tpl->setVariable("TXT_INST_DEP", $h);
			$tpl->setVariable("INST_DEP", $v);
			$tpl->parseCurrentBlock();
		}

		// contact
		$val_arr = array(
			"getPhoneOffice" => "phone_office", "getPhoneHome" => "phone_home",
			"getPhoneMobile" => "phone_mobile", "getFax" => "fax", "getEmail" => "email");
		$v = $sep = "";
		foreach ($val_arr as $key => $value)
		{
			// if value "y" show information
			if ($this->getPublicPref($user, "public_".$value) == "y")
			{
				$v.= $sep.$lng->txt($value).": ".$user->$key();
				$sep = "<br />";
			}
		}
		if ($ilSetting->get("usr_settings_hide_instant_messengers") != 1)
		{
			$im_arr = array("icq","yahoo","msn","aim","skype","jabber","voip");
			
			foreach ($im_arr as $im_name)
			{
				if ($im_id = $user->getInstantMessengerId($im_name))
				{
					if ($this->getPublicPref($user, "public_im_".$im_name) != "n")
					{
						$v.= $sep.$lng->txt('im_'.$im_name).": ".$im_id;
						$sep = "<br />";
					}
				}
			}
		}
		if ($v != "")
		{
			$tpl->parseCurrentBlock("contact");
			$tpl->setVariable("TXT_CONTACT", $lng->txt("contact"));
			$tpl->setVariable("CONTACT", $v);
			$tpl->parseCurrentBlock();
		}

		
		$val_arr = array(
			"getHobby" => "hobby", "getMatriculation" => "matriculation", "getClientIP" => "client_ip");
			
		foreach ($val_arr as $key => $value)
		{
			// if value "y" show information
			if ($this->getPublicPref($user, "public_".$value) == "y")
			{
				$tpl->setCurrentBlock("profile_data");
				$tpl->setVariable("TXT_DATA", $lng->txt($value));
				$tpl->setVariable("DATA", $user->$key());
				$tpl->parseCurrentBlock();
			}
		}
		
		// delicious row
		//$d_set = new ilSetting("delicious");
		if ($this->getPublicPref($user, "public_delicious") == "y")
		{
			$tpl->setCurrentBlock("delicious_row");
			$tpl->setVariable("TXT_DELICIOUS", $lng->txt("delicious"));
			$tpl->setVariable("TXT_DEL_ICON", $lng->txt("delicious"));
			$tpl->setVariable("SRC_DEL_ICON", ilUtil::getImagePath("icon_delicious.gif"));
			$tpl->setVariable("DEL_ACCOUNT", $user->getDelicious());
			$tpl->parseCurrentBlock();
		}
		
		// map
		include_once("./Services/GoogleMaps/classes/class.ilGoogleMapUtil.php");
		if (ilGoogleMapUtil::isActivated() && 
			$this->getPublicPref($user, "public_location") == "y" && 
			$user->getLatitude() != "")
		{
			$tpl->setVariable("TXT_LOCATION", $lng->txt("location"));

			include_once("./Services/GoogleMaps/classes/class.ilGoogleMapGUI.php");
			$map_gui = new ilGoogleMapGUI();
			
			$map_gui->setMapId("user_map");
			$map_gui->setWidth("350px");
			$map_gui->setHeight("230px");
			$map_gui->setLatitude($user->getLatitude());
			$map_gui->setLongitude($user->getLongitude());
			$map_gui->setZoom($user->getLocationZoom());
			$map_gui->setEnableNavigationControl(true);
			$map_gui->addUserMarker($user->getId());
			
			$tpl->setVariable("MAP_CONTENT", $map_gui->getHTML());
		}
		
		// additional defined user data fields
		include_once './Services/User/classes/class.ilUserDefinedFields.php';
		$this->user_defined_fields =& ilUserDefinedFields::_getInstance();
		$user_defined_data = $user->getUserDefinedData();
		foreach($this->user_defined_fields->getVisibleDefinitions() as $field_id => $definition)
		{
			// public setting
			if ($this->getPublicPref($user, "public_udf_".$definition["field_id"]) == "y")
			{
				if ($user_defined_data["f_".$definition["field_id"]] != "")
				{
					$tpl->setCurrentBlock("udf_data");
					$tpl->setVariable("TXT_UDF_DATA", $definition["field_name"]);
					$tpl->setVariable("UDF_DATA", $user_defined_data["f_".$definition["field_id"]]);
					$tpl->parseCurrentBlock();
				}
			}
		}
		
		// additional information
		$additional = $this->getAdditional();
		if (is_array($additional))
		{
			foreach($additional as $key => $val)
			{
				$tpl->setCurrentBlock("profile_data");
				$tpl->setVariable("TXT_DATA", $key);
				$tpl->setVariable("DATA", $val);
				$tpl->parseCurrentBlock();
			}
		}

		return $tpl->get();
	}
	
	/**
	* Deliver vcard information.
	*/
	function deliverVCard()
	{
		// get user object
		if (!ilObject::_exists($this->getUserId()))
		{
			return "";
		}
		$user = new ilObjUser($this->getUserId());

		require_once "./Services/User/classes/class.ilvCard.php";
		$vcard = new ilvCard();

		// ilsharedresourceGUI: embedded in shared portfolio
		if ($user->getPref("public_profile") != "y" &&
			$user->getPref("public_profile") != "g" &&
			$_GET["baseClass"] != "ilsharedresourceGUI")
		{
			return;
		}
		
		$vcard->setName($user->getLastName(), $user->getFirstName(), "", $user->getUTitle());
		$vcard->setNickname($user->getLogin());
		
		$webspace_dir = ilUtil::getWebspaceDir("output");
		$imagefile = $webspace_dir."/usr_images/".$user->getPref("profile_image");
		if ($user->getPref("public_upload")=="y" && @is_file($imagefile))
		{
			$fh = fopen($imagefile, "r");
			if ($fh)
			{
				$image = fread($fh, filesize($imagefile));
				fclose($fh);
				require_once "./Services/MediaObjects/classes/class.ilObjMediaObject.php";
				$mimetype = ilObjMediaObject::getMimeType($imagefile);
				if (preg_match("/^image/", $mimetype))
				{
					$type = $mimetype;
				}
				$vcard->setPhoto($image, $type);
			}
		}

		$val_arr = array("getInstitution" => "institution", "getDepartment" => "department",
			"getStreet" => "street",
			"getZipcode" => "zipcode", "getCity" => "city", "getCountry" => "country",
			"getPhoneOffice" => "phone_office", "getPhoneHome" => "phone_home",
			"getPhoneMobile" => "phone_mobile", "getFax" => "fax", "getEmail" => "email",
			"getHobby" => "hobby", "getMatriculation" => "matriculation", "getClientIP" => "client_ip");

		$org = array();
		$adr = array();
		foreach ($val_arr as $key => $value)
		{
			// if value "y" show information
			if ($user->getPref("public_".$value) == "y")
			{
				switch ($value)
				{
					case "institution":
						$org[0] = $user->$key();
						break;
					case "department":
						$org[1] = $user->$key();
						break;
					case "street":
						$adr[2] = $user->$key();
						break;
					case "zipcode":
						$adr[5] = $user->$key();
						break;
					case "city":
						$adr[3] = $user->$key();
						break;
					case "country":
						$adr[6] = $user->$key();
						break;
					case "phone_office":
						$vcard->setPhone($user->$key(), TEL_TYPE_WORK);
						break;
					case "phone_home":
						$vcard->setPhone($user->$key(), TEL_TYPE_HOME);
						break;
					case "phone_mobile":
						$vcard->setPhone($user->$key(), TEL_TYPE_CELL);
						break;
					case "fax":
						$vcard->setPhone($user->$key(), TEL_TYPE_FAX);
						break;
					case "email":
						$vcard->setEmail($user->$key());
						break;
					case "hobby":
						$vcard->setNote($user->$key());
						break;
				}
			}
		}

		if (count($org))
		{
			$vcard->setOrganization(join(";", $org));
		}
		if (count($adr))
		{
			$vcard->setAddress($adr[0], $adr[1], $adr[2], $adr[3], $adr[4], $adr[5], $adr[6]);
		}
		
		ilUtil::deliverData(utf8_decode($vcard->buildVCard()), $vcard->getFilename(), $vcard->getMimetype());
	}
	
	/**
	 * Check if given user id is valid
	 * 
	 * @return bool
	 */
	protected static function validateUser($a_user_id)
	{
		global $ilUser;
		
		if (ilObject::_lookupType($a_user_id) != "usr")
		{
			return false;
		}
		$user = new ilObjUser($a_user_id);
		if ($ilUser->getId() == ANONYMOUS_USER_ID && $user->getPref("public_profile") != "g")
		{
			return false;
		}
		return true;
	}
	
	function renderTitle()
	{
		global $tpl, $ilTabs, $lng;
		
		$tpl->resetHeaderBlock();
		
		include_once("./Services/User/classes/class.ilUserUtil.php");
		$tpl->setTitle(ilUserUtil::getNamePresentation($this->getUserId()));
		$tpl->setTitleIcon(ilObjUser::_getPersonalPicturePath($this->getUserId(), "xxsmall"));
		
		$back = ($this->getBackUrl() != "")
			? $this->getBackUrl()
			: $_GET["back_url"];

		if ($back != "")
		{
			$ilTabs->clearTargets();
			$ilTabs->setBackTarget($lng->txt("back"),
				$back);
		}
	}
	
	/**
	 * Check if current profile portfolio is accessible
	 * 
	 * @return int
	 */
	protected function getProfilePortfolio()
	{
		include_once "Services/Portfolio/classes/class.ilObjPortfolio.php";				
		$portfolio_id = ilObjPortfolio::getDefaultPortfolio($this->getUserId());
		if($portfolio_id)
		{
			include_once('./Services/Portfolio/classes/class.ilPortfolioAccessHandler.php');
			$access_handler = new ilPortfolioAccessHandler();
			if($access_handler->checkAccess("read", "", $portfolio_id))
			{
				return $portfolio_id;
			}
		}			
	}
}

?>
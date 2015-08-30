<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/** @defgroup ServicesInit Services/Init
 */

/**
* ILIAS Initialisation Utility Class
* perform basic setup: init database handler, load configuration file,
* init user authentification & error handler, load object type definitions
*
* @author Alex Killing <alex.killing@gmx.de>
* @author Sascha Hofmann <shofmann@databay.de>

* @version $Id: class.ilInitialisation.php 50230 2014-05-21 15:06:17Z mjansen $
*
* @ingroup ServicesInit
*/
class ilInitialisation
{
	private $return_before_auth = false;
	var $script = "";

	/**
	* Remove unsafe characters from GET
	*/
	function removeUnsafeCharacters()
	{
		// Remove unsafe characters from GET parameters.
		// We do not need this characters in any case, so it is
		// feasible to filter them everytime. POST parameters
		// need attention through ilUtil::stripSlashes() and similar functions)
		if (is_array($_GET))
		{
			foreach($_GET as $k => $v)
			{
				// \r\n used for IMAP MX Injection
				// ' used for SQL Injection
				$_GET[$k] = str_replace(array("\x00", "\n", "\r", "\\", "'", '"', "\x1a"), "", $v);

				// this one is for XSS of any kind
				$_GET[$k] = strip_tags($_GET[$k]);
			}
		}
	}
	
	public function returnBeforeAuth($a_flag = null)
	{
		if(null === $a_flag)
		{
			return $this->return_before_auth;
		}
		
		$this->return_before_auth = $a_flag;
		return $this;
	}

	/**
	 * get common include code files
	*/
	function requireCommonIncludes()
	{
		global $ilBench;

		// get pear
		require_once("include/inc.get_pear.php");
		require_once("include/inc.check_pear.php");

		//include class.util first to start StopWatch
		require_once "./Services/Utilities/classes/class.ilUtil.php";
		require_once "classes/class.ilBenchmark.php";
		$ilBench = new ilBenchmark();
		$GLOBALS['ilBench'] = $ilBench;

		// BEGIN Usability: Measure response time until footer is displayed on form
		// The stop statement is in class.ilTemplate.php function addILIASfooter()
		$ilBench->start("Core", "ElapsedTimeUntilFooter");
		// END Usability: Measure response time until footer is displayed on form

		$ilBench->start("Core", "HeaderInclude");

		// start the StopWatch
		$GLOBALS['t_pagestart'] = ilUtil::StopWatch();

		$ilBench->start("Core", "HeaderInclude_IncludeFiles");
//echo ":".class_exists("HTML_Template_ITX").":";
		// Major PEAR Includes
		require_once "PEAR.php";
		//require_once "DB.php";
		require_once "Auth/Auth.php";

		// HTML_Template_IT support
		// (location changed with 4.3.2 & higher)
/*		@include_once "HTML/ITX.php";		// old implementation
		if (!class_exists("IntegratedTemplateExtension"))
		{
			include_once "HTML/Template/ITX.php";
			include_once "classes/class.ilTemplateHTMLITX.php";
		}
		else
		{
			include_once "classes/class.ilTemplateITX.php";
		}*/
		
		@include_once "HTML/Template/ITX.php";		// new implementation
		if (class_exists("HTML_Template_ITX"))
		{
			include_once "classes/class.ilTemplateHTMLITX.php";
		}
		else
		{
			include_once "HTML/ITX.php";		// old implementation
			include_once "classes/class.ilTemplateITX.php";
		}
		
		require_once "classes/class.ilTemplate.php";

		//include classes and function libraries
		require_once "include/inc.db_session_handler.php";
		require_once "./Services/Database/classes/class.ilDB.php";
		require_once "./Services/AuthShibboleth/classes/class.ilShibboleth.php";
		require_once "classes/class.ilias.php";
		require_once './Services/User/classes/class.ilObjUser.php';
		require_once "classes/class.ilFormat.php";
		require_once "./Services/Calendar/classes/class.ilDatePresentation.php";
		require_once "classes/class.ilSaxParser.php";
		require_once "./Services/Object/classes/class.ilObjectDefinition.php";
		require_once "./Services/Style/classes/class.ilStyleDefinition.php";
		require_once "./Services/Tree/classes/class.ilTree.php";
		require_once "./Services/Language/classes/class.ilLanguage.php";
		require_once "./Services/Logging/classes/class.ilLog.php";
		require_once "classes/class.ilCtrl2.php";
		require_once "./Services/AccessControl/classes/class.ilConditionHandler.php";
		require_once "classes/class.ilBrowser.php";
		require_once "classes/class.ilFrameTargetInfo.php";
		require_once "Services/Navigation/classes/class.ilNavigationHistory.php";
		require_once "Services/Help/classes/class.ilHelp.php";
		require_once "include/inc.ilias_version.php";

		//include role based access control system
		require_once "./Services/AccessControl/classes/class.ilAccessHandler.php";
		require_once "./Services/AccessControl/classes/class.ilRbacAdmin.php";
		require_once "./Services/AccessControl/classes/class.ilRbacSystem.php";
		require_once "./Services/AccessControl/classes/class.ilRbacReview.php";

		// include object_data cache
		require_once "classes/class.ilObjectDataCache.php";
		require_once 'Services/Tracking/classes/class.ilOnlineTracking.php';

		//include LocatorGUI
		require_once "./Services/Locator/classes/class.ilLocatorGUI.php";

		// include error_handling
		require_once "classes/class.ilErrorHandling.php";

		$ilBench->stop("Core", "HeaderInclude_IncludeFiles");
	}
	
	/**
	 * This is a hack for CAS authentication.
	 * Since the phpCAS lib ships with its own compliance functions.
	 * @return 
	 */
	public function includePhp5Compliance()
	{
		// php5 downward complaince to php 4 dom xml and clone method
		if (version_compare(PHP_VERSION,'5','>='))
		{
			if(ilAuthFactory::getContext() != ilAuthFactory::CONTEXT_CAS)
			{
				require_once("include/inc.xml5compliance.php");
			}
			require_once("include/inc.xsl5compliance.php");
			require_once("include/inc.php4compliance.php");
		}
		else
		{
			require_once("include/inc.php5compliance.php");
		}
		
	}
	

	/**
	* This method provides a global instance of class ilIniFile for the
	* ilias.ini.php file in variable $ilIliasIniFile.
	*
	* It initializes a lot of constants accordingly to the settings in
	* the ilias.ini.php file.
	*/
	function initIliasIniFile()
	{
		global $ilIliasIniFile;

		require_once("classes/class.ilIniFile.php");
		$ilIliasIniFile = new ilIniFile("./ilias.ini.php");
		$GLOBALS['ilIliasIniFile'] =& $ilIliasIniFile;
		$ilIliasIniFile->read();

		// initialize constants
		define("ILIAS_DATA_DIR",$ilIliasIniFile->readVariable("clients","datadir"));
		define("ILIAS_WEB_DIR",$ilIliasIniFile->readVariable("clients","path"));
		define("ILIAS_ABSOLUTE_PATH",$ilIliasIniFile->readVariable('server','absolute_path'));

		// logging
		define ("ILIAS_LOG_DIR",$ilIliasIniFile->readVariable("log","path"));
		define ("ILIAS_LOG_FILE",$ilIliasIniFile->readVariable("log","file"));
		define ("ILIAS_LOG_ENABLED",$ilIliasIniFile->readVariable("log","enabled"));
		define ("ILIAS_LOG_LEVEL",$ilIliasIniFile->readVariable("log","level"));

		// read path + command for third party tools from ilias.ini
		define ("PATH_TO_CONVERT",$ilIliasIniFile->readVariable("tools","convert"));
		define ("PATH_TO_ZIP",$ilIliasIniFile->readVariable("tools","zip"));
		define ("PATH_TO_MKISOFS",$ilIliasIniFile->readVariable("tools","mkisofs"));
		define ("PATH_TO_UNZIP",$ilIliasIniFile->readVariable("tools","unzip"));
		define ("PATH_TO_JAVA",$ilIliasIniFile->readVariable("tools","java"));
		define ("PATH_TO_HTMLDOC",$ilIliasIniFile->readVariable("tools","htmldoc"));
		define ("URL_TO_LATEX",$ilIliasIniFile->readVariable("tools","latex"));
		define ("PATH_TO_FOP",$ilIliasIniFile->readVariable("tools","fop"));

		// read virus scanner settings
		switch ($ilIliasIniFile->readVariable("tools", "vscantype"))
		{
			case "sophos":
				define("IL_VIRUS_SCANNER", "Sophos");
				define("IL_VIRUS_SCAN_COMMAND", $ilIliasIniFile->readVariable("tools", "scancommand"));
				define("IL_VIRUS_CLEAN_COMMAND", $ilIliasIniFile->readVariable("tools", "cleancommand"));
				break;

			case "antivir":
				define("IL_VIRUS_SCANNER", "AntiVir");
				define("IL_VIRUS_SCAN_COMMAND", $ilIliasIniFile->readVariable("tools", "scancommand"));
				define("IL_VIRUS_CLEAN_COMMAND", $ilIliasIniFile->readVariable("tools", "cleancommand"));
				break;

			case "clamav":
				define("IL_VIRUS_SCANNER", "ClamAV");
				define("IL_VIRUS_SCAN_COMMAND", $ilIliasIniFile->readVariable("tools", "scancommand"));
				define("IL_VIRUS_CLEAN_COMMAND", $ilIliasIniFile->readVariable("tools", "cleancommand"));
				break;

			default:
				define("IL_VIRUS_SCANNER", "None");
				break;
		}
		
		$tz = $ilIliasIniFile->readVariable("server","timezone");
		if ($tz != "")
		{
			if (function_exists('date_default_timezone_set'))
			{
				date_default_timezone_set($tz);
			}
		}
		define ("IL_TIMEZONE", $ilIliasIniFile->readVariable("server","timezone"));
		
		//$this->buildHTTPPath();
	}

	/**
	* builds http path
	*
	* this is also used by other classes now,
	* e.g. in ilSoapAuthenticationCAS.php
	*/
	function buildHTTPPath()
	{
		include_once 'classes/class.ilHTTPS.php';
		$https = new ilHTTPS();

	    if($https->isDetected())
		{
			$protocol = 'https://';
		}
		else
		{
			$protocol = 'http://';
		}
		$host = $_SERVER['HTTP_HOST'];

		$rq_uri = $_SERVER['REQUEST_URI'];

		// security fix: this failed, if the URI contained "?" and following "/"
		// -> we remove everything after "?"
		if (is_int($pos = strpos($rq_uri, "?")))
		{
			$rq_uri = substr($rq_uri, 0, $pos);
		}

		if(!defined('ILIAS_MODULE'))
		{
			$path = pathinfo($rq_uri);
			if(!$path['extension'])
			{
				$uri = $rq_uri;
			}
			else
			{
				$uri = dirname($rq_uri);
			}
		}
		else
		{
			// if in module remove module name from HTTP_PATH
			$path = dirname($rq_uri);

			// dirname cuts the last directory from a directory path e.g content/classes return content

			$module = ilUtil::removeTrailingPathSeparators(ILIAS_MODULE);

			$dirs = explode('/',$module);
			$uri = $path;
			foreach($dirs as $dir)
			{
				$uri = dirname($uri);
			}
		}
		
		return define('ILIAS_HTTP_PATH',ilUtil::removeTrailingPathSeparators($protocol.$host.$uri));
	}


	/**
	* This method determines the current client and sets the
	* constant CLIENT_ID.
	*/
	function determineClient()
	{
		global $ilIliasIniFile;

		// check whether ini file object exists
		if (!is_object($ilIliasIniFile))
		{
			die ("Fatal Error: ilInitialisation::determineClient called without initialisation of ILIAS ini file object.");
		}

		// set to default client if empty
		if ($_GET["client_id"] != "")
		{
			$_GET["client_id"] = ilUtil::stripSlashes($_GET["client_id"]);
			if (!defined("IL_PHPUNIT_TEST"))
			{
				ilUtil::setCookie("ilClientId", $_GET["client_id"]);
			}
		}
		else if (!$_COOKIE["ilClientId"])
		{
			// to do: ilias ini raus nehmen
			$client_id = $ilIliasIniFile->readVariable("clients","default");
			ilUtil::setCookie("ilClientId", $client_id);
//echo "set cookie";
		}
//echo "-".$_COOKIE["ilClientId"]."-";
		if (!defined("IL_PHPUNIT_TEST"))
		{
			define ("CLIENT_ID", $_COOKIE["ilClientId"]);
		}
		else
		{
			define ("CLIENT_ID", $_GET["client_id"]);
		}
	}

	/**
	* This method provides a global instance of class ilIniFile for the
	* client.ini.php file in variable $ilClientIniFile.
	*
	* It initializes a lot of constants accordingly to the settings in
	* the client.ini.php file.
	*
	* Preconditions: ILIAS_WEB_DIR and CLIENT_ID must be set.
	*
	* @return	boolean		true, if no error occured with client init file
	*						otherwise false
	*/
	function initClientIniFile()
	{
		global $ilClientIniFile;

		// check whether ILIAS_WEB_DIR is set.
		if (ILIAS_WEB_DIR == "")
		{
			die ("Fatal Error: ilInitialisation::initClientIniFile called without ILIAS_WEB_DIR.");
		}

		// check whether CLIENT_ID is set.
		if (CLIENT_ID == "")
		{
			die ("Fatal Error: ilInitialisation::initClientIniFile called without CLIENT_ID.");
		}

		$ini_file = "./".ILIAS_WEB_DIR."/".CLIENT_ID."/client.ini.php";

		// get settings from ini file
		require_once("classes/class.ilIniFile.php");
		$ilClientIniFile = new ilIniFile($ini_file);
		$GLOBALS['ilClientIniFile'] =& $ilClientIniFile;
		$ilClientIniFile->read();

		// if no ini-file found switch to setup routine
		if ($ilClientIniFile->ERROR != "")
		{
			return false;
		}

		// set constants
		define ("SESSION_REMINDER_LEADTIME", 30);
		define ("DEBUG",$ilClientIniFile->readVariable("system","DEBUG"));
		define ("DEVMODE",$ilClientIniFile->readVariable("system","DEVMODE"));
		define ("SHOWNOTICES",$ilClientIniFile->readVariable("system","SHOWNOTICES"));
		define ("ROOT_FOLDER_ID",$ilClientIniFile->readVariable('system','ROOT_FOLDER_ID'));
		define ("SYSTEM_FOLDER_ID",$ilClientIniFile->readVariable('system','SYSTEM_FOLDER_ID'));
		define ("ROLE_FOLDER_ID",$ilClientIniFile->readVariable('system','ROLE_FOLDER_ID'));
		define ("MAIL_SETTINGS_ID",$ilClientIniFile->readVariable('system','MAIL_SETTINGS_ID'));

		define ("SYSTEM_MAIL_ADDRESS",$ilClientIniFile->readVariable('system','MAIL_SENT_ADDRESS')); // Change SS
		define ("MAIL_REPLY_WARNING",$ilClientIniFile->readVariable('system','MAIL_REPLY_WARNING')); // Change SS

		define ("MAXLENGTH_OBJ_TITLE",125);#$ilClientIniFile->readVariable('system','MAXLENGTH_OBJ_TITLE'));
		define ("MAXLENGTH_OBJ_DESC",$ilClientIniFile->readVariable('system','MAXLENGTH_OBJ_DESC'));

		define ("CLIENT_DATA_DIR",ILIAS_DATA_DIR."/".CLIENT_ID);
		define ("CLIENT_WEB_DIR",ILIAS_ABSOLUTE_PATH."/".ILIAS_WEB_DIR."/".CLIENT_ID);
		define ("CLIENT_NAME",$ilClientIniFile->readVariable('client','name')); // Change SS

		$val = $ilClientIniFile->readVariable("db","type");
		if ($val == "")
		{
			define ("IL_DB_TYPE", "mysql");
		}
		else
		{
			define ("IL_DB_TYPE", $val);
		}
		return true;
	}

	/**
	* handle maintenance mode
	*/
	function handleMaintenanceMode()
	{
		global $ilClientIniFile;

		if (!$ilClientIniFile->readVariable("client","access"))
		{
			if (is_file("./maintenance.html"))
			{
				ilUtil::redirect("./maintenance.html");
			}
			else
			{
				// to do: include standard template here
				die('<br /><p style="text-align:center;">The server is not '.
					'available due to maintenance. We apologise for any inconvenience.</p>');
			}
		}
	}

	/**
	* initialise database object $ilDB
	*
	*/
	function initDatabase()
	{
		global $ilDB, $ilClientIniFile;

		// build dsn of database connection and connect
		require_once("./Services/Database/classes/class.ilDBWrapperFactory.php");
		$ilDB = ilDBWrapperFactory::getWrapper(IL_DB_TYPE);
		$ilDB->initFromIniFile();
		$ilDB->connect();
		$GLOBALS['ilDB'] = $ilDB;
		
	}

	/**
	* initialise event handler ilAppEventHandler
	*/
	function initEventHandling()
	{
		global $ilAppEventHandler;

		// build dsn of database connection and connect
		require_once("./Services/EventHandling/classes/class.ilAppEventHandler.php");
		$ilAppEventHandler = new ilAppEventHandler();
		$GLOBALS['ilAppEventHandler'] =& $ilAppEventHandler;
	}

	/**
	* set session handler to db
	*/
	function setSessionHandler()
	{
		global $ilErr;

		// set session handler
		if(ini_get('session.save_handler') != 'user')
		{
			ini_set("session.save_handler", "user");
		}
		if (!db_set_save_handler())
		{
			die("Please turn off Safe mode OR set session.save_handler to \"user\" in your php.ini");
		}

	}
	/**
	* set session cookie params for path, domain, etc.
	*/
	function setCookieParams()
	{
		include_once 'Services/Authentication/classes/class.ilAuthFactory.php';
		if(ilAuthFactory::getContext() == ilAuthFactory::CONTEXT_HTTP) 
		{
			$cookie_path = '/';
		}
		elseif ($GLOBALS['COOKIE_PATH'])
		{
			// use a predefined cookie path from WebAccessChecker
	        $cookie_path = $GLOBALS['COOKIE_PATH'];
	    }
		else
		{
			$cookie_path = dirname( $_SERVER['PHP_SELF'] );
		}
		
		/* if ilias is called directly within the docroot $cookie_path
		is set to '/' expecting on servers running under windows..
		here it is set to '\'.
		in both cases a further '/' won't be appended due to the following regex
		*/
		$cookie_path .= (!preg_match("/[\/|\\\\]$/", $cookie_path)) ? "/" : "";
		
		if($cookie_path == "\\") $cookie_path = '/';
		
		$cookie_domain = $_SERVER['SERVER_NAME'];		

		define('IL_COOKIE_EXPIRE',0);
		define('IL_COOKIE_PATH',$cookie_path);
		define('IL_COOKIE_DOMAIN','');
		define('IL_COOKIE_SECURE',false); // Default Value

		// session_set_cookie_params() supports 5th parameter
		// only for php version 5.2.0 and above
		if( version_compare(PHP_VERSION, '5.2.0', '>=') )
		{
			// PHP version >= 5.2.0
			define('IL_COOKIE_HTTPONLY',false); // Default Value
			session_set_cookie_params(
					IL_COOKIE_EXPIRE, IL_COOKIE_PATH, IL_COOKIE_DOMAIN, IL_COOKIE_SECURE, IL_COOKIE_HTTPONLY
			);
		}
		else
		{
			// PHP version < 5.2.0
			session_set_cookie_params(
					IL_COOKIE_EXPIRE, IL_COOKIE_PATH, IL_COOKIE_DOMAIN, IL_COOKIE_SECURE
			);
		}
	}

	/**
	* initialise $ilSettings object and define constants
	*/
	function initSettings()
	{
		global $ilSetting;

		require_once("Services/Administration/classes/class.ilSetting.php");
		$ilSetting = new ilSetting();
		$GLOBALS['ilSetting'] =& $ilSetting;

		// set anonymous user & role id and system role id
		define ("ANONYMOUS_USER_ID", $ilSetting->get("anonymous_user_id"));
		define ("ANONYMOUS_ROLE_ID", $ilSetting->get("anonymous_role_id"));
		define ("SYSTEM_USER_ID", $ilSetting->get("system_user_id"));
		define ("SYSTEM_ROLE_ID", $ilSetting->get("system_role_id"));
		define ("USER_FOLDER_ID", 7);

		// recovery folder
		define ("RECOVERY_FOLDER_ID", $ilSetting->get("recovery_folder_id"));

		// installation id
		define ("IL_INST_ID", $ilSetting->get("inst_id",0));

		// define default suffix replacements
		define ("SUFFIX_REPL_DEFAULT", "php,php3,php4,inc,lang,phtml,htaccess");
		define ("SUFFIX_REPL_ADDITIONAL", $ilSetting->get("suffix_repl_additional"));

		$this->buildHTTPPath();

		// payment setting
		require_once('Services/Payment/classes/class.ilPaymentSettings.php');
		define('IS_PAYMENT_ENABLED', ilPaymentSettings::_isPaymentEnabled());
	}


	/**
	* determine current script and path to main ILIAS directory
	*/
	function determineScriptAndUpDir()
	{
		$this->script = substr(strrchr($_SERVER["PHP_SELF"],"/"),1);
		$dirname = dirname($_SERVER["PHP_SELF"]);
		$ilurl = @parse_url(ILIAS_HTTP_PATH);
		if (!$ilurl["path"])
		{
			$ilurl["path"] = "/";
		}
		$subdir = substr(strstr($dirname,$ilurl["path"]),strlen($ilurl["path"]));
		$updir = "";

		if ($subdir)
		{
			$num_subdirs = substr_count($subdir,"/");

			for ($i=1;$i<=$num_subdirs;$i++)
			{
				$updir .= "../";
			}
		}
		$this->updir = $updir;
	}

	/**
	* provide $styleDefinition object
	*/
	function initStyle()
	{
		global $ilBench, $styleDefinition;

		// load style definitions
		$ilBench->start("Core", "HeaderInclude_getStyleDefinitions");
		$styleDefinition = new ilStyleDefinition();
		$GLOBALS['styleDefinition'] =& $styleDefinition;

		// add user interface hook for style initialisation
		global $ilPluginAdmin;
		$pl_names = $ilPluginAdmin->getActivePluginsForSlot(IL_COMP_SERVICE, "UIComponent", "uihk");
		foreach ($pl_names as $pl)
		{
			$ui_plugin = ilPluginAdmin::getPluginObject(IL_COMP_SERVICE, "UIComponent", "uihk", $pl);
			$gui_class = $ui_plugin->getUIClassInstance();
			$resp = $gui_class->modifyGUI("Services/Init", "init_style", array("styleDefinition" => $styleDefinition));
		}

		$styleDefinition->startParsing();
		$ilBench->stop("Core", "HeaderInclude_getStyleDefinitions");
	}


	/**
	* set skin and style via $_GET parameters "skin" and "style"
	*/
	function handleStyle()
	{
		global $styleDefinition;

		if (isset($_GET['skin']) && isset($_GET['style']))
		{
			include_once("./Services/Style/classes/class.ilObjStyleSettings.php");
			if ($styleDefinition->styleExists($_GET['skin'], $_GET['style']) &&
				ilObjStyleSettings::_lookupActivatedStyle($_GET['skin'], $_GET['style']))
			{
				$_SESSION['skin'] = $_GET['skin'];
				$_SESSION['style'] = $_GET['style'];
			}
		}
		if (isset($_SESSION['skin']) && isset($_SESSION['style']))
		{
			include_once("./Services/Style/classes/class.ilObjStyleSettings.php");
			if ($styleDefinition->styleExists($_SESSION['skin'], $_SESSION['style']) &&
				ilObjStyleSettings::_lookupActivatedStyle($_SESSION['skin'], $_SESSION['style']))
			{
				$ilias->account->skin = $_SESSION['skin'];
				$ilias->account->prefs['style'] = $_SESSION['style'];
			}
		}
	}

	function initUserAccount()
	{
		global $ilUser, $ilLog, $ilAuth;

		//get user id
		if (empty($_SESSION["AccountId"]))
		{
			$uid = $ilUser->checkUserId();
			$_SESSION["AccountId"] = $uid;
			if ($uid > 0)
			{
				$ilUser->setId($uid);
			}
			// assigned roles are stored in $_SESSION["RoleId"]
			// DISABLED smeyer 20070510
			#$rbacreview = new ilRbacReview();
			#$GLOBALS['rbacreview'] =& $rbacreview;
			#$_SESSION["RoleId"] = $rbacreview->assignedRoles($_SESSION["AccountId"]);
		} // TODO: do we need 'else' here?
		else
		{
			// init user
			$ilUser->setId($_SESSION["AccountId"]);
		}

		// load account data of current user
		$ilUser->read();
		// endriss-patch: begin
		setcookie('ilUserId',$ilUser->getId());
		// endriss-patch: end
	}
	
	/**
	* Init Locale
	*/
	function initLocale()
	{
		global $ilSetting;
		
		if (trim($ilSetting->get("locale") != ""))
		{
			$larr = explode(",", trim($ilSetting->get("locale")));
			$ls = array();
			$first = $larr[0];
			foreach ($larr as $l)
			{
				if (trim($l) != "")
				{
					$ls[] = $l;
				}
			}
			if (count($ls) > 0)
			{
				setlocale(LC_ALL, $ls);
				if (class_exists("Collator"))
				{
					$GLOBALS["ilCollator"] = new Collator($first);
				}
			}
		}
	}
	

	function checkUserClientIP()
	{
		global $ilUser, $ilLog, $ilAuth, $ilias;

		// check client ip
		$clientip = $ilUser->getClientIP();
		if (trim($clientip) != "")
		{
			$clientip = preg_replace("/[^0-9.?*,:]+/","",$clientip);
			$clientip = str_replace(".","\\.",$clientip);
			$clientip = str_replace(Array("?","*",","), Array("[0-9]","[0-9]*","|"), $clientip);
			if (!preg_match("/^".$clientip."$/", $_SERVER["REMOTE_ADDR"]))
			{
				$ilLog ->logError(1,
				$ilias->account->getLogin().":".$_SERVER["REMOTE_ADDR"].":".$message);
				$ilAuth->logout();
				@session_destroy();
				ilUtil::redirect("login.php?wrong_ip=true");
			}
		}
	}

	function checkUserAgreement()
	{
		global $ilUser, $ilAuth;

		// are we currently in user agreement acceptance?
		$in_user_agreement = false;
		if (strtolower($_GET["cmdClass"]) == "ilstartupgui" &&
			(strtolower($_GET["cmd"]) == "getacceptance" ||
			(is_array($_POST["cmd"]) &&
			key($_POST["cmd"]) == "getAcceptance")))
		{
			$in_user_agreement = true;
		}

		// check wether user has accepted the user agreement
		//	echo "-".$script;
		if (!$ilUser->hasAcceptedUserAgreement() &&
			$ilAuth->getAuth() &&
			!$in_user_agreement &&
			$ilUser->getId() != ANONYMOUS_USER_ID &&
			$ilUser->checkTimeLimit())
		{
			if(!defined('IL_CERT_SSO') && $ilAuth->supportsRedirects())
			{
				ilUtil::redirect("ilias.php?baseClass=ilStartUpGUI&cmdClass=ilstartupgui&target=".$_GET["target"]."&cmd=getAcceptance");
			}
		}
	}


	/**
	* go to public section
	*/
	function goToPublicSection()
	{
		global $ilAuth;

		// logout and end previous session
		$ilAuth->logout();
		session_unset();
		session_destroy();

		// new session and login as anonymous
		$this->setSessionHandler();
		session_start();
		$_POST["username"] = "anonymous";
		$_POST["password"] = "anonymous";
		ilAuthUtils::_initAuth();
		
		$oldSid = session_id();
		
		$ilAuth->start();
		if(IS_PAYMENT_ENABLED)
		{
			$newSid = session_id();
			include_once './Services/Payment/classes/class.ilPaymentShoppingCart.php';
			ilPaymentShoppingCart::_migrateShoppingCart($oldSid, $newSid);
		}
		if (ANONYMOUS_USER_ID == "")
		{
			die ("Public Section enabled, but no Anonymous user found.");
		}
		if (!$ilAuth->getAuth())
		{
			die("ANONYMOUS user with the object_id ".ANONYMOUS_USER_ID." not found!");
		}

		// if target given, try to go there
		if ($_GET["target"] != "")
		{
			$this->initUserAccount();

			// target is accessible -> goto target
			include_once("Services/Init/classes/class.ilStartUpGUI.php");
			if	(ilStartUpGUI::_checkGoto($_GET["target"]))
			{
				// Disabled: GET parameter is kept, since no redirect. smeyer
				// additional parameter capturing for survey access codes
				/*
				$survey_parameter = "";
				if (array_key_exists("accesscode", $_GET))
				{
					$survey_parameter = "&accesscode=" . $_GET["accesscode"];
				}
				*/
				// Disabled redirect for public section
				return true;
				#ilUtil::redirect(ILIAS_HTTP_PATH.
				#	"/goto.php?target=".$_GET["target"].$survey_parameter);
			}
			else	// target is not accessible -> login
			{
				$this->goToLogin($_GET['auth_stat']);
			}
		}

		$_GET["ref_id"] = ROOT_FOLDER_ID;

		$_GET["cmd"] = "frameset";
		$jump_script = "repository.php";

		$script = $this->updir.$jump_script."?reloadpublic=1&cmd=".$_GET["cmd"]."&ref_id=".$_GET["ref_id"];

		// todo do it better, if JS disabled
		//echo "<script language=\"Javascript\">\ntop.location.href = \"".$script."\";\n</script>\n";
		echo "<script language=\"Javascript\">\ntop.location.href = \"".$script."\";\n</script>\n".
			'Please click <a target="_top" href="'.$script.'">here</a> if you are not redirected automatically.';
		exit;
	}


	/**
	* go to login
	*/
	function goToLogin($a_auth_stat = "")
	{
		global $PHP_SELF;

		session_unset();
		session_destroy();

		$add = "";
		if ($_GET["soap_pw"] != "")
		{
			$add = "&soap_pw=".$_GET["soap_pw"]."&ext_uid=".$_GET["ext_uid"];
		}

		$script = $this->updir."login.php?target=".$_GET["target"]."&client_id=".$_COOKIE["ilClientId"].
			"&auth_stat=".$a_auth_stat.$add;

		// todo do it better, if JS disabled
		// + this is, when session "ends", so
		// we should try to prevent some information about current
		// location
		//
		// check whether we are currently doing a goto call
		if (is_int(strpos($PHP_SELF, "goto.php")) && $_GET["soap_pw"] == "" &&
			$_GET["reloadpublic"] != "1")
		{
			$script = $this->updir."goto.php?target=".$_GET["target"]."&client_id=".CLIENT_ID.
				"&reloadpublic=1";
		}

		echo "<script language=\"Javascript\">\ntop.location.href = \"".$script."\";\n</script>\n".
			'Please click <a target="_top" href="'.$script.'">here</a> if you are not redirected automatically.';

		exit;

	}

	/**
	* $lng initialisation
	*/
	function initLanguage()
	{
		global $ilBench, $lng, $ilUser, $ilSetting;
		
		//init language
		$ilBench->start("Core", "HeaderInclude_initLanguage");

		if (!isset($_SESSION['lang']))
		{
			if ($_GET["lang"])
			{
				$_GET["lang"] = $_GET["lang"];
			}
			else
			{
				if (is_object($ilUser))
				{
					$_GET["lang"] = $ilUser->getPref("language");
				}
			}
		}

		if (isset($_POST['change_lang_to']) && $_POST['change_lang_to'] != "")
		{
			$_GET['lang'] = ilUtil::stripSlashes($_POST['change_lang_to']);
		}

		// prefer personal setting when coming from login screen
		// Added check for ilUser->getId > 0 because it is 0 when the language is changed and the user agreement should be displayes (Helmut Schottm��ller, 2006-10-14)
		if (is_object($ilUser) && $ilUser->getId() != ANONYMOUS_USER_ID && $ilUser->getId() > 0)
		{
			$_SESSION['lang'] = $ilUser->getPref("language");
		}

		$_SESSION['lang'] = (isset($_GET['lang']) && $_GET['lang']) ? $_GET['lang'] : $_SESSION['lang'];

		// check whether lang selection is valid
		$langs = ilLanguage::getInstalledLanguages();
		if (!in_array($_SESSION['lang'], $langs))
		{
			if (is_object($ilSetting) && $ilSetting->get("language") != "")
			{
				$_SESSION['lang'] = $ilSetting->get("language");
			}
			else
			{
				$_SESSION['lang'] = $langs[0];
			}
		}
		$_GET['lang'] = $_SESSION['lang'];
		
		$lng = new ilLanguage($_SESSION['lang']);
		$GLOBALS['lng'] =& $lng;
		$ilBench->stop("Core", "HeaderInclude_initLanguage");
		
		// TODO: another location
		global $rbacsystem;
		if(is_object($rbacsystem))
		{
			$rbacsystem->initMemberView();
		}

	}

	/**
	* $ilAccess and $rbac... initialisation
	*/
	function initAccessHandling()
	{
		global $ilBench, $rbacsystem, $rbacadmin, $rbacreview;

		$ilBench->start("Core", "HeaderInclude_initRBAC");
		$rbacreview = new ilRbacReview();
		$GLOBALS['rbacreview'] =& $rbacreview;

		$rbacsystem = ilRbacSystem::getInstance();
		$GLOBALS['rbacsystem'] =& $rbacsystem;

		$rbacadmin = new ilRbacAdmin();
		$GLOBALS['rbacadmin'] =& $rbacadmin;

		$ilAccess = new ilAccessHandler();
		$GLOBALS["ilAccess"] =& $ilAccess;
		$ilBench->stop("Core", "HeaderInclude_initRBAC");
	}


	/**
	* ilias initialisation
	* @param string $context this is used for circumvent redirects to the login page if called e.g. by soap
	*/
	function initILIAS($context = "web")
	{
		global $ilDB, $ilUser, $ilLog, $ilErr, $ilClientIniFile, $ilIliasIniFile,
			$ilSetting, $ilias, $https, $ilObjDataCache,
			$ilLog, $objDefinition, $lng, $ilCtrl, $ilBrowser, $ilHelp,
			$ilTabs, $ilMainMenu, $rbacsystem, $ilNavigationHistory;

		// remove unsafe characters
		$this->removeUnsafeCharacters();

		// error reporting
		// remove notices from error reporting
		if (version_compare(PHP_VERSION, '5.4.0', '>='))
		{
			// Prior to PHP 5.4.0 E_ALL does not include E_STRICT.
			// With PHP 5.4.0 and above E_ALL >DOES< include E_STRICT.
			
			error_reporting(((ini_get("error_reporting") & ~E_NOTICE) & ~E_DEPRECATED) & ~E_STRICT);
		}
		elseif (version_compare(PHP_VERSION, '5.3.0', '>='))
		{
			error_reporting((ini_get("error_reporting") & ~E_NOTICE) & ~E_DEPRECATED);
		}
		else
		{
			error_reporting(ini_get('error_reporting') & ~E_NOTICE);
		}

		
		// include common code files
		$this->requireCommonIncludes();
		global $ilBench;

		// set error handler (to do: check preconditions for error handler to work)
		$ilBench->start("Core", "HeaderInclude_GetErrorHandler");
		$ilErr = new ilErrorHandling();
		$GLOBALS['ilErr'] =& $ilErr;
		$ilErr->setErrorHandling(PEAR_ERROR_CALLBACK,array($ilErr,'errorHandler'));
		$ilBench->stop("Core", "HeaderInclude_GetErrorHandler");


		// prepare file access to work with safe mode (has been done in class ilias before)
		umask(0117);
		
		// set cookie params
		$this->setCookieParams();

		// $ilIliasIniFile initialisation
		$this->initIliasIniFile();
		
		// CLIENT_ID determination
		$this->determineClient();

		// $ilAppEventHandler initialisation
		$this->initEventHandling();

		// $ilClientIniFile initialisation
		if (!$this->initClientIniFile())
		{
			$c = $_COOKIE["ilClientId"];
			ilUtil::setCookie("ilClientId", $ilIliasIniFile->readVariable("clients","default"));
			if (CLIENT_ID != "" && CLIENT_ID != $ilIliasIniFile->readVariable("clients","default"))
			{
				ilUtil::redirect("index.php?client_id=".$ilIliasIniFile->readVariable("clients","default"));
			}
			else
			{
				echo ("Client $c does not exist. ".'Please <a href="./index.php">click here</a> to return to the default client.');
			}
			exit;
			//ilUtil::redirect("./setup/setup.php");	// to do: this could fail in subdirectories
													// this is also source of a bug (see mantis)
		}

		if (DEVMODE && SHOWNOTICES)
		{
			// no further differentiating of php version regarding to 5.4 neccessary
			// when the error reporting is set to E_ALL anyway
			
			// remove notices from error reporting
			if (version_compare(PHP_VERSION, '5.3.0', '>='))
			{
				error_reporting(E_ALL);
			}
			else
			{
				error_reporting(E_ALL);
			}
		}
		
		// allow login by submitting user data
		// in query string when DEVMODE is enabled
		if( DEVMODE
		    && isset($_GET['username']) && strlen($_GET['username'])
		    && isset($_GET['password']) && strlen($_GET['password'])
		){
			$_POST['username'] = $_GET['username'];
			$_POST['password'] = $_GET['password'];
		}

		// maintenance mode
		$this->handleMaintenanceMode();

		// $ilDB initialisation
		$this->initDatabase();

		// init plugin admin class
		include_once("./Services/Component/classes/class.ilPluginAdmin.php");
		$ilPluginAdmin = new ilPluginAdmin();
		$GLOBALS['ilPluginAdmin'] = $ilPluginAdmin;

		// set session handler
		$this->setSessionHandler();

		// $ilSetting initialisation
		$this->initSettings();


		// $ilLog initialisation
		$this->initLog();

		// $https initialisation
		require_once './classes/class.ilHTTPS.php';
		$https = new ilHTTPS();
		$GLOBALS['https'] =& $https;
		$https->enableSecureCookies();
		$https->checkPort();		
		
		if($this->returnBeforeAuth()) return;
		
		$ilCtrl = new ilCtrl2();
		$GLOBALS['ilCtrl'] =& $ilCtrl;

		// $ilAuth initialisation
		include_once("./Services/Authentication/classes/class.ilAuthUtils.php");
		ilAuthUtils::_initAuth();
		global $ilAuth;
		
		$this->includePhp5Compliance();

//echo get_class($ilAuth);
//var_dump($ilAuth);
		
		// Do not accept external session ids
		if (!ilSession::_exists(session_id()) && !defined('IL_PHPUNIT_TEST'))
		{
//			$_GET["PHPSESSID"] = "";
			session_regenerate_id();
		}

		// $ilias initialisation
		global $ilias, $ilBench;
		$ilBench->start("Core", "HeaderInclude_GetILIASObject");
		$ilias = new ILIAS();
		$GLOBALS['ilias'] =& $ilias;
		$ilBench->stop("Core", "HeaderInclude_GetILIASObject");

		// test: trace function calls in debug mode
		if (DEVMODE)
		{
			if (function_exists("xdebug_start_trace"))
			{
				//xdebug_start_trace("/tmp/test.txt");
			}
		}

		// $ilObjDataCache initialisation
		$ilObjDataCache = new ilObjectDataCache();
		$GLOBALS['ilObjDataCache'] =& $ilObjDataCache;

		// workaround: load old post variables if error handler 'message' was called
		if (isset($_SESSION["message"]) && $_SESSION["message"])
		{
			$_POST = $_SESSION["post_vars"];
		}


		// put debugging functions here
		require_once "include/inc.debug.php";


		// $objDefinition initialisation
		$ilBench->start("Core", "HeaderInclude_getObjectDefinitions");
		$objDefinition = new ilObjectDefinition();
		$GLOBALS['objDefinition'] =& $objDefinition;
//		$objDefinition->startParsing();
		$ilBench->stop("Core", "HeaderInclude_getObjectDefinitions");

		// init tree
		$tree = new ilTree(ROOT_FOLDER_ID);
		$GLOBALS['tree'] =& $tree;

		// $ilAccess and $rbac... initialisation
		$this->initAccessHandling();

		// authenticate & start session
		PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, array($ilErr, "errorHandler"));
		$ilBench->start("Core", "HeaderInclude_Authentication");
//var_dump($_SESSION);
		////require_once('Log.php');
		////$ilAuth->logger = Log::singleton('error_log',PEAR_LOG_TYPE_SYSTEM,'TEST');
                ////$ilAuth->enableLogging = true;
			
		if (!defined("IL_PHPUNIT_TEST"))
		{
			$oldSid = session_id();
			
			$ilAuth->start();
			if(IS_PAYMENT_ENABLED)
			{
				$newSid = session_id();
				include_once './Services/Payment/classes/class.ilPaymentShoppingCart.php';
				ilPaymentShoppingCart::_migrateShoppingCart($oldSid, $newSid);
			}
		}

//var_dump($_SESSION);
		$ilias->setAuthError($ilErr->getLastError());
		$ilBench->stop("Core", "HeaderInclude_Authentication");

		// workaround: force login
		if ((isset($_GET["cmd"]) && $_GET["cmd"] == "force_login") || $this->script == "login.php")
		{
			$ilAuth->logout();
			if(!isset($_GET['forceShoppingCartRedirect']))
				$_SESSION = array();
			$_SESSION["AccountId"] = "";
			$ilAuth->start();
			$ilias->setAuthError($ilErr->getLastError());
		}

		// check correct setup
		if (!$ilias->getSetting("setup_ok"))
		{
			die("Setup is not completed. Please run setup routine again.");
		}

		// $ilUser initialisation (1)
		$ilBench->start("Core", "HeaderInclude_getCurrentUser");
		$ilUser = new ilObjUser();
		$ilias->account =& $ilUser;
		$GLOBALS['ilUser'] =& $ilUser;
		$ilBench->stop("Core", "HeaderInclude_getCurrentUser");

		// $ilCtrl initialisation
		//$ilCtrl = new ilCtrl();

		// determin current script and up-path to main directory
		// (sets $this->script and $this->updir)
		$this->determineScriptAndUpDir();

		// $styleDefinition initialisation and style handling for login and co.
		$this->initStyle();
		if (in_array($this->script,
			array("login.php", "register.php", "view_usr_agreement.php"))
			|| $_GET["baseClass"] == "ilStartUpGUI")
		{
			$this->handleStyle();
		}

		// init locale
		$this->initLocale();

		// handle ILIAS 2 imported users:
		// check ilias 2 password, if authentication failed
		// only if AUTH_LOCAL
//echo "A";
		if (AUTH_CURRENT == AUTH_LOCAL && !$ilAuth->getAuth() && $this->script == "login.php" && $_POST["username"] != "")
		{
			if (ilObjUser::_lookupHasIlias2Password(ilUtil::stripSlashes($_POST["username"])))
			{
				if (ilObjUser::_switchToIlias3Password(
					ilUtil::stripSlashes($_POST["username"]),
					ilUtil::stripSlashes($_POST["password"])))
				{
					$ilAuth->start();
					$ilias->setAuthError($ilErr->getLastError());
					ilUtil::redirect("index.php");
				}
			}
		}

//echo $_POST; exit;
		//
		// SUCCESSFUL AUTHENTICATION
		//
//if (!$ilAuth->getAuth() && $this->script != "login.php")
//{
//	var_dump($_SESSION);
//	echo "<br>B-".$ilAuth->getAuth()."-".$ilAuth->_sessionName."-".$ilias->account->isCurrentUserActive()."-";
//}
//var_dump ($session[_authsession]);
		#if (($ilAuth->getAuth() && $ilias->account->isCurrentUserActive()) ||
		#	(defined("IL_PHPUNIT_TEST") && DEVMODE))
			
		if($ilAuth->getStatus() == '' &&
			$ilias->account->isCurrentUserActive() ||
			(defined("IL_PHPUNIT_TEST") && DEVMODE))
		{
//echo "C"; exit;
			$ilBench->start("Core", "HeaderInclude_getCurrentUserAccountData");
//var_dump($_SESSION);
			// get user data
			$this->initUserAccount();
			
//var_dump($_SESSION);
			// check client IP of user
			$this->checkUserClientIP();

			// check user agreement (went here due to bug 5634)
			$this->checkUserAgreement();
			
			// update last_login date once the user logged in
			if ($this->script == "login.php" ||
				$_GET["baseClass"] == "ilStartUpGUI")
			{

				// determine first login of user for setting an indicator
				// which still is available in PersonalDesktop, Repository, ...
				// (last login date is set to current date in next step)
				require_once('Services/PrivacySecurity/classes/class.ilSecuritySettings.php');
				$security_settings = ilSecuritySettings::_getInstance();
				if( $security_settings->isPasswordChangeOnFirstLoginEnabled() &&
					null == $ilUser->getLastLogin() )
				{
					$ilUser->resetLastPasswordChange();
				}

				$ilUser->refreshLogin();
			}

			// differentiate account security mode
			require_once('./Services/PrivacySecurity/classes/class.ilSecuritySettings.php');
			$security_settings = ilSecuritySettings::_getInstance();
			if( $security_settings->getAccountSecurityMode() ==
				ilSecuritySettings::ACCOUNT_SECURITY_MODE_CUSTOMIZED )
			{
				// reset counter for failed logins
				ilObjUser::_resetLoginAttempts( $ilUser->getId() );
			}

			// set hits per page for all lists using table module
			$_GET['limit'] = $_SESSION['tbl_limit'] = (int) $ilUser->getPref('hits_per_page');

			// the next line makes it impossible to save the offset somehow in a session for
			// a specific table (I tried it for the user administration).
			// its not posssible to distinguish whether it has been set to page 1 (=offset = 0)
			// or not set at all (then we want the last offset, e.g. being used from a session var).
			// So I added the wrapping if statement. Seems to work (hopefully).
			// Alex April 14th 2006
			if (isset($_GET['offset']) && $_GET['offset'] != "")							// added April 14th 2006
			{
				$_GET['offset'] = (int) $_GET['offset'];		// old code
			}

			$ilBench->stop("Core", "HeaderInclude_getCurrentUserAccountData");
		}
		elseif (
					$this->script != "login.php"
					and $this->script != "shib_login.php"
					and $this->script != "shib_logout.php"
					and $this->script != "error.php"
					and $this->script != "index.php"
					and $this->script != "view_usr_agreement.php"
					and $this->script != "register.php"
					and $this->script != "chat.php"
					and $this->script != "pwassist.php"
					and $this->script != "confirmReg.php"
				)
		{
				
			// authentication failed due to inactive user?
			if ($ilAuth->getAuth() && !$ilUser->isCurrentUserActive())
			{
				$inactive = true;
			}
                        
			// jump to public section (to do: is this always the indended
			// behaviour, login could be another possibility (including
			// message)
//echo "-".$_GET["baseClass"]."-";
			if ($_GET["baseClass"] != "ilStartUpGUI")
			{
				// $lng initialisation
				$this->initLanguage();
				
				// Do not redirect for Auth_SOAP Auth_CRON Auth_HTTP
				if(!$ilAuth->supportsRedirects())
				{
					return false;
				}

				if ($ilSetting->get("pub_section") &&
					($ilAuth->getStatus() == "" || 
						$ilAuth->getStatus() == AUTH_EXPIRED ||
						$ilAuth->getStatus() == AUTH_IDLED) &&
					$_GET["reloadpublic"] != "1")
				{
						$this->goToPublicSection();
				}
				else
				{
					if ($context == "web")
					{
						// normal access by webinterface
						$this->goToLogin(($_GET['auth_stat'] && !$ilAuth->getStatus()) ? $_GET['auth_stat'] : $ilAuth->getStatus());
						exit;
					} 
					else 
					{
						// called by soapAuthenticationLdap
						return;
					}

				}
				// we should not get here => public section needs no redirect smeyer
				// exit;
			}
		}
		//
		// SUCCESSFUL AUTHENTICATED or NON-AUTH-AREA (Login, Registration, ...)
		//

		// $lng initialisation
		$this->initLanguage();

		// store user language in tree
		$GLOBALS['tree']->initLangCode();

		// instantiate main template
		$tpl = new ilTemplate("tpl.main.html", true, true);
		$GLOBALS['tpl'] =& $tpl;


		// ### AA 03.10.29 added new LocatorGUI class ###
		// when locator data array does not exist, initialise
		if ( !isset($_SESSION["locator_level"]) )
		{
			$_SESSION["locator_data"] = array();
			$_SESSION["locator_level"] = -1;
		}
		// initialise global ilias_locator object
		$ilias_locator = new ilLocatorGUI();			// deprecated
		$ilLocator = new ilLocatorGUI();
		$GLOBALS['ilias_locator'] =& $ilias_locator;	// deprecated
		$GLOBALS['ilLocator'] =& $ilLocator;

		// load style definitions
		// use the init function with plugin hook here, too
		$this->initStyle();

		// load style sheet depending on user's settings
		$location_stylesheet = ilUtil::getStyleSheetLocation();
		$tpl->setVariable("LOCATION_STYLESHEET",$location_stylesheet);

		// Init Navigation History
		$ilNavigationHistory = new ilNavigationHistory();
		$GLOBALS['ilNavigationHistory'] =& $ilNavigationHistory;

		// init infopanel

		// provide global browser information
		$ilBrowser = new ilBrowser();
		$GLOBALS['ilBrowser'] =& $ilBrowser;

		// provide global help object
		$ilHelp = new ilHelp();
		$GLOBALS['ilHelp'] =& $ilHelp;

		// main tabs gui
		include_once 'classes/class.ilTabsGUI.php';
		$ilTabs = new ilTabsGUI();
		$GLOBALS['ilTabs'] =& $ilTabs;
		
		// main toolbar gui
		include_once './Services/UIComponent/Toolbar/classes/class.ilToolbarGUI.php';
		$ilToolbar = new ilToolbarGUI();
		/**
		 * @var object ilToolbarGUI
		 */
		$GLOBALS['ilToolbar'] =& $ilToolbar;

		// main menu
		include_once './Services/MainMenu/classes/class.ilMainMenuGUI.php';
		$ilMainMenu = new ilMainMenuGUI("_top");
		$GLOBALS['ilMainMenu'] =& $ilMainMenu;

		// Store online time of user
		ilOnlineTracking::_updateAccess($ilUser->getId());
		
		// ECS Tasks
	 	include_once('./Services/WebServices/ECS/classes/class.ilECSTaskScheduler.php');
	 	$scheduler = ilECSTaskScheduler::start();

		$ilBench->stop("Core", "HeaderInclude");
//		$ilBench->save();

	}

	/**
	* Initialisation for feed.php
	*/
	function initFeed()
	{
		global $ilDB, $ilUser, $ilLog, $ilErr, $ilClientIniFile, $ilIliasIniFile,
			$ilSetting, $ilias, $https, $ilObjDataCache,
			$ilLog, $objDefinition, $lng, $ilCtrl, $ilBrowser, $ilHelp,
			$ilTabs, $ilMainMenu, $rbacsystem, $ilNavigationHistory;

		// remove unsafe characters
		$this->removeUnsafeCharacters();

		// include common code files
		$this->requireCommonIncludes();
		global $ilBench;

		// $ilAppEventHandler initialisation
		$this->initEventHandling();

		// set error handler (to do: check preconditions for error handler to work)
		$ilBench->start("Core", "HeaderInclude_GetErrorHandler");
		$ilErr = new ilErrorHandling();
		$GLOBALS['ilErr'] =& $ilErr;
		$ilErr->setErrorHandling(PEAR_ERROR_CALLBACK,array($ilErr,'errorHandler'));
		$ilBench->stop("Core", "HeaderInclude_GetErrorHandler");

		// prepare file access to work with safe mode (has been done in class ilias before)
		umask(0117);

		// $ilIliasIniFile initialisation
		$this->initIliasIniFile();

		// CLIENT_ID determination
		$this->determineClient();

		// $ilClientIniFile initialisation
		if (!$this->initClientIniFile())
		{
			$c = $_COOKIE["ilClientId"];
			ilUtil::setCookie("ilClientId", $ilIliasIniFile->readVariable("clients","default"));
			echo ("Client $c does not exist. Please reload this page to return to the default client.");
			exit;
		}

		// maintenance mode
		$this->handleMaintenanceMode();

		// $ilDB initialisation
		$this->initDatabase();

		// init plugin admin class
		include_once("./Services/Component/classes/class.ilPluginAdmin.php");
		$ilPluginAdmin = new ilPluginAdmin();
		$GLOBALS['ilPluginAdmin'] = $ilPluginAdmin;

		// $ilObjDataCache initialisation
		$ilObjDataCache = new ilObjectDataCache();
		$GLOBALS['ilObjDataCache'] =& $ilObjDataCache;

		// init settings
		$this->initSettings();

		// init tree
		$tree = new ilTree(ROOT_FOLDER_ID);
		$GLOBALS['tree'] =& $tree;

		// init language
		$lng = new ilLanguage($ilClientIniFile->readVariable("language","default"));
		$GLOBALS['lng'] =& $lng;

	}

	function initLog() {
		global $ilLog;
		$log = new ilLog(ILIAS_LOG_DIR,ILIAS_LOG_FILE,CLIENT_ID,ILIAS_LOG_ENABLED,ILIAS_LOG_LEVEL);
		$GLOBALS['log'] = $log;
		$ilLog = $log;
		$GLOBALS['ilLog'] = $ilLog;
	}

	function initILIASObject() {
		global $ilias, $ilBench;
		$ilBench->start("Core", "HeaderInclude_GetILIASObject");
		$ilias = new ILIAS();
		$GLOBALS['ilias'] =& $ilias;
		$ilBench->stop("Core", "HeaderInclude_GetILIASObject");
//var_dump($_SESSION);
	}
}
?>

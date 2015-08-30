<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once "./classes/class.ilObjectGUI.php";


/**
* Class ilObjExternalToolsSettingsGUI
*
* @author Sascha Hofmann <saschahofmann@gmx.de> 
* @version $Id: class.ilObjExternalToolsSettingsGUI.php 35584 2012-07-17 10:26:19Z jluetzen $
* 
* @ilCtrl_Calls ilObjExternalToolsSettingsGUI: ilPermissionGUI, ilECSSettingsGUI
* 
* @extends ilObjectGUI
*/
class ilObjExternalToolsSettingsGUI extends ilObjectGUI
{
	/**
	* Constructor
	* @access public
	*/
	function ilObjExternalToolsSettingsGUI($a_data,$a_id,$a_call_by_reference,$a_prepare_output = true)
	{
		global $lng;
		
		$this->type = "extt";
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference,false);
		
		define ("ILINC_DEFAULT_HTTP_PORT",80);
		define ("ILINC_DEFAULT_SSL_PORT",443);
		define ("ILINC_DEFAULT_TIMEOUT",30);
		$lng->loadLanguageModule("delic");
		$lng->loadLanguageModule("gmaps");
		$lng->loadLanguageModule("jsmath");
	}
	
	/**
	* display settings menu
	* 
	* @access	public
	*/
	function viewObject()
	{
		global $rbacsystem;
		
		if (!$rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}
		
		$this->__initSubTabs("view");
		
		$this->getTemplateFile("general");
		
		$this->tpl->setVariable("FORMACTION",
			$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_EXTT_TITLE", $this->lng->txt("extt_title_configure"));

		$this->tpl->setVariable("TXT_EXTT_NAME", $this->lng->txt("extt_name"));
		$this->tpl->setVariable("TXT_EXTT_ACTIVE", $this->lng->txt("active")."?");
		$this->tpl->setVariable("TXT_EXTT_DESC", $this->lng->txt("description"));

		$this->tpl->setVariable("TXT_CONFIGURE", $this->lng->txt("extt_configure"));
		$this->tpl->setVariable("TXT_EXTT_REMARK", $this->lng->txt("extt_remark"));

		// ilinc
		$this->tpl->setVariable("TXT_EXTT_ILINC_NAME", $this->lng->txt("extt_ilinc"));
		$this->tpl->setVariable("TXT_EXTT_ILINC_DESC", $this->lng->txt("extt_ilinc_desc"));

	
		// icon handlers
		$icon_ok = "<img src=\"".ilUtil::getImagePath("icon_ok.gif")."\" alt=\"".$this->lng->txt("enabled")."\" title=\"".$this->lng->txt("enabled")."\" border=\"0\" vspace=\"0\"/>";
		$icon_not_ok = "<img src=\"".ilUtil::getImagePath("icon_not_ok.gif")."\" alt=\"".$this->lng->txt("disabled")."\" title=\"".$this->lng->txt("disabled")."\" border=\"0\" vspace=\"0\"/>";

		$this->tpl->setVariable("EXTT_ILINC_ACTIVE", $this->ilias->getSetting('ilinc_active') ? $icon_ok : $icon_not_ok);
	}
	
	function cancelObject()
	{
		$this->ctrl->redirect($this, "view");
	}

	function getAdminTabs(&$tabs_gui)
	{
		$this->getTabs($tabs_gui);
	}	
	
	/**
	* get tabs
	* @access	public
	* @param	object	tabs gui object
	*/
	function getTabs(&$tabs_gui)
	{
		global $rbacsystem;

		$this->ctrl->setParameter($this,"ref_id",$this->object->getRefId());

		if ($rbacsystem->checkAccess("visible,read",$this->object->getRefId()))
		{
			$tabs_gui->addTarget("settings",
				$this->ctrl->getLinkTarget($this, "view"), 
				array("view","editiLinc","editDelicious", "editGoogleMaps","editjsMath", ""), "", "");
				
			$this->lng->loadLanguageModule('ecs');
			$tabs_gui->addTarget('ecs_server_settings',
				$this->ctrl->getLinkTargetByClass('ilecssettingsgui','overview'));
				
		}

		if ($rbacsystem->checkAccess('edit_permission',$this->object->getRefId()))
		{
			$tabs_gui->addTarget("perm_settings",
				$this->ctrl->getLinkTargetByClass(array(get_class($this),'ilpermissiongui'), "perm"), array("perm","info","owner"), 'ilpermissiongui');
		}
	}
	
	/**
	* Configure iLinc settings
	* 
	* @access	public
	*/
	function editiLincObject()
	{
		global $rbacsystem, $rbacreview;
		
		if (!$rbacsystem->checkAccess("write",$this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}
		
		$this->__initSubTabs("editiLinc");
		
		if ($_SESSION["error_post_vars"])
		{
			if ($_SESSION["error_post_vars"]["ilinc"]["active"] == "1")
			{
				$this->tpl->setVariable("CHK_ILINC_ACTIVE", "checked=\"checked\"");
			}
			
			if ($_SESSION["error_post_vars"]["ilinc"]["akclassvalues_active"] == "1")
			{
				$this->tpl->setVariable("CHK_ILINC_AKCLASSVALUES_ACTIVE", "checked=\"checked\"");
			}
			
			if ($_SESSION["error_post_vars"]["ilinc"]["akclassvalues_required"] == "1")
			{
				$this->tpl->setVariable("CHK_ILINC_AKCLASSVALUES_REQUIRED", "checked=\"checked\"");
			}
			
			$this->tpl->setVariable("ILINC_SERVER", $_SESSION["error_post_vars"]["ilinc"]["server"]);
			$this->tpl->setVariable("ILINC_REGISTRAR_LOGIN", $_SESSION["error_post_vars"]["ilinc"]["registrar_login"]);
			$this->tpl->setVariable("ILINC_REGISTRAR_PASSWD", $_SESSION["error_post_vars"]["ilinc"]["registrar_passwd"]);
			$this->tpl->setVariable("ILINC_CUSTOMER_ID", $_SESSION["error_post_vars"]["ilinc"]["customer_id"]);
		}
		else
		{
			// set already saved data or default value for port
			$settings = $this->ilias->getAllSettings();

			if ($settings["ilinc_active"] == "1")
			{
				$this->tpl->setVariable("CHK_ILINC_ACTIVE", "checked=\"checked\"");
			}

			$this->tpl->setVariable("ILINC_SERVER", $settings["ilinc_server"].$settings["ilinc_path"]);
			$this->tpl->setVariable("ILINC_REGISTRAR_LOGIN", $settings["ilinc_registrar_login"]);
			$this->tpl->setVariable("ILINC_REGISTRAR_PASSWD", $settings["ilinc_registrar_passwd"]);
			$this->tpl->setVariable("ILINC_CUSTOMER_ID", $settings["ilinc_customer_id"]);
			
			if (empty($settings["ilinc_port"]))
			{
				$this->tpl->setVariable("ILINC_PORT", ILINC_DEFAULT_HTTP_PORT);
			}
			else
			{
				$this->tpl->setVariable("ILINC_PORT", $settings["ilinc_port"]);			
			}

			if ($settings["ilinc_protocol"] == "https")
			{
				$this->tpl->setVariable("ILINC_PROTOCOL_SSL_SEL", "selected=\"selected\"");
			}
			else
			{
				$this->tpl->setVariable("ILINC_PROTOCOL_HTTP_SEL", "selected=\"selected\"");		
			}
			
			if (empty($settings["ilinc_timeout"]))
			{
				$this->tpl->setVariable("ILINC_TIMEOUT", ILINC_DEFAULT_TIMEOUT);
			}
			else
			{
				$this->tpl->setVariable("ILINC_TIMEOUT", $settings["ilinc_timeout"]);			
			}

			if ($settings["ilinc_akclassvalues_active"] == "1")
			{
				$this->tpl->setVariable("CHK_ILINC_AKCLASSVALUES_ACTIVE", "checked=\"checked\"");
			}

			if ($settings["ilinc_akclassvalues_required"] == "1")
			{
				$this->tpl->setVariable("CHK_ILINC_AKCLASSVALUES_REQUIRED", "checked=\"checked\"");
			}	
		}

		$this->getTemplateFile("ilinc");
		
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_ILINC_TITLE", $this->lng->txt("extt_ilinc_configure"));
		$this->tpl->setVariable("TXT_ILINC_ACTIVE", $this->lng->txt("extt_ilinc_enable"));
		$this->tpl->setVariable("TXT_ILINC_CONNECTION_DATA", $this->lng->txt("extt_ilinc_connection_data"));
		$this->tpl->setVariable("TXT_ILINC_ADDITIONAL_OPTIONS", $this->lng->txt("extt_ilinc_additional_options"));
		$this->tpl->setVariable("TXT_ILINC_SERVER", $this->lng->txt("extt_ilinc_server"));
		$this->tpl->setVariable("TXT_ILINC_PROTOCOL_PORT", $this->lng->txt("extt_ilinc_protocol_port"));
		$this->tpl->setVariable("TXT_ILINC_TIMEOUT", $this->lng->txt("extt_ilinc_timeout"));
		$this->tpl->setVariable("ILINC_DEFAULT_HTTP_PORT", ILINC_DEFAULT_HTTP_PORT);
		$this->tpl->setVariable("ILINC_DEFAULT_SSL_PORT", ILINC_DEFAULT_SSL_PORT);
		$this->tpl->setVariable("TXT_HTTP", $this->lng->txt('http'));
		$this->tpl->setVariable("TXT_SSL", $this->lng->txt('ssl'));
		
		$this->tpl->setVariable("TXT_SECONDS", $this->lng->txt("seconds"));
		$this->tpl->setVariable("TXT_ILINC_REGISTRAR_LOGIN", $this->lng->txt("extt_ilinc_registrar_login"));
		$this->tpl->setVariable("TXT_ILINC_REGISTRAR_PASSWD", $this->lng->txt("extt_ilinc_registrar_passwd"));
		$this->tpl->setVariable("TXT_ILINC_CUSTOMER_ID", $this->lng->txt("extt_ilinc_customer_id"));
		
		$this->tpl->setVariable("TXT_ILINC_AKCLASSVALUES_ACTIVE", $this->lng->txt("extt_ilinc_akclassvalues_active"));
		$this->tpl->setVariable("TXT_ILINC_AKCLASSVALUES_ACTIVE_INFO", $this->lng->txt("extt_ilinc_akclassvalues_active_info"));
		$this->tpl->setVariable("TXT_ILINC_AKCLASSVALUES_REQUIRED", $this->lng->txt("extt_ilinc_akclassvalues_required"));
		$this->tpl->setVariable("TXT_ILINC_AKCLASSVALUES_REQUIRED_INFO", $this->lng->txt("extt_ilinc_akclassvalues_required_info"));

		$this->tpl->setVariable("TXT_REQUIRED_FLD", $this->lng->txt("required_field"));
		$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->setVariable("TXT_SUBMIT", $this->lng->txt("save"));
		$this->tpl->setVariable("CMD_SUBMIT", "saveiLinc");
	}

	/**
	* validates all input data, save them to database if correct and active chosen extt mode
	* 
	* @access	public
	*/
	function saveiLincObject()
	{
         global $ilUser;

        // validate required data 
		if (!$_POST["ilinc"]["server"] or !$_POST["ilinc"]["port"] or !$_POST["ilinc"]["registrar_login"] or !$_POST["ilinc"]["registrar_passwd"] or !$_POST["ilinc"]["customer_id"])
		{
			$this->ilias->raiseError($this->lng->txt("fill_out_all_required_fields"),$this->ilias->error_obj->MESSAGE);
		}
		
		// validate port
		if ((preg_match("/^[0-9]{0,5}$/",$_POST["ilinc"]["port"])) == false)
		{
			$this->ilias->raiseError($this->lng->txt("err_invalid_port"),$this->ilias->error_obj->MESSAGE);
		}
		
		if (substr($_POST["ilinc"]["server"],0,8) != "https://" and substr($_POST["ilinc"]["server"],0,7) != "http://")
		{
			$_POST["ilinc"]["server"] = $_POST["ilinc"]["protocol"]."://".$_POST["ilinc"]["server"];
		}
		
		$url = parse_url($_POST["ilinc"]["server"]);
		
		if (!ilUtil::isIPv4($url["host"]) and !ilUtil::isDN($url["host"]))
		{
			$this->ilias->raiseError($this->lng->txt("err_invalid_server"),$this->ilias->error_obj->MESSAGE);
		}
		
		if (is_numeric($_POST["ilinc"]["timeout"]))
		{
			$this->ilias->setSetting("ilinc_timeout", $_POST["ilinc"]["timeout"]);
		}

		// all ok. save settings
		$this->ilias->setSetting("ilinc_server", $url["host"]);
		$this->ilias->setSetting("ilinc_path", $url["path"]);
		$this->ilias->setSetting("ilinc_protocol", $_POST["ilinc"]["protocol"]);
		$this->ilias->setSetting("ilinc_port", $_POST["ilinc"]["port"]);
		$this->ilias->setSetting("ilinc_active", $_POST["ilinc"]["active"]);
		$this->ilias->setSetting("ilinc_registrar_login", $_POST["ilinc"]["registrar_login"]);
		$this->ilias->setSetting("ilinc_registrar_passwd", $_POST["ilinc"]["registrar_passwd"]);
		$this->ilias->setSetting("ilinc_customer_id", $_POST["ilinc"]["customer_id"]);
		
		$this->ilias->setSetting("ilinc_akclassvalues_active", $_POST["ilinc"]["akclassvalues_active"]);
		$this->ilias->setSetting("ilinc_akclassvalues_required", $_POST["ilinc"]["akclassvalues_required"]);

		ilUtil::sendSuccess($this->lng->txt("extt_ilinc_settings_saved"),true);
		$this->ctrl->redirect($this,'editiLinc');
	}
	
	function addSocialBookmarkObject()
	{
		global $ilAccess, $rbacreview, $lng, $ilCtrl;
		
		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}
		
		$this->__initSubTabs("editSocialBookmarks");

		include_once 'class.ilSocialBookmarks.php';
		$form = ilSocialBookmarks::_initForm($this, 'create');
		$this->tpl->setVariable('ADM_CONTENT', $form->getHTML());
	}

	function createSocialBookmarkObject()
	{
		global $ilAccess, $rbacreview, $lng, $ilCtrl;
		
		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		include_once 'class.ilSocialBookmarks.php';
		$form = ilSocialBookmarks::_initForm($this, 'create');
		if ($form->checkInput())
		{
			$title = $form->getInput('title');
			$link = $form->getInput('link');
			$file = $form->getInput('image_file');
			$active = $form->getInput('activate');

			$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
			$icon_path = ilUtil::getWebspaceDir() . DIRECTORY_SEPARATOR . 'social_bm_icons' . DIRECTORY_SEPARATOR . time() . '.' . $extension;

			$path = ilUtil::getWebspaceDir() . DIRECTORY_SEPARATOR . 'social_bm_icons';
			if (!is_dir($path))	
				ilUtil::createDirectory($path);

			ilSocialBookmarks::_insertSocialBookmark($title, $link, $active, $icon_path);

			ilUtil::moveUploadedFile($file['tmp_name'], $file['name'], $icon_path);

			$this->editSocialBookmarksObject();
		}
		else
		{
			$this->__initSubTabs("editSocialBookmarks");
			$form->setValuesByPost();
			$this->tpl->setVariable('ADM_CONTENT', $form->getHTML());
		}
	}

	function updateSocialBookmarkObject()
	{
		global $ilAccess, $rbacreview, $lng, $ilCtrl;
		
		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		include_once 'class.ilSocialBookmarks.php';
		$form = ilSocialBookmarks::_initForm($this, 'update');
		if ($form->checkInput())
		{
			$title = $form->getInput('title');
			$link = $form->getInput('link');
			$file = $form->getInput('image_file');
			$active = $form->getInput('activate');
			$id = $form->getInput('sbm_id');

			if (!$file['name'])
				ilSocialBookmarks::_updateSocialBookmark($id, $title, $link, $active);
			else
			{
				$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
				$icon_path = ilUtil::getWebspaceDir() . DIRECTORY_SEPARATOR . 'social_bm_icons' . DIRECTORY_SEPARATOR . time() . '.' . $extension;

				$path = ilUtil::getWebspaceDir() . DIRECTORY_SEPARATOR . 'social_bm_icons';
				if (!is_dir($path))	
					ilUtil::createDirectory($path);

				ilSocialBookmarks::_deleteImage($id);
				ilSocialBookmarks::_updateSocialBookmark($id, $title, $link, $active, $icon_path);	
				ilUtil::moveUploadedFile($file['tmp_name'], $file['name'], $icon_path);
			}

			$this->editSocialBookmarksObject();
		}
		else
		{
			$this->__initSubTabs("editSocialBookmarks");
			$form->setValuesByPost();
			$this->tpl->setVariable('ADM_CONTENT', $form->getHTML());
		}
	}

	/**
	* edit a social bookmark
	* 
	* @access	public
	*/
	function editSocialBookmarkObject()
	{
		global $ilAccess, $rbacreview, $lng, $ilCtrl;

		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}
		
		$this->__initSubTabs("editSocialBookmarks");

		include_once 'class.ilSocialBookmarks.php';		
		$row = ilSocialBookmarks::_getEntry($_GET['sbm_id']);
		$dset = array
		(
			'sbm_id' => $row->sbm_id,
			'title' => $row->sbm_title,
			'link' => $row->sbm_link,
			'activate' => $row->sbm_active
		);

		$form = ilSocialBookmarks::_initForm($this, 'update');
		$form->setValuesByArray($dset);
		$this->tpl->setVariable('ADM_CONTENT', $form->getHTML());
	}

	function enableSocialBookmarksObject()
	{
		global $ilAccess, $rbacreview, $lng, $ilCtrl;

		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}

		$ids = ((int)$_GET['sbm_id']) ? array((int)$_GET['sbm_id']) : $_POST['sbm_id'];
		include_once 'class.ilSocialBookmarks.php';
		ilSocialBookmarks::_setActive($ids, true);
		$this->editSocialBookmarksObject();
	}

	function disableSocialBookmarksObject()
	{
		global $ilAccess, $rbacreview, $lng, $ilCtrl;

		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);

		}
		$ids = ((int)$_GET['sbm_id']) ? array((int)$_GET['sbm_id']) : $_POST['sbm_id'];
		include_once 'class.ilSocialBookmarks.php';
		ilSocialBookmarks::_setActive($ids, false);
		$this->editSocialBookmarksObject();
	}

	function deleteSocialBookmarksObject()
	{
		global $ilAccess, $rbacreview, $lng, $ilCtrl;
		
		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);

		}

		$this->__initSubTabs("editSocialBookmarks");

		include_once("Services/Utilities/classes/class.ilConfirmationGUI.php");
		$c_gui = new ilConfirmationGUI();
		
		$ids = ((int)$_GET['sbm_id']) ? array((int)$_GET['sbm_id']) : $_POST['sbm_id'];

		// set confirm/cancel commands
		$c_gui->setFormAction($ilCtrl->getFormAction($this, "confirmDeleteSocialBookmarks"));
		$c_gui->setHeaderText($lng->txt("socialbm_sure_delete_entry"));
		$c_gui->setCancel($lng->txt("cancel"), "editSocialBookmarks");
		$c_gui->setConfirm($lng->txt("confirm"), "confirmDeleteSocialBookmarks");
		
		include_once 'class.ilSocialBookmarks.php';
		// add items to delete
		foreach($ids as $id)
		{
			$entry = ilSocialBookmarks::_getEntry($id);
			$c_gui->addItem("sbm_id[]", $id, $entry->sbm_title . ' (' . str_replace('{', '&#123;', $entry->sbm_link) . ')');
		}
		
		$this->tpl->setVariable('ADM_CONTENT', $c_gui->getHTML());
	}

	function confirmDeleteSocialBookmarksObject()
	{
		global $ilAccess, $rbacreview, $lng, $ilCtrl;
		
		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}


		$ids = ((int)$_GET['sbm_id']) ? array((int)$_GET['sbm_id']) : $_POST['sbm_id'];
		include_once 'class.ilSocialBookmarks.php';
		ilSocialBookmarks::_delete($ids, false);
		$this->editSocialBookmarksObject();
	}

	/**
	* Configure social bookmark settings
	* 
	* @access	public
	*/
	function editSocialBookmarksObject()
	{
		global $ilAccess, $rbacreview, $lng, $ilCtrl;
		
		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}
		
		$this->__initSubTabs("editSocialBookmarks");
		


		include_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");

		include_once 'class.ilSocialBookmarks.php';
		$rset = ilSocialBookmarks::_getEntry();

		$counter = 0;
		foreach($rset as $row)
		{
			$current_selection_list = new ilAdvancedSelectionListGUI();
			$current_selection_list->setListTitle($lng->txt("actions"));
			$current_selection_list->setId("act_".$counter++);

			$ilCtrl->setParameter($this, 'sbm_id', $row->sbm_id);

			$current_selection_list->addItem($lng->txt("edit"), '', $ilCtrl->getLinkTarget($this, "editSocialBookmark"));
			$current_selection_list->addItem($lng->txt("delete"), '', $ilCtrl->getLinkTarget($this, "deleteSocialBookmarks"));
			
			$toggle_action = '';
			if ($row->sbm_active)
			{
				$current_selection_list->addItem($lng->txt("socialbm_disable"), '', $toggle_action = $ilCtrl->getLinkTarget($this, "disableSocialBookmarks"));
			}
			else
			{
				$current_selection_list->addItem($lng->txt("socialbm_enable"), '', $toggle_action = $ilCtrl->getLinkTarget($this, "enableSocialBookmarks"));
			}



			$dset[] = array
			(
				'CHECK' => ilUtil::formCheckbox(0, 'sbm_id[]', $row->sbm_id),
				'ID' => $row->sbm_id,
				'TITLE' => $row->sbm_title,
				'LINK' => str_replace('{', '&#123;', $row->sbm_link),
				'ICON' => $row->sbm_icon,
				'ACTIVE' => $row->sbm_active ? $lng->txt('enabled') : $lng->txt('disabled'),
				'ACTIONS' => $current_selection_list->getHTML(),
				'TOGGLE_LINK' => $toggle_action
			);
			$ilCtrl->clearParameters($this);
		}

		require_once 'Services/Table/classes/class.ilTable2GUI.php';
		$table = new ilTable2GUI($this, 'editSocialBookmarks');
		$table->setFormName('smtable');
		$table->setId('smtable');
		$table->setPrefix('sm');
		$table->setFormAction($ilCtrl->getFormAction($this, 'saveSocialBookmarks'));
		$table->addColumn('', 'check', '', true);
		$table->addColumn($lng->txt('icon'), '');
		$table->addColumn($lng->txt('title'), 'TITLE');
		$table->addColumn($lng->txt('link'), 'LINK');
		$table->addColumn($lng->txt('active'), 'ACTIVE');
		$table->addColumn($lng->txt('actions'), '');
		$table->setTitle($lng->txt('bm_manage_social_bm'));
		$table->setData($dset);
		$table->setRowTemplate('tpl.social_bookmarking_row.html', 'Services/Administration');
		$table->setSelectAllCheckbox('sbm_id');

		$table->setDefaultOrderField("title");
		$table->setDefaultOrderDirection("asc");

		$table->addMultiCommand('enableSocialBookmarks', $lng->txt('socialbm_enable'));
		$table->addMultiCommand('disableSocialBookmarks', $lng->txt('socialbm_disable'));
		$table->addMultiCommand('deleteSocialBookmarks', $lng->txt('delete'));

		$table->addCommandButton('addSocialBookmark', $lng->txt('create'));
		
		$this->tpl->setVariable('ADM_CONTENT', $table->getHTML());
	}


	/**
	* Configure jsMath settings
	* 
	* @access	public
	*/
	function editjsMathObject()
	{
		global $ilAccess, $rbacreview, $lng, $ilCtrl, $tpl;
		
		$jsMathSetting = new ilSetting("jsMath");
		$path_to_jsmath = array_key_exists("path_to_jsmath", $_GET) ? $_GET["path_to_jsmath"] : $jsMathSetting->get("path_to_jsmath");
		
		$this->__initSubTabs("editjsMath");

		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this));
		$form->setTitle($lng->txt("jsmath_settings"));
		
		// Enable jsMath
		$enable = new ilCheckboxInputGUI($lng->txt("jsmath_enable_jsmath"), "enable");
		$enable->setChecked($jsMathSetting->get("enable"));
		$enable->setInfo($lng->txt("jsmath_enable_jsmath_info"));
		$form->addItem($enable);
		// Path to jsMath
		$text_prop = new ilTextInputGUI($lng->txt("jsmath_path_to_jsmath"), "path_to_jsmath");
		$text_prop->setInfo($lng->txt("jsmath_path_to_jsmath_desc"));
		$text_prop->setValue($path_to_jsmath);
		$text_prop->setRequired(TRUE);
		$text_prop->setMaxLength(400);
		$text_prop->setSize(80);
		$form->addItem($text_prop);
		// jsMath as default
		$enable = new ilCheckboxInputGUI($lng->txt("jsmath_default_setting"), "makedefault");
		$enable->setChecked($jsMathSetting->get("makedefault"));
		$enable->setInfo($lng->txt("jsmath_default_setting_info"));
		$form->addItem($enable);

		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$form->addCommandButton("savejsMath", $lng->txt("save"));
		}
				
		$tpl->setVariable("ADM_CONTENT", $form->getHTML());
	}

	/**
	* Save jsMath Setttings
	*/
	function savejsMathObject()
	{
		global $ilCtrl;
		$error = FALSE;
		
		$path_to_jsmath = ilUtil::stripSlashes($_POST["path_to_jsmath"]);
		while (strrpos($path_to_jsmath, "/") == strlen($path_to_jsmath)-1)
		{
			$path_to_jsmath = substr($path_to_jsmath, 0, strlen($path_to_jsmath)-1);
		}
		// check jsmath path
		if (file_exists($path_to_jsmath . "/" . "jsMath.js"))
		{
		}
		else
		{
			$error = TRUE;
			if (strlen($path_to_jsmath) == 0)
			{
				ilUtil::sendFailure($this->lng->txt("fill_out_all_required_fields"), TRUE);
			}
			else
			{
				$ilCtrl->setParameter($this, "path_to_jsmath", $path_to_jsmath);
				ilUtil::sendFailure($this->lng->txt("jsmath_path_not_found"), TRUE);
			}
		}

		if (!$error)
		{
			$jsMathSetting = new ilSetting("jsMath");
			$jsMathSetting->set("path_to_jsmath", $path_to_jsmath);
			$jsMathSetting->set("enable", ilUtil::stripSlashes($_POST["enable"]));
			$jsMathSetting->set("makedefault", ilUtil::stripSlashes($_POST["makedefault"]));
		}
		
		$ilCtrl->redirect($this, "editjsMath");
	}

	/**
	* Configure google maps settings
	* 
	* @access	public
	*/
	function editGoogleMapsObject()
	{
		global $ilAccess, $rbacreview, $lng, $ilCtrl, $tpl;
		
		$gm_set = new ilSetting("google_maps");
		
		if (!$ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->ilias->raiseError($this->lng->txt("permission_denied"),$this->ilias->error_obj->MESSAGE);
		}
		
		$this->__initSubTabs("editGoogleMaps");

		// $api_key = $gm_set->get("api_key");
		$std_latitude = $gm_set->get("std_latitude");
		$std_longitude = $gm_set->get("std_longitude");
		$std_zoom = $gm_set->get("std_zoom");
		$api_url = "http://www.google.com/apis/maps/signup.html";
		
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this));
		$form->setTitle($lng->txt("gmaps_settings"));
		
		// Enable Google Maps
		$enable = new ilCheckboxInputGUI($lng->txt("gmaps_enable_gmaps"), "enable");
		$enable->setChecked($gm_set->get("enable"));
		$enable->setInfo($lng->txt("gmaps_enable_gmaps_info"));
		$form->addItem($enable);

		// API key
		/*
		$text_prop = new ilTextInputGUI($lng->txt("gmaps_api_key"), "api_key");
		$text_prop->setInfo($lng->txt("gmaps_api_key_desc").' <a href="'.$api_url.'" target="_blank">'.$api_url.'</a>');
		$text_prop->setValue($api_key);
		$text_prop->setRequired(false);
		$text_prop->setMaxLength(200);
		$text_prop->setSize(60);
		$form->addItem($text_prop);		 
		*/
		
		// location property
		$loc_prop = new ilLocationInputGUI($lng->txt("gmaps_std_location"),
			"std_location");
		$loc_prop->setLatitude($std_latitude);
		$loc_prop->setLongitude($std_longitude);
		$loc_prop->setZoom($std_zoom);
		$form->addItem($loc_prop);
		
		$form->addCommandButton("saveGoogleMaps", $lng->txt("save"));
		$form->addCommandButton("view", $lng->txt("cancel"));
		
		$tpl->setVariable("ADM_CONTENT", $form->getHTML());
	}


	/**
	* Save Google Maps Setttings
	*/
	function saveGoogleMapsObject()
	{
		global $ilCtrl;

		$gm_set = new ilSetting("google_maps");
		
		$gm_set->set("enable", ilUtil::stripSlashes($_POST["enable"]));
		// $gm_set->set("api_key", ilUtil::stripSlashes($_POST["api_key"]));
		$gm_set->set("std_latitude", ilUtil::stripSlashes($_POST["std_location"]["latitude"]));
		$gm_set->set("std_longitude", ilUtil::stripSlashes($_POST["std_location"]["longitude"]));
		$gm_set->set("std_zoom", ilUtil::stripSlashes($_POST["std_location"]["zoom"]));
		
		$ilCtrl->redirect($this, "editGoogleMaps");
	}
	
	// init sub tabs
	function __initSubTabs($a_cmd)
	{
		$ilinc = ($a_cmd == 'editiLinc') ? true : false;
		$overview = ($a_cmd == 'view' or $a_cmd == '') ? true : false;
		//$delicious = ($a_cmd == 'editDelicious') ? true : false;
		$socialbookmarks = ($a_cmd == 'editSocialBookmarks') ? true : false;
		$gmaps = ($a_cmd == 'editGoogleMaps') ? true : false;
		$jsmath = ($a_cmd == 'editjsMath') ? true : false;

		$this->tabs_gui->addSubTabTarget("overview", $this->ctrl->getLinkTarget($this, "view"),
										 "", "", "", $overview);
		/*$this->tabs_gui->addSubTabTarget("delic_extt_delicious", $this->ctrl->getLinkTarget($this, "editDelicious"),
											"", "", "", $delicious);*/
		$this->tabs_gui->addSubTabTarget("socialbm_extt_social_bookmarks", $this->ctrl->getLinkTarget($this, "editSocialBookmarks"),
											"", "", "", $socialbookmarks);
		$this->tabs_gui->addSubTabTarget("jsmath_extt_jsmath", $this->ctrl->getLinkTarget($this, "editjsMath"),
											"", "", "", $jsmath);
		$this->tabs_gui->addSubTabTarget("gmaps_extt_gmaps", $this->ctrl->getLinkTarget($this, "editGoogleMaps"),
										 "", "", "", $gmaps);
		$this->tabs_gui->addSubTabTarget("extt_ilinc", $this->ctrl->getLinkTarget($this, "editiLinc"),
										 "", "", "", $ilinc);
	}
	
	function &executeCommand()
	{
		
		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();
		$this->prepareOutput();

		switch($next_class)
		{
			case 'ilecssettingsgui':
				$this->tabs_gui->setTabActive('ecs_server_settings');
				include_once('./Services/WebServices/ECS/classes/class.ilECSSettingsGUI.php');
				$this->ctrl->forwardCommand(new ilECSSettingsGUI());
				break;
			
			case 'ilpermissiongui':
				include_once("Services/AccessControl/classes/class.ilPermissionGUI.php");
				$perm_gui =& new ilPermissionGUI($this);
				$ret =& $this->ctrl->forwardCommand($perm_gui);
				$this->tabs_gui->setTabActive('perm_settings');
				break;

			default:
				$this->tabs_gui->setTabActive('settings');
				if(!$cmd)
				{
					$cmd = "view";
				}
				$cmd .= "Object";
				$this->$cmd();

				break;
		}
		return true;
	}
	
} // END class.ilObjExternalToolsSettingsGUI
?>

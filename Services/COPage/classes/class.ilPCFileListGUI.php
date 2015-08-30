<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once("./Services/COPage/classes/class.ilPCFileList.php");
require_once("./Services/COPage/classes/class.ilPageContentGUI.php");

/**
* Class ilPCListGUI
*
* User Interface for LM List Editing
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id: class.ilPCFileListGUI.php 30762 2011-09-22 15:16:28Z jluetzen $
*
* @ingroup ServicesCOPage
*/
class ilPCFileListGUI extends ilPageContentGUI
{

	/**
	* Constructor
	* @access	public
	*/
	function ilPCFileListGUI(&$a_pg_obj, &$a_content_obj, $a_hier_id, $a_pc_id = "")
	{
		parent::ilPageContentGUI($a_pg_obj, $a_content_obj, $a_hier_id, $a_pc_id);
		$this->setCharacteristics(array("FileListItem" => $this->lng->txt("cont_FileListItem")));
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		// get next class that processes or forwards current command
		$next_class = $this->ctrl->getNextClass($this);
		
		$this->getCharacteristicsOfCurrentStyle("flist_li");	// scorm-2004

		// get current command
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			default:
				$ret =& $this->$cmd();
				break;
		}

		return $ret;
	}

	/**
	* insert new file list form
	*/
	function insert()
	{
		global $ilUser, $ilTabs;

		if ($_GET["subCmd"] == "insertNew")
		{
			$_SESSION["cont_file_insert"] = "insertNew";
		}
		if ($_GET["subCmd"] == "insertFromRepository")
		{
			$_SESSION["cont_file_insert"] = "insertFromRepository";
		}
		if ($_GET["subCmd"] == "insertFromWorkspace")
		{
			$_SESSION["cont_file_insert"] = "insertFromWorkspace";
		}
		if (($_GET["subCmd"] == "") && $_SESSION["cont_file_insert"] != "")
		{
			$_GET["subCmd"] = $_SESSION["cont_file_insert"];
		}
	
		switch ($_GET["subCmd"])
		{
			case "insertFromWorkspace":
				$this->insertFromWorkspace();
				break;
			
			case "insertFromRepository":
				$this->insertFromRepository();
				break;
				
			case "selectFile":
				$this->selectFile();
				break;

			default:
				$this->setTabs();
				$ilTabs->setSubTabActive("cont_new_file");
		
				// new file list form
				$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.file_list_new.html", "Services/COPage");
				
				$this->tpl->setCurrentBlock("new_file");
				$this->tpl->setVariable("TXT_FILE", $this->lng->txt("file"));
				$this->tpl->parseCurrentBlock();
				
				$this->tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_insert_file_list"));
				$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
		
				$this->displayValidationError();
		
				if ($_SESSION["il_text_lang_".$_GET["ref_id"]] != "")
				{
					$s_lang = $_SESSION["il_text_lang_".$_GET["ref_id"]];
				}
				else
				{
					$s_lang = $ilUser->getLanguage();
				}
		
		
				// title
				$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("title"));
				$this->tpl->setVariable("INPUT_TITLE", "flst_title");
		
				// language
				$this->tpl->setVariable("TXT_LANGUAGE", $this->lng->txt("language"));
				require_once("Services/MetaData/classes/class.ilMDLanguageItem.php");
				$lang = ilMDLanguageItem::_getLanguages();
				$select_lang = ilUtil::formSelect ($s_lang, "flst_language",$lang,false,true);
				$this->tpl->setVariable("SELECT_LANGUAGE", $select_lang);
		
		
				$this->tpl->parseCurrentBlock();
		
				// operations
				$this->tpl->setCurrentBlock("commands");
				$this->tpl->setVariable("BTN_NAME", "create_flst");
				$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
				$this->tpl->setVariable("BTN_CANCEL", "cancelCreate");
				$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
				$this->tpl->parseCurrentBlock();
				break;
		}

	}

	/**
	* Select file
	*/
	function selectFile()
	{
		global $ilTabs, $ilUser;
		
		$this->setTabs();
		$ilTabs->setSubTabActive("cont_file_from_repository");
		
		// new file list form
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.file_list_new.html", "Services/COPage");
		
		$this->tpl->setCurrentBlock("rep_file");
		$this->tpl->setVariable("TXT_FILE", $this->lng->txt("file"));
		
		if(isset($_GET["file_ref_id"]))
		{
			include_once("./Modules/File/classes/class.ilObjFile.php");
			$file_obj = new ilObjFile($_GET["file_ref_id"]);
			if (is_object($file_obj))
			{
				$this->tpl->setVariable("TXT_FILE_TITLE", $file_obj->getTitle());
				$this->tpl->setVariable("FILE_REF_ID", $file_obj->getRefId());
			}
			$this->tpl->parseCurrentBlock();
		}
		else if(isset($_GET["fl_wsp_id"]))
		{
			// we need the object id for the instance
			include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceTree.php";
			$tree = new ilWorkspaceTree($ilUser->getId());			
			$node = $tree->getNodeData($_GET["fl_wsp_id"]);			
			
			include_once("./Modules/File/classes/class.ilObjFile.php");
			$file_obj = new ilObjFile($node["obj_id"], false);
			if (is_object($file_obj))
			{
				$this->tpl->setVariable("TXT_FILE_TITLE", $file_obj->getTitle());
				$this->tpl->setVariable("FILE_REF_ID", "wsp_".$node["obj_id"]);
			}
			$this->tpl->parseCurrentBlock();
		}
		
		$this->tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_insert_file_list"));
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		$this->displayValidationError();

		if ($_SESSION["il_text_lang_".$_GET["ref_id"]] != "")
		{
			$s_lang = $_SESSION["il_text_lang_".$_GET["ref_id"]];
		}
		else
		{
			$s_lang = $ilUser->getLanguage();
		}

		// select fields for number of columns
		$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("INPUT_TITLE", "flst_title");

		// language
		$this->tpl->setVariable("TXT_LANGUAGE", $this->lng->txt("language"));
		require_once("Services/MetaData/classes/class.ilMDLanguageItem.php");
		$lang = ilMDLanguageItem::_getLanguages();
		$select_lang = ilUtil::formSelect ($s_lang, "flst_language",$lang,false,true);
		$this->tpl->setVariable("SELECT_LANGUAGE", $select_lang);

//		$this->tpl->parseCurrentBlock();

		// operations
		$this->tpl->setCurrentBlock("commands");
		$this->tpl->setVariable("BTN_NAME", "create_flst");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->setVariable("BTN_CANCEL", "cancelCreate");
		$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->parseCurrentBlock();
	}
	
	/**
	* Insert file from repository
	*/
	function insertFromRepository($a_cmd = "insert")
	{
		global $ilTabs, $tree, $ilCtrl, $tpl;

		if ($a_cmd == "insert")
		{
			$this->setTabs();
		}
		else
		{
			$this->setItemTabs($a_cmd);
		}

		$ilTabs->setSubTabActive("cont_file_from_repository");
		
		include_once "./Services/COPage/classes/class.ilFileSelectorGUI.php";

		$exp = new ilFileSelectorGUI($this->ctrl->getLinkTarget($this, $a_cmd));

		if ($_GET["expand"] == "")
		{
			$expanded = $tree->readRootId();
		}
		else
		{
			$expanded = $_GET["expand"];
		}
		$exp->setExpand($expanded);

		$exp->setTargetGet("sel_id");
		//$this->ctrl->setParameter($this, "target_type", $a_type);
		$ilCtrl->setParameter($this, "subCmd", "insertFromRepository");
		$exp->setParamsGet($this->ctrl->getParameterArray($this, $a_cmd));
		
		// filter
		$exp->setFiltered(true);
		$exp->setFilterMode(IL_FM_POSITIVE);
		$exp->addFilter("root");
		$exp->addFilter("cat");
		$exp->addFilter("grp");
		$exp->addFilter("fold");
		$exp->addFilter("crs");
		$exp->addFilter("file");

		$sel_types = array('file');

		$exp->setOutput(0);

		$tpl->setContent($exp->getOutput());
	}
	
	/**
	* Insert file from personal workspace
	*/
	function insertFromWorkspace($a_cmd = "insert")
	{
		global $ilTabs, $tree, $ilCtrl, $tpl, $ilUser;

		if ($a_cmd == "insert")
		{
			$this->setTabs();
		}
		else
		{
			$this->setItemTabs($a_cmd);
		}

		$ilTabs->setSubTabActive("cont_file_from_workspace");
		
		// get ws tree
		include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceTree.php";
		$tree = new ilWorkspaceTree($ilUser->getId());
		
		// get access handler
		include_once("./Services/PersonalWorkspace/classes/class.ilWorkspaceAccessHandler.php");
		$acc_handler = new ilWorkspaceAccessHandler($tree);
		
		// get es explorer
		include_once("./Services/PersonalWorkspace/classes/class.ilWorkspaceExplorer.php");
		$exp = new ilWorkspaceExplorer(ilWorkspaceExplorer::SEL_TYPE_RADIO, '', 
			'filelist_wspexpand', $tree, $acc_handler);
		$exp->setTargetGet('fl_wsp_id');
		$exp->setFiltered(false);
		$exp->removeAllFormItemTypes();
		
		// select link 
		$exp->setTypeClickable("file");
		$ilCtrl->setParameter($this, "subCmd", "selectFile");
		$exp->setCustomLinkTarget($ilCtrl->getLinkTarget($this, $a_cmd));
		
		// filter
		$exp->setFiltered(true);
		$exp->setFilterMode(IL_FM_POSITIVE);
		$exp->addFilter("wsrt");
		$exp->addFilter("wfld");
		$exp->addFilter("file");
	
		// expand link
		$ilCtrl->setParameter($this, "subCmd", "insertFromWorkspace");
		$exp->setParamsGet($ilCtrl->getParameterArray($this, $a_cmd));		

		if($_GET['filelist_wspexpand'] == '')
		{
			$expanded = $tree->readRootId();
		}
		else
		{
			$expanded = $_GET['filelist_wspexpand'];
		}
		$exp->setExpand($expanded);
		$exp->setOutput(0);
		
		$tpl->setContent($exp->getOutput());
	}
	
	/**
	* create new file list in dom and update page in db
	*/
	function create()
	{
		include_once("./Modules/File/classes/class.ilObjFile.php");

		// from personal workspace
		if(substr($_POST["file_ref_id"], 0, 4) == "wsp_")
		{
			$fileObj = new ilObjFile(substr($_POST["file_ref_id"], 4), false);
		}
		// upload
		else if ($_POST["file_ref_id"] == 0)
		{
			$fileObj = new ilObjFile();
			$fileObj->setType("file");
			$fileObj->setTitle($_FILES["Fobject"]["name"]["file"]);
			$fileObj->setDescription("");
			$fileObj->setFileName($_FILES["Fobject"]["name"]["file"]);
			$fileObj->setFileType($_FILES["Fobject"]["type"]["file"]);
			$fileObj->setFileSize($_FILES["Fobject"]["size"]["file"]);
			$fileObj->setMode("filelist");
			$fileObj->create();
			// upload file to filesystem
			$fileObj->createDirectory();
			$fileObj->raiseUploadError(false);
			$fileObj->getUploadFile($_FILES["Fobject"]["tmp_name"]["file"],
				$_FILES["Fobject"]["name"]["file"]);
		}
		// from repository
		else
		{
			$fileObj = new ilObjFile($_POST["file_ref_id"]);
		}
		$_SESSION["il_text_lang_".$_GET["ref_id"]] = $_POST["flst_language"];

//echo "::".is_object($this->dom).":";
		$this->content_obj = new ilPCFileList($this->dom);
		$this->content_obj->create($this->pg_obj, $this->hier_id, $this->pc_id);
		$this->content_obj->setListTitle(ilUtil::stripSlashes($_POST["flst_title"]), $_POST["flst_language"]);
		$this->content_obj->appendItem($fileObj->getId(), $fileObj->getFileName(),
			$fileObj->getFileType());
			
		$this->updated = $this->pg_obj->update();
		if ($this->updated === true)
		{
			$this->ctrl->returnToParent($this, "jump".$this->hier_id);
		}
		else
		{
			$this->insert();
		}
	}

	/**
	* edit properties form
	*/
	function edit()
	{
		$this->setTabs(false);
		
		// add paragraph edit template
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.file_list_edit.html", "Services/COPage");
		$this->tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_edit_file_list_properties"));
		$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

		$this->displayValidationError();

		// select fields for number of columns
		$this->tpl->setVariable("TXT_TITLE", $this->lng->txt("title"));
		$this->tpl->setVariable("INPUT_TITLE", "flst_title");

		// todo: this doesnt work if title contains " quotes
		// ... addslashes doesnt work
		$this->tpl->setVariable("VALUE_TITLE", $this->content_obj->getListTitle());

		// language
		$this->tpl->setVariable("TXT_LANGUAGE", $this->lng->txt("language"));
		require_once("Services/MetaData/classes/class.ilMDLanguageItem.php");
		$lang = ilMDLanguageItem::_getLanguages();
		$select_lang = ilUtil::formSelect ($this->content_obj->getLanguage(),"flst_language",$lang,false,true);
		$this->tpl->setVariable("SELECT_LANGUAGE", $select_lang);


//		$this->tpl->parseCurrentBlock();

		// operations
		$this->tpl->setCurrentBlock("commands");
		$this->tpl->setVariable("BTN_NAME", "saveProperties");
		$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
		$this->tpl->setVariable("BTN_CANCEL", "cancelUpdate");
		$this->tpl->setVariable("TXT_CANCEL", $this->lng->txt("cancel"));
		$this->tpl->parseCurrentBlock();

	}


	/**
	* save table properties in db and return to page edit screen
	*/
	function saveProperties()
	{
		$this->content_obj->setListTitle(ilUtil::stripSlashes($_POST["flst_title"]),
			$_POST["flst_language"]);
		$this->updated = $this->pg_obj->update();
		if ($this->updated === true)
		{
			$this->ctrl->returnToParent($this, "jump".$this->hier_id);
		}
		else
		{
			$this->pg_obj->addHierIDs();
			$this->edit();
		}
	}

	/**
	* Edit Files
	*/
	function editFiles()
	{
		global $tpl;
		
		$this->setTabs(false);
		include_once("./Services/COPage/classes/class.ilPCFileListTableGUI.php");
		$table_gui = new ilPCFileListTableGUI($this, "editFiles", $this->content_obj);
		$tpl->setContent($table_gui->getHTML());
	}

	/**
	* Set Tabs
	*/
	function setTabs($a_create = true)
	{
		global $ilTabs, $ilCtrl, $lng, $ilSetting;

		if ($a_create)
		{
			$cmd = "insert";
			
			$ilCtrl->setParameter($this, "subCmd", "insertNew");
			$ilTabs->addSubTabTarget("cont_new_file",
				$ilCtrl->getLinkTarget($this, $cmd), $cmd);
	
			$ilCtrl->setParameter($this, "subCmd", "insertFromRepository");
			$ilTabs->addSubTabTarget("cont_file_from_repository",
				$ilCtrl->getLinkTarget($this, $cmd), $cmd);
			$ilCtrl->setParameter($this, "subCmd", "");
			
			if(!$ilSetting->get("disable_personal_workspace") &&
				!$ilSetting->get("disable_wsp_files"))
			{
				$ilCtrl->setParameter($this, "subCmd", "insertFromWorkspace");
				$ilTabs->addSubTabTarget("cont_file_from_workspace",
					$ilCtrl->getLinkTarget($this, $cmd), $cmd);
				$ilCtrl->setParameter($this, "subCmd", "");
			}
		}
		else
		{
			$ilTabs->setBackTarget($lng->txt("pg"),
				$this->ctrl->getParentReturn($this));
	
			$ilTabs->addTarget("cont_ed_edit_prop",
				$ilCtrl->getLinkTarget($this, "edit"), "edit",
				get_class($this));
	
			$ilTabs->addTarget("cont_ed_edit_files",
				$ilCtrl->getLinkTarget($this, "editFiles"), "editFiles",
				get_class($this));
		}
	}

	/**
	* Add file item. This function is called from file list table and calls
	* newItemAfter function.
	*/
	function addFileItem()
	{
		global $ilCtrl;
		
		$files = $this->content_obj->getFileList();

		if (count($files) >= 1)
		{
			$ilCtrl->setParameterByClass("ilpcfileitemgui", "hier_id",
				$files[count($files) - 1]["hier_id"]);
			$ilCtrl->setParameterByClass("ilpcfileitemgui", "pc_id",
				$files[count($files) - 1]["pc_id"]);
			$ilCtrl->redirectByClass("ilpcfileitemgui", "newItemAfter");
		}
		else
		{
			$ilCtrl->redirect($this, "newFileItem");
		}
	}
	
	/**
	* Delete file items from list
	*/
	function deleteFileItem()
	{
		global $ilCtrl;
		
		if (is_array($_POST["fid"]))
		{
			$ids = array();
			foreach($_POST["fid"] as $k => $v)
			{
				$ids[] = $k;
			}
			$this->content_obj->deleteFileItems($ids);
		}
		$this->updated = $this->pg_obj->update();
		$ilCtrl->redirect($this, "editFiles");
	}
	
	/**
	* Save positions of file items
	*/
	function savePositions()
	{
		global $ilCtrl;
		
		if (is_array($_POST["position"]))
		{
			$this->content_obj->savePositions($_POST["position"]);
		}
		$this->updated = $this->pg_obj->update();
		$ilCtrl->redirect($this, "editFiles");
	}

	/**
	* Save positions of file items and style classes
	*/
	function savePositionsAndClasses()
	{
		global $ilCtrl;
		
		if (is_array($_POST["position"]))
		{
			$this->content_obj->savePositions($_POST["position"]);
		}
		if (is_array($_POST["class"]))
		{
			$this->content_obj->saveStyleClasses($_POST["class"]);
		}
		$this->updated = $this->pg_obj->update();
		$ilCtrl->redirect($this, "editFiles");
	}

	/**
	* Checks whether style selection shoudl be available or not
	*/
	function checkStyleSelection()
	{
		// check whether there is more than one style class
		$chars = $this->getCharacteristics();

		$classes = $this->content_obj->getAllClasses();
		if (count($chars) > 1)
		{
			return true;
		}
		foreach ($classes as $class)
		{
			if ($class != "" && $class != "FileListItem")
			{
				return true;
			}
		}
		return false;
	}

	//
	//
	// New file item
	//
	//

	/**
	 * New file item (called, if there is no file item in an existing
	 *
	 * @param
	 * @return
	 */
	function newFileItem()
	{
		global $ilTabs;

		if ($_GET["subCmd"] == "insertNew")
		{
			$_SESSION["cont_file_insert"] = "insertNew";
		}
		if ($_GET["subCmd"] == "insertFromRepository")
		{
			$_SESSION["cont_file_insert"] = "insertFromRepository";
		}
		if ($_GET["subCmd"] == "insertFromWorkspace")
		{
			$_SESSION["cont_file_insert"] = "insertFromWorkspace";
		}
		if (($_GET["subCmd"] == "") && $_SESSION["cont_file_insert"] != "")
		{
			$_GET["subCmd"] = $_SESSION["cont_file_insert"];
		}

		switch ($_GET["subCmd"])
		{		
			case "insertFromWorkspace":
				$this->insertFromWorkspace("newFileItem");
				break;
			
			case "insertFromRepository":
				$this->insertFromRepository("newFileItem");
				break;

			case "selectFile":
				$this->insertNewFileItem($_GET["file_ref_id"]);
				break;

			default:
				$this->setItemTabs("newFileItem");
				$ilTabs->setSubTabActive("cont_new_file");

				// new file list form
				$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.file_item_edit.html", "Services/COPage");
				$this->tpl->setVariable("TXT_ACTION", $this->lng->txt("cont_insert_file_item"));
				$this->tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));

				$this->displayValidationError();

				// file
				$this->tpl->setVariable("TXT_FILE", $this->lng->txt("file"));

				$this->tpl->parseCurrentBlock();

				// operations
				$this->tpl->setCurrentBlock("commands");
				$this->tpl->setVariable("BTN_NAME", "insertNewFileItem");
				$this->tpl->setVariable("BTN_TEXT", $this->lng->txt("save"));
				$this->tpl->parseCurrentBlock();
				break;
		}
	}

	/**
	 * insert new file item after another item
	 */
	function insertNewFileItem($a_file_ref_id = 0)
	{
		global $ilUser;
		
		// from personal workspace
		if(isset($_GET["fl_wsp_id"]))
		{
			// we need the object id for the instance
			include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceTree.php";
			$tree = new ilWorkspaceTree($ilUser->getId());			
			$node = $tree->getNodeData($_GET["fl_wsp_id"]);		
			
			include_once("./Modules/File/classes/class.ilObjFile.php");
			$file_obj = new ilObjFile($node["obj_id"], false);
		}
		// upload
		else if ($a_file_ref_id == 0)
		{
			$file_obj = $this->createFileItem();
		}
		// from repository
		else
		{
			include_once("./Modules/File/classes/class.ilObjFile.php");
			$file_obj = new ilObjFile($a_file_ref_id);
		}
		if (is_object($file_obj))
		{
			$this->content_obj->appendItem($file_obj->getId(),
				$file_obj->getTitle(), $file_obj->getFileType());
			$this->updated = $this->pg_obj->update();
			if ($this->updated === true)
			{
				$this->ctrl->returnToParent($this, "jump".$this->hier_id);
			}
		}

		$_GET["subCmd"] = "-";
		$this->newFileItem();
	}

	/**
	 * insert new file item
	 */
	function createFileItem()
	{
		global $lng;

		if ($_FILES["Fobject"]["name"]["file"] == "")
		{
			$_GET["subCmd"] = "-";
			ilUtil::sendFailure($lng->txt("upload_error_file_not_found"));
			return false;
		}
		include_once("./Modules/File/classes/class.ilObjFile.php");
		$fileObj = new ilObjFile();
		$fileObj->setType("file");
		$fileObj->setTitle($_FILES["Fobject"]["name"]["file"]);
		$fileObj->setDescription("");
		$fileObj->setFileName($_FILES["Fobject"]["name"]["file"]);
		$fileObj->setFileType($_FILES["Fobject"]["type"]["file"]);
		$fileObj->setFileSize($_FILES["Fobject"]["size"]["file"]);
		$fileObj->setMode("filelist");
		$fileObj->create();
		$fileObj->raiseUploadError(false);
		// upload file to filesystem
		$fileObj->createDirectory();
		$fileObj->getUploadFile($_FILES["Fobject"]["tmp_name"]["file"],
			$_FILES["Fobject"]["name"]["file"]);

		return $fileObj;
	}


	/**
	 * output tabs
	 */
	function setItemTabs($a_cmd = "")
	{
		global $ilTabs, $ilCtrl, $ilSetting;

		$ilTabs->addTarget("cont_back",
			$this->ctrl->getParentReturn($this), "",
			"");

		if ($a_cmd != "")
		{
			$ilCtrl->setParameter($this, "subCmd", "insertNew");
			$ilTabs->addSubTabTarget("cont_new_file",
				$ilCtrl->getLinkTarget($this, $a_cmd), $a_cmd);

			$ilCtrl->setParameter($this, "subCmd", "insertFromRepository");
			$ilTabs->addSubTabTarget("cont_file_from_repository",
				$ilCtrl->getLinkTarget($this, $a_cmd), $a_cmd);
			$ilCtrl->setParameter($this, "subCmd", "");
			
			if(!$ilSetting->get("disable_personal_workspace") &&
				!$ilSetting->get("disable_wsp_files"))
			{
				$ilCtrl->setParameter($this, "subCmd", "insertFromWorkspace");
				$ilTabs->addSubTabTarget("cont_file_from_workspace",
					$ilCtrl->getLinkTarget($this, $a_cmd), $a_cmd);
				$ilCtrl->setParameter($this, "subCmd", "");
			}
		}
	}


}
?>

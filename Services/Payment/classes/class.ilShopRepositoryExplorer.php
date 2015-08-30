<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once './classes/class.ilExplorer.php';

/*
*/
class ilShopRepositoryExplorer extends ilExplorer
{
	/**
	 * id of root folder
	 * @var int root folder id
	 * @access private
	 */
	var $root_id;
	var $output;
	var $ctrl;
	/**
	* Constructor
	* @access	public
	* @param	string	scriptname
	* @param    int user_id
	*/
	function ilShopRepositoryExplorer($a_target, $a_top_node = 0)
	{
		global $tree, $ilCtrl, $lng, $ilSetting, $objDefinition;

		$this->ctrl = $ilCtrl;

		$this->force_open_path = array();

		parent::ilExplorer($a_target);
		$this->tree = $tree;
		$this->root_id = $this->tree->readRootId();
		$this->order_column = "title";
		$this->frame_target = false;
		$this->setSessionExpandVariable("repexpand");
		#$this->setTitle($lng->txt("overview"));

# Es sollen nur container angezeigt werden, die entweder als container (z.B. Kurse) kaufbar sind oder kaufbare Objekte enthalten können!

/*		if ($ilSetting->get("repository_tree_pres") == "" ||
			($ilSetting->get("rep_tree_limit_grp_crs") && $a_top_node == 0))
		{*/
			$this->addFilter("root");
			$this->addFilter("cat");
			$this->addFilter('catr');
			$this->addFilter("grp");
			$this->addFilter("icrs");
			$this->addFilter("crs");
			$this->addFilter('crsr');
			$this->addFilter('rcrs');

#			$this->addFilter("file");
#			$this->addFilter("tst");
#			$this->addFilter("exc");
			$this->setFiltered(true);
			$this->setFilterMode(IL_FM_POSITIVE);
/*		}
		else if ($ilSetting->get("repository_tree_pres") == "all_types")
		{
			foreach ($objDefinition->getAllRBACObjects() as $rtype)
			{
				$this->addFilter($rtype);
			}
			$this->setFiltered(true);
			$this->setFilterMode(IL_FM_POSITIVE);
		}*/
	}

	/**
	 * set force open path
	 */
	function setForceOpenPath($a_path)
	{
		$this->force_open_path = $a_path;
	}

	/**
	* note: most of this stuff is used by ilCourseContentInterface too
	*/
	function buildLinkTarget($a_node_id, $a_type)
	{
		global $ilCtrl;
		switch($a_type)
		{

			case "cat":
				#return "repository.php?ref_id=".$a_node_id;
				return "ilias.php?baseClass=ilshopcontroller&ref_id=".$a_node_id;

			case "catr":
				#include_once('./Services/ContainerReference/classes/class.ilContainerReference.php');
				#$t_obj_id = ilContainerReference::_lookupTargetId(ilObject::_lookupObjId($a_node_id));
				#$ref_ids = ilObject::_getAllReferences($t_obj_id);
				#$a_node_id = current($ref_ids);
				return "ilias.php?cmd=redirect&baseClass=ilshopcontroller&ref_id=".$a_node_id;

			case "grp":
				#return "repository.php?ref_id=".$a_node_id."&cmdClass=ilobjgroupgui";
				return "ilias.php?baseClass=ilshopcontroller&ref_id=".$a_node_id;

			case "crs":
				#return "repository.php?ref_id=".$a_node_id."&cmdClass=ilobjcoursegui&cmd=view";
				return "ilias.php?baseClass=ilshopcontroller&ref_id=".$a_node_id;

			case "crsr":
				#include_once('./Services/ContainerReference/classes/class.ilContainerReference.php');
				#$t_obj_id = ilContainerReference::_lookupTargetId(ilObject::_lookupObjId($a_node_id));
				#$ref_ids = ilObject::_getAllReferences($t_obj_id);
				#$a_node_id = current($ref_ids);
				#return "repository.php?cmd=redirect&ref_id=".$a_node_id;
				return "ilias.php?cmd=redirect&baseClass=ilshopcontroller&ref_id=".$a_node_id;

			case 'crsg':
				return "repository.php?ref_id=".$a_node_id;

			case 'webr':
				return "ilias.php?baseClass=ilLinkResourceHandlerGUI&ref_id=".$a_node_id;

			case "icrs":
				return "repository.php?ref_id=".$a_node_id."&cmdClass=ilobjilinccoursegui";

			case 'rcrs':
				return "repository.php?cmd=infoScreen&ref_id=".$a_node_id;

			default:
				include_once('classes/class.ilLink.php');
				return ilLink::_getStaticLink($a_node_id, $a_type, true);

		}
	}

	/**
	* note: this method is not used by repository explorer any more
	* but still by ilCourseContentInterface (should be redesigned)
	*/
	function buildEditLinkTarget($a_node_id, $a_type)
	{
		global $ilCtrl;

		switch($a_type)
		{
			case "cat":
				return "repository.php?cmd=edit&ref_id=".$a_node_id;

			case "catr":
				return "repository.php?cmd=edit&ref_id=".$a_node_id;

			case "lm":
			case "dbk":
				return "ilias.php?baseClass=ilLMEditorGUI&amp;ref_id=".$a_node_id;

			case "htlm":
				return "ilias.php?baseClass=ilHTLMEditorGUI&amp;ref_id=".$a_node_id;

			case "sahs":
				return "ilias.php?baseClass=ilSAHSEditGUI&ref_id=".$a_node_id;

			case "mep":
				return "ilias.php?baseClass=ilMediaPoolPresentationGUI&ref_id=".$a_node_id;

			case "grp":
				return; // following link is the same as "read" link
				return "repository.php?ref_id=".$a_node_id."&cmdClass=ilobjgroupgui";

			case "crs":
				return "repository.php?ref_id=".$a_node_id."&cmdClass=ilobjcoursegui&cmd=edit";

			case "crsr":
				return "repository.php?ref_id=".$a_node_id;

			case "frm":
				return "repository.php?cmd=edit&ref_id=".$a_node_id;

			case "glo":
				return "ilias.php?baseClass=ilGlossaryEditorGUI&ref_id=".$a_node_id;

			case "exc":
				return "ilias.php?baseClass=ilExerciseHandlerGUI&cmd=view&ref_id=".$a_node_id;

			case "fold":
				return "repository.php?cmd=edit&ref_id=".$a_node_id;

			case "file":
				return "repository.php?cmd=edit&cmdClass=ilobjfilegui&ref_id=".$a_node_id;

			case "sess":
				return "repository.php?cmd=edit&ref_id=".$a_node_id;

			case 'tst':
				return "ilias.php?baseClass=ilObjTestGUI&ref_id=".$a_node_id;
				#return "assessment/test.php?ref_id=".$a_node_id;
/*
			case 'svy':
				return "survey/survey.php?ref_id=".$a_node_id;

			case 'qpl':
				return "assessment/questionpool.php?ref_id=".$a_node_id
					."&cmd=questions";

			case 'spl':
				return "survey/questionpool.php?ref_id=".$a_node_id
					."&cmd=questions";

			case 'svy':
				return "survey/survey.php?ref_id=".$a_node_id;
*/
			case 'crsg':
				return "repository.php?cmd=edit&ref_id=".$a_node_id;

			case 'webr':
				return "ilias.php?baseClass=ilLinkResourceHandlerGUI&cmd=editItems&ref_id=".$a_node_id;

			case 'rcrs':
				return "repository.php?cmd=infoScreen&ref_id=".$a_node_id;

		}
	}

	/**
	* get image path
	*/
	function getImage($a_name, $a_type = "", $a_obj_id = "")
	{
		if ($a_type != "")
		{
			return ilObject::_getIcon($a_obj_id, "tiny", $a_type);
		}

		return parent::getImage($a_name);
	}

	function isClickable($a_type, $a_ref_id,$a_obj_id = 0)
	{
		global $rbacsystem,$tree,$ilDB,$ilUser;

		if(!ilConditionHandler::_checkAllConditionsOfTarget($a_ref_id,$a_obj_id))
		{
			return false;
		}

		switch ($a_type)
		{
			case "crs":
				include_once './Modules/Course/classes/class.ilObjCourse.php';

				// Has to be replaced by ilAccess calls
				if(!ilObjCourse::_isActivated($a_obj_id) and !$rbacsystem->checkAccess('write',$a_ref_id))
				{
					return false;
				}

				include_once './Modules/Course/classes/class.ilCourseParticipants.php';

				if(ilCourseParticipants::_isBlocked($a_obj_id,$ilUser->getId()))
				{
					return false;
				}
				if(($rbacsystem->checkAccess('join',$a_ref_id) or
					$rbacsystem->checkAccess('read',$a_ref_id)))
				{
					return true;
				}
				return false;

			// visible groups can allways be clicked; group processing decides
			// what happens next
			case "grp":
				return true;
				break;

			case 'tst':
				if(!$rbacsystem->checkAccess("read", $a_ref_id))
				{
					return false;
				}

				$query = sprintf("SELECT * FROM tst_tests WHERE obj_fi=%s",$a_obj_id);
				$res = $ilDB->query($query);
				while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
				{
					return (bool) $row->complete;
				}
				return false;

			case 'svy':
				if(!$rbacsystem->checkAccess("read", $a_ref_id))
				{
					return false;
				}

				$query = sprintf("SELECT * FROM svy_svy WHERE obj_fi=%s",$a_obj_id);
				$res = $ilDB->query($query);
				while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
				{
					return (bool) $row->complete;
				}
				return false;

			// media pools can only be edited
			case "mep":
				if ($rbacsystem->checkAccess("read", $a_ref_id))
				{
					return true;
				}
				else
				{
					return false;
				}
				break;

			case 'crsr':
			case 'catr':
				include_once('./Services/ContainerReference/classes/class.ilContainerReferenceAccess.php');
				return ilContainerReferenceAccess::_isAccessible($a_ref_id);


			// all other types are only clickable, if read permission is given
			default:
				if ($rbacsystem->checkAccess("read", $a_ref_id))
				{
					// check if lm is online
					if ($a_type == "lm")
					{
						include_once("./Modules/LearningModule/classes/class.ilObjLearningModule.php");
						$lm_obj =& new ilObjLearningModule($a_ref_id);
						if((!$lm_obj->getOnline()) && (!$rbacsystem->checkAccess('write',$a_ref_id)))
						{
							return false;
						}
					}
					// check if fblm is online
					if ($a_type == "htlm")
					{
						include_once("./Modules/HTMLLearningModule/classes/class.ilObjFileBasedLM.php");
						$lm_obj =& new ilObjFileBasedLM($a_ref_id);
						if((!$lm_obj->getOnline()) && (!$rbacsystem->checkAccess('write',$a_ref_id)))
						{
							return false;
						}
					}
					// check if fblm is online
					if ($a_type == "sahs")
					{
						include_once("./Modules/ScormAicc/classes/class.ilObjSAHSLearningModule.php");
						$lm_obj =& new ilObjSAHSLearningModule($a_ref_id);
						if((!$lm_obj->getOnline()) && (!$rbacsystem->checkAccess('write',$a_ref_id)))
						{
							return false;
						}
					}
					// check if glossary is online
					if ($a_type == "glo")
					{
						$obj_id = ilObject::_lookupObjectId($a_ref_id);
						include_once("./Modules/Glossary/classes/class.ilObjGlossary.php");
						if((!ilObjGlossary::_lookupOnline($obj_id)) &&
							(!$rbacsystem->checkAccess('write',$a_ref_id)))
						{
							return false;
						}
					}

					return true;
				}
				else
				{
					return false;
				}
				break;
		}
	}

	function showChilds($a_ref_id,$a_obj_id = 0)
	{
		global $rbacsystem,$tree;

		if ($a_ref_id == 0)
		{
			return true;
		}
		if(!ilConditionHandler::_checkAllConditionsOfTarget($a_ref_id,$a_obj_id))
		{
			return false;
		}
		if ($rbacsystem->checkAccess("read", $a_ref_id))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function isVisible($a_ref_id,$a_type)
	{
		global $rbacsystem,$tree;

		if(!$rbacsystem->checkAccess('visible',$a_ref_id))
		{
			return false;
		}
		if($crs_id = $tree->checkForParentType($a_ref_id,'crs'))
		{
			if(!$rbacsystem->checkAccess('write',$crs_id))
			{
				// Show only activated courses
				$tmp_obj =& ilObjectFactory::getInstanceByRefId($crs_id,false);

				if(!$tmp_obj->isActivated())
				{
					unset($tmp_obj);
					return false;
				}
				if(($crs_id != $a_ref_id) and $tmp_obj->isArchived())
				{
					return false;
				}
				// Show only activated course items
				include_once "./Modules/Course/classes/class.ilCourseItems.php";

				if(($crs_id != $a_ref_id) and (!ilCourseItems::_isActivated($a_ref_id)))
				{
					return false;
				}
			}
		}
		return true;
	}

	/**
	* overwritten method from base class
	* @access	public
	* @param	integer obj_id
	* @param	integer array options
	* @return	string
	*/
	function formatHeader(&$tpl, $a_obj_id,$a_option)
	{
		global $lng, $ilias, $tree;

		// custom icons
		/*
		if ($this->ilias->getSetting("custom_icons"))
		{
			require_once("./Services/Container/classes/class.ilContainer.php");
			if (($path = ilContainer::_lookupIconPath($a_obj_id, "tiny")) == "")
			{
				$path = ilUtil::getImagePath("icon_root_s.gif");
			}
		}*/

		$path = ilObject::_getIcon($a_obj_id, "tiny", "root");

		$tpl->setCurrentBlock("icon");
		$nd = $tree->getNodeData(ROOT_FOLDER_ID);
		$title = $nd["title"];

		if ($title == "ILIAS")
		{
			$title = $lng->txt("repository");
		}

		$tpl->setVariable("ICON_IMAGE", $path);
		$tpl->setVariable("TXT_ALT_IMG", $lng->txt("icon")." ".$title);
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("link");
		$tpl->setVariable("TITLE", $title);
		$tpl->setVariable("LINK_TARGET", "ilias.php?baseClass=ilshopcontroller&ref_id=1");

		#$tpl->setVariable("TARGET", " target=\"_self\"");
		$tpl->parseCurrentBlock();

		$tpl->setCurrentBlock("element");
		$tpl->parseCurrentBlock();
	}

	/**
	 * sort nodes
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function sortNodes($a_nodes,$a_parent_obj_id)
	{
		global $objDefinition;

		if ($a_parent_obj_id > 0)
		{
			$parent_type = ilObject::_lookupType($a_parent_obj_id);
		}
		else
		{
			$parent_type  = "dummy";
			$this->type_grps["dummy"] = array("root" => "dummy");
		}

		if (empty($this->type_grps[$parent_type]))
		{
			$this->type_grps[$parent_type] =
				$objDefinition->getGroupedRepositoryObjectTypes($parent_type);
		}
		$group = array();

		foreach ($a_nodes as $node)
		{
			$g = $objDefinition->getGroupOfObj($node["type"]);
			if ($g == "")
			{
				$g = $node["type"];
			}
			$group[$g][] = $node;
		}

		$nodes = array();
		foreach ($this->type_grps[$parent_type] as $t => $g)
		{
			if (is_array($group[$t]))
			{
				// do we have to sort this group??
				include_once("./Services/Container/classes/class.ilContainer.php");
				include_once("./Services/Container/classes/class.ilContainerSorting.php");
				$sort = ilContainerSorting::_getInstance($a_parent_obj_id);
				$group = $sort->sortItems($group);

				foreach ($group[$t] as $k => $item)
				{
					$nodes[] = $item;
				}
			}
		}

		return $nodes;
		//return parent::sortNodes($a_nodes,$a_parent_obj_id);
	}

	/**
	 * Force expansion of node
	 *
	 * @param
	 * @return
	 */
	function forceExpanded($a_node)
	{
		if (in_array($a_node, $this->force_open_path))
		{
			return true;
		}
		return false;
	}


} 
?>
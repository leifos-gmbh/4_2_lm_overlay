<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once './Services/User/classes/class.ilObjUser.php';
require_once 'Services/Mail/classes/class.ilMailbox.php';
require_once 'Services/Mail/classes/class.ilFormatMail.php';
require_once 'Services/Contact/classes/class.ilAddressbook.php';

/**
* @author Jens Conze
* @version $Id: class.ilMailSearchGroupsGUI.php 35638 2012-07-19 10:43:19Z jluetzen $
*
* @ingroup ServicesMail
*/
class ilMailSearchGroupsGUI
{
	private $tpl = null;
	private $ctrl = null;
	private $lng = null;
	
	private $umail = null;
	private $abook = null;

	protected $mailing_allowed;

	public function __construct($wsp_access_handler = null, $wsp_node_id = null)
	{
		global $tpl, $ilCtrl, $lng, $ilUser, $rbacsystem;

		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		
		// personal workspace
		$this->wsp_access_handler = $wsp_access_handler;
		$this->wsp_node_id = $wsp_node_id;
		
		$this->ctrl->saveParameter($this, "mobj_id");
		$this->ctrl->saveParameter($this, "ref");

		// check if current user may send mails
		include_once "Services/Mail/classes/class.ilMail.php";
		$mail = new ilMail($_SESSION["AccountId"]);
		$this->mailing_allowed = $rbacsystem->checkAccess('mail_visible',$mail->getMailObjectReferenceId());

		$this->umail = new ilFormatMail($ilUser->getId());
		$this->abook = new ilAddressbook($ilUser->getId());
	}

	public function executeCommand()
	{
		$forward_class = $this->ctrl->getNextClass($this);
		switch($forward_class)
		{
			default:
				if (!($cmd = $this->ctrl->getCmd()))
				{
					$cmd = "showMyGroups";
				}

				$this->$cmd();
				break;
		}
		return true;
	}

	function mail()
	{
		global $ilUser, $lng;

		if ($_GET["view"] == "mygroups")
		{
			$ids = ((int) $_GET['search_grp']) ? array((int)$_GET['search_grp']) : $_POST['search_grp'];
			if ($ids)
			{
				$this->mailGroups();
			}
			else
			{
				ilUtil::sendInfo($lng->txt("mail_select_group"));
				$this->showMyGroups();
			}
		}
		else if ($_GET["view"] == "grp_members")
		{
			$ids = ((int) $_GET['search_members']) ? array((int)$_GET['search_members']) : $_POST['search_members'];
			if ($ids)
			{
				$this->mailMembers();
			}
			else
			{
				ilUtil::sendInfo($lng->txt("mail_select_one_entry"));
				$this->showMembers();
			}
		}
		else
		{
			$this->showMyGroups();
		}
	}

	function mailGroups()
	{
		global $ilUser, $lng, $rbacreview;

		$members = array();

		if (!is_array($old_mail_data = $this->umail->getSavedData()))
		{
			$this->umail->savePostData(
				$ilUser->getId(),
				array(),
				"",
				"",
				"",
				"",
				"",
				"",
				"",
				""
			);
		}
		
		require_once 'classes/class.ilObject.php';
		$ids = ((int) $_GET['search_grp']) ? array((int)$_GET['search_grp']) : $_POST['search_grp'];  
		foreach ($ids as $grp_id)
		{
			$ref_ids = ilObject::_getAllReferences($grp_id);
			foreach ($ref_ids as $ref_id)
			{
				$roles = $rbacreview->getAssignableChildRoles($ref_id);
				foreach ($roles as $role)
				{
					if (substr($role['title'], 0, 14) == 'il_grp_member_' ||
					    substr($role['title'], 0, 13) == 'il_grp_admin_')
					{
						if(isset($old_mail_data['rcp_to']) && 
						   trim($old_mail_data['rcp_to']) != '')
						{
							$rcpt = $rbacreview->getRoleMailboxAddress($role['obj_id']);
						
							if(!$this->umail->doesRecipientStillExists($rcpt, $old_mail_data['rcp_to']))							
								// does not work if Pear is enabled and Mailbox Address contain special chars!!	
//								array_push($members, $rcpt);	
								// FIX for Mantis: 7523, 8061
								array_push($members, '#'.$role['title']);
							
							unset($rcpt);
						}
						else
						{
							// does not work if Pear is enabled and Mailbox Address contain special chars!!	
//							array_push($members, $rbacreview->getRoleMailboxAddress($role['obj_id']));
							// FIX for Mantis: 7523, 8061
							array_push($members, '#'.$role['title']);
						}					
					}
				}
			}
		}
		
		if(count($members))
			$mail_data = $this->umail->appendSearchResult($members, 'to');
		else
			$mail_data = $this->umail->getSavedData();

		$this->umail->savePostData(
			$mail_data["user_id"],
			$mail_data["attachments"],
			$mail_data["rcp_to"],
			$mail_data["rcp_cc"],
			$mail_data["rcp_bcc"],
			$mail_data["m_type"],
			$mail_data["m_email"],
			$mail_data["m_subject"],
			$mail_data["m_message"],
			$mail_data["use_placeholders"]
		);
		
		ilUtil::redirect("ilias.php?baseClass=ilMailGUI&type=search_res");
	}

	function mailMembers()
	{
		global $ilUser;

		$members = array();

		if (!is_array($this->umail->getSavedData()))
		{
			$this->umail->savePostData(
				$ilUser->getId(),
				array(),
				"",
				"",
				"",
				"",
				"",
				"",
				"",
				""
			);
		}
		
		$ids = ((int) $_GET['search_members']) ? array((int)$_GET['search_members']) : $_POST['search_members'];
		
		foreach ($ids as $member)
		{
			$login = ilObjUser::_lookupLogin($member);
			array_push($members, $login);
		}
		$mail_data = $this->umail->appendSearchResult($members,"to");

		$this->umail->savePostData(
			$mail_data["user_id"],
			$mail_data["attachments"],
			$mail_data["rcp_to"],
			$mail_data["rcp_cc"],
			$mail_data["rcp_bcc"],
			$mail_data["m_type"],
			$mail_data["m_email"],
			$mail_data["m_subject"],
			$mail_data["m_message"],
			$mail_data["use_placeholders"]
		);
	
		ilUtil::redirect("ilias.php?baseClass=ilMailGUI&type=search_res");
	}

	/**
	 * Take over course members to addressbook
	 */
	public function adoptMembers()
	{
		global $lng;

		$ids = ((int)$_GET['search_members']) ? array((int)$_GET['search_members']) : $_POST['search_members']; 
		
		if ($ids )
		{
			$members = array();
		
			foreach ($ids as $member)
			{
				$login = ilObjUser::_lookupLogin($member);
	
				if (!$this->abook->checkEntry($login))
				{
					$name = ilObjUser::_lookupName($member);
					$email = '';
					if(ilObjUser::_lookupPref((int)$member, 'public_email') == 'y')
					{
						$email = ilObjUser::_lookupEmail($member);	
					}					
					$this->abook->addEntry(
						$login,
						$name["firstname"],
						$name["lastname"],
						$email
					);
				}
			}
			ilUtil::sendInfo($lng->txt("mail_members_added_addressbook"));
		}
		else
		{
			ilUtil::sendInfo($lng->txt("mail_select_one_entry"));
		}

		$this->showMembers();
	}
	
	/**
	 * Cancel action
	 */
	function cancel()
	{
		if ($_GET["view"] == "mygroups" &&
			$_GET["ref"] == "mail")
		{
			$this->ctrl->returnToParent($this);	
		}
		else
		{
			$this->showMyGroups();
		}
	}
	
	/**
	 * Show user's courses
	 */
	public function showMyGroups()
	{
		global $lng, $ilUser, $ilObjDataCache, $tree;

		include_once 'Modules/Group/classes/class.ilGroupParticipants.php';

		$this->tpl->setTitle($this->lng->txt('mail'));

		$searchTpl = new ilTemplate('tpl.mail_search_template.html', true, true, 'Services/Contact');
		
		$_GET['view'] = 'mygroups';

		$lng->loadLanguageModule('crs');
		
		$this->ctrl->setParameter($this, 'view', 'mygroups');
		
		include_once 'Services/Contact/classes/class.ilMailSearchCoursesTableGUI.php';
		$table = new ilMailSearchCoursesTableGUI($this, 'grp', $_GET["ref"]);
		$table->setId('search_grps_tbl');
		$grp_ids = ilGroupParticipants::_getMembershipByType($ilUser->getId(), 'grp');
		
		$counter = 0;
		$tableData = array();
		if (is_array($grp_ids) &&
			count($grp_ids) > 0)
		{				
	
			include_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
			foreach($grp_ids as $grp_id) 
			{
				if(ilObject::_hasUntrashedReference($grp_id))
				{
					$oGroupParticipants = ilGroupParticipants::_getInstanceByObjId($grp_id);
					$grp_members = $oGroupParticipants->getParticipants();

					foreach ($grp_members as $key => $member)
					{
						$tmp_usr = new ilObjUser($member);
						
						if($tmp_usr->checkTimeLimit()== false || $tmp_usr->getActive() == false )
						{
							unset($grp_members[$key]);
						}			
					}
					unset($tmp_usr);

					$ref_ids = ilObject::_getAllReferences($grp_id);
					$ref_id = current($ref_ids);				
					$path_arr = $tree->getPathFull($ref_id, $tree->getRootId());
					$path_counter = 0;
					$path = '';
					foreach($path_arr as $data)
					{
						if($path_counter++)
						{
							$path .= " -> ";
						}
						$path .= $data['title'];
					}
					$path = $this->lng->txt('path').': '.$path;
					
					$current_selection_list = new ilAdvancedSelectionListGUI();
					$current_selection_list->setListTitle($this->lng->txt("actions"));
					$current_selection_list->setId("act_".$counter);

					$this->ctrl->setParameter($this, 'search_grp', $grp_id);
					$this->ctrl->setParameter($this, 'view', 'mygroups');
					
					if($_GET["ref"] == "mail")
					{
						if ($this->mailing_allowed)
							$current_selection_list->addItem($this->lng->txt("mail_members"), '', $this->ctrl->getLinkTarget($this, "mail"));
					}
					else if($_GET["ref"] == "wsp")
					{
						$current_selection_list->addItem($this->lng->txt("wsp_share_with_members"), '', $this->ctrl->getLinkTarget($this, "share"));
					}
					$current_selection_list->addItem($this->lng->txt("mail_list_members"), '', $this->ctrl->getLinkTarget($this, "showMembers"));
					
					$this->ctrl->clearParameters($this);
					
					$rowData = array
					(
						'CRS_ID' => $grp_id,
						'CRS_NAME' => $ilObjDataCache->lookupTitle($grp_id),
						'CRS_NO_MEMBERS' => count($grp_members),
						'CRS_PATH' => $path,
						'COMMAND_SELECTION_LIST' => $current_selection_list->getHTML()
					);
					$counter++;
					$tableData[] = $rowData;
				}
			}
		}
		$table->setData($tableData);
		if($counter > 0)
		{
			$this->tpl->setVariable('TXT_MARKED_ENTRIES',$lng->txt('marked_entries'));
		}
		
		$searchTpl->setVariable('TABLE', $table->getHtml());
		$this->tpl->setContent($searchTpl->get());
		
		if($_GET["ref"] != "wsp")
		{		
			$this->tpl->show();
		}
	}

	/**
	 * Show course members
	 */
	public function showMembers()
	{
		global $lng, $ilUser;

		if ($_GET["search_grp"] != "")
		{
			$_POST["search_grp"] = explode(",", $_GET["search_grp"]);
		}

		if (!is_array($_POST["search_grp"]) ||
			count($_POST["search_grp"]) == 0)
		{
			ilUtil::sendInfo($lng->txt("mail_select_group"));
			$this->showMyGroups();
		}
		else
		{
			$this->tpl->setTitle($this->lng->txt("mail"));
			include_once 'Services/Contact/classes/class.ilMailSearchCoursesMembersTableGUI.php';
			$context = $_GET["ref"] ? $_GET["ref"] : "mail"; 	
			$table = new ilMailSearchCoursesMembersTableGUI($this, 'grp', $context);
			$table->setId('show_grps_mmbrs_tbl');
			$lng->loadLanguageModule('crs');
	
			$counter = 0;
			$tableData = array();
			$searchTpl = new ilTemplate('tpl.mail_search_template.html', true, true, 'Services/Contact');
			
			foreach($_POST["search_grp"] as $grp_id) 
			{
				$ref_ids = ilObject::_getAllReferences($grp_id);
				$ref_id = current($ref_ids);

				if (is_object($group_obj = ilObjectFactory::getInstanceByRefId($ref_id,false)))
				{
					$grp_members = $group_obj->getGroupMemberData($group_obj->getGroupMemberIds());

					foreach($grp_members as $member)
					{ 
						$tmp_usr = new ilObjUser($member['id']);
						if($tmp_usr->checkTimeLimit()== false || $tmp_usr->getActive() == false )
						{
							unset($tmp_usr);
							continue;
						}
						unset($tmp_usr);
						
						$fullname = "";
						if(in_array(ilObjUser::_lookupPref($member['id'], 'public_profile'), array("g", 'y')))
							$fullname = $member['lastname'].', '.$member['firstname'];

						$rowData = array(
							'MEMBERS_ID' => $member["id"],
							'MEMBERS_LOGIN' => $member["login"],
							'MEMBERS_NAME' => $fullname,
							'MEMBERS_CRS_GRP' => $group_obj->getTitle(),
							'MEMBERS_IN_ADDRESSBOOK' => $this->abook->checkEntryByLogin($member["login"]) ? $lng->txt("yes") : $lng->txt("no"),
							'search_grp' => $grp_id
						);
						$tableData[] = $rowData;
					}
				}
			}
			$table->setData($tableData);
			if (count($tableData))
			{
				//$table->addCommandButton('mail', $lng->txt("grp_mem_send_mail"));
				//$table->addCommandButton('adoptMembers', $lng->txt("mail_into_addressbook"));
				$searchTpl->setVariable("TXT_MARKED_ENTRIES",$lng->txt("marked_entries"));
			}
			$searchTpl->setVariable('TABLE', $table->getHtml());
			$this->tpl->setContent($searchTpl->get());
			
			if($_GET["ref"] != "wsp")
			{	
				$this->tpl->show();
			}
		}
	}

	function share()
	{
		global $lng;
		
		if ($_GET["view"] == "mygroups")
		{
			$ids = $_REQUEST["search_grp"];
			if (sizeof($ids))
			{
				$this->addPermission($ids);
			}
			else
			{
				ilUtil::sendInfo($lng->txt("mail_select_course"));
				$this->showMyGroups();
			}
		}
		else if ($_GET["view"] == "grp_members")
		{
			$ids = $_REQUEST["search_members"];
			if (sizeof($ids))
			{
				$this->addPermission($ids);
			}
			else
			{
				ilUtil::sendInfo($lng->txt("mail_select_one_entry"));
				$this->showMembers();
			}
		}
		else
		{
			$this->showMyGroups();
		}
	}
	
	protected function addPermission($a_obj_ids)
	{
		if(!is_array($a_obj_ids))
		{
			$a_obj_ids = array($a_obj_ids);
		}
		
		$existing = $this->wsp_access_handler->getPermissions($this->wsp_node_id);
		$added = false;
		foreach($a_obj_ids as $object_id)
		{
			if(!in_array($object_id, $existing))
			{
				$added = $this->wsp_access_handler->addPermission($this->wsp_node_id, $object_id);
			}
		}
		
		if($added)
		{
			ilUtil::sendSuccess($this->lng->txt("wsp_share_success"), true);
		}
		$this->ctrl->redirectByClass("ilworkspaceaccessgui", "share");		
	}
}

?>

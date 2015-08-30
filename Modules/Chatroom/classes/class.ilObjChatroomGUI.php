<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once "./classes/class.ilObjectGUI.php";
require_once "./Modules/Chatroom/classes/class.ilObjChatroom.php";
require_once "./Modules/Chatroom/classes/class.ilChatroom.php";
require_once "./Modules/Chatroom/classes/class.ilObjChatroomAccess.php";
require_once 'Modules/Chatroom/lib/DatabayHelper/databayHelperLoader.php';

/**
 * Class ilObjChatroomGUI
 *
 * GUI class for chatroom objects.
 *
 * @author Jan Posselt <jposselt at databay.de>
 * @version $Id$
 *
 * @ilCtrl_Calls ilObjChatroomGUI: ilMDEditorGUI, ilInfoScreenGUI, ilPermissionGUI, ilObjectCopyGUI
 * @ilCtrl_Calls ilObjChatroomGUI: ilExportGUI
 * @ilCtrl_IsCalledBy ilObjChatroomGUI: ilRepositoryGUI, ilpersonaldesktopgui, iladministrationgui, ilobjrootfoldergui
 *
 * @ingroup ModulesChatroom
 */
class ilObjChatroomGUI extends ilDBayObjectGUI
{

	/**
	 * Constructor
	 *
	 * @param array $a_data
	 * @param integer $a_id
	 * @param boolean $a_call_by_reference
	 */
	public function __construct($a_data = null, $a_id = null, $a_call_by_reference = true)
	{
		if (in_array($_REQUEST['cmd'], array('getOSDNotifications','removeOSDNotifications'))) {
			require_once 'Services/Notifications/classes/class.ilNotificationGUI.php';
			$notifications = new ilNotificationGUI();
			$notifications->{$_REQUEST['cmd'] . 'Object'}();
			exit;
		}


		if( $a_data == null )
		{
			if( $_GET['serverInquiry'] )
			{
				require_once dirname( __FILE__ ) . '/class.ilChatroomServerHandler.php';
				new ilChatroomServerHandler();
				return;
			}
		}
		/*require_once 'Modules/Chatroom/classes/class.ilChatroomServerConnector.php';
		 var_dump(  ilChatroomServerConnector::checkServerConnection());*/
		$this->type = 'chtr';
		$this->ilObjectGUI( $a_data, $a_id, $a_call_by_reference, false );
		$this->lng->loadLanguageModule( 'chatroom' );
		$this->lng->loadLanguageModule( 'chatroom_adm' );
	}

	/**
	 * Returns object definition by calling getDefaultDefinition method
	 * in ilDBayObjectDefinition.
	 *
	 * @return ilDBayObjectDefinition
	 */
	protected function getObjectDefinition()
	{
		return ilDBayObjectDefinition::getDefaultDefinition( 'Chatroom' );
	}

	/**
	 * Returns an empty array.
	 *
	 * @return array
	 */
	public function _forwards()
	{
		return array();
	}

	/**
	 * Dispatches the command to the related executor class.
	 *
	 * @global ilCtrl2 $ilCtrl
	 */
	public function executeCommand()
	{
		global $ilAccess, $ilNavigationHistory, $ilCtrl;

		if ('cancel' == $ilCtrl->getCmd() && $this->getCreationMode()) {
		    parent::cancelCreation();
		    return;
		}

		// add entry to navigation history
		if(!$this->getCreationMode() && $ilAccess->checkAccess('read', '', $_GET['ref_id']))
		{
			$ilNavigationHistory->addItem($_GET['ref_id'], './goto.php?target=' . $this->type . '_' . $_GET['ref_id'], $this->type);
		}
		
		$next_class = $ilCtrl->getNextClass();

		require_once 'Modules/Chatroom/classes/class.ilChatroomTabFactory.php';
		if (!$this->getCreationMode()) {
		    $tabFactory = new ilChatroomTabFactory( $this );
		    
		    if(strtolower($_GET["baseClass"]) == "iladministrationgui") {
			$tabFactory->getAdminTabsForCommand( $ilCtrl->getCmd() );
		    }
		    else {
			$tabFactory->getTabsForCommand( $ilCtrl->getCmd() );
		    }
		}
		
		// #8701 - infoscreen actions
		if($next_class == "ilinfoscreengui" && $ilCtrl->getCmd() != "info")
		{
			$ilCtrl->setCmd("info-".$ilCtrl->getCmd());
		}
		// repository info call
		if($ilCtrl->getCmd() == "infoScreen")
		{
			$ilCtrl->setCmdClass("ilinfoscreengui");
			$ilCtrl->setCmd("info"); 
		}

		switch($next_class)
		{								
			case 'ilpermissiongui':
				include_once("Services/AccessControl/classes/class.ilPermissionGUI.php");
				$this->prepareOutput();
				$perm_gui = & new ilPermissionGUI( $this );
				$ret = & $this->ctrl->forwardCommand( $perm_gui );
				break;
			case 'ilobjectcopygui':
				$this->prepareOutput();
				include_once './Services/Object/classes/class.ilObjectCopyGUI.php';
				$cp = new ilObjectCopyGUI($this);
				$cp->setType('chtr');
				$this->ctrl->forwardCommand($cp);
				break;
			default:
				try
				{
					$res = split( '-', $ilCtrl->getCmd(), 2 );
					$this->dispatchCall( $res[0], $res[1] ? $res[1] : '' );
				}
				catch (Exception $e)
				{
					$error = array(
						'success' => false,
						'reason' => $e->getMessage()
					);
					echo json_encode($error);
					exit;
				}

		}
	}

	/**
	 * Returns default connector for this room.
	 *
	 * @return ilChatroomServerConnector
	 */
	public function getConnector()
	{
		require_once 'Modules/Chatroom/classes/class.ilChatroomServerConnector.php';
		require_once 'Modules/Chatroom/classes/class.ilChatroomServerSettings.php';
		require_once 'Modules/Chatroom/classes/class.ilChatroomAdmin.php';
		//require_once 'Modules/Chatroom/classes/class.ilChatroomInstaller.php';
		//		ilChatroomInstaller::install();

		//$settings = new ilChatroomServerSettings();
		$settings	= ilChatroomAdmin::getDefaultConfiguration()->getServerSettings();
		$connector	= new ilChatroomServerConnector( $settings );

		return $connector;
	}

	/**
	 * Calls $this->prepareOutput method and sets template variable.
	 */
	public function fallback()
	{
		$this->prepareOutput();
		$this->tpl->setVariable( 'ADM_CONTENT', $this->lng->txt( 'invalid_operation' ) );
	}

	/**
	 * Calls prepareOutput method.
	 */
	public function settings()
	{
		$this->prepareOutput();
	}

	/**
	 * Instantiates, prepares and returns object.
	 *
	 * $class_name = "ilObj" . $objDefinition->getClassName( $new_type ).
	 * Fetches title from $_POST["title"], description from $_POST["desc"]
	 * and RefID from $_GET["ref_id"].
	 *
	 * @global ilRbacSystem $rbacsystem
	 * @global ilObjectDefinition $objDefinition
	 * @global ilRbacReview $rbacreview
	 * @return class_name
	 */
	public function insertObject()
	{
		global $rbacsystem, $objDefinition, $rbacreview;

		$new_type = $this->type;

		// create permission is already checked in createObject.
		// This check here is done to prevent hacking attempts
		if( !$rbacsystem->checkAccess( "create", $_GET["ref_id"], $new_type ) )
		{
			$this->ilias->raiseError(
			$this->lng->txt( "no_create_permission" ),
			$this->ilias->error_obj->MESSAGE
			);
		}

		$location = $objDefinition->getLocation( $new_type );

		// create and insert object in objecttree
		$class_name = "ilObj" . $objDefinition->getClassName( $new_type );
		include_once($location . "/class." . $class_name . ".php");

		$newObj = new $class_name();
		$newObj->setType( $new_type );
		$newObj->setTitle( ilUtil::stripSlashes( $_POST["title"] ) );
		$newObj->setDescription( ilUtil::stripSlashes( $_POST["desc"] ) );
		$newObj->create();
		$newObj->createReference();
		$newObj->putInTree( $_GET["ref_id"] );
		$newObj->setPermissions( $_GET["ref_id"] );

		$objId = $newObj->getId();

		$room = new ilChatroom();

		$room->saveSettings(
			array(
			'object_id' 			=> $objId,
			'autogen_usernames'		=> 'Autogen #',
			'display_past_msgs'		=> 20,
			'private_rooms_enabled'		=> 0
		));

		// rbac log
		include_once "Services/AccessControl/classes/class.ilRbacLog.php";
		$rbac_log_roles = $rbacreview->getParentRoleIds( $newObj->getRefId(), false );
		$rbac_log = ilRbacLog::gatherFaPa( $newObj->getRefId(), array_keys( $rbac_log_roles ), true );
		ilRbacLog::add( ilRbacLog::CREATE_OBJECT, $newObj->getRefId(), $rbac_log );

		$this->object = $newObj;

		return $newObj;
	}

	/**
	 * Returns RefId
	 *
	 * @return integer
	 */
	public function getRefId()
	{
		return $this->object->getRefId();
	}

	/**
	 * Overwrites $_GET['ref_id'] with given $ref_id.
	 *
	 * @global ilCtrl2 $ilCtrl
	 * @param string $params
	 * @param array $res
	 */
	public static function _goto($params)
	{
		/**
		 * @var $ilAccess ilAccessHandler
		 * @var $ilError  ilErrorHandling
		 * @var $lng      ilLanguage
		 */
		global $ilAccess, $ilErr, $lng;

		$parts  = explode('_', $params);
		$ref_id = $parts[0];
		$sub    = $parts[1];

		if($ilAccess->checkAccess('read', '', $ref_id))
		{
			$_GET['cmd']    = 'view';
			$_GET['ref_id'] = $ref_id;
			if($sub)
			{
				$_REQUEST['sub'] = $_GET['sub']     = (int)$sub;
			}
			require 'repository.php';
			exit();
		}
		else if($ilAccess->checkAccess('read', '', ROOT_FOLDER_ID))
		{
			$_GET['target'] = '';
			$_GET['ref_id'] = ROOT_FOLDER_ID;
			ilUtil::sendInfo(sprintf($lng->txt('msg_no_perm_read_item'), ilObject::_lookupTitle(ilObject::_lookupObjId($ref_id))), true);
			include 'repository.php';
			exit();
		}

		$ilErr->raiseError(sprintf($lng->txt('msg_no_perm_read_item'), ilObject::_lookupTitle(ilObject::_lookupObjId($ref_id))), $ilErr->FATAL);
	}

	protected function initCreationForms($a_new_type)
	{
		$forms = parent::initCreationForms($a_new_type);

		unset($forms[self::CFORM_IMPORT]);
		//unset($forms[self::CFORM_CLONE]);
		
		$forms[self::CFORM_NEW]->clearCommandButtons();
		$forms[self::CFORM_NEW]->addCommandButton("create-save", $this->lng->txt($a_new_type."_add"));
		$forms[self::CFORM_NEW]->addCommandButton("cancel", $this->lng->txt("cancel"));
		return $forms;
	}
	
	function addLocatorItems()
	{
		global $ilLocator;
		
		if (is_object($this->object))
		{
			$ilLocator->addItem($this->object->getTitle(), $this->ctrl->getLinkTarget($this, "view"), "", $this->getRefId());
		}
	}
}

?>

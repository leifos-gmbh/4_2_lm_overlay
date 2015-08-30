<#1>
<?php
	// IMPORTANT: Inform the lead developer, if you want to add any steps here.
	//
	// This is the hotfix file for ILIAS 4.1.x DB fixes
	// This file should be used, if bugfixes need DB changes, but the
	// main db update script cannot be used anymore, since it is
	// impossible to merge the changes with the trunk.
	//
	// IMPORTANT: The fixes done here must ALSO BE reflected in the trunk.
	// The trunk needs to work in both cases !!!
	// 1. If the hotfixes have been applied.
	// 2. If the hotfixes have not been applied.
?>
<#2>
<?php
	$setting = new ilSetting();
	$ilrqtix = $setting->get("ilrqtix");
	if (!$ilrqtix)
	{
		$ilDB->addIndex("il_request_token", array("stamp"), "i4");
		$setting->set("ilrqtix", 1);
	}
?>
<#3>
<?php
	$setting = new ilSetting();
	$ilmpathix = $setting->get("ilmpathix");
	if (!$ilmpathix)
	{
		$ilDB->addIndex("mail_attachment", array("path"), "i1");
		$setting->set("ilmpathix", 1);
	}
?>
<#4>
<?php
	$ilCtrlStructureReader->getStructure();
?>
<#5>
<?php
	$ilDB->queryF("UPDATE tst_tests SET pool_usage = %s", array('integer'), array(1));
	
	$setting = new ilSetting();
	$setting->set("ilGlobalTstPoolUsageSettingInitilisation", 1);
?>
<#6>
<?php
	$setting = new ilSetting();
	$ilpghi2 = $setting->get("ilpghi2");
	if (!$ilpghi2)
	{
		$ilDB->addIndex("page_history", array("parent_id", "parent_type", "hdate"), "i2");
		$setting->set("ilpghi2", 1);
	}
?>
<#7>
<?php
	$setting = new ilSetting();
	$ilpgi3 = $setting->get("ilpgi3");
	if (!$ilpgi3)
	{
		$ilDB->addIndex("page_object", array("parent_id", "parent_type", "last_change"), "i3");
		$setting->set("ilpgi3", 1);
	}
?>
<#8>
<?php

	$setting = new ilSetting();
	$ilchtrbacfix = $setting->get("ilchtrbacfix");
	if(!$ilchtrbacfix)
	{
		$result = $ilDB->query(
			'SELECT ops_id 
			FROM rbac_operations 
			WHERE operation = '. $ilDB->quote('create_chat', 'text')
		);
		while ($row = $ilDB->fetchAssoc($result))
		{
			$chat_id = $row['ops_id'];
		}

		$result = $ilDB->query(
			'SELECT ops_id
			FROM rbac_operations
			WHERE operation = ' . $ilDB->quote('create_chtr', 'text')
		);
		while ($row = $ilDB->fetchAssoc($result))
		{
			$chatroom_id = $row['ops_id'];
		}

		if ($chat_id && $chatroom_id)
		{
			$result = $ilDB->query(
				'SELECT * 
				FROM rbac_pa
				WHERE ' . $ilDB->like('ops_id', 'text', '%i:' . $chat_id . ';%')
			);

			$statement = $ilDB->prepareManip(
				'UPDATE rbac_pa 
				SET ops_id = ?
				WHERE rol_id = ?
				AND ref_id = ?',
				array('text', 'integer', 'integer')
			);

			$rows = array();
			while ($row = $ilDB->fetchAssoc($result))
			{
				$rows[] = $row;
			}

			foreach ($rows as $row)
			{
				$ops_arr = unserialize($row['ops_id']);

				if(!$ops_arr)
				{
					continue;
				}

				$key = array_search($chat_id, $ops_arr);
				if(!$key)
				{
					continue;
				}

				$ops_arr[$key] = $chatroom_id;
				$new_ops = serialize($ops_arr);
				$ilDB->execute(
					$statement, 
					array($new_ops, $row['rol_id'], $row['ref_id'])
				);
			}

			$like =  '%s:' . strlen($chat_id) .':"'. $chat_id . '";%';
			$result = $ilDB->query(
				'SELECT * FROM rbac_pa
				WHERE ' . $ilDB->like('ops_id', 'text', $like)
			);

			$rows = array();
			while ($row = $ilDB->fetchAssoc($result))
			{
				$rows[] = $row;
			}

			foreach ($rows as $row)
			{
				$ops_arr = unserialize($row['ops_id']);	
				if(!$ops_arr)
				{
					continue;
				}

				$key = array_search($chat_id, $ops_arr);
				if(!$key)
				{
					continue;
				}

				$ops_arr[$key] = $chatroom_id;
				$new_ops = serialize($ops_arr);
				$ilDB->execute(
					$statement, 
					array($new_ops, $row['rol_id'], $row['ref_id'])
				);
			}
			$ilDB->free($statement);

			$ilDB->manipulate(
				'DELETE
				FROM rbac_operations
				WHERE ops_id = ' . $ilDB->quote($chat_id, 'integer')
			);
			
			$ilDB->manipulate(
				'UPDATE rbac_ta 
				SET ops_id = ' . $ilDB->quote($chatroom_id, 'integer') .'
				WHERE ops_id = ' . $ilDB->quote($chat_id, 'integer')
			);

			$ilDB->manipulate(
				'UPDATE rbac_templates 
				SET ops_id = ' . $ilDB->quote($chatroom_id, 'integer') .'
				WHERE ops_id = ' . $ilDB->quote($chat_id, 'integer')
			);
		}
		
		$setting->set("ilchtrbacfix", 1);
	}
?>
<#9>
<?php	
	$ilDB->manipulate(
		'UPDATE rbac_templates 
		SET type = ' . $ilDB->quote('chtr', 'text') . '
		WHERE type = ' . $ilDB->quote('chat', 'text')
	);
?>
<#10>
<?php
	require_once 'Modules/Chatroom/classes/class.ilChatroom.php';
	require_once 'Modules/Chatroom/classes/class.ilChatroomInstaller.php';
	ilChatroomInstaller::createMissinRoomSettingsForConvertedObjects();
?>
<#11>
<?php

$query = "SELECT obj_id FROM object_data WHERE type = ".$ilDB->quote('typ', 'text').
	" AND title = ".$ilDB->quote('book', 'text');
$res = $ilDB->query($query);
$row = $res->fetchRow();
$typ_id = $row[0];

$query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES (".$ilDB->quote($typ_id, 'integer').
	",".$ilDB->quote(6, 'integer').")";
$ilDB->query($query);

?>
<#12>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#13>
<?php

	$pfpg = array();

	$set = $ilDB->query("SELECT id,rep_obj_id".
				" FROM note".				
				" WHERE obj_type = ".$ilDB->quote("pf", "text").
				" AND obj_id = ".$ilDB->quote(0, "integer"));
	while($nt = $ilDB->fetchAssoc($set))
	{
		// get first page of portfolio
		if(!isset($pfpg[$nt["rep_obj_id"]]))
		{		
			$ilDB->setLimit(1);
			$fset = $ilDB->query("SELECT id".
				" FROM usr_portfolio_page".
				" WHERE portfolio_id  = ".$ilDB->quote($nt["rep_obj_id"], "integer").
				" AND type = ".$ilDB->quote(1, "integer").
				" ORDER BY order_nr ASC");		
			 $first = $ilDB->fetchAssoc($fset);			 
			 $pfpg[$nt["rep_obj_id"]] = $first["id"];
		}
		
		if($pfpg[$nt["rep_obj_id"]] && $nt["id"])
		{
			$ilDB->manipulate("UPDATE note".
				" SET obj_type = ".$ilDB->quote("pfpg", "text").
				", obj_id = ".$ilDB->quote($pfpg[$nt["rep_obj_id"]], "integer").
				" WHERE id = ".$ilDB->quote($nt["id"], "integer"));		
		}
	}
	
	unset($pfpg);
?>
<#14>
<?php
$setting = new ilSetting();
$chtr_perms = $setting->get("ilchtrperms");
if(!$chtr_perms)
{
	global $ilDB;
	
	$sql = 'SELECT obj_id FROM object_data WHERE type = '.$ilDB->quote('typ', 'text').' AND title = '.$ilDB->quote('chtr', 'text');
	$res = $ilDB->query($sql);
	$row = $ilDB->fetchAssoc($res);
	$type_id = $row['obj_id'];
	
	if($type_id)
	{
		foreach(array(6, 99) as $ops_id)
		{
			// analogous to the implementation of jluetzen in 4.3.x (copy_id = 99)
			if($ops_id == 99)
			{
				$sql = 'SELECT ops_id FROM rbac_operations WHERE operation = '.$ilDB->quote('copy', 'text');
				$res = $ilDB->query($sql);
				$row = $ilDB->fetchAssoc($res);
				$ops_id = $row['ops_id'];
				if(!$ops_id)
				{
					continue;
				}
			}
			
			// check if it already exists
			$set = $ilDB->query(
				'SELECT * FROM rbac_ta'.
				' WHERE typ_id = '.$ilDB->quote($type_id, 'integer').
				' AND ops_id = '.$ilDB->quote($ops_id, 'integer')
			);
			if($ilDB->numRows($set))
			{			
				continue;
			}		
			
			$fields = array(
				'typ_id' => array('integer', $type_id),
				'ops_id' => array('integer', $ops_id)
			);
			$ilDB->insert('rbac_ta', $fields);
		}
	}
	
	$setting->set("ilchtrperms", 1);
}
$sql = array();
?>
<#15>
<?php
	// Manual feedback
	/** @var ilDB $ilDB */
	$ilDB->addTableColumn('tst_manual_fb', 'feedback_tmp', array(
									'type' => 'clob',
									'notnull' => false,
									'default' => null)
	);

	$ilDB->manipulate('UPDATE tst_manual_fb SET feedback_tmp = feedback');
	$ilDB->dropTableColumn('tst_manual_fb', 'feedback');
	$ilDB->renameTableColumn('tst_manual_fb', 'feedback_tmp', 'feedback');
?>
<#16>
<?php
	// Suggested Solution
	/** @var ilDB $ilDB */
	$ilDB->addTableColumn('qpl_sol_sug', 'value_tmp', array(
											 'type' => 'clob',
											 'notnull' => false,
											 'default' => null)
	);
	
	$ilDB->manipulate('UPDATE qpl_sol_sug SET value_tmp = value');
	$ilDB->dropTableColumn('qpl_sol_sug', 'value');
	$ilDB->renameTableColumn('qpl_sol_sug', 'value_tmp', 'value');
?>
<#17>
<?php
	// Generic feedback
	/** @var ilDB $ilDB */
	$ilDB->addTableColumn('qpl_fb_generic', 'feedback_tmp', array(
											 'type' => 'clob',
											 'notnull' => false,
											 'default' => null)
	);
	
	$ilDB->manipulate('UPDATE qpl_fb_generic SET feedback_tmp = feedback');
	$ilDB->dropTableColumn('qpl_fb_generic', 'feedback');
	$ilDB->renameTableColumn('qpl_fb_generic', 'feedback_tmp', 'feedback');
?>
<#18>
<?php
	// Feedback Imagemap
	/** @var ilDB $ilDB */
	$ilDB->addTableColumn('qpl_fb_imap', 'feedback_tmp', array(
											  'type' => 'clob',
											  'notnull' => false,
											  'default' => null)
	);
	
	$ilDB->manipulate('UPDATE qpl_fb_imap SET feedback_tmp = feedback');
	$ilDB->dropTableColumn('qpl_fb_imap', 'feedback');
	$ilDB->renameTableColumn('qpl_fb_imap', 'feedback_tmp', 'feedback');
?>
<#19>
<?php
	// Feedback Multiple Choice
	/** @var ilDB $ilDB */
	$ilDB->addTableColumn('qpl_fb_mc', 'feedback_tmp', array(
										   'type' => 'clob',
										   'notnull' => false,
										   'default' => null)
	);
	
	$ilDB->manipulate('UPDATE qpl_fb_mc SET feedback_tmp = feedback');
	$ilDB->dropTableColumn('qpl_fb_mc', 'feedback');
	$ilDB->renameTableColumn('qpl_fb_mc', 'feedback_tmp', 'feedback');
?>
<#20>
<?php
	// Feedback Single Choice
	/** @var ilDB $ilDB */
	$ilDB->addTableColumn('qpl_fb_sc', 'feedback_tmp', array(
										 'type' => 'clob',
										 'notnull' => false,
										 'default' => null)
	);
	
	$ilDB->manipulate('UPDATE qpl_fb_sc SET feedback_tmp = feedback');
	$ilDB->dropTableColumn('qpl_fb_sc', 'feedback');
	$ilDB->renameTableColumn('qpl_fb_sc', 'feedback_tmp', 'feedback');
?>
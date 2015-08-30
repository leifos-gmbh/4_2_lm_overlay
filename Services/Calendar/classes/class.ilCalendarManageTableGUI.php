<?php
/*
        +-----------------------------------------------------------------------------+
        | ILIAS open source                                                           |
        +-----------------------------------------------------------------------------+
        | Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
        |                                                                             |
        | This program is free software; you can redistribute it and/or               |
        | modify it under the terms of the GNU General Public License                 |
        | as published by the Free Software Foundation; either version 2              |
        | of the License, or (at your option) any later version.                      |
        |                                                                             |
        | This program is distributed in the hope that it will be useful,             |
        | but WITHOUT ANY WARRANTY; without even the implied warranty of              |
        | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
        | GNU General Public License for more details.                                |
        |                                                                             |
        | You should have received a copy of the GNU General Public License           |
        | along with this program; if not, write to the Free Software                 |
        | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
        +-----------------------------------------------------------------------------+
*/

include_once('./Services/Table/classes/class.ilTable2GUI.php');

/**
* show list of alle calendars to manage
*
* @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
* @version $Id$
*
* @ingroup ServicesCalendar
*/

class ilCalendarManageTableGUI extends ilTable2GUI
{
	/**
	 * Constructor
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function __construct($a_parent_obj)
	{
	 	global $lng, $ilCtrl, $ilUser;

		$this->setId("calmng");
	 	
	 	$this->lng = $lng;
		$this->lng->loadLanguageModule('dateplaner');
	 	$this->ctrl = $ilCtrl;
	 	
		parent::__construct($a_parent_obj, 'manage');
		$this->setFormName('categories');
	 	$this->addColumn('','','5%', true);
		$this->addColumn($this->lng->txt('type'), '', '10%');
	 	$this->addColumn($this->lng->txt('title'),'title', '50%');
	 	$this->addColumn('','','35%');
	 	
	 	$this->setPrefix('categories');
		$this->setFormAction($this->ctrl->getFormAction($a_parent_obj, "manage"));
		$this->setRowTemplate("tpl.manage_row.html","Services/Calendar");
		$this->enable('select_all');
		$this->setSelectAllCheckbox('selected_cat_ids');
		// $this->setDisplayAsBlock(true);


		/*
		$title = $this->lng->txt('cal_table_categories');
		$title .= $this->appendCalendarSelection();
		$table_gui->setTitle($title);
	    */

	    $this->addMultiCommand('confirmDelete',$this->lng->txt('delete'));
		// $this->addCommandButton('add',$this->lng->txt('add'));

		$this->setDefaultOrderDirection('asc');
		$this->setDefaultOrderField('title');
	}
	
	/**
	 * fill row
	 *
	 * @access protected
	 * @param
	 * @return
	 */
	protected function fillRow($a_set)
	{
		include_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
		$current_selection_list = new ilAdvancedSelectionListGUI();
		$current_selection_list->setListTitle($this->lng->txt("actions"));
		$current_selection_list->setId("act_".$a_set['id']);
		
		$this->ctrl->setParameter($this->getParentObject(),'category_id',$a_set['id']);

		// repository calendars cannot be edited
		if($a_set['editable'] && !in_array($a_set['type'], array(ilCalendarCategory::TYPE_OBJ, ilCalendarCategory::TYPE_CH, ilCalendarCategory::TYPE_BOOK)))
		{
			$url = $this->ctrl->getLinkTarget($this->getParentObject(), 'edit');
			$current_selection_list->addItem($this->lng->txt('edit'), '', $url);

			$this->tpl->setCurrentBlock("checkbox");
			$this->tpl->setVariable('VAL_ID',$a_set['id']);
			$this->tpl->parseCurrentBlock();
		}

		if($a_set['accepted'])
		{
			$url = $this->ctrl->getLinkTarget($this->getParentObject(), 'unshare');
			$current_selection_list->addItem($this->lng->txt('cal_unshare'), '', $url);
		}
		else if($a_set['type'] == ilCalendarCategory::TYPE_USR)
		{
			$url = $this->ctrl->getLinkTarget($this->getParentObject(), 'shareSearch');
			$current_selection_list->addItem($this->lng->txt('cal_share'), '', $url);
		}

		$this->ctrl->setParameter($this->getParentObject(),'category_id','');

		switch($a_set['type'])
		{
			case ilCalendarCategory::TYPE_GLOBAL:
				$this->tpl->setVariable('IMG_SRC',ilUtil::getImagePath('icon_calg_s.gif'));
				$this->tpl->setVariable('IMG_ALT', $this->lng->txt('cal_type_system'));
				break;
				
			case ilCalendarCategory::TYPE_USR:
				$this->tpl->setVariable('IMG_SRC',ilUtil::getImagePath('icon_usr_s.gif'));
				$this->tpl->setVariable('IMG_ALT',$this->lng->txt('cal_type_personal'));
				break;
			
			case ilCalendarCategory::TYPE_OBJ:
				$type = ilObject::_lookupType($a_set['obj_id']);
				$this->tpl->setVariable('IMG_SRC',ilUtil::getImagePath('icon_'.$type.'_s.gif'));
				$this->tpl->setVariable('IMG_ALT',$this->lng->txt('cal_type_'.$type));
				break;				
		}
		
		$this->tpl->setVariable('VAL_TITLE',$a_set['title']);
		$this->tpl->setVariable('BGCOLOR',$a_set['color']);
		$this->tpl->setVariable("ACTIONS", $current_selection_list->getHTML());
		
		if(strlen($a_set['path']))
		{
			$this->tpl->setCurrentBlock('calendar_path');
			$this->tpl->setVariable('ADD_PATH_INFO',$a_set['path']);
			$this->tpl->parseCurrentBlock();
		}
	}
	
	/**
	 * parse
	 *
	 * @access public
	 * @return
	 */
	public function parse()
	{
		global $ilUser, $tree;
		
		include_once('./Services/Calendar/classes/class.ilCalendarCategories.php');
		$cats = ilCalendarCategories::_getInstance($ilUser->getId());
		$cats->initialize(ilCalendarCategories::MODE_MANAGE);
	
		$tmp_title_counter = array();
		$categories = array();
		foreach($cats->getCategoriesInfo() as $category)
		{
			$tmp_arr['obj_id'] = $category['obj_id'];
			$tmp_arr['id'] = $category['cat_id'];
			$tmp_arr['title'] = $category['title'];
			$tmp_arr['type'] = $category['type'];
			$tmp_arr['color'] = $category['color'];
			$tmp_arr['editable'] = $category['editable'];
			$tmp_arr['accepted'] = $category['accepted'];

			$categories[] = $tmp_arr;
			
			// count title for appending the parent container if there is more than one entry.
			$tmp_title_counter[$category['type'].'_'.$category['title']]++;
		}
		
		$path_categories = array();
		foreach($categories as $cat)
		{
			if($cat['type'] == ilCalendarCategory::TYPE_OBJ)
			{
				if($tmp_title_counter[$cat['type'].'_'.$cat['title']] > 1)
				{
					foreach(ilObject::_getAllReferences($cat['obj_id']) as $ref_id)
					{
						$cat['path'] = $this->buildPath($ref_id);
						break;					
					}
				}
			}			
			$path_categories[] = $cat;
		}
		$this->setData($path_categories ? $path_categories : array());
	}
	
	protected function buildPath($a_ref_id)
	{
		global $tree;

		$path_arr = $tree->getPathFull($a_ref_id,ROOT_FOLDER_ID);
		$counter = 0;
		unset($path_arr[count($path_arr) - 1]);

		foreach($path_arr as $data)
		{
			if($counter++)
			{
				$path .= " -> ";
			}
			$path .= $data['title'];
		}
		if(strlen($path) > 30)
		{
			return '...'.substr($path,-30);
		}
		return $path;
	}
	
}
?>

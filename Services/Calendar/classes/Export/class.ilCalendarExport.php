<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
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

include_once './Services/Calendar/classes/iCal/class.ilICalWriter.php';
include_once './Services/Calendar/classes/class.ilCalendarCategory.php';
include_once './Services/Calendar/classes/class.ilCalendarEntry.php';
include_once './Services/Calendar/classes/class.ilCalendarCategoryAssignments.php';

/**
 * @classDescription Export calendar(s) to ical format
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * @version $Id$
 * 
 * @ingroup ServicesCalendar
 */
class ilCalendarExport
{
	protected $calendars = array();
	protected $writer = null;
	
	public function __construct($a_calendar_ids)
	{
		$this->calendars = $a_calendar_ids;
		$this->writer = new ilICalWriter();
	}
	
	public function export()
	{
		$this->writer->addLine('BEGIN:VCALENDAR');
		$this->writer->addLine('VERSION:2.0');
		$this->writer->addLine('PRODID:-//ilias.de/NONSGML ILIAS Calendar V4.1//EN');
		
		$this->addTimezone();
		$this->addCategories();
		
		$this->writer->addLine('END:VCALENDAR');
	}
	
	protected function addTimezone()
	{
		// TODO
	}
	
	protected function addCategories()
	{
		foreach($this->calendars as $category_id)
		{
			foreach(ilCalendarCategoryAssignments::_getAssignedAppointments(
				ilCalendarCategories::_getInstance()->getSubitemCategories($category_id)
				) as $app_id)
			{
				$app = new ilCalendarEntry($app_id);
				if($app->isMilestone())
				{
					$this->createVTODO($app);
				}
				else
				{
					$this->createVEVENT($app);
				}
			}
		}
	}
	
	protected function createVTODO($app)
	{
		// TODO
		return true;
	}
	
	protected function createVEVENT($app)
	{
		global $ilUser;
		
		$this->writer->addLine('BEGIN:VEVENT');
		// TODO only domain
		$this->writer->addLine('UID:'.ilICalWriter::escapeText(
			$app->getEntryId().'_'.CLIENT_ID.'@'.ILIAS_HTTP_PATH));
			
		#$last_mod = $app->getLastUpdate()->get(IL_CAL_FKT_DATE,'Ymd\THis\Z',ilTimeZone::UTC);
		$last_mod = $app->getLastUpdate()->get(IL_CAL_FKT_DATE,'Ymd\THis\Z',$ilUser->getTimeZone());
		$this->writer->addLine('LAST-MODIFIED:'.$last_mod);	
		
		if($app->isFullday())
		{
			// According to RFC 5545 3.6.1 DTEND is not inklusive.
			// But ILIAS stores inklusive dates in the database.
			$app->getEnd()->increment(IL_CAL_DAY,1);

			#$start = $app->getStart()->get(IL_CAL_FKT_DATE,'Ymd\Z',ilTimeZone::UTC);
			$start = $app->getStart()->get(IL_CAL_FKT_DATE,'Ymd',$ilUser->getTimeZone());
			#$end = $app->getEnd()->get(IL_CAL_FKT_DATE,'Ymd\Z',ilTimeZone::UTC);
			$end = $app->getEnd()->get(IL_CAL_FKT_DATE,'Ymd',$ilUser->getTimeZone());
			
			$this->writer->addLine('DTSTART;VALUE=DATE:' . $start);
			$this->writer->addLine('DTEND;VALUE=DATE:'.$end);
		}
		else
		{
			#$start = $app->getStart()->get(IL_CAL_FKT_DATE,'Ymd\THis\Z',ilTimeZone::UTC);
			$start = $app->getStart()->get(IL_CAL_FKT_DATE,'Ymd\THis',$ilUser->getTimeZone());
			#$end = $app->getEnd()->get(IL_CAL_FKT_DATE,'Ymd\THis\Z',ilTimeZone::UTC);
			$end = $app->getEnd()->get(IL_CAL_FKT_DATE,'Ymd\THis',$ilUser->getTimeZone());
			$this->writer->addLine('DTSTART;TZID='.$ilUser->getTimezone().':'. $start);
			$this->writer->addLine('DTEND;TZID='.$ilUser->getTimezone().':'.$end);
		}
		
		
		$this->createRecurrences($app);
		
		$this->writer->addLine('SUMMARY:'.ilICalWriter::escapeText($app->getPresentationTitle()));
		if(strlen($app->getDescription()))
			$this->writer->addLine('DESCRIPTION:'.ilICalWriter::escapeText($app->getDescription()));
		if(strlen($app->getLocation()))
			$this->writer->addLine('LOCATION:'.ilICalWriter::escapeText($app->getLocation()));

		// TODO: URL
		$this->buildAppointmentUrl($app);

		$this->writer->addLine('END:VEVENT');
		
	}
	
	protected function createRecurrences($app)
	{
		include_once './Services/Calendar/classes/class.ilCalendarRecurrences.php';
		foreach(ilCalendarRecurrences::_getRecurrences($app->getEntryId()) as $rec)
		{
			foreach(ilCalendarRecurrenceExclusions::getExclusionDates($app->getEntryId()) as $excl)
			{
				$this->writer->addLine($excl->toICal());
			}
			$this->writer->addLine($rec->toICal());
		}
	}
	
	
	public function getExportString()
	{
		return $this->writer->__toString();
	}

	/**
	 * Build url from calendar entry
	 * @param ilCalendarEntry $entry
	 * @return string
	 */
	protected function buildAppointmentUrl(ilCalendarEntry $entry)
	{
		$cat = ilCalendarCategory::getInstanceByCategoryId(
			current((array) ilCalendarCategoryAssignments::_lookupCategories($entry->getEntryId()))
		);

		if($cat->getType() != ilCalendarCategory::TYPE_OBJ)
		{
			$this->writer->addLine('URL;VALUE=URI:'.ILIAS_HTTP_PATH);
		}
		else
		{
			$refs = ilObject::_getAllReferences($cat->getObjId());

			include_once './classes/class.ilLink.php';
			$this->writer->addLine(
				'URL;VALUE=URI:'.ilLink::_getLink(current((array) $refs))
			);
		}
	}
}

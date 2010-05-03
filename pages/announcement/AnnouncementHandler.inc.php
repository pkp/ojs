<?php

/**
 * @file pages/announcement/AnnouncementHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementHandler
 * @ingroup pages_announcement
 *
 * @brief Handle requests for public announcement functions. 
 */

// $Id$


import('lib.pkp.pages.announcement.PKPAnnouncementHandler');

class AnnouncementHandler extends PKPAnnouncementHandler {
	/**
	 * Constructor
	 **/
	function AnnouncementHandler() {
		parent::PKPAnnouncementHandler();
	}
	function _getAnnouncementsEnabled() {
		$journal =& Request::getJournal();
		return $journal->getSetting('enableAnnouncements');
	}

	function &_getAnnouncements($rangeInfo = null) {
		$journal =& Request::getJournal();

		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		$announcements =& $announcementDao->getAnnouncementsNotExpiredByAssocId(ASSOC_TYPE_JOURNAL, $journal->getId(), $rangeInfo);
		$announcementsIntroduction = $journal->getLocalizedSetting('announcementsIntroduction');

		return $announcements;
	}
	
	function _getAnnouncementsIntroduction() {
		$journal =& Request::getJournal();
		return $journal->getLocalizedSetting('announcementsIntroduction');
	}
		
	function _announcementIsValid($announcementId) {
		$journal =& Request::getJournal();
		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');		
		return ($announcementId != null && $announcementDao->getAnnouncementAssocId($announcementId) == $journal->getId());
	}
}

?>

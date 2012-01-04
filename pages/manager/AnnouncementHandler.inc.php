<?php

/**
 * @file pages/manager/AnnouncementHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for announcement management functions.
 */

import('lib.pkp.pages.manager.PKPAnnouncementHandler');

class AnnouncementHandler extends PKPAnnouncementHandler {
	/**
	 * Constructor
	 */
	function AnnouncementHandler() {
		parent::PKPAnnouncementHandler();
	}

	/**
	 * Display a list of announcements for the current journal.
	 * @see PKPAnnouncementHandler::announcements
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function announcements($args, &$request) {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.announcements');
		parent::announcements($args, $request);
	}

	/**
	 * Display a list of announcement types for the current journal.
	 * @see PKPAnnouncementHandler::announcementTypes
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function announcementTypes($args, &$request) {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.announcements');
		parent::announcementTypes($args, $request);
	}

	/**
	 * @see PKPAnnouncementHandler::_getAnnouncements
	 * @param $request PKPRequest
	 * @param $rangeInfo Object optional
	 */
	function &_getAnnouncements($request, $rangeInfo = null) {
		$journal =& $request->getJournal();
		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		$announcements =& $announcementDao->getAnnouncementsByAssocId(ASSOC_TYPE_JOURNAL, $journal->getId(), $rangeInfo);

		return $announcements;
	}

	/**
	 * @see PKPAnnouncementHandler::_getAnnouncementTypes
	 * @param $request PKPRequest
	 * @param $rangeInfo object optional
	 */
	function &_getAnnouncementTypes($request, $rangeInfo = null) {
		$journal =& $request->getJournal();
		$announcementTypeDao =& DAORegistry::getDAO('AnnouncementTypeDAO');
		$announcements =& $announcementTypeDao->getAnnouncementTypesByAssocId(ASSOC_TYPE_JOURNAL, $journal->getId(), $rangeInfo);

		return $announcements;
	}

	/**
	 * Checks the announcement to see if it belongs to this journal or scheduled journal
	 * @param $request PKPRequest
	 * @param $announcementId int
	 * return bool
	 */
	function _announcementIsValid($request, $announcementId) {
		if ($announcementId == null) return true;

		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		$announcement =& $announcementDao->getAnnouncement($announcementId);

		$journal =& $request->getJournal();
		if ( $announcement && $journal
			&& $announcement->getAssocType() == ASSOC_TYPE_JOURNAL
			&& $announcement->getAssocId() == $journal->getId())
				return true;

		return false;
	}

	/**
	 * Checks the announcement type to see if it belongs to this journal.  All announcement types are set at the journal level.
	 * @param $request PKPRequest
	 * @param $typeId int
	 * return bool
	 */
	function _announcementTypeIsValid($request, $typeId) {
		$journal =& $request->getJournal();
		$announcementTypeDao =& DAORegistry::getDAO('AnnouncementTypeDAO');
		return (($typeId != null && $announcementTypeDao->getAnnouncementTypeAssocId($typeId) == $journal->getId()) || $typeId == null);
	}
}

?>

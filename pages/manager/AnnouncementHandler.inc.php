<?php

/**
 * @file pages/manager/AnnouncementHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
	 * @see PKPAnnouncementHandler::getContextId()
	 */
	function getContextId(&$request) {
		$journal =& $request->getJournal();
		if ($journal) {
			return $journal->getId();
		} else {
			return null;
		}

	}

	/**
	 * @see PKPAnnouncementHandler::_getAnnouncements
	 * @param $request PKPRequest
	 * @param $rangeInfo Object optional
	 */
	function &_getAnnouncements($request, $rangeInfo = null) {
		$journalId = $this->getContextId($request);
		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		$announcements =& $announcementDao->getByAssocId(ASSOC_TYPE_JOURNAL, $journalId, $rangeInfo);

		return $announcements;
	}

	/**
	 * @see PKPAnnouncementHandler::_getAnnouncementTypes
	 * @param $request PKPRequest
	 * @param $rangeInfo object optional
	 */
	function &_getAnnouncementTypes(&$request, $rangeInfo = null) {
		$journalId = $this->getContextId($request);
		$announcementTypeDao =& DAORegistry::getDAO('AnnouncementTypeDAO');
		$announcements =& $announcementTypeDao->getByAssoc(ASSOC_TYPE_JOURNAL, $journalId, $rangeInfo);

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
		$announcement =& $announcementDao->getById($announcementId);

		$journalId = $this->getContextId($request);
		if ( $announcement && $journalId
			&& $announcement->getAssocType() == ASSOC_TYPE_JOURNAL
			&& $announcement->getAssocId() == $journalId)
				return true;

		return false;
	}

	/**
	 * Checks the announcement type to see if it belongs to this journal.  All announcement types are set at the journal level.
	 * @param $request PKPRequest
	 * @param $typeId int
	 * return bool
	 */
	function _announcementTypeIsValid(&$request, $typeId) {
		$journalId = $this->getContextId($request);
		$announcementTypeDao =& DAORegistry::getDAO('AnnouncementTypeDAO');
		$announcementType = $announcementTypeDao->getById($typeId);
		return (($announcementType && $announcementType->getAssocId() == $journalId && $announcementType->getAssocType() == ASSOC_TYPE_JOURNAL) || $typeId == null);
	}
}

?>

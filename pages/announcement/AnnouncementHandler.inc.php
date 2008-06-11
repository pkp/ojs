<?php

/**
 * @file AnnouncementHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.announcement
 * @class AnnouncementHandler
 *
 * Handle requests for public announcement functions. 
 *
 * $Id$
 */

class AnnouncementHandler extends Handler {

	/**
	 * Display announcement index page.
	 */
	function index() {
		AnnouncementHandler::setupTemplate();

		$journal = &Request::getJournal();
		$announcementsEnabled = $journal->getSetting('enableAnnouncements');

		if ($announcementsEnabled) {
			$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');
			$rangeInfo = &Handler::getRangeInfo('announcements');
			$announcements = &$announcementDao->getAnnouncementsNotExpiredByJournalId($journal->getJournalId(), $rangeInfo);
			$announcementsIntroduction = $journal->getLocalizedSetting('announcementsIntroduction');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('announcements', $announcements);
			$templateMgr->assign('announcementsIntroduction', $announcementsIntroduction);
			$templateMgr->display('announcement/index.tpl');
		} else {
			Request::redirect(null, 'index');
		}

	}

	/**
	 * View announcement details.
	 * @param $args array optional, first parameter is the ID of the announcement to display 
	 */
	function view($args = array()) {
		AnnouncementHandler::setupTemplate();

		$journal = &Request::getJournal();
		$announcementsEnabled = $journal->getSetting('enableAnnouncements');
		$announcementId = !isset($args) || empty($args) ? null : (int) $args[0];
		$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');

		if ($announcementsEnabled && $announcementId != null && $announcementDao->getAnnouncementJournalId($announcementId) == $journal->getJournalId()) {
			$announcement = &$announcementDao->getAnnouncement($announcementId);

			if ($announcement->getDateExpire() == null || strtotime($announcement->getDateExpire()) > time()) {
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('announcement', $announcement);
				if ($announcement->getTypeId() == null) {
					$templateMgr->assign('announcementTitle', $announcement->getAnnouncementTitle());
				} else {
					$templateMgr->assign('announcementTitle', $announcement->getAnnouncementTypeName() . ": " . $announcement->getAnnouncementTitle());
				}
				$templateMgr->append('pageHierarchy', array(Request::url(null, 'announcement'), 'announcement.announcements'));
				$templateMgr->display('announcement/view.tpl');
			} else {
				Request::redirect(null, null, 'announcement');
			}
		} else {
				Request::redirect(null, null, 'announcement');
		}
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		parent::validate();
		$templateMgr = &TemplateManager::getManager();
		$journal =& Request::getJournal();

		if (!$journal || !$journal->getSetting('restrictSiteAccess')) {
			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		}
		$templateMgr->assign('pageHierachy', array(array(Request::url(null, 'announcements'), 'announcement.announcements')));
	}
}

?>

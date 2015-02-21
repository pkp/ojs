<?php

/**
 * @file pages/rtadmin/RTSetupHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RTSetupHandler
 * @ingroup pages_rtadmin
 *
 * @brief Handle Reading Tools administration requests -- setup section.
 */

import('pages.rtadmin.RTAdminHandler');

class RTSetupHandler extends RTAdminHandler {
	/**
	 * Constructor
	 **/
	function RTSetupHandler() {
		parent::RTAdminHandler();
	}

	function settings() {
		$this->validate();

		$journal = Request::getJournal();

		if ($journal) {
			$this->setupTemplate(true);
			$templateMgr =& TemplateManager::getManager();

			$rtDao =& DAORegistry::getDAO('RTDAO');
			$rt = $rtDao->getJournalRTByJournal($journal);

			$versionOptions = array();
			$versions = $rtDao->getVersions($journal->getId());
			foreach ($versions->toArray() as $version) {
				$versionOptions[$version->getVersionId()] = $version->getTitle();
			}

			$templateMgr->assign('versionOptions', $versionOptions);
			$templateMgr->assign_by_ref('version', $rt->getVersion());
			$templateMgr->assign('enabled', $rt->getEnabled());
			$templateMgr->assign('abstract', $rt->getAbstract());
			$templateMgr->assign('captureCite', $rt->getCaptureCite());
			$templateMgr->assign('viewMetadata', $rt->getViewMetadata());
			$templateMgr->assign('supplementaryFiles', $rt->getSupplementaryFiles());
			$templateMgr->assign('printerFriendly', $rt->getPrinterFriendly());
			$templateMgr->assign('defineTerms', $rt->getDefineTerms());
			$templateMgr->assign('emailAuthor', $rt->getEmailAuthor());
			$templateMgr->assign('emailOthers', $rt->getEmailOthers());
			$templateMgr->assign('findingReferences', $rt->getFindingReferences());
			$templateMgr->assign('viewReviewPolicy', $rt->getviewReviewPolicy());

			// Bring in the comments constants.
			$commentDao =& DAORegistry::getDao('CommentDAO');

			$templateMgr->assign('commentsOptions', array(
				'COMMENTS_DISABLED' => COMMENTS_DISABLED,
				'COMMENTS_AUTHENTICATED' => COMMENTS_AUTHENTICATED,
				'COMMENTS_ANONYMOUS' => COMMENTS_ANONYMOUS,
				'COMMENTS_UNAUTHENTICATED' => COMMENTS_UNAUTHENTICATED
			));
			$templateMgr->assign('enableComments', $journal->getSetting('enableComments'));

			$templateMgr->assign('helpTopicId', 'journal.managementPages.readingTools.settings');
			$templateMgr->display('rtadmin/settings.tpl');
		} else {
			Request::redirect(null, Request::getRequestedPage());
		}
	}

	function saveSettings() {
		$this->validate();

		// Bring in the comments constants.
		$commentDao =& DAORegistry::getDao('CommentDAO');

		$journal = Request::getJournal();

		if ($journal) {
			$rtDao =& DAORegistry::getDAO('RTDAO');
			$rt = $rtDao->getJournalRTByJournal($journal);

			if (Request::getUserVar('version')=='') $rt->setVersion(null);
			else $rt->setVersion(Request::getUserVar('version'));
			$rt->setEnabled(Request::getUserVar('enabled')==true);
			$rt->setAbstract(Request::getUserVar('abstract')==true);
			$rt->setCaptureCite(Request::getUserVar('captureCite')==true);
			$rt->setViewMetadata(Request::getUserVar('viewMetadata')==true);
			$rt->setSupplementaryFiles(Request::getUserVar('supplementaryFiles')==true);
			$rt->setPrinterFriendly(Request::getUserVar('printerFriendly')==true);
			$rt->setDefineTerms(Request::getUserVar('defineTerms')==true);
			$rt->setEmailAuthor(Request::getUserVar('emailAuthor')==true);
			$rt->setEmailOthers(Request::getUserVar('emailOthers')==true);
			$rt->setFindingReferences(Request::getUserVar('findingReferences')==true);
			$rt->setViewReviewPolicy(Request::getUserVar('viewReviewPolicy')==true);

			$journal->updateSetting('enableComments', Request::getUserVar('enableComments')?Request::getUserVar('enableCommentsMode'):COMMENTS_DISABLED);

			$rtDao->updateJournalRT($rt);
		}
		Request::redirect(null, Request::getRequestedPage());
	}
}

?>

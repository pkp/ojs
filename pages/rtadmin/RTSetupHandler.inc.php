<?php

/**
 * @file pages/rtadmin/RTSetupHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
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
	 */
	function RTSetupHandler() {
		parent::RTAdminHandler();
	}

	function settings($args, $request) {
		$this->validate();

		$journal = $request->getJournal();

		if ($journal) {
			$this->setupTemplate($request, true);
			$templateMgr = TemplateManager::getManager($request);

			$rtDao = DAORegistry::getDAO('RTDAO');
			$rt = $rtDao->getJournalRTByJournal($journal);

			$versionOptions = array();
			$versions = $rtDao->getVersions($journal->getId());
			foreach ($versions->toArray() as $version) {
				$versionOptions[$version->getVersionId()] = $version->getTitle();
			}

			$templateMgr->assign('versionOptions', $versionOptions);
			$templateMgr->assign('version', $rt->getVersion());
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
			$commentDao = DAORegistry::getDao('CommentDAO');

			$templateMgr->assign('commentsOptions', array(
				'COMMENTS_DISABLED' => COMMENTS_DISABLED,
				'COMMENTS_AUTHENTICATED' => COMMENTS_AUTHENTICATED,
				'COMMENTS_ANONYMOUS' => COMMENTS_ANONYMOUS,
				'COMMENTS_UNAUTHENTICATED' => COMMENTS_UNAUTHENTICATED
			));
			$templateMgr->assign('enableComments', $journal->getSetting('enableComments'));

			$templateMgr->display('rtadmin/settings.tpl');
		} else {
			$request->redirect(null, $request->getRequestedPage());
		}
	}

	function saveSettings($args, $request) {
		$this->validate();

		// Bring in the comments constants.
		$commentDao = DAORegistry::getDao('CommentDAO');

		$journal = $request->getJournal();

		if ($journal) {
			$rtDao = DAORegistry::getDAO('RTDAO');
			$rt = $rtDao->getJournalRTByJournal($journal);

			if ($request->getUserVar('version')=='') $rt->setVersion(null);
			else $rt->setVersion($request->getUserVar('version'));
			$rt->setEnabled($request->getUserVar('enabled')==true);
			$rt->setAbstract($request->getUserVar('abstract')==true);
			$rt->setCaptureCite($request->getUserVar('captureCite')==true);
			$rt->setViewMetadata($request->getUserVar('viewMetadata')==true);
			$rt->setSupplementaryFiles($request->getUserVar('supplementaryFiles')==true);
			$rt->setPrinterFriendly($request->getUserVar('printerFriendly')==true);
			$rt->setDefineTerms($request->getUserVar('defineTerms')==true);
			$rt->setEmailAuthor($request->getUserVar('emailAuthor')==true);
			$rt->setEmailOthers($request->getUserVar('emailOthers')==true);
			$rt->setFindingReferences($request->getUserVar('findingReferences')==true);
			$rt->setViewReviewPolicy($request->getUserVar('viewReviewPolicy')==true);

			$journal->updateSetting('enableComments', $request->getUserVar('enableComments')?$request->getUserVar('enableCommentsMode'):COMMENTS_DISABLED);

			$rtDao->updateJournalRT($rt);
		}
		$request->redirect(null, $request->getRequestedPage());
	}
}

?>

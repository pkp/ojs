<?php

/**
 * RTSetupHandler.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.rtadmin
 *
 * Handle Reading Tools administration requests -- setup section. 
 *
 * $Id$
 */

import('rt.ojs.JournalRTAdmin');

class RTSetupHandler extends RTAdminHandler {

	function settings() {
		RTAdminHandler::validate();

		$journal = Request::getJournal();

		if ($journal) {
			RTAdminHandler::setupTemplate(true);
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('journals', &$journals);

			$rtDao = &DAORegistry::getDAO('RTDAO');
			$rt = $rtDao->getJournalRTByJournalID($journal->getJournalId());

			$versionOptions = array();
			foreach ($rtDao->getVersions($journal->getJournalId()) as $version) {
				$versionOptions[$version->getVersionId()] = $version->getTitle();
			}

			$templateMgr->assign('versionOptions', &$versionOptions);

			if ($rt) {
				$templateMgr->assign('version', $rt->getVersion());
				$templateMgr->assign('captureCite', $rt->getCaptureCite());
				$templateMgr->assign('viewMetadata', $rt->getViewMetadata());
				$templateMgr->assign('supplementaryFiles', $rt->getSupplementaryFiles());
				$templateMgr->assign('printerFriendly', $rt->getPrinterFriendly());
				$templateMgr->assign('authorBio', $rt->getAuthorBio());
				$templateMgr->assign('defineTerms', $rt->getDefineTerms());
				$templateMgr->assign('addComment', $rt->getAddComment());
				$templateMgr->assign('emailAuthor', $rt->getEmailAuthor());
				$templateMgr->assign('emailOthers', $rt->getEmailOthers());
			}

			$templateMgr->display('rtadmin/settings.tpl');
		} else {
			Request::redirect('rtadmin');
		}
	}
	
	function saveSettings() {
		RTAdminHandler::validate();

		$journal = Request::getJournal();

		if ($journal) {
			$rtDao = &DAORegistry::getDAO('RTDAO');
			$rt = $rtDao->getJournalRTByJournalId($journal->getJournalId());
			$isNewConfig = false;

			if (!$rt) {
				// This journal doesn't yet have reading tools configured.
				$rt = new JournalRT($journal->getJournalId());
				$isNewConfig = true;
			}

			$rt->setVersion(Request::getUserVar('version'));
			$rt->setCaptureCite(Request::getUserVar('captureCite')==true);
			$rt->setViewMetadata(Request::getUserVar('viewMetadata')==true);
			$rt->setSupplementaryFiles(Request::getUserVar('supplementaryFiles')==true);
			$rt->setPrinterFriendly(Request::getUserVar('printerFriendly')==true);
			$rt->setAuthorBio(Request::getUserVar('authorBio')==true);
			$rt->setDefineTerms(Request::getUserVar('defineTerms')==true);
			$rt->setAddComment(Request::getUserVar('addComment')==true);
			$rt->setEmailAuthor(Request::getUserVar('emailAuthor')==true);
			$rt->setEmailOthers(Request::getUserVar('emailOthers')==true);

			if ($isNewConfig) {
				$rtDao->insertJournalRT($rt);
			} else {
				$rtDao->updateJournalRT($rt);
			}
		}
		Request::redirect('rtadmin');
	}
}

?>

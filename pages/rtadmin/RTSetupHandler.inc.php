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
			$templateMgr->assign_by_ref('journals', $journals);

			$rtDao = &DAORegistry::getDAO('RTDAO');
			$rt = $rtDao->getJournalRTByJournalId($journal->getJournalId());

			$versionOptions = array();
			$versions = $rtDao->getVersions($journal->getJournalId());
			foreach ($versions->toArray() as $version) {
				$versionOptions[$version->getVersionId()] = $version->getTitle();
			}

			$templateMgr->assign('versionOptions', $versionOptions);
			$templateMgr->assign('bibFormatOptions', array(
				'APA' => 'American Psychological Association (APA)',
				'MLA' => 'Modern Language Association (MLA)',
				'Turabian' => 'Turabian',
				'CBE' => 'Council of Biology Editors (CBE)',
				'BibTeX' => 'BibTeX',
				'ABNT' => 'ABNT 10520'
			));

			if ($rt) {
				$templateMgr->assign_by_ref('version', $rt->getVersion());
				$templateMgr->assign('captureCite', $rt->getCaptureCite());
				$templateMgr->assign('viewMetadata', $rt->getViewMetadata());
				$templateMgr->assign('supplementaryFiles', $rt->getSupplementaryFiles());
				$templateMgr->assign('printerFriendly', $rt->getPrinterFriendly());
				$templateMgr->assign('authorBio', $rt->getAuthorBio());
				$templateMgr->assign('defineTerms', $rt->getDefineTerms());
				$templateMgr->assign('addComment', $rt->getAddComment());
				$templateMgr->assign('emailAuthor', $rt->getEmailAuthor());
				$templateMgr->assign('emailOthers', $rt->getEmailOthers());
				$templateMgr->assign('bibFormat', $rt->getBibFormat());
			} else {
				// No current configuration exists; provide
				// all options enabled as a default configuration.
				$templateMgr->assign('captureCite', true);
				$templateMgr->assign('viewMetadata', true);
				$templateMgr->assign('supplementaryFiles', true);
				$templateMgr->assign('printerFriendly', true);
				$templateMgr->assign('authorBio', true);
				$templateMgr->assign('defineTerms', true);
				$templateMgr->assign('addComment', true);
				$templateMgr->assign('emailAuthor', true);
				$templateMgr->assign('emailOthers', true);
				$templateMgr->assign('bibFormat', true);
			}

			$templateMgr->assign('helpTopicId', 'journal.managementPages.readingTools.settings');
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
				$rt = &new JournalRT($journal->getJournalId());
				$isNewConfig = true;
			}

			if (Request::getUserVar('version')=='') $rt->setVersion(null);
			else $rt->setVersion(Request::getUserVar('version'));

			$rt->setCaptureCite(Request::getUserVar('captureCite')==true);
			$rt->setViewMetadata(Request::getUserVar('viewMetadata')==true);
			$rt->setSupplementaryFiles(Request::getUserVar('supplementaryFiles')==true);
			$rt->setPrinterFriendly(Request::getUserVar('printerFriendly')==true);
			$rt->setAuthorBio(Request::getUserVar('authorBio')==true);
			$rt->setDefineTerms(Request::getUserVar('defineTerms')==true);
			$rt->setAddComment(Request::getUserVar('addComment')==true);
			$rt->setEmailAuthor(Request::getUserVar('emailAuthor')==true);
			$rt->setEmailOthers(Request::getUserVar('emailOthers')==true);
			$rt->setBibFormat(Request::getUserVar('bibFormat'));

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

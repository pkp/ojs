<?php

/**
 * RTSetupHandler.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
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
			$rt = $rtDao->getJournalRTByJournal($journal);

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

			$templateMgr->assign_by_ref('version', $rt->getVersion());
			$templateMgr->assign('enabled', $rt->getEnabled());
			$templateMgr->assign('abstract', $rt->getAbstract());
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

			$templateMgr->assign('helpTopicId', 'journal.managementPages.readingTools.settings');
			$templateMgr->display('rtadmin/settings.tpl');
		} else {
			Request::redirect(null, Request::getRequestedPage());
		}
	}

	function saveSettings() {
		RTAdminHandler::validate();

		$journal = Request::getJournal();

		if ($journal) {
			$rtDao = &DAORegistry::getDAO('RTDAO');
			$rt = $rtDao->getJournalRTByJournal($journal);

			if (Request::getUserVar('version')=='') $rt->setVersion(null);
			else $rt->setVersion(Request::getUserVar('version'));
			$rt->setEnabled(Request::getUserVar('enabled')==true);
			$rt->setAbstract(Request::getUserVar('abstract')==true);
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

			$rtDao->updateJournalRT($rt);
		}
		Request::redirect(null, Request::getRequestedPage());
	}
}

?>

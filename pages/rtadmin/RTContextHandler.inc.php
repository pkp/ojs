<?php

/**
 * RTContextHandler.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.rtadmin
 *
 * Handle Reading Tools administration requests -- contexts section. 
 *
 * $Id$
 */

import('rt.ojs.JournalRTAdmin');

class RTContextHandler extends RTAdminHandler {
	function contexts($args) {
		RTAdminHandler::validate();
		RTAdminHandler::setupTemplate(true);

		$journal = Request::getJournal();

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());

		if ($version) {
			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign('version', $version);
			$templateMgr->assign('contexts', $version->getContexts());
			$templateMgr->display('rtadmin/contexts.tpl');
		}
		else Request::redirect('rtadmin/versions');
	}

	function editContext($args) {
		/* RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());

		if (isset($version)) {
			RTAdminHandler::setupTemplate(true);
			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign('version', $version);

			$templateMgr->display('rtadmin/version.tpl');
		}
		else Request::redirect('rtadmin/versions'); */

		
	}

	function deleteContext($args) {
		/* RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;

		$rtDao->deleteVersion($versionId, $journal->getJournalId());

		Request::redirect('rtadmin/versions'); */
	}

	function saveContext($args) {
		/* RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());

		if (isset($version)) {
			$version->setLocale(Request::getUserVar('locale'));
			$version->setTitle(Request::getUserVar('title'));
			$version->setDescription(Request::getUserVar('description'));
			$version->setKey(Request::getUserVar('key'));
			$rtDao->updateVersion($journal->getJournalId(), &$version);
		}

		Request::redirect('rtadmin/versions'); */
	}
}

?>

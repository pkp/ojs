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
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());
		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			import('rt.ojs.form.ContextForm');
			RTAdminHandler::setupTemplate(true);
			$contextForm = new ContextForm($contextId, $versionId);
			$contextForm->initData();
			$contextForm->display();
		}
		else Request::redirect('rtadmin/contexts/' . $versionId);

		
	}

	function deleteContext($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());
		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			$rtDao->deleteContext($contextId);
		}

		Request::redirect('rtadmin/contexts/' . $versionId);
	}

	function saveContext($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());
		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			import('rt.ojs.form.ContextForm');
			$contextForm = new ContextForm($contextId, $versionId);
			$contextForm->readInputData();
			$contextForm->execute();
		}

		Request::redirect('rtadmin/contexts/' . $versionId);
	}
}

?>

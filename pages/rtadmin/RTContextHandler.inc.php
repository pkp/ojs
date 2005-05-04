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
	function createContext($args) {
		RTAdminHandler::validate();

		$journal = Request::getJournal();

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());

		import('rt.ojs.form.ContextForm');
		$contextForm = new ContextForm(null, $versionId);

		if (isset($args[1]) && $args[1]=='save') {
			$contextForm->readInputData();
			$contextForm->execute();
			Request::redirect('rtadmin/contexts/' . $versionId);
		} else {
			RTAdminHandler::setupTemplate(true, $version);
			$contextForm->display();
		}
	}

	function contexts($args) {
		RTAdminHandler::validate();

		$journal = Request::getJournal();

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$rangeInfo = Handler::getRangeInfo('contexts');
		
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());

		if ($version) {
			RTAdminHandler::setupTemplate(true, $version);

			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign('version', $version);

			$templateMgr->assign_by_ref('contexts', new ArrayIterator($version->getContexts(), $rangeInfo->getPage(), $rangeInfo->getCount()));

			$templateMgr->assign('helpTopicId', 'journal.managementPages.readingTools.contexts');
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
			RTAdminHandler::setupTemplate(true, $version, $context);
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
			$rtDao->deleteContext($contextId, $versionId);
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

	function moveContext($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());
		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			$isDown = Request::getUserVar('dir')=='d';
			$contexts = $version->getContexts();

			$i=0;
			foreach ($contexts as $searchContext) {
				if ($searchContext->getContextId() == $contextId) {
					$contextIndex = $i;
					break;
				}
				$i++;
			}

			if (isset($contextIndex)) {
				$otherContext = &$contexts[$contextIndex + ($isDown?1:-1)];
				if (isset($otherContext)) {
					$tmpOrder = $otherContext->getOrder();
					$otherContext->setOrder($context->getOrder());
					$context->setOrder($tmpOrder);
					$rtDao->updateContext(&$context);
					$rtDao->updateContext(&$otherContext);
				}
			}
		}

		Request::redirect('rtadmin/contexts/' . $versionId);
	}
}

?>

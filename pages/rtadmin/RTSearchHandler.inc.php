<?php

/**
 * RTSearchHandler.inc.php
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

class RTSearchHandler extends RTAdminHandler {
	function createSearch($args) {
		RTAdminHandler::validate();

		$journal = Request::getJournal();

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());
		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);

		import('rt.ojs.form.SearchForm');
		$searchForm = new SearchForm(null, $contextId, $versionId);

		if (isset($args[2]) && $args[2]=='save') {
			$searchForm->readInputData();
			$searchForm->execute();
			Request::redirect('rtadmin/searches/' . $versionId . '/' . $contextId);
		} else {
			RTAdminHandler::setupTemplate(true, $version, $context);
			$searchForm->display();
		}
	}

	function searches($args) {
		RTAdminHandler::validate();

		$journal = Request::getJournal();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());

		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);

		if ($context && $version && $context->getVersionId() == $version->getVersionId()) {
			RTAdminHandler::setupTemplate(true, $version, $context);

			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign('version', $version);
			$templateMgr->assign('context', $context);
			$templateMgr->assign('searches', $context->getSearches());

			$templateMgr->display('rtadmin/searches.tpl');
		}
		else Request::redirect('rtadmin/versions');
	}

	function editSearch($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());
		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);
		$searchId = isset($args[2])?$args[2]:0;
		$search = &$rtDao->getSearch($searchId);

		if (isset($version) && isset($context) && isset($search) && $context->getVersionId() == $version->getVersionId() && $search->getContextId() == $context->getContextId()) {
			import('rt.ojs.form.SearchForm');
			RTAdminHandler::setupTemplate(true, $version, $context, $search);
			$searchForm = new SearchForm($searchId, $contextId, $versionId);
			$searchForm->initData();
			$searchForm->display();
		}
		else Request::redirect('rtadmin/searches/' . $versionId . '/' . $contextId);

		
	}

	function deleteSearch($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());
		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);
		$searchId = isset($args[2])?$args[2]:0;
		$search = &$rtDao->getSearch($searchId);

		if (isset($version) && isset($context) && isset($search) && $context->getVersionId() == $version->getVersionId() && $search->getContextId() == $context->getContextId()) {
			$rtDao->deleteSearch($searchId, $contextId);
		}

		Request::redirect('rtadmin/searches/' . $versionId . '/' . $contextId);
	}

	function saveSearch($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());
		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);
		$searchId = isset($args[2])?$args[2]:0;
		$search = &$rtDao->getSearch($searchId);

		if (isset($version) && isset($context) && isset($search) && $context->getVersionId() == $version->getVersionId() && $search->getContextId() == $context->getContextId()) {
			import('rt.ojs.form.SearchForm');
			$searchForm = new SearchForm($searchId, $contextId, $versionId);
			$searchForm->readInputData();
			$searchForm->execute();
		}

		Request::redirect('rtadmin/searches/' . $versionId . '/' . $contextId);
	}

	function moveSearch($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());
		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);
		$searchId = isset($args[2])?$args[2]:0;
		$search = &$rtDao->getSearch($searchId);

		if (isset($version) && isset($context) && isset($search) && $context->getVersionId() == $version->getVersionId() && $search->getContextId() == $context->getContextId()) {
			$isDown = Request::getUserVar('dir')=='d';
			$searches = $context->getSearches();

			$i=0;
			foreach ($searches as $searchSearch) {
				if ($searchSearch->getSearchId() == $searchId) {
					$searchIndex = $i;
					break;
				}
				$i++;
			}

			if (isset($searchIndex)) {
				$otherSearch = &$searches[$searchIndex + ($isDown?1:-1)];
				if (isset($otherSearch)) {
					$tmpOrder = $otherSearch->getOrder();
					$otherSearch->setOrder($search->getOrder());
					$search->setOrder($tmpOrder);
					$rtDao->updateSearch(&$search);
					$rtDao->updateSearch(&$otherSearch);
				}
			}
		}

		Request::redirect('rtadmin/searches/' . $versionId . '/' . $contextId);
	}
}

?>

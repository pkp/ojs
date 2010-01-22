<?php

/**
 * @file RTSearchHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RTSearchHandler
 * @ingroup pages_rtadmin
 *
 * @brief Handle Reading Tools administration requests -- contexts section.
 */

// $Id$


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
		$searchForm = &new SearchForm(null, $contextId, $versionId);

		if (isset($args[2]) && $args[2]=='save') {
			$searchForm->readInputData();
			$searchForm->execute();
			Request::redirect(null, null, 'searches', array($versionId, $contextId));
		} else {
			RTAdminHandler::setupTemplate(true, $version, $context);
			$searchForm->display();
		}
	}

	function searches($args) {
		RTAdminHandler::validate();

		$journal = Request::getJournal();

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$rangeInfo = Handler::getRangeInfo('searches');

		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());

		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);

		if ($context && $version && $context->getVersionId() == $version->getVersionId()) {
			RTAdminHandler::setupTemplate(true, $version, $context);

			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign_by_ref('version', $version);
			$templateMgr->assign_by_ref('context', $context);
			$templateMgr->assign_by_ref('searches', new ArrayItemIterator($context->getSearches(), $rangeInfo->getPage(), $rangeInfo->getCount()));

			$templateMgr->assign('helpTopicId', 'journal.managementPages.readingTools.contexts');
			$templateMgr->display('rtadmin/searches.tpl');
		}
		else Request::redirect(null, null, 'versions');
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
			$searchForm = &new SearchForm($searchId, $contextId, $versionId);
			$searchForm->initData();
			$searchForm->display();
		}
		else Request::redirect(null, null, 'searches', array($versionId, $contextId));


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

		Request::redirect(null, null, 'searches', array($versionId, $contextId));
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
			$searchForm = &new SearchForm($searchId, $contextId, $versionId);
			$searchForm->readInputData();
			$searchForm->execute();
		}

		Request::redirect(null, null, 'searches', array($versionId, $contextId));
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
			$search->setOrder($search->getOrder()+($isDown?1.5:-1.5));
			$rtDao->updateSearch($search);
			$rtDao->resequenceSearches($context->getContextId());
		}

		Request::redirect(null, null, 'searches', array($versionId, $contextId));
	}
}

?>

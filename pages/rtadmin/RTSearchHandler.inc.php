<?php

/**
 * @file pages/rtadmin/RTSearchHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RTSearchHandler
 * @ingroup pages_rtadmin
 *
 * @brief Handle Reading Tools administration requests -- contexts section.
 */

import('pages.rtadmin.RTAdminHandler');

class RTSearchHandler extends RTAdminHandler {
	/**
	 * Constructor
	 **/
	function RTSearchHandler() {
		parent::RTAdminHandler();
	}

	function createSearch($args) {
		$this->validate();

		$journal = Request::getJournal();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $journal->getId());
		$contextId = isset($args[1])?$args[1]:0;
		$context =& $rtDao->getContext($contextId);

		import('classes.rt.ojs.form.SearchForm');
		$searchForm = new SearchForm(null, $contextId, $versionId);

		if (isset($args[2]) && $args[2]=='save') {
			$searchForm->readInputData();
			$searchForm->execute();
			Request::redirect(null, null, 'searches', array($versionId, $contextId));
		} else {
			$this->setupTemplate(true, $version, $context);
			$searchForm->display();
		}
	}

	function searches($args) {
		$this->validate();

		$journal = Request::getJournal();

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$rangeInfo = $this->getRangeInfo('searches');

		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $journal->getId());

		$contextId = isset($args[1])?$args[1]:0;
		$context =& $rtDao->getContext($contextId);

		if ($context && $version && $context->getVersionId() == $version->getVersionId()) {
			$this->setupTemplate(true, $version, $context);

			$templateMgr =& TemplateManager::getManager();

			$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/jquery.tablednd.js');
			$templateMgr->addJavaScript('lib/pkp/js/functions/tablednd.js');

			$templateMgr->assign_by_ref('version', $version);
			$templateMgr->assign_by_ref('context', $context);
			import('lib.pkp.classes.core.ArrayItemIterator');
			$templateMgr->assign_by_ref('searches', new ArrayItemIterator($context->getSearches(), $rangeInfo->getPage(), $rangeInfo->getCount()));

			$templateMgr->assign('helpTopicId', 'journal.managementPages.readingTools.contexts');
			$templateMgr->display('rtadmin/searches.tpl');
		}
		else Request::redirect(null, null, 'versions');
	}

	function editSearch($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $journal->getId());
		$contextId = isset($args[1])?$args[1]:0;
		$context =& $rtDao->getContext($contextId);
		$searchId = isset($args[2])?$args[2]:0;
		$search =& $rtDao->getSearch($searchId);

		if (isset($version) && isset($context) && isset($search) && $context->getVersionId() == $version->getVersionId() && $search->getContextId() == $context->getContextId()) {
			import('classes.rt.ojs.form.SearchForm');
			$this->setupTemplate(true, $version, $context, $search);
			$searchForm = new SearchForm($searchId, $contextId, $versionId);
			$searchForm->initData();
			$searchForm->display();
		}
		else Request::redirect(null, null, 'searches', array($versionId, $contextId));


	}

	function deleteSearch($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $journal->getId());
		$contextId = isset($args[1])?$args[1]:0;
		$context =& $rtDao->getContext($contextId);
		$searchId = isset($args[2])?$args[2]:0;
		$search =& $rtDao->getSearch($searchId);

		if (isset($version) && isset($context) && isset($search) && $context->getVersionId() == $version->getVersionId() && $search->getContextId() == $context->getContextId()) {
			$rtDao->deleteSearch($searchId, $contextId);
		}

		Request::redirect(null, null, 'searches', array($versionId, $contextId));
	}

	function saveSearch($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $journal->getId());
		$contextId = isset($args[1])?$args[1]:0;
		$context =& $rtDao->getContext($contextId);
		$searchId = isset($args[2])?$args[2]:0;
		$search =& $rtDao->getSearch($searchId);

		if (isset($version) && isset($context) && isset($search) && $context->getVersionId() == $version->getVersionId() && $search->getContextId() == $context->getContextId()) {
			import('classes.rt.ojs.form.SearchForm');
			$searchForm = new SearchForm($searchId, $contextId, $versionId);
			$searchForm->readInputData();
			$searchForm->execute();
		}

		Request::redirect(null, null, 'searches', array($versionId, $contextId));
	}

	function moveSearch($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $journal->getId());
		$contextId = isset($args[1])?$args[1]:0;
		$context =& $rtDao->getContext($contextId);
		$searchId = Request::getUserVar('id');
		$search =& $rtDao->getSearch($searchId);

		if (isset($version) && isset($context) && isset($search) && $context->getVersionId() == $version->getVersionId() && $search->getContextId() == $context->getContextId()) {
			$direction = Request::getUserVar('dir');
			if ($direction != null) {
				// moving with up or down arrow
				$isDown = $direction =='d';
				$search->setOrder($search->getOrder()+($isDown?1.5:-1.5));
			} else {
				// drag and drop
				$prevId = Request::getUserVar('prevId');
				if ($prevId == null)
					$prevSeq = 0;
				else {
					$prevSearch = $rtDao->getSearch($prevId);
					$prevSeq = $prevSearch->getOrder();
				}

				$search->setOrder($prevSeq + .5);
			}
			$rtDao->updateSearch($search);
			$rtDao->resequenceSearches($context->getContextId());
		}

		// Moving up or down with the arrows requires a page reload.
		// In the case of a drag and drop move, the display has been
		// updated on the client side, so no reload is necessary.
		if ($direction != null) {
			Request::redirect(null, null, 'searches', array($versionId, $contextId));
		}
	}
}

?>

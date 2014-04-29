<?php

/**
 * @file pages/rtadmin/RTContextHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RTContextHandler
 * @ingroup pages_rtadmin
 *
 * @brief Handle Reading Tools administration requests -- contexts section.
 */

import('pages.rtadmin.RTAdminHandler');

class RTContextHandler extends RTAdminHandler {
	/**
	 * Constructor
	 **/
	function RTContextHandler() {
		parent::RTAdminHandler();
	}

	function createContext($args) {
		$this->validate();

		$journal = Request::getJournal();

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $journal->getId());

		import('classes.rt.ojs.form.ContextForm');
		$contextForm = new ContextForm(null, $versionId);

		if (isset($args[1]) && $args[1]=='save') {
			$contextForm->readInputData();
			$contextForm->execute();
			Request::redirect(null, null, 'contexts', $versionId);
		} else {
			$this->setupTemplate(true, $version);
			$contextForm->display();
		}
	}

	function contexts($args) {
		$this->validate();

		$journal = Request::getJournal();

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$rangeInfo = $this->getRangeInfo('contexts');

		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $journal->getId());

		if ($version) {
			$this->setupTemplate(true, $version);

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/jquery.tablednd.js');
			$templateMgr->addJavaScript('lib/pkp/js/functions/tablednd.js');

			$templateMgr->assign_by_ref('version', $version);

			import('lib.pkp.classes.core.ArrayItemIterator');
			$templateMgr->assign_by_ref('contexts', new ArrayItemIterator($version->getContexts(), $rangeInfo->getPage(), $rangeInfo->getCount()));

			$templateMgr->assign('helpTopicId', 'journal.managementPages.readingTools.contexts');
			$templateMgr->display('rtadmin/contexts.tpl');
		}
		else Request::redirect(null, null, 'versions');
	}

	function editContext($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $journal->getId());
		$contextId = isset($args[1])?$args[1]:0;
		$context =& $rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			import('classes.rt.ojs.form.ContextForm');
			$this->setupTemplate(true, $version, $context);
			$contextForm = new ContextForm($contextId, $versionId);
			$contextForm->initData();
			$contextForm->display();
		}
		else Request::redirect(null, null, 'contexts', $versionId);


	}

	function deleteContext($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $journal->getId());
		$contextId = isset($args[1])?$args[1]:0;
		$context =& $rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			$rtDao->deleteContext($contextId, $versionId);
		}

		Request::redirect(null, null, 'contexts', $versionId);
	}

	function saveContext($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $journal->getId());
		$contextId = isset($args[1])?$args[1]:0;
		$context =& $rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			import('classes.rt.ojs.form.ContextForm');
			$contextForm = new ContextForm($contextId, $versionId);
			$contextForm->readInputData();
			$contextForm->execute();
		}

		Request::redirect(null, null, 'contexts', $versionId);
	}

	function moveContext($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $journal->getId());
		$contextId = Request::getUserVar('id');
		$context =& $rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			$direction = Request::getUserVar('dir');
			if ($direction != null) {
				// moving with up or down arrow
				$isDown = $direction =='d';
				$context->setOrder($context->getOrder()+($isDown?1.5:-1.5));
			} else {
				// drag and drop
				$prevId = Request::getUserVar('prevId');
				if ($prevId == null)
					$prevSeq = 0;
				else {
					$prevContext =& $rtDao->getContext($prevId);
					$prevSeq = $prevContext->getOrder();
				}

				$context->setOrder($prevSeq + .5);
			}
			$rtDao->updateContext($context);
			$rtDao->resequenceContexts($version->getVersionId());
		}

		// Moving up or down with the arrows requires a page reload.
		// In the case of a drag and drop move, the display has been
		// updated on the client side, so no reload is necessary.
		if ($direction != null) {
			Request::redirect(null, null, 'contexts', $versionId);
		}
	}
}

?>

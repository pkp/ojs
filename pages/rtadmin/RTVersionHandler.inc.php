<?php

/**
 * @file pages/rtadmin/RTVersionHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RTVersionHandler
 * @ingroup pages_rtadmin
 *
 * @brief Handle Reading Tools administration requests -- setup section.
 */

import('pages.rtadmin.RTAdminHandler');

class RTVersionHandler extends RTAdminHandler {
	/**
	 * Constructor
	 **/
	function RTVersionHandler() {
		parent::RTAdminHandler();
	}
	
	function createVersion($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();

		import('classes.rt.ojs.form.VersionForm');
		$versionForm = new VersionForm(null, $journal->getId());

		if (isset($args[0]) && $args[0]=='save') {
			$versionForm->readInputData();
			$versionForm->execute();
			Request::redirect(null, null, 'versions');
		} else {
			$this->setupTemplate(true);
			$versionForm->display();
		}
	}

	function exportVersion($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $journal->getId());

		if ($version) {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign_by_ref('version', $version);

			$templateMgr->display('rtadmin/exportXml.tpl', 'application/xml');
		}
		else Request::redirect(null, null, 'versions');
	}

	function importVersion() {
		$this->validate();
		$journal =& Request::getJournal();

		$fileField = 'versionFile';
		if (isset($_FILES[$fileField]['tmp_name']) && is_uploaded_file($_FILES[$fileField]['tmp_name'])) {
			$rtAdmin = new JournalRTAdmin($journal->getId());
			$rtAdmin->importVersion($_FILES[$fileField]['tmp_name']);
		}
		Request::redirect(null, null, 'versions');
	}

	function restoreVersions() {
		$this->validate();

		$journal =& Request::getJournal();
		$rtAdmin = new JournalRTAdmin($journal->getId());
		$rtAdmin->restoreVersions();

		// If the journal RT was configured, change its state to
		// "disabled" because the RT version it was configured for
		// has now been deleted.
		$rtDao =& DAORegistry::getDAO('RTDAO');
		$journalRt = $rtDao->getJournalRTByJournal($journal);
		if ($journalRt) {
			$journalRt->setVersion(null);
			$rtDao->updateJournalRT($journalRt);
		}

		Request::redirect(null, null, 'versions');
	}

	function versions() {
		$this->validate();
		$this->setupTemplate(true);

		$journal = Request::getJournal();

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$rangeInfo = $this->getRangeInfo('versions');

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('versions', $rtDao->getVersions($journal->getId(), $rangeInfo));
		$templateMgr->assign('helpTopicId', 'journal.managementPages.readingTools.versions');
		$templateMgr->display('rtadmin/versions.tpl');
	}

	function editVersion($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $journal->getId());

		if (isset($version)) {
			import('classes.rt.ojs.form.VersionForm');
			$this->setupTemplate(true, $version);
			$versionForm = new VersionForm($versionId, $journal->getId());
			$versionForm->initData();
			$versionForm->display();
		}
		else Request::redirect(null, null, 'versions');
	}

	function deleteVersion($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;

		$rtDao->deleteVersion($versionId, $journal->getId());

		Request::redirect(null, null, 'versions');
	}

	function saveVersion($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $journal->getId());

		if (isset($version)) {
			import('classes.rt.ojs.form.VersionForm');
			$versionForm = new VersionForm($versionId, $journal->getId());
			$versionForm->readInputData();
			$versionForm->execute();
		}

		Request::redirect(null, null, 'versions');
	}
}

?>

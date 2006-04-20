<?php

/**
 * RTVersionHandler.inc.php
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

class RTVersionHandler extends RTAdminHandler {
	function createVersion($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();

		import('rt.ojs.form.VersionForm');
		$versionForm = &new VersionForm(null, $journal->getJournalId());

		if (isset($args[0]) && $args[0]=='save') {
			$versionForm->readInputData();
			$versionForm->execute();
			Request::redirect(null, null, 'versions');
		} else {
			RTAdminHandler::setupTemplate(true);
			$versionForm->display();
		}
	}

	function exportVersion($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());

		if ($version) {
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign_by_ref('version', $version);

			$templateMgr->display('rtadmin/exportXml.tpl', 'application/xml');
		}
		else Request::redirect(null, null, 'versions');
	}

	function importVersion() {
		RTAdminHandler::validate();
		$journal = &Request::getJournal();

		$fileField = 'versionFile';
		if (isset($_FILES[$fileField]['tmp_name']) && is_uploaded_file($_FILES[$fileField]['tmp_name'])) {
			$rtAdmin = &new JournalRTAdmin($journal->getJournalId());
			$rtAdmin->importVersion($_FILES[$fileField]['tmp_name']);
		}
		Request::redirect(null, null, 'versions');
	}

	function restoreVersions() {
		RTAdminHandler::validate();

		$journal = &Request::getJournal();
		$rtAdmin = &new JournalRTAdmin($journal->getJournalId());
		$rtAdmin->restoreVersions();

		// If the journal RT was configured, change its state to
		// "disabled" because the RT version it was configured for
		// has now been deleted.
		$rtDao = &DAORegistry::getDAO('RTDAO');
		$journalRt = $rtDao->getJournalRTByJournalId($journal->getJournalId());
		if ($journalRt) {
			$journalRt->setVersion(null);
			$rtDao->updateJournalRT($journalRt);
		}

		Request::redirect(null, null, 'versions');
	}

	function versions() {
		RTAdminHandler::validate();
		RTAdminHandler::setupTemplate(true);

		$journal = Request::getJournal();

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$rangeInfo = Handler::getRangeInfo('versions');

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('versions', $rtDao->getVersions($journal->getJournalId(), $rangeInfo));
		$templateMgr->assign('helpTopicId', 'journal.managementPages.readingTools.versions');
		$templateMgr->display('rtadmin/versions.tpl');
	}

	function editVersion($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());

		if (isset($version)) {
			import('rt.ojs.form.VersionForm');
			RTAdminHandler::setupTemplate(true, $version);
			$versionForm = &new VersionForm($versionId, $journal->getJournalId());
			$versionForm->initData();
			$versionForm->display();
		}
		else Request::redirect(null, null, 'versions');
	}

	function deleteVersion($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;

		$rtDao->deleteVersion($versionId, $journal->getJournalId());

		Request::redirect(null, null, 'versions');
	}

	function saveVersion($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$journal = Request::getJournal();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $journal->getJournalId());

		if (isset($version)) {
			import('rt.ojs.form.VersionForm');
			$versionForm = &new VersionForm($versionId, $journal->getJournalId());
			$versionForm->readInputData();
			$versionForm->execute();
		}

		Request::redirect(null, null, 'versions');
	}
}

?>

<?php

/**
 * @file AdminJournalHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.admin
 * @class AdminJournalHandler
 *
 * Handle requests for journal management in site administration. 
 *
 * $Id$
 */

class AdminJournalHandler extends AdminHandler {

	/**
	 * Display a list of the journals hosted on the site.
	 */
	function journals() {
		parent::validate();
		parent::setupTemplate(true);

		$rangeInfo = Handler::getRangeInfo('journals');

		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$journals = &$journalDao->getJournals($rangeInfo);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('journals', $journals);
		$templateMgr->assign('helpTopicId', 'site.siteManagement');
		$templateMgr->display('admin/journals.tpl');
	}

	/**
	 * Display form to create a new journal.
	 */
	function createJournal() {
		AdminJournalHandler::editJournal();
	}

	/**
	 * Display form to create/edit a journal.
	 * @param $args array optional, if set the first parameter is the ID of the journal to edit
	 */
	function editJournal($args = array()) {
		parent::validate();
		parent::setupTemplate(true);

		import('admin.form.JournalSiteSettingsForm');

		$settingsForm = &new JournalSiteSettingsForm(!isset($args) || empty($args) ? null : $args[0]);
		if ($settingsForm->isLocaleResubmit()) {
			$settingsForm->readInputData();
		} else {
			$settingsForm->initData();
		}
		$settingsForm->display();
	}

	/**
	 * Save changes to a journal's settings.
	 */
	function updateJournal() {
		parent::validate();

		import('admin.form.JournalSiteSettingsForm');

		$settingsForm = &new JournalSiteSettingsForm(Request::getUserVar('journalId'));
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			PluginRegistry::loadCategory('blocks');
			$settingsForm->execute();
			Request::redirect(null, null, 'journals');

		} else {
			parent::setupTemplate(true);
			$settingsForm->display();
		}
	}

	/**
	 * Delete a journal.
	 * @param $args array first parameter is the ID of the journal to delete
	 */
	function deleteJournal($args) {
		parent::validate();

		$journalDao = &DAORegistry::getDAO('JournalDAO');

		if (isset($args) && !empty($args) && !empty($args[0])) {
			$journalId = $args[0];
			if ($journalDao->deleteJournalById($journalId)) {
				// Delete journal file tree
				// FIXME move this somewhere better.
				import('file.FileManager');
				$fileManager = &new FileManager();

				$journalPath = Config::getVar('files', 'files_dir') . '/journals/' . $journalId;
				$fileManager->rmtree($journalPath);

				import('file.PublicFileManager');
				$publicFileManager = &new PublicFileManager();
				$publicFileManager->rmtree($publicFileManager->getJournalFilesPath($journalId));
			}
		}

		Request::redirect(null, null, 'journals');
	}

	/**
	 * Change the sequence of a journal on the site index page.
	 */
	function moveJournal() {
		parent::validate();

		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$journal = &$journalDao->getJournal(Request::getUserVar('journalId'));

		if ($journal != null) {
			$journal->setSequence($journal->getSequence() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$journalDao->updateJournal($journal);
			$journalDao->resequenceJournals();
		}

		Request::redirect(null, null, 'journals');
	}

	/**
	 * Show form to import data from an OJS 1.x journal.
	 */
	function importOJS1() {
		parent::validate();
		parent::setupTemplate(true);

		import('admin.form.ImportOJS1Form');

		$importForm = &new ImportOJS1Form();
		$importForm->initData();
		$importForm->display();
	}

	/**
	 * Import data from an OJS 1.x journal.
	 */
	function doImportOJS1() {
		parent::validate();

		import('admin.form.ImportOJS1Form');

		$importForm = &new ImportOJS1Form();
		$importForm->readInputData();

		if ($importForm->validate() && ($journalId = $importForm->execute()) !== false) {
			$redirects = $importForm->getRedirects();
			$conflicts = $importForm->getConflicts();

			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('journalId', $journalId);
			$templateMgr->assign('redirects', $redirects);
			$templateMgr->assign('conflicts', $conflicts);
			$templateMgr->display('admin/importComplete.tpl');
		} else {
			parent::setupTemplate(true);
			$importForm->display();
		}
	}

}

?>

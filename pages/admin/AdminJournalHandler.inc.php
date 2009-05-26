<?php

/**
 * @file pages/admin/AdminJournalHandler.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminJournalHandler
 * @ingroup pages_admin
 *
 * @brief Handle requests for journal management in site administration. 
 */

// $Id$

import('pages.admin.AdminHandler');

class AdminJournalHandler extends AdminHandler {
	/**
	 * Constructor
	 **/
	function AdminJournalHandler() {
		parent::AdminHandler();
	}

	/**
	 * Display a list of the journals hosted on the site.
	 */
	function journals() {
		$this->validate();
		$this->setupTemplate();

		$rangeInfo = Handler::getRangeInfo('journals');

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journals =& $journalDao->getJournals($rangeInfo);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('journals', $journals);
		$templateMgr->assign('helpTopicId', 'site.siteManagement');
		$templateMgr->display('admin/journals.tpl');
	}

	/**
	 * Display form to create a new journal.
	 */
	function createJournal() {
		$this->editJournal();
	}

	/**
	 * Display form to create/edit a journal.
	 * @param $args array optional, if set the first parameter is the ID of the journal to edit
	 */
	function editJournal($args = array()) {
		$this->validate();
		$this->setupTemplate();

		import('admin.form.JournalSiteSettingsForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$settingsForm =& new JournalSiteSettingsForm(!isset($args) || empty($args) ? null : $args[0]);
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
		$this->validate();

		import('admin.form.JournalSiteSettingsForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$settingsForm =& new JournalSiteSettingsForm(Request::getUserVar('journalId'));
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			PluginRegistry::loadCategory('blocks');
			$settingsForm->execute();

			Request::redirect(null, null, 'journals');

		} else {
			$this->setupTemplate();
			$settingsForm->display();
		}
	}

	/**
	 * Delete a journal.
	 * @param $args array first parameter is the ID of the journal to delete
	 */
	function deleteJournal($args) {
		$this->validate();

		$journalDao =& DAORegistry::getDAO('JournalDAO');

		if (isset($args) && !empty($args) && !empty($args[0])) {
			$journalId = $args[0];
			if ($journalDao->deleteJournalById($journalId)) {
				// Delete journal file tree
				// FIXME move this somewhere better.
				import('file.FileManager');
				$fileManager = new FileManager();

				$journalPath = Config::getVar('files', 'files_dir') . '/journals/' . $journalId;
				$fileManager->rmtree($journalPath);

				import('file.PublicFileManager');
				$publicFileManager = new PublicFileManager();
				$publicFileManager->rmtree($publicFileManager->getJournalFilesPath($journalId));
			}
		}

		Request::redirect(null, null, 'journals');
	}

	/**
	 * Change the sequence of a journal on the site index page.
	 */
	function moveJournal() {
		$this->validate();

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journal =& $journalDao->getJournal(Request::getUserVar('journalId'));

		if ($journal != null) {
			$journal->setSequence($journal->getSequence() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$journalDao->updateJournal($journal);
			$journalDao->resequenceJournals();
		}

		Request::redirect(null, null, 'journals');
	}

	/**
	 * Set up the template.
	 */
	function setupTemplate() {
		parent::setupTemplate(true);
		Locale::requireComponents(array(LOCALE_COMPONENT_OJS_MANAGER));
	}
}

?>

<?php

/**
 * JournalSiteSettingsForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package admin.form
 *
 * Form for site administrator to edit basic journal settings.
 *
 * $Id$
 */

import('db.DBDataXMLParser');

class JournalSiteSettingsForm extends Form {

	/** The ID of the journal being edited */
	var $journalId;
	
	/**
	 * Constructor.
	 * @param $journalId omit for a new journal
	 */
	function JournalSiteSettingsForm($journalId = null) {
		parent::Form('admin/journalSettings.tpl');
		
		$this->journalId = isset($journalId) ? (int) $journalId : null;
		
		// Validation checks for this form
		$this->addCheck(new FormValidator(&$this, 'title', 'required', 'admin.journals.form.titleRequired'));
		$this->addCheck(new FormValidator(&$this, 'path', 'required', 'admin.journals.form.pathRequired'));
		$this->addCheck(new FormValidatorAlphaNum(&$this, 'path', 'required', 'admin.journals.form.pathAlphaNumeric'));
		$this->addCheck(new FormValidatorCustom(&$this, 'path', 'required', 'admin.journals.form.pathExists', create_function('$path,$form,$journalDao', 'return !$journalDao->journalExistsByPath($path) || ($form->getData(\'oldPath\') != null && $form->getData(\'oldPath\') == $path);'), array(&$this, DAORegistry::getDAO('JournalDAO'))));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('journalId', $this->journalId);
		$templateMgr->assign('helpTopicId', 'site.siteManagement');
		parent::display();
	}
	
	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		if (isset($this->journalId)) {
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journal = &$journalDao->getJournal($this->journalId);
			
			if ($journal != null) {
				$this->_data = array(
					'title' => $journal->getTitle(),
					'description' => $journal->getDescription(),
					'path' => $journal->getPath(),
					'enabled' => $journal->getEnabled()
				);

			} else {
				$this->journalId = null;
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'description', 'path', 'enabled'));
		$this->setData('enabled', (int)$this->getData('enabled'));
		
		if (isset($this->journalId)) {
			$journalDao = &DAORegistry::getDAO('JournalDAO');
			$journal = &$journalDao->getJournal($this->journalId);
			$this->setData('oldPath', $journal->getPath());
		}
	}
	
	/**
	 * Save journal settings.
	 */
	function execute() {
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		
		if (isset($this->journalId)) {
			$journal = &$journalDao->getJournal($this->journalId);
		}
		
		if (!isset($journal)) {
			$journal = &new Journal();
		}
		
		$journal->setTitle($this->getData('title'));
		$journal->setDescription($this->getData('description'));
		$journal->setPath($this->getData('path'));
		$journal->setEnabled($this->getData('enabled'));
		
		if ($journal->getJournalId() != null) {
			$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
			$journalSettingsDao->updateSetting($journal->getJournalId(), 'journalUrl', Request::getIndexUrl() . '/' . $journal->getPath(), 'string');
			$journalDao->updateJournal($journal);
			
		} else {
			$journalDao->insertJournal($journal);
			$journalId = $journalDao->getInsertJournalId();
			$journalDao->resequenceJournals();
			
			// Make the site administrator the journal manager of newly created journals
			$sessionManager = &SessionManager::getManager();
			$userSession = &$sessionManager->getUserSession();
			if ($userSession->getUserId() != null && $userSession->getUserId() != 0 && !empty($journalId)) {
				$role = &new Role();
				$role->setJournalId($journalId);
				$role->setUserId($userSession->getUserId());
				$role->setRoleId(ROLE_ID_JOURNAL_MANAGER);
				
				$roleDao = DAORegistry::getDAO('RoleDAO');
				$roleDao->insertRole($role);
			}
			
			// Make the file directories for the journal
			FileManager::mkdir(Config::getVar('files', 'files_dir') . '/journals/' . $journalId);
			FileManager::mkdir(Config::getVar('files', 'files_dir'). '/journals/' . $journalId . '/articles');
			FileManager::mkdir(Config::getVar('files', 'files_dir'). '/journals/' . $journalId . '/issues');
			FileManager::mkdir(Config::getVar('files', 'public_files_dir') . '/journals/' . $journalId);

			// Install default journal settings
			$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
			$journalSettingsDao->installSettings($journalId, 'registry/journalSettings.xml', array(
				'indexUrl' => Request::getIndexUrl(),
				'journalPath' => $this->getData('path'),
				'journalName' => $this->getData('title')
			));
		}
	}
	
}

?>

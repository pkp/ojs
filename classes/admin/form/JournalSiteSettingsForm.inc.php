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

class JournalSiteSettingsForm extends Form {

	/** The ID of the journal being edited */
	var $journalId;
	
	/**
	 * Constructor.
	 * @param $journalId omit for a new journal
	 */
	function JournalSiteSettingsForm($journalId = null) {
		parent::Form('admin/journalSettings.tpl');
		
		$this->journalId = $journalId;
		
		// Validation checks for this form
		$this->addCheck(new FormValidator(&$this, 'title', 'required', 'admin.journals.form.titleRequired'));
		$this->addCheck(new FormValidator(&$this, 'path', 'required', 'admin.journals.form.pathRequired'));
		$this->addCheck(new FormValidatorAlphaNum(&$this, 'path', 'required', 'admin.journals.form.pathAlphaNumeric'));
		$this->addCheck(new FormValidatorCustom(&$this, 'path', 'required', 'admin.journals.form.pathExists', array(DAORegistry::getDAO('JournalDAO'), 'journalExistsByPath'), true));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('journalId', $this->journalId);
		
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
					'path' => $journal->getPath()
				);
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->_data = array(
			'title' => Request::getUserVar('title'),
			'path' => Request::getUserVar('path')
		);
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
		$journal->setPath($this->getData('path'));
		
		if ($journal->getJournalId() != null) {
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
		}
	}
	
}

?>

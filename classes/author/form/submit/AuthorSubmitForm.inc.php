<?php

/**
 * AuthorSubmitForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package author.form.submit
 *
 * Base class for journal author submit forms.
 *
 * $Id$
 */


class AuthorSubmitForm extends Form {
	var $step;
	var $settings;
	
	/**
	 * Constructor.
	 * @param $step the step number
	 * @param $settings an associative array with the setting names as keys and associated types as values
	 */
	function AuthorSubmitForm($step, $settings) {
		parent::Form(sprintf('author/submit/step%d.tpl', $step));
		$this->step = $step;
		$this->settings = $settings;
	}
	
	function display() {
		$templateManager = &TemplateManager::getManager();
		$templateManager->assign('leftSidebarTemplate', 'author/submit/submitSidebar.tpl');
		$templateManager->assign('setupStep', $this->step);
		parent::display();
	}
	
	function initData() {
		$journal = &Request::getJournal();
		$this->_data = $journal->getSettings();
	}
	
	function readInputData() {		
		$this->readUserVars(array_keys($this->settings));
	}
	
	function execute() {
		$journal = &Request::getJournal();
		$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		
		foreach ($this->_data as $name => $value) {
			$settingsDao->updateSetting(
				$journal->getJournalId(),
				$name,
				$value,
				$this->settings[$name]
			);
		}
	}
}

?>

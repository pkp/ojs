<?php

/**
 * JournalSetupForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 *
 * Base class for journal setup forms.
 *
 * $Id$
 */

import("manager.form.setup.JournalSetupForm");

class JournalSetupForm extends Form {
	var $step;
	var $settings;
	
	/**
	 * Constructor.
	 * @param $step the step number
	 * @param $settings an associative array with the setting names as keys and associated types as values
	 */
	function JournalSetupForm($step, $settings) {
		parent::Form(sprintf('manager/setup/step%d.tpl', $step));
		$this->step = $step;
		$this->settings = $settings;
	}
	
	function display() {
		$templateManager = &TemplateManager::getManager();
		$templateManager->assign('leftSidebarTemplate', 'manager/setup/setupSidebar.tpl');
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

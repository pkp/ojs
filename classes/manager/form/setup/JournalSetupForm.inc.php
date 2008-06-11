<?php

/**
 * @file JournalSetupForm.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 * @class JournalSetupForm
 *
 * Base class for journal setup forms.
 *
 * $Id$
 */

import("manager.form.setup.JournalSetupForm");
import('form.Form');

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
		$this->addCheck(new FormValidatorPost($this));
		$this->step = $step;
		$this->settings = $settings;
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('setupStep', $this->step);
		$templateMgr->assign('helpTopicId', 'journal.managementPages.setup');
		$templateMgr->setCacheability(CACHEABILITY_MUST_REVALIDATE);
		parent::display();
	}

	/**
	 * Initialize data from current settings.
	 */
	function initData() {
		$journal = &Request::getJournal();
		$this->_data = $journal->getSettings();
	}

	/**
	 * Read user input.
	 */
	function readInputData() {		
		$this->readUserVars(array_keys($this->settings));
	}

	/**
	 * Save modified settings.
	 */
	function execute() {
		$journal = &Request::getJournal();
		$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');

		foreach ($this->_data as $name => $value) {
			if (isset($this->settings[$name])) {
				$isLocalized = in_array($name, $this->getLocaleFieldNames());
				$settingsDao->updateSetting(
					$journal->getJournalId(),
					$name,
					$value,
					$this->settings[$name],
					$isLocalized
				);
			}
		}
	}
}

?>

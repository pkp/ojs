<?php

/**
 * @file LanguageSettingsForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 * @class LanguageSettingsForm
 *
 * Form for modifying journal language settings.
 *
 * $Id$
 */

import('form.Form');

class LanguageSettingsForm extends Form {

	/** @var array the setting names */
	var $settings;

	/** @var array set of locales available for journal use */
	var $availableLocales;

	/**
	 * Constructor.
	 */
	function LanguageSettingsForm() {
		parent::Form('manager/languageSettings.tpl');

		$this->settings = array(
			'supportedLocales' => 'object'
		);

		$site = &Request::getSite();
		$this->availableLocales = $site->getSupportedLocales();

		$localeCheck = create_function('$locale,$availableLocales', 'return in_array($locale,$availableLocales);');

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'primaryLocale', 'required', 'manager.languages.form.primaryLocaleRequired'), array('Locale', 'isLocaleValid'));
		$this->addCheck(new FormValidator($this, 'primaryLocale', 'required', 'manager.languages.form.primaryLocaleRequired'), $localeCheck, array(&$this->availableLocales));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$site = &Request::getSite();
		$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());
		$templateMgr->assign('helpTopicId','journal.managementPages.languages');
		parent::display();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$journal = &Request::getJournal();
		foreach ($this->settings as $settingName => $settingType) {
			$this->_data[$settingName] = $journal->getSetting($settingName);
		}

		$this->setData('primaryLocale', $journal->getPrimaryLocale());

		if ($this->getData('supportedLocales') == null || !is_array($this->getData('supportedLocales'))) {
			$this->setData('supportedLocales', array());
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$vars = array_keys($this->settings);
		$vars[] = 'primaryLocale';
		$this->readUserVars($vars);

		if ($this->getData('supportedLocales') == null || !is_array($this->getData('supportedLocales'))) {
			$this->setData('supportedLocales', array());
		}
	}

	/**
	 * Save modified settings.
	 */
	function execute() {
		$journal = &Request::getJournal();
		$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');

		// Verify additional locales
		$supportedLocales = array();
		foreach ($this->getData('supportedLocales') as $locale) {
			if (Locale::isLocaleValid($locale) && in_array($locale, $this->availableLocales)) {
				array_push($supportedLocales, $locale);
			}
		}

		$primaryLocale = $this->getData('primaryLocale');

		if ($primaryLocale != null && !empty($primaryLocale) && !in_array($primaryLocale, $supportedLocales)) {
			array_push($supportedLocales, $primaryLocale);
		}
		$this->setData('supportedLocales', $supportedLocales);

		foreach ($this->_data as $name => $value) {
			if (!in_array($this->settings, $name)) continue;
			$settingsDao->updateSetting(
				$journal->getJournalId(),
				$name,
				$value,
				$this->settings[$name]
			);
		}

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journal->setPrimaryLocale($this->getData('primaryLocale'));
		$journalDao->updateJournal($journal);
	}

}

?>

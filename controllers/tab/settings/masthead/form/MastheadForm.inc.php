<?php

/**
 * @file controllers/tab/settings/masthead/form/MastheadForm.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MastheadForm
 * @ingroup controllers_tab_settings_masthead_form
 *
 * @brief Form to edit masthead settings.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class MastheadForm extends ContextSettingsForm {

	/**
	 * Constructor.
	 */
	function MastheadForm($wizardMode = false) {
		$settings = array(
			'name' => 'string',
			'acronym' => 'string',
			'abbreviation' => 'string',
			'printIssn' => 'string',
			'onlineIssn' => 'string',
			'description' => 'string',
			'mailingAddress' => 'string',
			'journalEnabled' => 'bool',
			'masthead' => 'string'
		);

		parent::ContextSettingsForm($settings, 'controllers/tab/settings/masthead/form/mastheadForm.tpl', $wizardMode);

		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'manager.setup.form.journalNameRequired'));
		$this->addCheck(new FormValidatorLocale($this, 'acronym', 'required', 'manager.setup.form.journalInitialsRequired'));
		$this->addCheck(new FormValidatorISSN($this, 'printIssn', 'optional', 'manager.setup.form.issnInvalid'));
		$this->addCheck(new FormValidatorISSN($this, 'onlineIssn', 'optional', 'manager.setup.form.issnInvalid'));

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_ADMIN);
	}

	//
	// Implement template methods from Form.
	//
	/**
	 * Get all locale field names
	 */
	function getLocaleFieldNames() {
		return array('name', 'acronym', 'abbreviation', 'description', 'masthead');
	}

	//
	// Overridden methods from ContextSettingsForm.
	//
	/**
	 * @see ContextSettingsForm::initData.
	 * @param $request Request
	 */
	function initData(&$request) {
		parent::initData($request);

		$journal = $request->getContext();
		$this->setData('enabled', (int) $journal->getEnabled());
		if ($this->getData('acronym') == null) {
			$acronym = array();
			foreach (array_keys($this->supportedLocales) as $locale) {
				$acronym[$locale] = $journal->getPath();
			}
			$this->setData('acronym', $acronym);
		}
	}

	/**
	 * @see ContextSettingsForm::execute()
	 * @param $request Request
	 */
	function execute(&$request) {
		$journal = $request->getContext();

		if ($journal->getEnabled() !== $this->getData('journalEnabled')) {
			$journalDao = DAORegistry::getDAO('PressDAO');
			$journal->setEnabled($this->getData('pressEnabled'));
			$journalDao->updateObject($journal);
		}

		parent::execute($request);
	}
}

?>

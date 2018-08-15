<?php

/**
 * @file controllers/tab/settings/masthead/form/MastheadForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
	function __construct($wizardMode = false) {
		$settings = array(
			'name' => 'string',
			'acronym' => 'string',
			'abbreviation' => 'string',
			'publisherInstitution' => 'string',
			'printIssn' => 'string',
			'onlineIssn' => 'string',
			'description' => 'string',
			'editorialTeam' => 'string',
			'about' => 'string',
		);

		parent::__construct($settings, 'controllers/tab/settings/masthead/form/mastheadForm.tpl', $wizardMode);

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
		return array('name', 'acronym', 'abbreviation', 'description', 'editorialTeam', 'about');
	}

	//
	// Overridden methods from ContextSettingsForm.
	//
	/**
	 * @copydoc ContextSettingsForm::initData()
	 */
	function initData() {
		parent::initData();

		$request = Application::getRequest();
		$journal = $request->getContext();
		if ($this->getData('acronym') == null) {
			$acronym = array();
			foreach (array_keys($this->supportedLocales) as $locale) {
				$acronym[$locale] = $journal->getPath();
			}
			$this->setData('acronym', $acronym);
		}
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $params = null) {
		$site = $request->getSite();
		$templateMgr = TemplateManager::getManager($request);
		return parent::fetch($request, $params);
	}
}



<?php

/**
 * @file controllers/tab/settings/masthead/form/MastheadForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MastheadForm
 * @ingroup controllers_tab_settings_masthead_form
 *
 * @brief Form to edit masthead settings.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class MastheadForm extends ContextSettingsForm {
	/** @var array Used to unpack categories listbuilder */
	var $categories;

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
			'categories' => 'object',
			'masthead' => 'string',
			'history' => 'string'
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
		return array('name', 'acronym', 'abbreviation', 'description', 'masthead', 'history');
	}

	//
	// Overridden methods from ContextSettingsForm.
	//
	/**
	 * @copydoc ContextSettingsForm::initData()
	 */
	function initData($request) {
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
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $params = null) {
		$site = $request->getSite();
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('categoriesEnabled', $site->getSetting('categoriesEnabled'));
		return parent::fetch($request, $params);
	}

	/**
	 * @copydoc ContextSettingsForm::execute()
	 * @param $request Request
	 */
	function execute($request) {
		$journal = $request->getContext();

		if ($journal->getEnabled() !== $this->getData('journalEnabled')) {
			$journalDao = DAORegistry::getDAO('JournalDAO');
			$journal->setEnabled($this->getData('journalEnabled'));
			$journalDao->updateObject($journal);
		}

		// Save block plugins context positions.
		import('lib.pkp.classes.controllers.listbuilder.ListbuilderHandler');
		$this->categories = null;
		ListbuilderHandler::unpack($request, $request->getUserVar('categories'));
		$this->setData('categories', $this->categories);

		parent::execute($request);
	}

	/**
	 * @copydoc ListbuilderHandler::updateEntry()
	 */
	function updateEntry($request, $rowId, $newRowId) {
		$this->deleteEntry($request, $rowId);
		$this->insertEntry($request, $newRowId);
		return true;
	}

	/**
	 * @copydoc ListbuilderHandler::deleteEntry()
	 */
	function deleteEntry($request, $rowId) {
		if (isset($this->categories[$rowId['name']])) unset($this->categories[$rowId['name']]);
		return true;
	}

	/**
	 * @copydoc ListbuilderHandler::insertEntry()
	 */
	function insertEntry($request, $rowId) {
		$this->categories[$rowId['name']] = true;
		return true;
	}

}

?>

<?php

/**
 * @file controllers/tab/settings/details/form/DetailsForm.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Details
 * @ingroup controllers_tab_settings_details_form
 *
 * @brief Form for Step 1 of journal setup.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class DetailsForm extends ContextSettingsForm {
	/**
	 * Constructor.
	 */
	function DetailsForm($wizardMode = false) {
		$settings = array(
			'name' => 'string',
			'acronym' => 'string',
			'abbreviation' => 'string',
			'printIssn' => 'string',
			'onlineIssn' => 'string',
			'mailingAddress' => 'string',
			'categories' => 'object',
			'useEditorialBoard' => 'bool',
			'publisherInstitution' => 'string',
			'publisherUrl' => 'string',
			'publisherNote' => 'string',
			'history' => 'string',
			'envelopeSender' => 'string',
			'emailHeader' => 'string',
			'searchDescription' => 'string',
			'searchKeywords' => 'string',
			'customHeaders' => 'string'
		);
		parent::ContextSettingsForm($settings, 'controllers/tab/settings/details/form/detailsForm.tpl', $wizardMode);

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'manager.setup.form.journalTitleRequired'));
		$this->addCheck(new FormValidatorLocale($this, 'acronym', 'required', 'manager.setup.form.journalInitialsRequired'));
		$this->addCheck(new FormValidator($this, 'contactName', 'required', 'manager.setup.form.contactNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'contactEmail', 'required', 'manager.setup.form.contactEmailRequired'));
		$this->addCheck(new FormValidator($this, 'supportName', 'required', 'manager.setup.form.supportNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'supportEmail', 'required', 'manager.setup.form.supportEmailRequired'));
		$this->addCheck(new FormValidatorISSN($this, 'printIssn', 'optional', 'manager.setup.form.issnInvalid'));
		$this->addCheck(new FormValidatorISSN($this, 'onlineIssn', 'optional', 'manager.setup.form.issnInvalid'));
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('name', 'acronym', 'abbreviation', 'contactTitle', 'contactAffiliation', 'contactMailingAddress', 'publisherNote', 'history', 'searchDescription', 'searchKeywords', 'customHeaders');
	}

	/**
	 * Execute the form.
	 * @param $request PKPRequest
	 */
	function execute($request) {
		// In case the category list changed, flush the cache.
		$categoryDao = DAORegistry::getDAO('CategoryDAO');
		$categoryDao->rebuildCache();

		return parent::execute($request);
	}

	/**
	 * Fetch the form.
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		if (Config::getVar('email', 'allow_envelope_sender'))
			$templateMgr->assign('envelopeSenderEnabled', true);

		// If Categories are enabled by Site Admin, make selection
		// tools available to Journal Manager
		$categoryDao = DAORegistry::getDAO('CategoryDAO');
		$categories = $categoryDao->getCategories();
		$site = $request->getSite();
		if ($site->getSetting('categoriesEnabled') && !empty($categories)) {
			$templateMgr->assign('categoriesEnabled', true);
			$templateMgr->assign('allCategories', $categories);
		}

		return parent::fetch($request);
	}
}

?>

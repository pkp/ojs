<?php

/**
 * @file classes/manager/form/setup/JournalSetupStep1Form.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalSetupStep1Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 1 of journal setup.
 */

import('classes.manager.form.setup.JournalSetupForm');

class JournalSetupStep1Form extends JournalSetupForm {
	/**
	 * Constructor.
	 */
	function JournalSetupStep1Form() {
		parent::JournalSetupForm(
			1,
			array(
				'title' => 'string',
				'initials' => 'string',
				'abbreviation' => 'string',
				'printIssn' => 'string',
				'onlineIssn' => 'string',
				'mailingAddress' => 'string',
				'categories' => 'object',
				'useEditorialBoard' => 'bool',
				'contactName' => 'string',
				'contactTitle' => 'string',
				'contactAffiliation' => 'string',
				'contactEmail' => 'string',
				'contactPhone' => 'string',
				'contactFax' => 'string',
				'contactMailingAddress' => 'string',
				'supportName' => 'string',
				'supportEmail' => 'string',
				'supportPhone' => 'string',
				'sponsorNote' => 'string',
				'sponsors' => 'object',
				'publisherInstitution' => 'string',
				'publisherUrl' => 'string',
				'publisherNote' => 'string',
				'contributorNote' => 'string',
				'contributors' => 'object',
				'history' => 'string',
				'envelopeSender' => 'string',
				'emailHeader' => 'string',
				'emailSignature' => 'string',
				'searchDescription' => 'string',
				'searchKeywords' => 'string',
				'customHeaders' => 'string'
			)
		);

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'manager.setup.form.journalTitleRequired'));
		$this->addCheck(new FormValidatorLocale($this, 'initials', 'required', 'manager.setup.form.journalInitialsRequired'));
		$this->addCheck(new FormValidator($this, 'contactName', 'required', 'manager.setup.form.contactNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'contactEmail', 'required', 'manager.setup.form.contactEmailRequired'));
		$this->addCheck(new FormValidator($this, 'supportName', 'required', 'manager.setup.form.supportNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'supportEmail', 'required', 'manager.setup.form.supportEmailRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'envelopeSender', 'optional', 'user.profile.form.emailRequired'));
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'initials', 'abbreviation', 'contactTitle', 'contactAffiliation', 'contactMailingAddress', 'sponsorNote', 'publisherNote', 'contributorNote', 'history', 'searchDescription', 'searchKeywords', 'customHeaders');
	}

	/**
	 * Execute the form, but first:
	 * Make sure we're not saving an empty entry for sponsors. (This would
	 * result in a possibly empty heading for the Sponsors section in About
	 * the Journal.)
	 */
	function execute() {
		foreach (array('sponsors', 'contributors') as $element) {
			$elementValue = (array) $this->getData($element);
			foreach (array_keys($elementValue) as $key) {
				$values = array_values((array) $elementValue[$key]);
				$isEmpty = true;
				foreach ($values as $value) {
					if (!empty($value)) $isEmpty = false;
				}
				if ($isEmpty) unset($elementValue[$key]);
			}
			$this->setData($element, $elementValue);
		}

		// In case the category list changed, flush the cache.
		$categoryDao =& DAORegistry::getDAO('CategoryDAO');
		$categoryDao->rebuildCache();

		return parent::execute();
	}

	/**
	 * Display the form.
	 */
	function display($request, $dispatcher) {
		$templateMgr =& TemplateManager::getManager();
		if (Config::getVar('email', 'allow_envelope_sender') && !(Config::getVar('email', 'force_default_envelope_sender') && Config::getVar('email', 'default_envelope_sender')))
			$templateMgr->assign('envelopeSenderEnabled', true);

		// If Categories are enabled by Site Admin, make selection
		// tools available to Journal Manager
		$categoryDao =& DAORegistry::getDAO('CategoryDAO');
		$categories =& $categoryDao->getCategories();
		$site =& $request->getSite();
		if ($site->getSetting('categoriesEnabled') && !empty($categories)) {
			$templateMgr->assign('categoriesEnabled', true);
			$templateMgr->assign('allCategories', $categories);
		}

		parent::display($request, $dispatcher);
	}
}

?>

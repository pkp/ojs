<?php

/**
 * @file classes/manager/form/setup/JournalSetupStep1Form.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalSetupStep1Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 1 of journal setup.
 */

// $Id$


import("manager.form.setup.JournalSetupForm");

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
				'doiPrefix' => 'string',
				'doiSuffix' => 'string',
				'doiSuffixPattern' => 'string',
				'mailingAddress' => 'string',
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
				'envelopeSender' => 'string',
				'emailSignature' => 'string',
				'searchDescription' => 'string',
				'searchKeywords' => 'string',
				'customHeaders' => 'string'
			)
		);

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'manager.setup.form.journalTitleRequired'));
		$this->addCheck(new FormValidatorLocale($this, 'initials', 'required', 'manager.setup.form.journalInitialsRequired'));
		$this->addCheck(new FormValidatorRegExp($this, 'doiPrefix', 'optional', 'manager.setup.form.doiPrefixPattern', '/^10\.[0-9][0-9][0-9][0-9]$/'));
		$this->addCheck(new FormValidator($this, 'contactName', 'required', 'manager.setup.form.contactNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'contactEmail', 'required', 'manager.setup.form.contactEmailRequired'));
		$this->addCheck(new FormValidator($this, 'supportName', 'required', 'manager.setup.form.supportNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'supportEmail', 'required', 'manager.setup.form.supportEmailRequired'));
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'initials', 'abbreviation', 'sponsorNote', 'publisherNote', 'contributorNote', 'searchDescription', 'searchKeywords', 'customHeaders');
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

		return parent::execute();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		if (Config::getVar('email', 'allow_envelope_sender'))
			$templateMgr->assign('envelopeSenderEnabled', true);
		parent::display();
	}
}

?>

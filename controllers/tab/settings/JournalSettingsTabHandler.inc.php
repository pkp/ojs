<?php

/**
 * @file controllers/tab/settings/JournalSettingsTabHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalSettingsTabHandler
 * @ingroup controllers_tab_settings
 *
 * @brief Handle AJAX operations for tabs on Journal page.
 */

import('lib.pkp.controllers.tab.settings.ManagerSettingsTabHandler');

class JournalSettingsTabHandler extends ManagerSettingsTabHandler {
	/**
	 * Constructor
	 */
	function JournalSettingsTabHandler() {
		parent::ManagerSettingsTabHandler();
		$this->setPageTabs(array(
			'details' => 'controllers.tab.settings.details.form.DetailsForm',
			'masthead' => 'controllers.tab.settings.masthead.form.MastheadForm',
			'contact' => 'lib.pkp.controllers.tab.settings.contact.form.ContactForm',
			'policiesold' => 'controllers.tab.settings.policies.form.PoliciesForm',
			'policies' => 'lib.pkp.controllers.tab.settings.policies.form.PoliciesForm',
			'citations' => 'controllers.tab.settings.citations.form.CitationsForm',
			'submissions' => 'controllers.tab.settings.submissions.form.SubmissionsForm',
			'management' => 'controllers.tab.settings.management.form.ManagementForm',
			'guidelines' => 'lib.pkp.controllers.tab.settings.guidelines.form.GuidelinesForm',
			'affiliationAndSupport' => 'lib.pkp.controllers.tab.settings.affiliation.form.AffiliationForm',
		));
	}

	//
	// Overridden methods from Handler
	//
	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize(&$request, $args = null) {
		parent::initialize($request, $args);

		// Load grid-specific translations
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_USER);
	}
}

?>

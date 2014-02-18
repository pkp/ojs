<?php

/**
 * @file controllers/tab/settings/JournalSettingsTabHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
			'masthead' => 'controllers.tab.settings.masthead.form.MastheadForm',
			'contact' => 'lib.pkp.controllers.tab.settings.contact.form.ContactForm',
			'policies' => 'controllers.tab.settings.policies.form.OJSPoliciesForm',
			'citations' => 'controllers.tab.settings.citations.form.CitationsForm',
			'sections' => 'controllers/tab/settings/journal/sections.tpl',
			'guidelines' => 'lib.pkp.controllers.tab.settings.guidelines.form.GuidelinesForm',
			'affiliationAndSupport' => 'lib.pkp.controllers.tab.settings.affiliation.form.AffiliationForm',
		));
	}

	//
	// Overridden methods from Handler
	//
	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		// Load grid-specific translations
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_USER);
	}
}

?>

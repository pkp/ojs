<?php

/**
 * @file controllers/tab/settings/JournalSettingsTabHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
	function __construct() {
		parent::__construct();
		$this->setPageTabs(array(
			'masthead' => 'controllers.tab.settings.masthead.form.MastheadForm',
			'contact' => 'lib.pkp.controllers.tab.settings.contact.form.ContactForm',
			'sections' => 'controllers/tab/settings/journal/sections.tpl',
		));
	}

	//
	// Overridden methods from Handler
	//
	/**
	 * @copydoc ManagerSettingsTabHandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);

		// Load grid-specific translations
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_USER);
	}
}



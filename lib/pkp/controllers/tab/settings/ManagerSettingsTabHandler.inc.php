<?php

/**
 * @file controllers/tab/settings/ManagerSettingsTabHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManagerSettingsTabHandler
 * @ingroup controllers_tab_settings
 *
 * @brief Handle AJAX operations for tabs on manangement settings pages.
 * Implements the wizard mode, to let tabs show basic or advanced settings.
 */

// Import the base Handler.
import('lib.pkp.classes.controllers.tab.settings.SettingsTabHandler');

class ManagerSettingsTabHandler extends SettingsTabHandler {

	/** @var boolean */
	var $_wizardMode;

	/**
	 * Constructor
	 */
	function __construct() {
		$role = array(ROLE_ID_MANAGER);
		parent::__construct($role);
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}


	//
	// Getters and Setters
	//
	/**
	 * Get if the current tab is in wizard mode.
	 * @return boolean
	 */
	function getWizardMode() {
		return $this->_wizardMode;
	}

	/**
	 * Set if the current tab is in wizard mode.
	 * @param $wizardMode boolean
	 */
	function setWizardMode($wizardMode) {
		$this->_wizardMode = (boolean)$wizardMode;
	}


	//
	// Extended methods from SettingsTabHandler
	//
	/**
	 * @copydoc SettingsTabHandler::initialize()
	 */
	function initialize($request, $args = null) {
		$this->setWizardMode($request->getUserVar('wizardMode'));

		parent::initialize($request, $args);

		// Load handler specific translations.
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_PKP_GRID);
	}
}

?>

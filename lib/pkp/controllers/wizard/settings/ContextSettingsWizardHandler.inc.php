<?php
/**
 * @defgroup controllers_wizard_settings Settings Wizard Controller
 */

/**
 * @file controllers/wizard/settings/ContextSettingsWizardHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContextSettingsWizardHandler
 * @ingroup controllers_wizard_settings
 *
 * @brief A controller that handles basic server-side
 *  operations of the context settings wizard.
 */

// Import base class.
import('classes.handler.Handler');

class ContextSettingsWizardHandler extends Handler {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER),
			array('startWizard')
		);
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}


	//
	// Public handler methods
	//
	/**
	 * Displays the context settings wizard.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function startWizard($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_MANAGER,
			LOCALE_COMPONENT_PKP_MANAGER
		);

		$this->setupTemplate($request);
		return $templateMgr->fetchJson('controllers/wizard/settings/settingsWizard.tpl');
	}
}

?>

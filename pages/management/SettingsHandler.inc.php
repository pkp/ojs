<?php

/**
 * @file pages/management/SettingsHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SettingsHandler
 * @ingroup pages_management
 *
 * @brief Handle requests for settings pages.
 */

// Import the base ManagementHandler.
import('lib.pkp.pages.management.ManagementHandler');

class SettingsHandler extends ManagementHandler {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_SITE_ADMIN),
			array(
				'access',
			)
		);
		$this->addRoleAssignment(
			ROLE_ID_MANAGER,
			array(
				'settings',
			)
		);
	}

	/**
	 * Add the submission settings tab to the workflow settings page
	 *
	 * @param $args array
	 * @param $request Request
	 */
	function workflow($args, $request) {

		$templateMgr = TemplateManager::getManager($request);
		$context = $request->getContext();
		$router = $request->getRouter();
		$dispatcher = $request->getDispatcher();

		$apiUrl = $dispatcher->url($request, ROUTE_API, $context->getPath(), 'contexts/' . $context->getId());

		$supportedFormLocales = $context->getSupportedFormLocales();
		$localeNames = AppLocale::getAllLocales();
		$locales = array_map(function($localeKey) use ($localeNames) {
			return ['key' => $localeKey, 'label' => $localeNames[$localeKey]];
		}, $supportedFormLocales);

		$submissionSettingsForm = new \APP\components\forms\context\SubmissionSettingsForm($apiUrl, $locales, $context);

		// Add form to the existing settings data
		$settingsData = $templateMgr->getTemplateVars('settingsData');
		$settingsData['forms'][$submissionSettingsForm->id] = $submissionSettingsForm->getConfig();
		$templateMgr->assign('settingsData', $settingsData);		

		HookRegistry::register('Template::Settings::workflow::submission', function($hookName, $args) {
			$templateMgr = $args[1];
			$output = &$args[2];
			$output .= $templateMgr->fetch('management/additionalWorkflowTabs.tpl');
			return false;
		});

		parent::workflow($args, $request);

	}

	/**
	 * Add the submission settings tab to the workflow settings page
	 *
	 * @param $args array
	 * @param $request Request
	 */
	function distribution($args, $request) {
		parent::distribution($args, $request);
		TemplateManager::getManager($request)->display('management/distribution.tpl');
	}
}

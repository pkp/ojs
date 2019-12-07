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
	 * Add the PPS workflow settings page
	 *
	 * @param $args array
	 * @param $request Request
	 */
	function workflow($args, $request) {
		parent::workflow($args, $request);
		$templateMgr = TemplateManager::getManager($request);
		$context = $request->getContext();
		$dispatcher = $request->getDispatcher();

		$contextApiUrl = $dispatcher->url($request, ROUTE_API, $context->getPath(), 'contexts/' . $context->getId());
		$apiUrl = $dispatcher->url($request, ROUTE_API, $context->getPath(), 'contexts/' . $context->getId());

		$supportedFormLocales = $context->getSupportedFormLocales();
		$localeNames = AppLocale::getAllLocales();
		$locales = array_map(function($localeKey) use ($localeNames) {
			return ['key' => $localeKey, 'label' => $localeNames[$localeKey]];
		}, $supportedFormLocales);

		$screeningForm = new \APP\components\forms\context\ScreeningForm($apiUrl, $locales, $context);

		// Add forms to the existing settings data
		$settingsData = $templateMgr->getTemplateVars('settingsData');
		$settingsData['components'][$screeningForm->id] = $screeningForm->getConfig();

		$templateMgr->assign('settingsData', $settingsData);
		TemplateManager::getManager($request)->display('management/workflow.tpl');
	}

	/**
	 * Add PPS distribution settings
	 *
	 * @param $args array
	 * @param $request Request
	 */
	function distribution($args, $request) {
		parent::distribution($args, $request);
		$templateMgr = TemplateManager::getManager($request);
		$context = $request->getContext();
		$dispatcher = $request->getDispatcher();

		$apiUrl = $dispatcher->url($request, ROUTE_API, $context->getPath(), 'contexts/' . $context->getId());

		$supportedFormLocales = $context->getSupportedFormLocales();
		$localeNames = AppLocale::getAllLocales();
		$locales = array_map(function($localeKey) use ($localeNames) {
			return ['key' => $localeKey, 'label' => $localeNames[$localeKey]];
		}, $supportedFormLocales);

		$accessForm = new \APP\components\forms\context\AccessForm($apiUrl, $locales, $context);

		// Add forms to the existing settings data
		$settingsData = $templateMgr->getTemplateVars('settingsData');
		$settingsData['components'][$accessForm->id] = $accessForm->getConfig();
		$templateMgr->assign('settingsData', $settingsData);

		// Hook into the settings templates to add the appropriate tabs
		HookRegistry::register('Template::Settings::distribution', function($hookName, $args) {
			$templateMgr = $args[1];
			$output = &$args[2];
			$output .= $templateMgr->fetch('management/additionalDistributionTabs.tpl');
			return false;
		});
		$templateMgr->display('management/distribution.tpl');
	}
}


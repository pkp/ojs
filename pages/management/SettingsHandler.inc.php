<?php

/**
 * @file pages/management/SettingsHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
	 * Add the workflow settings page
	 *
	 * @param $args array
	 * @param $request Request
	 */
	function workflow($args, $request) {
		parent::workflow($args, $request);
		TemplateManager::getManager($request)->display('management/workflow.tpl');
	}

	/**
	 * Add the archive and payments tabs to the distribution settings page
	 *
	 * @param $args array
	 * @param $request Request
	 */
	function distribution($args, $request) {
		parent::distribution($args, $request);

		$templateMgr = TemplateManager::getManager($request);
		$context = $request->getContext();
		$router = $request->getRouter();
		$dispatcher = $request->getDispatcher();

		$apiUrl = $dispatcher->url($request, ROUTE_API, $context->getPath(), 'contexts/' . $context->getId());
		$lockssUrl = $router->url($request, $context->getPath(), 'gateway', 'lockss');
		$clockssUrl = $router->url($request, $context->getPath(), 'gateway', 'clockss');

		$supportedFormLocales = $context->getSupportedFormLocales();
		$localeNames = AppLocale::getAllLocales();
		$locales = array_map(function($localeKey) use ($localeNames) {
			return ['key' => $localeKey, 'label' => $localeNames[$localeKey]];
		}, $supportedFormLocales);

		$accessForm = new \APP\components\forms\context\AccessForm($apiUrl, $locales, $context);
		$archivingLockssForm = new \APP\components\forms\context\ArchivingLockssForm($apiUrl, $locales, $context, $lockssUrl, $clockssUrl);

		// Create a dummy "form" for the PKP Preservation Network settings. This
		// form loads a single field which enables/disables the plugin, and does
		// not need to be submitted. It's a dirty hack, but we can change this once
		// an API is in place for plugins and plugin settings.
		$plnPlugin = PluginRegistry::getPlugin('generic', 'plnplugin');
		$archivePnForm = new \PKP\components\forms\FormComponent('archivePn', 'PUT', 'dummy', $supportedFormLocales);
		$archivePnForm->addPage([
				'id' => 'default',
				'submitButton' => null,
			])
			->addGroup([
				'id' => 'default',
				'pageId' => 'default',
			]);

		if ($plnPlugin) {
			$plnPlugin = PluginRegistry::getPlugin('generic', 'plnplugin');
			$pnEnablePluginUrl = $dispatcher->url($request, ROUTE_COMPONENT, null, 'grid.settings.plugins.SettingsPluginGridHandler', 'enable', null, array('plugin' => 'plnplugin', 'category' => 'generic'));
			$pnDisablePluginUrl = $dispatcher->url($request, ROUTE_COMPONENT, null, 'grid.settings.plugins.SettingsPluginGridHandler', 'disable', null, array('plugin' => 'plnplugin', 'category' => 'generic'));
			$pnSettingsUrl = $dispatcher->url($request, ROUTE_COMPONENT, null, 'grid.settings.plugins.SettingsPluginGridHandler', 'manage', null, array('verb' => 'settings', 'plugin' => 'plnplugin', 'category' => 'generic'));

			$archivePnForm->addField(new \APP\components\forms\FieldArchivingPn('pn', [
				'label' => __('manager.setup.plnPluginArchiving'),
				'description' => __('manager.setup.plnDescription'),
				'terms' => __('manager.setup.plnSettingsDescription'),
				'options' => [
					[
						'value' => true,
						'label' => __('manager.setup.plnPluginEnable'),
					],
				],
				'value' => (bool) $plnPlugin,
				'enablePluginUrl' => $pnEnablePluginUrl,
				'disablePluginUrl' => $pnDisablePluginUrl,
				'settingsUrl' => $pnSettingsUrl,
				'csrfToken' => $request->getSession()->getCSRFToken(),
				'groupId' => 'default',
				'enablePluginSuccess' => __('common.pluginEnabled', ['pluginName' => __('manager.setup.plnPluginArchiving')]),
				'disablePluginSuccess' => __('common.pluginDisabled', ['pluginName' => __('manager.setup.plnPluginArchiving')]),
			]));
		} else {
			$archivePnForm->addField(new \PKP\components\forms\FieldHTML('pn', [
				'label' => __('manager.setup.plnPluginArchiving'),
				'description' => __('manager.setup.plnPluginNotInstalled'),
				'groupId' => 'default',
			]));
		}

		// Add forms to the existing settings data
		$components = $templateMgr->getState('components');
		$components[$accessForm->id] = $accessForm->getConfig();
		$components[$archivingLockssForm->id] = $archivingLockssForm->getConfig();
		$components[$archivePnForm->id] = $archivePnForm->getConfig();
		$templateMgr->setState(['components' => $components]);

		// Add a payments link to be added/removed when payments form submitted
		$templateMgr->setState([
			'paymentsNavLink' => [
				'name' => __('common.payments'),
				'url' => $router->url($request, null, 'payments'),
				'isCurrent' => false,
			],
		]);

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

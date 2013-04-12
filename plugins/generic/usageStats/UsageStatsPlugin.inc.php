<?php

/**
 * @file plugins/generic/usageStats/UsageStatsPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsPlugin
 * @ingroup plugins_generic_usageStats
 *
 * @brief Provide usage statistics to data objects.
 */


import('lib.pkp.classes.plugins.GenericPlugin');

class UsageStatsPlugin extends GenericPlugin {

	//
	// Implement methods from PKPPlugin.
	//
	/**
	* @see LazyLoadPlugin::register()
	*/
	function register($category, $path) {
		$success = parent::register($category, $path);

		if ($this->getEnabled() && $success) {
			// Register callbacks.
			$app =& PKPApplication::getApplication();
			$version = $app->getCurrentVersion();
			if ($version->getMajor() < 3) {
				HookRegistry::register('LoadHandler', array(&$this, 'callbackLoadHandler'));
			}
		}

		return $success;
	}

	/**
	* @see PKPPlugin::getDisplayName()
	*/
	function getDisplayName() {
		return __('plugins.generic.usageStats.displayName');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.usageStats.description');
	}

	/**
	* @see PKPPlugin::isSitePlugin()
	*/
	function isSitePlugin() {
		return true;
	}

	/**
	* @see PKPPlugin::getInstallSitePluginSettingsFile()
	*/
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	* @see PKPPlugin::getTemplatePath()
	*/
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}

	/**
	* @see PKPPlugin::getManagementVerbLinkAction()
	*/
	function getManagementVerbLinkAction($request, $verb) {
		$router = $request->getRouter();

		list($verbName, $verbLocalized) = $verb;

		if ($verbName === 'settings') {
			import('lib.pkp.classes.linkAction.request.AjaxModal');
			$actionRequest = new AjaxModal(
			$router->url($request, null, null, 'plugin', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')),
			$this->getDisplayName()
			);
			return new LinkAction($verbName, $actionRequest, $verbLocalized, null);
		}

		return null;
	}

	/**
	* @see PKPPlugin::manage()
	*/
	function manage($verb, $args, &$message, &$messageParams, &$pluginModalContent = null) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;
		$request =& $this->getRequest();
		$this->import('UsageStatsSettingsForm');

		switch($verb) {
			case 'settings':
				$settingsForm = new UsageStatsSettingsForm($this);
				$settingsForm->initData();
				$pluginModalContent = $settingsForm->fetch($request);
				return true;
			case 'save':
				$settingsForm = new UsageStatsSettingsForm($this);
				$settingsForm->readInputData();
				if ($settingsForm->validate()) {
					$settingsForm->execute();
					$message = NOTIFICATION_TYPE_SUCCESS;
					$messageParams = array('contents' => __('plugins.generic.usageStats.settings.saved'));
					return false;
				} else {
					$pluginModalContent = $settingsForm->fetch($request);
				}
				return true;
			default:
				break;
		}
	}


	//
	// Implement template methods from GenericPlugin.
	//
	/**
	* @see GenericPlugin::getManagementVerbs()
	*/
	function getManagementVerbs() {
	$verbs = parent::getManagementVerbs();
	if ($this->getEnabled()) {
	$verbs[] = array('settings', __('grid.settings'));
			}
			return $verbs;
	}


	//
	// Hook implementations.
	//
	/**
	 * @see PKPPageRouter::route()
	 * @todo Remove this callback for OJS 3.0. The issue current
	 * operation should redirect to the view operation in core.
	 */
	function callbackLoadHandler($hookName, $args) {
		// Check the page.
		$page = $args[0];
		if ($page !== 'issue') return;

		// Check the operation.
		$op = $args[1];
		if ($op !== 'current') return;

		// Check current issue.
		$request =& Application::getRequest();
		$journal = $request->getJournal();
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getCurrent($journal->getId(), true);
		if (!$issue) {
			// Let the default current operation work.
			return;
		}

		// Replace the default Issue handler by ours.
		define('HANDLER_CLASS', 'UsageStatsHandler');
		$handlerFile =& $args[2];
		$handlerFile = $this->getPluginPath() . '/' . 'UsageStatsHandler.inc.php';
	}

}

?>

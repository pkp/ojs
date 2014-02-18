<?php

/**
 * @file plugins/generic/usageStats/UsageStatsPlugin.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsPlugin
 * @ingroup plugins_generic_usageStats
 *
 * @brief Provide usage statistics to data objects.
 */


import('lib.pkp.classes.plugins.GenericPlugin');

class UsageStatsPlugin extends GenericPlugin {

	/** @var array */
	var $_currentUsageEvent;


	//
	// Implement methods from Plugin.
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

			HookRegistry::register('PluginRegistry::loadCategory', array($this, 'callbackLoadCategory'));

			if ($version->getMajor() < 3) {
				HookRegistry::register('LoadHandler', array(&$this, 'callbackLoadHandler'));
			}

			// If the plugin will provide the access logs,
			// register to the usage event hook provider.
			if ($this->getSetting(0, 'createLogFiles')) {
				HookRegistry::register('UsageEventPlugin::getUsageEvent', array(&$this, 'logUsageEvent'));
			}
		}

		return $success;
	}

	/**
	* @see Plugin::getDisplayName()
	*/
	function getDisplayName() {
		return __('plugins.generic.usageStats.displayName');
	}

	/**
	 * @see Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.usageStats.description');
	}

	/**
	* @see Plugin::isSitePlugin()
	*/
	function isSitePlugin() {
		return true;
	}

	/**
	* @see Plugin::getInstallSitePluginSettingsFile()
	*/
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	* @see Plugin::getTemplatePath()
	*/
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}

	/**
	* @see Plugin::getManagementVerbLinkAction()
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
	* @see Plugin::manage()
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
	* @see PluginRegistry::loadCategory()
	*/
	function callbackLoadCategory($hookName, $args) {
		// Instantiate report plugin.
		$plugin = null;
		$category = $args[0];
		if ($category == 'reports') {
			$this->import('UsageStatsReportPlugin');
			$plugin = new UsageStatsReportPlugin();
		}

		// Register report plugin (by reference).
		if ($plugin) {
			$seq = $plugin->getSeq();
			$plugins =& $args[1];
			if (!isset($plugins[$seq])) $plugins[$seq] = array();
			$plugins[$seq][$this->getPluginPath()] = $plugin;
		}

		return false;
	}

	/**
	 * Log the usage event into a file.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function logUsageEvent($hookName, $args) {
		$hookName = $args[0];
		$usageEvent = $args[1];

		if ($hookName == 'FileManager::downloadFileFinished' && !$usageEvent && $this->_currentUsageEvent) {
			// File download is finished, try to log the current usage event.
			$downloadSuccess = $args[2];
			if ($downloadSuccess && !connection_aborted()) {
				$this->_currentUsageEvent['downloadSuccess'] = true;
				$usageEvent = $this->_currentUsageEvent;
			}
		}

		if ($usageEvent && !$usageEvent['downloadSuccess']) {
			// Don't log until we get the download finished hook call.
			$this->_currentUsageEvent = $usageEvent;
			return false;
		}

		if ($usageEvent) {
			$this->_writeUsageEventInLogFile($usageEvent);
		}

		return false;
	}

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
			return false;
		}

		// Replace the default Issue handler by ours.
		define('HANDLER_CLASS', 'UsageStatsHandler');
		$handlerFile =& $args[2];
		$handlerFile = $this->getPluginPath() . '/' . 'UsageStatsHandler.inc.php';
	}


	//
	// Private helper methods.
	//
	/**
	 * @param $usageEvent array
	 */
	private function _writeUsageEventInLogFile($usageEvent) {
		$desiredParams = array($usageEvent['ip']);

		if (isset($usageEvent['classification'])) {
			$desiredParams[] = $usageEvent['classification'];
		} else {
			$desiredParams[] = '-';
		}

		if (isset($usageEvent['user'])) {
			$desiredParams[] = $usageEvent['user']->getId();
		} else {
			$desiredParams[] = '-';
		}

		$desiredParams = array_merge($desiredParams,
		array('"' . $usageEvent['time'] . '"', $usageEvent['canonicalUrl'],
						'200', // The usage event plugin always log requests that returned this code.
						'"' . $usageEvent['userAgent'] . '"'));

		$usageLogEntry = implode(' ', $desiredParams) . PHP_EOL;

		import('lib.pkp.classes.file.PrivateFileManager');
		$fileMgr = new PrivateFileManager();

		// Get the current day filename.
		$filename = 'usage_events_' . date("Ymd") . '.log';

		// Check the plugin file directory.
		$usageEventFilesPath = realpath($fileMgr->getBasePath()) .
		DIRECTORY_SEPARATOR . 'usageStats' .
		DIRECTORY_SEPARATOR . 'usageEventLogs';
		if (!$fileMgr->fileExists($usageEventFilesPath, 'dir')) {
			$success = $fileMgr->mkdirtree($usageEventFilesPath);
			if (!$success) {
				// Files directory wrong configuration?
				assert(false);
				return false;
			}
		}

		$filePath = $usageEventFilesPath . DIRECTORY_SEPARATOR . $filename;
		$fp = fopen($filePath, 'ab');
		if (flock($fp, LOCK_EX)) {
			fwrite($fp, $usageLogEntry);
			flock($fp, LOCK_UN);
		} else {
			// Couldn't lock the file.
			assert(false);
		}
		fclose($fp);
	}
}

?>

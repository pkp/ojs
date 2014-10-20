<?php

/**
 * @file plugins/generic/acron/AcronPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AcronPlugin
 * @ingroup plugins_generic_acron
 *
 * @brief Removes dependency on 'cron' for scheduled tasks, including
 * possible tasks defined by plugins. See the AcronPlugin::parseCrontab
 * hook implementation.
 */

import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.classes.scheduledTask.ScheduledTaskHelper');

// TODO: Error handling. If a scheduled task encounters an error...?

class AcronPlugin extends GenericPlugin {

	/** @var $_workingDir string */
	var $_workingDir;

	/** @var $_tasksToRun array */
	var $_tasksToRun;

	/**
	 * @see LazyLoadPlugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		HookRegistry::register('Installer::postInstall', array(&$this, 'callbackPostInstall'));

		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return $success;
		if ($success) {
			$this->addLocaleData();
			HookRegistry::register('LoadHandler',array(&$this, 'callbackLoadHandler'));
			// Need to reload cron tab on possible enable or disable generic plugin actions.
			HookRegistry::register('PluginHandler::plugin',array(&$this, 'callbackManage'));
		}
		return $success;
	}

	/**
	* @see PKPPlugin::isSitePlugin()
	*/
	function isSitePlugin() {
		// This is a site-wide plugin.
		return true;
	}

	/**
	 * @see LazyLoadPlugin::getName()
	 */
	function getName() {
		return 'acronPlugin';
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.acron.name');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.acron.description');
	}

	/**
	* @see PKPPlugin::getInstallSitePluginSettingsFile()
	*/
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * @see GenericPlugin::getManagementVerbs()
	 */
	function getManagementVerbs() {
		$isEnabled = $this->getSetting(0, 'enabled');

		$verbs = array();
		$verbs[] = array(
		($isEnabled?'disable':'enable'),
		__($isEnabled?'manager.plugins.disable':'manager.plugins.enable')
		);
		$verbs[] = array(
				'reload', __('plugins.generic.acron.reload')
		);
		return $verbs;
	}

	/**
	 * @see GenericPlugin::manage()
	 */
	function manage($verb, $args, &$message) {
		switch ($verb) {
			case 'enable':
				$this->updateSetting(0, 'enabled', true);
				$message = __('plugins.generic.acron.enabled');
				break;
			case 'disable':
				$this->updateSetting(0, 'enabled', false);
				$message = __('plugins.generic.acron.disabled');
				break;
			case 'reload':
				$this->_parseCrontab();
		}
		return false;
	}

	/**
	 * Post install hook to flag cron tab reload on every install/upgrade.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 * @see Installer::postInstall() for the hook call.
	 */
	function callbackPostInstall($hookName, $args) {
		$this->_parseCrontab();
		return false;
	}

	/**
	 * Load handler hook to check for tasks to run.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 * @see PKPPageRouter::loadHandler() for the hook call.
	 */
	function callbackLoadHandler($hookName, $args) {
		$tasksToRun = $this->_getTasksToRun();

		if (!empty($tasksToRun)) {
			// Save the current working directory, so we can fix
			// it inside the shutdown function.
			$this->_workingDir = getcwd();

			// Save the tasks to be executed.
			$this->_tasksToRun = $tasksToRun;

			// Need output buffering to send a finish message
			// to browser inside the shutdown function. Couldn't
			// do without the buffer.
			ob_start();

			// This callback will be used as soon as the main script
			// is finished. It will not stop running, even if the user cancels
			// the request or the time limit is reach.
			register_shutdown_function(array(&$this, 'shutdownFunction'));
		}

		return false;
	}

	/**
	 * Syncronize crontab with lazy load plugins management.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 * @see PluginHandler::plugin() for the hook call.
	 */
	function callbackManage($hookName, $args) {
		$verb = $args[0];
		$plugin =& $args[4]; /* @var $plugin LazyLoadPlugin */

		// Only interested in plugins that can be enabled/disabled.
		if (!is_a($plugin, 'LazyLoadPlugin')) return false;

		// Only interested in enable/disable actions.
		if ($verb !== 'enable' && $verb !== 'disable') return false;

		// Check if the plugin wants to add its own
		// scheduled task into the cron tab.
		$hooks = HookRegistry::getHooks();
		$hookName = 'AcronPlugin::parseCronTab';
		if (!isset($hooks[$hookName])) return false;

		foreach($hooks[$hookName] as $index => $callback) {
			if ($callback[0] == $plugin) {
				$this->_parseCrontab();
				break;
			}
		}

		return false;
	}

	/**
	 * Shutdown callback.
	 */
	function shutdownFunction() {
		// After PHP 4.1.0, the execution of this callback is part of the request,
		// so users will have no response until it finishes executing it. We avoid
		// that by sending headers to the browser that makes them believe the script
		// is over.
		header("Connection: close");

		// This header is needed so avoid using any kind of compression. If zlib is
		// enabled, for example, the buffer will not output until the end of the
		// script execution.
		header("Content-Encoding: none");

		header("Content-Length: " . ob_get_length());

		ob_end_flush();
		flush();

		// Fix the current working directory. See
		// http://www.php.net/manual/en/function.register-shutdown-function.php#92657
		chdir($this->_workingDir);

		$taskDao =& DAORegistry::getDao('ScheduledTaskDAO');

		foreach($this->_tasksToRun as $task) {
			// Strip off the package name(s) to get the base class name
			$className = $task['className'];

			$pos = strrpos($className, '.');
			if ($pos === false) {
				$baseClassName = $className;
			} else {
				$baseClassName = substr($className, $pos+1);
			}

			$taskArgs = array();
			if (isset($task['args'])) {
				$taskArgs = $task['args'];
			}


			// There's a race here. Several requests may come in closely spaced.
			// Each may decide it's time to run scheduled tasks, and more than one
			// can happily go ahead and do it before the "last run" time is updated.

			// By updating the last run time as soon as feasible, we can minimize
			// the race window. See bug #8737.
			$updateResult = $taskDao->updateLastRunTime($className, time());

			switch ($updateResult) {
				case false: // DB doesn't support the get affected rows used inside update method.
				case 1: // Introduced a new last run time.
					// Load and execute the task.
					import($className);
					$task = new $baseClassName($taskArgs);
					$task->execute();
					break;
				case 0: // Another simultaneously request came first.
				default:
					break;
			}
		}
	}


	//
	// Private helper methods.
	//
	/**
	 * Parse all scheduled tasks files and
	 * save the result object in database.
	 */
	function _parseCrontab() {
		$xmlParser = new XMLParser();

		$taskFilesPath = array();

		// Load all plugins so any plugin can register a crontab.
		PluginRegistry::loadAllPlugins();

		// Let plugins register their scheduled tasks too.
		HookRegistry::call('AcronPlugin::parseCronTab', array(&$taskFilesPath));

		// Add the default tasks file.
		$taskFilesPath[] = Config::getVar('general', 'registry_dir') . '/scheduledTasks.xml'; // TODO: make this a plugin setting, rather than assuming.

		$tasks = array();
		foreach ($taskFilesPath as $filePath) {
			$tree = $xmlParser->parse($filePath);

			if (!$tree) {
				$xmlParser->destroy();

				// TODO: graceful error handling
				fatalError('Error parsing scheduled tasks XML file: ' . $filePath);
			}

			foreach ($tree->getChildren() as $task) {
				$frequency = $task->getChildByName('frequency');

				$args = ScheduledTaskHelper::getTaskArgs($task);

				// Tasks without a frequency defined, or defined to zero, will run on every request.
				// To avoid that happening (may cause performance problems) we
				// setup a default period of time.
				$frequencyAttributes = $frequency->getAttributes();
				$setDefaultFrequency = true;
				$minHoursRunPeriod = 24;
				if (is_array($frequencyAttributes)) {
					foreach($frequencyAttributes as $key => $value) {
						if ($value != 0) {
							$setDefaultFrequency = false;
							break;
						}
					}
				}

				$tasks[] = array(
					'className' => $task->getAttribute('class'),
					'frequency' => $setDefaultFrequency ? array('hour' => $minHoursRunPeriod) : $frequencyAttributes,
					'args' => $args
				);
			}

			$xmlParser->destroy();
		}

		// Store the object.
		$this->updateSetting(0, 'crontab', $tasks, 'object');
	}

	/**
	 * Get all scheduled tasks that needs to be executed.
	 * @return array
	 */
	function _getTasksToRun() {
		$tasksToRun = array();
		$isEnabled = $this->getSetting(0, 'enabled');

		if($isEnabled) {
			$taskDao =& DAORegistry::getDao('ScheduledTaskDAO');

			// Grab the scheduled scheduled tree
			$scheduledTasks = $this->getSetting(0, 'crontab');
			if(is_null($scheduledTasks)) {
				$this->_parseCrontab();
				$scheduledTasks = $this->getSetting(0, 'crontab');
			}

			foreach($scheduledTasks as $task) {
				// We don't allow tasks without frequency, see _parseCronTab().
				$frequency = new XMLNode();
				$frequency->setAttribute(key($task['frequency']), current($task['frequency']));
				$canExecute = ScheduledTaskHelper::checkFrequency($task['className'], $frequency);

				if ($canExecute) {
					$tasksToRun[] = $task;
				}
			}
		}

		return $tasksToRun;
	}
}
?>

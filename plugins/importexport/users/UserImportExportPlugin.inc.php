<?php

/**
 * @file plugins/importexport/users/UserImportExportPlugin.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserImportExportPlugin
 * @ingroup plugins_importexport_user
 *
 * @brief User XML import/export plugin
 */

import('lib.pkp.plugins.importexport.users.PKPUserImportExportPlugin');

class UserImportExportPlugin extends PKPUserImportExportPlugin {

	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		return parent::register($category, $path, $mainContextId);
	}

	/**
	 * @copydoc PKPImportExportPlugin::usage
	 */
	function usage($scriptName) {
		echo __('plugins.importexport.users.cliUsage', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n\n";
		echo __('plugins.importexport.users.cliUsage.examples', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n\n";
	}

	/**
	 * @see PKPImportExportPlugin::executeCLI()
	 */
	function executeCLI($scriptName, &$args) {
		$command = array_shift($args);
		$xmlFile = array_shift($args);
		$journalPath = array_shift($args);

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER);

		$journalDao = DAORegistry::getDAO('JournalDAO');
		$userDao = DAORegistry::getDAO('UserDAO');

		$journal = $journalDao->getByPath($journalPath);

		if (!$journal) {
			if ($journalPath != '') {
				echo __('plugins.importexport.common.cliError') . "\n";
				echo __('plugins.importexport.common.error.unknownJournal', array('journalPath' => $journalPath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		}

		if ($xmlFile && $this->isRelativePath($xmlFile)) {
			$xmlFile = PWD . '/' . $xmlFile;
		}
		$outputDir = dirname($xmlFile);
		if (!is_writable($outputDir) || (file_exists($xmlFile) && !is_writable($xmlFile))) {
			echo __('plugins.importexport.common.cliError') . "\n";
			echo __('plugins.importexport.common.export.error.outputFileNotWritable', array('param' => $xmlFile)) . "\n\n";
			$this->usage($scriptName);
			return;
		}

		switch ($command) {
			case 'import':
				$this->importUsers(file_get_contents($xmlFile), $journal, null);
				return;
			case 'export':
				if ($xmlFile != '') {
					if (empty($args)) {
						file_put_contents($xmlFile, $this->exportAllUsers($journal, null));
						return;
					} else {
						file_put_contents($xmlFile, $this->exportUsers($args, $journal, null));
						return;
					}
				}
				break;
		}
		$this->usage($scriptName);
	}
}



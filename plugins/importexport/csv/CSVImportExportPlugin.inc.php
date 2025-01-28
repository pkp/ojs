<?php

/**
 * @file plugins/importexport/csv/CSVImportExportPlugin.inc.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CSVImportExportPlugin
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief CSV import/export plugin
 */

namespace PKP\Plugins\ImportExport\CSV;

import('lib.pkp.classes.plugins.ImportExportPlugin');

use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedDaos;
use PKP\Plugins\ImportExport\CSV\Classes\Commands\IssueCommand;
use PKP\Plugins\ImportExport\CSV\Classes\Commands\UserCommand;

class CSVImportExportPlugin extends \ImportExportPlugin
{

    /**
	 * Which command is the tool using from CLI. Currently supports "issues" or "users"
	 *
	 * @var string
	 */
    private $_command;

    /**
	 * Username passed as parameter from CLI
	 *
	 * @var string
	 */
    private $_username;

    /**
	 * User registered on system to perform the CLI command
	 *
	 * @var \User
	 */
    private $_user;

    /**
	 * The folder containing all CSV files that the command must go through
	 *
	 * @var string
	 */
    private $_sourceDir;

    /**
	 * Whether to send welcome email to the user
	 *
	 * @var bool
	 */
    private $_sendWelcomeEmail = false;

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
		$isInstalled = !!\Config::getVar('general', 'installed');
		$isUpgrading = defined('RUNNING_UPGRADE');

        if (!$isInstalled || $isUpgrading) {
            return $success;
        }

        if ($success && $this->getEnabled()) {
            $this->addLocaleData();
        }

        return $success;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.importexport.csv.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.importexport.csv.description');
    }

    /**
     * @copydoc Plugin::getName()
     */
    public function getName()
    {
        return 'CSVImportExportPlugin';
    }

    /**
     * @copydoc PKPImportExportPlugin::usage
     */
    public function usage($scriptName)
    {
        echo __('plugins.importexport.csv.cliUsage', [
            'scriptName' => $scriptName,
            'pluginName' => $this->getName()
        ]) . "\n\n";
        echo __('plugins.importexport.csv.cliUsage.examples', [
            'scriptName' => $scriptName,
            'pluginName' => $this->getName()
        ]) . "\n\n";
    }

    /**
     * @see PKPImportExportPlugin::executeCLI()
     */
    public function executeCLI($scriptName, &$args)
    {
        $startTime = microtime(true);
        $this->_command = array_shift($args);
		$this->_username = array_shift($args);
        $this->_sourceDir = array_shift($args);
        $this->_sendWelcomeEmail = array_shift($args) ?? false;

        if (! in_array($this->_command, ['issues', 'users']) || !$this->_sourceDir || !$this->_username) {
			$this->usage($scriptName);
			exit(1);
		}

        if (! is_dir($this->_sourceDir)) {
            echo __('plugins.importexport.csv.unknownSourceDir', ['sourceDir' => $this->_sourceDir]) . "\n";
            exit(1);
        }

		import('plugins.importexport.csv.classes.cachedAttributes.CachedDaos');

		$this->_validateUser();

		import('plugins.importexport.csv.classes.handlers.CSVFileHandler');
		import('plugins.importexport.csv.classes.validations.InvalidRowValidations');
		import('plugins.importexport.csv.classes.cachedAttributes.CachedEntities');

        switch ($this->_command) {
            case 'issues':
				import('plugins.importexport.csv.classes.commands.IssueCommand');
				(new IssueCommand($this->_sourceDir, $this->_user))->run();
                break;
            case 'users':
				import('plugins.importexport.csv.classes.commands.UserCommand');
                (new UserCommand($this->_sourceDir, $this->_user, $this->_sendWelcomeEmail))->run();
                break;
            default:
                throw new \InvalidArgumentException("Comando inválido: {$this->_command}");
        }

		$endTime = microtime(true);
		$executionTime = $endTime - $startTime;
		echo "Executed in: " . number_format($executionTime, 2) . " seconds\n";
    }

    /**
	 * Retrieve and validate the User by username
	 *
	 * @return void
	 */
	private function _validateUser()
    {
		$this->_user = $this->_getUser();
		if (!$this->_user) {
			echo __('plugins.importexport.csv.unknownUser', ['username' => $this->_username]) . "\n";
			exit(1);
		}
	}

    /**
	 * Retrives an user by username or null if not found
	 *
	 * @return \User|null
	 */
	private function _getUser()
    {
		/** @var \UserDAO */
		$userDao = CachedDaos::getUserDao();
		return $userDao->getByUsername($this->_username);
	}
}

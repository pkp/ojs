<?php

/**
 * @file plugins/importexport/csv/classes/commands/UserCommand.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserCommand
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Handles the issue import when the user uses the issue command
 */

namespace PKP\Plugins\ImportExport\CSV\Classes\Commands;

import('plugins.importexport.csv.classes.validations.RequiredUserHeaders');

use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedEntities;
use PKP\Plugins\ImportExport\CSV\Classes\Handlers\CSVFileHandler;
use PKP\Plugins\ImportExport\CSV\Classes\Handlers\WelcomeEmailHandler;
use PKP\Plugins\ImportExport\CSV\Classes\Processors\UserGroupsProcessor;
use PKP\Plugins\ImportExport\CSV\Classes\Processors\UserInterestsProcessor;
use PKP\Plugins\ImportExport\CSV\Classes\Processors\UsersProcessor;
use PKP\Plugins\ImportExport\CSV\Classes\Validations\InvalidRowValidations;
use PKP\Plugins\ImportExport\CSV\Classes\Validations\RequiredUserHeaders;

class UserCommand
{
    /**
     * Expected row size for a CSV based on the command passed as argument
     *
     * @var int
     */
    private $_expectedRowSize;

    /**
     * The folder containing all CSV files that the command must go through
     *
     * @var string
     */
    private $_sourceDir;

    /** @var int */
    private $_processedRows;

    /** @var int */
    private $_failedRows;

    /** @var bool */
    private $_sendWelcomeEmail;

    /** @var \User */
    private $_senderEmailUser;

    /**
     * @param string $sourceDir The folder containing all CSV files that the command must go through
     * @param \User $user The user that is importing the CSV file
     * @param bool $sendWelcomeEmail Whether to send welcome email to the user
     */
    public function __construct($sourceDir, $user, $sendWelcomeEmail)
    {
        $this->_expectedRowSize = count(RequiredUserHeaders::$userHeaders);
        $this->_sourceDir = $sourceDir;
        $this->_senderEmailUser = $user;
        $this->_sendWelcomeEmail = $sendWelcomeEmail;
    }

    public function run(): void
    {
        foreach (new \DirectoryIterator($this->_sourceDir) as $fileInfo) {
            if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'csv') {
                continue;
            }

            $filePath = $fileInfo->getPathname();

            $file = CSVFileHandler::createReadableCSVFile($filePath);

            if (is_null($file)) {
                continue;
            }

            $basename = $fileInfo->getBasename();
            $invalidCsvFile = CSVFileHandler::createCSVFileInvalidRows($this->_sourceDir, "invalid_{$basename}", RequiredUserHeaders::$userHeaders);

            if (is_null($invalidCsvFile)) {
                continue;
            }

            $this->_processedRows = 0;
            $this->_failedRows = 0;

            foreach ($file as $index => $fields) {
                if (!$index || empty(array_filter($fields))) {
                    continue; // Skip headers or end of file
                }

                ++$this->_processedRows;

                $reason = InvalidRowValidations::validateRowContainAllFields($fields, $this->_expectedRowSize);

                if (!is_null($reason)) {
                    CSVFileHandler::processFailedRow($invalidCsvFile, $fields, $this->_expectedRowSize, $reason, $this->_failedRows);
                    continue;
                }

                $fieldsList = array_pad(array_map('trim', $fields), $this->_expectedRowSize, null);
                $data = (object) array_combine(RequiredUserHeaders::$userHeaders, $fieldsList);

                $reason = InvalidRowValidations::validateRowHasAllRequiredFields($data, [RequiredUserHeaders::class, 'validateRowHasAllRequiredFields']);

                if (!is_null($reason)) {
                    CSVFileHandler::processFailedRow($invalidCsvFile, $fields, $this->_expectedRowSize, $reason, $this->_failedRows);
                    continue;
                }

                $journal = CachedEntities::getCachedJournal($data->journalPath);

                $reason = InvalidRowValidations::validateJournalIsValid($journal, $data->journalPath);
                if (!is_null($reason)) {
                    CSVFileHandler::processFailedRow($invalidCsvFile, $fields, $this->_expectedRowSize, $reason, $this->_failedRows);
                    continue;
                }

                $existingUserByEmail = CachedEntities::getCachedUserByEmail($data->email);
                if (!is_null($existingUserByEmail)) {
                    CSVFileHandler::processFailedRow($invalidCsvFile, $fields, $this->_expectedRowSize, __('plugins.importexport.csv.userAlreadyExistsWithEmail', ['email' => $data->email]), $this->_failedRows);
                    continue;
                }

                if ($data->username) {
                    $existingUserByUsername = CachedEntities::getCachedUserByUsername($data->username);
                    if (!is_null($existingUserByUsername)) {
                        CSVFileHandler::processFailedRow($invalidCsvFile, $fields, $this->_expectedRowSize, __('plugins.importexport.csv.userAlreadyExistsWithUsername', ['username' => $data->username]), $this->_failedRows);
                        continue;
                    }
                }

				import('plugins.importexport.csv.classes.processors.UsersProcessor');
                $data->username = UsersProcessor::getValidUsername($data->firstname, $data->lastname);

                $roles = array_map('trim', explode(';', $data->roles));

                $reason = InvalidRowValidations::validateAllUserGroupsAreValid($roles, $journal->getId(), $journal->getPrimaryLocale());

                if (!is_null($reason)) {
                    CSVFileHandler::processFailedRow($invalidCsvFile, $fields, $this->_expectedRowSize, $reason, $this->_failedRows);
                    continue;
                }

                if (is_null($data->tempPassword)) {
                    $data->tempPassword = \Validation::generatePassword();
                }

                $user = UsersProcessor::process($data, $journal->getPrimaryLocale());
                $userId = $user->getId();

				import('plugins.importexport.csv.classes.processors.UserInterestsProcessor');
                $userInterests = array_map('trim', explode(';', $data->reviewInterests));
                UserInterestsProcessor::process($userInterests, $userId);

				import('plugins.importexport.csv.classes.processors.UserGroupsProcessor');
                UserGroupsProcessor::process($roles, $userId, $journal->getId(), $journal->getPrimaryLocale());

                if ($this->_sendWelcomeEmail) {
					import('plugins.importexport.csv.classes.handlers.WelcomeEmailHandler');
                    WelcomeEmailHandler::sendWelcomeEmail($journal, $user, $this->_senderEmailUser, $data->tempPassword);
                }
            }

            echo __('plugins.importexpot.csv.fileProcessFinished', [
                'filename' => $fileInfo->getFilename(),
                'processedRows' => $this->_processedRows,
                'failedRows' => $this->_failedRows,
            ]) . "\n";

            if (!$this->_failedRows) {
                unlink($this->_sourceDir . '/' . "invalid_{$basename}");
            }
        }
    }
}

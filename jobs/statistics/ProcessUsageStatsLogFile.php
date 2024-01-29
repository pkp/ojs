<?php

/**
 * @file jobs/statistics/ProcessUsageStatsLogFile.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ProcessUsageStatsLogFile
 *
 * @ingroup jobs
 *
 * @brief Compile context metrics.
 */

namespace APP\jobs\statistics;

use APP\core\Application;
use APP\statistics\StatisticsHelper;
use APP\statistics\TemporaryItemInvestigationsDAO;
use APP\statistics\TemporaryItemRequestsDAO;
use APP\statistics\TemporaryTotalsDAO;
use DateTime;
use Exception;
use PKP\core\Core;
use PKP\db\DAORegistry;
use PKP\job\exceptions\JobException;
use PKP\jobs\BaseJob;
use PKP\statistics\TemporaryInstitutionsDAO;
use PKP\task\FileLoader;

class ProcessUsageStatsLogFile extends BaseJob
{
    /**
     * The number of times the job may be attempted.
     */
    public $tries = 1;

    /**
     * The load ID = usage stats log file name
     */
    protected string $loadId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $loadId)
    {
        parent::__construct();
        $this->loadId = $loadId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $filename = $this->loadId;
        $dispatchFilePath = StatisticsHelper::getUsageStatsDirPath() . '/' . FileLoader::FILE_LOADER_PATH_DISPATCH . '/' . $filename;
        if (!file_exists($dispatchFilePath)) {
            throw new JobException(__(
                'admin.job.processLogFile.fileNotFound',
                ['file' => $dispatchFilePath]
            ));
        }
        $this->process($dispatchFilePath);
    }


    protected function process(string $dispatchFilePath): void
    {
        $fhandle = fopen($dispatchFilePath, 'r');
        if (!$fhandle) {
            // reject file -- move the file from dispatch to reject folder
            $filename = $this->loadId;
            $rejectFilePath = StatisticsHelper::getUsageStatsDirPath() . '/' . FileLoader::FILE_LOADER_PATH_REJECT . '/' . $filename;
            if (!rename($dispatchFilePath, $rejectFilePath)) {
                $message = __('admin.job.compileMetrics.returnToStaging.error', ['file' => $filename,
                    'dispatchFilePath' => $dispatchFilePath, 'rejectFilePath' => $rejectFilePath]);
                error_log($message);
            }
            throw new JobException(__('admin.job.processLogFile.openFileFailed', ['file' => $dispatchFilePath]));
        }

        // Make sure we don't have any temporary records associated
        // with the current load ID in database.
        $this->deleteByLoadId();

        $lineNumber = 0;
        while (!feof($fhandle)) {
            $lineNumber++;
            $line = trim(fgets($fhandle));
            if (empty($line) || substr($line, 0, 1) === '#') {
                continue;
            } // Spacing or comment lines. This actually should not occur in the new format.

            $entryData = json_decode($line);
            if ($entryData === null) {
                // This line is not in the right format.
                $message = __(
                    'admin.job.processLogFile.wrongLoglineFormat',
                    ['file' => $this->loadId, 'lineNumber' => $lineNumber]
                );
                error_log($message);
                continue;
            }

            try {
                $this->isLogEntryValid($entryData);
            } catch (Exception $e) {
                $message = __(
                    'admin.job.processLogFile.invalidLogEntry',
                    ['file' => $this->loadId, 'lineNumber' => $lineNumber, 'error' => $e->getMessage()]
                );
                error_log($message);
                continue;
            }

            // Avoid bots.
            if (Core::isUserAgentBot($entryData->userAgent)) {
                continue;
            }

            $this->insertTemporaryUsageStatsData($entryData, $lineNumber);
        }
        fclose($fhandle);
    }

    /**
     * Delete entries in usage stats temporary tables by loadId
     */
    protected function deleteByLoadId(): void
    {
        $temporaryInstitutionsDao = DAORegistry::getDAO('TemporaryInstitutionsDAO'); /** @var TemporaryInstitutionsDAO $temporaryInstitutionsDao */
        $temporaryTotalsDao = DAORegistry::getDAO('TemporaryTotalsDAO'); /** @var TemporaryTotalsDAO $temporaryTotalsDao */
        $temporaryItemInvestigationsDao = DAORegistry::getDAO('TemporaryItemInvestigationsDAO'); /** @var TemporaryItemInvestigationsDAO $temporaryItemInvestigationsDao */
        $temporaryItemRequestsDao = DAORegistry::getDAO('TemporaryItemRequestsDAO'); /** @var TemporaryItemRequestsDAO $temporaryItemRequestsDao */

        $temporaryInstitutionsDao->deleteByLoadId($this->loadId);
        $temporaryTotalsDao->deleteByLoadId($this->loadId);
        $temporaryItemInvestigationsDao->deleteByLoadId($this->loadId);
        $temporaryItemRequestsDao->deleteByLoadId($this->loadId);
    }

    /**
     * Validate the usage stats log entry
     *
     * @throws Exception.
     */
    protected function isLogEntryValid(object $entry): void
    {
        if (!$this->validateDate($entry->time)) {
            throw new Exception(__('admin.job.processLogFile.invalidLogEntry.time'));
        }
        // check hashed IP ?
        // check canonicalUrl ?
        if (!is_int($entry->contextId)) {
            throw new Exception(__('admin.job.processLogFile.invalidLogEntry.contextId'));
        }
        if (!empty($entry->submissionId) && !is_int($entry->submissionId)) {
            throw new Exception(__('admin.job.processLogFile.invalidLogEntry.submissionId'));
        }

        $validAssocTypes = $this->getValidAssocTypes();
        if (!in_array($entry->assocType, $validAssocTypes)) {
            throw new Exception(__('admin.job.processLogFile.invalidLogEntry.assocType'));
        }
        $validFileTypes = [
            StatisticsHelper::STATISTICS_FILE_TYPE_PDF,
            StatisticsHelper::STATISTICS_FILE_TYPE_DOC,
            StatisticsHelper::STATISTICS_FILE_TYPE_HTML,
            StatisticsHelper::STATISTICS_FILE_TYPE_OTHER,
        ];
        if (!empty($entry->fileType) && !in_array($entry->fileType, $validFileTypes)) {
            throw new Exception(__('admin.job.processLogFile.invalidLogEntry.fileType'));
        }
        if (!empty($entry->country) && (!ctype_alpha($entry->country) || !(strlen($entry->country) == 2))) {
            throw new Exception(__('admin.job.processLogFile.invalidLogEntry.country'));
        }
        if (!empty($entry->region) && (!ctype_alnum($entry->region) || !(strlen($entry->region) <= 3))) {
            throw new Exception(__('admin.job.processLogFile.invalidLogEntry.region'));
        }
        if (!is_array($entry->institutionIds)) {
            throw new Exception(__('admin.job.processLogFile.invalidLogEntry.institutionIds'));
        }
        if (!empty($entry->issueId) && !is_int($entry->issueId)) {
            throw new Exception(__('admin.job.processLogFile.invalidLogEntry.issueId'));
        }
        if (!empty($entry->issueGalleyId) && !is_int($entry->issueGalleyId)) {
            throw new Exception(__('admin.job.processLogFile.invalidLogEntry.issueGalleyId'));
        }
    }

    /**
     * Validate date, check if the date is a valid date and in requested format
     */
    protected function validateDate(string $datetime, string $format = 'Y-m-d H:i:s'): bool
    {
        $d = DateTime::createFromFormat($format, $datetime);
        return $d && $d->format($format) === $datetime;
    }

    /**
     * Get valid assoc types that an usage event can contain
     */
    protected function getValidAssocTypes(): array
    {
        return [
            Application::ASSOC_TYPE_SUBMISSION_FILE,
            Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER,
            Application::ASSOC_TYPE_SUBMISSION,
            Application::ASSOC_TYPE_ISSUE_GALLEY,
            Application::ASSOC_TYPE_ISSUE,
            Application::ASSOC_TYPE_JOURNAL,
        ];
    }

    /**
     * Insert usage stats log entry into temporary tables
     */
    protected function insertTemporaryUsageStatsData(object $entry, int $lineNumber): void
    {
        $temporaryInstitutionsDao = DAORegistry::getDAO('TemporaryInstitutionsDAO'); /** @var TemporaryInstitutionsDAO $temporaryInstitutionsDao */
        $temporaryTotalsDao = DAORegistry::getDAO('TemporaryTotalsDAO'); /** @var TemporaryTotalsDAO $temporaryTotalsDao */
        $temporaryItemInvestigationsDao = DAORegistry::getDAO('TemporaryItemInvestigationsDAO'); /** @var TemporaryItemInvestigationsDAO $temporaryItemInvestigationsDao */
        $temporaryItemRequestsDao = DAORegistry::getDAO('TemporaryItemRequestsDAO'); /** @var TemporaryItemRequestsDAO $temporaryItemRequestsDao */

        try {
            $temporaryTotalsDao->insert($entry, $lineNumber, $this->loadId);
            $temporaryInstitutionsDao->insert($entry->institutionIds, $lineNumber, $this->loadId);
            if (!empty($entry->submissionId)) {
                $temporaryItemInvestigationsDao->insert($entry, $lineNumber, $this->loadId);
                if ($entry->assocType == Application::ASSOC_TYPE_SUBMISSION_FILE) {
                    $temporaryItemRequestsDao->insert($entry, $lineNumber, $this->loadId);
                }
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $message = __(
                'admin.job.processLogFile.insertError',
                ['file' => $this->loadId, 'lineNumber' => $lineNumber, 'msg' => $e->getMessage()]
            );
            error_log($message);
        }
    }
}

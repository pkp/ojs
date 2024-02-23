<?php

/**
 * @file jobs/statistics/CompileUsageStatsFromTemporaryRecords.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CompileUsageStatsFromTemporaryRecords
 *
 * @ingroup jobs
 *
 * @brief Compile the temporary usage stats and store them in the metrics table.
 *
 * @deprecated 3.4.0.5
 */

namespace APP\jobs\statistics;

use APP\statistics\StatisticsHelper;
use APP\statistics\TemporaryItemInvestigationsDAO;
use APP\statistics\TemporaryItemRequestsDAO;
use APP\statistics\TemporaryTotalsDAO;
use PKP\db\DAORegistry;
use PKP\job\exceptions\JobException;
use PKP\jobs\BaseJob;
use PKP\statistics\TemporaryInstitutionsDAO;
use PKP\task\FileLoader;

class CompileUsageStatsFromTemporaryRecords extends BaseJob
{
    /**
     * Create a new job instance.
     *
     * @param string $loadId Usage stats log file name
     */
    public function __construct(protected string $loadId)
    {
        parent::__construct();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $compileSuccessful = $this->compileMetrics();
        if (!$compileSuccessful) {
            // Move the archived file back to staging
            $filename = $this->loadId;
            $archivedFilePath = StatisticsHelper::getUsageStatsDirPath() . '/' . FileLoader::FILE_LOADER_PATH_ARCHIVE . '/' . $filename;
            if (!file_exists($archivedFilePath)) {
                $filename .= '.gz';
                $archivedFilePath = StatisticsHelper::getUsageStatsDirPath() . '/' . FileLoader::FILE_LOADER_PATH_ARCHIVE . '/' . $filename;
            }
            $stagingPath = StatisticsHelper::getUsageStatsDirPath() . '/' . FileLoader::FILE_LOADER_PATH_STAGING . '/' . $filename;

            if (!rename($archivedFilePath, $stagingPath)) {
                $message = __('admin.job.compileMetrics.returnToStaging.error', ['file' => $filename,
                    'archivedFilePath' => $archivedFilePath, 'stagingPath' => $stagingPath]);
            } else {
                $message = __('admin.job.compileMetrics.error', ['file' => $filename]);
            }

            throw new JobException($message);
        }

        $temporaryTotalsDao = DAORegistry::getDAO('TemporaryTotalsDAO'); /** @var TemporaryTotalsDAO $temporaryTotalsDao */
        $temporaryItemInvestigationsDao = DAORegistry::getDAO('TemporaryItemInvestigationsDAO'); /** @var TemporaryItemInvestigationsDAO $temporaryItemInvestigationsDao */
        $temporaryItemRequestsDao = DAORegistry::getDAO('TemporaryItemRequestsDAO'); /** @var TemporaryItemRequestsDAO $temporaryItemRequestsDao */
        $temporaryInstitutionDao = DAORegistry::getDAO('TemporaryInstitutionsDAO'); /** @var TemporaryInstitutionsDAO $temporaryInstitutionDao */

        $temporaryTotalsDao->deleteByLoadId($this->loadId);
        $temporaryItemInvestigationsDao->deleteByLoadId($this->loadId);
        $temporaryItemRequestsDao->deleteByLoadId($this->loadId);
        $temporaryInstitutionDao->deleteByLoadId($this->loadId);
    }

    /**
     * Load the entries inside the temporary database associated with
     * the passed load id to the metrics tables.
     */
    protected function compileMetrics(): bool
    {
        $temporaryTotalsDao = DAORegistry::getDAO('TemporaryTotalsDAO'); /** @var TemporaryTotalsDAO $temporaryTotalsDao */
        $temporaryItemInvestigationsDao = DAORegistry::getDAO('TemporaryItemInvestigationsDAO'); /** @var TemporaryItemInvestigationsDAO $temporaryItemInvestigationsDao */
        $temporaryItemRequestsDao = DAORegistry::getDAO('TemporaryItemRequestsDAO'); /** @var TemporaryItemRequestsDAO $temporaryItemRequestsDao */

        $temporaryTotalsDao->removeDoubleClicks($this->loadId, StatisticsHelper::COUNTER_DOUBLE_CLICK_TIME_FILTER_SECONDS);
        $temporaryItemInvestigationsDao->compileUniqueClicks($this->loadId);
        $temporaryItemRequestsDao->compileUniqueClicks($this->loadId);

        $temporaryTotalsDao->compileContextMetrics($this->loadId);
        $temporaryTotalsDao->compileIssueMetrics($this->loadId);
        $temporaryTotalsDao->compileSubmissionMetrics($this->loadId);

        // Geo database only contains total and unique investigations (no extra requests differentiation)
        $temporaryTotalsDao->deleteSubmissionGeoDailyByLoadId($this->loadId); // always call first, before loading the data
        $temporaryTotalsDao->compileSubmissionGeoDailyMetrics($this->loadId);
        $temporaryItemInvestigationsDao->compileSubmissionGeoDailyMetrics($this->loadId);

        $temporaryTotalsDao->deleteCounterSubmissionDailyByLoadId($this->loadId); // always call first, before loading the data
        $temporaryTotalsDao->compileCounterSubmissionDailyMetrics($this->loadId);
        $temporaryItemInvestigationsDao->compileCounterSubmissionDailyMetrics($this->loadId);
        $temporaryItemRequestsDao->compileCounterSubmissionDailyMetrics($this->loadId);

        $temporaryTotalsDao->deleteCounterSubmissionInstitutionDailyByLoadId($this->loadId); // always call first, before loading the data
        $temporaryTotalsDao->compileCounterSubmissionInstitutionDailyMetrics($this->loadId);
        $temporaryItemInvestigationsDao->compileCounterSubmissionInstitutionDailyMetrics($this->loadId);
        $temporaryItemRequestsDao->compileCounterSubmissionInstitutionDailyMetrics($this->loadId);

        return true;
    }
}

<?php

/**
 * @file Jobs/Statistics/LoadMetricsDataJob.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LoadMetricsDataJob
 * @ingroup jobs
 *
 * @brief Class to handle the usage metrics data loading as a Job
 */

namespace APP\Jobs\Statistics;

use APP\statistics\StatisticsHelper;
use PKP\db\DAORegistry;
use PKP\Domains\Jobs\Exceptions\JobException;
use PKP\Support\Jobs\BaseJob;
use PKP\task\FileLoader;

class LoadMetricsDataJob extends BaseJob
{
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
        $loadSuccessful = $this->_loadData();
        if (!$loadSuccessful) {
            // Move the archived file back to staging
            $filename = $this->loadId;
            $archivedFilePath = StatisticsHelper::getUsageStatsDirPath() . DIRECTORY_SEPARATOR . FileLoader::FILE_LOADER_PATH_ARCHIVE . DIRECTORY_SEPARATOR . $filename;
            if (!file_exists($archivedFilePath)) {
                $filename .= '.gz';
                $archivedFilePath = StatisticsHelper::getUsageStatsDirPath() . DIRECTORY_SEPARATOR . FileLoader::FILE_LOADER_PATH_ARCHIVE . DIRECTORY_SEPARATOR . $filename;
            }
            $stagingPath = StatisticsHelper::getUsageStatsDirPath() . DIRECTORY_SEPARATOR . FileLoader::FILE_LOADER_PATH_STAGING . DIRECTORY_SEPARATOR . $filename;

            if (!rename($archivedFilePath, $stagingPath)) {
                $message = __('admin.job.loadMetricsData.returnToStaging.error', ['file' => $filename,
                    'archivedFilePath' => $archivedFilePath, 'stagingPath' => $stagingPath]);
            } else {
                $message = __('admin.job.loadMetricsData.error', ['file' => $filename]);
            }
            $this->failed(new JobException($message));
            return;
        }

        $statsTotalDao = DAORegistry::getDAO('UsageStatsTotalTemporaryRecordDAO'); /* @var UsageStatsTotalTemporaryRecordDAO $statsTotalDao */
        $statsUniqueItemInvestigationsDao = DAORegistry::getDAO('UsageStatsUniqueItemInvestigationsTemporaryRecordDAO'); /* @var UsageStatsUniqueItemInvestigationsTemporaryRecordDAO $statsUniqueItemInvestigationsDao */
        $statsUniqueItemRequestsDao = DAORegistry::getDAO('UsageStatsUniqueItemRequestsTemporaryRecordDAO'); /* @var UsageStatsUniqueItemRequestsTemporaryRecordDAO $statsUniqueItemRequestsDao */
        $statsInstitutionDao = DAORegistry::getDAO('UsageStatsInstitutionTemporaryRecordDAO'); /* @var UsageStatsInstitutionTemporaryRecordDAO $statsInstitutionDao */

        $statsTotalDao->deleteByLoadId($this->loadId);
        $statsUniqueItemInvestigationsDao->deleteByLoadId($this->loadId);
        $statsUniqueItemRequestsDao->deleteByLoadId($this->loadId);
        $statsInstitutionDao->deleteByLoadId($this->loadId);
    }

    /**
     * Load the entries inside the temporary database associated with
     * the passed load id to the metrics tables.
     */
    private function _loadData(): bool
    {
        $statsTotalDao = DAORegistry::getDAO('UsageStatsTotalTemporaryRecordDAO'); /* @var UsageStatsTotalTemporaryRecordDAO $statsTotalDao */
        $statsUniqueItemInvestigationsDao = DAORegistry::getDAO('UsageStatsUniqueItemInvestigationsTemporaryRecordDAO'); /* @var UsageStatsUniqueItemInvestigationsTemporaryRecordDAO $statsUniqueItemInvestigationsDao */
        $statsUniqueItemRequestsDao = DAORegistry::getDAO('UsageStatsUniqueItemRequestsTemporaryRecordDAO'); /* @var UsageStatsUniqueItemRequestsTemporaryRecordDAO $statsUniqueItemRequestsDao */

        $statsTotalDao->removeDoubleClicks(StatisticsHelper::COUNTER_DOUBLE_CLICK_TIME_FILTER_SECONDS);
        $statsUniqueItemInvestigationsDao->removeUniqueClicks();
        $statsUniqueItemRequestsDao->removeUniqueClicks();

        $statsTotalDao->loadMetricsContext($this->loadId);
        $statsTotalDao->loadMetricsIssue($this->loadId);
        $statsTotalDao->loadMetricsSubmission($this->loadId);

        // Geo database only contains total and unique investigations (no extra requests differentiation)
        $statsTotalDao->deleteSubmissionGeoDailyByLoadId($this->loadId); // always call first, before loading the data
        $statsTotalDao->loadMetricsSubmissionGeoDaily($this->loadId);
        $statsUniqueItemInvestigationsDao->loadMetricsSubmissionGeoDaily($this->loadId);

        $statsTotalDao->deleteCounterSubmissionDailyByLoadId($this->loadId); // always call first, before loading the data
        $statsTotalDao->loadMetricsCounterSubmissionDaily($this->loadId);
        $statsUniqueItemInvestigationsDao->loadMetricsCounterSubmissionDaily($this->loadId);
        $statsUniqueItemRequestsDao->loadMetricsCounterSubmissionDaily($this->loadId);

        $statsTotalDao->deleteCounterSubmissionInstitutionDailyByLoadId($this->loadId); // always call first, before loading the data
        $statsTotalDao->loadMetricsCounterSubmissionInstitutionDaily($this->loadId);
        $statsUniqueItemInvestigationsDao->loadMetricsCounterSubmissionInstitutionDaily($this->loadId);
        $statsUniqueItemRequestsDao->loadMetricsCounterSubmissionInstitutionDaily($this->loadId);

        return true;
    }
}

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
use APP\statistics\TemporaryItemInvestigationsDAO;
use APP\statistics\TemporaryItemRequestsDAO;
use APP\statistics\TemporaryTotalsDAO;
use Exception;
use PKP\db\DAORegistry;
use PKP\jobs\statistics\PKPProcessUsageStatsLogFile;
use PKP\statistics\TemporaryInstitutionsDAO;

class ProcessUsageStatsLogFile extends PKPProcessUsageStatsLogFile
{
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

    protected function validateLogEntry(object $entry): void
    {
        parent::validateLogEntry($entry);
        if (!empty($entry->issueId) && !is_int($entry->issueId)) {
            throw new Exception(__('admin.job.processLogFile.invalidLogEntry.issueId'));
        }
        if (!empty($entry->issueGalleyId) && !is_int($entry->issueGalleyId)) {
            throw new Exception(__('admin.job.processLogFile.invalidLogEntry.issueGalleyId'));
        }
    }

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

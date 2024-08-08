<?php

/**
 * @file tests/jobs/statistics/CompileUsageStatsFromTemporaryRecordsTest.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Tests for compile usage stats from temporary records job.
 */

namespace APP\tests\jobs\statistics;

use APP\jobs\statistics\CompileUsageStatsFromTemporaryRecords;
use Mockery;
use PKP\db\DAORegistry;
use PKP\tests\PKPTestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @see https://docs.phpunit.de/en/9.6/annotations.html#runtestsinseparateprocesses
 */
class CompileUsageStatsFromTemporaryRecordsTest extends PKPTestCase
{
    /**
     * base64_encoded serializion from OJS 3.4.0
     */
    protected string $serializedJobData = <<<END
    O:57:"APP\\jobs\\statistics\\CompileUsageStatsFromTemporaryRecords":3:{s:9:"\0*\0loadId";s:25:"usage_events_20240130.log";s:10:"connection";s:8:"database";s:5:"queue";s:5:"queue";}
    END;

    /**
     * Test job is a proper instance
     */
    public function testUnserializationGetProperDepositIssueJobInstance(): void
    {
        $this->assertInstanceOf(
            CompileUsageStatsFromTemporaryRecords::class,
            unserialize($this->serializedJobData)
        );
    }

    /**
     * Ensure that a serialized job can be unserialized and executed
     */
    public function testRunSerializedJob()
    {
        /** @var CompileUsageStatsFromTemporaryRecords $compileUsageStatsFromTemporaryRecordsJob */
        $compileUsageStatsFromTemporaryRecordsJob = unserialize($this->serializedJobData);

        $temporaryTotalsDAOMock = Mockery::mock(\APP\statistics\TemporaryTotalsDAO::class)
            ->makePartial()
            ->shouldReceive([
                'deleteByLoadId' => null,
                'compileContextMetrics' => null,
                'compileIssueMetrics' => null,
                'compileSubmissionMetrics' => null,
                'deleteSubmissionGeoDailyByLoadId' => null,
                'compileSubmissionGeoDailyMetrics' => null,
                'deleteCounterSubmissionDailyByLoadId' => null,
                'compileCounterSubmissionDailyMetrics' => null,
                'deleteCounterSubmissionInstitutionDailyByLoadId' => null,
                'compileCounterSubmissionInstitutionDailyMetrics' => null,
            ])
            ->withAnyArgs()
            ->getMock();

        DAORegistry::registerDAO('TemporaryTotalsDAO', $temporaryTotalsDAOMock);

        $temporaryItemInvestigationsDAOMock = Mockery::mock(\APP\statistics\TemporaryItemInvestigationsDAO::class)
            ->makePartial()
            ->shouldReceive([
                'deleteByLoadId' => null,
                'compileUniqueClicks' => null,
                'compileSubmissionGeoDailyMetrics' => null,
                'compileCounterSubmissionDailyMetrics' => null,
                'compileCounterSubmissionInstitutionDailyMetrics' => null,
            ])
            ->withAnyArgs()
            ->getMock();

        DAORegistry::registerDAO('TemporaryItemInvestigationsDAO', $temporaryItemInvestigationsDAOMock);

        $temporaryItemRequestsDAOMock = Mockery::mock(\APP\statistics\TemporaryItemRequestsDAO::class)
            ->makePartial()
            ->shouldReceive([
                'deleteByLoadId' => null,
                'compileUniqueClicks' => null,
                'compileCounterSubmissionDailyMetrics' => null,
                'compileCounterSubmissionInstitutionDailyMetrics' => null,
            ])
            ->withAnyArgs()
            ->getMock();

        DAORegistry::registerDAO('TemporaryItemRequestsDAO', $temporaryItemRequestsDAOMock);

        $temporaryInstitutionsDAOMock = Mockery::mock(\PKP\statistics\TemporaryInstitutionsDAO::class)
            ->makePartial()
            ->shouldReceive([
                'deleteByLoadId' => null,
            ])
            ->withAnyArgs()
            ->getMock();

        DAORegistry::registerDAO('TemporaryInstitutionsDAO', $temporaryInstitutionsDAOMock);

        $this->assertNull($compileUsageStatsFromTemporaryRecordsJob->handle());
    }
}

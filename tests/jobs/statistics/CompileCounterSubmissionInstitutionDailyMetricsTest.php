<?php

/**
 * @file tests/jobs/statistics/CompileCounterSubmissionInstitutionDailyMetricsTest.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Tests for compile counter submission institution daily metrics job.
 */

namespace APP\tests\jobs\statistics;

use APP\jobs\statistics\CompileCounterSubmissionInstitutionDailyMetrics;
use Mockery;
use PKP\db\DAORegistry;
use PKP\tests\PKPTestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @see https://docs.phpunit.de/en/9.6/annotations.html#runtestsinseparateprocesses
 */
class CompileCounterSubmissionInstitutionDailyMetricsTest extends PKPTestCase
{
    /**
     * base64_encoded serializion from OJS 3.4.0
     */
    protected string $serializedJobData = <<<END
    O:67:"APP\\jobs\\statistics\\CompileCounterSubmissionInstitutionDailyMetrics":3:{s:9:"\0*\0loadId";s:25:"usage_events_20240130.log";s:10:"connection";s:8:"database";s:5:"queue";s:5:"queue";}
    END;

    /**
     * Test job is a proper instance
     */
    public function testUnserializationGetProperDepositIssueJobInstance(): void
    {
        $this->assertInstanceOf(
            CompileCounterSubmissionInstitutionDailyMetrics::class,
            unserialize($this->serializedJobData)
        );
    }

    /**
     * Ensure that a serialized job can be unserialized and executed
     */
    public function testRunSerializedJob(): void
    {
        /** @var CompileCounterSubmissionInstitutionDailyMetrics $compileCounterSubmissionInstitutionDailyMetricsJob */
        $compileCounterSubmissionInstitutionDailyMetricsJob = unserialize($this->serializedJobData);

        $temporaryTotalsDAOMock = Mockery::mock(\APP\statistics\TemporaryTotalsDAO::class)
            ->makePartial()
            ->shouldReceive([
                'deleteCounterSubmissionInstitutionDailyByLoadId' => null,
                'compileCounterSubmissionDailyMetrics' => null,
            ])
            ->withAnyArgs()
            ->getMock();

        DAORegistry::registerDAO('TemporaryTotalsDAO', $temporaryTotalsDAOMock);

        $temporaryItemInvestigationsDAOMock = Mockery::mock(\APP\statistics\TemporaryItemInvestigationsDAO::class)
            ->makePartial()
            ->shouldReceive([
                'compileCounterSubmissionInstitutionDailyMetrics' => null,
            ])
            ->withAnyArgs()
            ->getMock();

        DAORegistry::registerDAO('TemporaryItemInvestigationsDAO', $temporaryItemInvestigationsDAOMock);

        $temporaryItemRequestsDAOMock = Mockery::mock(\APP\statistics\TemporaryItemRequestsDAO::class)
            ->makePartial()
            ->shouldReceive([
                'compileCounterSubmissionInstitutionDailyMetrics' => null,
            ])
            ->withAnyArgs()
            ->getMock();

        DAORegistry::registerDAO('TemporaryItemRequestsDAO', $temporaryItemRequestsDAOMock);


        $this->assertNull($compileCounterSubmissionInstitutionDailyMetricsJob->handle());
    }
}

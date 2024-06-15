<?php

/**
 * @file tests/jobs/statistics/DeleteUsageStatsTemporaryRecordsTest.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Tests for delete usage stats temporary records job.
 */

namespace APP\tests\jobs\statistics;

use APP\jobs\statistics\DeleteUsageStatsTemporaryRecords;
use Mockery;
use PKP\db\DAORegistry;
use PKP\tests\PKPTestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @see https://docs.phpunit.de/en/9.6/annotations.html#runtestsinseparateprocesses
 */
class DeleteUsageStatsTemporaryRecordsTest extends PKPTestCase
{
    /**
     * base64_encoded serializion from OJS 3.4.0
     */
    protected string $serializedJobData = 'Tzo1MjoiQVBQXGpvYnNcc3RhdGlzdGljc1xEZWxldGVVc2FnZVN0YXRzVGVtcG9yYXJ5UmVjb3JkcyI6Mzp7czo5OiIAKgBsb2FkSWQiO3M6MjU6InVzYWdlX2V2ZW50c18yMDI0MDEzMC5sb2ciO3M6MTA6ImNvbm5lY3Rpb24iO3M6ODoiZGF0YWJhc2UiO3M6NToicXVldWUiO3M6NToicXVldWUiO30=';

    /**
     * Test job is a proper instance
     */
    public function testUnserializationGetProperDepositIssueJobInstance(): void
    {
        $this->assertInstanceOf(
            DeleteUsageStatsTemporaryRecords::class,
            unserialize(base64_decode($this->serializedJobData))
        );
    }

    /**
     * Ensure that a serialized job can be unserialized and executed
     */
    public function testRunSerializedJob()
    {
        /** @var DeleteUsageStatsTemporaryRecords $deleteUsageStatsTemporaryRecordsJob */
        $deleteUsageStatsTemporaryRecordsJob = unserialize(base64_decode($this->serializedJobData));

        $temporaryTotalsDAOMock = Mockery::mock(\APP\statistics\TemporaryTotalsDAO::class)
            ->makePartial()
            ->shouldReceive([
                'deleteByLoadId' => null,
            ])
            ->withAnyArgs()
            ->getMock();

        DAORegistry::registerDAO('TemporaryTotalsDAO', $temporaryTotalsDAOMock);

        $temporaryItemInvestigationsDAOMock = Mockery::mock(\APP\statistics\TemporaryItemInvestigationsDAO::class)
            ->makePartial()
            ->shouldReceive([
                'deleteByLoadId' => null,
            ])
            ->withAnyArgs()
            ->getMock();

        DAORegistry::registerDAO('TemporaryItemInvestigationsDAO', $temporaryItemInvestigationsDAOMock);

        $temporaryItemRequestsDAOMock = Mockery::mock(\APP\statistics\TemporaryItemRequestsDAO::class)
            ->makePartial()
            ->shouldReceive([
                'deleteByLoadId' => null,
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

        $this->assertNull($deleteUsageStatsTemporaryRecordsJob->handle());
    }
}

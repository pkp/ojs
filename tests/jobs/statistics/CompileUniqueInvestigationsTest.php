<?php

/**
 * @file tests/jobs/statistics/CompileUniqueInvestigationsTest.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Tests for compile unique investigations job.
 */

namespace APP\tests\jobs\statistics;

use APP\jobs\statistics\CompileUniqueInvestigations;
use Mockery;
use PKP\db\DAORegistry;
use PKP\tests\PKPTestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @see https://docs.phpunit.de/en/9.6/annotations.html#runtestsinseparateprocesses
 */
class CompileUniqueInvestigationsTest extends PKPTestCase
{
    /**
     * base64_encoded serializion from OJS 3.4.0
     */
    protected string $serializedJobData = <<<END
    O:47:"APP\\jobs\\statistics\\CompileUniqueInvestigations":3:{s:9:"\0*\0loadId";s:25:"usage_events_20240130.log";s:10:"connection";s:8:"database";s:5:"queue";s:5:"queue";}
    END;

    /**
     * Test job is a proper instance
     */
    public function testUnserializationGetProperDepositIssueJobInstance(): void
    {
        $this->assertInstanceOf(
            CompileUniqueInvestigations::class,
            unserialize($this->serializedJobData)
        );
    }

    /**
     * Ensure that a serialized job can be unserialized and executed
     */
    public function testRunSerializedJob(): void
    {
        /** @var CompileUniqueInvestigations $compileUniqueInvestigationsJob */
        $compileUniqueInvestigationsJob = unserialize($this->serializedJobData);

        $temporaryItemInvestigationsDAOMock = Mockery::mock(\APP\statistics\TemporaryItemInvestigationsDAO::class)
            ->makePartial()
            ->shouldReceive([
                'compileUniqueClicks' => null,
            ])
            ->withAnyArgs()
            ->getMock();

        DAORegistry::registerDAO('TemporaryItemInvestigationsDAO', $temporaryItemInvestigationsDAOMock);

        $this->assertNull($compileUniqueInvestigationsJob->handle());
    }
}

<?php

/**
 * @file tests/jobs/statistics/CompileUniqueRequestsTest.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Tests for compile unique requests job.
 */

namespace APP\tests\jobs\statistics;

use APP\jobs\statistics\CompileUniqueRequests;
use Mockery;
use PKP\db\DAORegistry;
use PKP\tests\PKPTestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @see https://docs.phpunit.de/en/9.6/annotations.html#runtestsinseparateprocesses
 */
class CompileUniqueRequestsTest extends PKPTestCase
{
    /**
     * base64_encoded serializion from OJS 3.4.0
     */
    protected string $serializedJobData = 'Tzo0MToiQVBQXGpvYnNcc3RhdGlzdGljc1xDb21waWxlVW5pcXVlUmVxdWVzdHMiOjM6e3M6OToiACoAbG9hZElkIjtzOjI1OiJ1c2FnZV9ldmVudHNfMjAyNDAxMzAubG9nIjtzOjEwOiJjb25uZWN0aW9uIjtzOjg6ImRhdGFiYXNlIjtzOjU6InF1ZXVlIjtzOjU6InF1ZXVlIjt9';

    /**
     * Test job is a proper instance
     */
    public function testUnserializationGetProperDepositIssueJobInstance(): void
    {
        $this->assertInstanceOf(
            CompileUniqueRequests::class,
            unserialize(base64_decode($this->serializedJobData))
        );
    }

    /**
     * Ensure that a serialized job can be unserialized and executed
     */
    public function testRunSerializedJob()
    {
        /** @var CompileUniqueRequests $compileUniqueRequestsJob */
        $compileUniqueRequestsJob = unserialize(base64_decode($this->serializedJobData));

        $temporaryItemRequestsDAOMock = Mockery::mock(\APP\statistics\TemporaryItemRequestsDAO::class)
            ->makePartial()
            ->shouldReceive([
                'compileUniqueClicks' => null,
            ])
            ->withAnyArgs()
            ->getMock();

        DAORegistry::registerDAO('TemporaryItemRequestsDAO', $temporaryItemRequestsDAOMock);

        $this->assertNull($compileUniqueRequestsJob->handle());
    }
}

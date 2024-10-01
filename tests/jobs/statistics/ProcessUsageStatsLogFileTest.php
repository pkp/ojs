<?php

/**
 * @file tests/jobs/statistics/ProcessUsageStatsLogFileTest.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Tests for process usage stats log file job.
 */

namespace APP\tests\jobs\statistics;

use APP\jobs\statistics\ProcessUsageStatsLogFile;
use APP\statistics\StatisticsHelper;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PKP\db\DAORegistry;
use PKP\file\FileManager;
use PKP\task\FileLoader;
use PKP\tests\PKPTestCase;
use ReflectionClass;

#[RunTestsInSeparateProcesses]
#[CoversClass(ProcessUsageStatsLogFile::class)]
class ProcessUsageStatsLogFileTest extends PKPTestCase
{
    /**
     * base64_encoded serializion from OJS 3.4.0
     */
    protected string $serializedJobData = <<<END
    O:44:"APP\\jobs\\statistics\\ProcessUsageStatsLogFile":3:{s:9:"\0*\0loadId";s:25:"usage_events_20240130.log";s:10:"connection";s:8:"database";s:5:"queue";s:5:"queue";}
    END;

    /**
     * Content example from OJS 3.4.0
     */
    protected $dummyFileContent = '{"time":"2023-08-07 17:27:11","ip":"228dc4e5b6424e9dad52f21261cb2ab5f4651d9cb426d6fdb3d71d5ab8e2ae83","userAgent":"Mozilla\/5.0 (Macintosh; Intel Mac OS X 10.15; rv:109.0) Gecko\/20100101 Firefox\/115.0","canonicalUrl":"http:\/\/ojs-stable-3_4_0.test\/index.php\/publicknowledge\/index","assocType":256,"contextId":1,"submissionId":null,"representationId":null,"submissionFileId":null,"fileType":null,"country":null,"region":null,"city":null,"institutionIds":[],"version":"3.4.0.0","issueId":null,"issueGalleyId":null}';

    /**
     * Test job is a proper instance
     */
    public function testUnserializationGetProperDepositIssueJobInstance(): void
    {
        $this->assertInstanceOf(
            ProcessUsageStatsLogFile::class,
            unserialize($this->serializedJobData)
        );
    }

    /**
     * Ensure that a serialized job can be unserialized and executed
     */
    public function testRunSerializedJob(): void
    {
        /** @var ProcessUsageStatsLogFile $processUsageStatsLogFileJob */
        $processUsageStatsLogFileJob = unserialize($this->serializedJobData);

        // we need to create a dummy file if not existed as to avoid mocking PHP's built in functions
        $dummyFile = $this->createDummyFileIfNeeded($processUsageStatsLogFileJob, 'loadId');

        $this->createArchiveDirectoryIfRequired();

        $temporaryTotalsDAOMock = Mockery::mock(\APP\statistics\TemporaryTotalsDAO::class)
            ->makePartial()
            ->shouldReceive([
                'deleteByLoadId' => null,
                'insert' => null,
            ])
            ->withAnyArgs()
            ->getMock();

        DAORegistry::registerDAO('TemporaryTotalsDAO', $temporaryTotalsDAOMock);

        $temporaryItemInvestigationsDAOMock = Mockery::mock(\APP\statistics\TemporaryItemInvestigationsDAO::class)
            ->makePartial()
            ->shouldReceive([
                'deleteByLoadId' => null,
                'insert' => null,
            ])
            ->withAnyArgs()
            ->getMock();

        DAORegistry::registerDAO('TemporaryItemInvestigationsDAO', $temporaryItemInvestigationsDAOMock);

        $temporaryItemRequestsDAOMock = Mockery::mock(\APP\statistics\TemporaryItemRequestsDAO::class)
            ->makePartial()
            ->shouldReceive([
                'deleteByLoadId' => null,
                'insert' => null,
            ])
            ->withAnyArgs()
            ->getMock();

        DAORegistry::registerDAO('TemporaryItemRequestsDAO', $temporaryItemRequestsDAOMock);

        $temporaryInstitutionsDAOMock = Mockery::mock(\PKP\statistics\TemporaryInstitutionsDAO::class)
            ->makePartial()
            ->shouldReceive([
                'deleteByLoadId' => null,
                'insert' => null,
            ])
            ->withAnyArgs()
            ->getMock();

        DAORegistry::registerDAO('TemporaryInstitutionsDAO', $temporaryInstitutionsDAOMock);

        $processUsageStatsLogFileJob->handle();

        if ($dummyFile) {
            unlink($dummyFile);
        }

        $this->expectNotToPerformAssertions();
    }

    /**
     * Create the dummy file with dummy content if required
     */
    protected function createDummyFileIfNeeded(ProcessUsageStatsLogFile $job, string $propertyName): ?string
    {
        $reflection = new ReflectionClass($job);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $fileName = $property->getValue($job);

        $filePath = StatisticsHelper::getUsageStatsDirPath()
            . DIRECTORY_SEPARATOR
            . FileLoader::FILE_LOADER_PATH_DISPATCH
            . DIRECTORY_SEPARATOR;

        if (!file_exists($filePath . $fileName)) {

            // create the 'FileLoader::FILE_LOADER_PATH_DISPATCH' directory if not exists
            if (!file_exists($filePath)) {
                $fileManager = new FileManager();
                $fileManager->mkdirtree($filePath);
            }

            touch($filePath . $fileName);

            file_put_contents($filePath . $fileName, $this->dummyFileContent);
            return $filePath . $fileName;
        }

        return null;
    }

    /**
     * Create the archive path/directory as needed
     */
    protected function createArchiveDirectoryIfRequired(): bool
    {
        $filePath = StatisticsHelper::getUsageStatsDirPath()
            . DIRECTORY_SEPARATOR
            . FileLoader::FILE_LOADER_PATH_ARCHIVE
            . DIRECTORY_SEPARATOR;

        if (file_exists($filePath)) {
            return true;
        }

        // create the 'FileLoader::FILE_LOADER_PATH_ARCHIVE' directory if not exists
        $fileManager = new FileManager();
        $fileManager->mkdirtree($filePath);

        return file_exists($filePath);
    }
}

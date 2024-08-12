<?php

/**
 * @file tests/jobs/notifications/OpenAccessMailUsersTest.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Tests for open access mail to users job.
 */

namespace APP\tests\jobs\notifications;

use APP\issue\Repository as IssueRepository;
use APP\jobs\notifications\OpenAccessMailUsers;
use Mockery;
use PKP\db\DAORegistry;
use PKP\emailTemplate\Repository as EmailTemplateRepository;
use PKP\tests\PKPTestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @see https://docs.phpunit.de/en/9.6/annotations.html#runtestsinseparateprocesses
 */
class OpenAccessMailUsersTest extends PKPTestCase
{
    /**
     * base64_encoded serializion from OJS 3.4.0
     */
    protected string $serializedJobData = <<<END
    O:42:"APP\\jobs\\notifications\\OpenAccessMailUsers":6:{s:10:"\0*\0userIds";O:29:"Illuminate\\Support\\Collection":2:{s:8:"\0*\0items";a:2:{i:0;i:1;i:1;i:2;}s:28:"\0*\0escapeWhenCastingToString";b:0;}s:12:"\0*\0contextId";i:1;s:10:"\0*\0issueId";i:1;s:10:"connection";s:8:"database";s:5:"queue";s:5:"queue";s:7:"batchId";s:36:"9c1c4502-5261-4b4a-965c-256cd0eaaaa4";}
    END;

    /**
     * Test job is a proper instance
     */
    public function testUnserializationGetProperDepositIssueJobInstance(): void
    {
        $this->assertInstanceOf(
            OpenAccessMailUsers::class,
            unserialize($this->serializedJobData)
        );
    }

    /**
     * Ensure that a serialized job can be unserialized and executed
     */
    public function testRunSerializedJob(): void
    {
        $this->mockMail();

        // need to mock request so that a valid context information is set and can be retrived
        $this->mockRequest();

        /** @var OpenAccessMailUsers $openAccessMailUsersJob */
        $openAccessMailUsersJob = unserialize($this->serializedJobData);

        $journalDAOMock = Mockery::mock(\APP\journal\JournalDAO::class)
            ->makePartial()
            ->shouldReceive('getId')
            ->withAnyArgs()
            ->andReturn(
                Mockery::mock(\APP\journal\Journal::class)
                    ->makePartial()
                    ->shouldReceive([
                        'getData' => '',
                        'getPrimaryLocale' => 'en'
                    ])
                    ->withAnyArgs()
                    ->getMock()
            )
            ->getMock();

        DAORegistry::registerDAO('JournalDAO', $journalDAOMock);

        $issueRepoMock = Mockery::mock(app(IssueRepository::class))
            ->makePartial()
            ->shouldReceive([
                'get' => new \APP\issue\Issue(),
            ])
            ->withAnyArgs()
            ->getMock();

        app()->instance(IssueRepository::class, $issueRepoMock);

        $emailTemplateMock = Mockery::mock(\PKP\emailTemplate\EmailTemplate::class)
            ->makePartial()
            ->shouldReceive([
                'getLocalizedData' => 'some test string',
            ])
            ->withAnyArgs()
            ->getMock();

        $emailTemplateRepoMock = Mockery::mock(app(EmailTemplateRepository::class))
            ->makePartial()
            ->shouldReceive([
                'getByKey' => $emailTemplateMock,
            ])
            ->withAnyArgs()
            ->getMock();

        app()->instance(EmailTemplateRepository::class, $emailTemplateRepoMock);

        $this->assertNull($openAccessMailUsersJob->handle());
    }
}

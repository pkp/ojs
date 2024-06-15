<?php

/**
 * @file tests/jobs/notifications/IssuePublishedNotifyUsersTest.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Tests for notifying users about issue publish job.
 */

namespace APP\tests\jobs\notifications;

use APP\jobs\notifications\IssuePublishedNotifyUsers;
use Mockery;
use PKP\db\DAORegistry;
use PKP\emailTemplate\Repository as EmailTemplateRepository;
use PKP\tests\PKPTestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @see https://docs.phpunit.de/en/9.6/annotations.html#runtestsinseparateprocesses
 */
class IssuePublishedNotifyUsersTest extends PKPTestCase
{
    /**
     * base64_encoded serializion from OJS 3.4.0
     */
    protected string $serializedJobData = 'Tzo0ODoiQVBQXGpvYnNcbm90aWZpY2F0aW9uc1xJc3N1ZVB1Ymxpc2hlZE5vdGlmeVVzZXJzIjo4OntzOjE1OiIAKgByZWNpcGllbnRJZHMiO086Mjk6IklsbHVtaW5hdGVcU3VwcG9ydFxDb2xsZWN0aW9uIjoyOntzOjg6IgAqAGl0ZW1zIjthOjE4OntpOjA7aToxO2k6MTtpOjI7aToyO2k6MztpOjM7aTo0O2k6NDtpOjU7aTo1O2k6NjtpOjY7aTo3O2k6NztpOjg7aTo4O2k6OTtpOjk7aToxMDtpOjEwO2k6MTE7aToxMTtpOjEyO2k6MTI7aToxMztpOjEzO2k6MTQ7aToxNDtpOjE1O2k6MTU7aToxNjtpOjM1O2k6Mzc7aTozNjtpOjM4O31zOjI4OiIAKgBlc2NhcGVXaGVuQ2FzdGluZ1RvU3RyaW5nIjtiOjA7fXM6MTI6IgAqAGNvbnRleHRJZCI7aToxO3M6ODoiACoAaXNzdWUiO086MTU6IkFQUFxpc3N1ZVxJc3N1ZSI6Njp7czo1OiJfZGF0YSI7YToyMTp7czoyOiJpZCI7aToyO3M6OToiam91cm5hbElkIjtpOjE7czo2OiJ2b2x1bWUiO2k6MjtzOjY6Im51bWJlciI7czoxOiIxIjtzOjQ6InllYXIiO2k6MjAxNTtzOjk6InB1Ymxpc2hlZCI7aToxO3M6MTM6ImRhdGVQdWJsaXNoZWQiO3M6MTk6IjIwMjQtMDUtMjMgMTE6NTk6NDYiO3M6MTI6ImRhdGVOb3RpZmllZCI7TjtzOjEyOiJsYXN0TW9kaWZpZWQiO3M6MTk6IjIwMjQtMDUtMjMgMTE6NTk6NDYiO3M6MTI6ImFjY2Vzc1N0YXR1cyI7aToxO3M6MTQ6Im9wZW5BY2Nlc3NEYXRlIjtOO3M6MTA6InNob3dWb2x1bWUiO2I6MTtzOjEwOiJzaG93TnVtYmVyIjtiOjE7czo4OiJzaG93WWVhciI7YjoxO3M6OToic2hvd1RpdGxlIjtiOjA7czoxMzoic3R5bGVGaWxlTmFtZSI7TjtzOjIxOiJvcmlnaW5hbFN0eWxlRmlsZU5hbWUiO047czo3OiJ1cmxQYXRoIjtzOjA6IiI7czo1OiJkb2lJZCI7aTo1O3M6MTE6ImRlc2NyaXB0aW9uIjthOjI6e3M6MjoiZW4iO3M6MDoiIjtzOjU6ImZyX0NBIjtzOjA6IiI7fXM6NToidGl0bGUiO2E6Mjp7czoyOiJlbiI7czowOiIiO3M6NToiZnJfQ0EiO3M6MDoiIjt9fXM6MjA6Il9oYXNMb2FkYWJsZUFkYXB0ZXJzIjtiOjA7czoyNzoiX21ldGFkYXRhRXh0cmFjdGlvbkFkYXB0ZXJzIjthOjA6e31zOjI1OiJfZXh0cmFjdGlvbkFkYXB0ZXJzTG9hZGVkIjtiOjA7czoyNjoiX21ldGFkYXRhSW5qZWN0aW9uQWRhcHRlcnMiO2E6MDp7fXM6MjQ6Il9pbmplY3Rpb25BZGFwdGVyc0xvYWRlZCI7YjowO31zOjk6IgAqAGxvY2FsZSI7czoyOiJlbiI7czo5OiIAKgBzZW5kZXIiO086MTM6IlBLUFx1c2VyXFVzZXIiOjc6e3M6NToiX2RhdGEiO2E6MjI6e3M6MjoiaWQiO2k6MTtzOjg6InVzZXJOYW1lIjtzOjU6ImFkbWluIjtzOjg6InBhc3N3b3JkIjtzOjYwOiIkMnkkMTAkdUZtWVhnOC9VZmEwSGJza3lXNTdCZTIyc3RGR1k1cXR4SlptVE9hZTNQZkRCODZWM3g3QlciO3M6NToiZW1haWwiO3M6MjM6InBrcGFkbWluQG1haWxpbmF0b3IuY29tIjtzOjM6InVybCI7TjtzOjU6InBob25lIjtOO3M6MTQ6Im1haWxpbmdBZGRyZXNzIjtOO3M6MTQ6ImJpbGxpbmdBZGRyZXNzIjtOO3M6NzoiY291bnRyeSI7TjtzOjc6ImxvY2FsZXMiO2E6MDp7fXM6NjoiZ29zc2lwIjtOO3M6MTM6ImRhdGVMYXN0RW1haWwiO047czoxNDoiZGF0ZVJlZ2lzdGVyZWQiO3M6MTk6IjIwMjMtMDItMjggMjA6MTk6MDciO3M6MTM6ImRhdGVWYWxpZGF0ZWQiO047czoxMzoiZGF0ZUxhc3RMb2dpbiI7czoxOToiMjAyNC0wNS0yMiAxOTowNTowMyI7czoxODoibXVzdENoYW5nZVBhc3N3b3JkIjtOO3M6NzoiYXV0aFN0ciI7TjtzOjg6ImRpc2FibGVkIjtiOjA7czoxNDoiZGlzYWJsZWRSZWFzb24iO047czoxMDoiaW5saW5lSGVscCI7YjoxO3M6MTA6ImZhbWlseU5hbWUiO2E6MTp7czoyOiJlbiI7czo1OiJhZG1pbiI7fXM6OToiZ2l2ZW5OYW1lIjthOjE6e3M6MjoiZW4iO3M6NToiYWRtaW4iO319czoyMDoiX2hhc0xvYWRhYmxlQWRhcHRlcnMiO2I6MDtzOjI3OiJfbWV0YWRhdGFFeHRyYWN0aW9uQWRhcHRlcnMiO2E6MDp7fXM6MjU6Il9leHRyYWN0aW9uQWRhcHRlcnNMb2FkZWQiO2I6MDtzOjI2OiJfbWV0YWRhdGFJbmplY3Rpb25BZGFwdGVycyI7YTowOnt9czoyNDoiX2luamVjdGlvbkFkYXB0ZXJzTG9hZGVkIjtiOjA7czo5OiIAKgBfcm9sZXMiO2E6MDp7fX1zOjEwOiJjb25uZWN0aW9uIjtzOjg6ImRhdGFiYXNlIjtzOjU6InF1ZXVlIjtzOjU6InF1ZXVlIjtzOjc6ImJhdGNoSWQiO3M6MzY6IjljMWMxMzY4LThiYjQtNDE3OS1hMzUwLTQwM2RlY2I3MDBiYSI7fQ==';

    /**
     * Test job is a proper instance
     */
    public function testUnserializationGetProperDepositIssueJobInstance(): void
    {
        $this->assertInstanceOf(
            IssuePublishedNotifyUsers::class,
            unserialize(base64_decode($this->serializedJobData))
        );
    }

    /**
     * Ensure that a serialized job can be unserialized and executed
     */
    public function testRunSerializedJob()
    {
        $this->mockMail();

        // need to mock request so that a valid context information is set and can be retrived
        $this->mockRequest();

        /** @var IssuePublishedNotifyUsers $issuePublishedNotifyUsersJob */
        $issuePublishedNotifyUsersJob = unserialize(base64_decode($this->serializedJobData));

        $journalDAOMock = Mockery::mock(\APP\journal\JournalDAO::class)
            ->makePartial()
            ->shouldReceive('getId')
            ->withAnyArgs()
            ->andReturn(new \APP\journal\JournalDAO())
            ->getMock();

        DAORegistry::registerDAO('JournalDAO', $journalDAOMock);

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

        $this->assertNull($issuePublishedNotifyUsersJob->handle());
    }
}

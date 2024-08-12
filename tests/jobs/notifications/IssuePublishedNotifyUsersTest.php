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
    protected string $serializedJobData = <<<END
    O:48:"APP\\jobs\\notifications\\IssuePublishedNotifyUsers":8:{s:15:"\0*\0recipientIds";O:29:"Illuminate\\Support\\Collection":2:{s:8:"\0*\0items";a:18:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;i:6;i:7;i:7;i:8;i:8;i:9;i:9;i:10;i:10;i:11;i:11;i:12;i:12;i:13;i:13;i:14;i:14;i:15;i:15;i:16;i:35;i:37;i:36;i:38;}s:28:"\0*\0escapeWhenCastingToString";b:0;}s:12:"\0*\0contextId";i:1;s:8:"\0*\0issue";O:15:"APP\\issue\\Issue":6:{s:5:"_data";a:21:{s:2:"id";i:2;s:9:"journalId";i:1;s:6:"volume";i:2;s:6:"number";s:1:"1";s:4:"year";i:2015;s:9:"published";i:1;s:13:"datePublished";s:19:"2024-05-23 11:59:46";s:12:"dateNotified";N;s:12:"lastModified";s:19:"2024-05-23 11:59:46";s:12:"accessStatus";i:1;s:14:"openAccessDate";N;s:10:"showVolume";b:1;s:10:"showNumber";b:1;s:8:"showYear";b:1;s:9:"showTitle";b:0;s:13:"styleFileName";N;s:21:"originalStyleFileName";N;s:7:"urlPath";s:0:"";s:5:"doiId";i:5;s:11:"description";a:2:{s:2:"en";s:0:"";s:5:"fr_CA";s:0:"";}s:5:"title";a:2:{s:2:"en";s:0:"";s:5:"fr_CA";s:0:"";}}s:20:"_hasLoadableAdapters";b:0;s:27:"_metadataExtractionAdapters";a:0:{}s:25:"_extractionAdaptersLoaded";b:0;s:26:"_metadataInjectionAdapters";a:0:{}s:24:"_injectionAdaptersLoaded";b:0;}s:9:"\0*\0locale";s:2:"en";s:9:"\0*\0sender";O:13:"PKP\\user\\User":7:{s:5:"_data";a:22:{s:2:"id";i:1;s:8:"userName";s:5:"admin";s:8:"password";s:60:"$2y$10\$uFmYXg8/Ufa0HbskyW57Be22stFGY5qtxJZmTOae3PfDB86V3x7BW";s:5:"email";s:23:"pkpadmin@mailinator.com";s:3:"url";N;s:5:"phone";N;s:14:"mailingAddress";N;s:14:"billingAddress";N;s:7:"country";N;s:7:"locales";a:0:{}s:6:"gossip";N;s:13:"dateLastEmail";N;s:14:"dateRegistered";s:19:"2023-02-28 20:19:07";s:13:"dateValidated";N;s:13:"dateLastLogin";s:19:"2024-05-22 19:05:03";s:18:"mustChangePassword";N;s:7:"authStr";N;s:8:"disabled";b:0;s:14:"disabledReason";N;s:10:"inlineHelp";b:1;s:10:"familyName";a:1:{s:2:"en";s:5:"admin";}s:9:"givenName";a:1:{s:2:"en";s:5:"admin";}}s:20:"_hasLoadableAdapters";b:0;s:27:"_metadataExtractionAdapters";a:0:{}s:25:"_extractionAdaptersLoaded";b:0;s:26:"_metadataInjectionAdapters";a:0:{}s:24:"_injectionAdaptersLoaded";b:0;s:9:"\0*\0_roles";a:0:{}}s:10:"connection";s:8:"database";s:5:"queue";s:5:"queue";s:7:"batchId";s:36:"9c1c1368-8bb4-4179-a350-403decb700ba";}
    END;

    /**
     * Test job is a proper instance
     */
    public function testUnserializationGetProperDepositIssueJobInstance(): void
    {
        $this->assertInstanceOf(
            IssuePublishedNotifyUsers::class,
            unserialize(($this->serializedJobData))
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

        /** @var IssuePublishedNotifyUsers $issuePublishedNotifyUsersJob */
        $issuePublishedNotifyUsersJob = unserialize(($this->serializedJobData));

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

<?php

/**
 * @file tests/classes/publication/PublicationIdentityTest.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationIdentityTest
 *
 * @brief Tests for OJS-specific identity metadata: ISSN and publisher resolver methods,
 *   clearIdentityMetadata(), and stampContextIdentity() including issue-inheritance.
 */

namespace APP\tests\classes\publication;

use APP\issue\Issue;
use APP\issue\Repository as IssueRepository;
use APP\journal\Journal;
use APP\publication\DAO;
use APP\publication\HasContextIdentityMetadata;
use APP\publication\Publication;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PKP\core\Registry;
use PKP\services\PKPSchemaService;
use PKP\site\Site;
use PKP\tests\PKPTestCase;

#[CoversClass(HasContextIdentityMetadata::class)]
#[CoversClass(Publication::class)]
class PublicationIdentityTest extends PKPTestCase
{
    private Publication $publication;
    private Journal|MockInterface $journal;

    protected function getMockedRegistryKeys(): array
    {
        return ['site'];
    }

    protected function getMockedContainerKeys(): array
    {
        return [IssueRepository::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // getLocalizedData() → getLocalePrecedence() accesses the request router and site.
        // Set up a minimal mock site so the locale precedence logic does not hit the DB.
        $mockSite = Mockery::mock(Site::class);
        $mockSite->shouldReceive('getPrimaryLocale')->andReturn('en');
        Registry::set('site', $mockSite);

        $this->mockRequest();

        $this->publication = (new DAO(new PKPSchemaService()))->newDataObject();
        $this->journal = Mockery::mock(Journal::class);
    }

    // --- getOnlineIssn ---

    public function testGetOnlineIssnReturnsStampedValue(): void
    {
        $this->publication->setData('onlineIssn', '8765-4321');
        $this->assertSame('8765-4321', $this->publication->getOnlineIssn($this->journal));
    }

    public function testGetOnlineIssnFallsBackToLiveContext(): void
    {
        $this->journal->shouldReceive('getData')->with('onlineIssn')->once()->andReturn('9999-0000');
        $this->assertSame('9999-0000', $this->publication->getOnlineIssn($this->journal));
    }

    // --- getPrintIssn ---

    public function testGetPrintIssnReturnsStampedValue(): void
    {
        $this->publication->setData('printIssn', '1234-5678');
        $this->assertSame('1234-5678', $this->publication->getPrintIssn($this->journal));
    }

    public function testGetPrintIssnFallsBackToLiveContext(): void
    {
        $this->journal->shouldReceive('getData')->with('printIssn')->once()->andReturn('0001-0001');
        $this->assertSame('0001-0001', $this->publication->getPrintIssn($this->journal));
    }

    // --- getPublisher ---

    public function testGetPublisherReturnsStampedValue(): void
    {
        $this->publication->setData('publisher', 'Stamped Publisher');
        $this->assertSame('Stamped Publisher', $this->publication->getPublisher($this->journal));
    }

    /**
     * The live fallback reads publisherInstitution (not publisher) from the context,
     * because that is the field name on the Journal/Context object.
     */
    public function testGetPublisherFallsBackToPublisherInstitution(): void
    {
        $this->journal->shouldReceive('getData')->with('publisherInstitution')->once()->andReturn('Live Publisher');
        $this->assertSame('Live Publisher', $this->publication->getPublisher($this->journal));
    }

    // --- clearIdentityMetadata ---

    public function testClearIdentityMetadataNullsAllIdentityFields(): void
    {
        $this->publication->setData('contextName', ['en' => 'Some Journal']);
        $this->publication->setData('contextPrimaryLocale', 'en');
        $this->publication->setData('publisherLocation', 'Geneva');
        $this->publication->setData('publisher', 'Test Publisher');
        $this->publication->setData('onlineIssn', '8765-4321');
        $this->publication->setData('printIssn', '1234-5678');

        $this->publication->clearIdentityMetadata();

        $this->assertNull($this->publication->getData('contextName'));
        $this->assertNull($this->publication->getData('contextPrimaryLocale'));
        $this->assertNull($this->publication->getData('publisherLocation'));
        $this->assertNull($this->publication->getData('publisher'));
        $this->assertNull($this->publication->getData('onlineIssn'));
        $this->assertNull($this->publication->getData('printIssn'));
    }

    // Calls stampContextIdentity() directly, mirroring what publish() does internally.

    // --- stampContextIdentity (context path) ---

    public function testStampContextIdentityStampsFromContextWhenNoIssue(): void
    {
        $this->journal->shouldReceive('getName')->andReturn(['en' => 'My Journal']);
        $this->journal->shouldReceive('getPrimaryLocale')->andReturn('en');
        $this->journal->shouldReceive('getData')->with('printIssn')->andReturn('1234-5678');
        $this->journal->shouldReceive('getData')->with('onlineIssn')->andReturn('8765-4321');
        $this->journal->shouldReceive('getData')->with('publisherInstitution')->andReturn('My Publisher');

        // No issueId set, no CSL plugin loaded → stamps entirely from context.
        $this->publication->stampContextIdentity($this->journal);

        $this->assertSame(['en' => 'My Journal'], $this->publication->getData('contextName'));
        $this->assertSame('en', $this->publication->getData('contextPrimaryLocale'));
        $this->assertSame('1234-5678', $this->publication->getData('printIssn'));
        $this->assertSame('8765-4321', $this->publication->getData('onlineIssn'));
        $this->assertSame('My Publisher', $this->publication->getData('publisher'));
        // CSL plugin not loaded → publisherLocation is not stamped.
        $this->assertNull($this->publication->getData('publisherLocation'));
    }

    // --- stampContextIdentity (issue-inheritance path) ---

    /**
     * An article added to a previously-published issue must inherit the issue's stamped identity
     * rather than the current context — this is the core back-issue and ORE use case.
     */
    public function testStampContextIdentityInheritsFromAlreadyPublishedIssue(): void
    {
        $issue = Mockery::mock(Issue::class);
        $issue->shouldReceive('getData')->with('published')->andReturn(true);
        $issue->shouldReceive('getData')->with('contextName')->andReturn(['en' => 'Old Journal Name']);
        $issue->shouldReceive('getData')->with('contextPrimaryLocale')->andReturn('en');
        $issue->shouldReceive('getData')->with('printIssn')->andReturn('1111-2222');
        $issue->shouldReceive('getData')->with('onlineIssn')->andReturn('3333-4444');
        $issue->shouldReceive('getData')->with('publisher')->andReturn('Old Publisher');
        $issue->shouldReceive('getData')->with('publisherLocation')->andReturn('Old City');

        $issueRepo = Mockery::mock(IssueRepository::class);
        $issueRepo->shouldReceive('get')->with(42)->once()->andReturn($issue);
        app()->instance(IssueRepository::class, $issueRepo);

        $this->publication->setIssueId(42);
        // Context should not be consulted — the issue carries everything needed.
        $this->publication->stampContextIdentity($this->journal);

        $this->assertSame(['en' => 'Old Journal Name'], $this->publication->getData('contextName'));
        $this->assertSame('en', $this->publication->getData('contextPrimaryLocale'));
        $this->assertSame('1111-2222', $this->publication->getData('printIssn'));
        $this->assertSame('3333-4444', $this->publication->getData('onlineIssn'));
        $this->assertSame('Old Publisher', $this->publication->getData('publisher'));
        $this->assertSame('Old City', $this->publication->getData('publisherLocation'));
    }
}

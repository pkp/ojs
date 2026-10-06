<?php

/**
 * @file plugins/importexport/pubmed/tests/ArticlePubMedXmlFilterTest.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class ArticlePubMedXmlFilterTest
 *
 * @brief Tests for the journal title written by the PubMed XML export filter.
 */

namespace APP\plugins\importexport\pubmed\tests;

use APP\journal\Journal;
use APP\plugins\importexport\pubmed\filter\ArticlePubMedXmlFilter;
use APP\plugins\importexport\pubmed\PubMedExportPlugin;
use APP\publication\Publication;
use APP\submission\Submission;
use DOMDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PKP\filter\FilterGroup;
use PKP\tests\PKPTestCase;

#[CoversClass(ArticlePubMedXmlFilter::class)]
class ArticlePubMedXmlFilterTest extends PKPTestCase
{
    private const JOURNAL_ID = 1;
    private const JOURNAL_NAME = 'Journal of Public Knowledge';
    private const NLM_TITLE = 'J Pub Knowl';

    /**
     * Provide the stored NLM title abbreviation setting and the journal title expected in the export.
     */
    public static function nlmTitleProvider(): array
    {
        return [
            'setting never saved' => [null, self::JOURNAL_NAME],
            'setting saved empty' => ['', self::JOURNAL_NAME],
            'setting saved with an abbreviation' => [self::NLM_TITLE, self::NLM_TITLE],
        ];
    }

    /**
     * Test that the JournalTitle element uses the NLM title abbreviation when one is set, and the
     * journal's name in its primary locale otherwise.
     */
    #[DataProvider('nlmTitleProvider')]
    public function testJournalTitle(?string $nlmTitle, string $expectedTitle): void
    {
        $doc = new DOMDocument();
        $journalNode = $this->createFilter()->createJournalNode(
            $doc,
            $this->createPluginMock($nlmTitle),
            $this->createJournal(),
            null,
            $this->createSubmissionMock(new Publication())
        );

        $journalTitleNodes = $journalNode->getElementsByTagName('JournalTitle');
        self::assertSame(1, $journalTitleNodes->length);
        self::assertSame($expectedTitle, $journalTitleNodes->item(0)->textContent);
    }

    /**
     * Create the filter under test, in the filter group declared in the plugin's filterConfig.xml.
     */
    private function createFilter(): ArticlePubMedXmlFilter
    {
        $filterGroup = new FilterGroup();
        $filterGroup->setSymbolic('article=>pubmed-xml');
        $filterGroup->setInputType('class::classes.submission.Submission[]');
        $filterGroup->setOutputType('xml::dtd');

        return new ArticlePubMedXmlFilter($filterGroup);
    }

    /**
     * Create a PubMed export plugin that returns the given value for the journal's nlmTitle setting.
     */
    private function createPluginMock(?string $nlmTitle): PubMedExportPlugin
    {
        /** @var PubMedExportPlugin|MockObject $plugin */
        $plugin = $this->getMockBuilder(PubMedExportPlugin::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSetting'])
            ->getMock();
        $plugin->expects($this->once())
            ->method('getSetting')
            ->with(self::JOURNAL_ID, 'nlmTitle')
            ->willReturn($nlmTitle);

        return $plugin;
    }

    /**
     * Create a journal with a name in its primary locale and another locale.
     */
    private function createJournal(): Journal
    {
        $journal = new Journal();
        $journal->setId(self::JOURNAL_ID);
        $journal->setPrimaryLocale('en');
        $journal->setName(self::JOURNAL_NAME, 'en');
        $journal->setName('Revue de la connaissance publique', 'fr_CA');
        $journal->setData('publisherInstitution', 'Public Knowledge Project');

        return $journal;
    }

    /**
     * Create a submission whose current publication is the given publication.
     */
    private function createSubmissionMock(Publication $publication): Submission
    {
        /** @var Submission|MockObject $submission */
        $submission = $this->getMockBuilder(Submission::class)
            ->onlyMethods(['getCurrentPublication'])
            ->getMock();
        $submission->method('getCurrentPublication')->willReturn($publication);

        return $submission;
    }
}

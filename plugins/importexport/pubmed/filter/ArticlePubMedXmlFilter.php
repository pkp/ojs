<?php

/**
 * @file plugins/importexport/pubmed/filter/ArticlePubMedXmlFilter.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2000-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticlePubMedXmlFilter
 *
 * @brief Class that converts a Article to a PubMed XML document.
 */

namespace APP\plugins\importexport\pubmed\filter;

use APP\author\Author;
use APP\decision\Decision;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\journal\Journal;
use APP\journal\JournalDAO;
use APP\submission\Submission;
use PKP\core\PKPString;
use PKP\db\DAORegistry;
use PKP\filter\PersistableFilter;
use PKP\i18n\LocaleConversion;

class ArticlePubMedXmlFilter extends PersistableFilter
{
    //
    // Implement abstract methods from SubmissionPubMedXmlFilter
    //
    /**
     * Get the representation export filter group name
     *
     * @return string
     */
    public function getRepresentationExportFilterGroupName()
    {
        return 'article-galley=>pubmed-xml';
    }

    //
    // Implement template methods from Filter
    //
    /**
     * @see Filter::process()
     *
     * @param array $submissions Array of submissions
     *
     * @return \DOMDocument
     */
    public function &process(&$submissions)
    {
        // Create the XML document
        $implementation = new \DOMImplementation();
        $dtd = $implementation->createDocumentType('ArticleSet', '-//NLM//DTD PubMed 2.0//EN', 'http://www.ncbi.nlm.nih.gov/entrez/query/static/PubMed.dtd');
        $doc = $implementation->createDocument('', '', $dtd);
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;

        $journalDao = DAORegistry::getDAO('JournalDAO'); /** @var JournalDAO $journalDao */
        $journal = null;

        $rootNode = $doc->createElement('ArticleSet');
        foreach ($submissions as $submission) {
            // Fetch associated objects
            if ($journal?->getId() !== $submission->getData('contextId')) {
                $journal = $journalDao->getById($submission->getData('contextId'));
            }
            $issue = Repo::issue()->getBySubmissionId($submission->getId());
            $issue = $issue?->getJournalId() === $journal->getId() ? $issue : null;

            $articleNode = $doc->createElement('Article');
            $articleNode->appendChild($this->createJournalNode($doc, $journal, $issue, $submission));

            $publication = $submission->getCurrentPublication();

            $locale = $publication->getData('locale');
            if ($locale == 'en') {
                $articleNode->appendChild($doc->createElement('ArticleTitle'))->appendChild($doc->createTextNode($publication->getLocalizedTitle($locale, 'html')));
            } else {
                $articleNode->appendChild($doc->createElement('VernacularTitle'))->appendChild($doc->createTextNode($publication->getLocalizedTitle($locale, 'html')));
            }

            $startPage = $publication->getStartingPage();
            $endPage = $publication->getEndingPage();
            if (isset($startPage) && $startPage !== '') {
                // We have a page range or e-location id
                $articleNode->appendChild($doc->createElement('FirstPage'))->appendChild($doc->createTextNode($startPage));
                $articleNode->appendChild($doc->createElement('LastPage'))->appendChild($doc->createTextNode($endPage));
            }

            if ($doi = $publication->getStoredPubId('doi')) {
                $doiNode = $doc->createElement('ELocationID');
                $doiNode->appendChild($doc->createTextNode($doi));
                $doiNode->setAttribute('EIdType', 'doi');
                $articleNode->appendChild($doiNode);
            }

            $articleNode->appendChild($doc->createElement('Language'))->appendChild($doc->createTextNode(LocaleConversion::get3LetterIsoFromLocale($locale)));

            $authorListNode = $doc->createElement('AuthorList');
            foreach ($publication->getData('authors') ?? [] as $author) {
                $authorListNode->appendChild($this->generateAuthorNode($doc, $journal, $issue, $submission, $author));
            }
            $articleNode->appendChild($authorListNode);

            if ($publication->getStoredPubId('publisher-id')) {
                $articleIdListNode = $doc->createElement('ArticleIdList');
                $articleIdNode = $doc->createElement('ArticleId');
                $articleIdNode->appendChild($doc->createTextNode($publication->getStoredPubId('publisher-id')));
                $articleIdNode->setAttribute('IdType', 'pii');
                $articleIdListNode->appendChild($articleIdNode);
                $articleNode->appendChild($articleIdListNode);
            }

            // History
            $historyNode = $doc->createElement('History');
            $historyNode->appendChild($this->generatePubDateDom($doc, $submission->getData('dateSubmitted'), 'received'));

            $editorDecision = Repo::decision()->getCollector()
                ->filterBySubmissionIds([$submission->getId()])
                ->getMany()
                ->first(fn (Decision $decision, $key) => $decision->getData('decision') === Decision::ACCEPT);

            if ($editorDecision) {
                $historyNode->appendChild($this->generatePubDateDom($doc, $editorDecision->getData('dateDecided'), 'accepted'));
            }
            $articleNode->appendChild($historyNode);

            // FIXME: Revision dates

            if ($abstract = PKPString::html2text($publication->getLocalizedData('abstract', $locale))) {
                $articleNode->appendChild($doc->createElement('Abstract'))->appendChild($doc->createTextNode($abstract));
            }

            $rootNode->appendChild($articleNode);
        }
        $doc->appendChild($rootNode);
        return $doc;
    }

    /**
     * Construct and return a Journal element.
     *
     * @param \DOMDocument $doc
     * @param Journal $journal
     * @param Issue $issue
     * @param Submission $submission
     */
    public function createJournalNode($doc, $journal, $issue, $submission)
    {
        $journalNode = $doc->createElement('Journal');

        $publisherNameNode = $doc->createElement('PublisherName');
        $publisherNameNode->appendChild($doc->createTextNode($journal->getData('publisherInstitution')));
        $journalNode->appendChild($publisherNameNode);

        $journalTitleNode = $doc->createElement('JournalTitle');
        $journalTitleNode->appendChild($doc->createTextNode($journal->getName($journal->getPrimaryLocale())));
        $journalNode->appendChild($journalTitleNode);

        // check various ISSN fields to create the ISSN tag
        if ($journal->getData('printIssn') != '') {
            $issn = $journal->getData('printIssn');
        } elseif ($journal->getData('issn') != '') {
            $issn = $journal->getData('issn');
        } elseif ($journal->getData('onlineIssn') != '') {
            $issn = $journal->getData('onlineIssn');
        } else {
            $issn = '';
        }
        if ($issn != '') {
            $journalNode->appendChild($doc->createElement('Issn', $issn));
        }

        if ($issue && $issue->getShowVolume()) {
            $journalNode->appendChild($doc->createElement('Volume'))->appendChild($doc->createTextNode($issue->getVolume()));
        }
        if ($issue && $issue->getShowNumber()) {
            $journalNode->appendChild($doc->createElement('Issue'))->appendChild($doc->createTextNode($issue->getNumber()));
        }

        $datePublished = $submission->getCurrentPublication()?->getData('datePublished')
            ?: $issue?->getDatePublished();
        if ($datePublished) {
            $journalNode->appendChild($this->generatePubDateDom($doc, $datePublished, 'epublish'));
        }

        return $journalNode;
    }

    /**
     * Generate and return an author node representing the supplied author.
     *
     * @param \DOMDocument $doc
     * @param Journal $journal
     * @param Issue $issue
     * @param Submission $submission
     * @param Author $author
     *
     * @return \DOMElement
     */
    public function generateAuthorNode($doc, $journal, $issue, $submission, $author)
    {
        $authorElement = $doc->createElement('Author');

        if (empty($author->getLocalizedFamilyName())) {
            $authorElement->appendChild($node = $doc->createElement('FirstName'));
            $node->setAttribute('EmptyYN', 'Y');
            $authorElement->appendChild($doc->createElement('LastName'))->appendChild($doc->createTextNode(ucfirst($author->getLocalizedGivenName())));
        } else {
            $authorElement->appendChild($doc->createElement('FirstName'))->appendChild($doc->createTextNode(ucfirst($author->getLocalizedGivenName())));
            $authorElement->appendChild($doc->createElement('LastName'))->appendChild($doc->createTextNode(ucfirst($author->getLocalizedFamilyName())));
        }
        foreach ($author->getAffiliations() as $affiliation) {
            $affiliationInfoElement = $doc->createElement('AffiliationInfo');
            $affiliationInfoElement->appendChild($doc->createElement('Affiliation'))->appendChild($doc->createTextNode($affiliation->getLocalizedName()));
            if ($affiliation->getRor()) {
                $affiliationInfoElement->appendChild($identifierNode = $doc->createElement('Identifier'))->appendChild($doc->createTextNode($affiliation->getRor()));
                $identifierNode->setAttribute('Source', 'ROR');
            }
            $authorElement->appendChild($affiliationInfoElement);
        }
        if ($author->getData('orcid') && $author->getData('orcidIsVerified')) {
            // We're storing the ORCID with a URL (http://orcid.org/{$ID}), but the XML expects just the ID
            $orcidId = explode('/', trim($author->getData('orcid') ?? '', '/'));
            $orcidId = array_pop($orcidId);
            if ($orcidId) {
                $orcidNode = $authorElement->appendChild($doc->createElement('Identifier'));
                $orcidNode->setAttribute('Source', 'ORCID');
                $orcidNode->appendChild($doc->createTextNode($orcidId));
            }
        }

        return $authorElement;
    }

    /**
     * Generate and return a date element per the PubMed standard.
     *
     * @param \DOMDocument $doc
     * @param string $pubDate
     * @param string $pubStatus
     *
     * @return \DOMElement
     */
    public function generatePubDateDom($doc, $pubDate, $pubStatus)
    {
        $pubDateNode = $doc->createElement('PubDate');
        $pubDateNode->setAttribute('PubStatus', $pubStatus);

        $pubDateNode->appendChild($doc->createElement('Year', date('Y', strtotime($pubDate))));
        $pubDateNode->appendChild($doc->createElement('Month', date('m', strtotime($pubDate))));
        $pubDateNode->appendChild($doc->createElement('Day', date('d', strtotime($pubDate))));

        return $pubDateNode;
    }
}

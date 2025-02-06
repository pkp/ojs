<?php

/**
 * @file plugins/oaiMetadataFormats/rfc1807/OAIMetadataFormat_RFC1807.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_RFC1807
 *
 * @see OAI
 *
 * @brief OAI metadata format class -- RFC 1807.
 */

namespace APP\plugins\oaiMetadataFormats\rfc1807;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\issue\IssueAction;
use APP\journal\Journal;
use APP\publication\Publication;
use APP\submission\Submission;
use PKP\controlledVocab\ControlledVocab;
use PKP\oai\OAIMetadataFormat;
use PKP\oai\OAIUtils;

class OAIMetadataFormat_RFC1807 extends OAIMetadataFormat
{
    /**
     * @see OAIMetadataFormat#toXml
     *
     * @param null|mixed $format
     */
    public function toXml($record, $format = null)
    {
        /** @var Submission $article */
        $article = &$record->getData('article');

        /** @var Journal $journal */
        $journal = &$record->getData('journal');

        /* @var Section $section */
        $section = &$record->getData('section');

        /** @var Issue $issue */
        $issue = &$record->getData('issue');

        /** @var Publication $publication */
        $publication = $article->getCurrentPublication();

        $publisher = $journal->getLocalizedName(); // Default
        $publisherInstitution = $journal->getData('publisherInstitution');
        if (!empty($publisherInstitution)) {
            $publisher = $publisherInstitution;
        }

        // Sources contains journal title, issue ID, and pages
        $source = $issue->getIssueIdentification();
        $pages = $publication->getData('pages');
        if (!empty($pages)) {
            $source .= '; ' . $pages;
        }

        // Format creators
        $creators = [];
        foreach ($publication->getData('authors') as $author) {
            $creators[] = $author->getFullName(false, true);
        }

        $subjects = array_merge_recursive(
            Repo::controlledVocab()->getBySymbolic(
                ControlledVocab::CONTROLLED_VOCAB_SUBMISSION_KEYWORD,
                Application::ASSOC_TYPE_PUBLICATION,
                $publication->getId()
            ),
            Repo::controlledVocab()->getBySymbolic(
                ControlledVocab::CONTROLLED_VOCAB_SUBMISSION_SUBJECT,
                Application::ASSOC_TYPE_PUBLICATION,
                $article->getCurrentPublication()->getId()
            )
        );
        $subject = $subjects[$journal->getPrimaryLocale()] ?? '';

        $coverage = $publication->getData('coverage', $publication->getData('locale'));

        $issueAction = new IssueAction();
        $request = Application::get()->getRequest();
        $url = $request->getDispatcher()->url(
            $request,
            Application::ROUTE_PAGE,
            $journal->getPath(),
            'article',
            'view',
            [$article->getBestId()],
            urlLocaleForPage: ''
        );
        $includeUrls = $journal->getData('publishingMode') != Journal::PUBLISHING_MODE_NONE || $issueAction->subscribedUser($request->getUser(), $journal, null, $article->getId());
        return "<rfc1807\n" .
            "\txmlns=\"http://info.internet.isi.edu:80/in-notes/rfc/files/rfc1807.txt\"\n" .
            "\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
            "\txsi:schemaLocation=\"http://info.internet.isi.edu:80/in-notes/rfc/files/rfc1807.txt\n" .
            "\thttp://www.openarchives.org/OAI/1.1/rfc1807.xsd\">\n" .
            "\t<bib-version>v2</bib-version>\n" .
            $this->formatElement('id', $url) .
            $this->formatElement('entry', $record->datestamp) .
            $this->formatElement('organization', $publisher) .
            $this->formatElement('organization', $source) .
            $this->formatElement('title', $publication->getLocalizedTitle()) .
            $this->formatElement('type', $section->getLocalizedIdentifyType()) .
            $this->formatElement('author', $creators) .
            ($publication->getData('datePublished') ? $this->formatElement('date', $publication->getData('datePublished')) : '') .
            $this->formatElement('copyright', strip_tags($journal->getLocalizedData('licenseTerms'))) .
            ($includeUrls ? $this->formatElement('other_access', "url:{$url}") : '') .
            $this->formatElement('keyword', $subject) .
            $this->formatElement('period', $coverage) .
            $this->formatElement('monitoring', $publication->getLocalizedData('sponsor')) .
            $this->formatElement('language', $publication->getData('locale')) .
            $this->formatElement('abstract', strip_tags($publication->getLocalizedData('abstract'))) .
            "</rfc1807>\n";
    }

    /**
     * Format XML for single RFC 1807 element.
     *
     * @param string $name
     */
    public function formatElement($name, $value)
    {
        $response = '';
        foreach ((array) $value as $v) {
            $response .= "\t<{$name}>" . OAIUtils::prepOutput($v) . "</{$name}>\n";
        }
        return $response;
    }
}

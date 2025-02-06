<?php

/**
 * @file plugins/importexport/doaj/filter/DOAJXmlFilter.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2000-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOAJXmlFilter
 *
 * @brief Class that converts an Article to a DOAJ XML document.
 */

namespace APP\plugins\importexport\doaj\filter;

use APP\core\Application;
use APP\facades\Repo;
use APP\plugins\importexport\doaj\DOAJExportDeployment;
use APP\plugins\importexport\doaj\DOAJExportPlugin;
use APP\publication\Publication;
use APP\submission\Submission;
use PKP\controlledVocab\ControlledVocab;
use PKP\core\PKPString;
use PKP\i18n\LocaleConversion;

class DOAJXmlFilter extends \PKP\plugins\importexport\native\filter\NativeExportFilter
{
    /**
     * Constructor
     *
     * @param \PKP\filter\FilterGroup $filterGroup
     */
    public function __construct($filterGroup)
    {
        $this->setDisplayName('DOAJ XML export');
        parent::__construct($filterGroup);
    }

    //
    // Implement template methods from Filter
    //
    /**
     * @see Filter::process()
     *
     * @param array $pubObjects Array of Submissions
     *
     * @return \DOMDocument
     */
    public function &process(&$pubObjects)
    {
        // Create the XML document
        $doc = new \DOMDocument('1.0', 'utf-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        /** @var DOAJExportDeployment */
        $deployment = $this->getDeployment();
        $context = $deployment->getContext();
        /** @var DOAJExportPlugin */
        $plugin = $deployment->getPlugin();
        $cache = $plugin->getCache();

        // Create the root node
        $rootNode = $this->createRootNode($doc);
        $doc->appendChild($rootNode);

        foreach ($pubObjects as $pubObject) { /** @var Submission $pubObject */
            $publication = $pubObject->getCurrentPublication();
            $issueId = $publication->getData('issueId');
            if ($cache->isCached('issues', $issueId)) {
                $issue = $cache->get('issues', $issueId);
            } else {
                $issue = Repo::issue()->get($issueId);
                $issue = $issue->getJournalId() == $context->getId() ? $issue : null;
                if ($issue) {
                    $cache->add($issue, null);
                }
            }

            // Record
            $recordNode = $doc->createElement('record');
            $rootNode->appendChild($recordNode);
            // Language
            $language = LocaleConversion::get3LetterIsoFromLocale($publication->getData('locale'));
            if (!empty($language)) {
                $recordNode->appendChild($node = $doc->createElement('language', $language));
            }
            // Publisher name (i.e. institution name)
            $publisher = $context->getData('publisherInstitution');
            if (!empty($publisher)) {
                $recordNode->appendChild($node = $doc->createElement('publisher', htmlspecialchars($publisher, ENT_COMPAT, 'UTF-8')));
            }
            // Journal's title (M)
            $journalTitle = $context->getName($context->getPrimaryLocale());
            $recordNode->appendChild($node = $doc->createElement('journalTitle', htmlspecialchars($journalTitle, ENT_COMPAT, 'UTF-8')));
            // Identification Numbers
            $issn = $context->getData('printIssn');
            if (!empty($issn)) {
                $recordNode->appendChild($node = $doc->createElement('issn', $issn));
            }
            $eissn = $context->getData('onlineIssn');
            if (!empty($eissn)) {
                $recordNode->appendChild($node = $doc->createElement('eissn', $eissn));
            }
            // Article's publication date, volume, issue
            if ($publication->getData('datePublished')) {
                $recordNode->appendChild($node = $doc->createElement('publicationDate', $this->formatDate($publication->getData('datePublished'))));
            } else {
                $recordNode->appendChild($node = $doc->createElement('publicationDate', $this->formatDate($issue->getDatePublished())));
            }
            $volume = $issue->getVolume();
            if (!empty($volume) && $issue->getShowVolume()) {
                $recordNode->appendChild($node = $doc->createElement('volume', htmlspecialchars($volume, ENT_COMPAT, 'UTF-8')));
            }
            $issueNumber = $issue->getNumber();
            if (!empty($issueNumber) && $issue->getShowNumber()) {
                $recordNode->appendChild($node = $doc->createElement('issue', htmlspecialchars($issueNumber, ENT_COMPAT, 'UTF-8')));
            }
            /** --- FirstPage / LastPage (from PubMed plugin)---
             * there is some ambiguity for online journals as to what
             * "page numbers" are; for example, some journals (eg. JMIR)
             * use the "e-location ID" as the "page numbers" in PubMed
             */
            $startPage = $publication->getStartingPage();
            $endPage = $publication->getEndingPage();
            if (isset($startPage) && $startPage !== '') {
                $recordNode->appendChild($node = $doc->createElement('startPage', htmlspecialchars($startPage, ENT_COMPAT, 'UTF-8')));
                $recordNode->appendChild($node = $doc->createElement('endPage', htmlspecialchars($endPage, ENT_COMPAT, 'UTF-8')));
            }
            // DOI
            $doi = $publication->getStoredPubId('doi');
            if (!empty($doi)) {
                $recordNode->appendChild($node = $doc->createElement('doi', htmlspecialchars($doi, ENT_COMPAT, 'UTF-8')));
            }
            // publisherRecordId
            $recordNode->appendChild($node = $doc->createElement('publisherRecordId', htmlspecialchars($publication->getId(), ENT_COMPAT, 'UTF-8')));
            // documentType
            $type = $publication->getLocalizedData('type', $publication->getData('locale'));
            if (!empty($type)) {
                $recordNode->appendChild($node = $doc->createElement('documentType', htmlspecialchars($type, ENT_COMPAT, 'UTF-8')));
            }
            // Article title
            $articleTitles = $publication->getFullTitles();
            if (array_key_exists($publication->getData('locale'), $articleTitles)) {
                $titleInArticleLocale = $articleTitles[$publication->getData('locale')];
                unset($articleTitles[$publication->getData('locale')]);
                $articleTitles = array_merge([$publication->getData('locale') => $titleInArticleLocale], $articleTitles);
            }
            foreach ($articleTitles as $locale => $title) {
                if (!empty($title)) {
                    $recordNode->appendChild($node = $doc->createElement('title', htmlspecialchars($title, ENT_COMPAT, 'UTF-8')));
                    $node->setAttribute('language', LocaleConversion::get3LetterIsoFromLocale($locale));
                }
            }
            // Authors and affiliations
            $authors = $publication->getData('authors');
            if (!empty($authors)) {
                $authorsNode = $doc->createElement('authors');
                $recordNode->appendChild($authorsNode);
                $affilList = $this->createAffiliationsList($authors, $publication);
                foreach ($authors as $author) {
                    $authorsNode->appendChild($this->createAuthorNode($doc, $publication, $author, $affilList));
                }
            }

            if (!empty($affilList[0])) {
                $affilsNode = $doc->createElement('affiliationsList');
                $recordNode->appendChild($affilsNode);
                for ($i = 0; $i < count($affilList); $i++) {
                    $affilsNode->appendChild($node = $doc->createElement('affiliationName', htmlspecialchars($affilList[$i], ENT_COMPAT, 'UTF-8')));
                    $node->setAttribute('affiliationId', $i);
                }
            }
            // Abstract
            $articleAbstracts = $publication->getData('abstract') ?: [];
            if (array_key_exists($publication->getData('locale'), $articleAbstracts)) {
                $abstractInArticleLocale = $articleAbstracts[$publication->getData('locale')];
                unset($articleAbstracts[$publication->getData('locale')]);
                $articleAbstracts = array_merge([$publication->getData('locale') => $abstractInArticleLocale], $articleAbstracts);
            }
            foreach ($articleAbstracts as $locale => $abstract) {
                if (!empty($abstract)) {
                    $recordNode->appendChild($node = $doc->createElement('abstract', htmlspecialchars(PKPString::html2text($abstract), ENT_COMPAT, 'UTF-8')));
                    $node->setAttribute('language', LocaleConversion::get3LetterIsoFromLocale($locale));
                }
            }
            // FullText URL
            $request = Application::get()->getRequest();
            $recordNode->appendChild($node = $doc->createElement('fullTextUrl', htmlspecialchars($request->getDispatcher()->url($request, Application::ROUTE_PAGE, null, 'article', 'view', [$pubObject->getId()], urlLocaleForPage: ''), ENT_COMPAT, 'UTF-8')));
            $node->setAttribute('format', 'html');

            // Keywords
            $articleKeywords = Repo::controlledVocab()->getBySymbolic(
                ControlledVocab::CONTROLLED_VOCAB_SUBMISSION_KEYWORD,
                Application::ASSOC_TYPE_PUBLICATION,
                $publication->getId()
            );

            if (array_key_exists($publication->getData('locale'), $articleKeywords)) {
                $keywordsInArticleLocale = $articleKeywords[$publication->getData('locale')];
                unset($articleKeywords[$publication->getData('locale')]);
                $articleKeywords = array_merge([$publication->getData('locale') => $keywordsInArticleLocale], $articleKeywords);
            }

            foreach ($articleKeywords as $locale => $keywords) {
                $keywordsNode = $doc->createElement('keywords');
                $keywordsNode->setAttribute('language', LocaleConversion::get3LetterIsoFromLocale($locale));
                $recordNode->appendChild($keywordsNode);
                foreach ($keywords as $keyword) {
                    if (!empty($keyword)) {
                        $keywordsNode->appendChild($node = $doc->createElement('keyword', htmlspecialchars($keyword, ENT_COMPAT, 'UTF-8')));
                    }
                }
            }
        }
        return $doc;
    }

    /**
     * Create and return the root node.
     *
     * @param \DOMDocument $doc
     *
     * @return \DOMElement
     */
    public function createRootNode($doc)
    {
        /** @var DOAJExportDeployment */
        $deployment = $this->getDeployment();
        $rootNode = $doc->createElement($deployment->getRootElementName());
        $rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $rootNode->setAttribute('xsi:noNamespaceSchemaLocation', $deployment->getXmlSchemaLocation());
        return $rootNode;
    }

    /**
     * Generate the author node.
     *
     * @param \DOMDocument $doc
     * @param object $publication Article
     * @param object $author Author
     * @param array $affilList List of author affiliations
     *
     * @return \DOMElement
     */
    public function createAuthorNode($doc, $publication, $author, $affilList)
    {
        $deployment = $this->getDeployment();
        $authorNode = $doc->createElement('author');
        $authorNode->appendChild($node = $doc->createElement('name', htmlspecialchars($author->getFullName(false, false, $publication->getData('locale')), ENT_COMPAT, 'UTF-8')));
        $affiliations = $author->getLocalizedAffiliationNames($publication->getData('locale'));
        foreach ($affiliations as $affiliation) {
            $authorNode->appendChild(
                $doc->createElement(
                    'affiliationId',
                    htmlspecialchars(current(array_keys($affilList, $affiliation)), ENT_COMPAT, 'UTF-8')
                )
            );
        }
        if ($author->getData('orcid') && $author->getData('orcidIsVerified')) {
            $authorNode->appendChild($doc->createElement('orcid_id'))->appendChild($doc->createTextNode($author->getData('orcid')));
        }
        return $authorNode;
    }

    /**
     * Generate a list of affiliations among all authors of an article.
     *
     * @param object $authors Array of article authors
     * @param Publication $publication
     *
     * @return array
     */
    public function createAffiliationsList($authors, $publication)
    {
        $affilList = [];
        foreach ($authors as $author) {
            $affiliations = $author->getLocalizedAffiliationNames($publication->getData('locale'));
            foreach ($affiliations as $affiliation) {
                if (!in_array($affiliation, $affilList)) {
                    $affilList[] = $affiliation;
                    ;
                }
            }
        }
        return $affilList;
    }

    /**
     * Format a date by Y-m-d format.
     *
     * @param string $date
     *
     * @return string
     */
    public function formatDate($date)
    {
        if ($date == '') {
            return null;
        }
        return date('Y-m-d', strtotime($date));
    }
}

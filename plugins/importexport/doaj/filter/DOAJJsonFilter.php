<?php

/**
 * @file plugins/importexport/doaj/filter/DOAJJsonFilter.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOAJJsonFilter
 *
 * @ingroup plugins_importexport_doaj
 *
 * @brief Class that converts an Article to a DOAJ JSON string.
 */

namespace APP\plugins\importexport\doaj\filter;

use APP\core\Application;
use APP\facades\Repo;
use APP\plugins\importexport\doaj\DOAJExportDeployment;
use APP\plugins\importexport\doaj\DOAJExportPlugin;
use PKP\core\PKPString;
use PKP\db\DAORegistry;
use PKP\plugins\importexport\PKPImportExportFilter;
use PKP\submission\SubmissionKeywordDAO;

class DOAJJsonFilter extends PKPImportExportFilter
{
    /**
     * Constructor
     *
     * @param \PKP\filter\FilterGroup $filterGroup
     */
    public function __construct($filterGroup)
    {
        $this->setDisplayName('DOAJ JSON export');
        parent::__construct($filterGroup);
    }

    //
    // Implement template methods from Filter
    //
    /**
     * @see Filter::process()
     *
     * @param \APP\submission\Submission $pubObject
     *
     * @return string JSON
     */
    public function &process(&$pubObject)
    {
        /** @var DOAJExportDeployment */
        $deployment = $this->getDeployment();
        $context = $deployment->getContext();
        /** @var DOAJExportPlugin */
        $plugin = $deployment->getPlugin();
        $cache = $plugin->getCache();

        // Create the JSON string
        // Article JSON example bibJson https://github.com/DOAJ/harvester/blob/9b59fddf2d01f7c918429d33b63ca0f1a6d3d0d0/service/tests/fixtures/article.py
        // S. also https://doaj.github.io/doaj-docs/master/data_models/IncomingAPIArticle

        $publication = $pubObject->getCurrentPublication();
        $publicationLocale = $publication->getData('locale');

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

        $article = [];
        $article['bibjson']['journal'] = [];
        // Publisher name (i.e. institution name)
        $publisher = $context->getData('publisherInstitution');
        if (!empty($publisher)) {
            $article['bibjson']['journal']['publisher'] = $publisher;
        }
        // To-Do: license ???
        // Journal's title (M)
        $journalTitle = $context->getName($context->getPrimaryLocale());
        $article['bibjson']['journal']['title'] = $journalTitle;
        // Identification Numbers
        $issns = [];
        $pissn = $context->getData('printIssn');
        if (!empty($pissn)) {
            $issns[] = $pissn;
        }
        $eissn = $context->getData('onlineIssn');
        if (!empty($eissn)) {
            $issns[] = $eissn;
        }
        if (!empty($issns)) {
            $article['bibjson']['journal']['issns'] = $issns;
        }
        // Volume, Number
        $volume = $issue->getVolume();
        if (!empty($volume)) {
            $article['bibjson']['journal']['volume'] = $volume;
        }
        $issueNumber = $issue->getNumber();
        if (!empty($issueNumber)) {
            $article['bibjson']['journal']['number'] = $issueNumber;
        }

        // Article title
        $article['bibjson']['title'] = $publication?->getLocalizedTitle($publicationLocale) ?? '';
        // Identifiers
        $article['bibjson']['identifier'] = [];
        // DOI
        $doi = $publication->getDoi();
        if (!empty($doi)) {
            $article['bibjson']['identifier'][] = ['type' => 'doi', 'id' => $doi];
        }
        // Print and online ISSN
        if (!empty($pissn)) {
            $article['bibjson']['identifier'][] = ['type' => 'pissn', 'id' => $pissn];
        }
        if (!empty($eissn)) {
            $article['bibjson']['identifier'][] = ['type' => 'eissn', 'id' => $eissn];
        }
        // Year and month from article's publication date
        $publicationDate = $this->formatDate($issue->getDatePublished());
        if ($publication->getData('datePublished')) {
            $publicationDate = $this->formatDate($publication->getData('datePublished'));
        }
        $yearMonth = explode('-', $publicationDate);
        $article['bibjson']['year'] = $yearMonth[0];
        $article['bibjson']['month'] = $yearMonth[1];
        /** --- FirstPage / LastPage (from PubMed plugin)---
         * there is some ambiguity for online journals as to what
         * "page numbers" are; for example, some journals (eg. JMIR)
         * use the "e-location ID" as the "page numbers" in PubMed
         */
        $startPage = $publication->getStartingPage();
        $endPage = $publication->getEndingPage();
        if (isset($startPage) && $startPage !== '') {
            $article['bibjson']['start_page'] = $startPage;
            $article['bibjson']['end_page'] = $endPage;
        }
        // FullText URL
        $request = Application::get()->getRequest();
        $article['bibjson']['link'] = [];
        $article['bibjson']['link'][] = [
            'url' => $request->url($context->getPath(), 'article', 'view', $pubObject->getId()),
            'type' => 'fulltext',
            'content_type' => 'html'
        ];
        // Authors: name, affiliation and ORCID
        $articleAuthors = $publication->getData('authors');
        if ($articleAuthors->isNotEmpty()) {
            $article['bibjson']['author'] = [];

            foreach ($articleAuthors as $articleAuthor) {
                $author = ['name' => $articleAuthor->getFullName(false, false, $publicationLocale)];
                $affiliation = $articleAuthor->getAffiliation($publicationLocale);
                if (!empty($affiliation)) {
                    $author['affiliation'] = $affiliation;
                }
                if ($orcid = $articleAuthor->getData('orcid')) {
                    $author['orcid_id'] = $orcid;
                }
                $article['bibjson']['author'][] = $author;
            }
        }

        // Abstract
        $abstract = $publication->getData('abstract', $publicationLocale);
        if (!empty($abstract)) {
            $article['bibjson']['abstract'] = PKPString::html2text($abstract);
        }
        // Keywords
        /** @var SubmissionKeywordDAO */
        $dao = DAORegistry::getDAO('SubmissionKeywordDAO');
        $keywords = $dao->getKeywords($publication->getId(), [$publicationLocale]);
        $allowedNoOfKeywords = array_slice($keywords[$publicationLocale] ?? [], 0, 6);
        if (!empty($keywords[$publicationLocale])) {
            $article['bibjson']['keywords'] = $allowedNoOfKeywords;
        }

        $json = json_encode($article);
        return $json;
    }

    /**
     * Format a date by Y-F format.
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
        return date('Y-F', strtotime($date));
    }
}

<?php

/**
 * @file pages/sitemap/SitemapHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SitemapHandler
 * @ingroup pages_sitemap
 *
 * @brief Produce a sitemap in XML format for submitting to search engines.
 */

import('lib.pkp.pages.sitemap.PKPSitemapHandler');

use APP\facades\Repo;
use APP\issue\Collector;
use APP\submission\Submission;

class SitemapHandler extends PKPSitemapHandler
{
    /**
     * @copydoc PKPSitemapHandler_createContextSitemap()
     */
    public function _createContextSitemap($request)
    {
        $doc = parent::_createContextSitemap($request);
        $root = $doc->documentElement;

        $journal = $request->getJournal();
        $journalId = $journal->getId();

        // Search
        $root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'search')));
        // Issues
        if ($journal->getData('publishingMode') != \APP\journal\Journal::PUBLISHING_MODE_NONE) {
            $root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'issue', 'current')));
            $root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'issue', 'archive')));
            $publishedIssuesCollector = Repo::issue()->getCollector()
                ->filterByContextIds([$journalId])
                ->filterByPublished(true)
                ->orderBy(Collector::ORDERBY_PUBLISHED_ISSUES);
            $publishedIssues = Repo::issue()->getMany($publishedIssuesCollector);
            foreach ($publishedIssues as $issue) {
                $root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'issue', 'view', $issue->getId())));
                // Articles for issue
                $submissions = Repo::submission()->getMany(
                    Repo::submission()
                        ->getCollector()
                        ->filterByContextIds([$journal->getId()])
                        ->filterByIssueIds([$issue->getId()])
                        ->filterByStatus([Submission::STATUS_PUBLISHED])
                );
                foreach ($submissions as $submission) {
                    // Abstract
                    $root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'article', 'view', [$submission->getBestId()])));
                    // Galley files



                    $galleys = Repo::galley()->getMany(
                        Repo::galley()
                            ->getCollector()
                            ->filterByPublicationIds([($submission->getCurrentPublication()->getId())])
                    );
                    foreach ($galleys as $galley) {
                        $root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'article', 'view', [$submission->getBestId(), $galley->getBestGalleyId()])));
                    }
                }
            }
        }

        $doc->appendChild($root);

        // Enable plugins to change the sitemap
        HookRegistry::call('SitemapHandler::createJournalSitemap', [&$doc]);

        return $doc;
    }
}

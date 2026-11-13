<?php

/**
 * @file pages/sitemap/SitemapHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SitemapHandler
 *
 * @ingroup pages_sitemap
 *
 * @brief Produce a sitemap in XML format for submitting to search engines.
 */

namespace APP\pages\sitemap;

use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\issue\Collector;
use APP\submission\Submission;
use DOMDocument;
use Illuminate\Support\Facades\DB;
use PKP\pages\sitemap\PKPSitemapHandler;
use PKP\plugins\Hook;

class SitemapHandler extends PKPSitemapHandler
{
    /** Number of submissions whose URLs are read with one set of queries */
    public const SUBMISSIONS_PER_BATCH = 1000;

    /**
     * @copydoc PKPSitemapHandler_createContextSitemap()
     *
     * @hook SitemapHandler::createJournalSitemap [[&$doc]]
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
            $publishedIssues = Repo::issue()->getCollector()
                ->filterByContextIds([$journalId])
                ->filterByPublished(true)
                ->orderBy(Collector::ORDERBY_PUBLISHED_ISSUES)
                ->getMany();
            // Nothing to show on these pages before the first issue is published
            if ($publishedIssues->isNotEmpty()) {
                $root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'issue', 'current')));
                $root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'issue', 'archive')));
            }
            foreach ($publishedIssues as $issue) {
                $root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'issue', 'view', [$issue->getId()])));
                // Articles for issue. Only the ids are read here: loading the submissions
                // would also load every publication with its authors, DOIs and metadata.
                $submissionIds = Repo::submission()
                    ->getCollector()
                    ->filterByContextIds([$journalId])
                    ->filterByIssueIds([$issue->getId()])
                    ->filterByStatus([Submission::STATUS_PUBLISHED])
                    ->getIds()
                    ->all();
                foreach (array_chunk($submissionIds, self::SUBMISSIONS_PER_BATCH) as $batch) {
                    $this->_addSubmissionUrls($doc, $request, $batch);
                }
            }
        }

        $doc->appendChild($root);

        // Enable plugins to change the sitemap
        Hook::call('SitemapHandler::createJournalSitemap', [&$doc]);

        return $doc;
    }

    /**
     * Add the article and galley URLs of published submissions, in the given order,
     * reading only the columns the URLs need.
     *
     * @param int[] $submissionIds
     */
    protected function _addSubmissionUrls(DOMDocument $doc, Request $request, array $submissionIds): void
    {
        $root = $doc->documentElement;
        $journalPath = $request->getJournal()->getPath();

        // The URL path of the current publication (see Submission::getBestId())
        $submissions = DB::table('submissions AS s')
            ->join('publications AS p', 'p.publication_id', '=', 's.current_publication_id')
            ->whereIn('s.submission_id', $submissionIds)
            ->get(['s.submission_id', 's.current_publication_id', 'p.url_path'])
            ->keyBy('submission_id');

        $galleys = Repo::galley()
            ->getCollector()
            ->filterByPublicationIds($submissions->pluck('current_publication_id')->all())
            ->getQueryBuilder()
            ->leftJoin('submission_files AS sf', 'sf.submission_file_id', '=', 'g.submission_file_id')
            ->leftJoin('files AS f', 'f.file_id', '=', 'sf.file_id')
            ->select(['g.galley_id', 'g.publication_id', 'g.url_path', 'f.mimetype'])
            ->get()
            ->groupBy('publication_id');

        foreach ($submissionIds as $submissionId) {
            $submission = $submissions->get($submissionId);
            if (!$submission) {
                continue;
            }
            $bestId = strlen((string) $submission->url_path) ? $submission->url_path : $submissionId;
            // Abstract
            $root->appendChild($this->_createUrlTree($doc, $request->url($journalPath, 'article', 'view', [$bestId])));
            // Galley files
            foreach ($galleys->get($submission->current_publication_id, []) as $galley) {
                // See Galley::getBestGalleyId()
                $galleyBestId = strlen((string) $galley->url_path) ? $galley->url_path : $galley->galley_id;
                // Same PDF URL as citation_pdf_url, which Google Scholar indexes
                $url = $galley->mimetype === 'application/pdf'
                    ? $request->getDispatcher()->url($request, Application::ROUTE_PAGE, $journalPath, 'article', 'download', [$bestId, $galleyBestId], urlLocaleForPage: '')
                    : $request->url($journalPath, 'article', 'view', [$bestId, $galleyBestId]);
                $root->appendChild($this->_createUrlTree($doc, $url));
            }
        }
    }
}

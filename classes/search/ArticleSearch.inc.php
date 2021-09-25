<?php

/**
 * @file classes/search/ArticleSearch.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleSearch
 * @ingroup search
 *
 * @see ArticleSearchDAO
 *
 * @brief Class for retrieving article search results.
 *
 */

namespace APP\search;

use APP\core\Application;
use APP\facades\Repo;
use PKP\facades\Locale;
use APP\issue\IssueAction;
use PKP\db\DAORegistry;
use PKP\plugins\HookRegistry;

use PKP\search\SubmissionSearch;
use PKP\statistics\PKPStatisticsHelper;
use PKP\submission\PKPSubmission;

class ArticleSearch extends SubmissionSearch
{
    /**
     * See SubmissionSearch::getSparseArray()
     */
    public function getSparseArray($unorderedResults, $orderBy, $orderDir, $exclude)
    {
        // Calculate a well-ordered (unique) score.
        $resultCount = count($unorderedResults);
        $i = 0;
        foreach ($unorderedResults as $submissionId => &$data) {
            // Reference is necessary to permit modification
            $data['score'] = ($resultCount * $data['count']) + $i++;
        }

        // If we got a primary sort order then apply it and use score as secondary
        // order only.
        // NB: We apply order after merging and before paging/formatting. Applying
        // order before merging would require us to retrieve dependent objects for
        // results being purged later. Doing everything in a closed SQL is not
        // possible (e.g. for authors). Applying sort order after paging and
        // formatting is not possible as we have to order the whole list before
        // slicing it. So this seems to be the most appropriate place, although we
        // may have to retrieve some objects again when formatting results.
        $orderedResults = [];
        $contextDao = Application::getContextDAO();
        $contextTitles = [];
        if ($orderBy == 'popularityAll' || $orderBy == 'popularityMonth') {
            $application = Application::get();
            $metricType = $application->getDefaultMetricType();
            if (is_null($metricType)) {
                // If no default metric has been found then sort by score...
                $orderBy = 'score';
            } else {
                // Retrieve a metrics report for all submissions.
                $column = PKPStatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID;
                $filter = [
                    PKPStatisticsHelper::STATISTICS_DIMENSION_ASSOC_TYPE => [ASSOC_TYPE_GALLEY, ASSOC_TYPE_SUBMISSION],
                    PKPStatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID => [array_keys($unorderedResults)]
                ];
                if ($orderBy == 'popularityMonth') {
                    $oneMonthAgo = date('Ymd', strtotime('-1 month'));
                    $today = date('Ymd');
                    $filter[PKPStatisticsHelper::STATISTICS_DIMENSION_DAY] = ['from' => $oneMonthAgo, 'to' => $today];
                }
                $rawReport = $application->getMetrics($metricType, $column, $filter);
                foreach ($rawReport as $row) {
                    $unorderedResults[$row['submission_id']]['metric'] = (int)$row['metric'];
                }
            }
        }

        $i = 0; // Used to prevent ties from clobbering each other
        foreach ($unorderedResults as $submissionId => $data) {
            // Exclude unwanted IDs.
            if (in_array($submissionId, $exclude)) {
                continue;
            }

            switch ($orderBy) {
                case 'authors':
                    $submission = Repo::submission()->get($submissionId);
                    $orderKey = $submission->getAuthorString();
                    break;

                case 'title':
                    $submission = Repo::submission()->get($submissionId);
                    $orderKey = '';
                    if (!empty($submission->getCurrentPublication())) {
                        $orderKey = $submission->getCurrentPublication()->getLocalizedData('title');
                    }
                    break;

                case 'journalTitle':
                    if (!isset($contextTitles[$data['journal_id']])) {
                        $context = $contextDao->getById($data['journal_id']);
                        $contextTitles[$data['journal_id']] = $context->getLocalizedName();
                    }
                    $orderKey = $contextTitles[$data['journal_id']];
                    break;

                case 'issuePublicationDate':
                case 'publicationDate':
                    $orderKey = $data[$orderBy];
                    break;

                case 'popularityAll':
                case 'popularityMonth':
                    $orderKey = ($data['metric'] ?? 0);
                    break;

                default: // order by score.
                    $orderKey = $data['score'];
            }
            if (!isset($orderedResults[$orderKey])) {
                $orderedResults[$orderKey] = [];
            }
            $orderedResults[$orderKey][$data['score'] + $i++] = $submissionId;
        }

        // Order the results by primary order.
        if (strtolower($orderDir) == 'asc') {
            ksort($orderedResults);
        } else {
            krsort($orderedResults);
        }

        // Order the result by secondary order and flatten it.
        $finalOrder = [];
        foreach ($orderedResults as $orderKey => $submissionIds) {
            if (count($submissionIds) == 1) {
                $finalOrder[] = array_pop($submissionIds);
            } else {
                if (strtolower($orderDir) == 'asc') {
                    ksort($submissionIds);
                } else {
                    krsort($submissionIds);
                }
                $finalOrder = array_merge($finalOrder, array_values($submissionIds));
            }
        }
        return $finalOrder;
    }

    /**
     * Retrieve the search filters from the request.
     *
     * @param Request $request
     *
     * @return array All search filters (empty and active)
     */
    public function getSearchFilters($request)
    {
        $searchFilters = [
            'query' => $request->getUserVar('query'),
            'searchJournal' => $request->getUserVar('searchJournal'),
            'abstract' => $request->getUserVar('abstract'),
            'authors' => $request->getUserVar('authors'),
            'title' => $request->getUserVar('title'),
            'galleyFullText' => $request->getUserVar('galleyFullText'),
            'discipline' => $request->getUserVar('discipline'),
            'subject' => $request->getUserVar('subject'),
            'type' => $request->getUserVar('type'),
            'coverage' => $request->getUserVar('coverage'),
            'indexTerms' => $request->getUserVar('indexTerms')
        ];

        // Is this a simplified query from the navigation
        // block plugin?
        $simpleQuery = $request->getUserVar('simpleQuery');
        if (!empty($simpleQuery)) {
            // In the case of a simplified query we get the
            // filter type from a drop-down.
            $searchType = $request->getUserVar('searchField');
            if (array_key_exists($searchType, $searchFilters)) {
                $searchFilters[$searchType] = $simpleQuery;
            }
        }

        // Publishing dates.
        $fromDate = $request->getUserDateVar('dateFrom', 1, 1);
        $searchFilters['fromDate'] = (is_null($fromDate) ? null : date('Y-m-d H:i:s', $fromDate));
        $toDate = $request->getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
        $searchFilters['toDate'] = (is_null($toDate) ? null : date('Y-m-d H:i:s', $toDate));

        // Instantiate the context.
        $context = $request->getContext();
        $siteSearch = !((bool)$context);
        if ($siteSearch) {
            $contextDao = Application::getContextDAO();
            if (!empty($searchFilters['searchJournal'])) {
                $context = $contextDao->getById($searchFilters['searchJournal']);
            } elseif (array_key_exists('journalTitle', $request->getUserVars())) {
                $contexts = $contextDao->getAll(true);
                while ($context = $contexts->next()) {
                    if (in_array(
                        $request->getUserVar('journalTitle'),
                        (array) $context->getTitle(null)
                    )) {
                        break;
                    }
                }
            }
        }
        $searchFilters['searchJournal'] = $context;
        $searchFilters['siteSearch'] = $siteSearch;

        return $searchFilters;
    }

    /**
     * Load the keywords array from a given search filter.
     *
     * @param array $searchFilters Search filters as returned from
     *  ArticleSearch::getSearchFilters()
     *
     * @return array Keyword array as required by SubmissionSearch::retrieveResults()
     */
    public function getKeywordsFromSearchFilters($searchFilters)
    {
        $indexFieldMap = $this->getIndexFieldMap();
        $indexFieldMap[SubmissionSearch::SUBMISSION_SEARCH_INDEX_TERMS] = 'indexTerms';
        $keywords = [];
        if (isset($searchFilters['query'])) {
            $keywords[null] = $searchFilters['query'];
        }
        foreach ($indexFieldMap as $bitmap => $searchField) {
            if (isset($searchFilters[$searchField]) && !empty($searchFilters[$searchField])) {
                $keywords[$bitmap] = $searchFilters[$searchField];
            }
        }
        return $keywords;
    }

    /**
     * See SubmissionSearch::formatResults()
     *
     * @param array $results
     * @param User $user optional (if availability information is desired)
     *
     * @return array An array with the articles, published submissions,
     *  issue, journal, section and the issue availability.
     */
    public function formatResults($results, $user = null)
    {
        $contextDao = Application::getContextDAO();
        $sectionDao = DAORegistry::getDAO('SectionDAO'); /** @var SectionDAO $sectionDao */

        $publishedSubmissionCache = [];
        $articleCache = [];
        $issueCache = [];
        $issueAvailabilityCache = [];
        $contextCache = [];
        $sectionCache = [];

        $returner = [];
        foreach ($results as $articleId) {
            // Get the article, storing in cache if necessary.
            if (!isset($articleCache[$articleId])) {
                $submission = Repo::submission()->get($articleId);
                $publishedSubmissionCache[$articleId] = $submission;
                $articleCache[$articleId] = $submission;
            }
            $article = $articleCache[$articleId];
            $publishedSubmission = $publishedSubmissionCache[$articleId];

            if ($publishedSubmission && $article) {
                $sectionId = $article->getSectionId();
                if (!isset($sectionCache[$sectionId])) {
                    $sectionCache[$sectionId] = $sectionDao->getById($sectionId);
                }

                // Get the context, storing in cache if necessary.
                $contextId = $article->getData('contextId');
                if (!isset($contextCache[$contextId])) {
                    $contextCache[$contextId] = $contextDao->getById($contextId);
                }

                // Get the issue, storing in cache if necessary.
                $issueId = $publishedSubmission->getCurrentPublication()->getData('issueId');
                if (!isset($issueCache[$issueId])) {
                    $issue = Repo::issue()->get($issueId);
                    $issueCache[$issueId] = $issue;
                    $issueAction = new IssueAction();
                    $issueAvailabilityCache[$issueId] = !$issueAction->subscriptionRequired($issue, $contextCache[$contextId]) || $issueAction->subscribedUser($user, $contextCache[$contextId], $issueId, $articleId) || $issueAction->subscribedDomain(Application::get()->getRequest(), $contextCache[$contextId], $issueId, $articleId);
                }

                // Only display articles from published issues.
                if (!isset($issueCache[$issueId]) || !$issueCache[$issueId]->getPublished()) {
                    continue;
                }

                // Store the retrieved objects in the result array.
                $returner[] = [
                    'article' => $article,
                    'publishedSubmission' => $publishedSubmissionCache[$articleId],
                    'issue' => $issueCache[$issueId],
                    'journal' => $contextCache[$contextId],
                    'issueAvailable' => $issueAvailabilityCache[$issueId],
                    'section' => $sectionCache[$sectionId]
                ];
            }
        }
        return $returner;
    }

    /**
     * Identify similarity terms for a given submission.
     *
     * @param int $submissionId
     *
     * @return null|array An array of string keywords or null
     * if some kind of error occurred.
     */
    public function getSimilarityTerms($submissionId)
    {
        // Check whether a search plugin provides terms for a similarity search.
        $searchTerms = [];
        $result = HookRegistry::call('ArticleSearch::getSimilarityTerms', [$submissionId, &$searchTerms]);

        // If no plugin implements the hook then use the subject keywords
        // of the submission for a similarity search.
        if ($result === false) {
            // Retrieve the article.
            $article = Repo::submission()->get($submissionId);
            if ($article->getData('status') === PKPSubmission::STATUS_PUBLISHED) {
                // Retrieve keywords (if any).
                $submissionSubjectDao = DAORegistry::getDAO('SubmissionKeywordDAO'); /** @var SubmissionKeywordDAO $submissionSubjectDao */
                $allSearchTerms = array_filter($submissionSubjectDao->getKeywords($article->getCurrentPublication()->getId(), [Locale::getLocale(), $article->getLocale(), Locale::getPrimaryLocale()]));
                foreach ($allSearchTerms as $locale => $localeSearchTerms) {
                    $searchTerms += $localeSearchTerms;
                }
            }
        }

        return $searchTerms;
    }

    public function getIndexFieldMap()
    {
        return [
            SubmissionSearch::SUBMISSION_SEARCH_AUTHOR => 'authors',
            SubmissionSearch::SUBMISSION_SEARCH_TITLE => 'title',
            SubmissionSearch::SUBMISSION_SEARCH_ABSTRACT => 'abstract',
            SubmissionSearch::SUBMISSION_SEARCH_GALLEY_FILE => 'galleyFullText',
            SubmissionSearch::SUBMISSION_SEARCH_DISCIPLINE => 'discipline',
            SubmissionSearch::SUBMISSION_SEARCH_SUBJECT => 'subject',
            SubmissionSearch::SUBMISSION_SEARCH_KEYWORD => 'keyword',
            SubmissionSearch::SUBMISSION_SEARCH_TYPE => 'type',
            SubmissionSearch::SUBMISSION_SEARCH_COVERAGE => 'coverage'
        ];
    }

    /**
     * See SubmissionSearch::getResultSetOrderingOptions()
     */
    public function getResultSetOrderingOptions($request)
    {
        $resultSetOrderingOptions = [
            'score' => __('search.results.orderBy.relevance'),
            'authors' => __('search.results.orderBy.author'),
            'issuePublicationDate' => __('search.results.orderBy.issue'),
            'publicationDate' => __('search.results.orderBy.date'),
            'title' => __('search.results.orderBy.article')
        ];

        // Only show the "popularity" options if we have a default metric.
        $application = Application::get();
        $metricType = $application->getDefaultMetricType();
        if (!is_null($metricType)) {
            $resultSetOrderingOptions['popularityAll'] = __('search.results.orderBy.popularityAll');
            $resultSetOrderingOptions['popularityMonth'] = __('search.results.orderBy.popularityMonth');
        }

        // Only show the "journal title" option if we have several journals.
        $context = $request->getContext();
        if (!$context) {
            $resultSetOrderingOptions['journalTitle'] = __('search.results.orderBy.journal');
        }

        // Let plugins mangle the search ordering options.
        HookRegistry::call(
            'SubmissionSearch::getResultSetOrderingOptions',
            [$context, &$resultSetOrderingOptions]
        );

        return $resultSetOrderingOptions;
    }

    /**
     * See SubmissionSearch::getDefaultOrderDir()
     */
    public function getDefaultOrderDir($orderBy)
    {
        $orderDir = 'asc';
        if (in_array($orderBy, ['score', 'publicationDate', 'issuePublicationDate', 'popularityAll', 'popularityMonth'])) {
            $orderDir = 'desc';
        }
        return $orderDir;
    }

    /**
     * See SubmissionSearch::getSearchDao()
     */
    protected function getSearchDao()
    {
        return DAORegistry::getDAO('ArticleSearchDAO');
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\search\ArticleSearch', '\ArticleSearch');
}

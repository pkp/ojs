<?php

/**
 * @file pages/search/SearchHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SearchHandler
 *
 * @ingroup pages_search
 *
 * @brief Handle site index requests.
 */

namespace APP\pages\search;

use APP\facades\Repo;
use APP\handler\Handler;
use APP\search\SubmissionSearchResult;
use APP\security\authorization\OjsJournalMustPublishPolicy;
use APP\template\TemplateManager;
use Laravel\Scout\Builder;
use PKP\core\PKPRequest;
use PKP\plugins\Hook;

class SearchHandler extends Handler
{
    /**
     * @copydoc PKPHandler::authorize()
     *
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        if ($request->getContext()) {
            $this->addPolicy(new OjsJournalMustPublishPolicy($request));
        }

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Show the search form
     */
    public function index($args, $request)
    {
        $this->validate(null, $request);
        $this->search($args, $request);
    }

    /**
     * Show the search form
     *
     * @hook SearchHandler::search::builder ['builder' => $builder, 'request' => $request]
     */
    public function search(array $args, PKPRequest $request): void
    {
        $this->validate(null, $request);

        $context = $request->getContext();
        $contextId = $context?->getId() ?? (int) $request->getUserVar('searchContext');

        $query = (string) $request->getUserVar('query');
        $dateFrom = $request->getUserDateVar('dateFrom');
        $dateTo = $request->getUserDateVar('dateTo');

        $rangeInfo = $this->getRangeInfo($request, 'search');

        $builder = new Builder(new SubmissionSearchResult(), $query);
        // Retrieve results.
        $builder
            ->where('contextId', $contextId)
            ->where('publishedFrom', $dateFrom)
            ->where('publishedTo', $dateTo)
            ->whereIn('categoryIds', $request->getUserVar('categoryIds'))
            ->whereIn('sectionIds', $request->getUserVar('sectionIds'))
            ->whereIn('keywords', $request->getUserVar('keywords'))
            ->whereIn('subjects', $request->getUserVar('subjects'));

        // Allow hook registrants to adjust the builder before querying
        Hook::run('SearchHandler::search::builder', ['builder' => $builder, 'request' => $request]);

        $results = $builder->paginate($rangeInfo->getCount(), 'submissions', $rangeInfo->getPage());

        $this->setupTemplate($request);

        $templateMgr = TemplateManager::getManager($request);

        // Assign the year range.
        $collector = Repo::publication()->getCollector();
        $collector->filterByContextIds($contextId ? [$contextId] : null);
        $yearRange = Repo::publication()->getDateBoundaries($collector);
        $yearStart = substr($yearRange->min_date_published, 0, 4);
        $yearEnd = substr($yearRange->max_date_published, 0, 4);

        $this->_assignDateFromTo($request, $templateMgr);

        $templateMgr->assign([
            'query' => $query,
            'results' => $results,
            'searchContext' => $contextId,
            'yearStart' => $yearStart,
            'yearEnd' => $yearEnd,
        ]);

        if (!$request->getContext()) {
            $templateMgr->assign([
                'searchableContexts' => $this->getSearchableContexts(),
            ]);
        }

        $templateMgr->display('frontend/pages/search.tpl');
    }

    /**
     * Assign dateFrom* and dateTo* variables to template
     *
     */
    public function _assignDateFromTo(PKPRequest $request, TemplateManager &$templateMgr)
    {
        // Special case: publication date filters.
        foreach (['From', 'To'] as $fromTo) {
            $month = $request->getUserVar("date{$fromTo}Month");
            $day = $request->getUserVar("date{$fromTo}Day");
            $year = $request->getUserVar("date{$fromTo}Year");
            if (empty($year)) {
                $date = null;
                $hasEmptyFilters = true;
            } else {
                $defaultMonth = ($fromTo == 'From' ? 1 : 12);
                $defaultDay = ($fromTo == 'From' ? 1 : 31);
                $date = date(
                    'Y-m-d H:i:s',
                    mktime(
                        0,
                        0,
                        0,
                        empty($month) ? $defaultMonth : $month,
                        empty($day) ? $defaultDay : $day,
                        $year
                    )
                );
                $hasActiveFilters = true;
            }

            $templateMgr->assign([
                "date{$fromTo}Month" => $month,
                "date{$fromTo}Day" => $day,
                "date{$fromTo}Year" => $year,
                "date{$fromTo}" => $date
            ]);
        }
    }

    /**
     * Setup common template variables.
     *
     * @param \APP\core\Request $request
     */
    public function setupTemplate($request)
    {
        parent::setupTemplate($request);
        $templateMgr = TemplateManager::getManager($request);
        $journal = $request->getJournal();
        if (!$journal || !$journal->getData('restrictSiteAccess')) {
            $templateMgr->setCacheability(TemplateManager::CACHEABILITY_PUBLIC);
        }
    }

    protected function getSearchableContexts(): array
    {
        $contextService = app()->get('context');
        return $contextService->getManySummary([
            'isEnabled' => true,
        ]);
    }
}

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
use APP\security\authorization\OjsJournalMustPublishPolicy;
use APP\template\TemplateManager;
use Laravel\Scout\Builder;
use PKP\core\PKPRequest;
use PKP\search\SubmissionSearchResult;
use PKP\userGroup\UserGroup;

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
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function index($args, $request)
    {
        $this->validate(null, $request);
        $this->search($args, $request);
    }

    /**
     * Show the search form
     */
    public function search(array $args, PKPRequest $request)
    {
        $this->validate(null, $request);

        $context = $request->getContext();
        $contextId = $context?->getId();
        if (!$context) {
            $contextId = (int) $request->getUserVar('searchContext');
        }

        $query = (string) $request->getUserVar('query');
        $dateFrom = $request->getUserDateVar('dateFrom');
        $dateTo = $request->getUserDateVar('dateTo');

        $rangeInfo = $this->getRangeInfo($request, 'search');

        // Retrieve results.
        $results = (new Builder(new SubmissionSearchResult(), $query))
            ->where('contextId', $contextId)
            ->where('publishedFrom', $dateFrom)
            ->where('publishedTo', $dateTo)
            ->whereIn('categoryIds', $request->getUserVar('categoryIds'))
            ->whereIn('sectionIds', $request->getUserVar('sectionIds'))
            ->paginate($rangeInfo->getCount(), 'submissions', $rangeInfo->getPage());

        $this->setupTemplate($request);

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->setCacheability(TemplateManager::CACHEABILITY_NO_STORE);

        // Assign the year range.
        $collector = Repo::publication()->getCollector();
        if ($contextId) {
            $collector->filterByContextIds([$contextId]);
        }
        $yearRange = Repo::publication()->getDateBoundaries($collector);
        $yearStart = substr($yearRange->min_date_published, 0, 4);
        $yearEnd = substr($yearRange->max_date_published, 0, 4);

        $templateMgr->assign([
            'query' => $query,
            'results' => $results,
            'searchContext' => $contextId,
            'dateFrom' => $dateFrom ? date('Y-m-d H:i:s', $dateFrom) : null,
            'dateTo' => $dateTo ? date('Y-m-d H:i:s', $dateTo) : null,
            'yearStart' => $yearStart,
            'yearEnd' => $yearEnd,
            'authorUserGroups' => UserGroup::withRoleIds([\PKP\security\Role::ROLE_ID_AUTHOR])
                ->withContextIds($contextId ? [$contextId] : null)
                ->get(),
        ]);

        if (!$request->getContext()) {
            $templateMgr->assign([
                'searchableContexts' => $this->getSearchableContexts(),
            ]);
        }

        $templateMgr->display('frontend/pages/search.tpl');
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

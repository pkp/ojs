<?php

/**
 * @file api/v1/stats/StatsIssueController.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsIssueController
 *
 * @ingroup api_v1_stats
 *
 * @brief Controller class to handle API requests for issue statistics.
 *
 */

namespace APP\API\v1\stats\issues;

use APP\core\Application;
use APP\facades\Repo;
use APP\security\authorization\OjsIssueRequiredPolicy;
use APP\statistics\StatisticsHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use PKP\core\PKPBaseController;
use PKP\core\PKPRequest;
use PKP\plugins\Hook;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\authorization\PolicySet;
use PKP\security\authorization\RoleBasedHandlerOperationPolicy;
use PKP\security\authorization\UserRolesRequiredPolicy;
use PKP\security\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatsIssueController extends PKPBaseController
{
    /**
     * @copydoc \PKP\core\PKPBaseController::getHandlerPath()
     */
    public function getHandlerPath(): string
    {
        return 'stats/issues';
    }

    /**
     * @copydoc \PKP\core\PKPBaseController::getRouteGroupMiddleware()
     */
    public function getRouteGroupMiddleware(): array
    {
        return [
            'has.user',
            'has.context',
            self::roleAuthorizer([
                Role::ROLE_ID_SITE_ADMIN,
                Role::ROLE_ID_MANAGER,
                Role::ROLE_ID_SUB_EDITOR,
            ]),
        ];
    }

    /**
     * @copydoc \PKP\core\PKPBaseController::getGroupRoutes()
     */
    public function getGroupRoutes(): void
    {
        Route::get('timeline', $this->getManyTimeline(...))
            ->name('stats.issue.getManyTimeline');

        Route::get('{issueId}', $this->get(...))
            ->name('stats.issue.getIssue')
            ->whereNumber('issueId');

        Route::get('{issueId}/timeline', $this->getTimeline(...))
            ->name('stats.issue.getTimeline')
            ->whereNumber('issueId');

        Route::get('', $this->getMany(...))
            ->name('stats.issue.getMany');
    }

    /**
     * @copydoc \PKP\core\PKPBaseController::authorize()
     */
    public function authorize(PKPRequest $request, array &$args, array $roleAssignments): bool
    {
        $this->addPolicy(new UserRolesRequiredPolicy($request), true);

        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));

        $rolePolicy = new PolicySet(PolicySet::COMBINING_PERMIT_OVERRIDES);

        foreach ($roleAssignments as $role => $operations) {
            $rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
        }

        $this->addPolicy($rolePolicy);

        $illuminateRequest = $args[0]; /** @var \Illuminate\Http\Request $illuminateRequest */

        if (in_array(static::getRouteActionName($illuminateRequest), ['get', 'getGalley', 'getToc'])) {
            $this->addPolicy(new OjsIssueRequiredPolicy($request, $args));
        }

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Get usage stats for a set of issues
     *
     * Returns total views by toc and all galleys.
     *
     * @hook API::stats::issues::params [[&$allowedParams, $illuminateRequest]]
     */
    public function getMany(Request $illuminateRequest): StreamedResponse|JsonResponse
    {
        $request = $this->getRequest();
        $responseCSV = str_contains($illuminateRequest->headers->get('Accept'), 'text/csv') ? true : false;

        $defaultParams = [
            'count' => 30,
            'offset' => 0,
            'orderDirection' => StatisticsHelper::STATISTICS_ORDER_DESC,
        ];

        $requestParams = array_merge($defaultParams, $illuminateRequest->query());

        $allowedParams = $this->_processAllowedParams($requestParams, [
            'dateStart',
            'dateEnd',
            'count',
            'offset',
            'orderDirection',
            'searchPhrase',
            'issueIds',
        ]);

        Hook::call('API::stats::issues::params', [&$allowedParams, $illuminateRequest]);

        $allowedParams['contextIds'] = [$request->getContext()->getId()];

        $result = $this->_validateStatDates($allowedParams);
        if ($result !== true) {
            return response()->json(['error' => $result], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($allowedParams['orderDirection'], [StatisticsHelper::STATISTICS_ORDER_ASC, StatisticsHelper::STATISTICS_ORDER_DESC])) {
            return response()->json([
                'error' => __('api.stats.400.invalidOrderDirection'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Identify issues which should be included in the results when a searchPhrase is passed
        if (!empty($allowedParams['searchPhrase'])) {
            $allowedIssueIds = empty($allowedParams['issueIds']) ? [] : $allowedParams['issueIds'];
            $allowedParams['issueIds'] = $this->_processSearchPhrase($allowedParams['searchPhrase'], $allowedIssueIds);

            if (empty($allowedParams['issueIds'])) {
                $csvColumnNames = $this->_getIssueReportColumnNames();
                if ($responseCSV) {
                    return response()->withFile([], $csvColumnNames, 0);
                } else {
                    return response()->json([
                        'items' => [],
                        'itemsMax' => 0,
                    ], Response::HTTP_OK);
                }
            }
        }

        // Get a list (count number) of top issues by total (toc + galley) views
        $statsService = app()->get('issueStats'); /** @var \APP\services\StatsIssueService $statsService */
        $totalMetrics = $statsService->getTotals($allowedParams);

        // Get the stats for each issue
        $items = [];
        foreach ($totalMetrics as $totalMetric) {
            $issueId = $totalMetric->issue_id;
            $dateStart = array_key_exists('dateStart', $allowedParams) ? $allowedParams['dateStart'] : null;
            $dateEnd = array_key_exists('dateEnd', $allowedParams) ? $allowedParams['dateEnd'] : null;
            $metricsByType = $statsService->getTotalsByType($issueId, $this->getRequest()->getContext()->getId(), $dateStart, $dateEnd);

            if ($responseCSV) {
                $items[] = $this->getItemForCSV($issueId, $metricsByType['toc'], $metricsByType['galley']);
            } else {
                $items[] = $this->getItemForJSON($issueId, $metricsByType['toc'], $metricsByType['galley']);
            }
        }

        $itemsMax = $statsService->getCount($allowedParams);
        $csvColumnNames = $this->_getIssueReportColumnNames();
        if ($responseCSV) {
            return response()->withFile($items, $csvColumnNames, $itemsMax);
        } else {
            return response()->json([
                'items' => $items,
                'itemsMax' => $itemsMax,
            ], Response::HTTP_OK);
        }
    }

    /**
     * Get the total TOC or issue galley views for a set of issues
     * in a timeline broken down by month or day
     *
     * @hook API::stats::issues::timeline::params [[&$allowedParams, $illuminateRequest]]
     */
    public function getManyTimeline(Request $illuminateRequest): StreamedResponse|JsonResponse
    {
        $responseCSV = str_contains($illuminateRequest->headers->get('Accept'), 'text/csv') ? true : false;

        $request = $this->getRequest();

        $defaultParams = [
            'timelineInterval' => StatisticsHelper::STATISTICS_DIMENSION_MONTH,
        ];

        $requestParams = array_merge($defaultParams, $illuminateRequest->query());

        $allowedParams = $this->_processAllowedParams($requestParams, [
            'dateStart',
            'dateEnd',
            'timelineInterval',
            'searchPhrase',
            'type'
        ]);

        Hook::call('API::stats::issues::timeline::params', [&$allowedParams, $illuminateRequest]);

        if (!$this->isValidTimelineInterval($allowedParams['timelineInterval'])) {
            return response()->json([
                'error' => __('api.stats.400.wrongTimelineInterval'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->_validateStatDates($allowedParams);
        if ($result !== true) {
            return response()->json(['error' => $result], Response::HTTP_BAD_REQUEST);
        }

        $allowedParams['contextIds'] = [$request->getContext()->getId()];
        $allowedParams['assocTypes'] = [Application::ASSOC_TYPE_ISSUE];
        if (array_key_exists('type', $allowedParams) && $allowedParams['type'] == 'files') {
            $allowedParams['assocTypes'] = [Application::ASSOC_TYPE_ISSUE_GALLEY];
        };

        $statsService = app()->get('issueStats'); /** @var \APP\services\StatsIssueService $statsService */

        // Identify issues which should be included in the results when a searchPhrase is passed
        if (!empty($allowedParams['searchPhrase'])) {
            $allowedIssueIds = empty($allowedParams['issueIds']) ? [] : $allowedParams['issueIds'];
            $allowedParams['issueIds'] = $this->_processSearchPhrase($allowedParams['searchPhrase'], $allowedIssueIds);

            if (empty($allowedParams['issueIds'])) {
                $dateStart = empty($allowedParams['dateStart']) ? StatisticsHelper::STATISTICS_EARLIEST_DATE : $allowedParams['dateStart'];
                $dateEnd = empty($allowedParams['dateEnd']) ? date('Ymd', strtotime('yesterday')) : $allowedParams['dateEnd'];
                $emptyTimeline = $statsService->getEmptyTimelineIntervals($dateStart, $dateEnd, $allowedParams['timelineInterval']);
                if ($responseCSV) {
                    $csvColumnNames = $statsService->getTimelineReportColumnNames();
                    return response()->withFile($emptyTimeline, $csvColumnNames, 0);
                }
                return response()->json($emptyTimeline, Response::HTTP_OK);
            }
        }

        $data = $statsService->getTimeline($allowedParams['timelineInterval'], $allowedParams);
        if ($responseCSV) {
            $csvColumnNames = $statsService->getTimelineReportColumnNames();
            return response()->withFile($data, $csvColumnNames, count($data));
        }

        return response()->json($data, Response::HTTP_OK);
    }

    /**
     * Get a single issue's usage statistics
     *
     * @hook API::stats::issue::params [[&$allowedParams, $illuminateRequest]]
     */
    public function get(Request $illuminateRequest): JsonResponse
    {
        $request = $this->getRequest();

        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);

        $allowedParams = $this->_processAllowedParams($illuminateRequest->query(), [
            'dateStart',
            'dateEnd',
        ]);

        Hook::call('API::stats::issue::params', [&$allowedParams, $illuminateRequest]);

        $result = $this->_validateStatDates($allowedParams);
        if ($result !== true) {
            return response()->json(['error' => $result], Response::HTTP_BAD_REQUEST);
        }

        $statsService = app()->get('issueStats'); /** @var \APP\services\StatsIssueService $statsService */
        $dateStart = array_key_exists('dateStart', $allowedParams) ? $allowedParams['dateStart'] : null;
        $dateEnd = array_key_exists('dateEnd', $allowedParams) ? $allowedParams['dateEnd'] : null;
        $metricsByType = $statsService->getTotalsByType($issue->getId(), $request->getContext()->getId(), $dateStart, $dateEnd);

        return response()->json([
            'tocViews' => $metricsByType['toc'],
            'issueGalleyViews' => $metricsByType['galley'],
            'issue' => Repo::issue()->getSchemaMap()->mapToStats($issue),
        ], Response::HTTP_OK);
    }

    /**
     * Get the total TOC or issue galley views for an issue broken down by
     * month or day
     *
     * @hook API::stats::issue::timeline::params [[&$allowedParams, $illuminateRequest]]
     */
    public function getTimeline(Request $illuminateRequest): JsonResponse
    {
        $request = $this->getRequest();

        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);

        $defaultParams = [
            'timelineInterval' => StatisticsHelper::STATISTICS_DIMENSION_MONTH,
        ];

        $requestParams = array_merge($defaultParams, $illuminateRequest->query());

        $allowedParams = $this->_processAllowedParams($requestParams, [
            'dateStart',
            'dateEnd',
            'timelineInterval',
            'type'
        ]);

        Hook::call('API::stats::issue::timeline::params', [&$allowedParams, $illuminateRequest]);

        $allowedParams['contextIds'] = [$request->getContext()->getId()];
        $allowedParams['issueIds'] = [$issue->getId()];
        $allowedParams['assocTypes'] = [Application::ASSOC_TYPE_ISSUE];
        if (array_key_exists('type', $allowedParams) && $allowedParams['type'] == 'files') {
            $allowedParams['assocTypes'] = [Application::ASSOC_TYPE_ISSUE_GALLEY];
        };

        if (!$this->isValidTimelineInterval($allowedParams['timelineInterval'])) {
            return response()->json([
                'error' => __('api.stats.400.wrongTimelineInterval'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->_validateStatDates($allowedParams);
        if ($result !== true) {
            return response()->json(['error' => $result], Response::HTTP_BAD_REQUEST);
        }

        $statsService = app()->get('issueStats'); /** @var \APP\services\StatsIssueService $statsService */
        $data = $statsService->getTimeline($allowedParams['timelineInterval'], $allowedParams);
        return response()->json($data, Response::HTTP_OK);
    }

    /**
     * A helper method to filter and sanitize the request params
     *
     * Only allows the specified params through and enforces variable
     * type where needed.
     */
    protected function _processAllowedParams(array $requestParams, array $allowedParams): array
    {
        $returnParams = [];
        foreach ($requestParams as $requestParam => $value) {
            if (!in_array($requestParam, $allowedParams)) {
                continue;
            }
            switch ($requestParam) {
                case 'dateStart':
                case 'dateEnd':
                case 'timelineInterval':
                case 'searchPhrase':
                case 'type':
                    $returnParams[$requestParam] = $value;
                    break;

                case 'count':
                    $returnParams[$requestParam] = min(100, (int) $value);
                    break;

                case 'offset':
                    $returnParams[$requestParam] = (int) $value;
                    break;

                case 'orderDirection':
                    $returnParams[$requestParam] = strtoupper($value);
                    break;

                case 'issueIds':
                    if (is_string($value) && strpos($value, ',') > -1) {
                        $value = explode(',', $value);
                    } elseif (!is_array($value)) {
                        $value = [$value];
                    }
                    $returnParams[$requestParam] = array_map(intval(...), $value);
                    break;
            }
        }
        return $returnParams;
    }

    /**
     * A helper method to get the issueIds param when a searchPhase
     * param is also passed.
     *
     * If the searchPhrase and issueIds params were both passed in the
     * request, then we only return IDs that match both conditions.
     */
    protected function _processSearchPhrase(string $searchPhrase, array $issueIds = []): array
    {
        $searchPhraseIssueIds = Repo::issue()
            ->getCollector()
            ->filterByContextIds([Application::get()->getRequest()->getContext()->getId()])
            ->filterByPublished(true)
            ->searchPhrase($searchPhrase)
            ->getIds()
            ->toArray();

        if (!empty($issueIds)) {
            return array_intersect($issueIds, $searchPhraseIssueIds);
        }
        return $searchPhraseIssueIds;
    }

    /**
     * Get column names for the issue CSV report
     */
    protected function _getIssueReportColumnNames(): array
    {
        return [
            __('common.id'),
            __('editor.issues.issueIdentification'),
            __('stats.total'),
            __('stats.views'),
            __('stats.downloads')
        ];
    }

    /**
     * Get CSV row with issue metrics
     */
    protected function getItemForCSV(int $issueId, int $tocViews, int $issueGalleyViews): array
    {
        $totalViews = $tocViews + $issueGalleyViews;
        // Get issue identification for display
        $issue = Repo::issue()->get($issueId);
        $identification = $issue->getIssueIdentification();
        return [
            $issueId,
            $identification,
            $totalViews,
            $tocViews,
            $issueGalleyViews
        ];
    }

    /**
     * Get JSON data with issue metrics
     */
    protected function getItemForJSON(int $issueId, int $tocViews, int $issueGalleyViews): array
    {
        $totalViews = $tocViews + $issueGalleyViews;
        // Get basic issue details for display
        $issue = Repo::issue()->get($issueId);
        $issueProps = Repo::issue()->getSchemaMap()->mapToStats($issue);
        return [
            'totalViews' => $totalViews,
            'tocViews' => $tocViews,
            'issueGalleyViews' => $issueGalleyViews,
            'issue' => $issueProps,
        ];
    }

    /**
     * Check if the timeline interval is valid
     */
    protected function isValidTimelineInterval(string $interval): bool
    {
        return in_array($interval, [
            StatisticsHelper::STATISTICS_DIMENSION_DAY,
            StatisticsHelper::STATISTICS_DIMENSION_MONTH
        ]);
    }
}

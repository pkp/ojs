<?php

/**
 * @file api/v1/stats/StatsIssueHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsIssueHandler
 * @ingroup api_v1_stats
 *
 * @brief Handle API requests for issue statistics.
 *
 */

use APP\core\Application;
use APP\core\Services;
use APP\facades\Repo;
use APP\security\authorization\OjsIssueRequiredPolicy;
use APP\statistics\StatisticsHelper;
use PKP\core\APIResponse;
use PKP\handler\APIHandler;
use PKP\plugins\HookRegistry;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\authorization\PolicySet;
use PKP\security\authorization\RoleBasedHandlerOperationPolicy;
use PKP\security\Role;
use Slim\Http\Request as SlimHttpRequest;

class StatsIssueHandler extends APIHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_handlerPath = 'stats/issues';
        $roles = [Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_MANAGER /*, Role::ROLE_ID_SUB_EDITOR */];
        $this->_endpoints = [
            'GET' => [
                [
                    'pattern' => $this->getEndpointPattern(),
                    'handler' => [$this, 'getMany'],
                    'roles' => $roles
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/toc',
                    'handler' => [$this, 'getManyToc'],
                    'roles' => $roles
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/galley',
                    'handler' => [$this, 'getManyGalley'],
                    'roles' => $roles
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/{issueId:\d+}',
                    'handler' => [$this, 'get'],
                    'roles' => $roles
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/{issueId:\d+}/toc',
                    'handler' => [$this, 'getToc'],
                    'roles' => $roles
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/{issueId:\d+}/galley',
                    'handler' => [$this, 'getGalley'],
                    'roles' => $roles
                ],
            ],
        ];
        parent::__construct();
    }

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $routeName = null;
        $slimRequest = $this->getSlimRequest();

        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));

        $rolePolicy = new PolicySet(PolicySet::COMBINING_PERMIT_OVERRIDES);
        foreach ($roleAssignments as $role => $operations) {
            $rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
        }
        $this->addPolicy($rolePolicy);

        if (!is_null($slimRequest) && ($route = $slimRequest->getAttribute('route'))) {
            $routeName = $route->getName();
        }
        if (in_array($routeName, ['get', 'getGalley', 'getToc'])) {
            $this->addPolicy(new OjsIssueRequiredPolicy($request, $args));
        }

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Get usage stats for a set of issues
     *
     * Returns total views by toc and all galleys.
     */
    public function getMany(SlimHttpRequest $slimRequest, APIResponse $response, array $args): APIResponse
    {
        $request = $this->getRequest();
        $responseCSV = str_contains($slimRequest->getHeaderLine('Accept'), APIResponse::RESPONSE_CSV) ? true : false;

        if (!$request->getContext()) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        $defaultParams = [
            'count' => 30,
            'offset' => 0,
            'orderDirection' => StatisticsHelper::STATISTICS_ORDER_DESC,
        ];

        $requestParams = array_merge($defaultParams, $slimRequest->getQueryParams());

        $allowedParams = $this->_processAllowedParams($requestParams, [
            'dateStart',
            'dateEnd',
            'count',
            'offset',
            'orderDirection',
            'searchPhrase',
            'issueIds',
        ]);

        $allowedParams['contextIds'] = $request->getContext()->getId();

        HookRegistry::call('API::stats::issues::params', [&$allowedParams, $slimRequest]);

        $result = $this->_validateStatDates($allowedParams);
        if ($result !== true) {
            return $response->withStatus(400)->withJsonError($result);
        }

        if (!in_array($allowedParams['orderDirection'], [StatisticsHelper::STATISTICS_ORDER_ASC, StatisticsHelper::STATISTICS_ORDER_DESC])) {
            return $response->withStatus(400)->withJsonError('api.stats.400.invalidOrderDirection');
        }

        // Identify issues which should be included in the results when a searchPhrase is passed
        if (!empty($allowedParams['searchPhrase'])) {
            $allowedIssueIds = empty($allowedParams['issueIds']) ? [] : $allowedParams['issueIds'];
            $allowedParams['issueIds'] = $this->_processSearchPhrase($allowedParams['searchPhrase'], $allowedIssueIds);

            if (empty($allowedParams['issueIds'])) {
                $csvColumnNames = $this->_getIssueReportColumnNames();
                if ($responseCSV) {
                    return $response->withCSV(0, [], $csvColumnNames);
                } else {
                    return $response->withJson([
                        'items' => [],
                        'itemsMax' => 0,
                    ], 200);
                }
            }
        }

        // Get a list (count number) of top issues by total (toc + galley) views
        $statsService = Services::get('issueStats');
        $totalMetrics = $statsService->getTotalMetrics($allowedParams);

        // Get the stats for each issue
        $items = [];
        foreach ($totalMetrics as $totalMetric) {
            if (empty($totalMetric->issue_id)) {
                continue;
            }
            $issueId = $totalMetric->issue_id;

            $typeParams = $allowedParams;
            $typeParams['issueIds'] = $issueId;
            $metricsByType = $statsService->getMetricsByType($typeParams);

            $tocViews = $issueGalleyViews = 0;
            $tocRecord = array_filter($metricsByType, [$statsService, 'filterRecordTOC']);
            if (!empty($tocRecord)) {
                $tocViews = (int) current($tocRecord)->metric;
            }
            $issueGalleyRecord = array_filter($metricsByType, [$statsService, 'filterRecordIssueGalley']);
            if (!empty($issueGalleyRecord)) {
                $issueGalleyViews = current($issueGalleyRecord)->metric;
            }
            $totalViews = $tocViews + $issueGalleyViews;

            if ($responseCSV) {
                $items[] = $this->getCSVItem($issueId, $tocViews, $issueGalleyViews, $totalViews);
            } else {
                $items[] = $this->getJSONItem($issueId, $tocViews, $issueGalleyViews, $totalViews);
            }
        }

        $itemsMaxParams = $allowedParams;
        unset($itemsMaxParams['count']);
        unset($itemsMaxParams['offset']);
        $itemsMax = $statsService->getTotalCount($itemsMaxParams);

        $csvColumnNames = $this->_getIssueReportColumnNames();
        if ($responseCSV) {
            return $response->withCSV($itemsMax, $items, $csvColumnNames);
        } else {
            return $response->withJson([
                'items' => $items,
                'itemsMax' => $itemsMax,
            ], 200);
        }
    }

    /**
     * Get the total TOC views for a set of issues
     * in a timeline broken down by month or day
     */
    public function getManyToc(SlimHttpRequest $slimRequest, APIResponse $response, array $args): APIResponse
    {
        $request = $this->getRequest();

        if (!$request->getContext()) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        $defaultParams = [
            'timelineInterval' => StatisticsHelper::STATISTICS_DIMENSION_MONTH,
        ];

        $requestParams = array_merge($defaultParams, $slimRequest->getQueryParams());

        $allowedParams = $this->_processAllowedParams($requestParams, [
            'dateStart',
            'dateEnd',
            'timelineInterval',
            'searchPhrase',
        ]);

        HookRegistry::call('API::stats::issues::toc::params', [&$allowedParams, $slimRequest]);

        if (!in_array($allowedParams['timelineInterval'], [StatisticsHelper::STATISTICS_DIMENSION_DAY, StatisticsHelper::STATISTICS_DIMENSION_MONTH])) {
            return $response->withStatus(400)->withJsonError('api.stats.400.wrongTimelineInterval');
        }

        $result = $this->_validateStatDates($allowedParams);
        if ($result !== true) {
            return $response->withStatus(400)->withJsonError($result);
        }

        $allowedParams['contextIds'] = $request->getContext()->getId();
        $allowedParams['assocTypes'] = Application::ASSOC_TYPE_ISSUE;

        // Identify issues which should be included in the results when a searchPhrase is passed
        if (!empty($allowedParams['searchPhrase'])) {
            $allowedIssueIds = empty($allowedParams['issueIds']) ? [] : $allowedParams['issueIds'];
            $allowedParams['issueIds'] = $this->_processSearchPhrase($allowedParams['searchPhrase'], $allowedIssueIds);

            if (empty($allowedParams['issueIds'])) {
                $dateStart = empty($allowedParams['dateStart']) ? StatisticsHelper::STATISTICS_EARLIEST_DATE : $allowedParams['dateStart'];
                $dateEnd = empty($allowedParams['dateEnd']) ? date('Ymd', strtotime('yesterday')) : $allowedParams['dateEnd'];
                $emptyTimeline = Services::get('issueStats')->getEmptyTimelineIntervals($dateStart, $dateEnd, $allowedParams['timelineInterval']);
                return $response->withJson($emptyTimeline, 200);
            }
        }

        $data = Services::get('issueStats')->getTimeline($allowedParams['timelineInterval'], $allowedParams);

        return $response->withJson($data, 200);
    }

    /**
     * Get the total galley views for a set of issues
     * in a timeline broken down by month or day
     */
    public function getManyGalley(SlimHttpRequest $slimRequest, APIResponse $response, array $args): APIResponse
    {
        $request = $this->getRequest();

        if (!$request->getContext()) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        $defaultParams = [
            'timelineInterval' => StatisticsHelper::STATISTICS_DIMENSION_MONTH,
        ];

        $requestParams = array_merge($defaultParams, $slimRequest->getQueryParams());

        $allowedParams = $this->_processAllowedParams($requestParams, [
            'dateStart',
            'dateEnd',
            'timelineInterval',
            'searchPhrase',
        ]);

        HookRegistry::call('API::stats::issues::galley::params', [&$allowedParams, $slimRequest]);

        if (!in_array($allowedParams['timelineInterval'], [StatisticsHelper::STATISTICS_DIMENSION_DAY, StatisticsHelper::STATISTICS_DIMENSION_MONTH])) {
            return $response->withStatus(400)->withJsonError('api.stats.400.wrongTimelineInterval');
        }

        $result = $this->_validateStatDates($allowedParams);
        if ($result !== true) {
            return $response->withStatus(400)->withJsonError($result);
        }

        $allowedParams['contextIds'] = $request->getContext()->getId();
        $allowedParams['assocTypes'] = Application::ASSOC_TYPE_ISSUE_GALLEY;

        // Identify issues which should be included in the results when a searchPhrase is passed
        if (!empty($allowedParams['searchPhrase'])) {
            $allowedIssueIds = empty($allowedParams['issueIds']) ? [] : $allowedParams['issueIds'];
            $allowedParams['issueIds'] = $this->_processSearchPhrase($allowedParams['searchPhrase'], $allowedIssueIds);

            if (empty($allowedParams['issueIds'])) {
                $dateStart = empty($allowedParams['dateStart']) ? StatisticsHelper::STATISTICS_EARLIEST_DATE : $allowedParams['dateStart'];
                $dateEnd = empty($allowedParams['dateEnd']) ? date('Ymd', strtotime('yesterday')) : $allowedParams['dateEnd'];
                $emptyTimeline = Services::get('issueStats')->getEmptyTimelineIntervals($dateStart, $dateEnd, $allowedParams['timelineInterval']);
                return $response->withJson($emptyTimeline, 200);
            }
        }

        $data = Services::get('issueStats')->getTimeline($allowedParams['timelineInterval'], $allowedParams);

        return $response->withJson($data, 200);
    }

    /**
     * Get a single issue's usage statistics
     */
    public function get(SlimHttpRequest $slimRequest, APIResponse $response, array $args): APIResponse
    {
        $request = $this->getRequest();

        if (!$request->getContext()) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);
        // No need to check if $issue is set, the function authorize does it

        $allowedParams = $this->_processAllowedParams($slimRequest->getQueryParams(), [
            'dateStart',
            'dateEnd',
        ]);

        HookRegistry::call('API::stats::issue::params', [&$allowedParams, $slimRequest]);

        $result = $this->_validateStatDates($allowedParams);
        if ($result !== true) {
            return $response->withStatus(400)->withJsonError($result);
        }

        $allowedParams['issueIds'] = [$issue->getId()];
        $allowedParams['contextIds'] = $request->getContext()->getId();

        $statsService = Services::get('issueStats');
        $metricsByType = $statsService->getMetricsByType($allowedParams);

        $tocViews = $galleyViews = 0;
        $tocRecord = array_filter($metricsByType, [$statsService, 'filterRecordTOC']);
        if (!empty($tocRecord)) {
            $tocViews = (int) current($tocRecord)->metric;
        }
        $galleyRecord = array_filter($metricsByType, [$statsService, 'filterRecordIssueGalley']);
        if (!empty($galleyRecord)) {
            $galleyViews = (int) current($galleyRecord)->metric;
        }

        return $response->withJson([
            'tocViews' => $tocViews,
            'issueGalleyViews' => $galleyViews,
            'issue' => Repo::issue()->getSchemaMap()->mapToStats($issue),
        ], 200);
    }

    /**
     * Get the total TOC views for an issue broken down by
     * month or day
     */
    public function getToc(SlimHttpRequest $slimRequest, APIResponse $response, array $args): APIResponse
    {
        $request = $this->getRequest();

        if (!$request->getContext()) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);
        // No need to check if $issue is set, the function authorize does it

        $defaultParams = [
            'timelineInterval' => StatisticsHelper::STATISTICS_DIMENSION_MONTH,
        ];

        $requestParams = array_merge($defaultParams, $slimRequest->getQueryParams());

        $allowedParams = $this->_processAllowedParams($requestParams, [
            'dateStart',
            'dateEnd',
            'timelineInterval',
        ]);

        $allowedParams['contextIds'] = $request->getContext()->getId();
        $allowedParams['issueIds'] = $issue->getId();
        $allowedParams['assocTypes'] = Application::ASSOC_TYPE_ISSUE;

        HookRegistry::call('API::stats::issue::toc::params', [&$allowedParams, $slimRequest]);

        $result = $this->_validateStatDates($allowedParams);
        if ($result !== true) {
            return $response->withStatus(400)->withJsonError($result);
        }

        $statsService = Services::get('issueStats');
        $data = $statsService->getTimeline($allowedParams['timelineInterval'], $allowedParams);

        return $response->withJson($data, 200);
    }

    /**
     * Get the total galley views for an issue broken down by
     * month or day
     */
    public function getGalley(SlimHttpRequest $slimRequest, APIResponse $response, array $args): APIResponse
    {
        $request = $this->getRequest();

        if (!$request->getContext()) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);
        // No need to check if $issue is set, the function authorize does it

        $defaultParams = [
            'timelineInterval' => StatisticsHelper::STATISTICS_DIMENSION_MONTH,
        ];

        $requestParams = array_merge($defaultParams, $slimRequest->getQueryParams());

        $allowedParams = $this->_processAllowedParams($requestParams, [
            'dateStart',
            'dateEnd',
            'timelineInterval',
        ]);

        $allowedParams['contextIds'] = $request->getContext()->getId();
        $allowedParams['issueIds'] = $issue->getId();
        $allowedParams['assocTypes'] = Application::ASSOC_TYPE_ISSUE_GALLEY;

        HookRegistry::call('API::stats::issue::galley::params', [&$allowedParams, $slimRequest]);

        $result = $this->_validateStatDates($allowedParams);
        if ($result !== true) {
            return $response->withStatus(400)->withJsonError($result);
        }

        $statsService = Services::get('issueStats');
        $data = $statsService->getTimeline($allowedParams['timelineInterval'], $allowedParams);

        return $response->withJson($data, 200);
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
                    $returnParams[$requestParam] = array_map('intval', $value);
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
        $searchPhraseIssueIds = Repo::issue()->getIds(
            Repo::issue()
                ->getCollector()
                ->filterByContextIds([Application::get()->getRequest()->getContext()->getId()])
                ->filterByPublished(true)
                ->searchPhrase($searchPhrase)
        );

        if (!empty($issueIds)) {
            $issueIds = array_intersect($issueIds, $searchPhraseIssueIds->toArray());
        } else {
            $issueIds = $searchPhraseIssueIds->toArray();
        }
        return $issueIds;
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
            __('stats.issueTOCViews'),
            __('stats.issueGalleyViews')
        ];
    }

    /**
     * Get CSV row with issue metrics
     */
    protected function getCSVItem(int $issueId, int $tocViews, int $issueGalleyViews, int $totalViews): array
    {
        // Get issue identification for display
        // Now that we use foreign keys, the stats should not exist for deleted issues, but consider it however?
        $identification = '';
        $issue = Repo::issue()->get($issueId);
        if ($issue) {
            $identification = $issue->getIssueIdentification();
        }
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
    protected function getJSONItem(int $issueId, int $tocViews, int $issueGalleyViews, int $totalViews): array
    {
        // Get basic issue details for display
        // Now that we use foreign keys, the stats should not exist for deleted issues, but consider it however?
        $issueProps = ['id' => $issueId];
        $issue = Repo::issue()->get($issueId);
        if ($issue) {
            $issueProps = Repo::issue()->getSchemaMap()->mapToStats($issue);
        }
        return [
            'totalViews' => $totalViews,
            'tocViews' => $tocViews,
            'issueGalleyViews' => $issueGalleyViews,
            'issue' => $issueProps,
        ];
    }
}

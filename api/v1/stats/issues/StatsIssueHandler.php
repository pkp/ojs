<?php

/**
 * @file api/v1/stats/StatsIssueHandler.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsIssueHandler
 *
 * @ingroup api_v1_stats
 *
 * @brief Handle API requests for issue statistics.
 *
 */

namespace APP\API\v1\stats\issues;

use APP\core\Application;
use APP\core\Services;
use APP\facades\Repo;
use APP\security\authorization\OjsIssueRequiredPolicy;
use APP\statistics\StatisticsHelper;
use PKP\core\APIResponse;
use PKP\handler\APIHandler;
use PKP\plugins\Hook;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\authorization\PolicySet;
use PKP\security\authorization\RoleBasedHandlerOperationPolicy;
use PKP\security\authorization\UserRolesRequiredPolicy;
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
                    'pattern' => $this->getEndpointPattern() . '/timeline',
                    'handler' => [$this, 'getManyTimeline'],
                    'roles' => $roles
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/{issueId:\d+}',
                    'handler' => [$this, 'get'],
                    'roles' => $roles
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/{issueId:\d+}/timeline',
                    'handler' => [$this, 'getTimeline'],
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

        $this->addPolicy(new UserRolesRequiredPolicy($request), true);

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

        Hook::call('API::stats::issues::params', [&$allowedParams, $slimRequest]);

        $allowedParams['contextIds'] = [$request->getContext()->getId()];

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
                    return $response->withCSV([], $csvColumnNames, 0);
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
            return $response->withCSV($items, $csvColumnNames, $itemsMax);
        } else {
            return $response->withJson([
                'items' => $items,
                'itemsMax' => $itemsMax,
            ], 200);
        }
    }

    /**
     * Get the total TOC or issue galley views for a set of issues
     * in a timeline broken down by month or day
     */
    public function getManyTimeline(SlimHttpRequest $slimRequest, APIResponse $response, array $args): APIResponse
    {
        $responseCSV = str_contains($slimRequest->getHeaderLine('Accept'), APIResponse::RESPONSE_CSV) ? true : false;

        $request = $this->getRequest();

        $defaultParams = [
            'timelineInterval' => StatisticsHelper::STATISTICS_DIMENSION_MONTH,
        ];

        $requestParams = array_merge($defaultParams, $slimRequest->getQueryParams());

        $allowedParams = $this->_processAllowedParams($requestParams, [
            'dateStart',
            'dateEnd',
            'timelineInterval',
            'searchPhrase',
            'type'
        ]);

        Hook::call('API::stats::issues::timeline::params', [&$allowedParams, $slimRequest]);

        if (!$this->isValidTimelineInterval($allowedParams['timelineInterval'])) {
            return $response->withStatus(400)->withJsonError('api.stats.400.wrongTimelineInterval');
        }

        $result = $this->_validateStatDates($allowedParams);
        if ($result !== true) {
            return $response->withStatus(400)->withJsonError($result);
        }

        $allowedParams['contextIds'] = [$request->getContext()->getId()];
        $allowedParams['assocTypes'] = [Application::ASSOC_TYPE_ISSUE];
        if (array_key_exists('type', $allowedParams) && $allowedParams['type'] == 'files') {
            $allowedParams['assocTypes'] = [Application::ASSOC_TYPE_ISSUE_GALLEY];
        };

        // Identify issues which should be included in the results when a searchPhrase is passed
        if (!empty($allowedParams['searchPhrase'])) {
            $allowedIssueIds = empty($allowedParams['issueIds']) ? [] : $allowedParams['issueIds'];
            $allowedParams['issueIds'] = $this->_processSearchPhrase($allowedParams['searchPhrase'], $allowedIssueIds);

            if (empty($allowedParams['issueIds'])) {
                $dateStart = empty($allowedParams['dateStart']) ? StatisticsHelper::STATISTICS_EARLIEST_DATE : $allowedParams['dateStart'];
                $dateEnd = empty($allowedParams['dateEnd']) ? date('Ymd', strtotime('yesterday')) : $allowedParams['dateEnd'];
                $emptyTimeline = Services::get('issueStats')->getEmptyTimelineIntervals($dateStart, $dateEnd, $allowedParams['timelineInterval']);
                if ($responseCSV) {
                    $csvColumnNames = Services::get('issueStats')->getTimelineReportColumnNames();
                    return $response->withCSV($emptyTimeline, $csvColumnNames, 0);
                }
                return $response->withJson($emptyTimeline, 200);
            }
        }

        $data = Services::get('issueStats')->getTimeline($allowedParams['timelineInterval'], $allowedParams);
        if ($responseCSV) {
            $csvColumnNames = Services::get('issueStats')->getTimelineReportColumnNames();
            return $response->withCSV($data, $csvColumnNames, count($data));
        }
        return $response->withJson($data, 200);
    }

    /**
     * Get a single issue's usage statistics
     */
    public function get(SlimHttpRequest $slimRequest, APIResponse $response, array $args): APIResponse
    {
        $request = $this->getRequest();

        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);

        $allowedParams = $this->_processAllowedParams($slimRequest->getQueryParams(), [
            'dateStart',
            'dateEnd',
        ]);

        Hook::call('API::stats::issue::params', [&$allowedParams, $slimRequest]);

        $result = $this->_validateStatDates($allowedParams);
        if ($result !== true) {
            return $response->withStatus(400)->withJsonError($result);
        }

        $statsService = Services::get('issueStats');
        $dateStart = array_key_exists('dateStart', $allowedParams) ? $allowedParams['dateStart'] : null;
        $dateEnd = array_key_exists('dateEnd', $allowedParams) ? $allowedParams['dateEnd'] : null;
        $metricsByType = $statsService->getTotalsByType($issue->getId(), $request->getContext()->getId(), $dateStart, $dateEnd);

        return $response->withJson([
            'tocViews' => $metricsByType['toc'],
            'issueGalleyViews' => $metricsByType['galley'],
            'issue' => Repo::issue()->getSchemaMap()->mapToStats($issue),
        ], 200);
    }

    /**
     * Get the total TOC or issue galley views for an issue broken down by
     * month or day
     */
    public function getTimeline(SlimHttpRequest $slimRequest, APIResponse $response, array $args): APIResponse
    {
        $request = $this->getRequest();

        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);

        $defaultParams = [
            'timelineInterval' => StatisticsHelper::STATISTICS_DIMENSION_MONTH,
        ];

        $requestParams = array_merge($defaultParams, $slimRequest->getQueryParams());

        $allowedParams = $this->_processAllowedParams($requestParams, [
            'dateStart',
            'dateEnd',
            'timelineInterval',
            'type'
        ]);

        Hook::call('API::stats::issue::timeline::params', [&$allowedParams, $slimRequest]);

        $allowedParams['contextIds'] = [$request->getContext()->getId()];
        $allowedParams['issueIds'] = [$issue->getId()];
        $allowedParams['assocTypes'] = [Application::ASSOC_TYPE_ISSUE];
        if (array_key_exists('type', $allowedParams) && $allowedParams['type'] == 'files') {
            $allowedParams['assocTypes'] = [Application::ASSOC_TYPE_ISSUE_GALLEY];
        };

        if (!$this->isValidTimelineInterval($allowedParams['timelineInterval'])) {
            return $response->withStatus(400)->withJsonError('api.stats.400.wrongTimelineInterval');
        }

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

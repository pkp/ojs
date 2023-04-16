<?php

/**
 * @file pages/stats/StatsHandler.php
 *
 * Copyright (c) 2013-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsHandler
 *
 * @ingroup pages_stats
 *
 * @brief Handle requests for statistics pages.
 */

namespace APP\pages\stats;

use APP\core\Application;
use APP\core\Request;
use APP\core\Services;
use APP\facades\Repo;
use APP\template\TemplateManager;
use PKP\core\PKPApplication;
use PKP\pages\stats\PKPStatsHandler;
use PKP\plugins\Hook;
use PKP\security\Role;
use PKP\statistics\PKPStatisticsHelper;

class StatsHandler extends PKPStatsHandler
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR],
            ['issues']
        );
        Hook::add('TemplateManager::display', [$this, 'addSectionFilters']);
    }

    /**
     * Display issues stats
     *
     */
    public function issues(array $args, Request $request): void
    {
        $dispatcher = $request->getDispatcher();
        $context = $request->getContext();

        if (!$context) {
            $dispatcher->handle404();
        }

        $templateMgr = TemplateManager::getManager($request);
        $this->setupTemplate($request);

        $dateStart = date('Y-m-d', strtotime('-31 days'));
        $dateEnd = date('Y-m-d', strtotime('yesterday'));
        $count = 30;

        $timeline = Services::get('issueStats')->getTimeline(PKPStatisticsHelper::STATISTICS_DIMENSION_DAY, [
            'assocTypes' => [Application::ASSOC_TYPE_ISSUE],
            'contextIds' => [$context->getId()],
            'count' => $count,
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
        ]);

        $statsComponent = new \APP\components\StatsIssuePage(
            $dispatcher->url($request, PKPApplication::ROUTE_API, $context->getPath(), 'stats/issues'),
            [
                'timeline' => $timeline,
                'timelineInterval' => PKPStatisticsHelper::STATISTICS_DIMENSION_DAY,
                'timelineType' => 'toc',
                'tableColumns' => [
                    [
                        'name' => 'title',
                        'label' => __('issue.issue'),
                    ],
                    [
                        'name' => 'tocViews',
                        'label' => __('stats.views'),
                        'value' => 'tocViews',
                    ],
                    [
                        'name' => 'issueGalleyViews',
                        'label' => __('stats.downloads'),
                        'value' => 'issueGalleyViews',
                    ],
                    [
                        'name' => 'total',
                        'label' => __('stats.total'),
                        'value' => 'totalViews',
                        'orderBy' => 'totalViews',
                        'initialOrderDirection' => true,
                    ],
                ],
                'count' => $count,
                'dateStart' => $dateStart,
                'dateEnd' => $dateEnd,
                'dateRangeOptions' => [
                    [
                        'dateStart' => $dateStart,
                        'dateEnd' => $dateEnd,
                        'label' => __('stats.dateRange.last30Days'),
                    ],
                    [
                        'dateStart' => date('Y-m-d', strtotime('-91 days')),
                        'dateEnd' => $dateEnd,
                        'label' => __('stats.dateRange.last90Days'),
                    ],
                    [
                        'dateStart' => date('Y-m-d', strtotime('-12 months')),
                        'dateEnd' => $dateEnd,
                        'label' => __('stats.dateRange.last12Months'),
                    ],
                    [
                        'dateStart' => '',
                        'dateEnd' => '',
                        'label' => __('stats.dateRange.allDates'),
                    ],
                ],
                'orderBy' => 'total',
                'orderDirection' => true,
            ]
        );
        $templateMgr->setState($statsComponent->getConfig());
        $templateMgr->assign([
            'pageComponent' => 'StatsIssuesPage',
            'pageTitle' => __('stats.issueStats'),
            'pageWidth' => TemplateManager::PAGE_WIDTH_WIDE
        ]);

        $templateMgr->display('stats/issues.tpl');
    }

    /**
     * Add OJS-specific configuration options to the stats component data
     *
     * Fired when the `TemplateManager::display` hook is called.
     *
     * @param array $args [$templateMgr, $template, $sendContentType, $charset, $output]
     */
    public function addSectionFilters($hookName, $args)
    {
        $templateMgr = $args[0];
        $template = $args[1];

        if (!in_array($template, ['stats/publications.tpl', 'stats/editorial.tpl'])) {
            return;
        }

        $context = Application::get()->getRequest()->getContext();

        $filters = $templateMgr->getState('filters');
        if (is_null($filters)) {
            $filters = [];
        }
        $sections = Repo::section()->getSectionList($context->getId());
        $filters[] = [
            'heading' => __('section.sections'),
            'filters' => array_map(function ($section) {
                return [
                    'param' => 'sectionIds',
                    'value' => (int) $section['id'],
                    'title' => $section['title'],
                ];
            }, $sections),
        ];
        if ($template == 'stats/publications.tpl') {
            $issues = Repo::issue()->getCollector()
                ->filterByContextIds([$context->getId()])
                ->filterByPublished(true)
                ->getMany();
            $filters[] = [
                'heading' => __('issue.issues'),
                'filters' => $issues->map(function ($issue, $key) {
                    return [
                        'param' => 'issueIds',
                        'value' => (int) $issue->getId(),
                        'title' => $issue->getIssueIdentification(),
                    ];
                })->toArray(),
            ];
        }
        $templateMgr->setState([
            'filters' => $filters
        ]);
    }
}

<?php

/**
 * @file pages/stats/StatsHandler.php
 *
 * Copyright (c) 2013-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsHandler
 * @ingroup pages_stats
 *
 * @brief Handle requests for statistics pages.
 */

namespace APP\pages\stats;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\pages\stats\PKPStatsHandler;
use PKP\plugins\HookRegistry;

class StatsHandler extends PKPStatsHandler
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        HookRegistry::register('TemplateManager::display', [$this, 'addSectionFilters']);
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
        $sections = \Services::get('section')->getSectionList($context->getId());
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
        $templateMgr->setState([
            'filters' => $filters
        ]);
    }
}

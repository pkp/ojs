<?php

/**
 * @file classes/components/listPanels/DoiListPanel.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DoiListPanel
 *
 * @ingroup classes_components_list
 *
 * @brief A ListPanel component for viewing and editing DOIs
 */

namespace APP\components\listPanels;

use APP\components\forms\FieldSelectIssues;
use APP\core\Application;
use APP\template\TemplateManager;
use PKP\components\listPanels\PKPDoiListPanel;
use PKP\core\PKPApplication;

class DoiListPanel extends PKPDoiListPanel
{
    /** @var bool Whether objects being passed to DOI List Panel are submissions or not */
    public $isSubmission = true;

    /** @var boolean Whether to show issue filters */
    public $includeIssuesFilter = false;

    /**
     * Add any application-specific config to the list panel setup
     */
    protected function setAppConfig(array &$config): void
    {
        if ($this->isSubmission) {
            $config['executeActionApiUrl'] = $this->doiApiUrl . '/submissions';
        } else {
            $config['executeActionApiUrl'] = $this->doiApiUrl . '/issues';
            // Overwrite default submission published statuses for issue-specific ones
            $config['publishedStatuses'] = [
                'name' => 'isPublished',
                'published' => 1,
                'unpublished' => 0,
            ];
        }

        if ($this->includeIssuesFilter) {
            $request = Application::get()->getRequest();
            $issueAutosuggestField = new FieldSelectIssues('issueIds', [
                'label' => __('issue.issues'),
                'value' => [],
                'apiUrl' => $request->getDispatcher()->url($request, PKPApplication::ROUTE_API, $request->getContext()->getPath(), 'issues'),
            ]);
            $config['filters'][] = [
                'filters' => [
                    [
                        'title' => __('issue.issues'),
                        'param' => 'issueIds',
                        'value' => [],
                        'filterType' => 'pkp-filter-autosuggest',
                        'component' => 'field-select-issues',
                        'autosuggestProps' => $issueAutosuggestField->getConfig(),
                    ]
                ]
            ];
        }

        // Provide required locale keys
        $request = Application::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->setLocaleKeys([
            'article.article',
            'issue.issue'
        ]);
    }
}

<?php
/**
 * @file components/listPanels/SubmissionsListPanel.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionsListPanel
 * @ingroup classes_components_listPanels
 *
 * @brief Instantiates and manages a UI component to list submissions.
 */

namespace APP\components\listPanels;
use \PKP\components\listPanels\PKPSubmissionsListPanel;

class SubmissionsListPanel extends PKPSubmissionsListPanel {

    public $includeIssuesFilter = false;

    /**
     * @copydoc PKPSubmissionsListPanel::getConfig()
     */
    public function getConfig() {
        $config = parent::getConfig();

        $request = \Application::get()->getRequest();
        if ($request->getContext()) {
            // Add section filters above last activity filter
            array_splice($config['filters'], 2, 0, [[
                'heading' => __('section.sections'),
                'filters' => self::getSectionFilters(),
            ]]);
        }

        $context = $request->getContext();
        if ($this->includeIssuesFilter) {
            $config['filters'][] = [
                "filters" => [
                    [
                    'title' => _('issues'),
                    'param' => 'issueIds',
                    'value' => [],
                    'filterType' => 'pkp-filter-autosuggest',
                    'component' => 'field-select-issues',
                    'autosuggestProps' => [
                        'allErrors' => (object) [],
                        'apiUrl' => $request->getDispatcher()->url($request, ROUTE_API, $context->getPath(), 'issues', null, null, ['roleIds' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR]]),
                        'description' => '',
                        'deselectLabel' => __('common.removeItem'),
                        'formId' => 'default',
                        'groupId' => 'default',
                        'initialPosition' => 'inline',
                        'isRequired' => false,
                        'label' => __('issues.submissions.issueIds'),
                        'locales' => [],
                        'name' => 'issueIds',
                        'primaryLocale' => 'en_US',
                        'selectedLabel' => __('common.assigned'),
                        'value' => [],
                        ]
                    ]
                ]
            ];
        }
        return $config;
    }

    /**
     * Get an array of workflow stages supported by the current app
     *
     * @return array
     */
    public function getWorkflowStages() {
        return array(
            array(
                'param' => 'stageIds',
                'value' => WORKFLOW_STAGE_ID_SUBMISSION,
                'title' => __('manager.publication.submissionStage'),
            ),
            array(
                'param' => 'stageIds',
                'value' => WORKFLOW_STAGE_ID_EXTERNAL_REVIEW,
                'title' => __('manager.publication.reviewStage'),
            ),
            array(
                'param' => 'stageIds',
                'value' => WORKFLOW_STAGE_ID_EDITING,
                'title' => __('submission.copyediting'),
            ),
            array(
                'param' => 'stageIds',
                'value' => WORKFLOW_STAGE_ID_PRODUCTION,
                'title' => __('manager.publication.productionStage'),
            ),
        );
    }

    /**
     * Compile the sections for passing as filters
     *
     * @return array
     */
    static function getSectionFilters() {
        $request = \Application::get()->getRequest();
        $context = $request->getContext();

        if (!$context) {
            return [];
        }

        $sections = \Services::get('section')->getSectionList($context->getId());

        return array_map(function($section) {
            return [
                'param' => 'sectionIds',
                'value' => (int) $section['id'],
                'title' => $section['title'],
            ];
        }, $sections);
    }
}

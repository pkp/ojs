<?php
/**
 * @file components/listPanels/SubmissionsListPanel.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionsListPanel
 * @ingroup classes_components_listPanels
 *
 * @brief Instantiates and manages a UI component to list submissions.
 */

namespace APP\components\listPanels;

use APP\components\forms\FieldSelectIssues;
use PKP\components\listPanels\PKPSubmissionsListPanel;
use PKP\components\forms\FieldAutosuggestPreset;

class SubmissionsListPanel extends PKPSubmissionsListPanel {

	/** @var boolean Whether to show inactive section filters */
	public $includeActiveSectionFiltersOnly = false;

	/** @var boolean Whether to show issue filters */
	public $includeIssuesFilter = false;

	/**
	 * @copydoc PKPSubmissionsListPanel::getConfig()
	 */
	public function getConfig() {
		$config = parent::getConfig();

		$request = \Application::get()->getRequest();
		if ($request->getContext()) {
			$config['filters'][] = $this->getSectionFilters($this->includeActiveSectionFiltersOnly);
		}

		if ($this->includeIssuesFilter) {
			$issueAutosuggestField = new FieldSelectIssues('issueIds', [
				'label' => __('issue.issues'),
				'value' => [],
				'apiUrl' => $request->getDispatcher()->url($request, ROUTE_API, $request->getContext()->getPath(), 'issues'),
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
	 * @param $activeOnly boolean show inactive section filters or not
	 * @return array
	 */
	public function getSectionFilters($activeOnly = false) {
		$request = \Application::get()->getRequest();
		$context = $request->getContext();

		$sections = \Services::get('section')->getSectionList($context->getId(), $activeOnly);

		// Use an autosuggest field if the list of submissions is too long
		if (count($sections) > 5) {
			$autosuggestField = new FieldAutosuggestPreset('sectionIds', [
				'label' => __('section.sections'),
				'value' => [],
				'options' => array_map(function($section) {
					return [
						'value' => (int) $section['id'],
						'label' => $section['title'],
					];
				}, $sections),
			]);
			return [
				'filters' => [
					[
						'title' => __('section.sections'),
						'param' => 'sectionIds',
						'filterType' => 'pkp-filter-autosuggest',
						'component' => 'field-autosuggest-preset',
						'value' => [],
						'autosuggestProps' => $autosuggestField->getConfig(),
					]
				],
			];
		}

		return [
			'heading' => __('section.sections'),
			'filters' => array_map(function($section) {
				return [
					'param' => 'sectionIds',
					'value' => (int) $section['id'],
					'title' => $section['title'],
				];
			}, $sections),
		];
	}
}

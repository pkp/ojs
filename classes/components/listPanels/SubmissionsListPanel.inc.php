<?php
/**
 * @file components/listPanels/SubmissionsListPanel.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionsListPanel
 * @ingroup classes_components_listPanels
 *
 * @brief Instantiates and manages a UI component to list submissions.
 */

namespace APP\components\listPanels;
use \PKP\components\listPanels\PKPSubmissionsListPanel;

class SubmissionsListPanel extends PKPSubmissionsListPanel {

	/**
	 * @copydoc PKPSubmissionsListPanel::getConfig()
	 */
	public function getConfig() {
		$config = parent::getConfig();

		$request = \Application::get()->getRequest();
		if ($request->getContext()) {
			$config['filters'][] = [
				'heading' => __('section.sections'),
				'filters' => self::getSectionFilters(),
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
				'value' => $section['id'],
				'title' => $section['title'],
			];
		}, $sections);
	}
}

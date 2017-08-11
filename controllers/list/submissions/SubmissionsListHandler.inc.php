<?php
/**
 * @file controllers/list/submissions/SubmissionsListHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionsListHandler
 * @ingroup classes_controllers_list
 *
 * @brief Instantiates and manages a UI component to list submissions.
 */
import('lib.pkp.controllers.list.submissions.PKPSubmissionsListHandler');
import('lib.pkp.classes.db.DBResultRange');
import('lib.pkp.classes.submission.Submission');

class SubmissionsListHandler extends PKPSubmissionsListHandler {

	/**
	 * @copydoc PKPSubmissionsListHandler::getConfig()
	 */
	public function getConfig() {
		$config = parent::getConfig();

		$request = Application::getRequest();
		if ($request->getContext()) {
			import('classes.core.ServicesContainer');
			$config['sections'] = ServicesContainer::instance()
			->get('section')
			->getSectionList($request->getContext());
		}

		$config['i18n']['sections'] = __('section.sections');

		return $config;
	}

	/**
	 * @copydoc PKPSubmissionsListHandler::getWorkflowStages()
	 */
	public function getWorkflowStages() {
		return array(
			array(
				'id' => WORKFLOW_STAGE_ID_SUBMISSION,
				'title' => __('manager.publication.submissionStage'),
			),
			array(
				'id' => WORKFLOW_STAGE_ID_EXTERNAL_REVIEW,
				'title' => __('manager.publication.reviewStage'),
			),
			array(
				'id' => WORKFLOW_STAGE_ID_EDITING,
				'title' => __('manager.publication.editorialStage'),
			),
			array(
				'id' => WORKFLOW_STAGE_ID_PRODUCTION,
				'title' => __('manager.publication.productionStage'),
			),
		);
	}
}

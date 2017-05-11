<?php

/**
 * @file controllers/grid/submissions/SubmissionsListGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionsListGridHandler
 * @ingroup controllers_grid_submissions
 *
 * @brief Handle submission list grid requests.
 */

// Import grid base classes.
import('lib.pkp.classes.controllers.grid.GridHandler');

// Import submissions list grid specific classes.
import('lib.pkp.controllers.grid.submissions.SubmissionsListGridCellProvider');
import('lib.pkp.controllers.grid.submissions.SubmissionsListGridRow');

// Access decision actions constants.
import('classes.workflow.EditorDecisionActionsManager');

class SubmissionsListGridHandler extends GridHandler {
	/** @var true iff the current user has a managerial role */
	var $_isManager;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		// Load submission-specific translations.
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_COMMON,
			LOCALE_COMPONENT_APP_SUBMISSION,
			LOCALE_COMPONENT_PKP_SUBMISSION
		);

		// Fetch the authorized roles and determine if the user is a manager.
		$authorizedRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
		$this->_isManager = in_array(ROLE_ID_MANAGER, $authorizedRoles);

		// If there is more than one context in the system, add a context column
		$cellProvider = new SubmissionsListGridCellProvider($request->getUser(), $authorizedRoles);
		$this->addColumn(
			new GridColumn(
				'id',
				null,
				__('common.id'),
				'controllers/grid/gridCell.tpl',
				$cellProvider,
				array('alignment' => COLUMN_ALIGNMENT_LEFT,
					'width' => 10)
			)
		);
		$this->addColumn(
			new GridColumn(
				'title',
				'grid.submission.itemTitle',
				null,
				null,
				$cellProvider,
				array('html' => true,
					'alignment' => COLUMN_ALIGNMENT_LEFT)
			)
		);
		$this->addColumn(
			new GridColumn(
				'stage',
				'workflow.stage',
				null,
				null,
				$cellProvider,
				array('alignment' => COLUMN_ALIGNMENT_LEFT,
					'width' => 15)
			)
		);
	}

	/**
	 * @copyDoc GridHandler::getIsSubcomponent()
	 */
	function getIsSubcomponent() {
		return true;
	}

	/**
	 * @copyDoc GridHandler::getFilterForm()
	 */
	protected function getFilterForm() {
		return 'controllers/grid/submissions/submissionsGridFilter.tpl';
	}

	/**
	 * @copyDoc GridHandler::renderFilter()
	 */
	function renderFilter($request, $filterData = array()) {
		// Build a list of workflow stages
		$workflowStages = WorkflowStageDAO::getWorkflowStageTranslationKeys();
		$workflowStages[0] = 'workflow.stage.any';
		ksort($workflowStages);

		// Build a list of sections
		$sectionDao = Application::getSectionDAO();
		$sectionsIterator = $sectionDao->getByContextId($request->getContext()->getId());
		$sections = array(0 => __('section.any'));
		while ($section = $sectionsIterator->next()) {
			$sections[$section->getId()] = $section->getLocalizedTitle();
		}

				return parent::renderFilter(
			$request,
			array_merge($filterData, array(
				'columns' => $this->getFilterColumns(),
				'workflowStages' => $workflowStages,
				'sections' => $sections,
				'gridId' => $this->getId()
			))
		);
	}

	/**
	 * @copyDoc GridHandler::getFilterSelectionData()
	 */
	function getFilterSelectionData($request) {
		$search = (string) $request->getUserVar('search');
		$column = (string) $request->getUserVar('column');
		$stageId = (int) $request->getUserVar('stageId');
		$sectionId = (int) $request->getUserVar('sectionId');

		return array(
			'search' => $search,
			'column' => $column,
			'stageId' => $stageId,
			'sectionId' => $sectionId,
		);
	}


	//
	// Public handler operations
	//
	/**
	 * Delete a submission
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteSubmission($args, $request) {
		$submissionDao = Application::getSubmissionDAO();
		$submission = $submissionDao->getById(
			(int) $request->getUserVar('submissionId')
		);

		// If the submission is incomplete, or this is a manager, allow it to be deleted
		if ($request->checkCSRF() && $submission && ($this->_isManager || $submission->getSubmissionProgress() != 0)) {
			$submissionDao->deleteById($submission->getId());

			$user = $request->getUser();
			NotificationManager::createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.removedSubmission')));
			return DAO::getDataChangedEvent($submission->getId());
		}
		return new JSONMessage(false);
	}


	//
	// Protected methods
	//
	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	protected function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.InfiniteScrollingFeature');
		import('lib.pkp.classes.controllers.grid.feature.CollapsibleGridFeature');
		return array(new InfiniteScrollingFeature('infiniteScrolling', $this->getItemsNumber()), new CollapsibleGridFeature());
	}

	/**
	 * @copydoc GridHandler::getRowInstance()
	 * @return SubmissionsListGridRow
	 */
	protected function getRowInstance() {
		return new SubmissionsListGridRow($this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES));
	}

	/**
	 * Get which columns can be used by users to filter data.
	 * @return Array
	 */
	protected function getFilterColumns() {
		return array(
			'title' => __('submission.title'),
			'author' => __('submission.authors'),
		);
	}

	/**
	 * Process filter values, assigning default ones if
	 * none was set.
	 * @return Array
	 */
	protected function getFilterValues($filter) {
		$search = $column = $stageId = $sectionId = null;
		if (isset($filter['search']) && $filter['search']) {
			$search = $filter['search'];
		}

		if (isset($filter['column']) && $filter['column']) {
			$column = $filter['column'];
		}

		if (isset($filter['stageId']) && $filter['stageId']) {
			$stageId = $filter['stageId'];
		}

		if (isset($filter['sectionId']) && $filter['sectionId']) {
			$sectionId = $filter['sectionId'];
		}

		return array($search, $column, $stageId, $sectionId);
	}

	/**
	 * Define how many items this grid will start loading.
	 * @return int
	 */
	protected function getItemsNumber() {
		return 5;
	}
}

?>

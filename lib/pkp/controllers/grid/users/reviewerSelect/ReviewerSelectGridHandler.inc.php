<?php

/**
 * @file controllers/grid/users/reviewerSelect/ReviewerSelectGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerSelectGridHandler
 * @ingroup controllers_grid_users_reviewerSelect
 *
 * @brief Handle reviewer selector grid requests.
 */

// import grid base classes
import('lib.pkp.classes.controllers.grid.GridHandler');


// import author grid specific classes
import('lib.pkp.controllers.grid.users.reviewerSelect.ReviewerSelectGridCellProvider');

class ReviewerSelectGridHandler extends GridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();

		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER, ROLE_ID_ASSISTANT),
			array('fetchGrid', 'fetchRows')
		);
	}

	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$stageId = (int)$request->getUserVar('stageId');

		import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy');
		$this->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $stageId));

		import('lib.pkp.classes.security.authorization.internal.ReviewRoundRequiredPolicy');
		$this->addPolicy(new ReviewRoundRequiredPolicy($request, $args));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_PKP_MANAGER,
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_PKP_EDITOR,
			LOCALE_COMPONENT_APP_EDITOR
		);

		$this->setTitle('editor.submission.findAndSelectReviewer');

		// Columns
		$cellProvider = new ReviewerSelectGridCellProvider();
		$this->addColumn(
			new GridColumn(
				'select',
				'',
				null,
				'controllers/grid/users/reviewerSelect/reviewerSelectRadioButton.tpl',
				$cellProvider,
				array('width' => 5)
			)
		);
		$this->addColumn(
			new GridColumn(
				'name',
				'author.users.contributor.name',
				null,
				null,
				$cellProvider,
				array('alignment' => COLUMN_ALIGNMENT_LEFT,
					'width' => 30
				)
			)
		);
		$this->addColumn(
			new GridColumn(
				'done',
				'common.done',
				null,
				null,
				$cellProvider
			)
		);
		$this->addColumn(
			new GridColumn(
				'avg',
				'editor.review.days',
				null,
				null,
				$cellProvider
			)
		);
		$this->addColumn(
			new GridColumn(
				'last',
				'editor.submissions.lastAssigned',
				null,
				null,
				$cellProvider
			)
		);
		$this->addColumn(
			new GridColumn(
				'active',
				'common.active',
				null,
				null,
				$cellProvider
			)
		);
		$this->addColumn(
			new GridColumn(
				'interests',
				'user.interests',
				null,
				null,
				$cellProvider,
				array('width' => 20)
			)
		);
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.InfiniteScrollingFeature');
		import('lib.pkp.classes.controllers.grid.feature.CollapsibleGridFeature');
		return array(new InfiniteScrollingFeature('infiniteScrolling', $this->getItemsNumber()), new CollapsibleGridFeature());
	}

	/**
	 * @copydoc GridHandler::renderFilter()
	 */
	function renderFilter($request) {
		return parent::renderFilter($request, $this->getFilterSelectionData($request));
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		$interests = (array) $filter['interestSearchKeywords'];
		$reviewerValues = $filter['reviewerValues'];

		// Retrieve the authors associated with this submission to be displayed in the grid
		$name = $reviewerValues['name'];
		$doneEnabled = $reviewerValues['doneEnabled'];
		$doneMin = $doneEnabled ? $reviewerValues['doneMin'] : null;
		$doneMax = $doneEnabled ? $reviewerValues['doneMax'] : null;
		$avgEnabled = $reviewerValues['avgEnabled'];
		$avgMin = $avgEnabled ? $reviewerValues['avgMin'] : null;
		$avgMax = $avgEnabled ? $reviewerValues['avgMax'] : null;
		$lastEnabled = $reviewerValues['lastEnabled'];
		$lastMin = $lastEnabled ? $reviewerValues['lastMin'] : null;
		$lastMax = $lastEnabled ? $reviewerValues['lastMax'] : null;
		$activeEnabled = $reviewerValues['activeEnabled'];
		$activeMin = $activeEnabled ? $reviewerValues['activeMin'] : null;
		$activeMax = $activeEnabled ? $reviewerValues['activeMax'] : null;

		$userDao = DAORegistry::getDAO('UserDAO');
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$reviewRound = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ROUND);
		$rangeInfo = $this->getGridRangeInfo($request, $this->getId());

		$previousReviewRounds = $filter['previousReviewRounds'];
		$round = $previousReviewRounds ? $reviewRound->getRound() : null;
		return $userDao->getFilteredReviewers(
			$submission->getContextId(), $reviewRound->getStageId(), $name,
			$doneMin, $doneMax, $avgMin, $avgMax, $lastMin, $lastMax,
			$activeMin, $activeMax, $interests,
			$submission->getId(), $reviewRound->getId(), $round, $rangeInfo
		);
	}

	/**
	 * @copydoc GridHandler::getFilterSelectionData()
	 * @return array Filter selection data.
	 */
	function getFilterSelectionData($request) {
		$form = $this->getFilterForm();

		// Only read form data if the clientSubmit flag has been checked
		$clientSubmit = (boolean) $request->getUserVar('clientSubmit');

		$form->readInputData();
		if ($clientSubmit && $form->validate()) {
			return $form->getFilterSelectionData();
		} else {
			return array(
				'reviewerValues' => array(
					'name' => null,
					'doneEnabled' => null,
					'doneMin' => null,
					'doneMax' => null,
					'avgEnabled' => null,
					'avgMin' => null,
					'avgMax' => null,
					'lastEnabled' => null,
					'lastMin' => null,
					'lastMax' => null,
					'activeEnabled' => null,
					'activeMin' => null,
					'activeMax' => null,
				),
				'interestSearchKeywords' => array(),
				'previousReviewRounds' => null,
			);
		}
	}

	/**
	 * @copydoc GridHandler::getFilterForm()
	 * @return Form
	 */
	protected function getFilterForm() {
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		$reviewRound = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ROUND);
		import('lib.pkp.controllers.grid.users.reviewerSelect.form.AdvancedSearchReviewerFilterForm');
		return new AdvancedSearchReviewerFilterForm($submission, $stageId, $reviewRound->getId(), $reviewRound->getRound());
	}

	/**
	 * @copydoc GridHandler::getRequestArgs()
	 */
	function getRequestArgs() {
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		$reviewRound = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ROUND);
		return array(
				'submissionId' => $submission->getId(),
				'stageId' => $stageId,
				'reviewRoundId' => $reviewRound->getId(),
		);
	}

	/**
	 * Determine whether a filter form should be collapsible.
	 * @return boolean
	 */
	protected function isFilterFormCollapsible() {
		return false;
	}

	/**
	 * Define how many items this grid will start loading.
	 * @return int
	 */
	protected function getItemsNumber() {
		return 20;
	}
}

?>

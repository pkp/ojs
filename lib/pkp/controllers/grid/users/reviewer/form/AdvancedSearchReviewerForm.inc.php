<?php

/**
 * @file controllers/grid/users/reviewer/form/AdvancedSearchReviewerForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdvancedSearchReviewerForm
 * @ingroup controllers_grid_users_reviewer_form
 *
 * @brief Form for an advanced search and for adding a reviewer to a submission.
 */

import('lib.pkp.controllers.grid.users.reviewer.form.ReviewerForm');

class AdvancedSearchReviewerForm extends ReviewerForm {
	/**
	 * Constructor.
	 * @param $submission Submission
	 * @param $reviewRound ReviewRound
	 */
	function __construct($submission, $reviewRound) {
		parent::__construct($submission, $reviewRound);
		$this->setTemplate('controllers/grid/users/reviewer/form/advancedSearchReviewerForm.tpl');

		$this->addCheck(new FormValidator($this, 'reviewerId', 'required', 'editor.review.mustSelect'));
	}

	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData() {
		parent::readInputData();

		$this->readUserVars(array('reviewerId'));
	}

	/**
	 * Fetch the form.
	 * @see Form::fetch()
	 * @param $request PKPRequest
	 */
	function fetch($request) {
		// Pass along the request vars
		$actionArgs = $request->getUserVars();
		$reviewRound = $this->getReviewRound();
		$actionArgs['reviewRoundId'] = $reviewRound->getId();
		$actionArgs['selectionType'] = REVIEWER_SELECT_ADVANCED_SEARCH;
		// but change the selectionType for each action
		$advancedSearchAction = new LinkAction(
			'advancedSearch',
			new AjaxAction($request->url(null, null, 'reloadReviewerForm', null, $actionArgs)),
			__('manager.reviewerSearch.change'),
			'user_search'
		);

		$this->setReviewerFormAction($advancedSearchAction);

		// Only add actions to forms where user can operate.
		if (array_intersect($this->getUserRoles(), array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR))) {
			$actionArgs['selectionType'] = REVIEWER_SELECT_CREATE;
			// but change the selectionType for each action
			$advancedSearchAction = new LinkAction(
				'selectCreate',
				new AjaxAction($request->url(null, null, 'reloadReviewerForm', null, $actionArgs)),
				__('editor.review.createReviewer'),
				'add_user'
			);

			$this->setReviewerFormAction($advancedSearchAction);
			$actionArgs['selectionType'] = REVIEWER_SELECT_ENROLL_EXISTING;
			// but change the selectionType for each action
			$advancedSearchAction = new LinkAction(
				'enrolExisting',
				new AjaxAction($request->url(null, null, 'reloadReviewerForm', null, $actionArgs)),
				__('editor.review.enrollReviewer.short'),
				'enroll_user'
			);

			$this->setReviewerFormAction($advancedSearchAction);
		}

		return parent::fetch($request);
	}
}

?>

<?php

/**
 * @file controllers/review/linkAction/ReviewNotesLinkAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewInfoCenterLinkAction
 * @ingroup controllers_review_linkAction
 *
 * @brief An action to open up the review notes for a review assignments.
 */

import('lib.pkp.classes.linkAction.LinkAction');

class ReviewNotesLinkAction extends LinkAction {

	/**
	 * Constructor
	 * @param $request Request
	 * @param $reviewAssignment ReviewAssignment the review assignment
	 * to show information about.
	 * @param $submission Submission The reviewed submission.
	 * @param $user User The user.
	 * @param $isUnread bool Has a review been read
	 */
	function __construct($request, $reviewAssignment, $submission, $user, $isUnread = null) {
		// Instantiate the information center modal.
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$actionArgs = array(
			'submissionId' => $reviewAssignment->getSubmissionId(),
			'reviewAssignmentId' => $reviewAssignment->getId(),
			'stageId' => $reviewAssignment->getStageId()
		);

		$ajaxModal = new AjaxModal(
			$router->url(
				$request, null,
				'grid.users.reviewer.ReviewerGridHandler', 'readReview',
				null, $actionArgs
			),
			__('editor.review') . ': ' . $submission->getLocalizedTitle(),
			'modal_information'
		);

		$viewsDao = DAORegistry::getDAO('ViewsDAO');
		$lastViewDate = $viewsDao->getLastViewDate(ASSOC_TYPE_REVIEW_RESPONSE, $reviewAssignment->getId(), $user->getId());

		$icon = !$lastViewDate || $isUnread ? 'read_new_review' : null;

		// Configure the link action.
		parent::__construct( 'readReview', $ajaxModal, __('editor.review.readReview'), $icon );
	}
}

?>

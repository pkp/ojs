<?php
/**
 * @defgroup controllers_review_linkAction Review Link Actions
 */

/**
 * @file controllers/review/linkAction/UnconsiderReviewLinkAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UnconsiderReviewLinkAction
 * @ingroup controllers_review_linkAction
 *
 * @brief An action to allow editors to unconsider a review.
 */

import('lib.pkp.classes.linkAction.LinkAction');

class UnconsiderReviewLinkAction extends LinkAction {

	/**
	 * Constructor
	 * @param $request Request
	 * @param $reviewAssignment ReviewAssignment the review assignment
	 * to show information about.
	 * @param $submission Submission The reviewed submission.
	 */
	function __construct($request, $reviewAssignment, $submission) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
		parent::__construct(
			'unconsiderReview',
			new RemoteActionConfirmationModal(
				$request->getSession(),
				__('editor.review.unconsiderReviewText'), __('editor.review.unconsiderReview'),
				$router->url(
					$request, null,
					'grid.users.reviewer.ReviewerGridHandler', 'unconsiderReview',
					null,
					array(
						'submissionId' => $reviewAssignment->getSubmissionId(),
						'reviewAssignmentId' => $reviewAssignment->getId(),
						'stageId' => $reviewAssignment->getStageId()
					)
				),
				'modal_information'
			),
			__('editor.review.revertDecision'),
			'unconsider'
		);
	}
}

?>

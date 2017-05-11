<?php

/**
 * @file controllers/tab/workflow/PKPReviewRoundTabHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewRoundTabHandler
 * @ingroup controllers_tab_workflow
 *
 * @brief Handle AJAX operations for review round tabs on review stages workflow pages.
 */

// Import the base Handler.
import('classes.handler.Handler');
import('lib.pkp.classes.core.JSONMessage');

class PKPReviewRoundTabHandler extends Handler {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Extended methods from Handler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		// We need a review round id in request.
		import('lib.pkp.classes.security.authorization.internal.ReviewRoundRequiredPolicy');
		$this->addPolicy(new ReviewRoundRequiredPolicy($request, $args));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * JSON fetch the external review round info (tab).
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function externalReviewRound($args, $request) {
		return $this->_reviewRound($args, $request);
	}


	/**
	 * @see PKPHandler::setupTemplate
	 */
	function setupTemplate($request) {
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);
		parent::setupTemplate($request);
	}


	//
	// Protected helper methods.
	//
	/**
	 * Internal function to handle both internal and external reviews round info (tab content).
	 * @param $request PKPRequest
	 * @param $args array
	 * @return JSONMessage JSON object
	 */
	protected function _reviewRound($args, $request) {
		$this->setupTemplate($request);

		// Retrieve the authorized submission, stage id and review round.
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		$reviewRound = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ROUND);

		// Add the round information to the template.
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('stageId', $stageId);
		$templateMgr->assign('reviewRoundId', $reviewRound->getId());
		$templateMgr->assign('submission', $submission);

		// Assign editor decision actions to the template, only if
		// user is accessing the last review round for this stage.
		$notificationRequestOptions = array(
			NOTIFICATION_LEVEL_NORMAL => array(
				NOTIFICATION_TYPE_REVIEW_ROUND_STATUS => array(ASSOC_TYPE_REVIEW_ROUND, $reviewRound->getId())),
			NOTIFICATION_LEVEL_TASK => array(
				NOTIFICATION_TYPE_ALL_REVIEWS_IN => array(ASSOC_TYPE_REVIEW_ROUND, $reviewRound->getId()),
				NOTIFICATION_TYPE_ALL_REVISIONS_IN => array(ASSOC_TYPE_REVIEW_ROUND, $reviewRound->getId())),
			NOTIFICATION_LEVEL_TRIVIAL => array()
		);
		$templateMgr->assign('reviewRoundNotificationRequestOptions', $notificationRequestOptions);

		return $templateMgr->fetchJson('workflow/reviewRound.tpl');
	}
}

?>

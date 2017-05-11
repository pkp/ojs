<?php

/**
 * @file classes/controllers/grid/users/reviewer/PKPReviewerGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPReviewerGridHandler
 * @ingroup classes_controllers_grid_users_reviewer
 *
 * @brief Handle reviewer grid requests.
 */

// import grid base classes
import('lib.pkp.classes.controllers.grid.GridHandler');

// import reviewer grid specific classes
import('lib.pkp.controllers.grid.users.reviewer.ReviewerGridCellProvider');
import('lib.pkp.controllers.grid.users.reviewer.ReviewerGridRow');

// Reviewer selection types
define('REVIEWER_SELECT_ADVANCED_SEARCH',		0x00000001);
define('REVIEWER_SELECT_CREATE',			0x00000002);
define('REVIEWER_SELECT_ENROLL_EXISTING',		0x00000003);

class PKPReviewerGridHandler extends GridHandler {

	/** @var Submission */
	var $_submission;

	/** @var integer */
	var $_stageId;


	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();

		$allOperations = array_merge($this->_getReviewAssignmentOps(), $this->_getReviewRoundOps());

		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR),
			$allOperations
		);

		// Remove operations related to creation and enrollment of users.
		$assistantOperations = array_flip($allOperations);
		unset($assistantOperations['createReviewer']);
		unset($assistantOperations['enrollReviewer']);
		$assistantOperations = array_flip($assistantOperations);

		$this->addRoleAssignment(
			array(ROLE_ID_ASSISTANT),
			$assistantOperations
		);
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the authorized submission.
	 * @return Submission
	 */
	function getSubmission() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
	}

	/**
	 * Get the review stage id.
	 * @return integer
	 */
	function getStageId() {
		return $this->_stageId;
	}

	/**
	 * Get review round object.
	 * @return ReviewRound
	 */
	function getReviewRound() {
		$reviewRound = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ROUND);
		if (is_a($reviewRound, 'ReviewRound')) {
			return $reviewRound;
		} else {
			$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
			$reviewRoundId = $reviewAssignment->getReviewRoundId();
			$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
			$reviewRound = $reviewRoundDao->getById($reviewRoundId);
			return $reviewRound;
		}
	}


	//
	// Overridden methods from PKPHandler
	//
	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		// Load submission-specific translations
		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_PKP_MANAGER,
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_PKP_EDITOR,
			LOCALE_COMPONENT_PKP_REVIEWER,
			LOCALE_COMPONENT_APP_EDITOR
		);

		$this->setTitle('user.role.reviewers');

		// Grid actions
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$router = $request->getRouter();
		$actionArgs = array_merge($this->getRequestArgs(), array('selectionType' => REVIEWER_SELECT_ADVANCED_SEARCH));
		$this->addAction(
			new LinkAction(
				'addReviewer',
				new AjaxModal(
					$router->url($request, null, null, 'showReviewerForm', null, $actionArgs),
					__('editor.submission.addReviewer'),
					'modal_add_user'
					),
				__('editor.submission.addReviewer'),
				'add_user'
				)
			);

		// Columns
		$cellProvider = new ReviewerGridCellProvider();
		$this->addColumn(
			new GridColumn(
				'name',
				'user.name',
				null,
				null,
				$cellProvider
			)
		);

		// Add a column for the status of the review.
		$this->addColumn(
			new GridColumn(
				'considered',
				'common.status',
				null,
				null,
				$cellProvider,
				array('anyhtml' => true)
			)
		);

		// Add a column for the status of the review.
		$this->addColumn(
			new GridColumn(
				'actions',
				'grid.columns.actions',
				null,
				null,
				$cellProvider
			)
		);
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @see GridHandler::getRowInstance()
	 * @return ReviewerGridRow
	 */
	protected function getRowInstance() {
		return new ReviewerGridRow();
	}

	/**
	 * @see GridHandler::getRequestArgs()
	 */
	function getRequestArgs() {
		$submission = $this->getSubmission();
		$reviewRound = $this->getReviewRound();
		return array(
			'submissionId' => $submission->getId(),
			'stageId' => $this->getStageId(),
			'reviewRoundId' => $reviewRound->getId()
		);
	}

	/**
	 * @see GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		// Get the existing review assignments for this submission
		$reviewRound = $this->getReviewRound();
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		return $reviewAssignmentDao->getByReviewRoundId($reviewRound->getId());
	}


	//
	// Public actions
	//
	/**
	 * Add a reviewer.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function showReviewerForm($args, $request) {
		return new JSONMessage(true, $this->_fetchReviewerForm($args, $request));
	}

	/**
	 * Load the contents of the reviewer form
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function reloadReviewerForm($args, $request) {
		$json = new JSONMessage(true);
		$json->setEvent('refreshForm', $this->_fetchReviewerForm($args, $request));
		return $json;
	}

	/**
	 * Create a new user as reviewer.
	 * @param $args Array
	 * @param $request Request
	 * @return string Serialized JSON object
	 */
	function createReviewer($args, $request) {
		return $this->updateReviewer($args, $request);
	}

	/**
	 * Enroll an existing user as reviewer.
	 * @param $args Array
	 * @param $request Request
	 * @return string Serialized JSON object
	 */
	function enrollReviewer($args, $request) {
		return $this->updateReviewer($args, $request);
	}

	/**
	 * Edit a reviewer
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateReviewer($args, $request) {
		$selectionType = $request->getUserVar('selectionType');
		$formClassName = $this->_getReviewerFormClassName($selectionType);

		// Form handling
		import('lib.pkp.controllers.grid.users.reviewer.form.' . $formClassName );
		$reviewerForm = new $formClassName($this->getSubmission(), $this->getReviewRound());
		$reviewerForm->readInputData();
		if ($reviewerForm->validate()) {
			$reviewAssignment = $reviewerForm->execute($args, $request);
			return DAO::getDataChangedEvent($reviewAssignment->getId());
		} else {
			// There was an error, redisplay the form
			return new JSONMessage(true, $reviewerForm->fetch($request));
		}
	}

	/**
	 * Manage reviewer access to files
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editReview($args, $request) {
		import('lib.pkp.controllers.grid.users.reviewer.form.EditReviewForm');
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
		$editReviewForm = new EditReviewForm($reviewAssignment);
		$editReviewForm->initData();
		return new JSONMessage(true, $editReviewForm->fetch($request));
	}

	/**
	 * Save a change to reviewer access to files
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateReview($args, $request) {
		import('lib.pkp.controllers.grid.users.reviewer.form.EditReviewForm');
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
		$editReviewForm = new EditReviewForm($reviewAssignment);
		$editReviewForm->readInputData();
		if ($editReviewForm->validate()) {
			$editReviewForm->execute();
			return new JSONMessage(true);
		} else {
			return new JSONMessage(false);
		}
	}

	/**
	 * Get a list of all non-reviewer users in the system to populate the reviewer role assignment autocomplete.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function getUsersNotAssignedAsReviewers($args, $request) {
		$context = $request->getContext();
		$term = $request->getUserVar('term');

		$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
		$users = $userGroupDao->getUsersNotInRole(ROLE_ID_REVIEWER, $context->getId(), $term);

		$userList = array();
		while ($user = $users->next()) {
			$userList[] = array('label' => $user->getFullName(), 'value' => $user->getId());
		}

		if (count($userList) == 0) {
			return $this->noAutocompleteResults();
		}

		return new JSONMessage(true, $userList);
	}

	/**
	 * Unassign a reviewer
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function unassignReviewer($args, $request) {
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
		$reviewRound = $this->getReviewRound();
		$submission = $this->getSubmission();

		import('lib.pkp.controllers.grid.users.reviewer.form.UnassignReviewerForm');
		$unassignReviewerForm = new UnassignReviewerForm($reviewAssignment, $reviewRound, $submission);
		$unassignReviewerForm->initData($args, $request);

		return new JSONMessage(true, $unassignReviewerForm->fetch($request));
	}

	/**
	 * Save the reviewer unassignment
	 *
	 * @param mixed $args
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateUnassignReviewer($args, $request) {
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
		$reviewRound = $this->getReviewRound();
		$submission = $this->getSubmission();

		import('lib.pkp.controllers.grid.users.reviewer.form.UnassignReviewerForm');
		$unassignReviewerForm = new UnassignReviewerForm($reviewAssignment, $reviewRound, $submission);
		$unassignReviewerForm->readInputData();

		// Unassign the reviewer and return status message
		if ($unassignReviewerForm->validate()) {
			if ($unassignReviewerForm->execute($args, $request)) {
				return DAO::getDataChangedEvent($reviewAssignment->getId());
			} else {
				return new JSONMessage(false, __('editor.review.errorDeletingReviewer'));
			}
		}
	}

	/**
	 * An action triggered by a confirmation modal to allow an editor to unconsider a review.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function unconsiderReview($args, $request) {
		if (!$request->checkCSRF()) return new JSONMessage(false);

		// This resets the state of the review to 'unread', but does not delete note history.
		$submission = $this->getSubmission();
		$user = $request->getUser();
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');

		$reviewAssignment->setUnconsidered(REVIEW_ASSIGNMENT_UNCONSIDERED);
		$reviewAssignmentDao->updateObject($reviewAssignment);
		$this->_updateReviewRoundStatus($reviewAssignment);

		// log the unconsider.
		import('lib.pkp.classes.log.SubmissionLog');
		import('classes.log.SubmissionEventLogEntry');

		$entry = new SubmissionEventLogEntry();
		$entry->setSubmissionId($reviewAssignment->getSubmissionId());
		$entry->setUserId($user->getId());
		$entry->setDateLogged(Core::getCurrentDate());
		$entry->setEventType(SUBMISSION_LOG_REVIEW_UNCONSIDERED);

		SubmissionLog::logEvent(
			$request,
			$submission,
			SUBMISSION_LOG_REVIEW_UNCONSIDERED,
			'log.review.reviewUnconsidered',
			array(
				'editorName' => $user->getFullName(),
				'submissionId' => $submission->getId(),
				'round' => $reviewAssignment->getRound(),
			)
		);

		return DAO::getDataChangedEvent($reviewAssignment->getId());
	}

	/**
	 * Mark the review as read and trigger a rewrite of the row.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function reviewRead($args, $request) {
		// Retrieve review assignment.
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT); /* @var $reviewAssignment ReviewAssignment */

		// Mark the latest read date of the review by the editor.
		$user = $request->getUser();
		$viewsDao = DAORegistry::getDAO('ViewsDAO');
		$viewsDao->recordView(ASSOC_TYPE_REVIEW_RESPONSE, $reviewAssignment->getId(), $user->getId());

		// if the review assignment had been unconsidered, update the flag.
		if ($reviewAssignment->getUnconsidered() == REVIEW_ASSIGNMENT_UNCONSIDERED) {
			$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignment->setUnconsidered(REVIEW_ASSIGNMENT_UNCONSIDERED_READ);
			$reviewAssignmentDao->updateObject($reviewAssignment);
		}

		if (!$reviewAssignment->getDateCompleted()) {
			// Editor completes the review.
			$reviewAssignment->setDateConfirmed(Core::getCurrentDate());
			$reviewAssignment->setDateCompleted(Core::getCurrentDate());
		}

		$this->_updateReviewRoundStatus($reviewAssignment);

		// Remove the reviewer task.
		$notificationDao = DAORegistry::getDAO('NotificationDAO');
		$notificationDao->deleteByAssoc(
			ASSOC_TYPE_REVIEW_ASSIGNMENT,
			$reviewAssignment->getId(),
			$reviewAssignment->getReviewerId(),
			NOTIFICATION_TYPE_REVIEW_ASSIGNMENT
		);
		$notificationMgr = new NotificationManager();
		$reviewRound = $this->getReviewRound();
		$notificationMgr->updateNotification(
			$request,
			array(NOTIFICATION_TYPE_ALL_REVIEWS_IN),
			null,
			ASSOC_TYPE_REVIEW_ROUND,
			$reviewRound->getId()
		);

		return DAO::getDataChangedEvent($reviewAssignment->getId());
	}

	/**
	 * Displays a modal to allow the editor to enter a message to send to the reviewer as a thank you.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editThankReviewer($args, $request) {
		// Identify the review assignment being updated.
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);

		// Initialize form.
		import('lib.pkp.controllers.grid.users.reviewer.form.ThankReviewerForm');
		$thankReviewerForm = new ThankReviewerForm($reviewAssignment);
		$thankReviewerForm->initData($args, $request);

		// Render form.
		return new JSONMessage(true, $thankReviewerForm->fetch($request));
	}

	/**
	 * Open a modal to read the reviewer's review and
	 * download any files they may have uploaded
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function readReview($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
		$templateMgr->assign(array(
			'submission' => $this->getSubmission(),
			'reviewAssignment' => $reviewAssignment,
			'reviewerRecommendationOptions' =>ReviewAssignment::getReviewerRecommendationOptions(),
		));

		if ($reviewAssignment->getReviewFormId()) {
			// Retrieve review form
			$context = $request->getContext();
			$reviewFormElementDao = DAORegistry::getDAO('ReviewFormElementDAO');
			$reviewFormElements = $reviewFormElementDao->getByReviewFormId($reviewAssignment->getReviewFormId());
			$reviewFormResponseDao = DAORegistry::getDAO('ReviewFormResponseDAO');
			$reviewFormResponses = $reviewFormResponseDao->getReviewReviewFormResponseValues($reviewAssignment->getId());
			$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
			$reviewformid = $reviewAssignment->getReviewFormId();
			$reviewForm = $reviewFormDao->getById($reviewAssignment->getReviewFormId(), Application::getContextAssocType(), $context->getId());
			$templateMgr->assign(array(
				'reviewForm' => $reviewForm,
				'reviewFormElements' => $reviewFormElements,
				'reviewFormResponses' => $reviewFormResponses,
				'disabled' => true,
			));
		} else {
			// Retrieve reviewer comments.
			$submissionCommentDao = DAORegistry::getDAO('SubmissionCommentDAO');
			$templateMgr->assign(array(
				'comments' => $submissionCommentDao->getReviewerCommentsByReviewerId($reviewAssignment->getSubmissionId(), null, $reviewAssignment->getId(), true),
				'commentsPrivate' => $submissionCommentDao->getReviewerCommentsByReviewerId($reviewAssignment->getSubmissionId(), null, $reviewAssignment->getId(), false),
			));
		}


		// Render the response.
		return $templateMgr->fetchJson('controllers/grid/users/reviewer/readReview.tpl');
	}

	/**
	 * Send the acknowledgement email, if desired, and trigger a row refresh action.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function thankReviewer($args, $request) {
		// Identify the review assignment being updated.
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);

		// Form handling
		import('lib.pkp.controllers.grid.users.reviewer.form.ThankReviewerForm');
		$thankReviewerForm = new ThankReviewerForm($reviewAssignment);
		$thankReviewerForm->readInputData();
		if ($thankReviewerForm->validate()) {
			$thankReviewerForm->execute($args, $request);
			$json = new JSONMessage(true);
			// Insert a trivial notification to indicate the reviewer was reminded successfully.
			$currentUser = $request->getUser();
			$notificationMgr = new NotificationManager();
			$messageKey = $thankReviewerForm->getData('skipEmail') ? __('notification.reviewAcknowledged') : __('notification.sentNotification');
			$notificationMgr->createTrivialNotification($currentUser->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => $messageKey));
		} else {
			$json = new JSONMessage(false, __('editor.review.thankReviewerError'));
		}

		$this->_updateReviewRoundStatus($reviewAssignment);
		return DAO::getDataChangedEvent($reviewAssignment->getId());
	}

	/**
	 * Displays a modal to allow the editor to enter a message to send to the reviewer as a reminder
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editReminder($args, $request) {
		// Identify the review assignment being updated.
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);

		// Initialize form.
		import('lib.pkp.controllers.grid.users.reviewer.form.ReviewReminderForm');
		$reviewReminderForm = new ReviewReminderForm($reviewAssignment);
		$reviewReminderForm->initData($args, $request);

		// Render form.
		return new JSONMessage(true, $reviewReminderForm->fetch($request));
	}

	/**
	 * Send the reviewer reminder and close the modal
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function sendReminder($args, $request) {
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);

		// Form handling
		import('lib.pkp.controllers.grid.users.reviewer.form.ReviewReminderForm');
		$reviewReminderForm = new ReviewReminderForm($reviewAssignment);
		$reviewReminderForm->readInputData();
		if ($reviewReminderForm->validate()) {
			$reviewReminderForm->execute($args, $request);
			// Insert a trivial notification to indicate the reviewer was reminded successfully.
			$currentUser = $request->getUser();
			$notificationMgr = new NotificationManager();
			$notificationMgr->createTrivialNotification($currentUser->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.sentNotification')));
			return new JSONMessage(true);
		} else {
			return new JSONMessage(false, __('editor.review.reminderError'));
		}
	}

	/**
	 * Displays a modal to send an email message to the user.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function sendEmail($args, $request) {
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);

		// Form handling.
		import('lib.pkp.controllers.grid.users.reviewer.form.EmailReviewerForm');
		$emailReviewerForm = new EmailReviewerForm($reviewAssignment);
		if (!$request->isPost()) {
			$emailReviewerForm->initData();
			return new JSONMessage(
				true,
				$emailReviewerForm->fetch(
					$request,
					$this->getRequestArgs()
				)
			);
		}
		$emailReviewerForm->readInputData();
		$emailReviewerForm->execute($request, $submission);
		return new JSONMessage(true);
	}


	/**
	 * Displays a modal containing history for the review assignment.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function reviewHistory($args, $request) {
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('reviewAssignment', $reviewAssignment);
		return $templateMgr->fetchJson('workflow/reviewHistory.tpl');
	}


	/**
	 * Fetches an email template's message body and returns it via AJAX.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function fetchTemplateBody($args, $request) {
		import('lib.pkp.classes.mail.SubmissionMailTemplate');
		$template = new SubmissionMailTemplate($this->getSubmission(), $request->getUserVar('template'));
		if (!$template) return;

		$user = $request->getUser();
		$dispatcher = $request->getDispatcher();
		$context = $request->getContext();

		$template->assignParams(array(
			'editorialContactSignature' => $user->getContactSignature(),
			'signatureFullName' => $user->getFullname(),
		));

		return new JSONMessage(true, $template->getBody());
	}


	//
	// Private helper methods
	//
	/**
	 * Return a fetched reviewer form data in string.
	 * @param $args Array
	 * @param $request Request
	 * @return String
	 */
	function _fetchReviewerForm($args, $request) {
		$selectionType = $request->getUserVar('selectionType');
		assert(!empty($selectionType));
		$formClassName = $this->_getReviewerFormClassName($selectionType);
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);

		// Form handling.
		import('lib.pkp.controllers.grid.users.reviewer.form.' . $formClassName );
		$reviewerForm = new $formClassName($this->getSubmission(), $this->getReviewRound());
		$reviewerForm->initData($args, $request);
		$reviewerForm->setUserRoles($userRoles);

		return $reviewerForm->fetch($request);
	}

	/**
	 * Get the name of ReviewerForm class for the current selection type.
	 * @param $selectionType String (const)
	 * @return FormClassName String
	 */
	function _getReviewerFormClassName($selectionType) {
		switch ($selectionType) {
			case REVIEWER_SELECT_ADVANCED_SEARCH:
				return 'AdvancedSearchReviewerForm';
			case REVIEWER_SELECT_CREATE:
				return 'CreateReviewerForm';
			case REVIEWER_SELECT_ENROLL_EXISTING:
				return 'EnrollExistingReviewerForm';
		}
		assert(false);
	}

	/**
	 * Get operations that need a review assignment policy.
	 * @return array
	 */
	function _getReviewAssignmentOps() {
		// Define operations that need a review assignment policy.
		return array('readReview', 'reviewHistory', 'reviewRead', 'editThankReviewer', 'thankReviewer', 'editReminder', 'sendReminder', 'unassignReviewer', 'updateUnassignReviewer', 'sendEmail', 'unconsiderReview', 'editReview', 'updateReview');

	}

	/**
	 * Get operations that need a review round policy.
	 * @return array
	 */
	function _getReviewRoundOps() {
		// Define operations that need a review round policy.
		return array(
			'fetchGrid', 'fetchRow', 'showReviewerForm', 'reloadReviewerForm',
			'createReviewer', 'enrollReviewer', 'updateReviewer',
			'getUsersNotAssignedAsReviewers',
			'fetchTemplateBody'
		);
	}

	/**
	 * Update the review round status.
	 * @param $reviewAssignment ReviewAssignment
	 */
	function _updateReviewRoundStatus($reviewAssignment) {
		// Update the review round status.
		$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
		$reviewRound = $reviewRoundDao->getById($reviewAssignment->getReviewRoundId());
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignmentDao->updateObject($reviewAssignment);
		$reviewAssignments = $reviewAssignmentDao->getByReviewRoundId($reviewRound->getId());
		$reviewRoundDao->updateStatus($reviewRound, $reviewAssignments);
	}
}

?>

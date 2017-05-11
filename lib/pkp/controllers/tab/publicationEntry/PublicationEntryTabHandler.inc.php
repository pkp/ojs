<?php

/**
 * @file controllers/tab/publicationEntry/PublicationEntryTabHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublicationEntryTabHandler
 * @ingroup controllers_tab_catalogEntry
 *
 * @brief Base handler for AJAX operations for tabs on the Publication Entry management pages.
 */

// Import the base Handler.
import('classes.handler.Handler');

// Import classes for logging.
import('lib.pkp.classes.log.SubmissionLog');
import('classes.log.SubmissionEventLogEntry'); // app-specific.

class PublicationEntryTabHandler extends Handler {

	/** @var string */
	var $_currentTab;

	/** @var Submission object */
	var $_submission;

	/** @var int stageId */
	var $_stageId;

	/** @var int */
	var $_tabPosition;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER),
			array(
				'submissionMetadata',
				'saveForm',
			)
		);
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the current tab name.
	 * @return string
	 */
	function getCurrentTab() {
		return $this->_currentTab;
	}

	/**
	 * Set the current tab name.
	 * @param $currentTab string
	 */
	function setCurrentTab($currentTab) {
		$this->_currentTab = $currentTab;
	}


	//
	// Extended methods from Handler
	//
	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request) {
		$this->setCurrentTab($request->getUserVar('tab'));
		$this->_submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$this->_stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		$this->_tabPosition = (int) $request->getUserVar('tabPos');

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_APP_SUBMISSION);
		$this->setupTemplate($request);
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$stageId = (int) $request->getUserVar('stageId');
		import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy');
		$this->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $stageId));
		return parent::authorize($request, $args, $roleAssignments);
	}


	//
	// Public handler methods
	//
	/**
	 * Show the original submission metadata form.
	 * @param $request Request
	 * @param $args array
	 * @return JSONMessage JSON object
	 */
	function submissionMetadata($args, $request) {

		$publicationEntrySubmissionReviewForm = $this->_getPublicationEntrySubmissionReviewForm();

		$publicationEntrySubmissionReviewForm->initData($args, $request);
		return new JSONMessage(true, $publicationEntrySubmissionReviewForm->fetch($request));
	}

	/**
	 * @return the authorized submission for this handler
	 */
	function getSubmission() {
		return $this->_submission;
	}

	/**
	 * @return the authorized workflow stage id for this handler
	 */
	function getStageId() {
		return $this->_stageId;
	}

	/**
	 * @return the current tab position
	 */
	function getTabPosition() {
		return $this->_tabPosition;
	}


	/**
	 * Save the forms handled by this Handler.
	 * @param $request Request
	 * @param $args array
	 * @return string JSON message
	 */
	function saveForm($args, $request) {
		$json = new JSONMessage();
		$form = null;

		$submission = $this->getSubmission();
		$stageId = $this->getStageId();
		$notificationKey = null;

		$this->_getFormFromCurrentTab($form, $notificationKey, $request);

		if ($form) { // null if we didn't have a valid tab
			$form->readInputData();
			if($form->validate($request)) {
				$form->execute($request);
				// Create trivial notification in place on the form
				$notificationManager = new NotificationManager();
				$user = $request->getUser();
				$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __($notificationKey)));
			} else {
				// Could not validate; redisplay the form.
				$json->setStatus(true);
				$json->setContent($form->fetch($request));
			}

			if ($request->getUserVar('displayedInContainer')) {
				$router = $request->getRouter();
				$dispatcher = $router->getDispatcher();
				$url = $dispatcher->url($request, ROUTE_COMPONENT, null, $this->_getHandlerClassPath(), 'fetch', null, array('submissionId' => $submission->getId(), 'stageId' => $stageId, 'tabPos' => $this->getTabPosition(), 'hideHelp' => true));
				$json->setAdditionalAttributes(array('reloadContainer' => true, 'tabsUrl' => $url));
				$json->setContent(true); // prevents modal closure
			}
			return $json;
		} else {
			fatalError('Unknown or unassigned format id!');
		}
	}

	/**
	 * Get the form for a particular tab.
	 */
	function _getFormFromCurrentTab(&$form, &$notificationKey, $request) {
		switch ($this->getCurrentTab()) {
			case 'submission':
				$form = $this->_getPublicationEntrySubmissionReviewForm();
				$notificationKey = 'notification.savedSubmissionMetadata';
				break;
		}
	}

	/**
	 * Returns an instance of the form used for reviewing a submission's 'submission' metadata.
	 * @return PKPForm
	 */
	function _getPublicationEntrySubmissionReviewForm() {
		assert(false); // must be implemented in subclasses.
	}

	/**
	 * return a string to the Handler for this modal.
	 * @return String
	 */
	function _getHandlerClassPath() {
		assert(false); // in sub classes.
	}
}

?>

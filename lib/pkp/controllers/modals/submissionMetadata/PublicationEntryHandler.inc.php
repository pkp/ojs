<?php

/**
 * @file controllers/modals/submissionMetadata/PublicationEntryHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublicationEntryHandler
 * @ingroup controllers_modals_submissionMetadata
 *
 * @brief Base handler to generate the tab structure for a submission's publication metadata.
 */

// Import the base Handler.
import('classes.handler.Handler');

class PublicationEntryHandler extends Handler {

	/** The submission **/
	var $_submission;

	/** The current stage id **/
	var $_stageId;

	/** the current tab position **/
	var $_tabPosition;

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER),
			array('fetch', 'fetchFormatInfo'));
	}


	//
	// Overridden methods from Handler
	//
	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		$this->_submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$this->_stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		$this->_tabPosition = (int) $request->getUserVar('tabPos');

		// Load grid-specific translations
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
	// Getters and Setters
	//
	/**
	 * Get the Submission
	 * @return Submission
	 */
	function getSubmission() {
		return $this->_submission;
	}

	/**
	 * Get the stage id
	 * @return int
	 */
	function getStageId() {
		return $this->_stageId;
	}

	/**
	 * Get the current tab position
	 * @return int
	 */
	function getTabPosition() {
		return $this->_tabPosition;
	}

	//
	// Public handler methods
	//
	/**
	 * Display the tabs index page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function fetch($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'submissionId' => $this->getSubmission()->getId(),
			'stageId' => $this->getStageId(),
			'selectedTab' => (int) $this->getTabPosition(),
			'hideHelp' => (boolean) $request->getUserVar('hideHelp'),
		));
		$this->setupTemplate($request);
	}

	/**
	 * Returns a JSON response containing information regarding the formats enabled
	 * for this submission.
	 * @param $args array
	 * @param $request Request
	 */
	function fetchFormatInfo($args, $request) {
		assert(false); // provided in sub classes, submission-specific.
	}
}

?>

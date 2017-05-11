<?php

/**
 * @file controllers/modals/documentLibrary/DocumentLibraryHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DocumentLibraryHandler
 * @ingroup controllers_modals_documentLibrary
 *
 * @brief Submission document library modal handler.
 */

// Import the base Handler.
import('classes.handler.Handler');

class DocumentLibraryHandler extends Handler {

	/** The submission **/
	var $_submission;

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER, ROLE_ID_AUTHOR, ROLE_ID_ASSISTANT),
			array('documentLibrary'));
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
		$this->setupTemplate($request);
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
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

	//
	// Public handler methods
	//
	/**
	 * Display a list of the review form elements within a review form.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function documentLibrary($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('submission', $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION));
		return $templateMgr->fetchJson('controllers/modals/documentLibrary/documentLibrary.tpl');
	}
}

?>

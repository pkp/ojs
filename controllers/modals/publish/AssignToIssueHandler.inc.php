<?php

/**
 * @file controllers/modals/publish/AssignToIssueHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AssignToIssueHandler
 * @ingroup controllers_modals_publish
 *
 * @brief A handler to assign a publication to an issue before scheduling for
 *   publication
 */

// Import the base Handler.
import('classes.handler.Handler');

class AssignToIssueHandler extends Handler {

	/** @var Submission **/
	public $submission;

	/** @var Publication **/
	public $publication;

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			[ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER, ROLE_ID_ASSISTANT],
			['assign']
		);
	}


	//
	// Overridden methods from Handler
	//
	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);
		$this->submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$this->publication = $this->getAuthorizedContextObject(ASSOC_TYPE_PUBLICATION);
		$this->setupTemplate($request);
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
		import('lib.pkp.classes.security.authorization.PublicationAccessPolicy');
		$this->addPolicy(new PublicationAccessPolicy($request, $args, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}


	//
	// Public handler methods
	//
	/**
	 * Display a form to assign an issue to a publication
	 *
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function assign($args, $request) {
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_APP_SUBMISSION, LOCALE_COMPONENT_PKP_EDITOR, LOCALE_COMPONENT_APP_EDITOR);
		$templateMgr = TemplateManager::getManager($request);

		$submissionContext = $request->getContext();
		if (!$submissionContext || $submissionContext->getId() !== $this->submission->getData('contextId')) {
			$submissionContext = Services::get('context')->get($this->submission->getData('contextId'));
		}

		$publicationApiUrl = $request->getDispatcher()->url($request, ROUTE_API, $submissionContext->getPath(), 'submissions/' . $this->submission->getId() . '/publications/' . $this->publication->getId());
		$assignToIssueForm = new APP\components\forms\publication\AssignToIssueForm($publicationApiUrl, $this->publication, $submissionContext);
		$settingsData = [
			'components' => [
				FORM_ASSIGN_TO_ISSUE => $assignToIssueForm->getConfig(),
			],
		];

		$templateMgr->assign('assignData', $settingsData);

		return $templateMgr->fetchJson('controllers/modals/publish/assignToIssue.tpl');
	}
}

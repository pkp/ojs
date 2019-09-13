<?php

/**
 * @file api/v1/submissions/PKPSubmissionHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionHandler
 * @ingroup api_v1_submission
 *
 * @brief Handle API requests for submission operations.
 *
 */

import('lib.pkp.api.v1.submissions.PKPSubmissionHandler');
import('lib.pkp.classes.handler.APIHandler');
import('classes.core.Services');

class SubmissionHandler extends PKPSubmissionHandler {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->_handlerPath = 'submissions';
		$this->_endpoints = [
			'GET' => [
				[
					'pattern' => $this->getEndpointPattern(),
					'handler' => [$this, 'getMany'],
					'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_REVIEWER, ROLE_ID_AUTHOR],
				],
				[
					'pattern' => $this->getEndpointPattern() . '/{submissionId}',
					'handler' => [$this, 'get'],
					'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_REVIEWER, ROLE_ID_AUTHOR],
				],
				[
					'pattern' => $this->getEndpointPattern() . '/{submissionId}/participants',
					'handler' => [$this, 'getParticipants'],
					'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR],
				],
				[
					'pattern' => $this->getEndpointPattern() . '/{submissionId}/participants/{stageId}',
					'handler' => [$this, 'getParticipants'],
					'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR],
				],
				[
					'pattern' => $this->getEndpointPattern() . '/{submissionId}/publications',
					'handler' => [$this, 'getPublications'],
					'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_REVIEWER, ROLE_ID_AUTHOR],
				],
				[
					'pattern' => $this->getEndpointPattern() . '/{submissionId}/publications/{publicationId}',
					'handler' => [$this, 'getPublication'],
					'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_REVIEWER, ROLE_ID_AUTHOR],
				],
				[
					'pattern' => $this->getEndpointPattern() . '/{submissionId}/publications/{publicationId}/publish',
					'handler' => [$this, 'publishPublication'],
					'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR],
				],
			],
			'POST' => [
				[
					'pattern' => $this->getEndpointPattern(),
					'handler' => [$this, 'add'],
					'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR],
				],
				[
					'pattern' => $this->getEndpointPattern() . '/{submissionId}/publications',
					'handler' => [$this, 'addPublication'],
					'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR],
				],
				[
					'pattern' => $this->getEndpointPattern() . '/{submissionId}/publications/{publicationId}/version',
					'handler' => [$this, 'versionPublication'],
					'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR],
				],
			],
			'PUT' => [
				[
					'pattern' => $this->getEndpointPattern() . '/{submissionId}',
					'handler' => [$this, 'edit'],
					'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR],
				],
				[
					'pattern' => $this->getEndpointPattern() . '/{submissionId}/publications/{publicationId}',
					'handler' => [$this, 'editPublication'],
					'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR],
				],
				[
					'pattern' => $this->getEndpointPattern() . '/{submissionId}/publications/{publicationId}/publish',
					'handler' => [$this, 'publishPublication'],
					'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR],
				],
				[
					'pattern' => $this->getEndpointPattern() . '/{submissionId}/publications/{publicationId}/unpublish',
					'handler' => [$this, 'unpublishPublication'],
					'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR],
				],
			],
			'DELETE' => [
				[
					'pattern' => $this->getEndpointPattern() . '/{submissionId}',
					'handler' => [$this, 'delete'],
					'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR],
				],
				[
					'pattern' => $this->getEndpointPattern() . '/{submissionId}/publications/{publicationId}',
					'handler' => [$this, 'deletePublication'],
					'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT],
				],
			],
		];
		APIHandler::__construct();
	}

	//
	// Implement methods from PKPHandler
	//
	function authorize($request, &$args, $roleAssignments) {
		$routeName = $this->getSlimRequest()->getAttribute('route')->getName();

		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));

		$requiresSubmissionAccess = [
			'get',
			'edit',
			'delete',
			'getGalleys',
			'getParticipants',
			'getPublications',
			'getPublication',
			'addPublication',
			'versionPublication',
			'editPublication',
			'checkPublishRequirements',
			'publishPublication',
			'unpublishPublication',
			'deletePublication',
		];
		if (in_array($routeName, $requiresSubmissionAccess)) {
			import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
			$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
		}

		$requiresPublicationWriteAccess = [
			'addPublication',
			'editPublication',
			'checkPublishRequirements',
		];
		if (in_array($routeName, $requiresPublicationWriteAccess)) {
			import('lib.pkp.classes.security.authorization.PublicationWritePolicy');
			$this->addPolicy(new PublicationWritePolicy($request, $args, $roleAssignments));
		}

		$requiresProductionStageAccess = [
			'versionPublication',
			'publishPublication',
			'unpublishPublication',
			'deletePublication',
		];
		if (in_array($routeName, $requiresProductionStageAccess)) {
			// Can the user access this stage?
			import('lib.pkp.classes.security.authorization.internal.UserAccessibleWorkflowStageRequiredPolicy');
			$this->addPolicy(new UserAccessibleWorkflowStageRequiredPolicy($request));

			import('lib.pkp.classes.security.authorization.StageRolePolicy');
			$this->addPolicy(new StageRolePolicy([ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR]));
		}

		return APIHandler::authorize($request, $args, $roleAssignments);
	}

}

<?php

/**
 * @file api/v1/submission/SubmissionHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionHandler
 * @ingroup api_v1_submission
 *
 * @brief Handle API requests for submission operations.
 *
 */

import('lib.pkp.classes.handler.APIHandler');
import('classes.core.ServicesContainer');

class SubmissionHandler extends APIHandler {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->_handlerPath = 'submissions';
		$roles = array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_REVIEWER, ROLE_ID_AUTHOR);
		$this->_endpoints = array(
			'GET' => array (
				array(
					'pattern' => $this->getEndpointPattern() . '/{submissionId}/files',
					'handler' => array($this,'getFiles'),
					'roles' => $roles
				),
				array(
					'pattern' => $this->getEndpointPattern() . '/{submissionId}',
					'handler' => array($this,'submissionMetadata'),
					'roles' => $roles
				),
				array(
					'pattern' => $this->getEndpointPattern() . '/{submissionId}/participants',
					'handler' => array($this,'getParticipants'),
					'roles' => array(ROLE_ID_ASSISTANT, ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR)		// as per StageParticipantGridHandler::__construct()
				),
				array(
					'pattern' => $this->getEndpointPattern() . '/{submissionId}/galleys',
					'handler' => array($this,'getGalleys'),
					'roles' => $roles
				),
			),
		);
		parent::__construct();
	}

	//
	// Implement methods from PKPHandler
	//
	function authorize($request, &$args, $roleAssignments) {
		$routeName = null;
		$slimRequest = $this->getSlimRequest();
		
		if (!is_null($slimRequest) && ($route = $slimRequest->getAttribute('route'))) {
			$routeName = $route->getName();
		}
		
		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
		
		if (in_array($routeName, array('getFiles','getParticipants'))) {
			$stageId = $slimRequest->getQueryParam('stageId', WORKFLOW_STAGE_ID_SUBMISSION);
			import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy');
			$this->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $stageId));
		}
		
		if ($routeName == 'getGalleys') {
			import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy');
			$this->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', WORKFLOW_STAGE_ID_PRODUCTION));
		}
		
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Retrieve submission file list
	 *
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 * 
	 * @return Response
	 */
	public function getFiles($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$context = $request->getContext();
		$data = array();
		
		$sContainer = ServicesContainer::instance();
		$submissionService = $sContainer->get('submission');
		
		try {
			$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
			$fileStage = $slimRequest->getQueryParam('fileStage');
			$submissionFiles = $submissionService->getFiles($context->getId(), $submission, $fileStage);
			foreach ($submissionFiles as $submissionFile) {
				$entry = array(
					'fileId'           => $submissionFile->getFileId(),
					'revision'         => $submissionFile->getRevision(),
					'submissionId'     => $submissionFile->getSubmissionId(),
					'filename'         => $submissionFile->getName(),
					'fileLabel'        => $submissionFile->getFileLabel(),
					'fileStage'        => $submissionFile->getFileStage(),
					'uploaderUserId'   => $submissionFile->getUploaderUserId(),
					'userGroupId'      => $submissionFile->getUserGroupId()
				);
				if (is_a($submissionFile, 'SupplementaryFile')) {
				        $entry['metadata'] = array(
                                                'description'   => $submissionFile->getDescription(null),
                                                'creator'       => $submissionFile->getCreator(null),
                                                'publisher'     => $submissionFile->getPublisher(null),
                                                'source'        => $submissionFile->getSource(null),
                                                'subject'       => $submissionFile->getSubject(null),
                                                'sponsor'       => $submissionFile->getSponsor(null),
                                                'date'          => $submissionFile->getDateCreated(null),
                                                'language'      => $submissionFile->getLanguage(),
				        );
				}
				if (is_a($submissionFile, 'SubmissionArtworkFile')) {
				        $entry['metadata'] = array(
                                                'caption'               => $submissionFile->getCaption(),
                                                'credit'                => $submissionFile->getCredit(),
                                                'copyrightOwner'        => $submissionFile->getCopyrightOwner(),
                                                'permissionTerms'       => $submissionFile->getPermissionTerms(),
				        );
				}
				$data[] = $entry;
			}
		}
		catch (App\Services\Exceptions\InvalidSubmissionException $e) {
			return $response->withStatus(404)->withJsonError('api.submissions.404.resourceNotFound');
		}
		
		return $response->withJson($data, 200);
	}
	
	/**
	 * Retrieve participant list by stage
	 *
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 *
	 * @return Response
	 */
	public function getParticipants($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$context = $request->getContext();
		$data = array();
	
		$sContainer = ServicesContainer::instance();
		$submissionService = $sContainer->get('submission');
	
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$stageId = $slimRequest->getQueryParam('stageId', WORKFLOW_STAGE_ID_SUBMISSION);
		$data = $submissionService->getParticipantsByStage($context->getId(), $submission, $stageId);
	
		return $response->withJson($data, 200);
	}
	
	/**
	 * Retrieve galley list
	 *
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 *
	 * @return Response
	 */
	public function getGalleys($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$context = $request->getContext();
		$data = array();
	
		$sContainer = ServicesContainer::instance();
		$submissionService = $sContainer->get('submission');
	
		try {
			$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
			$data = $submissionService->getGalleys($context->getId(), $submission);
		}
		catch (App\Services\Exceptions\SubmissionStageNotValidException $e) {
			return $response->withStatus(400)->withJsonError('api.submissions.400.stageNotValid');
		}
	
		return $response->withJson($data, 200);
	}

	/**
	 * Get submission metadata
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 * @return Response
	 */
	public function submissionMetadata($slimRequest, $response, $args) {
		$request = $this->_request;
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		assert($submission);

		$queryParams = $slimRequest->getQueryParams();
		$format = isset($queryParams['format'])?$queryParams['format']:'';
		import('plugins.metadata.dc11.schema.Dc11Schema');
		if ($format == 'dc11' || $format == '') {
			$schema = new Dc11Schema();
			return $this->getMetadaJSON($submission, $schema);
		}
		import('plugins.metadata.mods34.schema.Mods34Schema');
		if ($format == 'mods34') {
			$schema = new Mods34Schema();
			return $this->getMetadaJSON($submission, $schema);
		}
	}

	function getMetadaJSON($submission, $schema) {
		$metadata = array();
		$dcDescription = $submission->extractMetadata($schema);
		foreach ($dcDescription->getProperties() as $propertyName => $property) {
			if ($dcDescription->hasStatement($propertyName)) {
				if ($property->getTranslated()) {
					$values = $dcDescription->getStatementTranslations($propertyName);
				} else {
					$values = $dcDescription->getStatement($propertyName);
				}
				$metadata[$propertyName][] = $values;
			}
		}
		return json_encode($metadata);
	}
}

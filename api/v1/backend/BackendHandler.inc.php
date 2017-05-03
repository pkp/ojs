<?php

/**
 * @file api/v1/backend/BackendHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BackendHandler
 * @ingroup api_v1_backend
 *
 * @brief Handle API requests for backend operations.
 *
 */

import('lib.pkp.classes.handler.APIHandler');

class BackendHandler extends APIHandler {

	/**
	 * Constructor
	 */
	public function __construct() {
		$rootPattern = '/{contextPath}/api/{version}/backend';
		$this->_endpoints = array(
			'GET' => array(
				array(
					'pattern' => "{$rootPattern}/submissions",
					'handler' => array($this, 'getSubmissions'),
					'roles' => array(
						ROLE_ID_SITE_ADMIN,
						ROLE_ID_MANAGER,
						ROLE_ID_SUB_EDITOR,
						ROLE_ID_AUTHOR,
						ROLE_ID_REVIEWER,
						ROLE_ID_ASSISTANT,
					),
				),
			),
			'DELETE' => array(
				array(
					'pattern' => "{$rootPattern}/submissions/{submissionId}",
					'handler' => array($this, 'deleteSubmission'),
					'roles' => array(
						ROLE_ID_SITE_ADMIN,
						ROLE_ID_MANAGER,
						ROLE_ID_AUTHOR,
					),
				),
			),
		);
		parent::__construct();
	}

	/**
	 * Get a list of submissions according to passed query parameters
	 *
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param $args array {
	 * 		@option string orderBy Supports `dateSubmitted` and `lastModified`.
	 *		  Default: `dateSubmitted`
	 * 		@option string orderDirection `ASC` or `DESC`. Default: `DESC`
	 * 		@option int assignedTo Return submissions assigned to this user ID.
	 *        A value of -1 will return unassigned submissions. Only journal
	 *		  managers and admins can view submissions they're not assigned to.
	 * 		@option int|array status Restrict results to specified statuses.
	 *		  By default it will return submissions in any status.
	 * 		@option string searchPhrase Return submissions matching the words in
	 *		  this string.
	 * 		@option int count Max submissions to return. Default: 20
	 * 		@option int offset Default: 0
	 * }
	 *
	 * @return Response
	 */
	public function getSubmissions($slimRequest, $response, $args) {

		$request = $this->getRequest();
		$currentUser = $request->getUser();
		$context = $request->getContext();

		// Merge query params over default params
		$defaultParams = array(
			'count' => 20,
			'offset' => 0,
		);

		$params = array_merge($defaultParams, $slimRequest->getQueryParams());

		// Anyone not a manager or site admin can only access their assigned
		// submissions
		if (!$currentUser->hasRole(array(ROLE_ID_MANAGER, ROLE_ID_SITE_ADMIN), $context->getId())) {
			$defaultParams['assignedTo'] = $currentUser->getId();
		}

		// Process query params to format incoming data as needed
		foreach ($params as $param => $val) {
			switch ($param) {

				// Always convert status to array
				case 'status':
					if (strpos($val, ',') > -1) {
						$val = explode(',', $val);
					} elseif (!is_array($val)) {
						$val = array($val);
					}
					$params[$param] = array_map('intval', $val);
					break;

				case 'assignedTo':
					$params[$param] = (int) $val;
					break;

				// Enforce a maximum count to prevent the API from crippling the
				// server
				case 'count':
					$params[$param] = min(100, (int) $val);
					break;

				case 'offset':
					$params[$param] = (int) $val;
					break;

				case 'orderBy':
					if (!in_array($val, array('id', 'dateSubmitted', 'lastModified'))) {
						unset($params[$param]);
					}
					break;

				case 'orderDirection':
					$params[$param] = $val === 'ASC' ? $val : 'DESC';
					break;
			}
		}

		// Prevent users from viewing submissions they're not assigned to,
		// except for journal managers and admins.
		if (!$currentUser->hasRole(array(ROLE_ID_MANAGER, ROLE_ID_SITE_ADMIN), $context->getId())
				&& $params['assignedTo'] != $currentUser->getId()) {
			return $response->withStatus(403)->withJsonError('api.submissions.403.requestedOthersUnpublishedSubmissions');
		}

		import('lib.pkp.classes.core.ServicesContainer');
		$submissions = ServicesContainer::instance()
				->get('submission')
				->getSubmissionList($context->getId(), $params);

		return $response->withJson($submissions);
	}

	/**
	 * Delete a submission
	 *
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 * @return Response
	 */
	public function deleteSubmission($slimRequest, $response, $args) {

		$request = Application::getRequest();
		$currentUser = $request->getUser();
		$context = $request->getContext();

		$submissionId = (int) $args['submissionId'];

		$submissionDao = Application::getSubmissionDAO();
		$submission = $submissionDao->getById($submissionId);

		if (!$submission) {
			return $response->withStatus(404)->withJsonError('api.submissions.404.resourceNotFound');
		}

		if ($context->getId() != $submission->getContextId()) {
			return $response->withStatus(403)->withJsonError('api.submissions.403.deleteSubmissionOutOfContext');
		}

		import('lib.pkp.classes.core.ServicesContainer');
		$submissionService = ServicesContainer::instance()
				->get('submission');

		if (!$submissionService->canCurrentUserDelete($submission)) {
			return $response->withStatus(403)->withJsonError('api.submissions.403.unauthorizedDeleteSubmission');
		}

		$submissionService->deleteSubmission($submissionId);

		return $response->withJson(true);
	}

}

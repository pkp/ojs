<?php

/**
 * @file api/v1/submission/SubmissionHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
					'pattern' => $this->getEndpointPattern(),
					'handler' => array($this, 'getSubmissionList'),
					'roles' => $roles
				),
				array(
					'pattern' => $this->getEndpointPattern() . '/{submissionId}',
					'handler' => array($this, 'getSubmission'),
					'roles' => $roles
				),
				array(
					'pattern' => $this->getEndpointPattern() . '/{submissionId}/galleys',
					'handler' => array($this, 'getGalleys'),
					'roles' => $roles
				),
				array(
					'pattern' => $this->getEndpointPattern() . '/{submissionId}/participants',
					'handler' => array($this, 'getParticipants'),
					'roles' => array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR),
				),
				array(
					'pattern' => $this->getEndpointPattern() . '/{submissionId}/participants/{stageId}',
					'handler' => array($this, 'getParticipants'),
					'roles' => array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR),
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

		if ($routeName === 'getSubmission' || $routeName === 'getGalleys' || $routeName === 'getParticipants') {
			import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
			$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Get a collection of submissions
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 *
	 * @return Response
	 */
	public function getSubmissionList($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$currentUser = $request->getUser();
		$dispatcher = $request->getDispatcher();
		$context = $request->getContext();
		$submissionService = ServicesContainer::instance()->get('submission');

		if (!$context) {
			return $response->withStatus(404)->withJsonError('api.submissions.404.resourceNotFound');
		}

		$params = $this->_buildListRequestParams($slimRequest);

		// Prevent users from viewing submissions they're not assigned to,
		// except for journal managers and admins.
		if (!$currentUser->hasRole(array(ROLE_ID_MANAGER, ROLE_ID_SITE_ADMIN), $context->getId())
				&& $params['assignedTo'] != $currentUser->getId()) {
			return $response->withStatus(403)->withJsonError('api.submissions.403.requestedOthersUnpublishedSubmissions');
		}

		$items = array();
		$submissions = $submissionService->getSubmissions($context->getId(), $params);
		if (!empty($submissions)) {
			$propertyArgs = array(
				'request' => $request,
				'slimRequest' => $slimRequest,
			);
			foreach ($submissions as $submission) {
				$items[] = $submissionService->getSummaryProperties($submission, $propertyArgs);
			}
		}

		$data = array(
			'itemsMax' => $submissionService->getSubmissionsMaxCount($context->getId(), $params),
			'items' => $items,
		);

		return $response->withJson($data, 200);
	}

	/**
	 * Get a single submission
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 *
	 * @return Response
	 */
	public function getSubmission($slimRequest, $response, $args) {
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_READER, LOCALE_COMPONENT_PKP_SUBMISSION);

		$request = $this->getRequest();
		$dispatcher = $request->getDispatcher();
		$context = $request->getContext();

		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);

		$data = ServicesContainer::instance()
			->get('submission')
			->getFullProperties($submission, array(
				'request' => $request,
				'slimRequest' 	=> $slimRequest
			));

		return $response->withJson($data, 200);
	}

	/**
	 * Get the galleys of a submission
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
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);

		if ($submission && $context) {
			$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
			$publishedArticle = $publishedArticleDao->getPublishedArticleByBestArticleId(
				(int) $context->getId(),
				$submission->getId(),
				true
			);
		}

		if (!$submission || !$publishedArticle) {
			return $response->withStatus(404)->withJsonError('api.submissions.404.resourceNotFound');
		}

		$data = array();

		$galleys = $publishedArticle->getGalleys();
		if (!empty($galleys)) {
			$galleyService = ServicesContainer::instance()->get('galley');
			$args = array(
				'request' => $request,
				'slimRequest' => $slimRequest,
				'parent' => $publishedArticle,
			);
			foreach ($galleys as $galley) {
				$data[] = $galleyService->getFullProperties($galley, $args);
			}
		}

		return $response->withJson($data, 200);
	}

	/**
	 * Get the participants assigned to a submission
	 *
	 * This does not return reviewers.
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
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$stageId = isset($args['stageId']) ? $args['stageId'] : null;

		if (!$submission) {
			return $response->withStatus(404)->withJsonError('api.submissions.404.resourceNotFound');
		}

		$data = array();

		$userService = ServicesContainer::instance()->get('user');

		$users = $userService->getUsers($context->getId(), array(
			'count' => 100, // high upper-limit
			'assignedToSubmission' => $submission->getId(),
			'assignedToSubmissionStage' => $stageId,
		));
		if (!empty($users)) {
			$args = array(
				'request' => $request,
				'slimRequest' => $slimRequest,
			);
			foreach ($users as $user) {
				$data[] = $userService->getSummaryProperties($user, $args);
			}
		}

		return $response->withJson($data, 200);
	}

	/**
	 * Convert params passed to list requests. Coerce type and only return
	 * white-listed params.
	 *
	 * @param $slimRequest Request Slim request object
	 * @return array
	 */
	private function _buildListRequestParams($slimRequest) {

		$request = $this->getRequest();
		$currentUser = $request->getUser();
		$context = $request->getContext();

		// Merge query params over default params
		$defaultParams = array(
			'count' => 20,
			'offset' => 0,
		);

		$requestParams = array_merge($defaultParams, $slimRequest->getQueryParams());

		// Anyone not a manager or site admin can only access their assigned
		// submissions
		if (!$currentUser->hasRole(array(ROLE_ID_MANAGER, ROLE_ID_SITE_ADMIN), $context->getId())) {
			$requestParams['assignedTo'] = $currentUser->getId();
		}

		$returnParams = array();

		// Process query params to format incoming data as needed
		foreach ($requestParams as $param => $val) {
			switch ($param) {

				case 'orderBy':
					if (in_array($val, array('dateSubmitted', 'lastModified', 'title'))) {
						$returnParams[$param] = $val;
					}
					break;

				case 'orderDirection':
					$returnParams[$param] = $val === 'ASC' ? $val : 'DESC';
					break;

				// Always convert status and stageIds to array
				case 'status':
				case 'stageIds':
					if (is_string($val) && strpos($val, ',') > -1) {
						$val = explode(',', $val);
					} elseif (!is_array($val)) {
						$val = array($val);
					}
					$returnParams[$param] = array_map('intval', $val);
					break;

				case 'assignedTo':
					$returnParams[$param] = (int) $val;
					break;

				case 'searchPhrase':
					$returnParams[$param] = $val;
					break;

				// Enforce a maximum count to prevent the API from crippling the
				// server
				case 'count':
					$returnParams[$param] = min(100, (int) $val);
					break;

				case 'offset':
					$returnParams[$param] = (int) $val;
					break;

				case 'isIncomplete':
				case 'isOverdue':
					$returnParams[$param] = true;
					break;
			}
		}

		\HookRegistry::call('API::submissions::params', array(&$returnParams, $slimRequest));

		return $returnParams;
	}
}

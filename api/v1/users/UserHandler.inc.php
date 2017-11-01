<?php

/**
 * @file api/v1/user/UserHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserHandler
 * @ingroup api_v1_user
 *
 * @brief Handle API requests for user operations.
 *
 */

import('lib.pkp.classes.handler.APIHandler');
import('classes.core.ServicesContainer');

class UserHandler extends APIHandler {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->_handlerPath = 'users';
		$roles = array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER);
		$this->_endpoints = array(
			'GET' => array (
				array(
					'pattern' => $this->getEndpointPattern(),
					'handler' => array($this, 'getUsers'),
					'roles' => $roles
				),
				array(
					'pattern' => $this->getEndpointPattern() . '/{userId}',
					'handler' => array($this, 'getUser'),
					'roles' => $roles
				),
			),
		);
		parent::__construct();
	}

	/**
	 * Get a collection of users
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 *
	 * @return Response
	 */
	public function getUsers($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$context = $request->getContext();
		$userService = ServicesContainer::instance()->get('user');

		if (!$context) {
			return $response->withStatus(404)->withJsonError('api.submissions.404.resourceNotFound');
		}

		$params = $this->_buildListRequestParams($slimRequest);

		$items = array();
		$users = $userService->getUsers($context->getId(), $params);
		if (!empty($users)) {
			$propertyArgs = array(
				'request' => $request,
				'slimRequest' => $slimRequest,
			);
			foreach ($users as $user) {
				$items[] = $userService->getSummaryProperties($user, $propertyArgs);
			}
		}

		$data = array(
			'maxItems' => $userService->getUsersMaxCount($context->getId(), $params),
			'items' => $items,
		);

		return $response->withJson($data, 200);
	}

	/**
	 * Get a single user
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 *
	 * @return Response
	 */
	public function getUser($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$dispatcher = $request->getDispatcher();
		$context = $request->getContext();
		$userService = ServicesContainer::instance()->get('user');

		if (!empty($args['userId'])) {
			$user = $userService->getUser((int) $args['userId']);
		}

		if (!$user) {
			return $response->withStatus(404)->withJsonError('api.submissions.404.resourceNotFound');
		}

		$data = $userService->getFullProperties($user, array(
			'request' => $request,
			'slimRequest' 	=> $slimRequest
		));

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

		$returnParams = array();

		// Process query params to format incoming data as needed
		foreach ($requestParams as $param => $val) {
			switch ($param) {

				case 'orderBy':
					if (in_array($val, array('id', 'familyName', 'givenName'))) {
						$returnParams[$param] = $val;
					}
					break;

				case 'orderDirection':
					$returnParams[$param] = $val === 'ASC' ? $val : 'DESC';
					break;

				case 'status':
					if (in_array($val, array('all', 'active', 'disabled'))) {
						$returnParams[$param] = $val;
					}
					break;

				// Always convert status and roleIds to array
				case 'roleIds':
					if (is_string($val) && strpos($val, ',') > -1) {
						$val = explode(',', $val);
					} elseif (!is_array($val)) {
						$val = array($val);
					}
					$returnParams[$param] = array_map('intval', $val);
					break;

				case 'assignedToSubmission':
					$returnParams[$param] = (int) $val;
					break;

				case 'assignedToSection':
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
			}
		}

		\HookRegistry::call('API::users::params', array(&$returnParams, $slimRequest));

		return $returnParams;
	}
}

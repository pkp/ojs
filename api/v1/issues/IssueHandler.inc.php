<?php

/**
 * @file api/v1/issues/IssueHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueHandler
 * @ingroup api_v1_issues
 *
 * @brief Handle API requests for issues operations.
 *
 */

import('lib.pkp.classes.handler.APIHandler');
import('classes.core.Services');

class IssueHandler extends APIHandler {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->_handlerPath = 'issues';
		$roles = array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_REVIEWER, ROLE_ID_AUTHOR);
		$this->_endpoints = array(
			'GET' => array (
				array(
					'pattern' => $this->getEndpointPattern(),
					'handler' => array($this, 'getMany'),
					'roles' => $roles
				),
				array(
					'pattern' => $this->getEndpointPattern().  '/current',
					'handler' => array($this, 'getCurrent'),
					'roles' => $roles
				),
				array(
					'pattern' => $this->getEndpointPattern().  '/{issueId}',
					'handler' => array($this, 'get'),
					'roles' => $roles
				),
			)
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

		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request));

		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));

		import('classes.security.authorization.OjsJournalMustPublishPolicy');
		$this->addPolicy(new OjsJournalMustPublishPolicy($request));

		if ($routeName === 'get') {
			import('classes.security.authorization.OjsIssueRequiredPolicy');
			$this->addPolicy(new OjsIssueRequiredPolicy($request, $args));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}

	//
	// Public handler methods
	//
	/**
	 * Get a collection of issues
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 * @return Response
	 */
	public function getMany($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$currentUser = $request->getUser();
		$context = $request->getContext();
		$issueService = Services::get('issue');

		if (!$context) {
			return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
		}

		$defaultParams = array(
			'count' => 20,
			'offset' => 0,
		);

		$requestParams = array_merge($defaultParams, $slimRequest->getQueryParams());

		$params = array();

		// Process query params to format incoming data as needed
		foreach ($requestParams as $param => $val) {
			switch ($param) {

				case 'orderBy':
					if (in_array($val, array('datePublished', 'lastModified', 'seq'))) {
						$params[$param] = $val;
					}
					break;

				case 'orderDirection':
					$params[$param] = $val === 'ASC' ? $val : 'DESC';
					break;

				// Enforce a maximum count to prevent the API from crippling the
				// server
				case 'count':
					$params[$param] = min(100, (int) $val);
					break;

				case 'offset':
					$params[$param] = (int) $val;
					break;

				// Always convert volume, number and year values to array
				case 'volumes':
				case 'volume':
				case 'numbers':
				case 'number':
				case 'years':
				case 'year':

					// Support deprecated `year`, `number` and `volume` params
					if (substr($param, -1) !== 's') {
						$param .= 's';
					}

					if (is_string($val) && strpos($val, ',') > -1) {
						$val = explode(',', $val);
					} elseif (!is_array($val)) {
						$val = array($val);
					}
					$params[$param] = array_map('intval', $val);
					break;

				case 'isPublished':
					$params[$param] = $val ? true : false;
					break;
			}
		}

		$params['contextId'] = $context->getId();

		\HookRegistry::call('API::issues::params', array(&$params, $slimRequest));

		// You must be a manager or site admin to access unpublished Issues
		$isAdmin = $currentUser->hasRole(array(ROLE_ID_MANAGER), $context->getId()) || $currentUser->hasRole(array(ROLE_ID_SITE_ADMIN), CONTEXT_SITE);
		if (isset($params['isPublished']) && !$params['isPublished'] && !$isAdmin) {
			return $response->withStatus(403)->withJsonError('api.submissions.403.unpublishedIssues');
		} elseif (!$isAdmin) {
			$params['isPublished'] = true;
		}

		$items = array();
		$issues = $issueService->getMany($params);
		if (!empty($issues)) {
			$propertyArgs = array(
				'request' => $request,
				'slimRequest' => $slimRequest,
			);
			foreach ($issues as $issue) {
				$items[] = $issueService->getSummaryProperties($issue, $propertyArgs);
			}
		}

		$data = array(
			'itemsMax' => $issueService->getMax($params),
			'items' => $items,
		);

		return $response->withJson($data, 200);
	}

	/**
	 * Get the current issue
	 *
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 *
	 * @return Response
	 */
	public function getCurrent($slimRequest, $response, $args) {

		$request = $this->getRequest();
		$context = $request->getContext();

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getCurrent($context->getId());

		if (!$issue) {
			return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
		}

		$data = Services::get('issue')->getFullProperties($issue, array(
			'request' => $request,
			'slimRequest' => $slimRequest,
		));

		return $response->withJson($data, 200);
	}

	/**
	 * Get a single issue
	 *
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 *
	 * @return Response
	 */
	public function get($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);

		if (!$issue) {
			return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
		}

		$data = Services::get('issue')->getFullProperties($issue, array(
			'request' => $request,
			'slimRequest' => $slimRequest,
		));

		return $response->withJson($data, 200);
	}
}

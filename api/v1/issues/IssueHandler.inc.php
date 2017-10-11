<?php

/**
 * @file api/v1/issues/IssueHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueHandler
 * @ingroup api_v1_issues
 *
 * @brief Handle API requests for issues operations.
 *
 */

import('lib.pkp.classes.handler.APIHandler');
import('classes.core.ServicesContainer');

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
					'handler' => array($this, 'getIssueList'),
					'roles' => $roles
				),
				array(
					'pattern' => $this->getEndpointPattern().  '/current',
					'handler' => array($this, 'getCurrentIssue'),
					'roles' => $roles
				),
				array(
					'pattern' => $this->getEndpointPattern().  '/{issueId}',
					'handler' => array($this, 'getIssue'),
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

		import('classes.security.authorization.OjsJournalMustPublishPolicy');
		$this->addPolicy(new OjsJournalMustPublishPolicy($request));

		if ($routeName === 'getIssue') {
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
	public function getIssueList($slimRequest, $response, $args) {

		$request = $this->getRequest();
		$context = $request->getContext();
		$data = array();

		$volume = $this->getParameter('volume', null);
		$number = $this->getParameter('number', null);
		$year = $this->getParameter('year', null);

		if (($volume && !ctype_digit($volume))
				|| ($number && !is_string($number))
				|| ($year && !ctype_digit($year))) {
			return $response->withStatus(400)->withJsonError('api.submissions.400.invalidIssueIdentifiers');
		}

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issues = $issueDao->getPublishedIssuesByNumber($context->getId(), $volume, $number, $year);

		while ($issue = $issues->next()) {
			$data[] = ServicesContainer::instance()
					->get('issue')
					->getSummaryProperties($issue, array(
						'request' => $request,
						'slimRequest' => $slimRequest,
					));
		}

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
	public function getCurrentIssue($slimRequest, $response, $args) {

		$request = $this->getRequest();
		$context = $request->getContext();

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getCurrent($context->getId());

		if (!$issue) {
			return $response->withStatus(404)->withJsonError('api.submissions.404.resourceNotFound');
		}

		$data = ServicesContainer::instance()
			->get('issue')
			->getFullProperties($issue, array(
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
	public function getIssue($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);

		if (!$issue) {
			return $response->withStatus(404)->withJsonError('api.submissions.404.resourceNotFound');
		}

		$data = ServicesContainer::instance()
				->get('issue')
				->getFullProperties($issue, array(
					'request' => $request,
					'slimRequest' => $slimRequest,
				));

		return $response->withJson($data, 200);
	}
}

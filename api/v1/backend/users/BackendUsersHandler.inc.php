<?php

/**
 * @file api/v1/backend/users/BackendUsersHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BackendUsersHandler
 * @ingroup api_v1_backend
 *
 * @brief Handle API requests for backend operations.
 *
 */

import('lib.pkp.classes.handler.APIHandler');
import('classes.core.ServicesContainer');

class BackendUsersHandler extends APIHandler {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->_handlerPath = 'backend';
		$this->_endpoints = array(
			'GET' => array(
				array(
					'pattern' => $this->getEndpointPattern() . '/users',
					'handler' => array($this, 'getUserList'),
					'roles' => array(
						ROLE_ID_MANAGER,
					),
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

		if ($routeName == 'getUserList') {
			import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
			$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}
	
	/**
	 * Get user list
	 *
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 *
	 * @return Response
	 */
	public function getUserList($slimRequest, $response, $args) {
		import('lib.pkp.classes.user.InterestManager');
		$interestManager = new InterestManager();
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_PKP_USER);

		$request = $this->getRequest();
		$currentUser = $request->getUser();
		$context = $request->getContext();
		$contextId = $context->getId();
		$route = $slimRequest->getAttribute('route');

		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$rangeInfo = parent::getRangeInfo($request, $route->getName());

		$results = $userGroupDao->getUsersById(
			$this->getParameter('userGroupId', null),
			($this->getParameter('includeNoRole') === 'true') ? null : $contextId,
			$this->getParameter('searchField', null),
			$this->getParameter('search', null),
			$this->getParameter('searchMatch', null),
			$rangeInfo
		);

		$items = array();
		$users = $results->toArray();
		$roleDao = DAORegistry::getDAO('RoleDAO');
		$roleNames = Application::getRoleNames(true);
		foreach($users as $user) {
			$userRoles = array();
			$roles = $roleDao->getByUserId($user->getId(), $contextId);
			$userRoles = array_map(function($role) use($roleNames)
			{
				$roleId = $role->getId();
				return array(
						'id' 	=> $roleId,
						'name' 	=> __($roleNames[$roleId]),
				);
			}, $roles);
			
			$items[] = array(
				'id' 					=> $user->getId(),
				'userName' 				=> $user->getUsername(),
				'fullName' 				=> $user->getFullName(),
				'firstName' 			=> $user->getFirstName(),
				'middleName' 			=> $user->getMiddleName(),
				'lastName' 				=> $user->getLastName(),
				'email' 				=> $user->getEmail(),
				'suffix' 				=> $user->getSuffix(),
				'country' 				=> $user->getCountry(),
				'orcid'					=> $user->getOrcid(),
				'url'					=> $user->getUrl(),
				'affiliation'			=> $user->getAffiliation(null),
				'initials'				=> $user->getInitials(),
				'signature'				=> $user->getSignature(null),
				'gender' 				=> $user->getGender(),
				'userUrl' 				=> $user->getUrl(),
				'phone' 				=> $user->getPhone(),
				'mailingAddress' 		=> $user->getMailingAddress(),
				'biography' 			=> $user->getBiography(null),
				'interests' 			=> $interestManager->getInterestsForUser($user),
				'userLocales' 			=> $user->getLocales(),
				'roles' 				=> $userRoles,
			);
		}
		
		return $response->withJson($items);
	}
}
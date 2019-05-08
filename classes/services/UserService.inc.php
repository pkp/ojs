<?php

/**
 * @file classes/services/UserService.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserService
 * @ingroup services
 *
 * @brief Extends the base user helper service class with app-specific
 *  requirements.
 */

namespace APP\Services;

class UserService extends \PKP\Services\PKPUserService {

	/**
	 * Initialize hooks for extending PKPUserService
	 */
	public function __construct() {
		\HookRegistry::register('API::users::params', array($this, 'modifyAPIUsersParams'));
		\HookRegistry::register('User::getMany::queryBuilder', array($this, 'modifyUserQueryBuilder'));
		\HookRegistry::register('User::getMany::queryObject', array($this, 'modifyUserQueryObject'));
	}

	/**
	 * Collect and sanitize request params for the /users API endpoint
	 *
	 * @param $hookName string
	 * @param $args array [
	 *		@option array $returnParams
	 *		@option SlimRequest $slimRequest
	 * ]
	 */
	public function modifyAPIUsersParams($hookName, $args) {
		$returnParams =& $args[0];
		$slimRequest = $args[1];
		$requestParams = $slimRequest->getQueryParams();

		if (!empty($requestParams['assignedToSection'])) {
			$returnParams['assignedToSection'] = (int) $requestParams['assignedToSection'];
		}
	}

	/**
	 * Run app-specific query builder methods for getMany
	 *
	 * @param $hookName string
	 * @param $args array [
	 *		@option \APP\Services\QueryBuilders\UserQueryBuiler
	 *		@option array Request args
	 * ]
	 */
	public function modifyUserQueryBuilder($hookName, $args) {
		$userQueryBuilder = $args[0];
		$requestArgs = $args[1];

		if (!empty($requestArgs['assignedToSection'])) {
			$userQueryBuilder->assignedToSection($requestArgs['assignedToSection']);
		}
	}

	/**
	 * Add app-specific query statements to the list get query
	 *
	 * @param $hookName string
	 * @param $args array [
	 *		@option object $queryObject
	 *		@option \APP\Services\QueryBuilders\UserQueryBuilder $queryBuilder
	 * ]
	 */
	public function modifyUserQueryObject($hookName, $args) {
		$queryObject =& $args[0];
		$queryBuilder = $args[1];

		$queryBuilder->appGet($queryObject);
	}
}

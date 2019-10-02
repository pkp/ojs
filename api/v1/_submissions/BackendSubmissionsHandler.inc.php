<?php

/**
 * @file api/v1/_submissions/BackendSubmissionsHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BackendSubmissionsHandler
 * @ingroup api_v1_backend
 *
 * @brief Handle API requests for backend operations.
 *
 */

import('lib.pkp.api.v1._submissions.PKPBackendSubmissionsHandler');

class BackendSubmissionsHandler extends PKPBackendSubmissionsHandler {

	/**
	 * Constructor
	 */
	public function __construct() {
		\HookRegistry::register('API::_submissions::params', array($this, 'addAppSubmissionsParams'));
		parent::__construct();
	}

	/**
	 * Add ojs-specific parameters to the getMany request
	 *
	 * @param $hookName string
	 * @param $args array [
	 * 		@option $params array
	 * 		@option $slimRequest Request Slim request object
	 * 		@option $response Response object
	 * ]
	 */
	public function addAppSubmissionsParams($hookName, $args) {
		$params =& $args[0];
		$slimRequest = $args[1];
		$response = $args[2];

		$originalParams = $slimRequest->getQueryParams();

		if (!empty($originalParams['sectionIds'])) {
			if (is_array($originalParams['sectionIds'])) {
				$params['sectionIds'] = array_map('intval', $originalParams['sectionIds']);
			} else {
				$params['sectionIds'] = array((int) $originalParams['sectionIds']);
			}
		}
	}
}

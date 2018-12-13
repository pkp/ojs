<?php

/**
 * @file api/v1/stats/StatsHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StatsHandler
 * @ingroup api_v1_stats
 *
 * @brief Handle API requests for statistics operations.
 *
 */

import('lib.pkp.api.v1.stats.PKPStatsHandler');
import('classes.core.ServicesContainer');

class StatsHandler extends PKPStatsHandler {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->_handlerPath = 'stats';
		$roles = array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER);
		$this->_endpoints = array(
			'GET' => array (
				array(
					'pattern' => $this->getEndpointPattern() . '/articles',
					'handler' => array($this, 'getSubmissionList'),
					'roles' => $roles
				),
				array(
					'pattern' => $this->getEndpointPattern() . '/articles/{submissionId}',
					'handler' => array($this, 'getSubmission'),
					'roles' => $roles
				),
			),
		);
		parent::__construct();
	}

}
